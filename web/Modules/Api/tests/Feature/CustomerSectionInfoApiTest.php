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
use Modules\Crm\Models\CrmSection;

/**
 * Feature tests for customer_section_info GraphQL API (P0101).
 */
class CustomerSectionInfoApiTest extends BaseApiTest {
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
     * Build GraphQL query string for customer_section_info.
     *
     * @param  int  $sectionId
     * @param  string  $version
     * @param  string  $platform
     * @return string
     */
    protected function getQuery(int $sectionId, string $version = '1.0', string $platform = 'android'): string {
        return sprintf(
            'query {
                customer_section_info(
                    section_id: %d,
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
                        postal_code
                        ward
                        building
                        address_line
                        latitude
                        longitude
                        rating_value
                        description
                        google_place_id
                        google_map_url
                        phone
                        email
                        website
                        check_in_time
                        check_out_time
                        company {
                            id
                            code
                            name
                            phone
                            email
                            open_date
                            address
                            director
                            status
                        }
                        images {
                            id
                            url
                            order
                            alt_text
                            title
                        }
                        room_types {
                            id
                            name
                            code
                            description
                            price
                            bed_count
                            max_guests
                            area
                            amenities
                            images {
                                id
                                url
                                order
                                alt_text
                                title
                            }
                        }
                    }
                }
            }',
            $sectionId,
            addslashes($version),
            addslashes($platform)
        );
    }

    /**
     * Test successful customer_section_info query.
     *
     * @return void
     */
    public function test_customer_section_info_success(): void {
        // Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        $section = CrmSection::query()->active()->first();
        $this->assertNotNull($section, 'CRM sections should be seeded');

        $response = $this->postGraphQL($this->getQuery($section->id));

        $response->assertStatus(200);

        $responseData = $response->json('data.customer_section_info');
        $this->assertIsArray($responseData);
        $this->assertEquals(1, $responseData['status']);
        $this->assertEquals('Section retrieved successfully', $responseData['message']);

        $detail = $responseData['data'];
        $this->assertIsArray($detail);
        $this->assertEquals($section->id, $detail['id']);
        $this->assertEquals($section->name, $detail['name']);
        $this->assertEquals($section->code, $detail['code']);

        // Company info should be present
        $this->assertIsArray($detail['company']);
        $this->assertArrayHasKey('id', $detail['company']);
        $this->assertArrayHasKey('code', $detail['company']);
        $this->assertArrayHasKey('name', $detail['company']);

        // Room types should be an array (can be empty but seeded data should have entries)
        $this->assertIsArray($detail['room_types']);
    }

    /**
     * Test customer_section_info with non-existing section id.
     *
     * @return void
     */
    public function test_customer_section_info_not_found(): void {
        // Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        $nonExistingId = 999999;

        $response = $this->postGraphQL($this->getQuery($nonExistingId));

        $response->assertStatus(200);

        $this->assertEquals(
            0,
            $response->json('data.customer_section_info.status')
        );
        $this->assertEquals(
            'Section not found',
            $response->json('data.customer_section_info.message')
        );
        $this->assertNull($response->json('data.customer_section_info.data'));
    }

    /**
     * Test customer_section_info validation error when platform is invalid.
     *
     * @return void
     */
    public function test_customer_section_info_invalid_platform(): void {
        // Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        $section = CrmSection::query()->active()->first();
        $this->assertNotNull($section, 'CRM sections should be seeded');

        $response = $this->postGraphQL($this->getQuery($section->id, platform: 'invalid_platform'));

        $response->assertStatus(200);

        $this->assertEquals(
            0,
            $response->json('data.customer_section_info.status')
        );

        $message = $response->json('data.customer_section_info.message');
        $this->assertIsString($message);
        $this->assertStringStartsWith('Invalid input data:', $message);
    }
}
