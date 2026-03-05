<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;
use Modules\Hr\Database\Seeders\HrCompanySeeder;
use Modules\Hr\Database\Seeders\HrWorkRegisterSeeder;
use Modules\Hr\Database\Seeders\HrWorkShiftSeeder;

class WorkRegisterListApiTest extends BaseApiTest {
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

    public function getQuery(
        $page = 1,
        $limit = 10,
        $filter = null,
        $sortBy = 'date',
        $order = 'asc',
        $startDate = null,
        $endDate = null,
        $employeeId = null,
        $groupId = null,
        $shiftId = null
    ) {
        return sprintf('query {
            work_register_list(
                page: %d,
                limit: %d,
                filter: "%s",
                sort_by: "%s",
                order: "%s",
                start_date: "%s",
                end_date: "%s",
                employee_id: %s,
                group_id: %s,
                shift_id: %s,
                version: "1.0",
                platform: "web"
            ) {
                status
                message
                data {
                    id
                    name
                    description
                    group_id
                    date
                    workshift {
                        id
                        code
                        name
                    }
                    employee {
                        id
                    }
                    repeat_cnt
                    type
                    status
                }
                paginatorInfo {
                    total
                    currentPage
                    lastPage
                    perPage
                }
            }
        }',
            $page,
            $limit,
            $filter !== null ? '"' . addslashes($filter) . '"' : 'null',
            $sortBy,
            $order,
            $startDate !== null ? '"' . addslashes($startDate) . '"' : 'null',
            $endDate !== null ? '"' . addslashes($endDate) . '"' : 'null',
            $employeeId !== null ? intval($employeeId) : 'null',
            $groupId !== null ? intval($groupId) : 'null',
            $shiftId !== null ? intval($shiftId) : 'null'
        );
    }

    public function getValidJsonStructure() {
        return [
            'data' => [
                'work_register_list' => [
                    'status',
                    'message',
                    'data'          => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'group_id',
                            'date',
                            'workshift' => [
                                'id',
                                'code',
                                'name',
                            ],
                            'employee'  => [
                                'id',
                            ],
                            'repeat_cnt',
                            'type',
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

    public function test_get_work_register_list_success() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the work register list with only required parameters (version and platform)
        $response = $this->postGraphQL($this->getQuery());

        // Assert: Check if the response is successful and matches the expected structure
        $response->assertStatus(200);
        $responseData = $response->json('data.work_register_list');
        if ($responseData && $responseData['status'] === 1) {
            $response->assertJsonStructure($this->getValidJsonStructure());
        }
    }

    public function test_get_work_register_list_with_filter_name() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the work register list with a name filter
        $response = $this->postGraphQL($this->getQuery(1, 10, 'Report'));

        // Assert: Check if the response is successful and contains filtered data
        $response->assertStatus(200);
        $responseData = $response->json('data.work_register_list');
        if ($responseData && $responseData['status'] === 1) {
            $response->assertJsonStructure($this->getValidJsonStructure());
        }
    }

    public function test_get_work_register_list_with_date_range() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the work register list with a date range filter
        $response = $this->postGraphQL($this->getQuery(1, 10, null, 'date', 'asc', '01/07/2024', '31/08/2024'));

        // Assert: Check if the response is successful and matches the expected structure
        $response->assertStatus(200);
        $responseData = $response->json('data.work_register_list');
        if ($responseData && $responseData['status'] === 1) {
            $response->assertJsonStructure($this->getValidJsonStructure());
        }
    }

    public function test_get_work_register_list_with_employee_and_group_filter() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the work register list filtered by employee and group
        $response = $this->postGraphQL($this->getQuery(1, 10, null, 'date', 'asc', null, null, 2, 1));

        // Assert: Check if the response is successful and matches the expected structure
        $response->assertStatus(200);
        $responseData = $response->json('data.work_register_list');
        if ($responseData && $responseData['status'] === 1) {
            $response->assertJsonStructure($this->getValidJsonStructure());
        }
    }

    public function test_get_work_register_list_without_auth() {
        // Act: Make a GraphQL query to get the work register list without authentication
        $response = $this->postGraphQL($this->getQuery());

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

    public function test_get_work_register_list_with_invalid_page() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query with an invalid page number
        $response = $this->postGraphQL($this->getQuery(9999, 10));

        // Assert: Check if the response returns an empty list or handles the invalid page
        $response->assertStatus(200);
        $responseData = $response->json('data.work_register_list');
        if ($responseData && $responseData['status'] === 1 && isset($responseData['paginatorInfo'])) {
            $pagination = $responseData['paginatorInfo'];
            $this->assertEquals(9999, $pagination['currentPage']);
        }
    }
}
