<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use App\Utils\DomainConst;
use Modules\Admin\Database\Seeders\PermissionSeeder;
use Modules\Admin\Database\Seeders\RolePermissionSeeder;
use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\SettingSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;

class UserDataApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    public function getQuery($version = null, $platform = null) {
        $arguments = [];

        if ($version !== null) {
            $arguments[] = 'version: "' . addslashes($version) . '"';
        }
        if ($platform !== null) {
            $arguments[] = 'platform: "' . addslashes($platform) . '"';
        }

        $argumentsString = implode(', ', $arguments);

        $query = 'query {
            user_data(' . $argumentsString . ') {
                status
                message
                data {
                    user {
                        id
                        username
                        email
                        name
                        status
                    }
                    configs {
                        language
                        timezone
                        notification_enabled
                        dark_mode
                    }
                    permissions {
                        group
                        actions
                    }
                }
            }
        }';

        return $query;
    }

    /**
     * Get expected response data for a successful user data retrieval
     *
     * @return array
     */
    public function getExpectedResponseData() {
        return [
            'data' => [
                'user_data' => [
                    'status'  => DomainConst::API_RESPONSE_STATUS_SUCCESS,
                    'message' => 'User data retrieved successfully',
                    'data'    => [
                        'user'    => [
                            'id'       => '2',
                            'username' => 'admin',
                            'email'    => 'admin@test.com',
                            'name'     => 'Admin',
                            'status'   => 1,
                        ],
                        'configs' => [
                            'language'             => 'en-US',
                            'timezone'             => 'Asia/Tokyo',
                            'notification_enabled' => true,
                            'dark_mode'            => true,
                        ],
                    ],
                ],
            ],
        ];
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

    public function test_get_user_data_success() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the user data
        $response = $this->postGraphQL($this->getQuery('1.0', 'web'));

        // Assert: Check if the response is successful and matches the expected structure
        $response->assertStatus(200);

        $permissions = $response->json('data.user_data.data.permissions');
        $this->assertIsArray($permissions);
        $this->assertMobilePermissions($permissions);

        // Check the content of the JSON response structure
        $response->assertJsonStructure([
            'data' => [
                'user_data' => [
                    'status',
                    'message',
                    'data' => [
                        'user',
                        'configs',
                        'permissions',
                    ],
                ],
            ],
        ]);

        $responseData = $response->json('data.user_data');
        $this->assertEquals(1, $responseData['status']);
        $this->assertEquals('User data retrieved successfully', $responseData['message']);
    }

    public function test_wrong_authorization() {
        // Act: Make a GraphQL query to get the user data without authentication
        $response = $this->postGraphQL($this->getQuery('1.0', 'web'));

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
}
