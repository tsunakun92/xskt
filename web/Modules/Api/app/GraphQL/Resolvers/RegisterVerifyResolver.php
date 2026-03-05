<?php

namespace Modules\Api\GraphQL\Resolvers;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

use App\Entities\Sanctum\PersonalAccessToken;
use Modules\Admin\Models\User;
use Modules\Admin\Services\MobilePermissionService;
use Modules\Api\Models\ApiRegRequest;
use Modules\Api\Models\OtpManagement;
use Modules\Api\Services\OtpService;
use Modules\Logging\Utils\LogHandler;

/**
 * Resolver for verifying registration OTP.
 */
class RegisterVerifyResolver extends BaseApiResolver {
    /**
     * Handle OTP verification for user registration.
     *
     * @param  mixed  $_
     * @param  array  $args
     * @return array
     */
    public function verify($_, array $args): array {
        // Validate input
        $validationError = $this->validateInput($args, [
            'email'        => 'required|email|max:255',
            'otp'          => 'required|string|max:255',
            'device_token' => 'required|string|max:255',
            'version'      => 'required|string|max:255',
            'platform'     => PersonalAccessToken::getPlatformValidationRules(),
        ], 'Register verify', [
            'email' => $args['email'] ?? null,
        ]);

        if ($validationError !== null) {
            return $validationError;
        }

        // Rate limiting (email-based key)
        $rateLimitError = $this->checkRateLimitFromConfigByEmail(
            'register_verify',
            'verify_register_otp',
            $args['email'],
            'Register verify',
            [
                'email' => $args['email'],
            ]
        );

        if ($rateLimitError !== null) {
            return $rateLimitError;
        }

        $key = 'verify_register_otp:' . strtolower($args['email']);

        return $this->execute('Register verify', [
            'email' => $args['email'] ?? null,
        ], function () use ($args, $key) {
            // Check user exists and is in register request status
            $user = User::where('email', $args['email'])
                ->where('status', User::STATUS_REGISTER_REQUEST)
                ->first();

            if (!$user) {
                return apiResponseError('User not found.');
            }

            // Check api_reg_requests table by email with status REGISTER_REQUEST
            $regRequest = ApiRegRequest::where('email', $args['email'])
                ->where('status', ApiRegRequest::STATUS_REGISTER_REQUEST)
                ->first();

            if (!$regRequest) {
                return apiResponseError('Registration request not found.');
            }

            // Verify OTP using OtpService
            $otpResult = OtpService::verifyOtp($args['email'], OtpManagement::TYPE_REGISTER, $args['otp']);

            if (!$otpResult['valid']) {
                return apiResponseError($otpResult['message']);
            }

            $otp = $otpResult['otp'];

            // Success: Update tables
            $user->status            = User::STATUS_ACTIVE;
            $user->email_verified_at = now();
            $user->save();

            $regRequest->status = ApiRegRequest::STATUS_ACTIVE;
            $regRequest->save();

            $otp->used_at     = now();
            $otp->verified_at = now();
            $otp->save();

            // Convert platform string to integer for internal use
            $platformInt = PersonalAccessToken::convertStringToInt($args['platform']);

            // Check if the user has exceeded the maximum number of mobile tokens
            if (in_array($platformInt, PersonalAccessToken::MOBILE_DEVICES)) {
                // Get all mobile tokens
                $mobileTokens = PersonalAccessToken::getMobileTokensByUserId($user->id);
                // Check maximum number of mobile tokens
                if ($mobileTokens->count() >= PersonalAccessToken::MAX_MOBILE_DEVICES) {
                    // Calculate number of tokens to delete
                    $tokensToDelete = $mobileTokens->take($mobileTokens->count() - PersonalAccessToken::MAX_MOBILE_DEVICES + 1);
                    // Delete tokens
                    foreach ($tokensToDelete as $token) {
                        $token->delete();
                    }
                    LogHandler::info('Old mobile tokens deleted due to max limit', [
                        'user_id'       => $user->id,
                        'deleted_count' => $tokensToDelete->count(),
                    ], LogHandler::CHANNEL_API);
                }
            }

            // Generate a token for the user
            $token = $user->createToken(Str::random(60), $args['device_token'], $platformInt)->plainTextToken;

            // Clear rate limit on success
            RateLimiter::clear($key);

            /** @var MobilePermissionService $permissionService */
            $permissionService = app(MobilePermissionService::class);

            // Get user details, configs, and permissions
            $userData = [
                'user'        => $user,
                'configs'     => $user->apiGetUserSettings(),
                'permissions' => $permissionService->getMobilePermissionsGrouped($user),
            ];

            LogHandler::info('User registration verified successfully, token generated', [
                'user_id'  => $user->id,
                'email'    => $user->email,
                'platform' => $args['platform'],
            ], LogHandler::CHANNEL_API);

            return apiResponseSuccess('OTP verified successfully.', [
                'token' => $token,
                'data'  => $userData,
            ]);
        });
    }
}
