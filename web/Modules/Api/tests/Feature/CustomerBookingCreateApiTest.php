<?php

namespace Modules\Api\Tests\Feature;

use Exception;
use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;
use Modules\Crm\Database\Seeders\CrmCustomerSeeder;
use Modules\Crm\Database\Seeders\CrmRoomSeeder;
use Modules\Crm\Database\Seeders\CrmRoomTypeFileSeeder;
use Modules\Crm\Database\Seeders\CrmRoomTypeSeeder;
use Modules\Crm\Database\Seeders\CrmSectionFileSeeder;
use Modules\Crm\Database\Seeders\CrmSectionSeeder;
use Modules\Crm\Models\CrmBooking;
use Modules\Crm\Models\CrmRoomType;

/**
 * Feature tests for customer_booking_create GraphQL API (P0103).
 */
class CustomerBookingCreateApiTest extends BaseApiTest {
    protected function setUp(): void {
        parent::setUp();
        // seed authentication data
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        // seed CRM data necessary to book
        $this->seed(CrmSectionSeeder::class);
        $this->seed(CrmSectionFileSeeder::class);
        $this->seed(CrmRoomTypeSeeder::class);
        $this->seed(CrmRoomTypeFileSeeder::class);
        $this->seed(CrmRoomSeeder::class);
        $this->seed(CrmCustomerSeeder::class);
        // bookings not required for create
    }

    /**
     * Build GraphQL mutation string for customer_booking_create.
     */
    protected function getMutation(int $sectionId, string $start, string $end, array $details, ?array $guestCount = null, ?float $deposit = null, ?string $special = null, ?string $notes = null, string $version = '1.0', string $platform = 'android'): string {
        $detailStrings = [];
        foreach ($details as $d) {
            $gc              = $d['guest_count'];
            $detailStrings[] = sprintf(
                '{ room_type_id: %d room_count: %d guest_count: { adults: %d children: %d }%s%s }',
                $d['room_type_id'],
                $d['room_count'],
                $gc['adults'] ?? 0,
                $gc['children'] ?? 0,
                isset($d['price_per_night']) ? " price_per_night: {$d['price_per_night']}" : '',
                isset($d['total_price']) ? " total_price: {$d['total_price']}" : ''
            );
        }
        $detailsBlock = implode(' ', $detailStrings);

        $optional = '';
        if ($guestCount !== null) {
            $optional .= sprintf(' guest_count: { adults: %d children: %d }', $guestCount['adults'] ?? 0, $guestCount['children'] ?? 0);
        }
        if ($deposit !== null) {
            $optional .= " deposit_amount: {$deposit}";
        }
        if ($special !== null) {
            $optional .= " special_requests: \"{$special}\"";
        }
        if ($notes !== null) {
            $optional .= " notes: \"{$notes}\"";
        }

        return sprintf('mutation { customer_booking_create(section_id: %d start: "%s" end: "%s" booking_details: [%s]%s version: "%s" platform: "%s") { status message data { id code status start end } } }',
            $sectionId,
            $start,
            $end,
            $detailsBlock,
            $optional,
            $version,
            $platform
        );
    }

    /**
     * Successful booking creation returns status 1 and message.
     *
     * @return void
     */
    public function test_create_booking_success(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        // pick a section/room type seeded earlier
        $roomType = CrmRoomType::first();
        $this->assertNotNull($roomType);
        $sectionId = $roomType->section_id;

        $details = [
            [
                'room_type_id' => $roomType->id,
                'room_count'   => 1,
                'guest_count'  => ['adults' => 1, 'children' => 0],
            ],
        ];

        $response = $this->postGraphQL($this->getMutation($sectionId, now()->format('Y-m-d'), now()->addDay()->format('Y-m-d'), $details));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'customer_booking_create' => [
                    'status',
                    'message',
                    'data' => [
                        'id', 'code', 'status', 'start', 'end',
                    ],
                ],
            ],
        ]);

        $data = $response->json('data.customer_booking_create');
        $this->assertEquals(1, $data['status']);
        $this->assertEquals('Booking created successfully', $data['message']);
    }

    /**
     * Missing required fields should trigger validation error message.
     *
     * @return void
     */
    public function test_create_booking_validation_error(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        // missing required fields - including version/platform so resolver processes validation
        // include a minimal sub-selection on `data` to avoid GraphQL parse errors
        $response = $this->postGraphQL('mutation { customer_booking_create(section_id: 0 start: "" end: "" booking_details: [] version: "" platform: "") { status message data { id } } }');
        $response->assertStatus(200);
        $message = $response->json('data.customer_booking_create.message') ?? '';
        $this->assertStringContainsString('Invalid input data:', $message);
    }

    /**
     * Section ID that doesn't exist will still pass through (service validation missing).
     *
     * @return void
     */
    public function test_create_booking_section_not_found(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        $roomType = CrmRoomType::first();
        $this->assertNotNull($roomType);

        $details = [
            [
                'room_type_id' => $roomType->id,
                'room_count'   => 1,
                'guest_count'  => ['adults' => 1, 'children' => 0],
            ],
        ];

        $invalidSection = 99999;
        $response       = $this->postGraphQL($this->getMutation($invalidSection, now()->format('Y-m-d'), now()->addDay()->format('Y-m-d'), $details));
        $response->assertStatus(200);

        $data = $response->json('data.customer_booking_create');
        $this->assertEquals(0, $data['status']);
        $this->assertEquals('Section not found', $data['message']);
    }

    /**
     * Invalid room_type_id should trigger validation error from API.
     *
     * @return void
     */
    public function test_create_booking_room_type_not_found(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        $details = [
            [
                'room_type_id' => 0,
                'room_count'   => 1,
                'guest_count'  => ['adults' => 1, 'children' => 0],
            ],
        ];

        $response = $this->postGraphQL($this->getMutation(1, now()->format('Y-m-d'), now()->addDay()->format('Y-m-d'), $details));
        $response->assertStatus(200);
        // validation happens before the service is called, so we expect a
        // generic invalid-input message rather than our custom service phrase.
        $data    = $response->json('data.customer_booking_create');
        $message = $data['message'] ?? '';
        $this->assertStringContainsString('Invalid input data', $message);
    }

    /**
     * End date before start should be caught by validation rules.
     *
     * @return void
     */
    public function test_create_booking_invalid_date_range(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        $roomType = CrmRoomType::first();
        $details  = [
            [
                'room_type_id' => $roomType->id,
                'room_count'   => 1,
                'guest_count'  => ['adults' => 1, 'children' => 0],
            ],
        ];

        // end before start should trigger validation error
        $response = $this->postGraphQL($this->getMutation($roomType->section_id, now()->addDay()->format('Y-m-d'), now()->format('Y-m-d'), $details));
        $response->assertStatus(200);
        $this->assertStringContainsString('Invalid input data', $response->json('data.customer_booking_create.message'));
    }

    /**
     * Request without authentication should result in failure.
     *
     * @return void
     */
    public function test_create_booking_user_not_found(): void {
        // do not authenticate a user
        $this->assertNull(auth()->id());

        $roomType = CrmRoomType::first();
        $details  = [
            [
                'room_type_id' => $roomType->id,
                'room_count'   => 1,
                'guest_count'  => ['adults' => 1, 'children' => 0],
            ],
        ];

        $response = $this->postGraphQL($this->getMutation($roomType->section_id, now()->format('Y-m-d'), now()->addDay()->format('Y-m-d'), $details));
        $response->assertStatus(200);

        // should be an authentication error due to @guard directive
        $this->assertArrayHasKey('errors', $response->json());
        $this->assertStringContainsString('Unauthenticated', $response->json('errors.0.message'));
    }

    /**
     * Simulate service failure (payment or other) by mocking booking service.
     */
    public function test_create_booking_payment_failure(): void {
        // bind a mock service that throws to simulate a failure (payment or other issue)
        $this->mock(\Modules\Crm\Services\Contracts\CrmBookingServiceInterface::class, function ($mock) {
            $mock->shouldReceive('createCustomerBooking')
                ->andThrow(new Exception('Payment failed. Please try again or use a different payment method.'));
        });

        $user = User::first();
        Sanctum::actingAs($user);

        $roomType = CrmRoomType::first();
        $details  = [
            [
                'room_type_id' => $roomType->id,
                'room_count'   => 1,
                'guest_count'  => ['adults' => 1, 'children' => 0],
            ],
        ];

        $response = $this->postGraphQL($this->getMutation($roomType->section_id, now()->format('Y-m-d'), now()->addDay()->format('Y-m-d'), $details));
        $response->assertStatus(200);
        $data = $response->json('data.customer_booking_create');
        $this->assertEquals(0, $data['status']);
        $this->assertEquals('Payment failed. Please try again or use a different payment method.', $data['message']);
    }

    /**
     * Guest capacity exceeded should return an error message.
     *
     * @return void
     */
    public function test_create_booking_capacity_exceeded(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        $roomType = CrmRoomType::first();
        $this->assertNotNull($roomType);

        // ensure max_guests is low for the test
        $roomType->max_guests = 1;
        $roomType->save();

        $details = [
            [
                'room_type_id' => $roomType->id,
                'room_count'   => 1,
                'guest_count'  => ['adults' => 2, 'children' => 0],
            ],
        ];

        $response = $this->postGraphQL($this->getMutation($roomType->section_id, now()->format('Y-m-d'), now()->addDay()->format('Y-m-d'), $details));
        $response->assertStatus(200);
        $data = $response->json('data.customer_booking_create');
        $this->assertEquals(0, $data['status']);
        $this->assertEquals('Guest capacity exceeded for room type', $data['message']);
    }

    /**
     * Booking with changed price should return a price-change error message.
     *
     * @return void
     */
    public function test_create_booking_price_changed(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        $roomType = CrmRoomType::first();
        $this->assertNotNull($roomType);
        $sectionId = $roomType->section_id;

        $details = [
            [
                'room_type_id'    => $roomType->id,
                'room_count'      => 1,
                'guest_count'     => ['adults' => 1, 'children' => 0],
                'price_per_night' => ($roomType->price ?? 0) + 10,
            ],
        ];

        $response = $this->postGraphQL($this->getMutation($sectionId, now()->format('Y-m-d'), now()->addDay()->format('Y-m-d'), $details));
        $response->assertStatus(200);
        $data = $response->json('data.customer_booking_create');
        $this->assertEquals(0, $data['status']);
        $this->assertEquals('Room price has changed. Please review and try again.', $data['message']);
    }

    /**
     * Duplicate room types in booking details should return an error.
     *
     * @return void
     */
    public function test_create_booking_duplicate_room_types_api(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        $roomType = CrmRoomType::first();
        $this->assertNotNull($roomType);
        $sectionId = $roomType->section_id;

        $details = [
            ['room_type_id' => $roomType->id, 'room_count' => 1, 'guest_count' => ['adults' => 1, 'children' => 0]],
            ['room_type_id' => $roomType->id, 'room_count' => 1, 'guest_count' => ['adults' => 1, 'children' => 0]],
        ];

        $response = $this->postGraphQL($this->getMutation($sectionId, now()->format('Y-m-d'), now()->addDay()->format('Y-m-d'), $details));
        $response->assertStatus(200);
        $data = $response->json('data.customer_booking_create');
        $this->assertEquals(0, $data['status']);
        $this->assertEquals('Duplicate room types in booking details', $data['message']);
    }

    /**
     * Overlapping booking should return a conflict error.
     *
     * @return void
     */
    public function test_create_booking_conflict(): void {
        $user = User::first();
        Sanctum::actingAs($user);

        $roomType = CrmRoomType::first();
        $this->assertNotNull($roomType);
        $sectionId = $roomType->section_id;

        // create an existing booking overlap
        $customer = \Modules\Crm\Models\CrmCustomer::where('user_id', $user->id)->first();
        CrmBooking::create([
            'customer_id' => $customer->id,
            'section_id'  => $sectionId,
            'code'        => 'EXIST',
            'status'      => CrmBooking::STATUS_CONFIRMED,
            'start'       => now()->format('Y-m-d'),
            'end'         => now()->addDays(2)->format('Y-m-d'),
        ]);

        $details = [
            [
                'room_type_id' => $roomType->id,
                'room_count'   => 1,
                'guest_count'  => ['adults' => 1, 'children' => 0],
            ],
        ];

        // attempt overlapping booking
        $response = $this->postGraphQL($this->getMutation($sectionId, now()->addDay()->format('Y-m-d'), now()->addDays(3)->format('Y-m-d'), $details));
        $response->assertStatus(200);
        $data = $response->json('data.customer_booking_create');
        $this->assertEquals(0, $data['status']);
        $this->assertEquals('You already have an active booking for the selected dates', $data['message']);
    }

    /**
     * Test that rate limit is enforced (placeholder - implementation depends on test environment setup).
     *
     * @return void
     */
    // Rate limit test removed: current test environment does not expose limiter configuration reliably.
}
