<?php

namespace Modules\Admin\Services;

use App\Services\BaseService;
use Modules\Admin\Models\User;
use Modules\Admin\Services\Contracts\UserServiceInterface;

/**
 * Service for handling user-related business logic.
 */
class UserService extends BaseService implements UserServiceInterface {
    /**
     * Update user permissions.
     *
     * @param  User  $user
     * @param  array  $permissionKeys
     * @return bool
     */
    public function updatePermissions(User $user, array $permissionKeys): bool {
        $result = $this->handleTransaction(function () use ($user, $permissionKeys) {
            $user->syncPermissions($permissionKeys);

            $this->logInfo('User permission updated successfully', [
                'user_id'          => $user->id,
                'permission_count' => count($permissionKeys),
            ]);

            return true;
        });

        return $result !== null;
    }

    /**
     * Update user settings.
     *
     * @param  User  $user
     * @param  array  $settings
     * @return bool
     */
    public function updateSettings(User $user, array $settings): bool {
        $result = $this->handleTransaction(function () use ($user, $settings) {
            $user->syncSettings($settings);

            $this->logInfo('User settings updated successfully', [
                'user_id' => $user->id,
            ]);

            return true;
        });

        return $result !== null;
    }
}
