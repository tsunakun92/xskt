<?php

namespace Modules\Api\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseApiTest extends TestCase {
    use RefreshDatabase;

    protected $apiUrl = '/graphql';

    protected $apiKey = 'test-api-key';

    /**
     * Make a GraphQL request with X-API-KEY header
     *
     * @param  string  $query
     * @param  array  $headers
     * @return \Illuminate\Testing\TestResponse
     */
    protected function postGraphQL($query, $headers = []) {
        $defaultHeaders = [
            'X-API-KEY'    => env('API_KEY', $this->apiKey),
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];

        return $this->postJson($this->apiUrl, [
            'query' => $query,
        ], array_merge($defaultHeaders, $headers));
    }

    /**
     * Set up the test environment
     */
    protected function setUp(): void {
        parent::setUp();
        // Set API_KEY for testing if not set
        if (!env('API_KEY')) {
            putenv('API_KEY=' . $this->apiKey);
        }
        // Avoid running global cache-clear commands (e.g. config:clear/cache:clear) during tests because
        // it can interfere with RefreshDatabase & migrations state. We only need to clear Lighthouse schema cache.
        $schemaCachePath = base_path('bootstrap/cache/lighthouse-schema.php');
        if (file_exists($schemaCachePath)) {
            @unlink($schemaCachePath);
        }
        // Set locale to English for consistent error messages in tests
        app()->setLocale('en');
    }
}
