<?php

namespace Modules\Admin\Tests\Unit\Models;

use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use Modules\Admin\Models\Permission;
use Modules\Admin\Models\Role;

class PermissionTest extends TestCase {
    use RefreshDatabase;

    protected Permission $permission;

    protected Role $role;

    protected function setUp(): void {
        parent::setUp();

        // Create a test permission
        $this->permission = Permission::create([
            'name'   => 'Test Permission',
            'key'    => 'test.permission',
            'group'  => 'test',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        // Create a test role
        $this->role = Role::create([
            'name'   => 'Test Role',
            'code'   => 'TEST_ROLE',
            'status' => Role::STATUS_ACTIVE,
        ]);
    }

    #[Test]
    public function it_has_correct_fillable_attributes() {
        $fillable = [
            'name',
            'key',
            'group',
            'module',
            'status',
        ];

        $this->assertEquals($fillable, $this->permission->getFillable());
    }

    #[Test]
    public function it_has_correct_datatable_columns() {
        $expectedColumns = [
            'id',
            'name',
            'key',
            'group',
            'module',
            'status',
            'action',
        ];

        $this->assertEquals($expectedColumns, Permission::getDatatableColumns());
    }

    #[Test]
    public function it_can_have_many_roles() {
        $role2 = Role::create([
            'name'   => 'Test Role 2',
            'code'   => 'TEST_ROLE_2',
            'status' => Role::STATUS_ACTIVE,
        ]);

        $this->permission->rRoles()->attach([$this->role->id, $role2->id]);

        $this->assertCount(2, $this->permission->rRoles);
        $this->assertTrue($this->permission->rRoles->contains($this->role));
        $this->assertTrue($this->permission->rRoles->contains($role2));
    }

    #[Test]
    public function it_can_get_form_fields() {
        // Create some permissions with different groups
        Permission::create([
            'name'   => 'Permission 1',
            'key'    => 'permission.1',
            'group'  => 'group1',
            'status' => Permission::STATUS_ACTIVE,
            'module' => Permission::MODULE_ADMIN,
        ]);

        Permission::create([
            'name'   => 'Permission 2',
            'key'    => 'permission.2',
            'group'  => 'group1',
            'status' => Permission::STATUS_ACTIVE,
            'module' => Permission::MODULE_HR,
        ]);

        Permission::create([
            'name'   => 'Permission 3',
            'key'    => 'permission.3',
            'group'  => 'group2',
            'status' => Permission::STATUS_ACTIVE,
            'module' => Permission::MODULE_API,
        ]);

        $fields = Permission::getFormFields('permissions');

        // At minimum, ensure group field exists and has correct type
        $this->assertIsArray($fields);
        $this->assertArrayHasKey('group', $fields);
        $this->assertEquals('text', $fields['group']['type']);

        // Modules select options
        $this->assertArrayHasKey('module', $fields);
        $this->assertEquals('select', $fields['module']['type']);
        $this->assertIsArray($fields['module']['options']);
    }

    #[Test]
    public function it_can_be_deleted_if_has_no_roles() {
        // Delete the permission without any roles
        $this->permission->delete();

        // Verify permission is deleted
        $this->assertDatabaseMissing('permissions', [
            'id' => $this->permission->id,
        ]);
    }

    #[Test]
    public function it_cannot_be_deleted_if_has_roles() {
        // Create a permission with roles
        $permissionWithRoles = Permission::create([
            'name'   => 'Permission With Roles',
            'key'    => 'permission.with.roles',
            'group'  => 'test',
            'status' => Permission::STATUS_ACTIVE,
            'module' => Permission::MODULE_ADMIN,
        ]);

        // Attach a role
        $permissionWithRoles->rRoles()->attach($this->role->id);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(__('admin::crud.delete_has_relationship_error', [
            'name'     => __('admin::crud.permissions.title'),
            'relation' => __('admin::crud.roles.title'),
        ]));

        $permissionWithRoles->delete();
    }

    #[Test]
    public function it_returns_modules_array() {
        $modulesWithSelect = Permission::getModulesArray();
        $this->assertArrayHasKey('', $modulesWithSelect);
        $modulesNoSelect = Permission::getModulesArray(false);
        $this->assertArrayNotHasKey('', $modulesNoSelect);
        $this->assertArrayHasKey(Permission::MODULE_ADMIN, $modulesNoSelect);
        $this->assertArrayHasKey(Permission::MODULE_HR, $modulesNoSelect);
        $this->assertArrayHasKey(Permission::MODULE_CRM, $modulesNoSelect);
        $this->assertArrayHasKey(Permission::MODULE_API, $modulesNoSelect);
    }

    #[Test]
    public function it_groups_permissions_by_module_and_group() {
        Permission::truncate();

        Permission::create([
            'name'   => 'Perm Admin',
            'key'    => 'perm.admin',
            'group'  => 'g1',
            'module' => Permission::MODULE_ADMIN,
            'status' => Permission::STATUS_ACTIVE,
        ]);
        Permission::create([
            'name'   => 'Perm No Module',
            'key'    => 'perm.nomodule',
            'group'  => 'g2',
            'module' => null,
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $grouped = Permission::groupByModuleGroup();
        $this->assertArrayHasKey(Permission::MODULE_ADMIN, $grouped);
        $this->assertArrayHasKey('g1', $grouped[Permission::MODULE_ADMIN]);
        $this->assertArrayHasKey('', $grouped); // bucket for no module
        $this->assertArrayHasKey('g2', $grouped['']);
    }

    #[Test]
    public function it_groups_permissions_by_module_and_group_with_filter() {
        Permission::truncate();

        $perm1 = Permission::create([
            'name'   => 'Perm Admin 1',
            'key'    => 'perm.admin.1',
            'group'  => 'group1',
            'module' => Permission::MODULE_ADMIN,
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $perm2 = Permission::create([
            'name'   => 'Perm Admin 2',
            'key'    => 'perm.admin.2',
            'group'  => 'group1',
            'module' => Permission::MODULE_ADMIN,
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $perm3 = Permission::create([
            'name'   => 'Perm HR 1',
            'key'    => 'perm.hr.1',
            'group'  => 'group2',
            'module' => Permission::MODULE_HR,
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $perm4 = Permission::create([
            'name'   => 'Perm No Module',
            'key'    => 'perm.nomodule',
            'group'  => 'group3',
            'module' => null,
            'status' => Permission::STATUS_ACTIVE,
        ]);

        // Filter with only admin permissions
        $filteredKeys = [$perm1->key, $perm2->key];
        $grouped      = Permission::groupByModuleGroupFiltered($filteredKeys);

        $this->assertArrayHasKey(Permission::MODULE_ADMIN, $grouped);
        $this->assertArrayHasKey('group1', $grouped[Permission::MODULE_ADMIN]);
        $this->assertCount(2, $grouped[Permission::MODULE_ADMIN]['group1']);
        $this->assertArrayNotHasKey(Permission::MODULE_HR, $grouped);

        // Filter with HR and no module permissions
        $filteredKeys = [$perm3->key, $perm4->key];
        $grouped      = Permission::groupByModuleGroupFiltered($filteredKeys);

        $this->assertArrayHasKey(Permission::MODULE_HR, $grouped);
        $this->assertArrayHasKey('group2', $grouped[Permission::MODULE_HR]);
        $this->assertArrayHasKey('', $grouped);
        $this->assertArrayHasKey('group3', $grouped['']);
        $this->assertArrayNotHasKey(Permission::MODULE_ADMIN, $grouped);

        // Filter with empty array
        $grouped = Permission::groupByModuleGroupFiltered([]);
        $this->assertEmpty($grouped);
    }

    #[Test]
    public function it_returns_filtered_modules_array() {
        Permission::truncate();

        Permission::create([
            'name'   => 'Perm Admin 1',
            'key'    => 'perm.admin.1',
            'group'  => 'group1',
            'module' => Permission::MODULE_ADMIN,
            'status' => Permission::STATUS_ACTIVE,
        ]);

        Permission::create([
            'name'   => 'Perm Admin 2',
            'key'    => 'perm.admin.2',
            'group'  => 'group1',
            'module' => Permission::MODULE_ADMIN,
            'status' => Permission::STATUS_ACTIVE,
        ]);

        Permission::create([
            'name'   => 'Perm HR 1',
            'key'    => 'perm.hr.1',
            'group'  => 'group2',
            'module' => Permission::MODULE_HR,
            'status' => Permission::STATUS_ACTIVE,
        ]);

        Permission::create([
            'name'   => 'Perm CRM 1',
            'key'    => 'perm.crm.1',
            'group'  => 'group3',
            'module' => Permission::MODULE_CRM,
            'status' => Permission::STATUS_ACTIVE,
        ]);

        // Filter with admin and HR permissions
        $filteredKeys    = ['perm.admin.1', 'perm.hr.1'];
        $filteredModules = Permission::getModulesArrayFiltered($filteredKeys, false);

        $this->assertArrayHasKey(Permission::MODULE_ADMIN, $filteredModules);
        $this->assertArrayHasKey(Permission::MODULE_HR, $filteredModules);
        $this->assertArrayNotHasKey(Permission::MODULE_CRM, $filteredModules);
        $this->assertArrayNotHasKey(Permission::MODULE_API, $filteredModules);

        // Filter with only admin permissions
        $filteredKeys    = ['perm.admin.1', 'perm.admin.2'];
        $filteredModules = Permission::getModulesArrayFiltered($filteredKeys, false);

        $this->assertArrayHasKey(Permission::MODULE_ADMIN, $filteredModules);
        $this->assertCount(1, $filteredModules);

        // Filter with empty array
        $filteredModules = Permission::getModulesArrayFiltered([], false);
        $this->assertEmpty($filteredModules);

        // Filter with isAddPleaseSelect = true
        $filteredKeys    = ['perm.admin.1', 'perm.hr.1'];
        $filteredModules = Permission::getModulesArrayFiltered($filteredKeys, true);

        $this->assertArrayHasKey('', $filteredModules);
        $this->assertArrayHasKey(Permission::MODULE_ADMIN, $filteredModules);
        $this->assertArrayHasKey(Permission::MODULE_HR, $filteredModules);
    }

    #[Test]
    public function it_returns_empty_filtered_modules_when_no_permissions_match() {
        Permission::truncate();

        Permission::create([
            'name'   => 'Perm Admin 1',
            'key'    => 'perm.admin.1',
            'group'  => 'group1',
            'module' => Permission::MODULE_ADMIN,
            'status' => Permission::STATUS_ACTIVE,
        ]);

        // Filter with non-existent permission keys
        $filteredKeys    = ['non.existent.1', 'non.existent.2'];
        $filteredModules = Permission::getModulesArrayFiltered($filteredKeys, false);

        $this->assertEmpty($filteredModules);
    }
}
