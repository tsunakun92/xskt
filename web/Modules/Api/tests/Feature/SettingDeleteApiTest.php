<?php

namespace Modules\Api\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\SettingSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\Setting;
use Modules\Admin\Models\User;

class SettingDeleteApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(SettingSeeder::class);
    }

    /**
     * Get GraphQL mutation for setting delete.
     *
     * @param  string  $key
     * @return string
     */
    public function getMutation(string $key): string {
        return sprintf('mutation {
            setting_delete(key: "%s", version: "1.0", platform: "web") {
                status
                message
            }
        }', addslashes($key));
    }

    /**
     * Test deleting setting successfully.
     */
    public function test_setting_delete_success(): void {
        // Arrange: Create a test setting
        $setting = Setting::create([
            'key'         => 'test_delete_key',
            'value'       => 'test_value',
            'description' => 'Test setting for deletion',
            'user_flag'   => 0,
            'status'      => Setting::STATUS_ACTIVE,
        ]);

        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make GraphQL request
        $response = $this->postGraphQL($this->getMutation('test_delete_key'));

        // Assert: Check response
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'setting_delete' => [
                    'status' => 1,
                ],
            ],
        ]);

        // Assert: Check setting was deleted from database
        $this->assertDatabaseMissing('settings', [
            'key' => 'test_delete_key',
        ]);
    }

    /**
     * Test deleting setting with non-existent key.
     */
    public function test_setting_delete_not_found(): void {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make GraphQL request with non-existent key
        $response = $this->postGraphQL($this->getMutation('non_existent_key'));

        // Assert: Check error response
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'setting_delete' => [
                    'status' => 0,
                ],
            ],
        ]);
    }

    /**
     * Test deleting setting without authentication.
     */
    public function test_setting_delete_unauthorized(): void {
        // Act: Make GraphQL request without authentication
        $response = $this->postGraphQL($this->getMutation('language'));

        // Assert: Check unauthorized response
        $response->assertStatus(200);
        $response->assertJson([
            'errors' => [
                [
                    'message' => 'Unauthenticated.',
                ],
            ],
            'data'   => [
                'setting_delete' => null,
            ],
        ]);
    }

    /**
     * Test deleting setting also deletes user overrides.
     */
    public function test_setting_delete_removes_user_overrides(): void {
        // Arrange: Create a test setting
        $setting = Setting::create([
            'key'         => 'test_delete_with_overrides',
            'value'       => 'test_value',
            'description' => 'Test setting',
            'user_flag'   => 1,
            'status'      => Setting::STATUS_ACTIVE,
        ]);

        // Arrange: Create user override
        $user = User::first();
        DB::table('user_settings')->insert([
            'user_id'    => $user->id,
            'setting_id' => $setting->id,
            'value'      => 'override_value',
        ]);

        // Arrange: Authenticate user
        Sanctum::actingAs($user);

        // Act: Delete setting
        $response = $this->postGraphQL($this->getMutation('test_delete_with_overrides'));

        // Assert: Check response
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'setting_delete' => [
                    'status' => 1,
                ],
            ],
        ]);

        // Assert: Check user override was also deleted
        $this->assertDatabaseMissing('user_settings', [
            'setting_id' => $setting->id,
        ]);
    }
}
