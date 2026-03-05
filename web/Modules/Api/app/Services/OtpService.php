<?php

namespace Modules\Api\Services;

use Exception;
use Illuminate\Support\Facades\Hash;

use App\Models\BaseModel;
use App\Utils\SqlHandler;
use Modules\Admin\Models\Role;
use Modules\Admin\Models\User;
use Modules\Api\Models\ApiRegRequest;
use Modules\Api\Models\OtpManagement;
use Modules\Logging\Utils\LogHandler;

/**
 * Service for handling OTP operations (create, verify, send email) and password reset.
 */
class OtpService {
    /**
     * OTP code length.
     */
    public const OTP_CODE_LENGTH = 6;

    /**
     * Get OTP expiration time in minutes for specific OTP type.
     *
     * @param  int|null  $otpType  OTP type (OtpManagement::TYPE_REGISTER, OtpManagement::TYPE_FORGOT_PASSWORD, etc.)
     * @return int
     */
    public static function getOtpExpirationMinutes(?int $otpType = null): int {
        if ($otpType === null) {
            return (int) config('api.otp.expiration_minutes.default', 30);
        }

        $typeKey    = self::getOtpTypeConfigKey($otpType);
        $expiration = config("api.otp.expiration_minutes.{$typeKey}", null);

        // Fallback to default if specific type not found
        return $expiration !== null
            ? (int) $expiration
            : (int) config('api.otp.expiration_minutes.default', 30);
    }

    /**
     * Get config key for OTP type.
     *
     * @param  int  $otpType
     * @return string
     */
    private static function getOtpTypeConfigKey(int $otpType): string {
        $typeMap = [
            OtpManagement::TYPE_REGISTER        => 'register',
            OtpManagement::TYPE_FORGOT_PASSWORD => 'forgot_password',
        ];

        return $typeMap[$otpType] ?? 'default';
    }

    /**
     * Find user by email.
     *
     * @param  string  $email
     * @return User|null
     */
    public static function findUserByEmail(string $email): ?User {
        try {
            return User::query()
                ->where('email', $email)
                ->whereIn('status', [User::STATUS_ACTIVE, User::STATUS_REGISTER_REQUEST])
                ->first();
        } catch (Exception $e) {
            LogHandler::error('Failed to find user by email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return null;
        }
    }

    /**
     * Generate random OTP code.
     *
     * @return string
     */
    public static function generateOtpCode(): string {
        return str_pad((string) random_int(0, 999999), self::OTP_CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Create OTP record for user.
     *
     * @param  User  $user
     * @param  int  $type
     * @param  int  $platform
     * @param  string  $version
     * @return OtpManagement|null
     */
    public static function createOtp(User $user, int $type, int $platform, string $version): ?OtpManagement {
        try {
            // Disable old OTP
            self::invalidateOldOtpsForEmail($user->email, $type);
            $otpCode   = self::generateOtpCode();
            $expiresAt = now()->addMinutes(self::getOtpExpirationMinutes($type));

            return OtpManagement::create([
                'user_id'    => $user->id,
                'type'       => $type, // 1: register, 2: forgot_password
                'email'      => $user->email,
                'otp_code'   => $otpCode,
                'expires_at' => $expiresAt,
                'platform'   => $platform,
                'version'    => $version,
            ]);
        } catch (Exception $e) {
            LogHandler::error('Failed to create OTP', [
                'user_id'  => $user->id,
                'platform' => $platform,
                'version'  => $version,
                'error'    => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return null;
        }
    }

    /**
     * Verify OTP code for email.
     *
     * @param  string  $email
     * @param  int  $type
     * @param  string  $otpCode
     * @return array{valid: bool, otp: OtpManagement|null, message: string}
     */
    public static function verifyOtp(string $email, int $type, string $otpCode): array {
        try {
            $user = self::findUserByEmail($email);
            if (!$user) {
                return [
                    'valid'   => false,
                    'otp'     => null,
                    'message' => 'User not found',
                ];
            }

            $otp = OtpManagement::findValidOtpForEmail($email, $type, $otpCode);
            if (!$otp) {
                return [
                    'valid'   => false,
                    'otp'     => null,
                    'message' => 'Invalid or expired OTP code',
                ];
            }

            $otp->verified_at = now();
            $otp->save();

            return [
                'valid'   => true,
                'otp'     => $otp,
                'message' => 'OTP is valid',
            ];
        } catch (Exception $e) {
            LogHandler::error('Failed to verify OTP', [
                'email' => $email,
                'error' => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return [
                'valid'   => false,
                'otp'     => null,
                'message' => 'An error occurred while verifying OTP',
            ];
        }
    }

    /**
     * Reset password using OTP.
     * Activates temporary users (STATUS_REGISTER_REQUEST) or updates password for existing ACTIVE users.
     *
     * @param  string  $email
     * @param  int  $type
     * @param  string  $newPassword
     * @param  string  $otpCode
     * @return array{success: bool, message: string}
     */
    public static function resetPassword(string $email, int $type, string $newPassword, string $otpCode): array {
        try {
            // Verify OTP first
            $otp = OtpManagement::findValidOtpForEmail($email, $type, $otpCode);
            if (!$otp) {
                return [
                    'success' => false,
                    'message' => 'Time out',
                ];
            }

            // Check if user exists (any status)
            $user = User::where('email', $email)->first();

            if (!$user) {
                // This shouldn't happen in the new flow, but keep as fallback
                LogHandler::warning('Reset password failed: User not found', [
                    'email' => $email,
                ], LogHandler::CHANNEL_API);

                return [
                    'success' => false,
                    'message' => 'User not found',
                ];
            }

            // Check if user is temporary (STATUS_REGISTER_REQUEST)
            if ($user->status === User::STATUS_REGISTER_REQUEST) {
                // Activate temporary user
                $user->password          = Hash::make($newPassword);
                $user->status            = User::STATUS_ACTIVE;
                $user->email_verified_at = now();
                $user->save();

                // Update ApiRegRequest status to ACTIVE
                $regRequest = ApiRegRequest::where('email', $email)
                    ->where('status', ApiRegRequest::STATUS_REGISTER_REQUEST)
                    ->first();

                if ($regRequest) {
                    $regRequest->status   = ApiRegRequest::STATUS_ACTIVE;
                    $regRequest->password = Hash::make($newPassword);
                    $regRequest->save();
                }

                LogHandler::info('User activated via forgot password', [
                    'email'   => $email,
                    'user_id' => $user->id,
                ], LogHandler::CHANNEL_API);
            } else {
                // Update password for existing ACTIVE user
                $user->password = Hash::make($newPassword);
                $user->save();

                LogHandler::info('Password reset successfully', [
                    'email'   => $email,
                    'user_id' => $user->id,
                ], LogHandler::CHANNEL_API);
            }

            $otp->used_at = now();
            $otp->save();

            // Invalidate other old OTPs
            self::invalidateOldOtpsForEmail($email, $type);

            return [
                'success' => true,
                'message' => 'Reset password successfully',
            ];
        } catch (Exception $e) {
            LogHandler::error('Failed to reset password', [
                'email' => $email,
                'error' => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return [
                'success' => false,
                'message' => 'An error occurred while resetting password',
            ];
        }
    }

    /**
     * Send OTP email to user.
     *
     * @param  OtpManagement  $otp
     * @param  string|null  $viewName  View name (e.g., 'api::email.register-otp'). If null, will use default based on OTP type.
     * @param  string|null  $subject  Email subject. If null, will use default based on OTP type.
     * @param  array  $additionalData  Additional data to pass to view
     * @return bool
     */
    public static function sendOtpEmail(OtpManagement $otp, ?string $viewName = null, ?string $subject = null, array $additionalData = []): bool {
        try {
            $user = $otp->rUser;
            if (!$user) {
                LogHandler::error('Failed to send OTP email: User not found', [
                    'otp_id' => $otp->id,
                ], LogHandler::CHANNEL_API);

                return false;
            }

            $expiresIn   = self::getOtpExpirationMinutes($otp->type);
            $appName     = (string) config('app.name', 'Citrine');
            $minutesText = $expiresIn . ' minute' . ($expiresIn > 1 ? 's' : '');

            // Use provided view name and subject, or get defaults
            $viewName = $viewName ?? self::getDefaultOtpEmailView($otp->type);
            $subject  = $subject ?? self::getDefaultOtpEmailSubject($otp->type);

            // Prepare data for email template
            $data = array_merge([
                'email'       => $otp->email,
                'otpCode'     => $otp->otp_code,
                'expiresIn'   => $expiresIn,
                'minutesText' => $minutesText,
                'appName'     => $appName,
            ], $additionalData);

            // Send email using EmailService
            return EmailService::sendEmailFromView($user->email, $subject, $viewName, $data);
        } catch (Exception $e) {
            LogHandler::error('Failed to send OTP email', [
                'otp_id' => $otp->id,
                'error'  => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return false;
        }
    }

    /**
     * Get default email view name for OTP type.
     *
     * @param  int  $otpType
     * @return string
     */
    private static function getDefaultOtpEmailView(int $otpType): string {
        $defaultViews = [
            OtpManagement::TYPE_REGISTER        => 'api::email.register-otp',
            OtpManagement::TYPE_FORGOT_PASSWORD => 'api::email.forgot-password-otp',
        ];

        return $defaultViews[$otpType] ?? 'api::email.otp';
    }

    /**
     * Get default email subject for OTP type.
     *
     * @param  int  $otpType
     * @return string
     */
    private static function getDefaultOtpEmailSubject(int $otpType): string {
        $defaultSubjects = [
            OtpManagement::TYPE_REGISTER        => 'Register OTP Code',
            OtpManagement::TYPE_FORGOT_PASSWORD => 'Password Reset OTP Code',
        ];

        return $defaultSubjects[$otpType] ?? 'OTP Code';
    }

    /**
     * Find user by email, create OTP, and send OTP email.
     * Common flow for operations that require OTP generation and email sending.
     *
     * @param  string  $email
     * @param  int  $otpType
     * @param  int  $platform
     * @param  string  $version
     * @return array{success: bool, user: User|null, otp: OtpManagement|null, message: string}
     */
    public static function findUserCreateAndSendOtp(string $email, int $otpType, int $platform, string $version): array {
        try {
            $user = self::findUserByEmail($email);
            if (!$user) {
                return [
                    'success' => false,
                    'user'    => null,
                    'otp'     => null,
                    'message' => 'Incorrect information',
                ];
            }

            $otp = self::createOtp($user, $otpType, $platform, $version);
            if (!$otp) {
                return [
                    'success' => false,
                    'user'    => $user,
                    'otp'     => null,
                    'message' => 'An error occurred. Please try again later.',
                ];
            }

            $emailSent = self::sendOtpEmail($otp);
            if (!$emailSent) {
                LogHandler::warning('Failed to send OTP email', [
                    'user_id' => $user->id,
                    'otp_id'  => $otp->id,
                ], LogHandler::CHANNEL_API);
            }

            return [
                'success' => true,
                'user'    => $user,
                'otp'     => $otp,
                'message' => 'OTP has been sent successfully',
            ];
        } catch (Exception $e) {
            LogHandler::error('Failed to find user, create and send OTP', [
                'email' => $email,
                'type'  => $otpType,
                'error' => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return [
                'success' => false,
                'user'    => null,
                'otp'     => null,
                'message' => 'An error occurred. Please try again later.',
            ];
        }
    }

    /**
     * Create temporary user with STATUS_REGISTER_REQUEST, ApiRegRequest, OTP and send email.
     * Common method for register and forgot password flows.
     *
     * @param  string  $email
     * @param  string  $password  Hashed password (can be empty string for forgot password)
     * @param  int  $otpType  OTP type (OtpManagement::TYPE_REGISTER, OtpManagement::TYPE_FORGOT_PASSWORD)
     * @param  int  $platform  Platform integer
     * @param  string  $version  Version string
     * @return array{success: bool, user: User|null, regRequest: ApiRegRequest|null, otp: OtpManagement|null, message: string}
     */
    public static function createTemporaryUserAndSendOtp(
        string $email,
        string $password,
        int $otpType,
        int $platform,
        string $version
    ): array {
        try {
            // Get customer role
            $role = Role::getByCode(Role::ROLE_CUSTOMER_CODE);
            if (!$role) {
                LogHandler::error('Create temporary user failed: Customer role not found', [
                    'email' => $email,
                ], LogHandler::CHANNEL_API);

                return [
                    'success'    => false,
                    'user'       => null,
                    'regRequest' => null,
                    'otp'        => null,
                    'message'    => 'An error occurred. Please try again later.',
                ];
            }

            // Use transaction to ensure data consistency
            $data = [
                'regRequest' => null,
                'user'       => null,
                'otp'        => null,
            ];

            $transactionResult = SqlHandler::handleTransaction(function () use ($email, $password, $role, $otpType, $platform, $version, &$data) {
                // Create ApiRegRequest
                $data['regRequest'] = ApiRegRequest::create([
                    'email'    => $email,
                    'password' => $password,
                    'status'   => ApiRegRequest::STATUS_REGISTER_REQUEST,
                ]);

                if (!$data['regRequest']) {
                    return false;
                }

                // Create temporary user
                $data['user'] = User::create([
                    'email'    => $email,
                    'username' => $email,
                    'password' => $password,
                    'name'     => $email,
                    'role_id'  => $role->id,
                    'status'   => BaseModel::STATUS_REGISTER_REQUEST,
                ]);

                if (!$data['user']) {
                    return false;
                }

                // Create OTP
                $data['otp'] = self::createOtp($data['user'], $otpType, $platform, $version);
                if (!$data['otp']) {
                    return false;
                }

                // Send OTP email
                $emailSent = self::sendOtpEmail($data['otp']);
                if (!$emailSent) {
                    LogHandler::warning('Create temporary user: Failed to send OTP email', [
                        'user_id' => $data['user']->id,
                        'otp_id'  => $data['otp']->id,
                    ], LogHandler::CHANNEL_API);
                }

                return true;
            });

            if (!$transactionResult) {
                LogHandler::error('Create temporary user failed: Transaction failed', [
                    'email' => $email,
                ], LogHandler::CHANNEL_API);

                return [
                    'success'    => false,
                    'user'       => null,
                    'regRequest' => null,
                    'otp'        => null,
                    'message'    => 'An error occurred. Please try again later.',
                ];
            }

            return [
                'success'    => true,
                'user'       => $data['user'],
                'regRequest' => $data['regRequest'],
                'otp'        => $data['otp'],
                'message'    => 'OTP has been sent successfully',
            ];
        } catch (Exception $e) {
            LogHandler::error('Failed to create temporary user and send OTP', [
                'email' => $email,
                'type'  => $otpType,
                'error' => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return [
                'success'    => false,
                'user'       => null,
                'regRequest' => null,
                'otp'        => null,
                'message'    => 'An error occurred. Please try again later.',
            ];
        }
    }

    /**
     * Invalidate old unused OTPs for a user.
     *
     * @param  string  $email
     * @param  int  $type
     * @return int
     */
    private static function invalidateOldOtpsForEmail(string $email, int $type): int {
        try {
            return OtpManagement::invalidateOldOtpsForEmail($email, $type);
        } catch (Exception $e) {
            LogHandler::warning('Failed to invalidate old OTPs', [
                'email' => $email,
                'type'  => $type,
                'error' => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return 0;
        }
    }
}
