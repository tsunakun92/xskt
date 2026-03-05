<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;
use Modules\Hr\Database\Seeders\HrCompanySeeder;

class CompanyListApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(HrCompanySeeder::class);
    }

    public function getQuery($page = 1, $limit = 10, $filter = null, $sortBy = 'id', $order = 'asc') {
        $filterValue = $filter !== null ? '"' . addslashes($filter) . '"' : 'null';

        return sprintf('query {
            company_list(page: %d, limit: %d, filter: %s, sort_by: "%s", order: "%s", version: "1", platform: "web") {
                status
                message
                data {
                    id
                    code
                    name
                    phone
                    email
                    open_date
                    address
                    director
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
                'company_list' => [
                    'status',
                    'message',
                    'data'          => [
                        '*' => [
                            'id',
                            'code',
                            'name',
                            'phone',
                            'email',
                            'open_date',
                            'address',
                            'director',
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

    public function test_get_company_list_success() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the company list
        $response = $this->postGraphQL($this->getQuery(1, 10));

        // Assert: Check if the response is successful and matches the expected structure
        $response->assertStatus(200);
        $response->assertJsonStructure($this->getValidJsonStructure());
    }

    public function test_get_company_list_without_auth() {
        // Act: Make a GraphQL query to get the company list without authentication
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

    public function test_get_company_list_with_filter() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the company list with a filter
        $response = $this->postGraphQL($this->getQuery(1, 10, '株式会社'));

        // Assert: Check if the response is successful and contains filtered data
        $response->assertStatus(200);
        $response->assertJsonStructure($this->getValidJsonStructure());

        // Additional checks for filtered results
        $responseData = $response->json('data.company_list.data');
        foreach ($responseData as $companyData) {
            $this->assertStringContainsString('株式会社', $companyData['name']);
        }
    }

    public function test_get_company_list_with_invalid_page() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query with an invalid page number
        $response = $this->postGraphQL($this->getQuery(9999, 10));

        // Assert: Check if the response returns an empty list or handles the invalid page
        $response->assertStatus(200);
        $this->assertEquals(9999, $response->json('data.company_list.paginatorInfo.currentPage'));
    }
}
