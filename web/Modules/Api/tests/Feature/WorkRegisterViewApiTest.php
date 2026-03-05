<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;
use Modules\Hr\Database\Seeders\HrCompanySeeder;
use Modules\Hr\Database\Seeders\HrWorkRegisterSeeder;
use Modules\Hr\Database\Seeders\HrWorkShiftSeeder;

class WorkRegisterViewApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(HrCompanySeeder::class);
        $this->seed(HrWorkShiftSeeder::class);
        $this->seed(HrWorkRegisterSeeder::class);
    }

    public function getQuery($workRegisterId = null) {
        return sprintf('query {
            work_register_view(work_register_id: %s, version: "1", platform: "web") {
                status
                message
                data {
                    id
                    name
                    description
                    date
                    repeat_cnt
                    type
                    status
                    workshift {
                        id
                        code
                        name
                    }
                    employee {
                        id
                        name
                    }
                }
            }
        }', $workRegisterId);
    }

    public function test_get_work_register_success() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the work register by ID
        $response = $this->postGraphQL($this->getQuery(1));

        // Expected response data structure
        $expectedStructure = [
            'data' => [
                'work_register_view' => [
                    'status',
                    'message',
                    'data' => [
                        'id',
                        'name',
                        'description',
                        'date',
                        'repeat_cnt',
                        'type',
                        'status',
                        'workshift' => [
                            'id',
                            'code',
                            'name',
                        ],
                        'employee'  => [
                            'id',
                            'name',
                        ],
                    ],
                ],
            ],
        ];

        // Assert: Check if the response is successful and matches the expected structure
        $response->assertStatus(200);
        $responseData = $response->json('data.work_register_view');
        $this->assertEquals(1, $responseData['status']);
        if ($responseData['status'] === 1 && $responseData['data'] !== null) {
            $response->assertJsonStructure($expectedStructure);
        }
    }

    public function test_get_work_register_without_auth() {
        // Act: Make a GraphQL query to get the work register by ID without authentication
        $response = $this->postGraphQL($this->getQuery(1));

        // Expected response data for unauthenticated access
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

    public function test_get_work_register_not_found() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get a non-existing work register by ID
        $response = $this->postGraphQL($this->getQuery(99999));

        // Expected response data for not found work register
        $expectedResponse = [
            'data' => [
                'work_register_view' => [
                    'status'  => 0,
                    'message' => 'Work register not found',
                    'data'    => null,
                ],
            ],
        ];

        // Assert: Check if the response returns "Work register not found"
        $response->assertStatus(200);
        $response->assertJson($expectedResponse);
    }

    public function test_get_work_register_with_invalid_input() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query with invalid input (e.g., non-integer ID)
        $response = $this->postGraphQL($this->getQuery('invalid'));

        // Expected response data for invalid input
        $expectedResponse = [
            'errors' => [
                [
                    'message' => 'Int cannot represent non-integer value: invalid',
                ],
            ],
        ];

        // Assert: Check if the response returns an error for invalid input
        $response->assertStatus(200);
        $response->assertJson($expectedResponse);
    }
}
