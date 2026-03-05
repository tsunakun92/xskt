<?php

namespace Modules\Api\Tests\Feature;

use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;
use Modules\Crm\Database\Seeders\CrmBookingSeeder;
use Modules\Crm\Database\Seeders\CrmCustomerSeeder;
use Modules\Crm\Database\Seeders\CrmRoomSeeder;
use Modules\Crm\Database\Seeders\CrmRoomTypeFileSeeder;
use Modules\Crm\Database\Seeders\CrmRoomTypeSeeder;
use Modules\Crm\Database\Seeders\CrmSectionFileSeeder;
use Modules\Crm\Database\Seeders\CrmSectionSeeder;
use Modules\Crm\Models\CrmBooking;
use Modules\Crm\Models\CrmCustomer;

/**
 * Feature tests for customer_booking_history GraphQL API (P0106).
 */
class CustomerBookingHistoryApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        // Seed necessary data for tests
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(CrmSectionSeeder::class);
        $this->seed(CrmSectionFileSeeder::class);
        $this->seed(CrmRoomTypeSeeder::class);
        $this->seed(CrmRoomTypeFileSeeder::class);
        $this->seed(CrmRoomSeeder::class);
        $this->seed(CrmCustomerSeeder::class);
        $this->seed(CrmBookingSeeder::class);
    }

    /**
     * Build GraphQL query string for customer_booking_history.
     *
     * @param  array  $args
     * @return string
     */
    protected function getQuery(array $args = []): string {
        $page      = $args['page'] ?? 1;
        $limit     = $args['limit'] ?? 10;
        $status    = $args['status'] ?? 'null';
        $date_from = isset($args['date_from']) ? '"' . $args['date_from'] . '"' : 'null';
        $date_to   = isset($args['date_to']) ? '"' . $args['date_to'] . '"' : 'null';
        $sort_by   = isset($args['sort_by']) ? '"' . $args['sort_by'] . '"' : 'null';
        $order     = isset($args['order']) ? '"' . $args['order'] . '"' : 'null';
        $version   = $args['version'] ?? '1.0';
        $platform  = $args['platform'] ?? 'android';

        return sprintf(
            'query {
                customer_booking_history(
                    page: %d,
                    limit: %d,
                    status: %s,
                    date_from: %s,
                    date_to: %s,
                    sort_by: %s,
                    order: %s,
                    version: "%s",
                    platform: "%s"
                ) {
                    status
                    message
                    data {
                        id
                        code
                        status
                        start
                        end
                        check_in_at
                        check_out_at
                        guest_count {
                            adults
                            children
                        }
                        total_price
                        section_name
                        room_details
                    }
                    paginatorInfo {
                        total
                        currentPage
                        lastPage
                        perPage
                    }
                }
            }',
            $page,
            $limit,
            $status,
            $date_from,
            $date_to,
            $sort_by,
            $order,
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
                'customer_booking_history' => [
                    'status',
                    'message',
                    'data'          => [
                        '*' => [
                            'id',
                            'code',
                            'status',
                            'start',
                            'end',
                            'check_in_at',
                            'check_out_at',
                            'guest_count' => [
                                'adults',
                                'children',
                            ],
                            'total_price',
                            'section_name',
                            'room_details',
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
     * Test successful customer_booking_history query.
     *
     * @return void
     */
    public function test_customer_booking_history_success(): void {
        $customer = CrmCustomer::whereHas('rBookings')->first();

        if (!$customer) {
            $this->markTestSkipped('No customer with bookings found. Check CrmBookingSeeder.');
        }

        $user = $customer->rUser;
        if (!$user) {
            $this->markTestSkipped('Customer has no associated user.');
        }

        Sanctum::actingAs($user);

        $response = $this->postGraphQL($this->getQuery());

        $response->assertStatus(200);
        $response->assertJsonStructure($this->getValidJsonStructure());

        $responseData = $response->json('data.customer_booking_history');
        $this->assertEquals(1, $responseData['status']);
        $this->assertIsArray($responseData['data']);
        $this->assertNotEmpty($responseData['data']);
    }

    /**
     * Test customer_booking_history with no results.
     *
     * @return void
     */
    public function test_customer_booking_history_no_results(): void {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postGraphQL($this->getQuery());

        $response->assertStatus(200);
        $responseData = $response->json('data.customer_booking_history');

        $this->assertEquals(0, $responseData['status']);
        $this->assertEquals('No bookings found', $responseData['message']);
        $this->assertSame([], $responseData['data']);
        $this->assertEquals(0, $responseData['paginatorInfo']['total']);
    }

    /**
     * Test successful customer_booking_history query filtered by status.
     *
     * @return void
     */
    public function test_customer_booking_history_filtered_by_status(): void {
        $customer = CrmCustomer::whereHas('rBookings')->first();
        if (!$customer) {
            $this->markTestSkipped('No customer with bookings found.');
        }

        $user = $customer->rUser;
        Sanctum::actingAs($user);

        // Filter by STATUS_CONFIRMED (1)
        $response = $this->postGraphQL($this->getQuery(['status' => CrmBooking::STATUS_CONFIRMED]));

        $response->assertStatus(200);
        $responseData = $response->json('data.customer_booking_history');

        if ($responseData['status'] === 1) {
            $this->assertIsArray($responseData['data']);
            foreach ($responseData['data'] as $booking) {
                $this->assertEquals(CrmBooking::STATUS_CONFIRMED, $booking['status']);
            }
        }
    }

    /**
     * Test sorting by check_in_date.
     *
     * @return void
     */
    public function test_customer_booking_history_sorting(): void {
        $customer = CrmCustomer::whereHas('rBookings')->first();
        if (!$customer) {
            $this->markTestSkipped('No customer with bookings found.');
        }

        $user = $customer->rUser;
        Sanctum::actingAs($user);

        // Sort by start
        $response = $this->postGraphQL($this->getQuery([
            'sort_by' => 'start',
            'order'   => 'asc',
        ]));

        $response->assertStatus(200);
        $responseData = $response->json('data.customer_booking_history');

        if ($responseData['status'] === 1) {
            $this->assertIsArray($responseData['data']);
            $dates       = array_column($responseData['data'], 'start');
            $sortedDates = $dates;
            sort($sortedDates);
            $this->assertEquals($sortedDates, $dates);
        }
    }
}
