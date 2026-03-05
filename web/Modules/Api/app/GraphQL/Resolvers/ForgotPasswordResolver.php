<?php

namespace Modules\Api\GraphQL\Resolvers;

use App\Entities\Sanctum\PersonalAccessToken;
use Modules\Api\Models\OtpManagement;
use Modules\Api\Services\OtpService;

/**
 * Resolver for forgot_password mutation.
 */
class ForgotPasswordResolver extends BaseApiResolver {
    /**
     * Handle forgot password request - reset password using OTP.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function forgotPassword($root, array $args): array {
        $validationError = $this->validateInput($args, [
            'email'    => 'required|email|max:255',
            'new_pass' => 'required|string|min:6|max:255',
            'otp'      => 'required|string|max:255',
            'version'  => 'required|string|max:255',
            'platform' => PersonalAccessToken::getPlatformValidationRules(),
        ], 'Forgot password', [
            'email' => $args['email'] ?? null,
        ]);

        if ($validationError !== null) {
            return $validationError;
        }

        return $this->execute('Forgot password', [
            'email' => $args['email'] ?? null,
        ], function () use ($args) {
            $result = OtpService::resetPassword(
                $args['email'],
                OtpManagement::TYPE_FORGOT_PASSWORD,
                $args['new_pass'],
                $args['otp']
            );

            if (!$result['success']) {
                return apiResponseError($result['message']);
            }

            return apiResponseSuccess($result['message']);
        });
    }
}
