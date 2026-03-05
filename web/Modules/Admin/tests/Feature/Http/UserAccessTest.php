<?php

namespace Modules\Admin\Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use Modules\Admin\Models\Permission;
use Modules\Admin\Models\Role;
use Modules\Admin\Models\User;

class UserAccessTest extends TestCase {
    use RefreshDatabase;

    /**
     * Ensure super admin users cannot be accessed via user routes, for any logged-in user.
     *
     * @return void
     */
    #[Test]
    public function super_admin_user_is_inaccessible_via_admin_routes(): void {
        // Create permissions
        $usersShowPermission = Permission::create([
            'name'   => 'View User',
            'key'    => 'users.show',
            'group'  => 'users',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $usersEditPermission = Permission::create([
            'name'   => 'Edit User',
            'key'    => 'users.edit',
            'group'  => 'users',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $usersPermissionPermission = Permission::create([
            'name'   => 'Manage User Permission',
            'key'    => 'users.permission',
            'group'  => 'users',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $usersSettingPermission = Permission::create([
            'name'   => 'Manage User Settings',
            'key'    => 'users.setting',
            'group'  => 'users',
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

        // Assign permissions to admin role
        $adminRole->rPermissions()->attach([
            $usersShowPermission->id,
            $usersEditPermission->id,
            $usersPermissionPermission->id,
            $usersSettingPermission->id,
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

        $normalUser = User::create([
            'name'     => 'Normal User',
            'email'    => 'normal@example.com',
            'username' => 'normaluser',
            'password' => Hash::make('password'),
            'role_id'  => $adminRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);
        $normalUser->load('rRole');

        // Admin user should receive 404 for super admin user (has permission but blocked by controller)
        // Note: If admin user doesn't have permission, they'll get 403 from middleware
        // But if they have permission, they'll get 404 from controller's ensureModelAccessible
        $this->actingAs($adminUser);
        $response = $this->get(route('users.show', $superAdminUser));
        $this->assertContains($response->status(), [403, 404]);

        $response = $this->get(route('users.edit', $superAdminUser));
        $this->assertContains($response->status(), [403, 404]);

        $response = $this->get(route('users.permission', $superAdminUser->id));
        $this->assertContains($response->status(), [403, 404]);

        $response = $this->get(route('users.setting', $superAdminUser->id));
        $this->assertContains($response->status(), [403, 404]);

        // Super admin user should receive 404 for super admin user (bypasses permission but blocked by controller)
        $this->actingAs($superAdminUser);
        $this->get(route('users.show', $superAdminUser))->assertNotFound();
        $this->get(route('users.edit', $superAdminUser))->assertNotFound();
        $this->get(route('users.permission', $superAdminUser->id))->assertNotFound();
        $this->get(route('users.setting', $superAdminUser->id))->assertNotFound();
    }
}
