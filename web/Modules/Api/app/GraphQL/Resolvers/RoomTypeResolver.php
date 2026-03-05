<?php

namespace Modules\Api\GraphQL\Resolvers;

use Illuminate\Support\Facades\Schema;

use Modules\Admin\Models\FileMaster;
use Modules\Crm\Models\CrmRoomType;

class RoomTypeResolver {
    /**
     * Get amenities as array of labels from amenities_flags.
     *
     * @param  CrmRoomType  $roomType
     * @return array
     */
    public function amenities(CrmRoomType $roomType): array {
        return $roomType->getAmenityLabels();
    }

    /**
     * Get room type images from FileMaster.
     *
     * @param  CrmRoomType  $roomType
     * @return array
     */
    public function images(CrmRoomType $roomType): array {
        $query = FileMaster::query()
            ->where('belong_type', FileMaster::BELONG_TYPE_CRM_ROOM_TYPE)
            ->where('belong_id', $roomType->id)
            ->where('file_type', FileMaster::TYPE_IMAGE)
            ->active();

        // Order by display_order if column exists, otherwise order by id only
        if (Schema::hasColumn('file_masters', 'display_order')) {
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
