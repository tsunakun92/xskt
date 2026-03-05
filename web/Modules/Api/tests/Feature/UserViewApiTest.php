<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use App\Utils\DomainConst;
use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;
use Modules\Hr\Database\Seeders\HrCompanySeeder;
use Modules\Hr\Database\Seeders\HrProfileSeeder;

class UserViewApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        // Seed the database
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(HrCompanySeeder::class);
        $this->seed(HrProfileSeeder::class);
    }

    public function getQuery($id = null) {
        return sprintf('query {
                user_view(user_id: %s, version: "1.0", platform: "web") {
                    status
                    message
                    data {
                        id
                        username
                        email
                        name
                        status
                        role {
                            id
                            name
                        }
                        profile {
                            id
                            fullname
                            birthday
                            address
                            gender
                            company {
                                id
                                name
                                code
                                address
                                phone
                                email
                            }
                        }
                    }
                }
            }', $id);
    }

    public function test_get_user_success() {
        // Arrange: Retrieve the user
        $user = User::find(2); // admin user

        // Authenticate the request using Sanctum
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the user by ID
        $response = $this->postGraphQL($this->getQuery(2));

        // Assert: Check if the response is successful
        $response->assertStatus(200);
        $responseData = $response->json('data.user_view');
        $this->assertEquals(1, $responseData['status']);
        if ($responseData['status'] === 1 && $responseData['data'] !== null) {
            $response->assertJsonStructure([
                'data' => [
                    'user_view' => [
                        'status',
                        'message',
                        'data' => [
                            'id',
                            'username',
                            'email',
                            'name',
                            'status',
                            'role',
                            'profile',
                        ],
                    ],
                ],
            ]);
        }

        $responseData = $response->json('data.user_view');
        $this->assertEquals(1, $responseData['status']);
        $this->assertEquals('User retrieved successfully', $responseData['message']);
        $this->assertNotNull($responseData['data']);
    }

    public function test_wrong_authorization() {
        // Act: Make a GraphQL query to get the user by ID without authentication
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
        // Get user
        $user = User::find(2);

        // Authenticate the request using Sanctum
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the user by a non-existing ID
        $response = $this->postGraphQL($this->getQuery(99999));

        // Expected response data
        $expectedResponse = [
            'data' => [
                'user_view' => [
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
