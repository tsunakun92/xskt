<?php

namespace Modules\Api\GraphQL\Resolvers;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

use App\Entities\Sanctum\PersonalAccessToken;
use Modules\Crm\Services\Contracts\CrmBookingServiceInterface;
use Modules\Logging\Utils\LogHandler;

/**
 * Resolver for customer booking creation API.
 * P0103_Customer_Booking_API
 */
class CustomerBookingCreateResolver extends BaseApiResolver {
    /**
     * Booking service instance.
     *
     * @var CrmBookingServiceInterface
     */
    protected CrmBookingServiceInterface $bookingService;

    /**
     * Constructor injection for service.
     *
     * @param  CrmBookingServiceInterface  $bookingService
     */
    public function __construct(CrmBookingServiceInterface $bookingService) {
        $this->bookingService = $bookingService;
    }

    /**
     * Create a new customer booking.
     *
     * The input arguments are validated here; business rules such as
     * availability, price sanity, guest capacity and conflict checks are
     * performed in the booking service implementation.  The client may omit
     * the top‑level `guest_count` and the service will compute it from the
     * per‑detail counts.  A `notes` field is available both at the booking
     * level (internal) and inside each detail.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function create($root, array $args): array {
        // Validation rules for nested booking_details
        $rules = [
            'section_id'                             => 'required|integer|min:1',
            'start'                                  => 'required|date_format:Y-m-d',
            'end'                                    => 'required|date_format:Y-m-d|after:start',
            'booking_details'                        => 'required|array|min:1',
            'booking_details.*.room_type_id'         => 'required|integer|min:1',
            'booking_details.*.room_count'           => 'required|integer|min:1',
            'booking_details.*.guest_count'          => 'required|array',
            'booking_details.*.guest_count.adults'   => 'nullable|integer|min:0',
            'booking_details.*.guest_count.children' => 'nullable|integer|min:0',
            'booking_details.*.price_per_night'      => 'nullable|numeric|min:0',
            'booking_details.*.total_price'          => 'nullable|numeric|min:0',
            'booking_details.*.notes'                => 'nullable|string',
            'guest_count'                            => 'nullable|array',
            'guest_count.adults'                     => 'nullable|integer|min:0',
            'guest_count.children'                   => 'nullable|integer|min:0',
            'deposit_amount'                         => 'nullable|numeric|min:0',
            'special_requests'                       => 'nullable|string',
            'notes'                                  => 'nullable|string',
            'version'                                => 'required|string|max:255',
            'platform'                               => PersonalAccessToken::getPlatformValidationRules(),
        ];

        $validationError = $this->validateInput($args, $rules, 'Customer booking create', []);
        if ($validationError !== null) {
            return $validationError;
        }

        // Rate limit check
        $rateLimitResult = $this->checkRateLimitFromConfig('customer_booking_create', 'customer_booking_create', 'Customer booking create');
        if ($rateLimitResult !== null) {
            return $rateLimitResult;
        }

        return $this->execute('Customer booking create', [], function () use ($args) {
            $userId = Auth::id();

            try {
                $booking = $this->bookingService->createCustomerBooking($userId, $args);
            } catch (Exception $e) {
                LogHandler::warning('Customer booking creation failed', [
                    'user_id' => $userId,
                    'input'   => $args,
                    'error'   => $e->getMessage(),
                ], LogHandler::CHANNEL_API);

                return apiResponseError($e->getMessage(), ['data' => null]);
            }

            if ($booking === null) {
                // should not happen with new service impl but keep fallback
                LogHandler::warning('Customer booking creation returned null', [
                    'user_id' => $userId,
                    'input'   => $args,
                ], LogHandler::CHANNEL_API);

                $msg = Session::get('transaction_error');
                if (!empty($msg)) {
                    return apiResponseError($msg, ['data' => null]);
                }

                return apiResponseError('Failed to create booking', ['data' => null]);
            }

            LogHandler::info('Customer booking created via API', [
                'booking_id' => $booking->id,
                'user_id'    => $userId,
            ], LogHandler::CHANNEL_API);

            return apiResponseSuccess('Booking created successfully', ['data' => $booking]);
        });
    }
}
