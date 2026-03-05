<?php

namespace Modules\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use Modules\Admin\Models\Permission;
use Modules\Admin\Models\Role;

class RolePermissionSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        DB::table('role_permissions')->truncate();
        // Assign all permissions to admin role
        $adminRole     = Role::getByCode(Role::ROLE_ADMIN_CODE);
        $permissionIds = Permission::all()->pluck('id')->toArray();
        $adminRole?->rPermissions()->sync($permissionIds);
    }
}
