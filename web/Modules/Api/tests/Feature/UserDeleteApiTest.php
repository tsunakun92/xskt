<?php

namespace Modules\Api\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;

class UserDeleteApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        // Seed the database
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
    }

    public function getMutation($user_id, $version, $platform) {
        return sprintf('mutation {
            user_delete(
                user_id: %d,
                version: "%s",
                platform: "%s"
            ) {
                status
                message
            }
        }', $user_id, $version, $platform);
    }

    public function test_delete_user_success() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Create a test user to delete
        $testUser = User::create([
            'username' => 'testdelete',
            'email'    => 'testdelete@test.com',
            'name'     => 'Test Delete User',
            'password' => Hash::make('password123'),
            'role_id'  => 3,
            'status'   => User::STATUS_ACTIVE,
        ]);

        // Act: Make a GraphQL mutation to delete the test user
        $response = $this->postGraphQL($this->getMutation($testUser->id, '1.0', 'web'));

        // Expected response for successful deletion
        $expectedResponse = [
            'data' => [
                'user_delete' => [
                    'status'  => 1,
                    'message' => 'User deleted successfully',
                ],
            ],
        ];

        // Assert response
        $response->assertStatus(200);
        $response->assertJson($expectedResponse);

        // Verify the user is deleted from the database
        $deletedUser = User::find($testUser->id);
        $this->assertNull($deletedUser);
    }

    public function test_delete_user_with_non_existent_user_id() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation with a non-existent user_id
        $response = $this->postGraphQL($this->getMutation(9999, '1.0', 'web'));

        // Expected response data
        $response->assertStatus(200);
        $responseData = $response->json('data.user_delete');
        $this->assertEquals(0, $responseData['status']);
        $this->assertStringContainsString('Invalid input data', $responseData['message']);
    }

    public function test_delete_user_without_auth() {
        // Act: Make a GraphQL mutation without authenticating
        $response = $this->postGraphQL($this->getMutation(3, '1.0', 'web'));

        // Expected response for unauthenticated access
        $expectedResponse = [
            'errors' => [
                [
                    'message' => 'Unauthenticated.',
                ],
            ],
        ];

        // Assert response
        $response->assertStatus(200);
        $response->assertJson($expectedResponse);
    }

    public function test_delete_user_with_invalid_user_id() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation with invalid user_id (e.g., negative value)
        $response = $this->postGraphQL($this->getMutation(-1, '1.0', 'web'));

        // Expected response for invalid user_id
        $response->assertStatus(200);
        $responseData = $response->json('data.user_delete');
        $this->assertEquals(0, $responseData['status']);
        $this->assertStringContainsString('Invalid input data', $responseData['message']);
    }
}
