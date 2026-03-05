<?php

namespace Modules\Api\GraphQL\Resolvers;

use App\Entities\Sanctum\PersonalAccessToken;
use Modules\Logging\Utils\LogHandler;

class LogoutResolver extends BaseApiResolver {
    /**
     * Handle user logout
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function logout($root, array $args) {
        $validationError = $this->validateInput($args, [
            'version'  => 'required|string|max:255',
            'platform' => PersonalAccessToken::getPlatformValidationRules(),
        ], 'Logout');

        if ($validationError !== null) {
            return $validationError;
        }

        return $this->execute('Logout', [], function () {
            // Get the current user using Sanctum guard
            $user = auth('sanctum')->user();

            // Check if the user exists
            if (!$user) {
                LogHandler::warning('Logout failed: User not authenticated', [], LogHandler::CHANNEL_API);

                return apiResponseError('User not authenticated');
            }

            $userId   = $user->id;
            $username = $user->username;

            // Delete the current access token (logout)
            $user->currentAccessToken()?->delete();

            LogHandler::info('User logged out successfully', [
                'user_id'  => $userId,
                'username' => $username,
            ], LogHandler::CHANNEL_API);

            // Return success response
            return apiResponseSuccess('Logout successfully');
        });
    }
}
