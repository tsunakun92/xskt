<?php

namespace Modules\Api\GraphQL\Resolvers;

use App\Entities\Sanctum\PersonalAccessToken;
use Modules\Crm\Models\CrmSection;
use Modules\Logging\Utils\LogHandler;

/**
 * Resolver for customer section info API.
 *
 * Provides detailed information for a specific section including
 * company information, section images and room types with prices and images.
 */
class CustomerSectionInfoResolver extends BaseApiResolver {
    /**
     * Get section detail information for customer.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function info($root, array $args): array {
        // Validate input
        $validationError = $this->validateInput($args, [
            'section_id' => 'required|integer|min:1',
            'version'    => 'required|string|max:255',
            'platform'   => PersonalAccessToken::getPlatformValidationRules(),
        ], 'Customer section info', []);

        if ($validationError !== null) {
            return $validationError;
        }

        // Check rate limiting using base helper
        $rateLimitResult = $this->checkRateLimitFromConfig('customer_section_info', 'customer_section_info', 'Customer section info');
        if ($rateLimitResult !== null) {
            return $rateLimitResult;
        }

        return $this->execute('Customer section info', [], function () use ($args) {
            $sectionId = (int) $args['section_id'];

            // Load section with relations
            $section = CrmSection::query()
                ->active()
                ->with(['rCompany', 'rRoomTypes'])
                ->find($sectionId);

            if ($section === null) {
                LogHandler::warning('Customer section not found', [
                    'section_id' => $sectionId,
                ], LogHandler::CHANNEL_API);

                return apiResponseError('Section not found', [
                    'data' => null,
                ]);
            }

            LogHandler::info('Customer section info retrieved via API', [
                'section_id' => $section->id,
            ], LogHandler::CHANNEL_API);

            return apiResponseSuccess('Section retrieved successfully', [
                'data' => $section,
            ]);
        });
    }
}
