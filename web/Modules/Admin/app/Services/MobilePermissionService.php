<?php

namespace Modules\Admin\Services;

use Modules\Admin\Models\Permission;
use Modules\Admin\Models\User;

/**
 * Class MobilePermissionService
 *
 * Build mobile permissions payload for API responses.
 */
class MobilePermissionService {
    /**
     * Get mobile permissions grouped by permission group.
     *
     * Format:
     * [
     *   ['group' => 'map', 'actions' => ['mobiles.map.view', 'mobiles.map.edit']],
     *   ...
     * ]
     *
     * @param  User  $user
     * @return array
     */
    public function getMobilePermissionsGrouped(User $user): array {
        $permissionKeys = $user->getRolePermissionKeys();
        if (empty($permissionKeys)) {
            return [];
        }

        $permissions = Permission::query()
            ->where('module', Permission::MODULE_MOBILES)
            ->whereIn('key', $permissionKeys)
            ->orderBy('group')
            ->orderBy('key')
            ->get(['key', 'group']);

        if ($permissions->isEmpty()) {
            return [];
        }

        $result = [];

        $grouped = $permissions->groupBy('group');
        foreach ($grouped as $group => $items) {
            $actions = $items
                ->pluck('key')
                ->unique()
                ->values()
                ->toArray();

            $result[] = [
                'group'   => (string) $group,
                'actions' => $actions,
            ];
        }

        return $result;
    }
}
