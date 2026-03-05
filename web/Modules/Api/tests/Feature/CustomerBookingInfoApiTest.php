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
 * Feature tests for customer_booking_info GraphQL API (P0107).
 */
class CustomerBookingInfoApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        // Seed users and roles for authentication
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        // Seed CRM data needed for bookings
        $this->seed(CrmSectionSeeder::class);
        $this->seed(CrmSectionFileSeeder::class);
        $this->seed(CrmRoomTypeSeeder::class);
        $this->seed(CrmRoomTypeFileSeeder::class);
        $this->seed(CrmRoomSeeder::class);
        $this->seed(CrmCustomerSeeder::class);
        $this->seed(CrmBookingSeeder::class);
    }

    /**
     * Build GraphQL query string for customer_booking_info.
     *
     * @param  int  $bookingId
     * @param  string  $version
     * @param  string  $platform
     * @return string
     */
    protected function getQuery(int $bookingId, string $version = '1.0', string $platform = 'android'): string {
        return sprintf(
            'query {
                customer_booking_info(
                    booking_id: %d,
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
                        deposit_amount
                        special_requests
                        cancel_reason
                        customer {
                            id
                            user_id
                            name
                            first_name
                            last_name
                            phone_number
                            code
                            gender
                            gender_name
                            type
                            type_name
                            status
                        }
                        section {
                            name
                            address
                            latitude
                            longitude
                        }
                        booking_details {
                            id
                            room_type {
                                id
                                name
                                images {
                                    url
                                }
                            }
                            room_count
                            guest_count {
                                adults
                                children
                            }
                            price_per_night
                            total_price
                            notes
                            assigned_rooms {
                                id
                                name
                            }
                        }
                        payments {
                            id
                            method_name
                            total
                            tax
                            discount
                            final
                            status
                            created_at
                        }
                    }
                }
            }',
            $bookingId,
            addslashes($version),
            addslashes($platform)
        );
    }

    /**
     * Test successful customer_booking_info query.
     *
     * @return void
     */
    public function test_customer_booking_info_success(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        $customer = CrmCustomer::where('user_id', $user->id)->first();
        $this->assertNotNull($customer, 'CRM customer should be seeded for the user');
        $booking = CrmBooking::query()->where('customer_id', $customer->id)->first();
        $this->assertNotNull($booking, 'CRM bookings should be seeded for the user');

        $response = $this->postGraphQL($this->getQuery($booking->id));

        $response->assertStatus(200);

        $responseData = $response->json('data.customer_booking_info');
        $this->assertIsArray($responseData);
        $this->assertEquals(1, $responseData['status']);
        $this->assertEquals('Booking detailed information retrieved successfully', $responseData['message']);

        $detail = $responseData['data'];
        $this->assertIsArray($detail);
        $this->assertEquals($booking->id, $detail['id']);
        $this->assertEquals($booking->code, $detail['code']);
        $this->assertIsArray($detail['customer']);
        $this->assertEquals($customer->id, $detail['customer']['id']);
        $this->assertEquals($customer->user_id, $detail['customer']['user_id']);
        $this->assertIsArray($detail['section']);
        $this->assertIsArray($detail['booking_details']);
        $this->assertIsArray($detail['payments']);

        // Check nested relations
        $firstDetail = $detail['booking_details'][0];
        $this->assertIsArray($firstDetail['assigned_rooms']);
        $this->assertGreaterThan(0, count($firstDetail['assigned_rooms']));
        $this->assertIsArray($firstDetail['room_type']['images']);
    }

    /**
     * Test customer_booking_info with non-existing booking id.
     *
     * @return void
     */
    public function test_customer_booking_info_not_found(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        $nonExistingId = 999999;

        $response = $this->postGraphQL($this->getQuery($nonExistingId));

        $response->assertStatus(200);

        $this->assertEquals(
            0,
            $response->json('data.customer_booking_info.status')
        );
        $this->assertEquals(
            'Booking not found or access denied',
            $response->json('data.customer_booking_info.message')
        );
        $this->assertNull($response->json('data.customer_booking_info.data'));
    }

    /**
     * Test customer_booking_info for a booking not belonging to the authenticated user.
     *
     * @return void
     */
    public function test_customer_booking_info_access_denied(): void {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $customer = CrmCustomer::create([
            'user_id'    => $otherUser->id,
            'first_name' => 'Other',
            'last_name'  => 'User',
            'code'       => 'OTHR01-000001',
            'address'    => 'Test',
            'birthday'   => '1990-01-01',
            'status'     => CrmCustomer::STATUS_ACTIVE,
        ]);
        $booking = CrmBooking::create([
            'customer_id' => $customer->id,
            'code'        => 'BKDENY-000001',
            'section_id'  => 1,
            'status'      => CrmBooking::STATUS_ACTIVE,
        ]);

        $response = $this->postGraphQL($this->getQuery($booking->id));

        $response->assertStatus(200);

        $this->assertEquals(
            0,
            $response->json('data.customer_booking_info.status')
        );
        $this->assertEquals(
            'Booking not found or access denied',
            $response->json('data.customer_booking_info.message')
        );
    }

    /**
     * Test customer_booking_info validation error when platform is invalid.
     *
     * @return void
     */
    public function test_customer_booking_info_invalid_platform(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        $customer = CrmCustomer::where('user_id', $user->id)->first();
        $this->assertNotNull($customer, 'CRM customer should be seeded for the user');
        $booking = CrmBooking::query()->where('customer_id', $customer->id)->first();
        $this->assertNotNull($booking, 'CRM bookings should be seeded for the user');

        $response = $this->postGraphQL($this->getQuery($booking->id, platform: 'invalid_platform'));

        $response->assertStatus(200);

        $this->assertEquals(
            0,
            $response->json('data.customer_booking_info.status')
        );

        $message = $response->json('data.customer_booking_info.message');
        $this->assertIsString($message);
        $this->assertStringStartsWith('Invalid input data:', $message);
    }
}
