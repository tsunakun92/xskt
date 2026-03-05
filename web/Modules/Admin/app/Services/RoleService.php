<?php

namespace Modules\Admin\Services;

use App\Services\BaseService;
use Modules\Admin\Models\Role;
use Modules\Admin\Services\Contracts\RoleServiceInterface;

/**
 * Service for handling role-related business logic.
 */
class RoleService extends BaseService implements RoleServiceInterface {
    /**
     * Update role permissions.
     *
     * @param  Role  $role
     * @param  array  $permissionKeys
     * @return bool
     */
    public function updatePermissions(Role $role, array $permissionKeys): bool {
        $result = $this->handleTransaction(function () use ($role, $permissionKeys) {
            $role->syncPermissions($permissionKeys);

            $this->logInfo('Permission updated successfully', [
                'role_id'          => $role->id,
                'permission_count' => count($permissionKeys),
            ]);

            return true;
        });

        return $result !== null;
    }
}
