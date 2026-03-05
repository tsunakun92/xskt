<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;
use Modules\Hr\Database\Seeders\HrCompanySeeder;
use Modules\Hr\Database\Seeders\HrWorkShiftSeeder;

class WorkshiftCreateApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        // Seed the database with necessary data
        $this->seed(HrCompanySeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(HrWorkShiftSeeder::class);
    }

    public function getMutation($name, $description, $start, $end, $company_id, $role_id, $max_employee_cnt, $color, $version, $platform, $code = null) {
        return sprintf('mutation {
            workshift_create(
                %s
                name: "%s",
                description: "%s",
                start: "%s",
                end: "%s",
                company_id: %d,
                role_id: %d,
                max_employee_cnt: %d,
                color: "%s",
                version: "%s",
                platform: "%s"
            ) {
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
                    company {
                        id
                    }
                    role {
                        id
                    }
                }
            }
        }',
            $code ? 'code: "' . $code . '",' : '',
            $name, $description, $start, $end, $company_id, $role_id, $max_employee_cnt, $color, $version, $platform);
    }

    public function test_create_workshift_success() {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation to create a new work shift
        $response = $this->postGraphQL($this->getMutation(
            'Evening Shift', 'Shift from 5PM to 1AM', '17:00', '01:00', 1, 1, 10, 'FFFFFF', '1.0', 'web', null
        ));

        // Assert: Check if the response is successful and matches the expected structure
        $response->assertStatus(200);
        $responseData = $response->json('data.workshift_create');
        $this->assertEquals(1, $responseData['status']);
        if ($responseData['status'] === 1 && $responseData['data'] !== null) {
            $response->assertJsonStructure([
                'data' => [
                    'workshift_create' => [
                        'status',
                        'message',
                        'data' => [
                            'id',
                            'code',
                            'name',
                            'description',
                            'start',
                            'end',
                            'max_employee_cnt',
                            'color',
                            'status',
                            'company' => ['id'],
                            'role'    => ['id'],
                        ],
                    ],
                ],
            ]);
        }

        // Verify if the response status is 1 (success)
        $responseData = $response->json('data.workshift_create');
        $this->assertEquals(1, $responseData['status']);
        $this->assertEquals('Workshift created successfully', $responseData['message']);
    }

    public function test_create_workshift_with_missing_fields() {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation with missing required fields
        $response = $this->postGraphQL($this->getMutation(
            '', '', '', '', 0, 0, 0, '', '', 'web', null
        ));

        // Assert: Check if the response indicates an error due to invalid input
        $response->assertStatus(200);
        $this->assertStringContainsString('Invalid input data:', $response->json('data.workshift_create.message'));
    }

    public function test_create_workshift_with_invalid_time_format() {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation with invalid time format
        $response = $this->postGraphQL($this->getMutation(
            'Evening Shift', 'Shift from 5PM to 1AM', '5PM', '1AM', 1, 1, 10, 'FFFFFF', '1.0', 'web', 'WSH125'
        ));

        // Assert: Check if the response indicates an error due to invalid time format
        $response->assertStatus(200);
        $this->assertStringContainsString('Invalid input data:', $response->json('data.workshift_create.message'));
    }

    public function test_create_workshift_with_invalid_color_code() {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation with an invalid color code
        $response = $this->postGraphQL($this->getMutation(
            'Evening Shift', 'Shift from 5PM to 1AM', '17:00', '01:00', 1, 1, 10, 'GGGGGG', '1.0', 'web', '125'
        ));

        // Assert: Check if the response indicates an error due to invalid color format
        $response->assertStatus(200);
        $this->assertStringContainsString('Invalid input data:', $response->json('data.workshift_create.message'));
    }
}
