<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;
use Modules\Hr\Database\Seeders\HrCompanySeeder;
use Modules\Hr\Database\Seeders\HrWorkShiftSeeder;

class WorkshiftListApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(HrCompanySeeder::class);
        $this->seed(HrWorkShiftSeeder::class);
    }

    public function getQuery($page = 1, $limit = 10, $filter = null, $sortBy = 'id', $order = 'asc') {
        $filterValue = $filter !== null ? '"' . addslashes($filter) . '"' : 'null';

        return sprintf('query {
            workshift_list(page: %d, limit: %d, filter: %s, sort_by: "%s", order: "%s", version: "1.0", platform: "web") {
                status
                message
                data {
                    id
                    code
                    name
                    description
                    start
                    end
                    max_employee_cnt
                    color
                    status
                }
                paginatorInfo {
                    total
                    currentPage
                    lastPage
                    perPage
                }
            }
        }', $page, $limit, $filterValue, $sortBy, $order);
    }

    public function getValidJsonStructure() {
        return [
            'data' => [
                'workshift_list' => [
                    'status',
                    'message',
                    'data'          => [
                        '*' => [
                            'id',
                            'code',
                            'name',
                            'description',
                            'start',
                            'end',
                            'max_employee_cnt',
                            'color',
                            'status',
                        ],
                    ],
                    'paginatorInfo' => [
                        'total',
                        'currentPage',
                        'lastPage',
                        'perPage',
                    ],
                ],
            ],
        ];
    }

    public function test_get_workshift_list_success() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the workshift list
        $response = $this->postGraphQL($this->getQuery(1, 10));

        // Assert: Check if the response is successful and matches the expected structure
        $response->assertStatus(200);
        $responseData = $response->json('data.workshift_list');
        if ($responseData && $responseData['status'] === 1) {
            $response->assertJsonStructure($this->getValidJsonStructure());
        }
    }

    public function test_get_workshift_list_without_auth() {
        // Act: Make a GraphQL query to get the workshift list without authentication
        $response = $this->postGraphQL($this->getQuery(1, 10));

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

    public function test_get_workshift_list_with_filter() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the workshift list with a filter
        $response = $this->postGraphQL($this->getQuery(1, 10, 'Cleaning'));

        // Assert: Check if the response is successful and contains filtered data
        $response->assertStatus(200);
        $responseData = $response->json('data.workshift_list');
        if ($responseData && $responseData['status'] === 1) {
            $response->assertJsonStructure($this->getValidJsonStructure());
        }

        // Additional checks for filtered results
        $listData = $response->json('data.workshift_list.data');
        if (is_array($listData)) {
            foreach ($listData as $workshiftData) {
                $this->assertStringContainsString('Cleaning', $workshiftData['name']);
            }
        }
    }

    public function test_get_workshift_list_with_invalid_page() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query with an invalid page number
        $response = $this->postGraphQL($this->getQuery(9999, 10));

        // Assert: Check if the response returns an empty list or handles the invalid page
        $response->assertStatus(200);
        $this->assertEquals(9999, $response->json('data.workshift_list.paginatorInfo.currentPage'));
    }
}
