<?php

namespace Modules\Api\GraphQL\Resolvers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Entities\Sanctum\PersonalAccessToken;
use Modules\Admin\Models\User;
use Modules\Admin\Services\MobilePermissionService;
use Modules\Logging\Utils\LogHandler;

class LoginResolver extends BaseApiResolver {
    /**
     * Handle user login
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function login($root, array $args) {
        $validationError = $this->validateInput($args, [
            'username'     => 'required|string|max:255',
            'password'     => 'required|string|min:6|max:255',
            'device_token' => 'required|string|max:255',
            'platform'     => PersonalAccessToken::getPlatformValidationRules(),
            'version'      => 'required|string|max:255',
        ], 'Login', [
            'username' => $args['username'] ?? null,
        ]);

        if ($validationError !== null) {
            return $validationError;
        }

        return $this->execute('Login', [
            'username' => $args['username'] ?? null,
        ], function () use ($args) {
            $identifier = $args['username'];
            $isEmail    = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;

            // Retrieve the user by username or email
            $userQuery = User::query()->active();
            if ($isEmail) {
                $userQuery->where('email', $identifier);
            } else {
                $userQuery->where('username', $identifier);
            }
            $user = $userQuery->first();

            // Check if the user exists
            if (!$user) {
                LogHandler::warning('Login failed: Account does not exist', [
                    'username' => $identifier,
                    'is_email' => $isEmail,
                ], LogHandler::CHANNEL_API);

                return apiResponseError('Account does not exist');
            }

            // Check if the password is correct
            if (!Hash::check($args['password'], $user->password)) {
                LogHandler::warning('Login failed: Invalid password', [
                    'user_id'  => $user->id,
                    'username' => $identifier,
                ], LogHandler::CHANNEL_API);

                return apiResponseError('Invalid username/email or password');
            }

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

            /** @var MobilePermissionService $permissionService */
            $permissionService = app(MobilePermissionService::class);

            // Get user details, configs, and permissions
            $userData = [
                'user'        => $user,
                'configs'     => $user->apiGetUserSettings(),
                'permissions' => $permissionService->getMobilePermissionsGrouped($user),
            ];

            LogHandler::info('User logged in successfully', [
                'user_id'  => $user->id,
                'username' => $user->username,
                'platform' => $args['platform'],
            ], LogHandler::CHANNEL_API);

            // Return success response
            return apiResponseSuccess('Login successfully', [
                'token' => $token,
                'data'  => $userData,
            ]);
        });
    }
}
