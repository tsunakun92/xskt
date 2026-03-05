<?php

namespace Modules\Api\GraphQL\Resolvers;

use Illuminate\Support\Facades\Hash;

use App\Entities\Sanctum\PersonalAccessToken;
use Modules\Admin\Models\User;
use Modules\Api\Models\OtpManagement;
use Modules\Api\Services\OtpService;
use Modules\Logging\Utils\LogHandler;

/**
 * Resolver for user registration.
 */
class RegisterResolver extends BaseApiResolver {
    /**
     * Handle user registration.
     *
     * @param  mixed  $_
     * @param  array  $args
     * @return array
     */
    public function register($_, array $args): array {
        // Validate input
        $validationError = $this->validateInput($args, [
            'email'    => 'required|email|max:255',
            'password' => 'required|string|min:8|max:256',
            'version'  => 'required|string|max:255',
            'platform' => PersonalAccessToken::getPlatformValidationRules(),
        ], 'Register', [
            'email' => $args['email'] ?? null,
        ]);

        if ($validationError !== null) {
            return $validationError;
        }

        // Rate limiting (email-based key)
        $rateLimitError = $this->checkRateLimitFromConfigByEmail(
            'register',
            'register',
            $args['email'],
            'Register',
            [
                'email' => $args['email'],
            ]
        );

        if ($rateLimitError !== null) {
            return $rateLimitError;
        }

        return $this->execute('Register', [
            'email' => $args['email'] ?? null,
        ], function () use ($args) {
            // Check if user already exists (any status)
            $existingUser = User::where('email', $args['email'])->first();
            if ($existingUser) {
                LogHandler::warning('Register failed: Email already exists', [
                    'email' => $args['email'],
                ], LogHandler::CHANNEL_API);

                return apiResponseError('Email already exists.');
            }

            // Create hashed password
            $password = Hash::make($args['password']);

            // Convert platform string to integer for internal use
            $platformInt = $this->convertPlatformToInt($args['platform']);

            // Create temporary user and send OTP
            $result = OtpService::createTemporaryUserAndSendOtp(
                $args['email'],
                $password,
                OtpManagement::TYPE_REGISTER,
                $platformInt,
                $args['version']
            );

            if (!$result['success']) {
                LogHandler::error('Register failed', [
                    'email' => $args['email'],
                ], LogHandler::CHANNEL_API);

                return apiResponseError($result['message']);
            }

            // Success
            LogHandler::info('User registered successfully, OTP sent', [
                'email' => $args['email'],
            ], LogHandler::CHANNEL_API);

            return apiResponseSuccess('OTP has been sent to your email.', [
                'expires_in_minutes' => OtpService::getOtpExpirationMinutes(OtpManagement::TYPE_REGISTER),
            ]);
        });
    }
}
