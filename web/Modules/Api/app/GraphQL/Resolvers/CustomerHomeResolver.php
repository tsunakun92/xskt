<?php

namespace Modules\Api\GraphQL\Resolvers;

use App\Entities\Sanctum\PersonalAccessToken;
use App\Utils\CommonProcess;
use Modules\Crm\Models\CrmSection;
use Modules\Logging\Utils\LogHandler;

/**
 * Resolver for customer home API.
 *
 * Provides home page data with 10 random active sections including
 * basic information, minimum price and images.
 */
class CustomerHomeResolver extends BaseApiResolver {
    /**
     * Get customer home data with random sections.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function home($root, array $args): array {
        // Validate input
        $validationError = $this->validateInput($args, [
            'version'  => 'required|string|max:255',
            'platform' => PersonalAccessToken::getPlatformValidationRules(),
        ], 'Customer home', []);

        if ($validationError !== null) {
            return $validationError;
        }

        // Check rate limiting using base helper
        $rateLimitResult = $this->checkRateLimitFromConfig('customer_home', 'customer_home', 'Customer home');
        if ($rateLimitResult !== null) {
            return $rateLimitResult;
        }

        return $this->execute('Customer home', [], function () {
            // Number of sections to retrieve (default: 10)
            $limit = (int) CommonProcess::getValue([], 'limit', 10);

            // Build base query for active sections
            $query = CrmSection::query()->active();

            $total = $query->count();

            if ($total === 0) {
                LogHandler::info('No sections found for customer home', [], LogHandler::CHANNEL_API);

                return apiResponseError('No sections found', [
                    'data' => [
                        'sections' => [],
                    ],
                ]);
            }

            // Get random sections limited by specified number
            $sections = $query->inRandomOrder()
                ->take($limit)
                ->get();

            // Get authenticated user data if available
            $userData = null;
            $user     = auth('sanctum')->user();
            if ($user) {
                // Load relationships needed for GraphQL field resolvers
                $user->load('rRole');

                /** @var \Modules\Admin\Services\MobilePermissionService $permissionService */
                $permissionService = app(\Modules\Admin\Services\MobilePermissionService::class);

                $userData = [
                    'user'        => $user,
                    'configs'     => $user->apiGetUserSettings(),
                    'permissions' => $permissionService->getMobilePermissionsGrouped($user),
                ];
            }

            LogHandler::info('Customer home sections retrieved via API', [
                'count' => $sections->count(),
            ], LogHandler::CHANNEL_API);

            $responseData = [
                'sections' => $sections,
            ];

            if ($userData !== null) {
                $responseData['user_data'] = $userData;
            }

            return apiResponseSuccess('Home data retrieved successfully', [
                'data' => $responseData,
            ]);
        });
    }
}
