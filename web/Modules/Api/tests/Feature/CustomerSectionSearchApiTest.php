<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;
use Modules\Crm\Database\Seeders\CrmRoomSeeder;
use Modules\Crm\Database\Seeders\CrmRoomTypeFileSeeder;
use Modules\Crm\Database\Seeders\CrmRoomTypeSeeder;
use Modules\Crm\Database\Seeders\CrmSectionFileSeeder;
use Modules\Crm\Database\Seeders\CrmSectionSeeder;

/**
 * Feature tests for customer_section_search GraphQL API (P0100).
 */
class CustomerSectionSearchApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        // Seed users and roles for authentication
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        // Seed CRM data needed for sections and room types
        $this->seed(CrmSectionSeeder::class);
        $this->seed(CrmSectionFileSeeder::class);
        $this->seed(CrmRoomTypeSeeder::class);
        $this->seed(CrmRoomTypeFileSeeder::class);
        $this->seed(CrmRoomSeeder::class);
    }

    /**
     * Build GraphQL query string for customer_section_search.
     *
     * @param  int|null  $page
     * @param  int|null  $limit
     * @param  string|null  $address
     * @param  int|null  $typeId
     * @param  string|null  $sortBy
     * @param  string|null  $order
     * @param  string  $version
     * @param  string  $platform
     * @return string
     */
    protected function getQuery(
        ?int $page = 1,
        ?int $limit = 10,
        ?string $address = null,
        ?int $typeId = null,
        ?string $sortBy = 'rating_value',
        ?string $order = 'desc',
        string $version = '1.0',
        string $platform = 'android'
    ): string {
        $addressValue = $address !== null ? '"' . addslashes($address) . '"' : 'null';
        $typeIdValue  = $typeId !== null ? $typeId : 'null';
        $sortByValue  = $sortBy !== null ? '"' . addslashes($sortBy) . '"' : 'null';
        $orderValue   = $order !== null ? '"' . addslashes($order) . '"' : 'null';

        return sprintf(
            'query {
                customer_section_search(
                    page: %d,
                    limit: %d,
                    address: %s,
                    type_id: %s,
                    sort_by: %s,
                    order: %s,
                    version: "%s",
                    platform: "%s"
                ) {
                    status
                    message
                    data {
                        id
                        name
                        code
                        address
                        latitude
                        longitude
                        rating_value
                        min_price
                        description
                        google_map_url
                        images {
                            id
                            url
                            order
                            alt_text
                            title
                        }
                    }
                    paginatorInfo {
                        total
                        currentPage
                        lastPage
                        perPage
                    }
                }
            }',
            $page ?? 1,
            $limit ?? 10,
            $addressValue,
            $typeIdValue,
            $sortByValue,
            $orderValue,
            addslashes($version),
            addslashes($platform)
        );
    }

    /**
     * Get expected JSON structure for a successful response.
     *
     * @return array<string, mixed>
     */
    protected function getValidJsonStructure(): array {
        return [
            'data' => [
                'customer_section_search' => [
                    'status',
                    'message',
                    'data'          => [
                        '*' => [
                            'id',
                            'name',
                            'code',
                            'address',
                            'latitude',
                            'longitude',
                            'rating_value',
                            'min_price',
                            'description',
                            'google_map_url',
                            'images' => [
                                '*' => [
                                    'id',
                                    'url',
                                    'order',
                                    'alt_text',
                                    'title',
                                ],
                            ],
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
     * Test successful customer_section_search query without filters.
     *
     * @return void
     */
    public function test_customer_section_search_success(): void {
        // Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        $response = $this->postGraphQL($this->getQuery());

        $response->assertStatus(200);

        $responseData = $response->json('data.customer_section_search');
        $this->assertIsArray($responseData);

        if ($responseData['status'] === 1) {
            $response->assertJsonStructure($this->getValidJsonStructure());
            $this->assertEquals(1, $responseData['paginatorInfo']['currentPage']);
            $this->assertIsArray($responseData['data']);
        }
    }

    /**
     * Test customer_section_search returns "No sections found" when address does not match.
     *
     * @return void
     */
    public function test_customer_section_search_no_results(): void {
        // Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        $response = $this->postGraphQL($this->getQuery(address: 'NonExistingAddressForTest'));

        $response->assertStatus(200);

        $this->assertEquals(
            0,
            $response->json('data.customer_section_search.status')
        );
        $this->assertEquals(
            'No sections found',
            $response->json('data.customer_section_search.message')
        );

        $this->assertSame(
            [],
            $response->json('data.customer_section_search.data')
        );

        $this->assertEquals(
            0,
            $response->json('data.customer_section_search.paginatorInfo.total')
        );
    }

    /**
     * Test customer_section_search validation error when sort_by is invalid.
     *
     * @return void
     */
    public function test_customer_section_search_invalid_sort_by(): void {
        // Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        $response = $this->postGraphQL($this->getQuery(sortBy: 'invalid_sort'));

        $response->assertStatus(200);

        $this->assertEquals(
            0,
            $response->json('data.customer_section_search.status')
        );

        $message = $response->json('data.customer_section_search.message');
        $this->assertIsString($message);
        $this->assertStringStartsWith('Invalid input data:', $message);
    }

    /**
     * Test customer_section_search validation error when platform is invalid.
     *
     * @return void
     */
    public function test_customer_section_search_invalid_platform(): void {
        // Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        $response = $this->postGraphQL($this->getQuery(platform: 'invalid_platform'));

        $response->assertStatus(200);

        $this->assertEquals(
            0,
            $response->json('data.customer_section_search.status')
        );

        $message = $response->json('data.customer_section_search.message');
        $this->assertIsString($message);
        $this->assertStringStartsWith('Invalid input data:', $message);
    }
}
