<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;
use Modules\Hr\Database\Seeders\HrCompanySeeder;

class CompanyViewApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(HrCompanySeeder::class);
    }

    public function getQuery($companyId = null) {
        return sprintf('query {
            company_view(company_id: %s, version: "1", platform: "web") {
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
            }
        }', $companyId);
    }

    public function test_get_company_success() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get the company by ID
        $response = $this->postGraphQL($this->getQuery(1));

        // Expected response data structure
        $expectedStructure = [
            'data' => [
                'company_view' => [
                    'status',
                    'message',
                    'data' => [
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
            ],
        ];

        // Assert: Check if the response is successful and matches the expected structure
        $response->assertStatus(200);
        $response->assertJsonStructure($expectedStructure);
    }

    public function test_get_company_without_auth() {
        // Act: Make a GraphQL query to get the company by ID without authentication
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

    public function test_get_company_not_found() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query to get a non-existing company by ID
        $response = $this->postGraphQL($this->getQuery(99999));

        // Expected response data for not found company
        $expectedResponse = [
            'data' => [
                'company_view' => [
                    'status'  => 0,
                    'message' => 'Company not found',
                    'data'    => null,
                ],
            ],
        ];

        // Assert: Check if the response returns "Company not found"
        $response->assertStatus(200);
        $response->assertJson($expectedResponse);
    }

    public function test_get_company_with_invalid_input() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL query with invalid input (e.g., non-integer ID)
        $response = $this->postGraphQL($this->getQuery('invalid'));

        // Expected response data for invalid input
        $expectedResponse = [
            'errors' => [
                [
                    'message' => 'ID cannot represent a non-string and non-integer value: invalid',
                ],
            ],
        ];

        // Assert: Check if the response returns an error for invalid input
        $response->assertStatus(200);
        $response->assertJson($expectedResponse);
    }
}
