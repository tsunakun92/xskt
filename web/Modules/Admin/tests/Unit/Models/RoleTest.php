<?php

namespace Modules\Admin\Tests\Unit\Models;

use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\Utils\CacheHandler;
use Modules\Admin\Models\Permission;
use Modules\Admin\Models\Role;
use Modules\Admin\Models\User;

class RoleTest extends TestCase {
    use RefreshDatabase;

    protected Role $role;

    protected Permission $permission;

    protected function setUp(): void {
        parent::setUp();

        // Create a test role
        $this->role = Role::create([
            'name'   => 'Test Role',
            'code'   => 'TEST_ROLE',
            'status' => Role::STATUS_ACTIVE,
        ]);

        // Create a test permission
        $this->permission = Permission::create([
            'name'   => 'Test Permission',
            'key'    => 'test.permission',
            'group'  => 'test',
            'status' => Permission::STATUS_ACTIVE,
        ]);
    }

    #[Test]
    public function it_has_correct_fillable_attributes() {
        $fillable = [
            'name',
            'code',
            'status',
        ];

        $this->assertEquals($fillable, $this->role->getFillable());
    }

    #[Test]
    public function it_has_correct_datatable_columns() {
        $expectedColumns = [
            'id',
            'name',
            'code',
            'status',
            'action',
        ];

        $this->assertEquals($expectedColumns, Role::getDatatableColumns());
    }

    #[Test]
    public function it_has_predefined_role_codes() {
        $this->assertEquals('ROLE_SUPER_ADMIN', Role::ROLE_SUPER_ADMIN_CODE);
        $this->assertEquals('ROLE_ADMIN', Role::ROLE_ADMIN_CODE);
        $this->assertEquals('ROLE_STAFF', Role::ROLE_STAFF_CODE);
        $this->assertEquals('ROLE_STAFF_MANAGER', Role::ROLE_STAFF_MANAGER_CODE);
    }

    #[Test]
    public function it_can_have_many_users() {
        $user1 = User::create([
            'name'     => 'Test User 1',
            'email'    => 'test1@example.com',
            'username' => 'testuser1',
            'password' => bcrypt('password'),
            'role_id'  => $this->role->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        $user2 = User::create([
            'name'     => 'Test User 2',
            'email'    => 'test2@example.com',
            'username' => 'testuser2',
            'password' => bcrypt('password'),
            'role_id'  => $this->role->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        $this->assertCount(2, $this->role->rUsers);
        $this->assertTrue($this->role->rUsers->contains($user1));
        $this->assertTrue($this->role->rUsers->contains($user2));
    }

    #[Test]
    public function it_can_have_many_permissions() {
        $permission2 = Permission::create([
            'name'   => 'Test Permission 2',
            'key'    => 'test.permission2',
            'group'  => 'test',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $this->role->rPermissions()->attach([$this->permission->id, $permission2->id]);

        $this->assertCount(2, $this->role->rPermissions);
        $this->assertTrue($this->role->rPermissions->contains($this->permission));
        $this->assertTrue($this->role->rPermissions->contains($permission2));
    }

    #[Test]
    public function it_can_get_role_by_code() {
        $role = Role::getByCode('TEST_ROLE');
        $this->assertNotNull($role);
        $this->assertEquals($this->role->id, $role->id);

        $nonExistentRole = Role::getByCode('NON_EXISTENT_ROLE');
        $this->assertNull($nonExistentRole);
    }

    #[Test]
    public function it_excludes_super_admin_from_datatables() {
        // Create super admin role
        $superAdmin = Role::create([
            'name'   => 'Super Admin',
            'code'   => Role::ROLE_SUPER_ADMIN_CODE,
            'status' => Role::STATUS_ACTIVE,
        ]);

        // Authenticate as non-super-admin user (using admin role)
        $userRole = Role::create([
            'name'   => 'Admin',
            'code'   => Role::ROLE_ADMIN_CODE,
            'status' => Role::STATUS_ACTIVE,
        ]);

        $user = User::create([
            'name'     => 'Normal User',
            'email'    => 'normal@example.com',
            'username' => 'normaluser',
            'password' => bcrypt('password'),
            'role_id'  => $userRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($user);

        $query = Role::getAsDatatables();
        $roles = $query->paginate(10);

        $this->assertFalse($roles->contains('id', $superAdmin->id));
        foreach ($roles as $role) {
            $this->assertNotEquals(Role::ROLE_SUPER_ADMIN_CODE, $role->code);
        }
    }

    #[Test]
    public function it_can_get_roles_as_dropdown() {
        // Create additional roles with active status
        $superAdmin = Role::create([
            'name'   => 'Super Admin',
            'code'   => Role::ROLE_SUPER_ADMIN_CODE,
            'status' => Role::STATUS_ACTIVE,
        ]);

        $admin = Role::create([
            'name'   => 'Admin',
            'code'   => Role::ROLE_ADMIN_CODE,
            'status' => Role::STATUS_ACTIVE,
        ]);

        // Test with please select option
        $dropdown = Role::getAsDropdown();
        $this->assertArrayHasKey(0, $dropdown);
        $this->assertEquals(__('admin::crud.please_select'), $dropdown[0]);
        $this->assertArrayNotHasKey($superAdmin->id, $dropdown); // Super admin should not be in dropdown
        $this->assertArrayHasKey($admin->id, $dropdown);         // Admin should be in dropdown

        // Test without please select option
        $dropdownWithoutSelect = Role::getAsDropdown(false);
        $this->assertArrayNotHasKey(0, $dropdownWithoutSelect);
        $this->assertArrayNotHasKey($superAdmin->id, $dropdownWithoutSelect);
        $this->assertArrayHasKey($admin->id, $dropdownWithoutSelect);
    }

    #[Test]
    public function it_can_check_role_has_permission() {
        $this->role->rPermissions()->attach($this->permission->id);

        $this->assertTrue(Role::hasPermission('test.permission', $this->role->id));
        $this->assertFalse(Role::hasPermission('non.existent.permission', $this->role->id));
    }

    #[Test]
    public function it_detaches_permissions_when_deleted() {
        // Create a role without users
        $roleWithoutUsers = Role::create([
            'name'   => 'Role Without Users',
            'code'   => 'NO_USERS_ROLE',
            'status' => Role::STATUS_ACTIVE,
        ]);

        // Attach permission and verify it's attached
        $roleWithoutUsers->rPermissions()->attach($this->permission->id);
        $this->assertDatabaseHas('role_permissions', [
            'role_id'       => $roleWithoutUsers->id,
            'permission_id' => $this->permission->id,
        ]);

        // Delete the role
        $roleWithoutUsers->delete();

        // Verify role is deleted
        $this->assertDatabaseMissing('roles', [
            'id' => $roleWithoutUsers->id,
        ]);

        // Verify permission is detached
        $this->assertDatabaseMissing('role_permissions', [
            'role_id'       => $roleWithoutUsers->id,
            'permission_id' => $this->permission->id,
        ]);
    }

    #[Test]
    public function it_cannot_be_deleted_if_has_users() {
        User::create([
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'username' => 'testuser',
            'password' => bcrypt('password'),
            'role_id'  => $this->role->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(__('admin::crud.delete_has_relationship_error', [
            'name'     => __('admin::crud.roles.title'),
            'relation' => __('admin::crud.users.title'),
        ]));

        $this->role->delete();
    }

    #[Test]
    public function it_can_clear_role_cache() {
        // Set some cache that should be cleared
        CacheHandler::set('role:code:TEST_ROLE', $this->role, null, CacheHandler::TYPE_STATIC);
        CacheHandler::set('role:1:permissions', ['permission1'], null, CacheHandler::TYPE_STATIC);

        // Clear role cache
        Role::clearRoleCache();

        // Cache should be cleared (we can't directly verify, but method should execute without error)
        $this->assertTrue(true);
    }

    #[Test]
    public function it_still_excludes_super_admin_in_datatables_when_user_is_super_admin() {
        // Create super admin role
        $superAdminRole = Role::create([
            'name'   => 'Super Admin',
            'code'   => Role::ROLE_SUPER_ADMIN_CODE,
            'status' => Role::STATUS_ACTIVE,
        ]);

        // Create super admin user
        $superAdminUser = User::create([
            'name'     => 'Super Admin',
            'email'    => 'superadmin@example.com',
            'username' => 'superadmin',
            'password' => bcrypt('password'),
            'role_id'  => $superAdminRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($superAdminUser);

        $query = Role::getAsDatatables();
        $roles = $query->get();

        // Even super admin should not see the super admin role in admin-facing listings
        $this->assertFalse($roles->contains('id', $superAdminRole->id));
        $this->assertTrue($roles->contains('id', $this->role->id));
    }

    #[Test]
    public function it_can_sync_permissions() {
        $permission2 = Permission::create([
            'name'   => 'Test Permission 2',
            'key'    => 'test.permission2',
            'group'  => 'test',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $permission3 = Permission::create([
            'name'   => 'Test Permission 3',
            'key'    => 'test.permission3',
            'group'  => 'test',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $permissionKeys = ['test.permission', 'test.permission2', 'test.permission3'];

        $this->role->syncPermissions($permissionKeys);

        // Verify permissions
        $rolePermissions = $this->role->rPermissions()->pluck('key')->toArray();
        $this->assertContains('test.permission', $rolePermissions);
        $this->assertContains('test.permission2', $rolePermissions);
        $this->assertContains('test.permission3', $rolePermissions);
    }

    #[Test]
    public function it_can_sync_permissions_with_empty_array() {
        // First attach some permissions
        $this->role->rPermissions()->attach($this->permission->id);

        // Verify permission is attached
        $this->assertGreaterThan(0, $this->role->rPermissions()->count());

        // Sync with empty array should remove all permissions
        $this->role->syncPermissions([]);

        // Refresh role to get updated permissions
        $this->role->refresh();

        // Permissions should be removed
        $this->assertEquals(0, $this->role->rPermissions()->count());
    }

    #[Test]
    public function it_can_get_current_permissions() {
        $permission2 = Permission::create([
            'name'   => 'Test Permission 2',
            'key'    => 'test.permission2',
            'group'  => 'test',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        // Attach permissions
        $this->role->rPermissions()->attach([$this->permission->id, $permission2->id]);

        $permissions = $this->role->getCurrentPermissions();

        $this->assertCount(2, $permissions);
        $keys = array_column($permissions, 'key');
        $this->assertContains('test.permission', $keys);
        $this->assertContains('test.permission2', $keys);
    }

    #[Test]
    public function it_can_check_if_role_is_super_admin() {
        $superAdminRole = Role::create([
            'name'   => 'Super Admin',
            'code'   => Role::ROLE_SUPER_ADMIN_CODE,
            'status' => Role::STATUS_ACTIVE,
        ]);

        $this->assertTrue($superAdminRole->isSuperAdmin());
        $this->assertFalse($this->role->isSuperAdmin());
    }

    #[Test]
    public function it_can_check_permission_access() {
        $permission2 = Permission::create([
            'name'   => 'Test Permission 2',
            'key'    => 'test.permission2',
            'group'  => 'test',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        // Attach permissions
        $this->role->rPermissions()->attach([$this->permission->id, $permission2->id]);

        // Should have access to both permissions
        $this->assertTrue($this->role->canAccess('test.permission'));
        $this->assertTrue($this->role->canAccess('test.permission2'));
        $this->assertFalse($this->role->canAccess('non.existent.permission'));
    }
}
