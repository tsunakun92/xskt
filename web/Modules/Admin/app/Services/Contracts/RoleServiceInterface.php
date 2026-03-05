<?php

namespace Modules\Admin\Services\Contracts;

use Modules\Admin\Models\Role;

/**
 * Interface for RoleService.
 * Defines contract for role-related business logic operations.
 */
interface RoleServiceInterface {
    /**
     * Update role permissions.
     *
     * @param  Role  $role
     * @param  array  $permissionKeys
     * @return bool
     */
    public function updatePermissions(Role $role, array $permissionKeys): bool;
}
