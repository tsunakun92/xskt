<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;
use Modules\Hr\Database\Seeders\HrCompanySeeder;
use Modules\Hr\Database\Seeders\HrWorkShiftSeeder;
use Modules\Hr\Models\HrWorkShift;

class WorkshiftUpdateApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->seed(HrCompanySeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(HrWorkShiftSeeder::class);
    }

    public function getMutation($workshift_id, $version, $platform, $code = null, $name = null, $description = null, $start = null, $end = null, $company_id = null, $role_id = null, $max_employee_cnt = null, $color = null, $status = null) {
        $mutation = sprintf('mutation {
            workshift_update(
                workshift_id: %d,
                version: "%s",
                platform: "%s"',
            $workshift_id, $version, $platform
        );

        if ($code !== null) {
            $mutation .= sprintf(', code: "%s"', $code);
        }
        if ($name !== null) {
            $mutation .= sprintf(', name: "%s"', $name);
        }
        if ($description !== null) {
            $mutation .= sprintf(', description: "%s"', $description);
        }
        if ($start !== null) {
            $mutation .= sprintf(', start: "%s"', $start);
        }
        if ($end !== null) {
            $mutation .= sprintf(', end: "%s"', $end);
        }
        if ($company_id !== null) {
            $mutation .= sprintf(', company_id: %d', $company_id);
        }
        if ($role_id !== null) {
            $mutation .= sprintf(', role_id: %d', $role_id);
        }
        if ($max_employee_cnt !== null) {
            $mutation .= sprintf(', max_employee_cnt: %d', $max_employee_cnt);
        }
        if ($color !== null) {
            $mutation .= sprintf(', color: "%s"', $color);
        }
        if ($status !== null) {
            $mutation .= sprintf(', status: %d', $status);
        }

        $mutation .= ') {
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
                    company { id }
                    role { id }
                }
            }
        }';

        return $mutation;
    }

    public function test_update_workshift_success() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Arrange: Get an existing workshift
        $workshift = HrWorkShift::first();

        // Act: Make a GraphQL mutation to update the workshift with new data
        $response = $this->postGraphQL($this->getMutation(
            $workshift->id,
            '1.0',
            1,
            'UPD123-000001',
            'Updated Workshift',
            'Shift Description',
            '08:00',
            '16:00',
            1,
            1,
            10,
            'FFFFFF',
            1
        ));

        // Assert: Check if the response is successful and matches the expected structure
        $response->assertStatus(200);
        $responseData = $response->json('data.workshift_update');
        $this->assertEquals(1, $responseData['status']);
        if ($responseData['status'] === 1 && $responseData['data'] !== null) {
            $response->assertJsonStructure([
                'data' => [
                    'workshift_update' => [
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
        $responseData = $response->json('data.workshift_update');
        $this->assertEquals(1, $responseData['status']);
        $this->assertEquals('Workshift updated successfully', $responseData['message']);
    }

    public function test_update_workshift_with_invalid_time_format() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Arrange: Get an existing workshift
        $workshift = HrWorkShift::first();

        // Act: Make a GraphQL mutation with an invalid start time format
        $response = $this->postGraphQL($this->getMutation($workshift->id, '1.0', 'web', 'UPD126-000001', 'Invalid Time Format', 'Shift Description', 'invalid-start', '16:00'));

        // Assert: Check if the response indicates an error due to invalid time format
        $response->assertStatus(200);
        $this->assertStringContainsString('Invalid input data:', $response->json('data.workshift_update.message'));
    }

    public function test_update_workshift_with_duplicate_code() {
        // Arrange: Authenticate a user and get existing workshifts
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Create a duplicate code scenario
        $workshift1 = HrWorkShift::first();
        $workshift2 = HrWorkShift::where('id', '!=', $workshift1->id)->first();

        if ($workshift2) {
            // Act: Attempt to update workshift2 with the same code as workshift1
            $response = $this->postGraphQL($this->getMutation($workshift2->id, '1.0', 'web', $workshift1->code));

            // Assert: Check if the response indicates duplicate code error
            $response->assertStatus(200);
            $message = $response->json('data.workshift_update.message');
            $this->assertStringContainsString('Invalid input data', $message);
            // Check for either English or Japanese error message about duplicate code
            $this->assertTrue(
                str_contains($message, 'code') ||
                str_contains($message, '既に使用されています') ||
                str_contains($message, 'has already been taken')
            );
        } else {
            $this->markTestSkipped('Not enough workshifts to test duplicate code');
        }
    }

    public function test_update_workshift_with_invalid_color_format() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Arrange: Get an existing workshift
        $workshift = HrWorkShift::first();

        // Act: Make a GraphQL mutation with an invalid color format
        $response = $this->postGraphQL($this->getMutation($workshift->id, '1.0', 'web', 'UPD126-000001', 'Invalid Color Format', 'Shift Description', '08:00', '16:00', 1, 1, 10, 'invalid-color'));

        // Assert: Check if the response indicates an error due to invalid color format
        $response->assertStatus(200);
        $this->assertStringContainsString('Invalid input data:', $response->json('data.workshift_update.message'));
    }
}
