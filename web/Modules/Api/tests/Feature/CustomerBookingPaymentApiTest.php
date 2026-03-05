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
use Modules\Crm\Models\CrmBookingDetail;
use Modules\Crm\Models\CrmCustomer;
use Modules\Crm\Models\CrmPayment;

/**
 * Feature tests for customer_booking_payment GraphQL API (P0104).
 */
class CustomerBookingPaymentApiTest extends BaseApiTest {
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
     * Build GraphQL mutation string for customer_booking_payment.
     *
     * @param  int  $bookingId
     * @param  string  $version
     * @param  string  $platform
     * @return string
     */
    protected function getMutation(int $bookingId, string $version = '1.0', string $platform = 'android'): string {
        return sprintf(
            'mutation {
                customer_booking_payment(
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
     * Test successful customer_booking_payment mutation.
     *
     * @return void
     */
    public function test_customer_booking_payment_success(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        $customer = CrmCustomer::where('user_id', $user->id)->first();
        $this->assertNotNull($customer, 'CRM customer should be seeded for the user');

        $booking = CrmBooking::create([
            'customer_id'    => $customer->id,
            'code'           => 'BKP01',
            'section_id'     => 1,
            'guest_count'    => ['adults' => 2, 'children' => 0],
            'deposit_amount' => 0,
            'status'         => CrmBooking::STATUS_PENDING,
        ]);

        // create a booking detail so total_price accessor returns a non-zero value
        CrmBookingDetail::create([
            'booking_id'      => $booking->id,
            'room_type_id'    => 1,
            'room_count'      => 1,
            'guest_count'     => ['adults' => 2, 'children' => 0],
            'price_per_night' => 100,
            'total_price'     => 100,
            'status'          => 1,
        ]);

        $response = $this->postGraphQL($this->getMutation($booking->id));

        $response->assertStatus(200);

        $responseData = $response->json('data.customer_booking_payment');
        $this->assertIsArray($responseData);
        $this->assertEquals(1, $responseData['status']);
        $this->assertEquals('Payment confirmed successfully', $responseData['message']);

        $detail = $responseData['data'];
        $this->assertIsArray($detail);
        $this->assertEquals($booking->id, $detail['id']);
        $this->assertIsArray($detail['customer']);
        $this->assertEquals($customer->id, $detail['customer']['id']);
        $this->assertEquals($customer->user_id, $detail['customer']['user_id']);

        // Ensure booking status remains unchanged; payment confirmation does not auto-confirm booking
        $this->assertEquals(CrmBooking::STATUS_PENDING, $detail['status']);

        // Ensure payment record is created and returned in API response
        $this->assertArrayHasKey('payments', $detail);
        $this->assertIsArray($detail['payments']);
        $this->assertCount(1, $detail['payments']);

        $payment = $detail['payments'][0];
        $this->assertEquals(100.0, (float) $payment['total']);
        $this->assertEquals(0.0, (float) $payment['discount']);
        $this->assertEquals(0.0, (float) $payment['tax']);
        $this->assertEquals(100.0, (float) $payment['final']);
        $this->assertEquals(CrmPayment::STATUS_ACTIVE, $payment['status']);
    }

    /**
     * Test customer_booking_payment with non-existing booking id.
     *
     * @return void
     */
    public function test_customer_booking_payment_not_found(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        $nonExistingId = 999999;

        $response = $this->postGraphQL($this->getMutation($nonExistingId));

        $response->assertStatus(200);

        $this->assertEquals(
            0,
            $response->json('data.customer_booking_payment.status')
        );
        $this->assertEquals(
            'Booking not found',
            $response->json('data.customer_booking_payment.message')
        );
        $this->assertNull($response->json('data.customer_booking_payment.data'));
    }

    /**
     * Test customer_booking_payment for a booking not belonging to the authenticated user.
     *
     * @return void
     */
    public function test_customer_booking_payment_access_denied(): void {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $customer = CrmCustomer::create([
            'user_id'    => $otherUser->id,
            'first_name' => 'Other',
            'last_name'  => 'User',
            'code'       => 'OTR01',
            'address'    => 'Test',
            'birthday'   => '1990-01-01',
            'status'     => CrmCustomer::STATUS_ACTIVE,
        ]);

        $booking = CrmBooking::create([
            'customer_id' => $customer->id,
            'code'        => 'BKDEN',
            'section_id'  => 1,
            'status'      => CrmBooking::STATUS_PENDING,
        ]);

        $response = $this->postGraphQL($this->getMutation($booking->id));

        $response->assertStatus(200);

        $this->assertEquals(
            0,
            $response->json('data.customer_booking_payment.status')
        );
        $this->assertEquals(
            'Access denied',
            $response->json('data.customer_booking_payment.message')
        );
        $this->assertNull($response->json('data.customer_booking_payment.data'));
    }

    /**
     * Test customer_booking_payment validation error when platform is invalid.
     *
     * @return void
     */
    public function test_customer_booking_payment_invalid_platform(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        $customer = CrmCustomer::where('user_id', $user->id)->first();
        $this->assertNotNull($customer, 'CRM customer should be seeded for the user');
        $booking = CrmBooking::query()->where('customer_id', $customer->id)->first();
        $this->assertNotNull($booking, 'CRM bookings should be seeded for the user');

        $response = $this->postGraphQL($this->getMutation($booking->id, platform: 'invalid_platform'));

        $response->assertStatus(200);

        $this->assertEquals(
            0,
            $response->json('data.customer_booking_payment.status')
        );

        $message = $response->json('data.customer_booking_payment.message');
        $this->assertIsString($message);
        $this->assertStringStartsWith('Invalid input data:', $message);
    }

    /**
     * Test customer_booking_payment invalid booking status.
     *
     * @return void
     */
    public function test_customer_booking_payment_invalid_status(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        $customer = CrmCustomer::where('user_id', $user->id)->first();
        $this->assertNotNull($customer, 'CRM customer should be seeded for the user');

        $booking = CrmBooking::create([
            'customer_id' => $customer->id,
            'code'        => 'BKCNL',
            'section_id'  => 1,
            'status'      => CrmBooking::STATUS_CANCELLED,
        ]);

        $response = $this->postGraphQL($this->getMutation($booking->id));

        $response->assertStatus(200);

        $this->assertEquals(
            0,
            $response->json('data.customer_booking_payment.status')
        );
        $this->assertEquals(
            'Booking cannot be paid in the current status',
            $response->json('data.customer_booking_payment.message')
        );
        $this->assertNull($response->json('data.customer_booking_payment.data'));
    }
}
