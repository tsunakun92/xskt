<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\SettingSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\Setting;
use Modules\Admin\Models\User;

class SettingUpdateApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(SettingSeeder::class);
    }

    /**
     * Get GraphQL mutation for setting update.
     *
     * @param  string  $key
     * @param  string  $value
     * @param  string|null  $description
     * @param  int|null  $userFlag
     * @return string
     */
    public function getMutation(string $key, string $value, ?string $description = null, ?int $userFlag = null): string {
        $descValue     = $description !== null ? '"' . addslashes($description) . '"' : 'null';
        $userFlagValue = $userFlag !== null ? (string) $userFlag : 'null';

        return sprintf('mutation {
            setting_update(key: "%s", value: "%s", description: %s, user_flag: %s, version: "1.0", platform: "web") {
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
        }', addslashes($key), addslashes($value), $descValue, $userFlagValue);
    }

    /**
     * Test updating setting successfully.
     */
    public function test_setting_update_success(): void {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make GraphQL request
        $response = $this->postGraphQL($this->getMutation('language', 'ja', 'Updated language', 1));

        // Assert: Check response
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'setting_update' => [
                    'status' => 1,
                    'data'   => [
                        'key'   => 'language',
                        'value' => 'ja',
                    ],
                ],
            ],
        ]);

        // Assert: Check setting was updated in database
        $this->assertDatabaseHas('settings', [
            'key'   => 'language',
            'value' => 'ja',
        ]);
    }

    /**
     * Test updating setting with non-existent key.
     */
    public function test_setting_update_not_found(): void {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make GraphQL request with non-existent key
        $response = $this->postGraphQL($this->getMutation('non_existent_key', 'test_value'));

        // Assert: Check error response
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'setting_update' => [
                    'status' => 0,
                    'data'   => null,
                ],
            ],
        ]);
    }

    /**
     * Test updating setting without authentication.
     */
    public function test_setting_update_unauthorized(): void {
        // Act: Make GraphQL request without authentication
        $response = $this->postGraphQL($this->getMutation('language', 'ja'));

        // Assert: Check unauthorized response
        $response->assertStatus(200);
        $response->assertJson([
            'errors' => [
                [
                    'message' => 'Unauthenticated.',
                ],
            ],
            'data'   => [
                'setting_update' => null,
            ],
        ]);
    }
}
