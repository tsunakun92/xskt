<?php

namespace Modules\Api\GraphQL\Resolvers;

use DomainException;
use Illuminate\Support\Facades\Auth;

use App\Entities\Sanctum\PersonalAccessToken;
use Modules\Crm\Services\Contracts\CrmBookingServiceInterface;
use Modules\Logging\Utils\LogHandler;

/**
 * Resolver for customer booking payment API.
 *
 * Handles confirming payment for an existing customer booking.
 */
class CustomerBookingPaymentResolver extends BaseApiResolver {
    /**
     * Create a new resolver instance.
     *
     * @param  CrmBookingServiceInterface  $bookingService
     */
    public function __construct(
        protected CrmBookingServiceInterface $bookingService
    ) {}

    /**
     * Confirm payment for a customer booking.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function pay($root, array $args): array {
        // Validate input
        $validationError = $this->validateInput($args, [
            'booking_id' => 'required|integer|min:1',
            'version'    => 'required|string|max:255',
            'platform'   => PersonalAccessToken::getPlatformValidationRules(),
        ], 'Customer booking payment', []);

        if ($validationError !== null) {
            return $validationError;
        }

        // Check rate limiting using base helper
        $rateLimitResult = $this->checkRateLimitFromConfig('customer_booking_payment', 'customer_booking_payment', 'Customer booking payment');
        if ($rateLimitResult !== null) {
            return $rateLimitResult;
        }

        return $this->execute('Customer booking payment', [], function () use ($args) {
            $bookingId = (int) $args['booking_id'];
            $user      = Auth::user();

            try {
                $booking = $this->bookingService->payCustomerBooking($bookingId, (int) $user->id);
            } catch (DomainException $e) {
                $message = $e->getMessage();

                if ($message === 'Booking not found') {
                    LogHandler::warning('Customer booking payment booking not found', [
                        'booking_id' => $bookingId,
                        'user_id'    => $user->id,
                    ], LogHandler::CHANNEL_API);

                    return apiResponseError('Booking not found', [
                        'data' => null,
                    ]);
                }

                if ($message === 'Access denied') {
                    LogHandler::warning('Customer booking payment access denied', [
                        'booking_id' => $bookingId,
                        'user_id'    => $user->id,
                    ], LogHandler::CHANNEL_API);

                    return apiResponseError('Access denied', [
                        'data' => null,
                    ]);
                }

                if ($message === 'Booking cannot be paid in the current status') {
                    LogHandler::warning('Customer booking payment invalid status', [
                        'booking_id' => $bookingId,
                        'user_id'    => $user->id,
                    ], LogHandler::CHANNEL_API);

                    return apiResponseError('Booking cannot be paid in the current status', [
                        'data' => null,
                    ]);
                }

                LogHandler::error('Customer booking payment domain error', [
                    'booking_id' => $bookingId,
                    'user_id'    => $user->id,
                    'error'      => $message,
                ], LogHandler::CHANNEL_API);

                return apiResponseError('An error occurred. Please try again later.', [
                    'data' => null,
                ]);
            }

            if ($booking === null) {
                LogHandler::error('Customer booking payment failed - null booking returned', [
                    'booking_id' => $bookingId,
                    'user_id'    => $user->id,
                ], LogHandler::CHANNEL_API);

                return apiResponseError('An error occurred. Please try again later.', [
                    'data' => null,
                ]);
            }

            LogHandler::info('Customer booking payment confirmed via API', [
                'booking_id' => $booking->id,
                'user_id'    => $user->id,
            ], LogHandler::CHANNEL_API);

            return apiResponseSuccess('Payment confirmed successfully', [
                'data' => $booking,
            ]);
        });
    }
}
