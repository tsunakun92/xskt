<?php

namespace Modules\Api\GraphQL\Resolvers;

use App\Entities\Sanctum\PersonalAccessToken;
use Modules\Api\Models\OtpManagement;
use Modules\Api\Services\OtpService;

/**
 * Resolver for verify_otp mutation.
 */
class VerifyOtpResolver extends BaseApiResolver {
    /**
     * Verify OTP by email, otp code, and type.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function verifyOtp($root, array $args): array {
        $validationError = $this->validateInput($args, [
            'email'    => 'required|email|max:255',
            'otp'      => 'required|string|max:255',
            'type'     => 'required|integer|in:' . OtpManagement::TYPE_REGISTER . ',' . OtpManagement::TYPE_FORGOT_PASSWORD,
            'version'  => 'required|string|max:255',
            'platform' => PersonalAccessToken::getPlatformValidationRules(),
        ], 'Verify OTP', [
            'email' => $args['email'] ?? null,
            'type'  => $args['type'] ?? null,
        ]);

        if ($validationError !== null) {
            return $validationError;
        }

        return $this->execute('Verify OTP', [
            'email' => $args['email'] ?? null,
            'type'  => $args['type'] ?? null,
        ], function () use ($args) {
            $result = OtpService::verifyOtp(
                $args['email'],
                (int) $args['type'],
                $args['otp']
            );
            if (!$result['valid']) {
                return apiResponseError($result['message']);
            }

            return apiResponseSuccess('OTP is valid');
        });
    }
}
