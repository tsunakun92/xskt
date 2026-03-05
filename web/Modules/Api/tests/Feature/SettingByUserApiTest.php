<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\SettingSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;

class SettingByUserApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(SettingSeeder::class);
    }

    /**
     * Get GraphQL query for setting by user.
     *
     * @param  int  $userId
     * @return string
     */
    public function getQuery(int $userId): string {
        return sprintf('query {
            setting_by_user(user_id: %d, version: "1.0", platform: "web") {
                status
                message
                data {
                    language
                    timezone
                    notification_enabled
                    dark_mode
                }
            }
        }', $userId);
    }

    /**
     * Test getting user settings successfully.
     */
    public function test_setting_by_user_success(): void {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make GraphQL request
        $response = $this->postGraphQL($this->getQuery($user->id));

        // Assert: Check response
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'setting_by_user' => [
                    'status' => 1,
                ],
            ],
        ]);

        // Assert: Check data structure
        $data = $response->json('data.setting_by_user.data');
        $this->assertIsString($data['language']);
        $this->assertIsString($data['timezone']);
        $this->assertIsBool($data['notification_enabled']);
        $this->assertIsBool($data['dark_mode']);
    }

    /**
     * Test getting user settings with non-existent user.
     */
    public function test_setting_by_user_not_found(): void {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make GraphQL request with non-existent user_id
        $response = $this->postGraphQL($this->getQuery(99999));

        // Assert: Check error response
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'setting_by_user' => [
                    'status' => 0,
                    'data'   => null,
                ],
            ],
        ]);
    }

    /**
     * Test getting user settings without authentication.
     */
    public function test_setting_by_user_unauthorized(): void {
        // Arrange: Get a user ID
        $user = User::first();

        // Act: Make GraphQL request without authentication
        $response = $this->postGraphQL($this->getQuery($user->id));

        // Assert: Check unauthorized response
        $response->assertStatus(200);
        $response->assertJson([
            'errors' => [
                [
                    'message' => 'Unauthenticated.',
                ],
            ],
            'data'   => [
                'setting_by_user' => null,
            ],
        ]);
    }
}
