<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use App\Utils\DomainConst;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;

class UserListApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        // Seed the database
        $this->seed(UserSeeder::class);
    }

    public function getQuery($page = 1, $limit = 10, $filter = null, $sortBy = 'created_at', $order = 'desc') {
        $filterValue = $filter !== null ? '"' . addslashes($filter) . '"' : 'null';

        return sprintf('query {
                user_list(page: %d, limit: %d, filter: %s, sort_by: "%s", order: "%s", version: "1.0", platform: "web") {
                    status
                    message
                    data {
                        id
                        username
                        email
                        name
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
                'user_list' => [
                    'status',
                    'message',
                    'data'          => [
                        '*' => [
                            'id',
                            'username',
                            'email',
                            'name',
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

    public function test_get_user_list_success() {
        // Arrange: Retrieve the user and authenticate
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the user list
        $response = $this->postGraphQL($this->getQuery(1, 10));

        // Assert: Check if the response is successful and matches the expected structure
        $response->assertStatus(200);
        $responseData = $response->json('data.user_list');
        if ($responseData && $responseData['status'] === 1) {
            $response->assertJsonStructure($this->getValidJsonStructure());
        }
    }

    public function test_get_user_list_without_auth() {
        // Act: Make a GraphQL query to get the user list without authentication
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

    public function test_get_user_list_with_invalid_page() {
        // Arrange: Retrieve the user and authenticate
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the user list with an invalid page number
        $total       = User::count();
        $perPage     = 10;
        $lastPage    = ceil($total / $perPage);
        $invalidPage = 99999;

        $response = $this->postGraphQL($this->getQuery($invalidPage, $perPage));

        // Expected response data (empty or not found data)
        $expectedResponse = [
            'data' => [
                'user_list' => [
                    'status'        => DomainConst::API_RESPONSE_STATUS_SUCCESS,
                    'message'       => 'Users retrieved successfully',
                    'data'          => [],
                    'paginatorInfo' => [
                        'total'       => $total,
                        'currentPage' => $invalidPage,
                        'lastPage'    => $lastPage,
                        'perPage'     => $perPage,
                    ],
                ],
            ],
        ];

        // Assert: Check if the response returns an empty list
        $response->assertStatus(200);
        $response->assertJson($expectedResponse);
    }

    public function test_get_user_list_with_filter() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the user list with a filter
        $response = $this->postGraphQL($this->getQuery(1, 10, 'admin'));

        // Assert: Check if the response is successful and contains filtered data
        $response->assertStatus(200);
        $responseData = $response->json('data.user_list');
        if ($responseData && $responseData['status'] === 1) {
            $response->assertJsonStructure($this->getValidJsonStructure());
        }

        // Additional checks for filtered results
        $responseData = $response->json('data.user_list.data');
        foreach ($responseData as $userData) {
            $this->assertStringContainsString('admin', strtolower($userData['username']));
        }
    }

    public function test_get_user_list_with_different_page() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the user list with page 2
        $response = $this->postGraphQL($this->getQuery(2, 10));

        // Assert: Check if the response is successful and matches the expected structure
        $response->assertStatus(200);
        $response->assertJsonStructure($this->getValidJsonStructure());

        // Assert that the current page is 2
        $this->assertEquals(2, $response->json('data.user_list.paginatorInfo.currentPage'));
    }

    public function test_get_user_list_with_custom_limit() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the user list with a custom limit of 5
        $response = $this->postGraphQL($this->getQuery(1, 5));

        // Assert: Check if the response is successful and matches the expected structure
        $response->assertStatus(200);
        $response->assertJsonStructure($this->getValidJsonStructure());

        // Assert that the perPage value is 5
        $this->assertEquals(5, $response->json('data.user_list.paginatorInfo.perPage'));
    }

    public function test_get_user_list_with_sorting() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the user list sorted by name in ascending order
        $responseAsc = $this->postGraphQL($this->getQuery(1, 10, null, 'name', 'asc'));

        // Assert: Check if the response is successful and matches the expected structure
        $responseAsc->assertStatus(200);
        $responseAsc->assertJsonStructure($this->getValidJsonStructure());

        // Extract the data and check if it is sorted in ascending order
        $responseDataAsc = $responseAsc->json('data.user_list.data');
        $namesAsc        = array_column($responseDataAsc, 'name');
        $sortedNamesAsc  = $namesAsc;
        sort($sortedNamesAsc);

        $this->assertEquals($sortedNamesAsc, $namesAsc);

        // Act: Make a GraphQL query to get the user list sorted by name in descending order
        $responseDesc = $this->postGraphQL($this->getQuery(1, 10, null, 'name', 'desc'));

        // Assert: Check if the response is successful and matches the expected structure
        $responseDesc->assertStatus(200);
        $responseDesc->assertJsonStructure($this->getValidJsonStructure());

        // Extract the data and check if it is sorted in descending order
        $responseDataDesc = $responseDesc->json('data.user_list.data');
        $namesDesc        = array_column($responseDataDesc, 'name');
        $sortedNamesDesc  = $namesDesc;
        rsort($sortedNamesDesc);

        $this->assertEquals($sortedNamesDesc, $namesDesc);
    }

    public function test_get_user_list_with_invalid_input_data() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query with invalid page number
        $response = $this->postGraphQL($this->getQuery(-1));

        // Expected response for invalid input
        $expectedResponse = [
            'data' => [
                'user_list' => [
                    'status'        => DomainConst::API_RESPONSE_STATUS_FAILED,
                    'data'          => null,
                    'paginatorInfo' => null,
                ],
            ],
        ];
        $response->assertStatus(200);
        $response->assertJson($expectedResponse);
        $this->assertStringContainsString('Invalid input data', $response->json('data.user_list.message'));

        // Act: Make a GraphQL query with invalid sort order
        $response = $this->postGraphQL($this->getQuery(1, 10, null, 'name', 'invalid'));

        // Expected response for invalid input
        $expectedResponse = [
            'data' => [
                'user_list' => [
                    'status'        => DomainConst::API_RESPONSE_STATUS_FAILED,
                    'data'          => null,
                    'paginatorInfo' => null,
                ],
            ],
        ];

        // Assert: Check if the response returns an error for invalid input data
        $response->assertStatus(200);
        $response->assertJson($expectedResponse);
        $this->assertStringContainsString('Invalid input data', $response->json('data.user_list.message'));

        // Act: Make a GraphQL query with invalid limit
        $response = $this->postGraphQL($this->getQuery(1, -1));

        // Expected response for invalid input
        $expectedResponse = [
            'data' => [
                'user_list' => [
                    'status'        => DomainConst::API_RESPONSE_STATUS_FAILED,
                    'data'          => null,
                    'paginatorInfo' => null,
                ],
            ],
        ];

        // Assert: Check if the response returns an error for invalid input data
        $response->assertStatus(200);
        $response->assertJson($expectedResponse);
        $this->assertStringContainsString('Invalid input data', $response->json('data.user_list.message'));
    }
}
