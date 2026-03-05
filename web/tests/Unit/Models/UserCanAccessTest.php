<?php

namespace Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

use Modules\Admin\Models\Permission;
use Modules\Admin\Models\Role;
use Modules\Admin\Models\User;

class UserCanAccessTest extends TestCase {
    use RefreshDatabase;

    protected function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_access_returns_false_when_no_role() {
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

        // Set role to null after creation to simulate no role
        $user->setRelation('rRole', null);

        $this->assertFalse($user->canAccess('users.index'));
    }

    public function test_can_access_falls_back_to_role_when_no_user_permissions() {
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

        $mockRole = Mockery::mock($role);
        $mockRole->shouldReceive('canAccess')
            ->with('users.index')
            ->once()
            ->andReturn(true);

        $user->setRelation('rRole', $mockRole);

        $this->assertTrue($user->canAccess('users.index'));
    }

    public function test_can_access_requires_both_user_and_role_permissions() {
        $role = Role::create([
            'name'   => 'Test Role',
            'code'   => 'TEST_ROLE',
            'status' => 1,
        ]);

        $permission = Permission::create([
            'name'   => 'List Users',
            'key'    => 'users.index',
            'group'  => 'users',
            'module' => 'admin',
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

        // Attach user permission
        DB::table('user_permissions')->insert([
            'user_id'       => $user->id,
            'permission_id' => $permission->id,
        ]);

        $mockRole = Mockery::mock($role);
        $mockRole->shouldReceive('canAccess')
            ->with('users.index')
            ->once()
            ->andReturn(true);

        $user->setRelation('rRole', $mockRole);
        $user->load('rPermissions');

        $this->assertTrue($user->canAccess('users.index'));
    }

    public function test_can_access_denies_when_user_has_permission_but_role_does_not() {
        $role = Role::create([
            'name'   => 'Test Role',
            'code'   => 'TEST_ROLE',
            'status' => 1,
        ]);

        $permission = Permission::create([
            'name'   => 'List Users',
            'key'    => 'users.index',
            'group'  => 'users',
            'module' => 'admin',
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

        // Attach user permission
        DB::table('user_permissions')->insert([
            'user_id'       => $user->id,
            'permission_id' => $permission->id,
        ]);

        $mockRole = Mockery::mock($role);
        $mockRole->shouldReceive('canAccess')
            ->with('users.index')
            ->once()
            ->andReturn(false);

        $user->setRelation('rRole', $mockRole);
        $user->load('rPermissions');

        $this->assertFalse($user->canAccess('users.index'));
    }

    public function test_can_access_falls_back_to_role_when_user_has_no_custom_permissions() {
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

        $mockRole = Mockery::mock($role);
        $mockRole->shouldReceive('canAccess')
            ->with('users.index')
            ->once()
            ->andReturn(true);

        $user->setRelation('rRole', $mockRole);
        $user->load('rPermissions');

        // When user has no custom permissions, should fallback to role
        $this->assertTrue($user->canAccess('users.index'));
    }
}
