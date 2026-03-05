<?php

namespace Modules\Api\Tests\Feature;

use Illuminate\Support\Facades\Mail;

use App\Mail\SendEmail;
use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Models\Role;
use Modules\Admin\Models\User;
use Modules\Api\Models\ApiRegRequest;
use Modules\Api\Models\OtpManagement;

class RegisterApiTest extends BaseApiTest {
    protected function setUp(): void {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_register_customer_success(): void {
        Mail::fake();

        $response = $this->postGraphQL('
            mutation {
                register_customer_with_email(
                    email: "newcustomer@example.com",
                    password: "password123",
                    version: "1.0",
                    platform: "android"
                ) {
                    status
                    message
                    expires_in_minutes
                }
            }
        ');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'register_customer_with_email' => [
                    'status'  => 1,
                    'message' => 'OTP has been sent to your email.',
                ],
            ],
        ]);

        // expires_in_minutes have when success
        $this->assertNotNull(
            data_get($response->json(), 'data.register_customer_with_email.expires_in_minutes')
        );

        // User created
        $user = User::where('email', 'newcustomer@example.com')->first();
        $this->assertNotNull($user);

        $role = Role::getByCode(Role::ROLE_CUSTOMER_CODE);
        $this->assertEquals($role->id, $user->role_id);

        // Log register request
        $this->assertDatabaseHas('api_reg_requests', [
            'email'  => 'newcustomer@example.com',
            'status' => ApiRegRequest::STATUS_REGISTER_REQUEST,
        ]);

        // OTP created
        $this->assertDatabaseHas('otp_managements', [
            'email' => 'newcustomer@example.com',
            'type'  => OtpManagement::TYPE_REGISTER,
        ]);

        // Email sent
        Mail::assertSent(SendEmail::class, function ($mail) {
            return $mail->hasTo('newcustomer@example.com');
        });
    }

    public function test_register_with_existing_email(): void {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->postGraphQL('
            mutation {
                register_customer_with_email(
                    email: "existing@example.com",
                    password: "password123",
                    version: "1.0",
                    platform: "android"
                ) {
                    status
                    message
                }
            }
        ');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'register_customer_with_email' => [
                    'status'  => 0,
                    'message' => 'Email already exists.',
                ],
            ],
        ]);
    }

    public function test_register_with_invalid_email(): void {
        $response = $this->postGraphQL('
            mutation {
                register_customer_with_email(
                    email: "invalid-email",
                    password: "password123",
                    version: "1.0",
                    platform: "android"
                ) {
                    status
                    message
                }
            }
        ');

        $response->assertStatus(200);
        $this->assertEquals(
            0,
            data_get($response->json(), 'data.register_customer_with_email.status')
        );
    }

    public function test_register_with_short_password(): void {
        $response = $this->postGraphQL('
            mutation {
                register_customer_with_email(
                    email: "test@example.com",
                    password: "12345",
                    version: "1.0",
                    platform: "android"
                ) {
                    status
                    message
                }
            }
        ');

        $response->assertStatus(200);
        $this->assertEquals(
            0,
            data_get($response->json(), 'data.register_customer_with_email.status')
        );
    }

    public function test_register_missing_required_fields(): void {
        $response = $this->postGraphQL('
            mutation {
                register_customer_with_email(
                    password: "password123",
                    version: "1.0",
                    platform: "android"
                ) {
                    status
                    message
                }
            }
        ');

        $response->assertJsonStructure([
            'errors' => [
                ['message'],
            ],
        ]);
    }

    public function test_register_with_invalid_platform(): void {
        $response = $this->postGraphQL('
            mutation {
                register_customer_with_email(
                    email: "test@example.com",
                    password: "password123",
                    version: "1.0",
                    platform: "invalid"
                ) {
                    status
                    message
                }
            }
        ');

        $response->assertStatus(200);
        $this->assertEquals(
            0,
            data_get($response->json(), 'data.register_customer_with_email.status')
        );
    }
}
