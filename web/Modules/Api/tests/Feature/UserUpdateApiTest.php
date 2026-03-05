<?php

namespace Modules\Api\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;
use Modules\Hr\Database\Seeders\HrCompanySeeder;

class UserUpdateApiTest extends BaseApiTest {
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

    public function getMutation($user_id, $username = null, $email = null, $password = null, $role_id = null, $fullname = null, $version = null, $platform = null, $birthday = null, $address = null, $gender = null, $company_id = null) {
        $fields = ['user_id: ' . intval($user_id)];

        if ($username !== null) {
            $fields[] = 'username: "' . addslashes($username) . '"';
        }
        if ($email !== null) {
            $fields[] = 'email: "' . addslashes($email) . '"';
        }
        if ($password !== null) {
            $fields[] = 'password: "' . addslashes($password) . '"';
        }
        if ($role_id !== null) {
            $fields[] = 'role_id: ' . intval($role_id);
        }
        if ($fullname !== null) {
            $fields[] = 'fullname: "' . addslashes($fullname) . '"';
        }
        if ($version !== null) {
            $fields[] = 'version: "' . addslashes($version) . '"';
        }
        if ($platform !== null) {
            $fields[] = 'platform: "' . addslashes($platform) . '"';
        }
        if ($birthday !== null) {
            $fields[] = 'birthday: "' . addslashes($birthday) . '"';
        }
        if ($address !== null) {
            $fields[] = 'address: "' . addslashes($address) . '"';
        }
        if ($gender !== null) {
            $fields[] = 'gender: "' . addslashes($gender) . '"';
        }
        if ($company_id !== null) {
            $fields[] = 'company_id: ' . intval($company_id);
        }

        $fieldsStr = implode(",\n                ", $fields);

        return sprintf('mutation {
            user_update(
                %s
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
        }', $fieldsStr);
    }

    public function test_update_user_success() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation to update an existing user
        $expectedData = [
            'id'       => 2,
            'username' => 'updateduser',
            'password' => 'newpassword123',
            'email'    => 'updateduser@example.com',
            'name'     => 'Updated User',
            'role'     => [
                'id'   => 2,
                'name' => 'Administrator',
            ],
            'status'   => 1,
            'profile'  => [
                'id'         => 1,
                'fullname'   => 'Updated User',
                'birthday'   => '1995-05-15',
                'code'       => '1',
                'address'    => '456 Updated Street',
                'gender'     => 1,
                'company_id' => 1,
            ],
        ];

        $response = $this->postGraphQL($this->getMutation(
            $expectedData['id'],
            $expectedData['username'],
            $expectedData['email'],
            $expectedData['password'],
            $expectedData['role']['id'],
            $expectedData['name'],
            '1.0',
            'web',
            $expectedData['profile']['birthday'],
            $expectedData['profile']['address'],
            $expectedData['profile']['gender'],
            $expectedData['profile']['company_id'],
        ));

        // Expected response data structure
        $response->assertStatus(200);
        $responseData = $response->json('data.user_update');
        $this->assertEquals(1, $responseData['status']);
        if ($responseData['status'] === 1 && $responseData['data'] !== null) {
            $response->assertJsonStructure([
                'data' => [
                    'user_update' => [
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

        // Verify user data
        $mUser = User::find($expectedData['id']);
        $this->assertEquals($expectedData['username'], $mUser->username);
        $this->assertEquals($expectedData['email'], $mUser->email);
        $this->assertEquals($expectedData['name'], $mUser->name);
        $this->assertEquals($expectedData['role']['id'], $mUser->role_id);
        $this->assertTrue(Hash::check($expectedData['password'], $mUser->password));

        // Verify if the response contains the expected message
        $responseData = $response->json()['data']['user_update'];
        $this->assertEquals('User updated successfully', $responseData['message']);
    }

    public function test_update_user_with_non_existent_user_id() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Act: Make a GraphQL mutation with a non-existent user_id
        $response = $this->postGraphQL($this->getMutation(9999, 'testuser', 'testuser@example.com', 'password', 1, 'Test User', '1.0', 'web', '1995-05-15', '123 Test Street', 1, 1));

        // Expected response data
        $response->assertStatus(200);
        $responseData = $response->json('data.user_update');
        $this->assertEquals(0, $responseData['status']);
        $this->assertStringContainsString('Invalid input data', $responseData['message']);
        $this->assertNull($responseData['data']);
    }

    public function test_update_user_duplicate_username() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Create a test user to update
        $testUser = User::create([
            'username' => 'testupdate1',
            'email'    => 'testupdate1@example.com',
            'name'     => 'Test Update User 1',
            'password' => Hash::make('password123'),
            'role_id'  => 3,
            'status'   => User::STATUS_ACTIVE,
        ]);

        // Act: Make a GraphQL mutation with a duplicate username
        $response = $this->postGraphQL($this->getMutation($testUser->id, 'admin', 'uniqueuser@example.com', null, 1, 'Test User', '1.0', 'web', '1995-05-15', '123 Test Street', 1, 1));

        // Expected response data for duplicate username
        $response->assertStatus(200);
        $responseData = $response->json('data.user_update');
        $this->assertEquals(0, $responseData['status']);
        $this->assertStringContainsString('Invalid input data', $responseData['message']);
        $this->assertNull($responseData['data']);
    }

    public function test_update_user_duplicate_email() {
        // Arrange: Authenticate a user
        $user = User::find(2);
        Sanctum::actingAs($user);

        // Create a test user to update
        $testUser = User::create([
            'username' => 'testupdate2',
            'email'    => 'testupdate2@example.com',
            'name'     => 'Test Update User 2',
            'password' => Hash::make('password123'),
            'role_id'  => 3,
            'status'   => User::STATUS_ACTIVE,
        ]);

        // Act: Make a GraphQL mutation with a duplicate email
        $response = $this->postGraphQL($this->getMutation($testUser->id, 'admin3332', 'admin@test.com', null, 1, 'Test User', '1.0', 'web', '1995-05-15', '123 Test Street', 1, 1));

        // Expected response data for duplicate email
        $response->assertStatus(200);
        $responseData = $response->json('data.user_update');
        $this->assertEquals(0, $responseData['status']);
        $this->assertStringContainsString('Invalid input data', $responseData['message']);
        $this->assertNull($responseData['data']);
    }
}
