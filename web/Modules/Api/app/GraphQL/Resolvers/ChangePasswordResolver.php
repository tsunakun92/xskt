<?php

namespace Modules\Api\GraphQL\Resolvers;

use Illuminate\Support\Facades\Hash;

use App\Entities\Sanctum\PersonalAccessToken;
use Modules\Logging\Utils\LogHandler;

class ChangePasswordResolver extends BaseApiResolver {
    /**
     * Change the user's password.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function changePassword($root, array $args): array {
        $validationError = $this->validateInput($args, [
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6',
            'version'          => 'required|string|max:255',
            'platform'         => PersonalAccessToken::getPlatformValidationRules(),
        ], 'Change password');

        if ($validationError !== null) {
            return $validationError;
        }

        return $this->execute('Change password', [], function () use ($args) {
            // Retrieve authenticated user
            $user = auth('sanctum')->user();

            // Check if the user exists
            if (!$user) {
                LogHandler::warning('Change password failed: User not authenticated', [], LogHandler::CHANNEL_API);

                return apiResponseError('User not authenticated');
            }

            // Check if the current password is correct
            if (!Hash::check($args['current_password'], $user->password)) {
                LogHandler::warning('Change password failed: Invalid current password', [
                    'user_id' => $user->id,
                ], LogHandler::CHANNEL_API);

                return apiResponseError('Invalid current password');
            }

            // Update the password
            $user->password = Hash::make($args['new_password']);
            $user->save();

            LogHandler::info('Password changed successfully via API', [
                'user_id'  => $user->id,
                'username' => $user->username,
            ], LogHandler::CHANNEL_API);

            return apiResponseSuccess('Password changed successfully');
        });
    }
}
