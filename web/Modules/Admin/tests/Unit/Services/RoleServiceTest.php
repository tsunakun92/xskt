<?php

namespace Modules\Admin\Tests\Unit\Services;

use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use Modules\Admin\Models\Permission;
use Modules\Admin\Models\Role;
use Modules\Admin\Services\RoleService;

/**
 * Test RoleService functionality.
 */
class RoleServiceTest extends TestCase {
    use RefreshDatabase;

    /**
     * Role service instance.
     *
     * @var RoleService
     */
    protected RoleService $roleService;

    /**
     * Setup test environment.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->roleService = app(RoleService::class);
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

        $permissionKeys = ['users.index', 'users.create'];

        $result = $this->roleService->updatePermissions($role, $permissionKeys);

        $this->assertTrue($result);
        $role->refresh();
        $this->assertCount(2, $role->rPermissions);
        $this->assertTrue($role->rPermissions->contains('key', 'users.index'));
        $this->assertTrue($role->rPermissions->contains('key', 'users.create'));
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

        // First add a permission
        $role->rPermissions()->attach([$permission->id]);
        $this->assertCount(1, $role->rPermissions);

        // Then remove all
        $result = $this->roleService->updatePermissions($role, []);

        $this->assertTrue($result);
        $role->refresh();
        $this->assertCount(0, $role->rPermissions);
    }

    /**
     * Test update permissions with invalid keys returns false.
     */
    public function test_update_permissions_with_invalid_keys_returns_false(): void {
        $role = Role::create([
            'name'   => 'Test Role',
            'code'   => 'TEST_ROLE',
            'status' => Role::STATUS_ACTIVE,
        ]);

        $permissionKeys = ['invalid.permission.key'];

        // The syncPermissions method in Role model throws exception for missing keys
        // But it's wrapped in handleTransaction which catches exceptions and returns false
        $result = $this->roleService->updatePermissions($role, $permissionKeys);

        // Transaction should fail and return false
        $this->assertFalse($result);
    }
}
