<?php

namespace Modules\Api\GraphQL\Resolvers;

use Illuminate\Support\Facades\Auth;

use App\Entities\Sanctum\PersonalAccessToken;
use App\Utils\CommonProcess;
use Modules\Crm\Models\CrmBooking;
use Modules\Crm\Services\Contracts\CrmBookingServiceInterface;
use Modules\Logging\Utils\LogHandler;

/**
 * Resolver for customer booking history API
 * P0106_Customer_Booking_History_API
 */
class CustomerBookingHistoryResolver extends BaseApiResolver {
    /**
     * @var CrmBookingServiceInterface
     */
    protected $bookingService;

    /**
     * Constructor injection for service.
     *
     * @param  CrmBookingServiceInterface  $bookingService
     */
    public function __construct(CrmBookingServiceInterface $bookingService) {
        $this->bookingService = $bookingService;
    }

    /**
     * Search booking history with filtering and sorting
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function search($root, array $args): array {
        // Validate input
        $validationError = $this->validateInput($args, [
            'page'      => 'nullable|integer|min:1',
            'limit'     => 'nullable|integer|min:1|max:100',
            'status'    => 'nullable|integer',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to'   => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            'sort_by'   => 'nullable|string|in:created_at,start',
            'order'     => 'nullable|string|in:asc,desc',
            'version'   => 'required|string|max:255',
            'platform'  => PersonalAccessToken::getPlatformValidationRules(),
        ], 'Customer booking history', []);

        if ($validationError !== null) {
            return $validationError;
        }

        // Check rate limiting using base helper
        $rateLimitResult = $this->checkRateLimitFromConfig('customer_booking_history', 'customer_booking_history', 'Customer booking history');
        if ($rateLimitResult !== null) {
            return $rateLimitResult;
        }

        return $this->execute('Customer booking history', [], function () use ($args) {
            // Set default values
            $page  = CommonProcess::getValue($args, 'page', 1);
            $limit = CommonProcess::getValue($args, 'limit', 10);

            // Prepare filters
            $filters = [
                'status'    => CommonProcess::getValue($args, 'status'),
                'date_from' => CommonProcess::getValue($args, 'date_from'),
                'date_to'   => CommonProcess::getValue($args, 'date_to'),
            ];

            $sortBy = CommonProcess::getValue($args, 'sort_by', 'created_at');
            $order  = CommonProcess::getValue($args, 'order', 'desc');

            // Get bookings via service
            $result = $this->bookingService->searchCustomerBookings(
                Auth::id(),
                $filters,
                $page,
                $limit,
                $sortBy,
                $order
            );

            // Check if no results found
            if ($result['bookings']->isEmpty()) {
                return apiResponseError('No bookings found', [
                    'data'          => [],
                    'paginatorInfo' => $this->buildPaginatorInfo(0, $page, $limit),
                ]);
            }

            $bookings = $result['bookings']->map(function (CrmBooking $booking) {
                return [
                    'id'           => $booking->id,
                    'code'         => $booking->code,
                    'status'       => $booking->status,
                    'start'        => $booking->start,
                    'end'          => $booking->end,
                    'check_in_at'  => $booking->check_in_at,
                    'check_out_at' => $booking->check_out_at,
                    'guest_count'  => [
                        'adults'   => $booking->getAdultCount(),
                        'children' => $booking->getChildrenCount(),
                    ],
                    'total_price'  => $booking->total_price,
                    'section_name' => $booking->section_name,
                    'room_details' => $this->bookingService->formatRoomDetails($booking),
                ];
            });

            $total = $result['total'];

            LogHandler::info('Customer booking history retrieved via API', [
                'total'  => $total,
                'page'   => $page,
                'limit'  => $limit,
                'status' => $filters['status'],
            ], LogHandler::CHANNEL_API);

            return apiResponseSuccess('Booking history retrieved successfully', [
                'data'          => $bookings,
                'paginatorInfo' => $this->buildPaginatorInfo($total, $page, $limit),
            ]);
        });
    }
}
