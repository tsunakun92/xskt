<?php

namespace Modules\Api\GraphQL\Resolvers;

use Illuminate\Support\Facades\Auth;

use App\Entities\Sanctum\PersonalAccessToken;
use Modules\Crm\Services\Contracts\CrmBookingServiceInterface;
use Modules\Logging\Utils\LogHandler;

/**
 * Resolver for customer booking info API.
 *
 * Provides detailed information for a specific booking.
 */
class CustomerBookingInfoResolver extends BaseApiResolver {
    /**
     * Create a new resolver instance.
     *
     * @param  CrmBookingServiceInterface  $bookingService
     */
    public function __construct(
        protected CrmBookingServiceInterface $bookingService
    ) {}

    /**
     * Get booking detail information for customer.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function info($root, array $args): array {
        // Validate input
        $validationError = $this->validateInput($args, [
            'booking_id' => 'required|integer|min:1',
            'version'    => 'required|string|max:255',
            'platform'   => PersonalAccessToken::getPlatformValidationRules(),
        ], 'Customer booking info', []);

        if ($validationError !== null) {
            return $validationError;
        }

        // Check rate limiting using base helper
        $rateLimitResult = $this->checkRateLimitFromConfig('customer_booking_info', 'customer_booking_info', 'Customer booking info');
        if ($rateLimitResult !== null) {
            return $rateLimitResult;
        }

        return $this->execute('Customer booking info', [], function () use ($args) {
            $bookingId = (int) $args['booking_id'];
            $user      = Auth::user();

            // Load booking with relations via Service Layer
            $booking = $this->bookingService->getCustomerBookingInfo($bookingId, (int) $user->id);

            if ($booking === null) {
                LogHandler::warning('Customer booking not found or access denied', [
                    'booking_id' => $bookingId,
                    'user_id'    => $user->id,
                ], LogHandler::CHANNEL_API);

                return apiResponseError('Booking not found or access denied', [
                    'data' => null,
                ]);
            }

            LogHandler::info('Customer booking info retrieved via API', [
                'booking_id' => $booking->id,
            ], LogHandler::CHANNEL_API);

            return apiResponseSuccess('Booking detailed information retrieved successfully', [
                'data' => $booking,
            ]);
        });
    }
}
