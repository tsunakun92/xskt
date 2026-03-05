<?php

namespace Modules\Api\Tests\Feature;

use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;
use Modules\Api\Models\OtpManagement;

class VerifyOtpApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    /**
     * Test verify_otp success.
     *
     * @return void
     */
    public function test_verify_otp_success(): void {
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
                verify_otp(
                    email: "admin@test.com",
                    otp: "123456",
                    type: 2,
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
                'verify_otp' => [
                    'status'  => 1,
                    'message' => 'OTP is valid',
                ],
            ],
        ]);

        $otp->refresh();
        $this->assertNotNull($otp->verified_at);
    }

    /**
     * Test verify_otp invalid otp.
     *
     * @return void
     */
    public function test_verify_otp_invalid(): void {
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
                verify_otp(
                    email: "admin@test.com",
                    otp: "000000",
                    type: 2,
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
                'verify_otp' => [
                    'status'  => 0,
                    'message' => 'Invalid or expired OTP code',
                ],
            ],
        ]);
    }

    /**
     * Test verify_otp expired otp.
     *
     * @return void
     */
    public function test_verify_otp_expired(): void {
        $user = User::find(2);

        OtpManagement::create([
            'user_id'    => $user->id,
            'type'       => OtpManagement::TYPE_FORGOT_PASSWORD, // 1: register, 2: forgot_password
            'email'      => $user->email,
            'otp_code'   => '123456',
            'expires_at' => now()->subMinute(),
            'platform'   => 2,
            'version'    => '1.0',
        ]);

        $response = $this->postGraphQL('
            mutation {
                verify_otp(
                    email: "admin@test.com",
                    otp: "123456",
                    type: 2,
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
                'verify_otp' => [
                    'status'  => 0,
                    'message' => 'Invalid or expired OTP code',
                ],
            ],
        ]);
    }

    /**
     * Test verify_otp missing required params.
     *
     * @return void
     */
    public function test_verify_otp_missing_required_params(): void {
        $response = $this->postGraphQL('
            mutation {
                verify_otp(
                    email: "admin@test.com",
                    type: 2,
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
                    'message' => 'Field "verify_otp" argument "otp" of type "String!" is required but not provided.',
                ],
            ],
        ]);
    }
}
