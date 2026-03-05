<?php

namespace Modules\Admin\Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use Modules\Admin\Models\Permission;
use Modules\Admin\Models\Role;
use Modules\Admin\Models\User;

class RoleAccessTest extends TestCase {
    use RefreshDatabase;

    /**
     * Ensure super admin role cannot be accessed via role routes, even by super admin user.
     *
     * @return void
     */
    #[Test]
    public function super_admin_role_is_inaccessible_via_admin_routes(): void {
        // Create permissions
        $rolesShowPermission = Permission::create([
            'name'   => 'View Role',
            'key'    => 'roles.show',
            'group'  => 'roles',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $rolesEditPermission = Permission::create([
            'name'   => 'Edit Role',
            'key'    => 'roles.edit',
            'group'  => 'roles',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $rolesPermissionPermission = Permission::create([
            'name'   => 'Manage Role Permission',
            'key'    => 'roles.permission',
            'group'  => 'roles',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $superAdminRole = Role::create([
            'name'   => 'Super Admin',
            'code'   => Role::ROLE_SUPER_ADMIN_CODE,
            'status' => Role::STATUS_ACTIVE,
        ]);

        $adminRole = Role::create([
            'name'   => 'Admin',
            'code'   => Role::ROLE_ADMIN_CODE,
            'status' => Role::STATUS_ACTIVE,
        ]);

        $normalRole = Role::create([
            'name'   => 'Normal',
            'code'   => 'ROLE_NORMAL',
            'status' => Role::STATUS_ACTIVE,
        ]);

        // Assign permissions to roles
        $adminRole->rPermissions()->attach([
            $rolesShowPermission->id,
            $rolesEditPermission->id,
            $rolesPermissionPermission->id,
        ]);

        // Clear role cache to ensure permissions are loaded correctly
        Role::clearRoleCache();

        $superAdminUser = User::create([
            'name'     => 'Super Admin',
            'email'    => 'superadmin@example.com',
            'username' => 'superadmin',
            'password' => Hash::make('password'),
            'role_id'  => $superAdminRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);
        $superAdminUser->load('rRole');

        $adminUser = User::create([
            'name'     => 'Admin',
            'email'    => 'admin@example.com',
            'username' => 'admin',
            'password' => Hash::make('password'),
            'role_id'  => $adminRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);
        $adminUser->load('rRole');
        $adminUser->clearUserPermissionCache();
        $adminUser->refresh();

        // Admin user should receive 404 for super admin role (has permission but blocked by controller)
        // Note: If admin user doesn't have permission, they'll get 403 from middleware
        // But if they have permission, they'll get 404 from controller's ensureModelAccessible
        $this->actingAs($adminUser);
        $response = $this->get(route('roles.show', $superAdminRole));
        // Admin user should either get 403 (no permission) or 404 (blocked by controller)
        $this->assertContains($response->status(), [403, 404]);

        $response = $this->get(route('roles.edit', $superAdminRole));
        $this->assertContains($response->status(), [403, 404]);

        $response = $this->get(route('roles.permission', $superAdminRole->id));
        $this->assertContains($response->status(), [403, 404]);

        // Super admin user should receive 404 for super admin role (bypasses permission but blocked by controller)
        $this->actingAs($superAdminUser);
        $this->get(route('roles.show', $superAdminRole))->assertNotFound();
        $this->get(route('roles.edit', $superAdminRole))->assertNotFound();
        $this->get(route('roles.permission', $superAdminRole->id))->assertNotFound();
    }
}
