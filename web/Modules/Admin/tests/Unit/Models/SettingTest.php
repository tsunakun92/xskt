<?php

namespace Modules\Admin\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Tests\TestCase;

use Modules\Admin\Models\Role;
use Modules\Admin\Models\Setting;
use Modules\Admin\Models\User;

class SettingTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        // Clear static caches before each test
        $reflection = new ReflectionClass(Setting::class);
        foreach (['aSettings', 'userCache', 'settingCache', 'userSettingCache'] as $propertyName) {
            if ($reflection->hasProperty($propertyName)) {
                $property = $reflection->getProperty($propertyName);
                $property->setAccessible(true);
                if ($propertyName === 'aSettings') {
                    $property->setValue(null);
                } else {
                    $property->setValue([]);
                }
            }
        }
    }

    public function test_get_value_returns_setting_value() {
        Setting::create([
            'key'    => 'test_key',
            'value'  => 'test_value',
            'status' => 1,
        ]);

        $this->assertEquals('test_value', Setting::getValue('test_key'));
    }

    public function test_get_value_returns_default_when_not_found() {
        $this->assertEquals('default', Setting::getValue('non_existent', 'default'));
    }

    public function test_get_value_caches_settings() {
        Setting::create([
            'key'    => 'cache_test',
            'value'  => 'original',
            'status' => 1,
        ]);

        $this->assertEquals('original', Setting::getValue('cache_test'));

        // Update directly in DB (bypassing model to test cache)
        DB::table('settings')->where('key', 'cache_test')->update(['value' => 'updated']);

        // Should still return cached value
        $this->assertEquals('original', Setting::getValue('cache_test'));
    }

    public function test_get_value_clears_cache_on_create() {
        Setting::create([
            'key'    => 'before',
            'value'  => 'before_value',
            'status' => 1,
        ]);

        $this->assertEquals('before_value', Setting::getValue('before'));

        Setting::create([
            'key'    => 'after',
            'value'  => 'after_value',
            'status' => 1,
        ]);

        // Cache should be cleared, new setting should be available
        $this->assertEquals('after_value', Setting::getValue('after'));
    }

    public function test_is_maintenance_returns_boolean() {
        Setting::create([
            'key'    => 'maintenance_mode',
            'value'  => '1',
            'status' => 1,
        ]);

        $this->assertTrue(Setting::isMaintenance());

        DB::table('settings')->where('key', 'maintenance_mode')->update(['value' => '0']);

        // Clear cache
        $reflection = new ReflectionClass(Setting::class);
        $property   = $reflection->getProperty('aSettings');
        $property->setAccessible(true);
        $property->setValue(null);

        $this->assertFalse(Setting::isMaintenance());
    }

    public function test_get_last_sync_time_returns_value() {
        Setting::create([
            'key'    => 'last_sync_time',
            'value'  => '2024-01-01 12:00:00',
            'status' => 1,
        ]);

        $this->assertEquals('2024-01-01 12:00:00', Setting::getLastSyncTime());
    }

    public function test_get_rules_returns_array() {
        Setting::create([
            'key'    => 'rules',
            'value'  => '{"rule1": "value1", "rule2": "value2"}',
            'status' => 1,
        ]);

        $rules = Setting::getRules();
        $this->assertIsArray($rules);
        $this->assertEquals('value1', $rules['rule1']);
        $this->assertEquals('value2', $rules['rule2']);
    }

    public function test_get_rules_returns_empty_array_for_invalid_json() {
        Setting::create([
            'key'    => 'rules',
            'value'  => 'invalid json',
            'status' => 1,
        ]);

        $rules = Setting::getRules();
        $this->assertIsArray($rules);
        $this->assertEmpty($rules);
    }

    public function test_get_show_link_returns_boolean() {
        Setting::create([
            'key'    => 'show_link',
            'value'  => '1',
            'status' => 1,
        ]);

        $this->assertTrue(Setting::getShowLink());

        DB::table('settings')->where('key', 'show_link')->update(['value' => '0']);

        // Clear cache
        $reflection = new ReflectionClass(Setting::class);
        $property   = $reflection->getProperty('aSettings');
        $property->setAccessible(true);
        $property->setValue(null);

        $this->assertFalse(Setting::getShowLink());
    }

    public function test_get_value_for_user_returns_global_value_when_no_override() {
        $role = Role::create([
            'name'   => 'Test Role',
            'code'   => 'TEST_ROLE',
            'status' => 1,
        ]);

        $user = User::create([
            'username' => 'testuser',
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
            'status'   => 1,
        ]);

        Setting::create([
            'key'    => 'test_setting',
            'value'  => 'global_value',
            'status' => 1,
        ]);

        $value = Setting::getValueForUser($user->id, 'test_setting');
        $this->assertEquals('global_value', $value);
    }

    public function test_get_value_for_user_returns_override_value_when_exists() {
        $role = Role::create([
            'name'   => 'Test Role',
            'code'   => 'TEST_ROLE',
            'status' => 1,
        ]);

        $user = User::create([
            'username' => 'testuser',
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
            'status'   => 1,
        ]);

        $setting = Setting::create([
            'key'    => 'test_setting',
            'value'  => 'global_value',
            'status' => 1,
        ]);

        DB::table('user_settings')->insert([
            'user_id'    => $user->id,
            'setting_id' => $setting->id,
            'value'      => 'user_override',
        ]);

        $value = Setting::getValueForUser($user->id, 'test_setting');
        $this->assertEquals('user_override', $value);
    }

    public function test_get_value_for_user_returns_global_for_super_admin() {
        $superAdminRole = Role::create([
            'name'   => 'Super Admin',
            'code'   => Role::ROLE_SUPER_ADMIN_CODE,
            'status' => 1,
        ]);

        $superAdmin = User::create([
            'username' => 'superadmin',
            'name'     => 'Super Admin',
            'email'    => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'role_id'  => $superAdminRole->id,
            'status'   => 1,
        ]);

        Setting::create([
            'key'    => 'test_setting',
            'value'  => 'global_value',
            'status' => 1,
        ]);

        $value = Setting::getValueForUser($superAdmin->id, 'test_setting');
        $this->assertEquals('global_value', $value);
    }

    public function test_set_value_for_user_creates_override() {
        $role = Role::create([
            'name'   => 'Test Role',
            'code'   => 'TEST_ROLE',
            'status' => 1,
        ]);

        $user = User::create([
            'username' => 'testuser',
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
            'status'   => 1,
        ]);

        $setting = Setting::create([
            'key'    => 'test_setting',
            'value'  => 'global_value',
            'status' => 1,
        ]);

        $result = Setting::setValueForUser($user->id, 'test_setting', 'user_value');
        $this->assertTrue($result);

        $this->assertDatabaseHas('user_settings', [
            'user_id'    => $user->id,
            'setting_id' => $setting->id,
            'value'      => 'user_value',
        ]);
    }

    public function test_set_value_for_user_updates_existing_override() {
        $role = Role::create([
            'name'   => 'Test Role',
            'code'   => 'TEST_ROLE',
            'status' => 1,
        ]);

        $user = User::create([
            'username' => 'testuser',
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
            'status'   => 1,
        ]);

        $setting = Setting::create([
            'key'    => 'test_setting',
            'value'  => 'global_value',
            'status' => 1,
        ]);

        DB::table('user_settings')->insert([
            'user_id'    => $user->id,
            'setting_id' => $setting->id,
            'value'      => 'old_value',
        ]);

        $result = Setting::setValueForUser($user->id, 'test_setting', 'new_value');
        $this->assertTrue($result);

        $this->assertDatabaseHas('user_settings', [
            'user_id'    => $user->id,
            'setting_id' => $setting->id,
            'value'      => 'new_value',
        ]);
    }

    public function test_set_value_for_user_prevents_super_admin_override() {
        $superAdminRole = Role::create([
            'name'   => 'Super Admin',
            'code'   => Role::ROLE_SUPER_ADMIN_CODE,
            'status' => 1,
        ]);

        $superAdmin = User::create([
            'username' => 'superadmin',
            'name'     => 'Super Admin',
            'email'    => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'role_id'  => $superAdminRole->id,
            'status'   => 1,
        ]);

        Setting::create([
            'key'    => 'test_setting',
            'value'  => 'global_value',
            'status' => 1,
        ]);

        $result = Setting::setValueForUser($superAdmin->id, 'test_setting', 'override');
        $this->assertFalse($result);
    }

    public function test_r_users_relationship() {
        $role = Role::create([
            'name'   => 'Test Role',
            'code'   => 'TEST_ROLE',
            'status' => 1,
        ]);

        $user = User::create([
            'username' => 'testuser',
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
            'status'   => 1,
        ]);

        $setting = Setting::create([
            'key'    => 'test_setting',
            'value'  => 'value',
            'status' => 1,
        ]);

        DB::table('user_settings')->insert([
            'user_id'    => $user->id,
            'setting_id' => $setting->id,
            'value'      => 'user_value',
        ]);

        $setting->load('rUsers');
        $this->assertTrue($setting->rUsers->contains($user));
        $this->assertEquals('user_value', $setting->rUsers->first()->pivot->value);
    }

    public function test_get_value_for_user_returns_default_when_setting_inactive() {
        $role = Role::create([
            'name'   => 'Test Role',
            'code'   => 'TEST_ROLE',
            'status' => 1,
        ]);

        $user = User::create([
            'username' => 'testuser',
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
            'status'   => 1,
        ]);

        Setting::create([
            'key'    => 'inactive_setting',
            'value'  => 'inactive_value',
            'status' => Setting::STATUS_INACTIVE, // Inactive
        ]);

        $value = Setting::getValueForUser($user->id, 'inactive_setting', 'default_value');
        $this->assertEquals('default_value', $value);
    }

    public function test_get_value_for_user_returns_default_when_user_not_exists() {
        $nonExistentUserId = 99999;

        Setting::create([
            'key'    => 'test_setting',
            'value'  => 'global_value',
            'status' => 1,
        ]);

        $value = Setting::getValueForUser($nonExistentUserId, 'test_setting', 'default_value');
        $this->assertEquals('default_value', $value);
    }

    public function test_get_value_for_user_returns_default_when_setting_not_exists() {
        $role = Role::create([
            'name'   => 'Test Role',
            'code'   => 'TEST_ROLE',
            'status' => 1,
        ]);

        $user = User::create([
            'username' => 'testuser',
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
            'status'   => 1,
        ]);

        $value = Setting::getValueForUser($user->id, 'non_existent_setting', 'default_value');
        $this->assertEquals('default_value', $value);
    }

    public function test_set_value_for_user_returns_false_when_user_not_exists() {
        $nonExistentUserId = 99999;

        Setting::create([
            'key'    => 'test_setting',
            'value'  => 'global_value',
            'status' => 1,
        ]);

        $result = Setting::setValueForUser($nonExistentUserId, 'test_setting', 'user_value');
        $this->assertFalse($result);
    }

    public function test_set_value_for_user_returns_false_when_setting_not_exists() {
        $role = Role::create([
            'name'   => 'Test Role',
            'code'   => 'TEST_ROLE',
            'status' => 1,
        ]);

        $user = User::create([
            'username' => 'testuser',
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
            'status'   => 1,
        ]);

        $result = Setting::setValueForUser($user->id, 'non_existent_setting', 'user_value');
        $this->assertFalse($result);
    }

    public function test_set_value_for_user_works_with_inactive_setting() {
        $role = Role::create([
            'name'   => 'Test Role',
            'code'   => 'TEST_ROLE',
            'status' => 1,
        ]);

        $user = User::create([
            'username' => 'testuser',
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
            'status'   => 1,
        ]);

        $setting = Setting::create([
            'key'    => 'inactive_setting',
            'value'  => 'global_value',
            'status' => Setting::STATUS_INACTIVE, // Inactive
        ]);

        $result = Setting::setValueForUser($user->id, 'inactive_setting', 'user_value');
        $this->assertTrue($result);

        $this->assertDatabaseHas('user_settings', [
            'user_id'    => $user->id,
            'setting_id' => $setting->id,
            'value'      => 'user_value',
        ]);
    }
}
