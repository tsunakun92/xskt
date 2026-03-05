<?php

namespace Modules\Api\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;

class ChangePasswordApiTest extends BaseApiTest {
    protected function setUp(): void {
        parent::setUp();
        // Add data seeds
        $this->seed(UserSeeder::class);
    }

    /**
     * Test successful password change.
     */
    public function test_change_password_success() {
        // Arrange: Retrieve user and authenticate with Sanctum
        $user = User::find(2); // admin user
        Sanctum::actingAs($user, ['*']);

        // Act: Send a GraphQL request to change the password
        $response = $this->postGraphQL('
            mutation {
                change_password(
                    current_password: "adminadmin",
                    new_password: "newPassword123",
                    version: "1.0",
                    platform: "web"
                ) {
                    status
                    message
                }
            }');

        // Assert: Check successful response
        $response->assertJson([
            'data' => [
                'change_password' => [
                    'status'  => 1,
                    'message' => 'Password changed successfully',
                ],
            ],
        ]);

        // Verify password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('newPassword123', $user->password));
    }

    /**
     * Test password change with incorrect current password.
     */
    public function test_wrong_current_password() {
        // Arrange: Authenticate user
        $user = User::find(2);
        Sanctum::actingAs($user, ['*']);

        // Act: Attempt password change with incorrect current password
        $response = $this->postGraphQL('
            mutation {
                change_password(
                    current_password: "wrongPassword123",
                    new_password: "newPassword123",
                    version: "1.0",
                    platform: "web"
                ) {
                    status
                    message
                }
            }');

        // Assert: Check for error response
        $response->assertJson([
            'data' => [
                'change_password' => [
                    'status'  => 0,
                    'message' => 'Invalid current password',
                ],
            ],
        ]);
    }

    /**
     * Test password change without authentication.
     */
    public function test_without_authentication() {
        // Act: Send password change request without authenticating
        $response = $this->postGraphQL('
            mutation {
                change_password(
                    current_password: "oldPassword123",
                    new_password: "newPassword123",
                    version: "1.0",
                    platform: "web"
                ) {
                    status
                    message
                }
            }');

        // Assert: Check for unauthenticated error
        $response->assertJson([
            'errors' => [
                [
                    'message' => 'Unauthenticated.',
                ],
            ],
        ]);
    }

    /**
     * Test change password with missing required parameters.
     */
    public function test_missing_required_params() {
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Missing current_password
        $response = $this->postGraphQL('
            mutation {
                change_password(
                    new_password: "newPassword123",
                    version: "1.0",
                    platform: "web"
                ) {
                    status
                    message
                }
            }');

        $response->assertJson([
            'errors' => [
                [
                    'message' => 'Field "change_password" argument "current_password" of type "String!" is required but not provided.',
                ],
            ],
        ]);

        // Missing new_password
        $response = $this->postGraphQL('
            mutation {
                change_password(
                    current_password: "oldPassword123",
                    version: "1.0",
                    platform: "web"
                ) {
                    status
                    message
                }
            }');

        $response->assertJson([
            'errors' => [
                [
                    'message' => 'Field "change_password" argument "new_password" of type "String!" is required but not provided.',
                ],
            ],
        ]);

        // Missing version
        $response = $this->postGraphQL('
            mutation {
                change_password(
                    current_password: "oldPassword123",
                    new_password: "newPassword123",
                    platform: "web"
                ) {
                    status
                    message
                }
            }');

        $response->assertJson([
            'errors' => [
                [
                    'message' => 'Field "change_password" argument "version" of type "String!" is required but not provided.',
                ],
            ],
        ]);

        // Missing platform
        $response = $this->postGraphQL('
            mutation {
                change_password(
                    current_password: "oldPassword123",
                    new_password: "newPassword123",
                    version: "1.0"
                ) {
                    status
                    message
                }
            }');

        $response->assertJson([
            'errors' => [
                [
                    'message' => 'Field "change_password" argument "platform" of type "String!" is required but not provided.',
                ],
            ],
        ]);
    }
}
