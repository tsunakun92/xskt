<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;
use Modules\Api\Utils\ApiLogger;
use Modules\Hr\Database\Seeders\HrCompanySeeder;
use Modules\Hr\Database\Seeders\HrProfileSeeder;

class LogoutApiTest extends BaseApiTest {
    protected function setUp(): void {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(HrProfileSeeder::class);
        $this->seed(HrCompanySeeder::class);
    }

    public function test_logout_success() {
        // Arrange: Create user and assign sample token
        $user = User::first();

        // Login with Sanctum to assign tokens to users
        Sanctum::actingAs($user, ['*']);

        // Generate token for user to check logout
        $deviceToken            = 'sample_device_token';
        $platform               = 1; // Internal use integer
        $personalAccessToken    = $user->createToken('test_token', $deviceToken, $platform);
        $token                  = $personalAccessToken->plainTextToken; // Get token in the form `1|token_value`
        [$tokenId, $tokenValue] = explode('|', $token);

        // Confirm that the token has been created in the database
        $this->assertDatabaseHas('personal_access_tokens', [
            'id'             => $tokenId,
            'tokenable_id'   => $user->id,
            'tokenable_type' => User::class,
        ]);

        // Act: Call the `logout` mutation with the generated token
        $logoutResponse = $this->postGraphQL('
            mutation {
                logout(version: "1.0", platform: "web") {
                    status
                    message
                }
            }', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        // Assert: Check response from API
        $logoutResponse->assertStatus(200);

        $logoutResponse->assertJson([
            'data' => [
                'logout' => [
                    'status'  => 1,
                    'message' => 'Logout successfully',
                ],
            ],
        ]);
    }

    public function test_logout_without_authentication() {
        // Act: Call the `logout` mutation without logging in
        $logoutResponse = $this->postGraphQL('
        mutation {
            logout(version: "1.0", platform: "web") {
                status
                message
            }
        }');

        // Assert: Check response from API
        $logoutResponse->assertJson([
            'errors' => [
                [
                    'message' => 'Unauthenticated.',
                ],
            ],
        ]);
    }

    public function test_mask_password_in_query_string() {
        $jsonQuery  = '{"password": "secret123", "username": "admin"}';
        $expected   = '{"password": "******", "username": "admin"}';
        $maskedJson = ApiLogger::maskPasswordInQueryString($jsonQuery);
        $this->assertEquals($expected, $maskedJson);

        $query    = 'password: "secret123", username: "admin"';
        $expected = 'password: "******", username: "admin"';
        $this->assertEquals($expected, ApiLogger::maskPasswordInQueryString($query));

        $query    = 'current_password: "old123", new_password: "new123"';
        $expected = 'current_password: "******", new_password: "******"';
        $this->assertEquals($expected, ApiLogger::maskPasswordInQueryString($query));

        $query    = 'password: "", username: "admin"';
        $expected = 'password: "", username: "admin"';
        $this->assertEquals($expected, ApiLogger::maskPasswordInQueryString($query));
    }
}
