<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;
use Modules\Hr\Database\Seeders\HrCompanySeeder;

class UserCreateApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        // Seed the database
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(HrCompanySeeder::class);
    }

    public function getMutation($username, $email, $password, $role_id, $fullname, $version, $platform, $birthday = null, $address = null, $gender = null, $company_id = null) {
        $optionalFields = [];
        if ($birthday !== null) {
            $optionalFields[] = 'birthday: "' . addslashes($birthday) . '"';
        }
        if ($address !== null) {
            $optionalFields[] = 'address: "' . addslashes($address) . '"';
        }
        if ($gender !== null) {
            $optionalFields[] = 'gender: ' . (int) $gender;
        }
        if ($company_id !== null) {
            $optionalFields[] = 'company_id: ' . intval($company_id);
        }
        $optionalFieldsStr = !empty($optionalFields) ? "\n                " . implode("\n                ", $optionalFields) : '';

        return sprintf('mutation {
            user_create(
                username: "%s"
                email: "%s"
                password: "%s"
                role_id: %d
                fullname: "%s"
                version: "%s"
                platform: "%s"%s
            ) {
                status
                message
                data {
                    id
                    username
                    email
                    name
                    status
                    profile {
                        id
                        fullname
                        birthday
                        code
                        address
                        gender
                        status
                    }
                    role {
                        id
                        name
                        status
                    }
                }
            }
        }',
            addslashes($username), addslashes($email), addslashes($password), $role_id, addslashes($fullname), addslashes($version), addslashes($platform), $optionalFieldsStr);
    }

    public function test_create_user_success() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation to create a new user
        $response = $this->postGraphQL($this->getMutation('test1', 'test1@example.com', 'testtest', 1, 'Test User', '1.0', 'web', '1995-05-15', '123 Test Street, Test City', '1', 1));

        // Expected response data structure
        $response->assertStatus(200);
        $responseData = $response->json('data.user_create');
        $this->assertEquals(1, $responseData['status']);
        if ($responseData['status'] === 1 && $responseData['data'] !== null) {
            $response->assertJsonStructure([
                'data' => [
                    'user_create' => [
                        'status',
                        'message',
                        'data' => [
                            'id',
                            'username',
                            'email',
                            'name',
                            'status',
                            'profile' => [
                                'id',
                                'fullname',
                                'birthday',
                                'code',
                                'address',
                                'gender',
                                'status',
                            ],
                            'role'    => [
                                'id',
                                'name',
                                'status',
                            ],
                        ],
                    ],
                ],
            ]);
        }

        // Verify if the response status is 1 (success)
        $responseData = $response->json('data.user_create');
        $this->assertEquals(1, $responseData['status']);
        $this->assertEquals('User created successfully', $responseData['message']);
    }

    public function test_create_user_duplicate_username() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation with a duplicate username
        $response = $this->postGraphQL($this->getMutation('admin', 'duplicate@example.com', 'testtest', 1, 'Duplicate User', '1.0', 'web'));

        // Expected response data for duplicate username
        $response->assertStatus(200);
        $responseData = $response->json('data.user_create');
        $this->assertEquals(0, $responseData['status']);
        $this->assertStringContainsString('Invalid input data', $responseData['message']);
        $this->assertNull($responseData['data']);
    }

    public function test_create_user_duplicate_email() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation with a duplicate email
        $response = $this->postGraphQL($this->getMutation('newusername', 'admin@test.com', 'testtest', 1, 'Duplicate Email', '1.0', 'web'));

        // Expected response data for duplicate email
        $response->assertStatus(200);
        $responseData = $response->json('data.user_create');
        $this->assertEquals(0, $responseData['status']);
        $this->assertStringContainsString('Invalid input data', $responseData['message']);
        $this->assertNull($responseData['data']);
    }

    public function test_create_user_with_non_existent_role_id() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation with a non-existent role_id
        $response = $this->postGraphQL($this->getMutation('test2', 'test2@example.com', 'testpassword', 999, 'Test User', '1.0', 'web'));

        // Expected response data
        $response->assertStatus(200);
        $responseData = $response->json('data.user_create');
        $this->assertEquals(0, $responseData['status']);
        $this->assertStringContainsString('Invalid input data', $responseData['message']);
        $this->assertNull($responseData['data']);
    }

    public function test_create_user_with_non_existent_company_id() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation with a non-existent company_id
        $response = $this->postGraphQL($this->getMutation('test3', 'test3@example.com', 'testpassword', 1, 'Test User', '1.0', 'web', '1995-05-15', '456 Test Avenue', '1', 999));

        // Expected response data
        $response->assertStatus(200);
        $responseData = $response->json('data.user_create');
        $this->assertEquals(0, $responseData['status']);
        $this->assertStringContainsString('Invalid input data', $responseData['message']);
        $this->assertNull($responseData['data']);
    }

    public function test_create_user_with_empty_required_fields() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        $requiredFields = [
            'username'   => '',
            'email'      => '',
            'password'   => '',
            'role_id'    => '',
            'fullname'   => '',
            'version'    => '',
            'birthday'   => '',
            'address'    => '',
            'gender'     => '',
            'company_id' => '',
        ];

        foreach ($requiredFields as $field => $value) {
            if ($field === 'birthday' || $field === 'address' || $field === 'gender') {
                continue;
            }

            $response = $this->postGraphQL($this->getMutation(
                $field === 'username' ? $value : 'testuser',
                $field === 'email' ? $value : 'test@example.com',
                $field === 'password' ? $value : 'testpassword',
                $field === 'role_id' ? $value : 1,
                $field === 'fullname' ? $value : 'Test User',
                $field === 'version' ? $value : '1.0',
                $field === 'platform' ? $value : 'web',
                $field === 'birthday' ? $value : '1995-05-15',
                $field === 'address' ? $value : '123 Test Street, Test City',
                $field === 'gender' ? $value : '1',
                $field === 'company_id' ? $value : 1
            ));

            // Assert response for each field being empty or null
            $response->assertStatus(200);
            $responseData = $response->json('data.user_create');
            $this->assertEquals(0, $responseData['status']);
            $this->assertStringContainsString('Invalid input data', $responseData['message']);
            $this->assertNull($responseData['data']);
        }
    }
}
