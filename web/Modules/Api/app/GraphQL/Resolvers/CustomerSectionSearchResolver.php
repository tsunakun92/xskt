<?php

namespace Modules\Api\GraphQL\Resolvers;

use App\Entities\Sanctum\PersonalAccessToken;
use App\Utils\CommonProcess;
use Modules\Crm\Models\CrmRoomType;
use Modules\Crm\Models\CrmSection;
use Modules\Logging\Utils\LogHandler;

/**
 * Resolver for customer section search API
 */
class CustomerSectionSearchResolver extends BaseApiResolver {
    /**
     * Search sections with filtering and sorting
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function search($root, array $args) {
        // Validate input
        $validationError = $this->validateInput($args, [
            'page'     => 'nullable|integer|min:1',
            'limit'    => 'nullable|integer|min:1|max:100',
            'address'  => 'nullable|string|max:255',
            'type_id'  => 'nullable|integer|min:1',
            'sort_by'  => 'nullable|string|in:rating_value,price_asc,price_desc',
            'order'    => 'nullable|string|in:asc,desc',
            'version'  => 'required|string|max:255',
            'platform' => PersonalAccessToken::getPlatformValidationRules(),
        ], 'Customer section search', []);

        if ($validationError !== null) {
            return $validationError;
        }

        // Check rate limiting using base helper
        $rateLimitResult = $this->checkRateLimitFromConfig('customer_section_search', 'customer_section_search', 'Customer section search');
        if ($rateLimitResult !== null) {
            return $rateLimitResult;
        }

        return $this->execute('Customer section search', [], function () use ($args) {
            // Set default values
            $page    = CommonProcess::getValue($args, 'page', 1);
            $limit   = CommonProcess::getValue($args, 'limit', 10);
            $address = CommonProcess::getValue($args, 'address', null);
            $typeId  = CommonProcess::getValue($args, 'type_id', null);
            $sortBy  = CommonProcess::getValue($args, 'sort_by', 'rating_value');
            $order   = CommonProcess::getValue($args, 'order', 'desc');

            // Build base query
            $query = $this->buildBaseQuery($address, $typeId);

            // Get sections with sorting and pagination
            $result = $this->getSectionsWithSorting($query, $sortBy, $order, $page, $limit);

            // Check if no results found
            $isEmpty = $result['sections']->isEmpty();

            if ($isEmpty) {
                $paginatorInfo = [
                    'total'       => 0,
                    'currentPage' => $page,
                    'lastPage'    => 1,
                    'perPage'     => $limit,
                ];

                return apiResponseError('No sections found', [
                    'data'          => [],
                    'paginatorInfo' => $paginatorInfo,
                ]);
            }

            $sections = $result['sections'];
            $total    = $result['total'];

            // Build paginator info
            $paginatorInfo = [
                'total'       => $total,
                'currentPage' => $page,
                'lastPage'    => (int) ceil($total / $limit),
                'perPage'     => $limit,
            ];

            LogHandler::info('Customer sections retrieved via API', [
                'total'   => $total,
                'page'    => $page,
                'limit'   => $limit,
                'address' => $address,
                'type_id' => $typeId,
            ], LogHandler::CHANNEL_API);

            return apiResponseSuccess('Sections retrieved successfully', [
                'data'          => $sections,
                'paginatorInfo' => $paginatorInfo,
            ]);
        });
    }

    /**
     * Get minimum price from all room types in a section
     *
     * @param  int  $sectionId
     * @return float|null
     */
    private function getMinPrice(int $sectionId): ?float {
        $minPrice = CrmRoomType::query()
            ->where('section_id', $sectionId)
            ->active()
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->min('price');

        return $minPrice !== null ? (float) $minPrice : null;
    }

    /**
     * Build base query with filters.
     *
     * @param  string|null  $address
     * @param  int|null  $typeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildBaseQuery(?string $address, ?int $typeId) {
        $query = CrmSection::query()
            ->active()
            ->searchByAddress($address);

        if ($typeId !== null) {
            $query->where('type_id', $typeId);
        }

        return $query;
    }

    /**
     * Get sections with sorting and pagination based on sort_by
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $sortBy
     * @param  string  $order
     * @param  int  $page
     * @param  int  $limit
     * @return array{total: int, sections: \Illuminate\Database\Eloquent\Collection, withPrice: bool}
     */
    private function getSectionsWithSorting($query, string $sortBy, string $order, int $page, int $limit): array {
        switch ($sortBy) {
            case 'rating_value':
                return $this->sortByRating($query, $order, $page, $limit);
            case 'price_asc':
            case 'price_desc':
                return $this->sortByPrice($query, $sortBy, $page, $limit);
            default:
                // Default to id descending
                return $this->sortByIdDesc($query, $page, $limit);
        }
    }

    /**
     * Sort sections by rating value (database-level sorting)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $order
     * @param  int  $page
     * @param  int  $limit
     * @return array{total: int, sections: \Illuminate\Database\Eloquent\Collection, withPrice: bool}
     */
    private function sortByRating($query, string $order, int $page, int $limit): array {
        $query->orderBy('rating_value', $order)
            ->orderBy('id', 'asc'); // Secondary sort for consistency

        $total = $query->count();

        $sections = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return [
            'total'     => $total,
            'sections'  => $sections,
            'withPrice' => false,
        ];
    }

    /**
     * Sort sections by id descending
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $page
     * @param  int  $limit
     * @return array{total: int, sections: \Illuminate\Database\Eloquent\Collection, withPrice: bool}
     */
    private function sortByIdDesc($query, int $page, int $limit): array {
        $query->orderBy('id', 'desc');

        $total = $query->count();

        $sections = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return [
            'total'     => $total,
            'sections'  => $sections,
            'withPrice' => false,
        ];
    }

    /**
     * Sort sections by price (database-level sorting using subquery)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $sortBy
     * @param  int  $page
     * @param  int  $limit
     * @return array{total: int, sections: \Illuminate\Database\Eloquent\Collection, withPrice: bool}
     */
    private function sortByPrice($query, string $sortBy, int $page, int $limit): array {
        $isAscending = $sortBy === 'price_asc';

        // Clone query for counting total (before adding selectSub)
        $countQuery = clone $query;
        $total      = $countQuery->count();

        // Add subquery to calculate min_price for each section
        $query->select('crm_sections.*')
            ->selectSub(function ($subQuery) {
                $subQuery->selectRaw('MIN(price)')
                    ->from('crm_room_types')
                    ->whereColumn('crm_room_types.section_id', 'crm_sections.id')
                    ->where('crm_room_types.status', CrmRoomType::STATUS_ACTIVE)
                    ->whereNotNull('crm_room_types.price')
                    ->where('crm_room_types.price', '>', 0);
            }, 'min_price')
            ->orderBy('min_price', $isAscending ? 'asc' : 'desc')
            ->orderBy('id', 'asc'); // Secondary sort for consistency

        $sections = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return [
            'total'     => $total,
            'sections'  => $sections,
            'withPrice' => false,
        ];
    }
}
