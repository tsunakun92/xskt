<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;
use Modules\Hr\Database\Seeders\HrCompanySeeder;
use Modules\Hr\Database\Seeders\HrWorkShiftSeeder;
use Modules\Hr\Models\HrWorkShift;

class WorkshiftDeleteApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        // Seed the database with necessary data
        $this->seed(UserSeeder::class);
        $this->seed(HrWorkShiftSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(HrCompanySeeder::class);
    }

    public function getMutation($workshift_id, $version, $platform) {
        return sprintf('mutation {
            workshift_delete(
                workshift_id: %d,
                version: "%s",
                platform: "%s"
            ) {
                status
                message
            }
        }', $workshift_id, $version, $platform);
    }

    public function test_delete_workshift_success() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Arrange: Get an existing workshift
        $workshift = HrWorkShift::first();

        // Act: Make a GraphQL mutation to delete an existing workshift
        $response = $this->postGraphQL($this->getMutation($workshift->id, '1.0', 'web'));

        // Expected response for successful deletion
        $expectedResponse = [
            'data' => [
                'workshift_delete' => [
                    'status'  => 1,
                    'message' => 'Workshift deleted successfully',
                ],
            ],
        ];

        // Assert response
        $response->assertStatus(200);
        $response->assertJson($expectedResponse);

        // Verify the workshift is deleted from the database
        $deletedWorkshift = HrWorkShift::find($workshift->id);
        $this->assertNull($deletedWorkshift);
    }

    public function test_delete_workshift_with_non_existent_workshift_id() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation with a non-existent workshift_id
        $response = $this->postGraphQL($this->getMutation(9999, '1.0', 'web'));

        // Expected response data
        $response->assertStatus(200);
        $responseData = $response->json('data.workshift_delete');
        $this->assertEquals(0, $responseData['status']);
        $this->assertStringContainsString('Invalid input data', $responseData['message']);
    }

    public function test_delete_workshift_without_auth() {
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

    public function test_delete_workshift_with_invalid_workshift_id() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation with an invalid workshift_id (e.g., negative value)
        $response = $this->postGraphQL($this->getMutation(-1, '1.0', 'web'));

        // Expected response for invalid workshift_id
        $response->assertStatus(200);
        $responseData = $response->json('data.workshift_delete');
        $this->assertEquals(0, $responseData['status']);
        $this->assertStringContainsString('Invalid input data', $responseData['message']);
    }
}
