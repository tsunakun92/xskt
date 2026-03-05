<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;
use Modules\Hr\Database\Seeders\HrCompanySeeder;
use Modules\Hr\Models\HrCompany;

class CompanyDeleteApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        // Seed the database
        $this->seed(UserSeeder::class);
        $this->seed(HrCompanySeeder::class);
    }

    public function getMutation($company_id, $version, $platform) {
        return sprintf('mutation {
            company_delete(
                company_id: %d,
                version: "%s",
                platform: "%s"
            ) {
                status
                message
            }
        }', $company_id, $version, $platform);
    }

    public function test_delete_company_success() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Create a fresh company without any related records
        $company = HrCompany::create([
            'name'      => 'Test Company to Delete',
            'code'      => 'DEL123-000001',
            'phone'     => '01-1234-5678',
            'email'     => 'delete@test.com',
            'open_date' => '2024-01-01',
            'address'   => 'Test Address',
            'director'  => 0,
            'status'    => HrCompany::STATUS_ACTIVE,
        ]);

        // Ensure no related data exists
        $company->rProfiles()->delete();
        $company->rSections()->delete();

        // Act: Make a GraphQL mutation to delete an existing company
        $response = $this->postGraphQL($this->getMutation($company->id, '1.0', 'web'));

        // Expected response for successful deletion
        $expectedResponse = [
            'data' => [
                'company_delete' => [
                    'status'  => 1,
                    'message' => 'Company deleted successfully',
                ],
            ],
        ];

        // Assert response
        $response->assertStatus(200);
        $response->assertJson($expectedResponse);

        // Verify the company is deleted from the database
        $deletedCompany = HrCompany::find($company->id);
        $this->assertNull($deletedCompany);
    }

    public function test_delete_company_with_non_existent_company_id() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation with a non-existent company_id
        $response = $this->postGraphQL($this->getMutation(9999, '1.0', 'web'));

        // Expected response data
        $response->assertStatus(200);
        $responseData = $response->json('data.company_delete');
        $this->assertEquals(0, $responseData['status']);
        $this->assertStringContainsString('Invalid input data', $responseData['message']);
    }

    public function test_delete_company_without_auth() {
        // Act: Make a GraphQL mutation without authenticating
        $response = $this->postGraphQL($this->getMutation(1, '1.0', 'web'));

        // Expected response for unauthenticated access
        $expectedResponse = [
            'errors' => [
                [
                    'message' => 'Unauthenticated.',
                ],
            ],
        ];

        // Assert response
        $response->assertStatus(200);
        $response->assertJson($expectedResponse);
    }

    public function test_delete_company_with_invalid_company_id() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation with invalid company_id (e.g., negative value)
        $response = $this->postGraphQL($this->getMutation(-1, '1.0', 'web'));

        // Expected response for invalid company_id
        $response->assertStatus(200);
        $responseData = $response->json('data.company_delete');
        $this->assertEquals(0, $responseData['status']);
        $this->assertStringContainsString('Invalid input data', $responseData['message']);
    }
}
