<?php

namespace Modules\Api\Tests\Feature;

use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\OneMany;
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
use Modules\Crm\Models\CrmPayment;

/**
 * Feature tests for customer_booking_cancel GraphQL API (P0105).
 */
class CustomerBookingCancelApiTest extends BaseApiTest {
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
     * Build GraphQL mutation string for customer_booking_cancel.
     *
     * @param  int  $bookingId
     * @param  string|null  $cancelReason
     * @param  string  $version
     * @param  string  $platform
     * @return string
     */
    protected function getMutation(
        int $bookingId,
        ?string $cancelReason = 'Change of travel plan',
        string $version = '1.0',
        string $platform = 'android'
    ): string {
        $cancelReasonString = $cancelReason === null
            ? 'cancel_reason: null,'
            : sprintf('cancel_reason: "%s",', addslashes($cancelReason));

        return sprintf(
            'mutation {
                customer_booking_cancel(
                    booking_id: %d,
                    %s
                    version: "%s",
                    platform: "%s"
                ) {
                    status
                    message
                    data {
                        id
                        code
                        status
                        cancel_reason
                        booking_details {
                            id
                            assigned_rooms {
                                id
                                name
                            }
                        }
                        payments {
                            id
                        }
                    }
                }
            }',
            $bookingId,
            $cancelReasonString,
            addslashes($version),
            addslashes($platform)
        );
    }

    /**
     * Test successful customer_booking_cancel mutation.
     *
     * @return void
     */
    public function test_customer_booking_cancel_success(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        $customer = CrmCustomer::where('user_id', $user->id)->first();
        $this->assertNotNull($customer, 'CRM customer should be seeded for the user');

        $booking = CrmBooking::query()->where('customer_id', $customer->id)->first();
        $this->assertNotNull($booking, 'CRM bookings should be seeded for the user');

        // Make sure booking is cancellable
        $booking->status = CrmBooking::STATUS_PENDING;
        $booking->save();

        // Ensure we have at least 1 assigned room mapping before cancel
        $detail = $booking->rBookingDetails()->first();
        $this->assertNotNull($detail, 'Seeded booking should have booking details');

        $assignedBefore = OneMany::query()
            ->where('one_id', $detail->id)
            ->where('type', OneMany::TYPE_CRM_BOOKING_DETAIL_ROOM)
            ->count();
        $this->assertGreaterThan(0, $assignedBefore, 'Seeded booking detail should have assigned rooms');

        // Ensure payments are not modified by cancel (create one if missing)
        $paymentsBefore = CrmPayment::query()->where('booking_id', $booking->id)->count();
        if ($paymentsBefore === 0) {
            CrmPayment::create([
                'booking_id'  => $booking->id,
                'customer_id' => $customer->id,
                'section_id'  => $booking->section_id,
                'total'       => 1000,
                'discount'    => 0,
                'tax'         => 100,
                'final'       => 1100,
                'method_id'   => CrmPayment::METHOD_CASH,
                'status'      => 1,
            ]);
            $paymentsBefore = 1;
        }

        $response = $this->postGraphQL($this->getMutation($booking->id, 'Change of travel plan'));

        $response->assertStatus(200);

        // Debug: Check if there are GraphQL errors
        $errors = $response->json('errors');
        if ($errors) {
            $this->fail('GraphQL errors: ' . json_encode($errors));
        }

        $payload = $response->json('data.customer_booking_cancel');
        $this->assertNotNull($payload, 'Response should have data.customer_booking_cancel');
        $this->assertEquals(1, $payload['status']);
        $this->assertEquals('Cancel request submitted successfully', $payload['message']);

        $data = $payload['data'];
        $this->assertEquals($booking->id, $data['id']);
        $this->assertEquals(CrmBooking::STATUS_CANCEL_REQUESTED, $data['status']);
        $this->assertEquals('Change of travel plan', $data['cancel_reason']);

        // Assigned rooms should remain as-is at the cancel requested stage
        $this->assertIsArray($data['booking_details']);
        $hasAssignedRooms = false;
        foreach ($data['booking_details'] as $bookingDetail) {
            $this->assertIsArray($bookingDetail['assigned_rooms']);
            if (count($bookingDetail['assigned_rooms']) > 0) {
                $hasAssignedRooms = true;
            }
        }
        $this->assertTrue($hasAssignedRooms, 'Assigned rooms should not be cleared at cancel request stage');

        $assignedAfter = OneMany::query()
            ->where('one_id', $detail->id)
            ->where('type', OneMany::TYPE_CRM_BOOKING_DETAIL_ROOM)
            ->count();
        $this->assertEquals($assignedBefore, $assignedAfter);

        // Payments should remain unchanged
        $paymentsAfter = CrmPayment::query()->where('booking_id', $booking->id)->count();
        $this->assertEquals($paymentsBefore, $paymentsAfter);
    }

    /**
     * Test customer_booking_cancel with non-existing booking id.
     *
     * @return void
     */
    public function test_customer_booking_cancel_not_found(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        $response = $this->postGraphQL($this->getMutation(999999));

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('data.customer_booking_cancel.status'));
        $this->assertEquals('Booking not found', $response->json('data.customer_booking_cancel.message'));
        $this->assertNull($response->json('data.customer_booking_cancel.data'));
    }

    /**
     * Test customer_booking_cancel invalid status.
     *
     * @return void
     */
    public function test_customer_booking_cancel_invalid_status(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        $customer = CrmCustomer::where('user_id', $user->id)->first();
        $this->assertNotNull($customer);

        $booking = CrmBooking::query()->where('customer_id', $customer->id)->first();
        $this->assertNotNull($booking);

        $booking->status = CrmBooking::STATUS_CANCELLED;
        $booking->save();

        $response = $this->postGraphQL($this->getMutation($booking->id));

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('data.customer_booking_cancel.status'));
        $this->assertEquals(
            'Booking cannot be cancelled in the current status',
            $response->json('data.customer_booking_cancel.message')
        );
        $this->assertNull($response->json('data.customer_booking_cancel.data'));
    }

    /**
     * Test customer_booking_cancel access denied when user has no customer.
     *
     * @return void
     */
    public function test_customer_booking_cancel_access_denied(): void {
        $userWithoutCustomer = User::factory()->create();
        Sanctum::actingAs($userWithoutCustomer);

        $bookingId = CrmBooking::query()->value('id');
        $this->assertNotNull($bookingId);

        $response = $this->postGraphQL($this->getMutation((int) $bookingId));

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('data.customer_booking_cancel.status'));
        $this->assertEquals('Access denied', $response->json('data.customer_booking_cancel.message'));
        $this->assertNull($response->json('data.customer_booking_cancel.data'));
    }

    /**
     * Test customer_booking_cancel validation error when platform is invalid.
     *
     * @return void
     */
    public function test_customer_booking_cancel_invalid_platform(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        $bookingId = CrmBooking::query()->value('id');
        $this->assertNotNull($bookingId);

        $response = $this->postGraphQL($this->getMutation((int) $bookingId, platform: 'invalid_platform'));

        $response->assertStatus(200);

        $this->assertEquals(0, $response->json('data.customer_booking_cancel.status'));
        $message = $response->json('data.customer_booking_cancel.message');
        $this->assertIsString($message);
        $this->assertStringStartsWith('Invalid input data:', $message);
    }

    /**
     * Test rate limiting for customer_booking_cancel.
     *
     * @return void
     */
    public function test_customer_booking_cancel_rate_limit(): void {
        config()->set('api.rate_limit.customer_booking_cancel.max_attempts', 1);
        config()->set('api.rate_limit.customer_booking_cancel.decay_minutes', 1);

        $user = User::first();
        Sanctum::actingAs($user);

        $bookingId = CrmBooking::query()->value('id');
        $this->assertNotNull($bookingId);

        RateLimiter::clear('customer_booking_cancel:127.0.0.1');

        $first = $this->postGraphQL($this->getMutation((int) $bookingId));
        $first->assertStatus(200);

        $second = $this->postGraphQL($this->getMutation((int) $bookingId));
        $second->assertStatus(200);

        $this->assertEquals(0, $second->json('data.customer_booking_cancel.status'));
        $this->assertStringStartsWith(
            'Too many requests. Please try again in',
            (string) $second->json('data.customer_booking_cancel.message')
        );
    }
}
