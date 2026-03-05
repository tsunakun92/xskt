<?php

namespace Modules\Api\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

class ApiKeyMiddlewareTest extends BaseApiTest {
    use RefreshDatabase;

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void {
        // Reset config to avoid test interference
        Config::set('api.api_key', null);
        parent::tearDown();
    }

    public function test_api_key_required_when_set() {
        // Set API_KEY in config
        Config::set('api.api_key', 'test-api-key-123');

        // Test without X-API-KEY header
        $response = $this->postJson($this->apiUrl, [
            'query' => 'query { __typename }',
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'status'  => 0,
            'message' => 'Unauthorized: Invalid or missing X-API-KEY header',
        ]);
    }

    public function test_api_key_valid() {
        // Set API_KEY in config
        Config::set('api.api_key', 'test-api-key-123');

        // Test with correct X-API-KEY header
        $response = $this->postJson($this->apiUrl, [
            'query' => 'query { __typename }',
        ], [
            'X-API-KEY' => 'test-api-key-123',
            'Accept'    => 'application/json',
        ]);

        // Should not return 401 (may return GraphQL error but not 401)
        $this->assertNotEquals(401, $response->status());
    }

    public function test_api_key_invalid() {
        // Set API_KEY in config
        Config::set('api.api_key', 'test-api-key-123');

        // Test with wrong X-API-KEY header
        $response = $this->postJson($this->apiUrl, [
            'query' => 'query { __typename }',
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

    public function test_api_key_not_required_when_not_set() {
        // Unset API_KEY in config
        Config::set('api.api_key', null);

        // Test without X-API-KEY header
        $response = $this->postJson($this->apiUrl, [
            'query' => 'query { __typename }',
        ], [
            'Accept' => 'application/json',
        ]);

        // Should not return 401 if API_KEY is not set
        $this->assertNotEquals(401, $response->status());
    }

    public function test_api_key_works_with_login() {
        // Set API_KEY in config
        Config::set('api.api_key', 'test-api-key-123');

        // Test login with correct API key
        $response = $this->postJson($this->apiUrl, [
            'query' => 'mutation {
                login(
                    username: "admin",
                    password: "adminadmin",
                    device_token: "test",
                    version: "1.0",
                    platform: "web"
                ) {
                    status
                    message
                }
            }',
        ], [
            'X-API-KEY' => 'test-api-key-123',
            'Accept'    => 'application/json',
        ]);

        // Should not return 401
        $this->assertNotEquals(401, $response->status());
    }
}
