<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;
use Modules\Hr\Database\Seeders\HrCompanySeeder;

class CompanyCreateApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        // Seed the database with necessary data
        $this->seed(UserSeeder::class);
        $this->seed(HrCompanySeeder::class);
    }

    public function getMutation($name, $phone, $email, $open_date, $address, $director, $version, $platform, $code = null) {
        return sprintf('mutation {
            company_create(
                %s
                name: "%s"
                phone: "%s"
                email: "%s"
                open_date: "%s"
                address: "%s"
                director: %d
                version: "%s"
                platform: "%s"
            ) {
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
        }',
            $code ? 'code: "' . $code . '"' : '',
            $name,
            $phone,
            $email,
            $open_date,
            $address,
            $director,
            $version,
            $platform);
    }

    public function test_create_company_success() {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation to create a new company
        $response = $this->postGraphQL($this->getMutation('Sample Company', '+1234567890', 'contact@samplecompany.com', '01/01/2020', '123 Main Street, City, Country', 1, '1.0', 'web'));

        // Assert: Check if the response is successful and matches the expected structure
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'company_create' => [
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
        $responseData = $response->json('data.company_create');
        $this->assertEquals(1, $responseData['status']);
        $this->assertEquals('Company created successfully', $responseData['message']);
    }

    public function test_create_company_with_missing_fields() {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation with missing required fields
        $response = $this->postGraphQL($this->getMutation('', '', '', '', '', 0, '', 'web'));

        // Assert: Check if the response indicates an error due to invalid input
        $response->assertStatus(200);
        $this->assertStringContainsString('Invalid input data:', $response->json('data.company_create.message'));
    }

    public function test_create_company_with_invalid_email_format() {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation with an invalid email format
        $response = $this->postGraphQL($this->getMutation('Company Invalid Email', '+1234567890', 'invalid-email', '01/01/2021', '456 Another St, City, Country', 1, '1.0', 'web'));

        // Assert: Check if the response indicates an error due to invalid email
        $response->assertStatus(200);
        $this->assertStringContainsString('Invalid input data:', $response->json('data.company_create.message'));
    }

    public function test_create_company_with_invalid_date_format() {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation with an invalid date format
        $response = $this->postGraphQL($this->getMutation('Company Invalid Date', '+9876543210', 'validemail@example.com', '2020-01-01', '789 Business Ave, City, Country', 1, '1.0', 'web'));

        // Assert: Check if the response indicates an error due to invalid date format
        $response->assertStatus(200);
        $this->assertStringContainsString('Invalid input data:', $response->json('data.company_create.message'));
    }
}
