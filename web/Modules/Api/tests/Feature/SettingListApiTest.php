<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\SettingSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;

class SettingListApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(SettingSeeder::class);
    }

    /**
     * Get GraphQL query for setting list.
     *
     * @param  int|null  $page
     * @param  int|null  $limit
     * @param  string|null  $filter
     * @param  string|null  $sortBy
     * @param  string|null  $order
     * @param  int|null  $userFlag
     * @return string
     */
    public function getQuery($page = 1, $limit = 10, $filter = null, $sortBy = 'updated_at', $order = 'desc', $userFlag = null): string {
        $filterValue   = $filter !== null ? '"' . addslashes($filter) . '"' : 'null';
        $userFlagValue = $userFlag !== null ? (string) $userFlag : 'null';

        return sprintf('query {
            setting_list(page: %s, limit: %s, filter: %s, sort_by: "%s", order: "%s", user_flag: %s, version: "1.0", platform: "web") {
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
                paginatorInfo {
                    total
                    currentPage
                    lastPage
                    perPage
                }
            }
        }', $page !== null ? (string) $page : 'null', $limit !== null ? (string) $limit : 'null', $filterValue, $sortBy, $order, $userFlagValue);
    }

    /**
     * Get valid JSON structure for response.
     *
     * @return array
     */
    public function getValidJsonStructure(): array {
        return [
            'data' => [
                'setting_list' => [
                    'status',
                    'message',
                    'data'          => [
                        '*' => [
                            'id',
                            'key',
                            'value',
                            'description',
                            'user_flag',
                            'status',
                            'updated_at',
                        ],
                    ],
                    'paginatorInfo' => [
                        'total',
                        'currentPage',
                        'lastPage',
                        'perPage',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test getting setting list successfully.
     */
    public function test_setting_list_success(): void {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make GraphQL request
        $response = $this->postGraphQL($this->getQuery());

        // Assert: Check response structure and status
        $response->assertStatus(200);
        $response->assertJsonStructure($this->getValidJsonStructure());
        $response->assertJson([
            'data' => [
                'setting_list' => [
                    'status' => 1,
                ],
            ],
        ]);

        // Assert: Check pagination info exists
        $responseData = $response->json('data.setting_list');
        $this->assertNotNull($responseData['paginatorInfo']);
        $this->assertGreaterThan(0, $responseData['paginatorInfo']['total']);
    }

    /**
     * Test getting setting list with filter.
     */
    public function test_setting_list_with_filter(): void {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make GraphQL request with filter
        $response = $this->postGraphQL($this->getQuery(1, 10, 'language'));

        // Assert: Check response
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'setting_list' => [
                    'status' => 1,
                ],
            ],
        ]);

        // Assert: Check that filtered results contain the filter term
        $settings = $response->json('data.setting_list.data');
        if (!empty($settings)) {
            $hasFilterMatch = false;
            foreach ($settings as $setting) {
                if (
                    str_contains(strtolower($setting['key']), 'language') ||
                    (isset($setting['description']) && str_contains(strtolower($setting['description']), 'language'))
                ) {
                    $hasFilterMatch = true;
                    break;
                }
            }
            $this->assertTrue($hasFilterMatch, 'Filter should match at least one setting');
        }
    }

    /**
     * Test getting setting list with user_flag filter.
     */
    public function test_setting_list_with_user_flag(): void {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make GraphQL request with user_flag=1
        $response = $this->postGraphQL($this->getQuery(1, 10, null, 'updated_at', 'desc', 1));

        // Assert: Check response
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'setting_list' => [
                    'status' => 1,
                ],
            ],
        ]);

        // Assert: Check that all returned settings have user_flag=1
        $settings = $response->json('data.setting_list.data');
        foreach ($settings as $setting) {
            $this->assertEquals(1, $setting['user_flag'], 'All settings should have user_flag=1');
        }
    }

    /**
     * Test getting setting list with pagination.
     */
    public function test_setting_list_with_pagination(): void {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make GraphQL request with page 1, limit 5
        $response1 = $this->postGraphQL($this->getQuery(1, 5));

        // Assert: Check first page
        $response1->assertStatus(200);
        $response1->assertJson([
            'data' => [
                'setting_list' => [
                    'status' => 1,
                ],
            ],
        ]);

        $data1 = $response1->json('data.setting_list');
        $this->assertEquals(1, $data1['paginatorInfo']['currentPage']);
        $this->assertEquals(5, $data1['paginatorInfo']['perPage']);
        $this->assertLessThanOrEqual(5, count($data1['data']));

        // Act: Make GraphQL request with page 2, limit 5
        $response2 = $this->postGraphQL($this->getQuery(2, 5));

        // Assert: Check second page
        $response2->assertStatus(200);
        $data2 = $response2->json('data.setting_list');
        $this->assertEquals(2, $data2['paginatorInfo']['currentPage']);

        // Assert: Different results on different pages
        if (count($data1['data']) > 0 && count($data2['data']) > 0) {
            $this->assertNotEquals($data1['data'][0]['id'], $data2['data'][0]['id'], 'Pages should have different results');
        }
    }

    /**
     * Test getting setting list without authentication.
     */
    public function test_setting_list_unauthorized(): void {
        // Act: Make GraphQL request without authentication
        $response = $this->postGraphQL($this->getQuery());

        // Assert: Check unauthorized response
        $response->assertStatus(200);
        $response->assertJson([
            'errors' => [
                [
                    'message' => 'Unauthenticated.',
                ],
            ],
            'data'   => [
                'setting_list' => null,
            ],
        ]);
    }

    /**
     * Test getting setting list with invalid page number.
     */
    public function test_setting_list_invalid_page(): void {
        // Arrange: Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Act: Make GraphQL request with invalid page
        $response = $this->postGraphQL($this->getQuery(0, 10));

        // Assert: Check error response
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'setting_list' => [
                    'status' => 0,
                ],
            ],
        ]);
    }
}
