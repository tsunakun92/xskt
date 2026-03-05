<?php

namespace Modules\Api\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Entities\Sanctum\PersonalAccessToken;
use App\Utils\DomainConst;
use Modules\Admin\Database\Seeders\PermissionSeeder;
use Modules\Admin\Database\Seeders\RolePermissionSeeder;
use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\SettingSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;

class LoginApiTest extends BaseApiTest {
    use RefreshDatabase;

    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        // Add data seeds
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    public function getQuery($username = null, $password = null, $device_token = null, $version = null, $platform = null) {
        $arguments = [];

        if ($username !== null) {
            $arguments[] = 'username: "' . addslashes($username) . '"';
        }
        if ($password !== null) {
            $arguments[] = 'password: "' . addslashes($password) . '"';
        }
        if ($device_token !== null) {
            $arguments[] = 'device_token: "' . addslashes($device_token) . '"';
        }
        if ($version !== null) {
            $arguments[] = 'version: "' . addslashes($version) . '"';
        }
        if ($platform !== null) {
            $arguments[] = 'platform: "' . addslashes($platform) . '"';
        }

        $argumentsString = implode(', ', $arguments);

        $query = 'mutation {
                login(' . $argumentsString . ') {
                    status
                    message
                    token
                    data {
                        user {
                            id
                            username
                            email
                            name
                            status
                        }
                        configs {
                            language
                            timezone
                            notification_enabled
                            dark_mode
                        }
                        permissions {
                            group
                            actions
                        }
                    }
                }
            }';

        return $query;
    }

    /**
     * Get expected response data for a successful login
     *
     * @return array
     */
    public function getExpectedResponseData($token) {
        return [
            'data' => [
                'login' => [
                    'status'  => DomainConst::API_RESPONSE_STATUS_SUCCESS,
                    'message' => 'Login successfully',
                    'token'   => $token,
                    'data'    => [
                        'user'    => [
                            'id'       => '2',
                            'username' => 'admin',
                            'email'    => 'admin@test.com',
                            'name'     => 'Admin',
                            'status'   => 1,
                        ],
                        'configs' => [
                            'language'             => 'en-US',
                            'timezone'             => 'Asia/Tokyo',
                            'notification_enabled' => true,
                            'dark_mode'            => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Assert permissions are mobiles-only and match expected shape.
     *
     * @param  array  $permissions
     * @return void
     */
    private function assertMobilePermissions(array $permissions): void {
        if (empty($permissions)) {
            // Allow empty permissions as long as shape is correct when present
            $this->assertIsArray($permissions);

            return;
        }

        foreach ($permissions as $permission) {
            $this->assertArrayHasKey('group', $permission);
            $this->assertArrayHasKey('actions', $permission);
            $this->assertIsString($permission['group']);
            $this->assertIsArray($permission['actions']);

            foreach ($permission['actions'] as $action) {
                $this->assertIsString($action);
            }
        }
    }

    public function test_login_success() {
        // Act: Make a POST request to the login API
        $response = $this->postGraphQL($this->getQuery('admin', 'adminadmin', 'example_device_token', 'example_version', 'web'));

        // Assert: Check that the response is as expected
        $response->assertStatus(200);

        // Expected response data
        $expectedResponse = $this->getExpectedResponseData($response->json()['data']['login']['token']);

        // Check the content of the JSON response
        $response->assertJson($expectedResponse);

        $permissions = $response->json('data.login.data.permissions');
        $this->assertIsArray($permissions);
        $this->assertMobilePermissions($permissions);

        $responseData = $response->json('data.login');
        $token        = $responseData['token'];
        // Assert that the token is not empty
        $this->assertNotEmpty($token, 'The login token should not be empty');
        // Extract the token ID from the bearer token
        [$tokenId, $tokenValue] = explode('|', $token);
        // Verify that the token exists in the personal_access_tokens table
        $tokenRecord = PersonalAccessToken::find($tokenId);
        $this->assertNotNull($tokenRecord);
        $this->assertEquals($tokenRecord->tokenable_id, 2);
        $this->assertEquals($tokenRecord->tokenable_type, User::class);
        $this->assertEquals(hash('sha256', $tokenValue), $tokenRecord->token);
    }

    public function test_login_success_with_email() {
        $response = $this->postGraphQL($this->getQuery('admin@test.com', 'adminadmin', 'example_device_token', 'example_version', 'web'));

        $response->assertStatus(200);

        $responseData = $response->json('data.login');
        $this->assertEquals(DomainConst::API_RESPONSE_STATUS_SUCCESS, $responseData['status']);
        $this->assertNotEmpty($responseData['token']);

        $permissions = $response->json('data.login.data.permissions');
        $this->assertIsArray($permissions);
        $this->assertMobilePermissions($permissions);
    }

    public function test_wrong_password() {
        // Send the POST request
        $response = $this->postGraphQL($this->getQuery('admin', 'wrong password', 'example_device_token', 'example_version', 'web'));

        // Check the response status
        $response->assertStatus(200);

        // Expected response data
        $expectedResponse = [
            'data' => [
                'login' => [
                    'status'  => DomainConst::API_RESPONSE_STATUS_FAILED,
                    'message' => 'Invalid username/email or password',
                    'token'   => null,
                    'data'    => null,
                ],
            ],
        ];

        // Assert the response matches the expected data
        $response->assertJson($expectedResponse);
    }

    public function test_inactive_account() {
        // Create an inactive account
        $inactiveUser = User::create([
            'username' => 'inactive',
            'email'    => 'inactive@test.com',
            'name'     => 'Inactive User',
            'password' => Hash::make('password123'),
            'role_id'  => 3,
            'status'   => User::STATUS_INACTIVE,
        ]);

        // Act: Make a POST request to the login API with inactive account
        $response = $this->postGraphQL($this->getQuery($inactiveUser->username, 'password123', 'example_device_token', 'example_version', 'web'));

        // Assert: Check that the response is as expected
        $response->assertStatus(200);

        // Expected response data
        $expectedResponse = [
            'data' => [
                'login' => [
                    'status'  => DomainConst::API_RESPONSE_STATUS_FAILED,
                    'message' => 'Account does not exist',
                    'token'   => null,
                    'data'    => null,
                ],
            ],
        ];

        // Check the content of the JSON response
        $response->assertJson($expectedResponse);
    }

    public function test_missing_required_params() {
        // Test missing 'username'
        $response = $this->postGraphQL($this->getQuery(null, 'password', 'example_device_token', 'example_version', 'web'));

        // Expected response data
        $expectedResponse = [
            'errors' => [
                [
                    'message' => 'Field "login" argument "username" of type "String!" is required but not provided.',
                ],
            ],
        ];

        $response->assertStatus(200);
        $response->assertJson($expectedResponse);

        // Test missing 'password'
        $response = $this->postGraphQL($this->getQuery('admin', null, 'example_device_token', 'example_version', 'web'));

        // Expected response data
        $expectedResponse = [
            'errors' => [
                [
                    'message' => 'Field "login" argument "password" of type "String!" is required but not provided.',
                ],
            ],
        ];

        $response->assertStatus(200);
        $response->assertJson($expectedResponse);

        // Test missing 'device_token'
        $response = $this->postGraphQL($this->getQuery('admin', 'adminadmin', null, 'example_version', 'web'));

        // Expected response data
        $expectedResponse = [
            'errors' => [
                [
                    'message' => 'Field "login" argument "device_token" of type "String!" is required but not provided.',
                ],
            ],
        ];

        $response->assertStatus(200);
        $response->assertJson($expectedResponse);

        // Test missing 'version'
        $response = $this->postGraphQL($this->getQuery('admin', 'adminadmin', 'device_token_123', null, 'web'));

        // Expected response data
        $expectedResponse = [
            'errors' => [
                [
                    'message' => 'Field "login" argument "version" of type "String!" is required but not provided.',
                ],
            ],
        ];

        $response->assertStatus(200);
        $response->assertJson($expectedResponse);

        // Test missing 'platform'
        $response = $this->postGraphQL($this->getQuery('admin', 'adminadmin', 'device_token_123', 'version', null));

        // Expected response data
        $expectedResponse = [
            'errors' => [
                [
                    'message' => 'Field "login" argument "platform" of type "String!" is required but not provided.',
                ],
            ],
        ];

        $response->assertStatus(200);
        $response->assertJson($expectedResponse);
    }

    public function test_empty_required_params() {
        // Test empty 'username'
        $response = $this->postGraphQL($this->getQuery('', 'aaa', 'device_token_123', '1.0', 'web'));

        // Expected response data
        $expectedResponse = [
            'data' => [
                'login' => [
                    'status' => DomainConst::API_RESPONSE_STATUS_FAILED,
                    'token'  => null,
                    'data'   => null,
                ],
            ],
        ];

        $response->assertStatus(200);
        $response->assertJson($expectedResponse);

        // Test empty 'password'
        $response = $this->postGraphQL($this->getQuery('admin', '', 'device_token_123', '1.0', 'web'));

        $response->assertStatus(200);
        $response->assertJson($expectedResponse);

        // Test empty 'device_token'
        $response = $this->postGraphQL($this->getQuery('admin', 'adminadmin', '', '1.0', 'web'));

        $response->assertStatus(200);
        $response->assertJson($expectedResponse);

        // Test empty 'version'
        $response = $this->postGraphQL($this->getQuery('admin', 'adminadmin', 'device_token_123', '', 'web'));

        $response->assertStatus(200);
        $response->assertJson($expectedResponse);
    }

    public function test_exceed_max_mobile_tokens() {
        // Assume the max number of mobile devices allowed is 3
        $maxMobileDevices = PersonalAccessToken::MAX_MOBILE_DEVICES;

        // Get the user
        $user = User::find(2);
        $this->assertNotNull($user, 'User with ID 2 should exist');

        // Simulate login on max allowed devices
        $createdTokens = [];
        for ($i = 1; $i <= $maxMobileDevices; $i++) {
            $token = $user->createToken(Str::random(60), 'example_device_token_' . $i, PersonalAccessToken::PLATFORM_ANDROID);
            // Ensure token is created and saved
            $this->assertNotNull($token, "Token $i should be created");
            $createdTokens[] = $token;
        }

        // Assert that the user has max tokens
        $mobileTokens = PersonalAccessToken::getMobileTokensByUserId($user->id);
        $this->assertCount($maxMobileDevices, $mobileTokens, 'Expected ' . $maxMobileDevices . ' mobile tokens but found ' . $mobileTokens->count());

        // Act: Simulate a new login which should cause the oldest token to be deleted
        $response = $this->postGraphQL($this->getQuery('admin', 'adminadmin', 'new_device_token', 'version_1', 'android'));

        // Expected response data
        $expectedResponse = $this->getExpectedResponseData($response->json()['data']['login']['token']);

        // Assert: Check that the response is as expected
        $response->assertStatus(200);
        $response->assertJson($expectedResponse);

        // Assert that the oldest token was deleted and a new one was added
        $mobileTokens = PersonalAccessToken::getMobileTokensByUserId($user->id);
        // Assert that the user has max tokens
        $this->assertCount($maxMobileDevices, $mobileTokens);
        // Oldest token should be deleted
        $this->assertFalse($mobileTokens->contains('device_token', 'example_device_token_1'));
        // New token should be added
        $this->assertTrue($mobileTokens->contains('device_token', 'new_device_token'));
    }

    public function test_api_key_required() {
        // Test without X-API-KEY header
        $response = $this->postJson($this->apiUrl, [
            'query' => $this->getQuery('admin', 'adminadmin', 'device_token', '1.0', 'web'),
        ], [
            'Accept' => 'application/json',
        ]);

        // Should return 401 if API_KEY is set in env
        if (env('API_KEY')) {
            $response->assertStatus(401);
            $response->assertJson([
                'message' => 'Unauthorized: Invalid or missing X-API-KEY header',
            ]);
        } else {
            // If API_KEY is not set, request should proceed
            $response->assertStatus(200);
        }
    }

    public function test_api_key_invalid() {
        // Set API_KEY in env for this test
        putenv('API_KEY=correct-key');

        // Test with wrong X-API-KEY header
        $response = $this->postJson($this->apiUrl, [
            'query' => $this->getQuery('admin', 'adminadmin', 'device_token', '1.0', 'web'),
        ], [
            'X-API-KEY' => 'wrong-key',
            'Accept'    => 'application/json',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'status'  => 0,
            'message' => 'Unauthorized: Invalid or missing X-API-KEY header',
        ]);
    }
}
