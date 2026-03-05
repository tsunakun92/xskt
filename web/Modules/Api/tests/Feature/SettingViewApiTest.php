<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\SettingSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;

class SettingViewApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(SettingSeeder::class);
    }

    /**
     * Get GraphQL query for setting view.
     *
     * @param  string  $settingKey
     * @return string
     */
    public function getQuery(string $settingKey): string {
        return sprintf('query {
            setting_view(setting_key: "%s", version: "1.0", platform: "web") {
                status
                message
                data {
                    id
                    key
                    value
                    description
                    user_flag
                    status
                    updated_at
                }
            }
        }', addslashes($settingKey));
    }

    /**
     * Test getting setting view successfully.
     */
    public function test_setting_view_success(): void {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make GraphQL request
        $response = $this->postGraphQL($this->getQuery('language'));

        // Assert: Check response
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'setting_view' => [
                    'status' => 1,
                    'data'   => [
                        'key' => 'language',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test getting setting view with non-existent key.
     */
    public function test_setting_view_not_found(): void {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make GraphQL request with non-existent key
        $response = $this->postGraphQL($this->getQuery('non_existent_key'));

        // Assert: Check error response
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'setting_view' => [
                    'status' => 0,
                    'data'   => null,
                ],
            ],
        ]);
    }

    /**
     * Test getting setting view without authentication.
     */
    public function test_setting_view_unauthorized(): void {
        // Act: Make GraphQL request without authentication
        $response = $this->postGraphQL($this->getQuery('language'));

        // Assert: Check unauthorized response
        $response->assertStatus(200);
        $response->assertJson([
            'errors' => [
                [
                    'message' => 'Unauthenticated.',
                ],
            ],
            'data'   => [
                'setting_view' => null,
            ],
        ]);
    }
}
