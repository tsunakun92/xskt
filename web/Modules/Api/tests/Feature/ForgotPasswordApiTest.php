<?php

namespace Modules\Api\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

use App\Mail\SendEmail;
use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\Role;
use Modules\Admin\Models\User;
use Modules\Api\Models\ApiRegRequest;
use Modules\Api\Models\OtpManagement;

class ForgotPasswordApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
    }

    /**
     * Test forgot_password success.
     *
     * @return void
     */
    public function test_forgot_password_success(): void {
        $user = User::find(2);

        $otp = OtpManagement::create([
            'user_id'    => $user->id,
            'type'       => OtpManagement::TYPE_FORGOT_PASSWORD, // 1: register, 2: forgot_password
            'email'      => $user->email,
            'otp_code'   => '123456',
            'expires_at' => now()->addMinutes(30),
            'platform'   => 2,
            'version'    => '1.0',
        ]);

        $response = $this->postGraphQL('
            mutation {
                forgot_password(
                    email: "admin@test.com",
                    new_pass: "newPassword123",
                    otp: "123456",
                    version: "1.0",
                    platform: "android"
                ) {
                    status
                    message
                }
            }');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'forgot_password' => [
                    'status'  => 1,
                    'message' => 'Reset password successfully',
                ],
            ],
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('newPassword123', $user->password));

        $otp->refresh();
        $this->assertNotNull($otp->used_at);
    }

    /**
     * Test forgot_password invalid otp.
     *
     * @return void
     */
    public function test_forgot_password_invalid_otp(): void {
        $user = User::find(2);

        OtpManagement::create([
            'user_id'    => $user->id,
            'type'       => OtpManagement::TYPE_FORGOT_PASSWORD, // 1: register, 2: forgot_password
            'email'      => $user->email,
            'otp_code'   => '123456',
            'expires_at' => now()->addMinutes(30),
            'platform'   => 2,
            'version'    => '1.0',
        ]);

        $response = $this->postGraphQL('
            mutation {
                forgot_password(
                    email: "admin@test.com",
                    new_pass: "newPassword123",
                    otp: "000000",
                    version: "1.0",
                    platform: "android"
                ) {
                    status
                    message
                }
            }');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'forgot_password' => [
                    'status'  => 0,
                    'message' => 'Time out',
                ],
            ],
        ]);
    }

    /**
     * Test forgot_password weak password.
     *
     * @return void
     */
    public function test_forgot_password_weak_password(): void {
        $response = $this->postGraphQL('
            mutation {
                forgot_password(
                    email: "admin@test.com",
                    new_pass: "123",
                    otp: "123456",
                    version: "1.0",
                    platform: "android"
                ) {
                    status
                    message
                }
            }');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'forgot_password' => [
                    'status' => 0,
                ],
            ],
        ]);
        $this->assertStringContainsString('Invalid input data', $response->json('data.forgot_password.message'));
    }

    /**
     * Test forgot_password activate temporary user.
     *
     * @return void
     */
    public function test_forgot_password_activate_temporary_user(): void {
        // Create temporary user (from send_otp)
        $role = Role::getByCode(Role::ROLE_CUSTOMER_CODE);
        $user = User::factory()->create([
            'email'    => 'tempuser@test.com',
            'status'   => User::STATUS_REGISTER_REQUEST,
            'role_id'  => $role->id,
            'password' => '', // Password will be set in forgot_password
        ]);

        // Create ApiRegRequest
        $regRequest = ApiRegRequest::create([
            'email'    => 'tempuser@test.com',
            'password' => '',
            'status'   => ApiRegRequest::STATUS_REGISTER_REQUEST,
        ]);

        // Create OTP
        $otp = OtpManagement::create([
            'user_id'    => $user->id,
            'type'       => OtpManagement::TYPE_FORGOT_PASSWORD,
            'email'      => 'tempuser@test.com',
            'otp_code'   => '123456',
            'expires_at' => now()->addMinutes(30),
            'platform'   => 2,
            'version'    => '1.0',
        ]);

        $response = $this->postGraphQL('
            mutation {
                forgot_password(
                    email: "tempuser@test.com",
                    new_pass: "newPassword123",
                    otp: "123456",
                    version: "1.0",
                    platform: "android"
                ) {
                    status
                    message
                }
            }');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'forgot_password' => [
                    'status'  => 1,
                    'message' => 'Reset password successfully',
                ],
            ],
        ]);

        // User should be activated
        $user->refresh();
        $this->assertEquals(User::STATUS_ACTIVE, $user->status);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('newPassword123', $user->password));

        // ApiRegRequest should be activated
        $regRequest->refresh();
        $this->assertEquals(ApiRegRequest::STATUS_ACTIVE, $regRequest->status);
        $this->assertTrue(Hash::check('newPassword123', $regRequest->password));

        // OTP should be marked as used
        $otp->refresh();
        $this->assertNotNull($otp->used_at);
    }

    /**
     * Test forgot_password with user from register flow (STATUS_REGISTER_REQUEST).
     * User đã register nhưng chưa verify, có thể dùng forgot password để activate.
     *
     * @return void
     */
    public function test_forgot_password_with_register_request_user(): void {
        // Create user from register flow (chưa verify)
        $role = Role::getByCode(Role::ROLE_CUSTOMER_CODE);
        $user = User::factory()->create([
            'email'    => 'registeruser@test.com',
            'status'   => User::STATUS_REGISTER_REQUEST,
            'role_id'  => $role->id,
            'password' => Hash::make('oldPassword123'), // Password từ register
        ]);

        // Create ApiRegRequest from register flow
        $regRequest = ApiRegRequest::create([
            'email'    => 'registeruser@test.com',
            'password' => Hash::make('oldPassword123'), // Password từ register
            'status'   => ApiRegRequest::STATUS_REGISTER_REQUEST,
        ]);

        // Create OTP for forgot password
        $otp = OtpManagement::create([
            'user_id'    => $user->id,
            'type'       => OtpManagement::TYPE_FORGOT_PASSWORD,
            'email'      => 'registeruser@test.com',
            'otp_code'   => '123456',
            'expires_at' => now()->addMinutes(30),
            'platform'   => 2,
            'version'    => '1.0',
        ]);

        $response = $this->postGraphQL('
            mutation {
                forgot_password(
                    email: "registeruser@test.com",
                    new_pass: "newPassword123",
                    otp: "123456",
                    version: "1.0",
                    platform: "android"
                ) {
                    status
                    message
                }
            }');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'forgot_password' => [
                    'status'  => 1,
                    'message' => 'Reset password successfully',
                ],
            ],
        ]);

        // User should be activated
        $user->refresh();
        $this->assertEquals(User::STATUS_ACTIVE, $user->status);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('newPassword123', $user->password));

        // ApiRegRequest should be activated (from register flow)
        $regRequest->refresh();
        $this->assertEquals(ApiRegRequest::STATUS_ACTIVE, $regRequest->status);
        $this->assertTrue(Hash::check('newPassword123', $regRequest->password));

        // OTP should be marked as used
        $otp->refresh();
        $this->assertNotNull($otp->used_at);
    }

    /**
     * Test send_otp (forgot password) with user from register flow (STATUS_REGISTER_REQUEST).
     *
     * @return void
     */
    public function test_send_otp_with_register_request_user(): void {
        Mail::fake();

        // Create user from register flow (chưa verify)
        $role = Role::getByCode(Role::ROLE_CUSTOMER_CODE);
        $user = User::factory()->create([
            'email'    => 'registeruser2@test.com',
            'status'   => User::STATUS_REGISTER_REQUEST,
            'role_id'  => $role->id,
            'password' => Hash::make('oldPassword123'),
        ]);

        // Create ApiRegRequest from register flow
        ApiRegRequest::create([
            'email'    => 'registeruser2@test.com',
            'password' => Hash::make('oldPassword123'),
            'status'   => ApiRegRequest::STATUS_REGISTER_REQUEST,
        ]);

        $response = $this->postGraphQL('
            mutation {
                send_otp(
                    email: "registeruser2@test.com",
                    type: 2,
                    version: "1.0",
                    platform: "android"
                ) {
                    status
                    message
                    expires_in_minutes
                }
            }');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'send_otp' => [
                    'status'             => 1,
                    'message'            => 'We have emailed your OTP code.',
                    'expires_in_minutes' => 10,
                ],
            ],
        ]);

        // OTP should be created for existing user
        $this->assertDatabaseHas('otp_managements', [
            'user_id' => $user->id,
            'type'    => OtpManagement::TYPE_FORGOT_PASSWORD,
            'email'   => $user->email,
        ]);

        // User status should remain REGISTER_REQUEST (chưa activate)
        $user->refresh();
        $this->assertEquals(User::STATUS_REGISTER_REQUEST, $user->status);

        Mail::assertSent(SendEmail::class);
    }

    /**
     * Test forgot_password missing required params.
     *
     * @return void
     */
    public function test_forgot_password_missing_required_params(): void {
        $response = $this->postGraphQL('
            mutation {
                forgot_password(
                    email: "admin@test.com",
                    otp: "123456",
                    version: "1.0",
                    platform: "android"
                ) {
                    status
                    message
                }
            }');

        $response->assertJson([
            'errors' => [
                [
                    'message' => 'Field "forgot_password" argument "new_pass" of type "String!" is required but not provided.',
                ],
            ],
        ]);
    }

    /**
     * Test forgot_password with snake_case mutation name.
     *
     * @return void
     */
    public function test_forgot_password_legacy_alias_still_works(): void {
        $user = User::find(2);

        OtpManagement::create([
            'user_id'    => $user->id,
            'type'       => OtpManagement::TYPE_FORGOT_PASSWORD,
            'email'      => $user->email,
            'otp_code'   => '123456',
            'expires_at' => now()->addMinutes(30),
            'platform'   => 2,
            'version'    => '1.0',
        ]);

        $response = $this->postGraphQL('
            mutation {
                forgot_password(
                    email: "admin@test.com",
                    new_pass: "newPassword123",
                    otp: "123456",
                    version: "1.0",
                    platform: "android"
                ) {
                    status
                    message
                }
            }');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'forgot_password' => [
                    'status'  => 1,
                    'message' => 'Reset password successfully',
                ],
            ],
        ]);
    }
}
