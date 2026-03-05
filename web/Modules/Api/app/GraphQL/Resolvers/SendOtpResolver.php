<?php

namespace Modules\Api\GraphQL\Resolvers;

use App\Entities\Sanctum\PersonalAccessToken;
use Modules\Admin\Models\User;
use Modules\Api\Models\OtpManagement;
use Modules\Api\Services\OtpService;
use Modules\Logging\Utils\LogHandler;

/**
 * Resolver for send_otp mutation.
 *
 * Currently supports forgot password OTP flow and can be extended for other OTP types.
 */
class SendOtpResolver extends BaseApiResolver {
    /**
     * Supported OTP types for this mutation.
     *
     * Keep this list in sync with API documentation and config when adding new OTP types.
     *
     * @var array<int, int>
     */
    private const SUPPORTED_OTP_TYPES = [
        OtpManagement::TYPE_FORGOT_PASSWORD,
    ];

    /**
     * Handle send OTP request.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function sendOtp($root, array $args): array {
        // Validate input
        $validationError = $this->validateInput($args, [
            'email'    => 'required|email|max:255',
            'type'     => 'required|integer|in:' . implode(',', self::SUPPORTED_OTP_TYPES),
            'version'  => 'required|string|max:255',
            'platform' => PersonalAccessToken::getPlatformValidationRules(),
        ], 'Send OTP', [
            'email' => $args['email'] ?? null,
            'type'  => $args['type'] ?? null,
        ]);

        if ($validationError !== null) {
            return $validationError;
        }

        // Dispatch by OTP type with per-type rate limiting
        $type = (int) $args['type'];

        switch ($type) {
            case OtpManagement::TYPE_FORGOT_PASSWORD:
                // Preserve existing forgot password rate limiting behavior (IP-based)
                $rateLimitError = $this->checkRateLimitFromConfig(
                    'send_otp_forgot_password',
                    'send_otp_forgot_password',
                    'Send OTP forgot password',
                    [
                        'email' => $args['email'] ?? null,
                    ]
                );

                if ($rateLimitError !== null) {
                    return $rateLimitError;
                }

                return $this->execute('Send OTP forgot password', [
                    'email' => $args['email'] ?? null,
                    'type'  => $type,
                ], function () use ($args): array {
                    return $this->handleForgotPasswordOtp($args);
                });

            default:
                // Fallback for unsupported types (should be unreachable due to validation)
                LogHandler::warning('Send OTP called with unsupported type', [
                    'email' => $args['email'] ?? null,
                    'type'  => $type,
                ], LogHandler::CHANNEL_API);

                return apiResponseError('Incorrect information');
        }
    }

    /**
     * Handle forgot password OTP flow (type = TYPE_FORGOT_PASSWORD).
     *
     * This logic preserves the existing send_otp_forgot_password behavior.
     *
     * @param  array  $args
     * @return array
     */
    private function handleForgotPasswordOtp(array $args): array {
        // Check if user exists (any status)
        $existingUser = User::where('email', $args['email'])->first();

        // If user exists and is ACTIVE or REGISTER_REQUEST, proceed normally
        if ($existingUser && in_array($existingUser->status, [User::STATUS_ACTIVE, User::STATUS_REGISTER_REQUEST], true)) {
            // Convert platform string to integer for internal use
            $platformInt = $this->convertPlatformToInt($args['platform']);

            // Find user, create OTP, and send email
            $result = OtpService::findUserCreateAndSendOtp(
                $args['email'],
                OtpManagement::TYPE_FORGOT_PASSWORD,
                $platformInt,
                $args['version']
            );

            if (!$result['success']) {
                LogHandler::warning('Send OTP forgot password failed', [
                    'email' => $args['email'],
                ], LogHandler::CHANNEL_API);

                return apiResponseError($result['message']);
            }

            return apiResponseSuccess('We have emailed your OTP code.', [
                'expires_in_minutes' => OtpService::getOtpExpirationMinutes(OtpManagement::TYPE_FORGOT_PASSWORD),
            ]);
        }

        // User doesn't exist or has different status - create temporary user
        // Convert platform string to integer for internal use
        $platformInt = $this->convertPlatformToInt($args['platform']);

        // Create temporary user and send OTP (password will be set in step 2)
        $result = OtpService::createTemporaryUserAndSendOtp(
            $args['email'],
            '', // Password will be set in step 2
            OtpManagement::TYPE_FORGOT_PASSWORD,
            $platformInt,
            $args['version']
        );

        if (!$result['success']) {
            LogHandler::error('Send OTP forgot password failed', [
                'email' => $args['email'],
            ], LogHandler::CHANNEL_API);

            return apiResponseError($result['message']);
        }

        // Success
        LogHandler::info('Temporary user created for forgot password, OTP sent', [
            'email' => $args['email'],
        ], LogHandler::CHANNEL_API);

        return apiResponseSuccess('We have emailed your OTP code.', [
            'expires_in_minutes' => OtpService::getOtpExpirationMinutes(OtpManagement::TYPE_FORGOT_PASSWORD),
        ]);
    }
}
