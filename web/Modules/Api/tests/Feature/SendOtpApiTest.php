<?php

namespace Modules\Api\Tests\Feature;

use Illuminate\Support\Facades\Mail;

use App\Mail\SendEmail;
use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\Role;
use Modules\Admin\Models\User;
use Modules\Api\Models\ApiRegRequest;
use Modules\Api\Models\OtpManagement;

class SendOtpApiTest extends BaseApiTest {
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
     * Test send_otp success for forgot password flow (type = TYPE_FORGOT_PASSWORD).
     *
     * @return void
     */
    public function test_send_otp_success(): void {
        Mail::fake();

        $user = User::find(2);

        $response = $this->postGraphQL('
            mutation {
                send_otp(
                    email: "admin@test.com",
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

        $this->assertDatabaseHas('otp_managements', [
            'user_id' => $user->id,
            'type'    => OtpManagement::TYPE_FORGOT_PASSWORD,
            'email'   => $user->email,
        ]);

        Mail::assertSent(SendEmail::class);

        $otp = OtpManagement::query()->where('user_id', $user->id)->latest()->first();
        $this->assertNotNull($otp);
        $this->assertNotEmpty($otp->otp_code);
        $this->assertEquals(6, strlen((string) $otp->otp_code));
    }

    /**
     * Test send_otp with non-existent email (creates temporary user) for forgot password flow.
     *
     * @return void
     */
    public function test_send_otp_creates_temporary_user(): void {
        Mail::fake();

        $response = $this->postGraphQL('
            mutation {
                send_otp(
                    email: "newuser@nothave.com",
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

        // Temporary user should be created
        $user = User::where('email', 'newuser@nothave.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals(User::STATUS_REGISTER_REQUEST, $user->status);

        $role = Role::getByCode(Role::ROLE_CUSTOMER_CODE);
        $this->assertEquals($role->id, $user->role_id);

        // ApiRegRequest should be created
        $this->assertDatabaseHas('api_reg_requests', [
            'email'  => 'newuser@nothave.com',
            'status' => ApiRegRequest::STATUS_REGISTER_REQUEST,
        ]);

        // OTP should be created
        $this->assertDatabaseHas('otp_managements', [
            'email' => 'newuser@nothave.com',
            'type'  => OtpManagement::TYPE_FORGOT_PASSWORD,
        ]);

        // Email should be sent
        Mail::assertSent(SendEmail::class, function ($mail) {
            return $mail->hasTo('newuser@nothave.com');
        });
    }

    /**
     * Test send_otp with existing REGISTER_REQUEST user for forgot password flow.
     *
     * @return void
     */
    public function test_send_otp_with_temporary_user(): void {
        Mail::fake();

        // Create temporary user (from previous forgot password attempt)
        $user = User::factory()->create([
            'email'  => 'tempuser@test.com',
            'status' => User::STATUS_REGISTER_REQUEST,
        ]);

        $response = $this->postGraphQL('
            mutation {
                send_otp(
                    email: "tempuser@test.com",
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

        // OTP should be created
        $this->assertDatabaseHas('otp_managements', [
            'user_id' => $user->id,
            'type'    => OtpManagement::TYPE_FORGOT_PASSWORD,
            'email'   => $user->email,
        ]);

        Mail::assertSent(SendEmail::class);
    }

    /**
     * Test send_otp missing required params.
     *
     * @return void
     */
    public function test_send_otp_missing_required_params(): void {
        $response = $this->postGraphQL('
            mutation {
                send_otp(
                    version: "1.0",
                    platform: "android"
                ) {
                    status
                    message
                    expires_in_minutes
                }
            }');

        $response->assertJson([
            'errors' => [
                [
                    'message' => 'Field "send_otp" argument "email" of type "String!" is required but not provided.',
                ],
            ],
        ]);
    }

    /**
     * Test send_otp missing required param type.
     *
     * @return void
     */
    public function test_send_otp_missing_type_returns_graphql_error(): void {
        $response = $this->postGraphQL('
            mutation {
                send_otp(
                    email: "admin@test.com",
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
            'errors' => [
                [
                    'message' => 'Field "send_otp" argument "type" of type "Int!" is required but not provided.',
                ],
            ],
        ]);
    }

    /**
     * Test send_otp with invalid type returns validation error response.
     *
     * @return void
     */
    public function test_send_otp_with_invalid_type_returns_validation_error(): void {
        $response = $this->postGraphQL('
            mutation {
                send_otp(
                    email: "admin@test.com",
                    type: 999,
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
                    'status' => 0,
                ],
            ],
        ]);

        $this->assertStringContainsString('Invalid input data', (string) $response->json('data.send_otp.message'));
    }
}
