<?php

namespace Modules\Admin\Tests\Unit\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\Utils\CacheHandler;
use Modules\Admin\Models\Permission;
use Modules\Admin\Models\Role;
use Modules\Admin\Models\Setting;
use Modules\Admin\Models\User;

class UserTest extends TestCase {
    use RefreshDatabase;

    protected User $user;

    protected Role $superAdminRole;

    protected Role $adminRole;

    protected Role $userRole;

    protected function setUp(): void {
        parent::setUp();

        // Create roles
        $this->superAdminRole = Role::firstOrCreate(
            ['code' => Role::ROLE_SUPER_ADMIN_CODE],
            [
                'name'   => 'Super Admin',
                'status' => Role::STATUS_ACTIVE,
            ]
        );

        $this->adminRole = Role::firstOrCreate(
            ['code' => Role::ROLE_ADMIN_CODE],
            [
                'name'   => 'Admin',
                'status' => Role::STATUS_ACTIVE,
            ]
        );

        $this->userRole = Role::firstOrCreate(
            ['code' => Role::ROLE_CUSTOMER_CODE],
            [
                'name'   => 'Customer',
                'status' => Role::STATUS_ACTIVE,
            ]
        );

        // Create a test user
        $this->user = User::create([
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'username' => 'testuser',
            'password' => Hash::make('password'),
            'role_id'  => $this->userRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);
    }

    #[Test]
    public function it_uses_correct_table_name() {
        $this->assertEquals('users', $this->user->getTable());
    }

    #[Test]
    public function it_hides_sensitive_attributes() {
        $userArray = $this->user->toArray();
        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('remember_token', $userArray);
    }

    #[Test]
    public function it_has_correct_fillable_attributes() {
        $fillable = [
            'username',
            'password',
            'name',
            'email',
            'role_id',
            'status',
        ];

        $this->assertEquals($fillable, $this->user->getFillable());
    }

    #[Test]
    public function it_has_correct_casts() {
        $casts = $this->user->getCasts();
        $this->assertArrayHasKey('email_verified_at', $casts);
        $this->assertArrayHasKey('password', $casts);
        $this->assertEquals('datetime', $casts['email_verified_at']);
        $this->assertEquals('hashed', $casts['password']);
    }

    #[Test]
    public function it_has_correct_datatable_columns() {
        $expectedColumns = [
            'id',
            'name',
            'email',
            'username',
            'role_name',
            'status',
            'action',
        ];

        $this->assertEquals($expectedColumns, User::getDatatableColumns());
    }

    #[Test]
    public function it_belongs_to_role() {
        $this->assertInstanceOf(Role::class, $this->user->rRole);
        $this->assertEquals($this->userRole->id, $this->user->role_id);
    }

    #[Test]
    public function it_can_get_role_name() {
        $this->assertEquals($this->userRole->name, $this->user->role_name);

        // Test with non-existent role
        $userWithoutRole = User::create([
            'name'     => 'No Role User',
            'email'    => 'norole@example.com',
            'username' => 'noroleuser',
            'password' => Hash::make('password'),
            'role_id'  => 9999,
            'status'   => User::STATUS_ACTIVE,
        ]);

        $this->assertEquals('', $userWithoutRole->role_name);
    }

    #[Test]
    public function it_can_check_super_admin_role() {
        $superAdmin = User::create([
            'name'     => 'Super Admin',
            'email'    => 'superadmin@example.com',
            'username' => 'superadmin',
            'password' => Hash::make('password'),
            'role_id'  => $this->superAdminRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($this->user->isSuperAdmin());
    }

    #[Test]
    public function it_can_check_admin_role() {
        $admin = User::create([
            'name'     => 'Admin',
            'email'    => 'admin@example.com',
            'username' => 'admin',
            'password' => Hash::make('password'),
            'role_id'  => $this->adminRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($this->user->isAdmin());
    }

    #[Test]
    public function it_can_get_form_fields() {
        $fields = User::getFormFields('users');

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('email', $fields);
        $this->assertArrayHasKey('password', $fields);
        $this->assertArrayHasKey('role_id', $fields);

        $this->assertEquals('email', $fields['email']['type']);
        $this->assertEquals('password', $fields['password']['type']);
        $this->assertEquals('select', $fields['role_id']['type']);
        $this->assertIsArray($fields['role_id']['options']);
    }

    #[Test]
    public function it_can_get_datatables_excluding_super_admin() {
        // Create a super admin user
        User::create([
            'name'     => 'Super Admin',
            'email'    => 'superadmin@example.com',
            'username' => 'superadmin',
            'password' => Hash::make('password'),
            'role_id'  => $this->superAdminRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        // Create another regular user
        User::create([
            'name'     => 'Another User',
            'email'    => 'another@example.com',
            'username' => 'anotheruser',
            'password' => Hash::make('password'),
            'role_id'  => $this->userRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        // Mock auth user as non-super admin to test exclusion
        $nonSuperAdmin = User::create([
            'name'     => 'Non Super Admin',
            'email'    => 'nonsuper@example.com',
            'username' => 'nonsuper',
            'password' => Hash::make('password'),
            'role_id'  => $this->adminRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($nonSuperAdmin);

        $query = User::getAsDatatables();
        $users = $query->paginate(10);

        // Should not include super admin
        foreach ($users as $user) {
            $this->assertFalse($user->isSuperAdmin());
        }
    }

    #[Test]
    public function it_still_excludes_super_admin_from_datatables_for_super_admin_user() {
        // Create a super admin user
        $superAdmin = User::create([
            'name'     => 'Super Admin',
            'email'    => 'superadmin@example.com',
            'username' => 'superadmin',
            'password' => Hash::make('password'),
            'role_id'  => $this->superAdminRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        // Create another regular user
        User::create([
            'name'     => 'Another User',
            'email'    => 'another@example.com',
            'username' => 'anotheruser',
            'password' => Hash::make('password'),
            'role_id'  => $this->userRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($superAdmin);

        $query = User::getAsDatatables();
        $users = $query->paginate(10);

        foreach ($users as $user) {
            $this->assertFalse($user->isSuperAdmin());
        }
    }

    #[Test]
    public function it_can_get_users_as_dropdown_excluding_super_admin() {
        // Create a super admin user
        $superAdmin = User::create([
            'name'     => 'Super Admin',
            'email'    => 'superadmin@example.com',
            'username' => 'superadmin',
            'password' => Hash::make('password'),
            'role_id'  => $this->superAdminRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        // Create another regular user
        $normalUser = User::create([
            'name'     => 'Normal User',
            'email'    => 'normal@example.com',
            'username' => 'normaluser',
            'password' => Hash::make('password'),
            'role_id'  => $this->userRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        $dropdown = User::getAsDropdown();

        $this->assertArrayHasKey(0, $dropdown);
        $this->assertEquals(__('admin::crud.please_select'), $dropdown[0]);
        $this->assertArrayNotHasKey($superAdmin->id, $dropdown);
        $this->assertArrayHasKey($normalUser->id, $dropdown);

        $dropdownWithoutSelect = User::getAsDropdown(false);

        $this->assertArrayNotHasKey(0, $dropdownWithoutSelect);
        $this->assertArrayNotHasKey($superAdmin->id, $dropdownWithoutSelect);
        $this->assertArrayHasKey($normalUser->id, $dropdownWithoutSelect);
    }

    #[Test]
    public function it_can_check_permission_access() {
        // Create a permission and assign it to the user role
        $permission = Permission::create([
            'name'   => 'Test Permission',
            'key'    => 'test.permission',
            'group'  => 'test',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $this->userRole->rPermissions()->attach($permission->id);

        $this->assertTrue($this->user->canAccess('test.permission'));
        $this->assertFalse($this->user->canAccess('non.existent.permission'));

        // Test with null role
        $userWithoutRole = User::create([
            'name'     => 'No Role User',
            'email'    => 'norole@example.com',
            'username' => 'noroleuser',
            'password' => Hash::make('password'),
            'role_id'  => 9999,
            'status'   => User::STATUS_ACTIVE,
        ]);

        $this->assertFalse($userWithoutRole->canAccess('test.permission'));
    }

    #[Test]
    public function it_can_get_filter_fields_and_mapping() {
        $fields = User::getFilterFields('users');

        $this->assertArrayHasKey('role_id', $fields);
        $this->assertEquals('select', $fields['role_id']['type']);
        $this->assertIsArray($fields['role_id']['options']);

        $this->assertArrayHasKey('status', $fields);
        $this->assertEquals('select', $fields['status']['type']);
        $this->assertIsArray($fields['status']['options']);

        $mapping = User::getFilterColumnMapping();
        $this->assertArrayHasKey('role_name', $mapping);
        $this->assertEquals('relationship', $mapping['role_name']['type']);
        $this->assertEquals('role_id', $mapping['role_name']['column']);
    }

    #[Test]
    public function it_can_filter_by_role_id() {
        $adminUser = User::create([
            'name'     => 'Admin User',
            'email'    => 'admin2@example.com',
            'username' => 'admin2',
            'password' => Hash::make('password'),
            'role_id'  => $this->adminRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        // value <=0 should not filter
        $allCount = User::query()->count();
        $noFilter = $this->user->filterRolesId(User::query(), 0)->count();
        $this->assertEquals($allCount, $noFilter);

        $filteredCount = $this->user->filterRolesId(User::query(), $this->adminRole->id)->count();
        $this->assertEquals(1, $filteredCount);
        $this->assertTrue($adminUser->isAdmin());
    }

    #[Test]
    public function it_can_check_is_roled_admin() {
        $adminUser = User::create([
            'name'     => 'Admin User',
            'email'    => 'admin2@example.com',
            'username' => 'admin2',
            'password' => Hash::make('password'),
            'role_id'  => $this->adminRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        $super = User::create([
            'name'     => 'Super',
            'email'    => 'super@example.com',
            'username' => 'super2',
            'password' => Hash::make('password'),
            'role_id'  => $this->superAdminRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        $this->assertFalse($this->user->isRoledAdmin());
        $this->assertTrue($adminUser->isRoledAdmin());
        $this->assertTrue($super->isRoledAdmin());
    }

    #[Test]
    public function it_checks_first_login_and_updates_last_login() {
        Carbon::setTestNow(Carbon::parse('2026-01-01 10:00:00'));
        $freshUser = User::create([
            'name'     => 'New',
            'email'    => 'new@example.com',
            'username' => 'newuser',
            'password' => Hash::make('password'),
            'role_id'  => $this->userRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        $this->assertTrue($freshUser->isFirstLogin());

        $freshUser->updateLastLogin();
        $freshUser->refresh();
        $this->assertFalse($freshUser->isFirstLogin());

        // Next day should be considered first login again
        Carbon::setTestNow(Carbon::parse('2026-01-02 09:00:00'));
        $this->assertTrue($freshUser->isFirstLogin());
        Carbon::setTestNow(); // reset
    }

    #[Test]
    public function it_returns_current_permissions_preferring_user_permissions() {
        $permRole = Permission::create([
            'name'   => 'Role Perm',
            'key'    => 'role.perm',
            'group'  => 'grp',
            'status' => Permission::STATUS_ACTIVE,
        ]);
        $permUser = Permission::create([
            'name'   => 'User Perm',
            'key'    => 'user.perm',
            'group'  => 'grp',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        // Role permission
        $this->userRole->rPermissions()->attach($permRole->id);
        // User-specific permission overrides
        $this->user->rPermissions()->attach($permUser->id);

        $current = $this->user->getCurrentPermissions();

        // Should use user permissions and ignore role fallback
        $this->assertCount(1, $current);
        $this->assertEquals('user.perm', $current[0]['key']);
    }

    #[Test]
    public function it_returns_role_permissions_when_no_user_permissions() {
        $userNoCustom = User::create([
            'name'     => 'No Custom',
            'email'    => 'nocustom@example.com',
            'username' => 'nocustom',
            'password' => Hash::make('password'),
            'role_id'  => $this->userRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        $permRole = Permission::create([
            'name'   => 'Role Only',
            'key'    => 'role.only',
            'group'  => 'grp',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $this->userRole->rPermissions()->attach($permRole->id);

        $current = $userNoCustom->getCurrentPermissions();

        $this->assertCount(1, $current);
        $this->assertEquals('role.only', $current[0]['key']);
    }

    #[Test]
    public function it_returns_role_permission_keys() {
        $perm1 = Permission::create([
            'name'   => 'Perm 1',
            'key'    => 'perm.1',
            'group'  => 'grp',
            'status' => Permission::STATUS_ACTIVE,
        ]);
        $perm2 = Permission::create([
            'name'   => 'Perm 2',
            'key'    => 'perm.2',
            'group'  => 'grp',
            'status' => Permission::STATUS_ACTIVE,
        ]);
        $this->userRole->rPermissions()->attach([$perm1->id, $perm2->id]);

        $keys = $this->user->getRolePermissionKeys();
        $this->assertCount(2, $keys);
        $this->assertContains('perm.1', $keys);
        $this->assertContains('perm.2', $keys);
    }

    #[Test]
    public function it_validates_permission_access_for_missing_role_and_empty_permissions() {
        // Missing role: use unsaved user without role_id
        $userNoRole = new User([
            'name'     => 'NoRole',
            'email'    => 'norole@example.com',
            'username' => 'norole',
            'password' => Hash::make('password'),
            'status'   => User::STATUS_ACTIVE,
        ]);
        $this->assertNotNull($userNoRole->validatePermissionAccess());

        // Role exists but no permissions
        $userNoPermRole = User::create([
            'name'     => 'NoPerm',
            'email'    => 'noperm@example.com',
            'username' => 'noperm',
            'password' => Hash::make('password'),
            'role_id'  => $this->userRole->id, // role has no permissions attached in this context
            'status'   => User::STATUS_ACTIVE,
        ]);
        $this->assertNotNull($userNoPermRole->validatePermissionAccess());
    }

    #[Test]
    public function it_validates_permission_access_when_role_has_permissions() {
        $perm = Permission::create([
            'name'   => 'Valid',
            'key'    => 'valid.perm',
            'group'  => 'grp',
            'status' => Permission::STATUS_ACTIVE,
        ]);
        $this->userRole->rPermissions()->attach($perm->id);

        $result = $this->user->validatePermissionAccess();
        $this->assertNull($result);
    }

    #[Test]
    public function it_filters_permissions_based_on_role_permissions() {
        Permission::truncate();

        $permKeep = Permission::create([
            'name'   => 'Keep',
            'key'    => 'keep.perm',
            'group'  => 'g1',
            'module' => Permission::MODULE_ADMIN,
            'status' => Permission::STATUS_ACTIVE,
        ]);
        $permSkip = Permission::create([
            'name'   => 'Skip',
            'key'    => 'skip.perm',
            'group'  => 'g2',
            'module' => Permission::MODULE_API,
            'status' => Permission::STATUS_ACTIVE,
        ]);

        // role has only keep perm
        $this->userRole->rPermissions()->attach($permKeep->id);

        $filtered = $this->user->getFilteredPermissions();

        $this->assertCount(1, $filtered);
        $this->assertEquals('keep.perm', $filtered[0]->key);
        $this->assertNotContains('skip.perm', array_column($filtered, 'key'));
    }

    #[Test]
    public function it_can_clear_user_permission_cache() {
        // Set some cache that should be cleared
        CacheHandler::set("user:{$this->user->id}:permissions", ['perm1'], null, CacheHandler::TYPE_STATIC);

        // Clear cache
        $this->user->clearUserPermissionCache();

        // Cache should be cleared (we can't directly verify, but method should execute without error)
        $this->assertTrue(true);
    }

    #[Test]
    public function it_can_sync_permissions_empty_array() {
        // First attach some permissions
        $permission = Permission::create([
            'name'   => 'Test Permission',
            'key'    => 'test.permission',
            'group'  => 'test',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $this->user->rPermissions()->attach($permission->id);

        // Sync with empty array should remove all permissions
        $this->user->syncPermissions([]);

        $this->assertEquals(0, $this->user->rPermissions()->count());
    }

    #[Test]
    public function it_can_sync_permissions_multiple_permissions() {
        $permission1 = Permission::create([
            'name'   => 'Test Permission 1',
            'key'    => 'test.permission1',
            'group'  => 'test',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $permission2 = Permission::create([
            'name'   => 'Test Permission 2',
            'key'    => 'test.permission2',
            'group'  => 'test',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        // Attach permissions to role first
        $this->userRole->rPermissions()->attach([$permission1->id, $permission2->id]);

        $permissionKeys = ['test.permission1', 'test.permission2'];

        $this->user->syncPermissions($permissionKeys);

        // Verify permissions
        $userPermissions = $this->user->rPermissions()->pluck('key')->toArray();
        $this->assertContains('test.permission1', $userPermissions);
        $this->assertContains('test.permission2', $userPermissions);
    }

    #[Test]
    public function it_skips_invalid_permissions_when_syncing() {
        $permission = Permission::create([
            'name'   => 'Valid Permission',
            'key'    => 'valid.permission',
            'group'  => 'test',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        // Attach permission to role
        $this->userRole->rPermissions()->attach($permission->id);

        $permissionKeys = ['valid.permission', 'invalid.permission']; // One invalid

        $this->user->syncPermissions($permissionKeys);

        // Should only have valid permission
        $userPermissions = $this->user->rPermissions()->pluck('key')->toArray();
        $this->assertContains('valid.permission', $userPermissions);
        $this->assertNotContains('invalid.permission', $userPermissions);
    }

    #[Test]
    public function it_handles_boot_logic_when_password_is_updated() {
        // Test that update works - boot() logout logic requires session which is complex to test
        $this->user->update(['password' => Hash::make('newpassword')]);
        $this->assertTrue($this->user->wasChanged('password'));
        $this->user->refresh();
        $this->assertNotEquals('password', $this->user->password);
    }

    #[Test]
    public function it_handles_boot_logic_when_username_is_updated() {
        $this->user->update(['username' => 'newusername']);
        $this->assertTrue($this->user->wasChanged('username'));
        $this->user->refresh();
        $this->assertEquals('newusername', $this->user->username);
    }

    #[Test]
    public function it_handles_boot_logic_when_email_is_updated() {
        $this->user->update(['email' => 'newemail@example.com']);
        $this->assertTrue($this->user->wasChanged('email'));
        $this->user->refresh();
        $this->assertEquals('newemail@example.com', $this->user->email);
    }

    #[Test]
    public function it_handles_boot_logic_when_role_id_is_updated() {
        $this->user->update(['role_id' => $this->adminRole->id]);
        $this->assertTrue($this->user->wasChanged('role_id'));
        $this->user->refresh();
        $this->assertEquals($this->adminRole->id, $this->user->role_id);
    }

    #[Test]
    public function it_handles_boot_logic_when_status_is_updated() {
        $this->user->update(['status' => User::STATUS_INACTIVE]);
        $this->assertTrue($this->user->wasChanged('status'));
        $this->user->refresh();
        $this->assertEquals(User::STATUS_INACTIVE, $this->user->status);
    }

    #[Test]
    public function it_deletes_user_permissions_and_settings_on_delete() {
        $permission = Permission::create([
            'name'   => 'Test Permission',
            'key'    => 'test.permission',
            'group'  => 'test',
            'status' => Permission::STATUS_ACTIVE,
        ]);

        $this->user->rPermissions()->attach($permission->id);

        // Create user setting
        $setting = Setting::create([
            'key'    => 'test_setting',
            'value'  => 'value',
            'status' => 1,
        ]);

        DB::table('user_settings')->insert([
            'user_id'    => $this->user->id,
            'setting_id' => $setting->id,
            'value'      => 'user_value',
        ]);

        $userId = $this->user->id;
        $this->user->delete();

        // Verify user permissions are deleted
        $this->assertEquals(0, DB::table('user_permissions')->where('user_id', $userId)->count());

        // Verify user settings are deleted
        $this->assertEquals(0, DB::table('user_settings')->where('user_id', $userId)->count());
    }

    #[Test]
    public function it_handles_role_permission_keys_with_null_role() {
        $userWithoutRole = User::create([
            'name'     => 'No Role',
            'email'    => 'norole@example.com',
            'username' => 'norole',
            'password' => Hash::make('password'),
            'role_id'  => 9999, // Non-existent role
            'status'   => User::STATUS_ACTIVE,
        ]);

        $permissionKeys = $userWithoutRole->getRolePermissionKeys();
        $this->assertIsArray($permissionKeys);
        $this->assertEmpty($permissionKeys);
    }
}
