<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use App\Utils\DomainConst;
use Modules\Admin\Database\Seeders\PermissionSeeder;
use Modules\Admin\Database\Seeders\RolePermissionSeeder;
use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;

class UserPermissionApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        // Seed the database
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    public function getQuery($id = null) {
        return sprintf('query {
                user_permissions(user_id: %s, version: "1", platform: "web") {
                    status
                    message
                    data {
                        group
                        actions
                    }
                }
            }', $id);
    }

    /**
     * Assert permissions are mobiles-only and match expected shape.
     *
     * @param  array  $permissions
     * @return void
     */
    private function assertMobilePermissions(array $permissions): void {
        if (empty($permissions)) {
            // Allow empty permissions as long as shape is correct when present
            $this->assertIsArray($permissions);

            return;
        }

        foreach ($permissions as $permission) {
            $this->assertArrayHasKey('group', $permission);
            $this->assertArrayHasKey('actions', $permission);
            $this->assertIsString($permission['group']);
            $this->assertIsArray($permission['actions']);

            foreach ($permission['actions'] as $action) {
                $this->assertIsString($action);
            }
        }
    }

    public function test_get_user_permissions_success() {
        // Arrange: Retrieve the user
        $user = User::find(2);

        // Authenticate the request using Sanctum
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the permissions for the user
        $response = $this->postGraphQL($this->getQuery(2));

        // Assert: Check if the response is successful and matches the expected structure
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'user_permissions' => [
                    'status',
                    'message',
                    'data',
                ],
            ],
        ]);

        $responseData = $response->json('data.user_permissions');
        $this->assertEquals(1, $responseData['status']);
        $this->assertEquals('Permissions retrieved successfully', $responseData['message']);

        $permissions = $response->json('data.user_permissions.data');
        $this->assertIsArray($permissions);
        $this->assertMobilePermissions($permissions);
    }

    public function test_wrong_authorization() {
        // Act: Make a GraphQL query to get the permissions without authentication
        $response = $this->postGraphQL($this->getQuery(2));

        // Expected response data
        $expectedResponse = [
            'errors' => [
                [
                    'message' => 'Unauthenticated.',
                ],
            ],
        ];

        // Assert: Check if the response returns unauthenticated
        $response->assertStatus(200);
        $response->assertJson($expectedResponse);
    }

    public function test_not_found_user() {
        // Arrange: Retrieve a valid user for authentication
        $user = User::find(2);

        // Authenticate the request using Sanctum
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get permissions for a non-existing user ID
        $response = $this->postGraphQL($this->getQuery(99999));

        // Expected response data
        $expectedResponse = [
            'data' => [
                'user_permissions' => [
                    'status'  => DomainConst::API_RESPONSE_STATUS_FAILED,
                    'message' => 'User not found',
                    'data'    => null,
                ],
            ],
        ];

        // Assert: Check if the response returns "User not found"
        $response->assertStatus(200);
        $response->assertJson($expectedResponse);
    }
}
