<?php

namespace Modules\Api\GraphQL\Resolvers;

use Modules\Crm\Models\CrmRoomType;
use Modules\Crm\Models\CrmSection;

class SectionResolver {
    /**
     * Get minimum price from all room types in a section.
     *
     * @param  CrmSection  $section
     * @return float|null
     */
    public function minPrice(CrmSection $section): ?float {
        $minPrice = CrmRoomType::query()
            ->where('section_id', $section->id)
            ->active()
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->min('price');

        return $minPrice !== null ? (float) $minPrice : null;
    }

    /**
     * Get section images from FileMaster.
     *
     * @param  CrmSection  $section
     * @return array
     */
    public function images(CrmSection $section): array {
        $query = \Modules\Admin\Models\FileMaster::query()
            ->where('belong_type', \Modules\Admin\Models\FileMaster::BELONG_TYPE_CRM_SECTION)
            ->where('belong_id', $section->id)
            ->where('file_type', \Modules\Admin\Models\FileMaster::TYPE_IMAGE)
            ->active();

        // Order by display_order if column exists, otherwise order by id only
        if (\Illuminate\Support\Facades\Schema::hasColumn('file_masters', 'display_order')) {
            $query->orderBy('display_order', 'asc');
        }

        $files = $query->orderBy('id', 'asc')->get();

        $images = [];

        foreach ($files as $file) {
            $images[] = [
                'id'       => $file->id,
                'url'      => $file->url ?? '',
                'order'    => $file->display_order ?? 0,
                'alt_text' => $file->alt_text,
                'title'    => $file->title,
            ];
        }

        return $images;
    }
}
