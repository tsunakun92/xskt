<?php

namespace Modules\Admin\Services\Contracts;

use Modules\Admin\Models\User;

/**
 * Interface for UserService.
 * Defines contract for user-related business logic operations.
 */
interface UserServiceInterface {
    /**
     * Update user permissions.
     *
     * @param  User  $user
     * @param  array  $permissionKeys
     * @return bool
     */
    public function updatePermissions(User $user, array $permissionKeys): bool;

    /**
     * Update user settings.
     *
     * @param  User  $user
     * @param  array  $settings
     * @return bool
     */
    public function updateSettings(User $user, array $settings): bool;
}
