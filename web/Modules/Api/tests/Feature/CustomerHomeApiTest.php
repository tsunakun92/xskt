<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\OneMany;
use Modules\Admin\Models\Role;
use Modules\Admin\Models\User;
use Modules\Crm\Database\Seeders\CrmRoomSeeder;
use Modules\Crm\Database\Seeders\CrmRoomTypeFileSeeder;
use Modules\Crm\Database\Seeders\CrmRoomTypeSeeder;
use Modules\Crm\Database\Seeders\CrmSectionFileSeeder;
use Modules\Crm\Database\Seeders\CrmSectionSeeder;
use Modules\Crm\Models\CrmSection;

/**
 * Feature tests for customer_home GraphQL API (P0100).
 */
class CustomerHomeApiTest extends BaseApiTest {
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
     * Build GraphQL query string for customer_home.
     *
     * @param  bool  $includeUserData
     * @param  string  $version
     * @param  string  $platform
     * @return string
     */
    protected function getQuery(bool $includeUserData = false, string $version = '1.0', string $platform = 'android'): string {
        $userDataFragment = $includeUserData ? '
                        user_data {
                            user {
                                id
                                username
                                email
                                name
                                section_id
                                list_roles {
                                    id
                                    name
                                }
                                list_sections {
                                    id
                                    name
                                }
                            }
                        }' : '';

        return sprintf(
            'query {
                customer_home(
                    version: "%s",
                    platform: "%s"
                ) {
                    status
                    message
                    data {
                        sections {
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
                        }%s
                    }
                }
            }',
            addslashes($version),
            addslashes($platform),
            $userDataFragment
        );
    }

    /**
     * Test successful customer_home query.
     *
     * @return void
     */
    public function test_customer_home_success(): void {
        // Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        $response = $this->postGraphQL($this->getQuery());

        $response->assertStatus(200);

        $responseData = $response->json('data.customer_home');
        $this->assertIsArray($responseData);
        $this->assertEquals(1, $responseData['status']);
        $this->assertEquals('Home data retrieved successfully', $responseData['message']);

        $data = $responseData['data'];
        $this->assertIsArray($data);
        $this->assertArrayHasKey('sections', $data);
        $this->assertIsArray($data['sections']);

        // Should return up to 10 random active sections
        $this->assertLessThanOrEqual(10, count($data['sections']));
    }

    /**
     * Test customer_home returns "No sections found" when no active sections exist.
     *
     * @return void
     */
    public function test_customer_home_no_sections_found(): void {
        // Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        CrmSection::query()->update(['status' => CrmSection::STATUS_INACTIVE]);

        $response = $this->postGraphQL($this->getQuery());

        $response->assertStatus(200);

        $this->assertEquals(
            0,
            $response->json('data.customer_home.status')
        );
        $this->assertEquals(
            'No sections found',
            $response->json('data.customer_home.message')
        );

        $this->assertSame(
            [],
            $response->json('data.customer_home.data.sections')
        );
    }

    /**
     * Test customer_home validation error when platform is invalid.
     *
     * @return void
     */
    public function test_customer_home_invalid_platform(): void {
        // Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        $response = $this->postGraphQL($this->getQuery(includeUserData: false, platform: 'invalid_platform'));

        $response->assertStatus(200);

        $this->assertEquals(
            0,
            $response->json('data.customer_home.status')
        );

        $message = $response->json('data.customer_home.message');
        $this->assertIsString($message);
        $this->assertStringStartsWith('Invalid input data:', $message);
    }

    /**
     * Test customer_home includes user_data with section_id, list_roles, and list_sections.
     *
     * @return void
     */
    public function test_customer_home_user_data_fields(): void {
        // Authenticate a user
        $user = User::first();
        Sanctum::actingAs($user);

        // Get roles and sections for testing
        $roles    = Role::take(2)->get();
        $sections = CrmSection::active()->take(2)->get();

        // Assign roles to user via one_many
        if ($roles->isNotEmpty()) {
            foreach ($roles as $role) {
                OneMany::create([
                    'one_id'  => $user->id,
                    'many_id' => $role->id,
                    'type'    => OneMany::TYPE_USER_ROLE,
                    'status'  => 1,
                ]);
            }
        }

        // Assign sections to user via one_many
        if ($sections->isNotEmpty()) {
            foreach ($sections as $section) {
                OneMany::create([
                    'one_id'  => $user->id,
                    'many_id' => $section->id,
                    'type'    => OneMany::TYPE_USER_SECTION,
                    'status'  => 1,
                ]);
            }

            // Set user's current section
            $user->section_id = $sections->first()->id;
            $user->save();
        }

        $response = $this->postGraphQL($this->getQuery(includeUserData: true));

        $response->assertStatus(200);

        $responseData = $response->json('data.customer_home');
        $this->assertIsArray($responseData);
        $this->assertEquals(1, $responseData['status']);

        // Check user_data exists
        $this->assertArrayHasKey('user_data', $responseData['data']);
        $userData = $responseData['data']['user_data'];
        $this->assertIsArray($userData);
        $this->assertArrayHasKey('user', $userData);

        // Check user fields
        $userResponse = $userData['user'];
        $this->assertIsArray($userResponse);
        $this->assertArrayHasKey('section_id', $userResponse);
        $this->assertArrayHasKey('list_roles', $userResponse);
        $this->assertArrayHasKey('list_sections', $userResponse);

        // Verify section_id
        if ($sections->isNotEmpty()) {
            $this->assertEquals($sections->first()->id, $userResponse['section_id']);
        } else {
            $this->assertNull($userResponse['section_id']);
        }

        // Verify list_roles and list_sections structure (type only)
        $this->assertIsArray($userResponse['list_roles']);
        $this->assertIsArray($userResponse['list_sections']);
    }

    /**
     * Test customer_home user_data with empty roles and sections.
     *
     * @return void
     */
    public function test_customer_home_user_data_empty_assignments(): void {
        // Authenticate a user
        $user = User::first();
        $this->assertNotNull($user, 'User should exist');
        $this->assertNotNull($user->role_id, 'User should have a role_id');
        Sanctum::actingAs($user);

        // Ensure user has no assigned roles/sections (except main role)
        OneMany::where('one_id', $user->id)
            ->whereIn('type', [OneMany::TYPE_USER_ROLE, OneMany::TYPE_USER_SECTION])
            ->delete();

        // Clear section_id
        $user->section_id = null;
        $user->save();

        // Refresh user to ensure fresh data
        $user->refresh();

        $response = $this->postGraphQL($this->getQuery(includeUserData: true));

        $response->assertStatus(200);

        $responseData = $response->json('data.customer_home');
        $this->assertEquals(1, $responseData['status']);

        $userData     = $responseData['data']['user_data'];
        $userResponse = $userData['user'];

        // section_id should be null
        $this->assertNull($userResponse['section_id']);

        // list_roles and list_sections should at least be arrays
        $this->assertIsArray($userResponse['list_roles']);
        $this->assertIsArray($userResponse['list_sections']);
    }
}
