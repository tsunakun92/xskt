<?php

namespace Modules\Api\GraphQL\Resolvers;

use DomainException;
use Illuminate\Support\Facades\Auth;

use App\Entities\Sanctum\PersonalAccessToken;
use Modules\Crm\Services\Contracts\CrmBookingServiceInterface;
use Modules\Logging\Utils\LogHandler;

/**
 * Resolver for customer booking cancel API.
 *
 * Submits a cancel request for an existing booking of the authenticated customer.
 * In this phase, the API only updates booking status/cancel info to mark it as
 * "Cancel requested" and does not modify any payment records.
 */
class CustomerBookingCancelResolver extends BaseApiResolver {
    /**
     * Create a new resolver instance.
     *
     * @param  CrmBookingServiceInterface  $bookingService
     */
    public function __construct(
        protected CrmBookingServiceInterface $bookingService
    ) {}

    /**
     * Submit a cancel request for an existing customer booking.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function cancel($root, array $args): array {
        // Validate input
        $validationError = $this->validateInput($args, [
            'booking_id'    => 'required|integer|min:1',
            'cancel_reason' => 'nullable|string',
            'version'       => 'required|string|max:255',
            'platform'      => PersonalAccessToken::getPlatformValidationRules(),
        ], 'Customer booking cancel', []);

        if ($validationError !== null) {
            return $validationError;
        }

        // Check rate limiting using base helper
        $rateLimitResult = $this->checkRateLimitFromConfig('customer_booking_cancel', 'customer_booking_cancel', 'Customer booking cancel');
        if ($rateLimitResult !== null) {
            return $rateLimitResult;
        }

        return $this->execute('Customer booking cancel', [], function () use ($args) {
            $bookingId     = (int) $args['booking_id'];
            $cancelReason  = $args['cancel_reason'] ?? null;
            $user          = Auth::user();

            try {
                $booking = $this->bookingService->cancelCustomerBooking($bookingId, (int) $user->id, $cancelReason);
            } catch (DomainException $e) {
                $message = $e->getMessage();

                if ($message === 'Booking not found') {
                    LogHandler::warning('Customer booking cancel booking not found', [
                        'booking_id' => $bookingId,
                        'user_id'    => $user->id,
                    ], LogHandler::CHANNEL_API);

                    return apiResponseError('Booking not found', [
                        'data' => null,
                    ]);
                }

                if ($message === 'Access denied') {
                    LogHandler::warning('Customer booking cancel access denied', [
                        'booking_id' => $bookingId,
                        'user_id'    => $user->id,
                    ], LogHandler::CHANNEL_API);

                    return apiResponseError('Access denied', [
                        'data' => null,
                    ]);
                }

                if ($message === 'Booking cannot be cancelled in the current status') {
                    LogHandler::warning('Customer booking cancel invalid status', [
                        'booking_id' => $bookingId,
                        'user_id'    => $user->id,
                    ], LogHandler::CHANNEL_API);

                    return apiResponseError('Booking cannot be cancelled in the current status', [
                        'data' => null,
                    ]);
                }

                LogHandler::error('Customer booking cancel domain error', [
                    'booking_id' => $bookingId,
                    'user_id'    => $user->id,
                    'error'      => $message,
                ], LogHandler::CHANNEL_API);

                return apiResponseError('An error occurred. Please try again later.', [
                    'data' => null,
                ]);
            }

            LogHandler::info('Customer booking cancel requested via API', [
                'booking_id' => $booking->id,
                'user_id'    => $user->id,
                'status'     => $booking->status,
            ], LogHandler::CHANNEL_API);

            return apiResponseSuccess('Cancel request submitted successfully', [
                'data' => $booking,
            ]);
        });
    }
}
