<?php

namespace Modules\Api\Tests\Feature;

use Illuminate\Support\Facades\RateLimiter;

use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Models\User;
use Modules\Api\Models\ApiRegRequest;
use Modules\Api\Models\OtpManagement;

class RegisterVerifyApiTest extends BaseApiTest {
    protected function setUp(): void {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_verify_register_otp_success(): void {
        // Create user and registration request
        $user = User::factory()->create([
            'email'  => 'verify@test.com',
            'status' => User::STATUS_REGISTER_REQUEST,
        ]);

        ApiRegRequest::create([
            'email'    => 'verify@test.com',
            'password' => 'hashed_password',
            'status'   => ApiRegRequest::STATUS_REGISTER_REQUEST,
        ]);

        // Create valid OTP
        $otp = OtpManagement::create([
            'user_id'    => $user->id,
            'type'       => OtpManagement::TYPE_REGISTER,
            'email'      => 'verify@test.com',
            'otp_code'   => '123456',
            'expires_at' => now()->addMinutes(2),
            'platform'   => 1, // android
            'version'    => '1.0',
        ]);

        $response = $this->postGraphQL('
            mutation {
                verify_register_otp(
                    email: "verify@test.com"
                    otp: "123456"
                    device_token: "test_device_token_123"
                    version: "1.0"
                    platform: "android"
                ) {
                    status
                    message
                    token
                    data {
                        user {
                            id
                            email
                        }
                    }
                }
            }
        ');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'verify_register_otp' => [
                    'status'  => 1,
                    'message' => 'OTP verified successfully.',
                    'data'    => [
                        'user' => [
                            'email' => 'verify@test.com',
                        ],
                    ],
                ],
            ],
        ]);

        // Verify token is returned
        $token = $response->json('data.verify_register_otp.token');
        $this->assertNotEmpty($token, 'The verification token should not be empty');

        // User should be activated and email verified
        $user->refresh();
        $this->assertEquals(User::STATUS_ACTIVE, $user->status);
        $this->assertNotNull($user->email_verified_at);

        // ApiRegRequest should be activated
        $regRequest = ApiRegRequest::where('email', 'verify@test.com')->first();
        $this->assertEquals(ApiRegRequest::STATUS_ACTIVE, $regRequest->status);

        // OTP should be marked as used and verified
        $otp->refresh();
        $this->assertNotNull($otp->used_at);
        $this->assertNotNull($otp->verified_at);

        // Rate limit should be cleared
        $key = 'verify_register_otp:verify@test.com';
        $this->assertFalse(RateLimiter::tooManyAttempts($key, 10));
    }

    public function test_verify_register_otp_invalid_email(): void {
        $response = $this->postGraphQL('
            mutation {
                verify_register_otp(
                    email: "invalid-email"
                    otp: "123456"
                    device_token: "test_device_token_123"
                    version: "1.0"
                    platform: "android"
                ) {
                    status
                    message
                    token
                }
            }
        ');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'verify_register_otp' => [
                    'status' => 0,
                ],
            ],
        ]);

        $this->assertStringContainsString(
            'Invalid input data',
            $response->json('data.verify_register_otp.message')
        );
    }

    public function test_verify_register_otp_invalid_platform(): void {
        $response = $this->postGraphQL('
            mutation {
                verify_register_otp(
                    email: "test@example.com"
                    otp: "123456"
                    device_token: "test_device_token_123"
                    version: "1.0"
                    platform: "invalid"
                ) {
                    status
                    message
                    token
                }
            }
        ');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'verify_register_otp' => [
                    'status' => 0,
                ],
            ],
        ]);

        $this->assertStringContainsString(
            'Invalid input data',
            $response->json('data.verify_register_otp.message')
        );
    }

    public function test_verify_register_otp_user_not_found(): void {
        // Create user with wrong status (ACTIVE instead of REGISTER_REQUEST)
        User::factory()->create([
            'email'  => 'nonexistent@example.com',
            'status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->postGraphQL('
            mutation {
                verify_register_otp(
                    email: "nonexistent@example.com"
                    otp: "123456"
                    device_token: "test_device_token_123"
                    version: "1.0"
                    platform: "android"
                ) {
                    status
                    message
                    token
                }
            }
        ');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'verify_register_otp' => [
                    'status'  => 0,
                    'message' => 'User not found.',
                ],
            ],
        ]);
    }

    public function test_verify_register_otp_wrong_user_status(): void {
        // Create user with wrong status (INACTIVE instead of REGISTER_REQUEST)
        User::factory()->create([
            'email'  => 'wrongstatus@test.com',
            'status' => User::STATUS_INACTIVE,
        ]);

        $response = $this->postGraphQL('
            mutation {
                verify_register_otp(
                    email: "wrongstatus@test.com"
                    otp: "123456"
                    device_token: "test_device_token_123"
                    version: "1.0"
                    platform: "android"
                ) {
                    status
                    message
                    token
                }
            }
        ');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'verify_register_otp' => [
                    'status'  => 0,
                    'message' => 'User not found.',
                ],
            ],
        ]);
    }

    public function test_verify_register_otp_no_registration_request(): void {
        // Create user in register request status but no ApiRegRequest record
        User::factory()->create([
            'email'  => 'norequest@test.com',
            'status' => User::STATUS_REGISTER_REQUEST,
        ]);

        $response = $this->postGraphQL('
            mutation {
                verify_register_otp(
                    email: "norequest@test.com"
                    otp: "123456"
                    device_token: "test_device_token_123"
                    version: "1.0"
                    platform: "android"
                ) {
                    status
                    message
                    token
                }
            }
        ');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'verify_register_otp' => [
                    'status'  => 0,
                    'message' => 'Registration request not found.',
                ],
            ],
        ]);
    }

    public function test_verify_register_otp_invalid_otp(): void {
        // Create user and registration request
        $user = User::factory()->create([
            'email'  => 'invalidotp@test.com',
            'status' => User::STATUS_REGISTER_REQUEST,
        ]);

        ApiRegRequest::create([
            'email'    => 'invalidotp@test.com',
            'password' => 'hashed_password',
            'status'   => ApiRegRequest::STATUS_REGISTER_REQUEST,
        ]);

        // Create OTP with different code
        OtpManagement::create([
            'user_id'    => $user->id,
            'type'       => OtpManagement::TYPE_REGISTER,
            'email'      => 'invalidotp@test.com',
            'otp_code'   => '654321',
            'expires_at' => now()->addMinutes(2),
            'platform'   => 1,
            'version'    => '1.0',
        ]);

        $response = $this->postGraphQL('
            mutation {
                verify_register_otp(
                    email: "invalidotp@test.com"
                    otp: "123456"
                    device_token: "test_device_token_123"
                    version: "1.0"
                    platform: "android"
                ) {
                    status
                    message
                    token
                }
            }
        ');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'verify_register_otp' => [
                    'status'  => 0,
                    'message' => 'Invalid or expired OTP code',
                ],
            ],
        ]);
    }

    public function test_verify_register_otp_expired_otp(): void {
        // Create user and registration request
        $user = User::factory()->create([
            'email'  => 'expiredotp@test.com',
            'status' => User::STATUS_REGISTER_REQUEST,
        ]);

        ApiRegRequest::create([
            'email'    => 'expiredotp@test.com',
            'password' => 'hashed_password',
            'status'   => ApiRegRequest::STATUS_REGISTER_REQUEST,
        ]);

        // Create expired OTP
        OtpManagement::create([
            'user_id'    => $user->id,
            'type'       => OtpManagement::TYPE_REGISTER,
            'email'      => 'expiredotp@test.com',
            'otp_code'   => '123456',
            'expires_at' => now()->subMinutes(1), // expired
            'platform'   => 1,
            'version'    => '1.0',
        ]);

        $response = $this->postGraphQL('
            mutation {
                verify_register_otp(
                    email: "expiredotp@test.com"
                    otp: "123456"
                    device_token: "test_device_token_123"
                    version: "1.0"
                    platform: "android"
                ) {
                    status
                    message
                    token
                }
            }
        ');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'verify_register_otp' => [
                    'status'  => 0,
                    'message' => 'Invalid or expired OTP code',
                ],
            ],
        ]);
    }

    public function test_verify_register_otp_used_otp(): void {
        // Create user and registration request
        $user = User::factory()->create([
            'email'  => 'usedotp@test.com',
            'status' => User::STATUS_REGISTER_REQUEST,
        ]);

        ApiRegRequest::create([
            'email'    => 'usedotp@test.com',
            'password' => 'hashed_password',
            'status'   => ApiRegRequest::STATUS_REGISTER_REQUEST,
        ]);

        // Create used OTP
        OtpManagement::create([
            'user_id'    => $user->id,
            'type'       => OtpManagement::TYPE_REGISTER,
            'email'      => 'usedotp@test.com',
            'otp_code'   => '123456',
            'expires_at' => now()->addMinutes(2),
            'used_at'    => now(), // already used
            'platform'   => 1,
            'version'    => '1.0',
        ]);

        $response = $this->postGraphQL('
            mutation {
                verify_register_otp(
                    email: "usedotp@test.com"
                    otp: "123456"
                    device_token: "test_device_token_123"
                    version: "1.0"
                    platform: "android"
                ) {
                    status
                    message
                    token
                }
            }
        ');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'verify_register_otp' => [
                    'status'  => 0,
                    'message' => 'Invalid or expired OTP code',
                ],
            ],
        ]);
    }

    public function test_verify_register_otp_rate_limiting(): void {
        $mutation = '
        mutation {
            verify_register_otp(
                email: "ratelimit@test.com"
                otp: "123456"
                device_token: "test_device_token_123"
                version: "1.0"
                platform: "android"
            ) {
                status
                message
                token
            }
        }
    ';

        // Hit 10 times (allowed)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postGraphQL($mutation);
            $response->assertStatus(200);
        }

        // 11th request should be blocked
        $response = $this->postGraphQL($mutation);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'verify_register_otp' => [
                    'status' => 0,
                ],
            ],
        ]);

        $this->assertStringContainsString(
            'Too many attempts',
            $response->json('data.verify_register_otp.message')
        );
    }

    public function test_verify_register_otp_missing_required_fields(): void {
        $response = $this->postGraphQL('
            mutation {
                verify_register_otp(
                    otp: "123456"
                    device_token: "test_device_token_123"
                    version: "1.0"
                    platform: "android"
                ) {
                    status
                    message
                    token
                }
            }
        ');

        $response->assertJsonStructure([
            'errors' => [
                ['message'],
            ],
        ]);
    }
}
