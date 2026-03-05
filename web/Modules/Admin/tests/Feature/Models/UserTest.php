<?php

namespace Modules\Admin\Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use Modules\Admin\Models\Role;
use Modules\Admin\Models\User;

class UserTest extends TestCase {
    use RefreshDatabase;

    #[Test]
    public function it_logs_out_user_when_critical_fields_are_updated() {
        // Create roles
        $customerRole = Role::create([
            'name'   => 'Customer',
            'code'   => Role::ROLE_CUSTOMER_CODE,
            'status' => Role::STATUS_ACTIVE,
        ]);

        $adminRole = Role::create([
            'name'   => 'Admin',
            'code'   => Role::ROLE_ADMIN_CODE,
            'status' => Role::STATUS_ACTIVE,
        ]);

        // Create a test user
        $user = User::create([
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'username' => 'testuser',
            'password' => Hash::make('password'),
            'role_id'  => $customerRole->id,
            'status'   => User::STATUS_ACTIVE,
        ]);

        // Test password update
        $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password',
        ]);
        $this->assertTrue(Auth::check());
        $user->update(['password' => Hash::make('newpassword')]);
        $this->assertFalse(Auth::check());

        // Test username update
        $this->post('/login', [
            'username' => 'testuser',
            'password' => 'newpassword',
        ]);
        $user->update(['username' => 'newusername']);
        $this->assertFalse(Auth::check());

        // Test email update
        $this->post('/login', [
            'username' => 'newusername',
            'password' => 'newpassword',
        ]);
        $user->update(['email' => 'newemail@example.com']);
        $this->assertFalse(Auth::check());

        // Test role update
        $this->post('/login', [
            'username' => 'newusername',
            'password' => 'newpassword',
        ]);
        $user->update(['role_id' => $adminRole->id]);
        $this->assertFalse(Auth::check());

        // Test status update
        $this->post('/login', [
            'username' => 'newusername',
            'password' => 'newpassword',
        ]);
        $user->update(['status' => User::STATUS_INACTIVE]);
        $this->assertFalse(Auth::check());
    }
}
