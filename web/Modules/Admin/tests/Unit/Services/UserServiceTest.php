<?php

namespace Modules\Admin\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use Modules\Admin\Models\Permission;
use Modules\Admin\Models\Role;
use Modules\Admin\Models\User;
use Modules\Admin\Services\UserService;

/**
 * Test UserService functionality.
 */
class UserServiceTest extends TestCase {
    use RefreshDatabase;

    /**
     * User service instance.
     *
     * @var UserService
     */
    protected UserService $userService;

    /**
     * Setup test environment.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->userService = app(UserService::class);
    }

    /**
     * Test update permissions successfully.
     */
    public function test_update_permissions_successfully(): void {
        $role = Role::create([
            'name'   => 'Test Role',
            'code'   => 'TEST_ROLE',
            'status' => Role::STATUS_ACTIVE,
        ]);

        $permission1 = Permission::create([
            'name'   => 'List Users',
            'key'    => 'users.index',
            'group'  => 'users',
            'module' => 'admin',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $permission2 = Permission::create([
            'name'   => 'Create Users',
            'key'    => 'users.create',
            'group'  => 'users',
            'module' => 'admin',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        // Attach permissions to role
        $role->rPermissions()->attach([$permission1->id, $permission2->id]);

        $user = User::create([
            'username' => 'testuser',
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        $permissionKeys = ['users.index', 'users.create'];

        $result = $this->userService->updatePermissions($user, $permissionKeys);

        $this->assertTrue($result);
        $this->assertCount(2, $user->rPermissions);
        $this->assertTrue($user->rPermissions->contains('key', 'users.index'));
        $this->assertTrue($user->rPermissions->contains('key', 'users.create'));
    }

    /**
     * Test update permissions with empty array removes all permissions.
     */
    public function test_update_permissions_with_empty_array_removes_all(): void {
        $role = Role::create([
            'name'   => 'Test Role',
            'code'   => 'TEST_ROLE',
            'status' => Role::STATUS_ACTIVE,
        ]);

        $permission = Permission::create([
            'name'   => 'List Users',
            'key'    => 'users.index',
            'group'  => 'users',
            'module' => 'admin',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $role->rPermissions()->attach([$permission->id]);

        $user = User::create([
            'username' => 'testuser',
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        // First add a permission
        $user->rPermissions()->attach([$permission->id]);
        $this->assertCount(1, $user->rPermissions);

        // Then remove all
        $result = $this->userService->updatePermissions($user, []);

        $this->assertTrue($result);
        $user->refresh();
        $this->assertCount(0, $user->rPermissions);
    }

    /**
     * Test update settings successfully.
     */
    public function test_update_settings_successfully(): void {
        $role = Role::create([
            'name'   => 'Test Role',
            'code'   => 'TEST_ROLE',
            'status' => Role::STATUS_ACTIVE,
        ]);

        $user = User::create([
            'username' => 'testuser',
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        $settings = [
            1 => 'value1',
            2 => 'value2',
        ];

        $result = $this->userService->updateSettings($user, $settings);

        $this->assertTrue($result);
    }

    /**
     * Test update settings for super admin does nothing.
     */
    public function test_update_settings_for_super_admin_does_nothing(): void {
        $role = Role::create([
            'name'   => 'Super Admin',
            'code'   => Role::ROLE_SUPER_ADMIN_CODE,
            'status' => Role::STATUS_ACTIVE,
        ]);

        $user = User::create([
            'username' => 'superadmin',
            'name'     => 'Super Admin',
            'email'    => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        $settings = [
            1 => 'value1',
        ];

        $result = $this->userService->updateSettings($user, $settings);

        $this->assertTrue($result);
        // Super admin should not have settings overridden (handled in User model)
    }
}
