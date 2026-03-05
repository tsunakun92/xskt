<?php

namespace Modules\Admin\Database\Seeders;

use Illuminate\Database\Seeder;

use App\Utils\DomainConst;
use Modules\Admin\Models\Permission;

class PermissionSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        Permission::truncate();

        $data = [];
        // Resource routes
        $resourceRoutes = [
            // Admin module
            'users',
            'roles',
            'permissions',
            'settings',
            'prefectures',
            'municipalities',
            'post-numbers',
            'personal-access-tokens',
            // API module
            'api-reg-requests',
            'api-request-logs',
        ];
        $resourceActions = [
            DomainConst::ACTION_INDEX,
            DomainConst::ACTION_SHOW,
            DomainConst::ACTION_CREATE,
            DomainConst::ACTION_EDIT,
            DomainConst::ACTION_DESTROY,
        ];

        foreach ($resourceRoutes as $route) {
            foreach ($resourceActions as $action) {
                $module = self::determineModule($route);
                $data[] = [
                    'name'       => title_case($action) . ' ' . title_case(kebab_case($route)),
                    'key'        => "{$route}.{$action}",
                    'group'      => $route,
                    'module'     => $module,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Mobiles module permissions (use the same routes/actions, but module is always mobiles)
        foreach ($resourceRoutes as $route) {
            foreach ($resourceActions as $action) {
                $data[] = [
                    'name'       => "{$route} {$action}",
                    'key'        => "mobiles.{$route}.{$action}",
                    'group'      => $route,
                    'module'     => Permission::MODULE_MOBILES,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Custom routes
        $customPermissions = [
            // Module level access control
            [
                'name'  => 'Access Admin Module',
                'key'   => 'admin.module',
                'group' => 'admin',
            ],
            [
                'name'  => 'Access API Module',
                'key'   => 'api.module',
                'group' => 'api',
            ],

            // Admin Dashboard
            [
                'name'  => 'Admin Dashboard',
                'key'   => 'admin',
                'group' => 'admin',
            ],
            [
                'name'  => 'View Changelog',
                'key'   => 'changelog.index',
                'group' => 'admin',
            ],

            // Role Permission Management
            [
                'name'  => 'Manage Role Permission',
                'key'   => 'roles.permission',
                'group' => 'roles',
            ],
            // User Permission Management
            [
                'name'  => 'Manage User Permission',
                'key'   => 'users.permission',
                'group' => 'users',
            ],
            // User Settings Management
            [
                'name'  => 'Manage User Settings',
                'key'   => 'users.setting',
                'group' => 'users',
            ],
        ];

        foreach ($customPermissions as $permission) {
            $module = self::determineModule($permission['group'], $permission['key']);
            $data[] = [
                'name'       => $permission['name'],
                'key'        => $permission['key'],
                'group'      => $permission['group'],
                'module'     => $module,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Permission::insert($data);
    }

    /**
     * Determine module based on route/group/key
     *
     * @param  string  $group  Group name (usually route prefix)
     * @param  string|null  $key  Full permission key (e.g., users.index)
     * @return string Module name (admin, hr, crm, api)
     */
    private static function determineModule(string $group, ?string $key = null): string {
        // Check key first for more specific matching
        if ($key) {
            if (str_starts_with($key, Permission::MODULE_MOBILES . '.')) {
                return Permission::MODULE_MOBILES;
            }
            // Check module prefixes in key
            if (str_starts_with($key, Permission::MODULE_HR . '.')) {
                return Permission::MODULE_HR;
            }
            if (str_starts_with($key, Permission::MODULE_CRM . '.')) {
                return Permission::MODULE_CRM;
            }
            if (str_starts_with($key, Permission::MODULE_API . '.')) {
                return Permission::MODULE_API;
            }

            // Check for crm- prefix in key (e.g., crm-bookings.index)
            if (str_starts_with($key, 'crm-')) {
                return Permission::MODULE_CRM;
            }

            // Check for hr- prefix in key
            if (str_starts_with($key, 'hr-')) {
                return Permission::MODULE_HR;
            }

            // Check for api- prefix in key
            if (str_starts_with($key, 'api-')) {
                return Permission::MODULE_API;
            }

            // CRM module resource routes (bookings, customers, sections, rooms)
            if (preg_match('/^(bookings|customers|sections|rooms)\./', $key)) {
                return Permission::MODULE_CRM;
            }
        }

        // Check group for module prefixes
        if ($group === Permission::MODULE_MOBILES || str_starts_with($group, Permission::MODULE_MOBILES . '-') || str_starts_with($group, Permission::MODULE_MOBILES . '.')) {
            return Permission::MODULE_MOBILES;
        }

        if (str_starts_with($group, 'crm-')) {
            return Permission::MODULE_CRM;
        }
        if (str_starts_with($group, 'hr-')) {
            return Permission::MODULE_HR;
        }
        if (str_starts_with($group, 'api-')) {
            return Permission::MODULE_API;
        }

        // Check group for exact matches
        if (in_array($group, [Permission::MODULE_HR, Permission::MODULE_HR . '.profiles', Permission::MODULE_HR . '.employees'])) {
            return Permission::MODULE_HR;
        }
        if (in_array($group, [Permission::MODULE_CRM, Permission::MODULE_CRM . '.contacts', Permission::MODULE_CRM . '.leads'])) {
            return Permission::MODULE_CRM;
        }
        if (in_array($group, [Permission::MODULE_API, Permission::MODULE_API . '.tokens'])) {
            return Permission::MODULE_API;
        }

        // CRM module resource groups (without prefix)
        if (in_array($group, ['bookings', 'customers', 'sections', 'rooms'])) {
            return Permission::MODULE_CRM;
        }

        // Default to admin
        return Permission::MODULE_ADMIN;
    }
}
