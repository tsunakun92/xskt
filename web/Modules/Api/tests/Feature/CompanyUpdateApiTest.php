<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;
use Modules\Hr\Database\Seeders\HrCompanySeeder;
use Modules\Hr\Models\HrCompany;

class CompanyUpdateApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        // Seed the database with necessary data
        $this->seed(UserSeeder::class);
        $this->seed(HrCompanySeeder::class);
    }

    public function getMutation($company_id, $version, $platform, $code = null, $name = null, $phone = null, $email = null, $open_date = null, $address = null, $director = null, $status = null) {
        $mutation = sprintf('mutation {
            company_update(
                company_id: %d
                version: "%s"
                platform: "%s"',
            $company_id, $version, $platform
        );

        if ($code !== null) {
            $mutation .= sprintf(' code: "%s"', $code);
        }
        if ($name !== null) {
            $mutation .= sprintf(' name: "%s"', $name);
        }
        if ($phone !== null) {
            $mutation .= sprintf(' phone: "%s"', $phone);
        }
        if ($email !== null) {
            $mutation .= sprintf(' email: "%s"', $email);
        }
        if ($open_date !== null) {
            $mutation .= sprintf(' open_date: "%s"', $open_date);
        }
        if ($address !== null) {
            $mutation .= sprintf(' address: "%s"', $address);
        }
        if ($director !== null) {
            $mutation .= sprintf(' director: %d', $director);
        }
        if ($status !== null) {
            $mutation .= sprintf(' status: %d', $status);
        }

        $mutation .= ') {
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
        }';

        return $mutation;
    }

    public function test_update_company_success() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Arrange: Get an existing company
        $company = HrCompany::first();

        // Act: Make a GraphQL mutation to update the company with some fields
        $response = $this->postGraphQL($this->getMutation($company->id, '1.0', 'web', 'UPD123-000001', 'Updated Company', '+1234567899', 'update@company.com', '01/01/2020', '456 Updated Street, City', 2, 1));

        // Assert: Check if the response is successful and matches the expected structure
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'company_update' => [
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
        ]);

        // Verify if the response status is 1 (success)
        $responseData = $response->json('data.company_update');
        $this->assertEquals(1, $responseData['status']);
        $this->assertEquals('Company updated successfully', $responseData['message']);
    }

    public function test_update_company_with_invalid_date_format() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Arrange: Get an existing company
        $company = HrCompany::first();

        // Act: Make a GraphQL mutation with an invalid date format
        $response = $this->postGraphQL($this->getMutation($company->id, '1.0', 'web', 'UPD126-000001', 'Company Invalid Date', null, 'validemail@example.com', '2020-01-01'));

        // Assert: Check if the response indicates an error due to invalid date format
        $response->assertStatus(200);
        $this->assertStringContainsString('Invalid input data:', $response->json('data.company_update.message'));
    }

    public function test_update_company_without_required_fields() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Arrange: Get an existing company
        $company = HrCompany::first();

        // Act: Make a GraphQL mutation with only the required fields
        $response = $this->postGraphQL($this->getMutation($company->id, '1.0', 'web'));

        // Assert: Check if the response is successful and matches the expected structure
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'company_update' => [
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
        ]);

        // Verify if the response indicates success with status 1
        $responseData = $response->json('data.company_update');
        $this->assertEquals(1, $responseData['status']);
        $this->assertEquals('Company updated successfully', $responseData['message']);
    }

    public function test_update_company_not_found() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation with a non-existent company_id
        $response = $this->postGraphQL($this->getMutation(999, '1.0', 'web'));

        // Assert: Check if the response indicates the company was not found
        $response->assertStatus(200);
        $this->assertStringContainsString('Invalid input data:', $response->json('data.company_update.message'));
    }

    public function test_update_company_with_duplicate_code() {
        // Arrange: Authenticate a user and get existing companies
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Create a duplicate code scenario
        $company1 = HrCompany::first();
        $company2 = HrCompany::where('id', '!=', $company1->id)->first();

        if ($company2) {
            // Act: Attempt to update company2 with the same code as company1
            $response = $this->postGraphQL($this->getMutation($company2->id, '1.0', 1, $company1->code));

            // Assert: Check if the response indicates duplicate code error
            $response->assertStatus(200);
            $message = $response->json('data.company_update.message');
            $this->assertStringContainsString('Invalid input data', $message);
            // Check for either English or Japanese error message about duplicate code
            $this->assertTrue(
                str_contains($message, 'code') ||
                str_contains($message, '既に使用されています') ||
                str_contains($message, 'has already been taken')
            );
        } else {
            $this->markTestSkipped('Not enough companies to test duplicate code');
        }
    }

    public function test_update_company_with_invalid_email_format() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Arrange: Get an existing company
        $company = HrCompany::first();

        // Act: Make a GraphQL mutation with an invalid email format
        $response = $this->postGraphQL($this->getMutation($company->id, '1.0', 'web', 'UPD126-000001', 'Company Invalid Email', '+1234567890', 'invalid-email'));

        // Assert: Check if the response indicates an error due to invalid email format
        $response->assertStatus(200);
        $this->assertStringContainsString('Invalid input data:', $response->json('data.company_update.message'));
    }

    public function test_update_company_with_valid_fields_only() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Arrange: Get an existing company
        $company = HrCompany::first();

        // Act: Make a GraphQL mutation with only required fields and one additional field
        $response = $this->postGraphQL($this->getMutation($company->id, '1.0', 'web', null, 'Updated Company Name'));

        // Assert: Check if the response is successful and the company name is updated
        $response->assertStatus(200);
        $responseData = $response->json('data.company_update');
        $this->assertEquals(1, $responseData['status']);
        $this->assertEquals('Company updated successfully', $responseData['message']);
        $this->assertEquals('Updated Company Name', $responseData['data']['name']);
        // Check other fields are unchanged
        $this->assertEquals($company->code, $responseData['data']['code']);
        $this->assertEquals($company->phone, $responseData['data']['phone']);
        $this->assertEquals($company->email, $responseData['data']['email']);
        $this->assertEquals($company->open_date, $responseData['data']['open_date']);
        $this->assertEquals($company->address, $responseData['data']['address']);
        $this->assertEquals($company->director, $responseData['data']['director']);
    }
}
