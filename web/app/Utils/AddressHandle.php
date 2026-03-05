<?php

namespace App\Utils;

use Modules\Admin\Models\Municipality;
use Modules\Admin\Models\PostNumber;
use Modules\Admin\Models\Prefecture;

/**
 * Class AddressHandle
 *
 * Centralized address processing helpers.
 */
class AddressHandle {
    /**
     * Build full address line string for full-text search.
     *
     * This method will:
     * - Normalize postal code (7 digits only)
     * - Resolve municipality and prefecture from postal code when possible
     * - Concatenate postal code, prefecture, municipality, ward and detailed parts
     * - Optionally append latitude/longitude if they exist
     *
     * @param  array|object  $source  Source data (e.g. Eloquent model or array) containing address fields
     * @return string|null Full address line or null when no data
     */
    public static function buildAddressLine($source): ?string {
        // Normalize postal code (keep only digits, expect 7 digits)
        $postalRaw    = trim((string) get_value($source, 'postal_code', ''));
        $postalDigits = preg_replace('/\D/', '', $postalRaw);
        if (strlen($postalDigits) !== 7) {
            $postalDigits = '';
        }

        $prefectureName   = '';
        $municipalityName = '';

        // Resolve municipality and prefecture from postal code when available
        if ($postalDigits !== '') {
            /** @var PostNumber|null $postNumber */
            $postNumber = PostNumber::where('post_number', $postalDigits)->first();
            if ($postNumber) {
                /** @var Municipality|null $municipality */
                $municipality = Municipality::find($postNumber->municipality_id);
                if ($municipality) {
                    $municipalityName = (string) $municipality->name;

                    /** @var Prefecture|null $prefecture */
                    $prefecture = Prefecture::find($municipality->prefecture_id);
                    if ($prefecture) {
                        $prefectureName = (string) $prefecture->name;
                    }
                }
            }
        }

        // Basic address parts
        $ward     = trim((string) get_value($source, 'ward', ''));
        $address  = trim((string) get_value($source, 'address', ''));
        $chome    = trim((string) get_value($source, 'chome', ''));
        $ban      = trim((string) get_value($source, 'ban', ''));
        $go       = trim((string) get_value($source, 'go', ''));
        $building = trim((string) get_value($source, 'building', ''));
        $room     = trim((string) get_value($source, 'room', ''));

        // Optional coordinates for search
        $latitude  = get_value($source, 'latitude', null);
        $longitude = get_value($source, 'longitude', null);

        $parts = [];

        if ($postalDigits !== '') {
            $parts[] = $postalDigits;
        }

        if ($prefectureName !== '') {
            $parts[] = $prefectureName;
        }

        if ($municipalityName !== '') {
            $parts[] = $municipalityName;
        }

        // Avoid duplicating ward when it is already part of municipality name (e.g. 福岡市博多区 + 博多区)
        if ($ward !== '' && !str_contains($municipalityName, $ward)) {
            $parts[] = $ward;
        }

        // Detail parts follow Japanese order after area names
        $detailParts = array_filter([
            $address,
            $chome,
            $ban,
            $go,
            $building,
            $room,
        ], static function ($value) {
            return trim((string) $value) !== '';
        });

        if (!empty($detailParts)) {
            $parts[] = implode(' ', $detailParts);
        }

        if ($latitude !== null && $longitude !== null && $latitude !== '' && $longitude !== '') {
            $parts[] = (string) $latitude;
            $parts[] = (string) $longitude;
        }

        if (empty($parts)) {
            return null;
        }

        return implode(' ', $parts);
    }

    /**
     * Build display full address string without postal code and coordinates.
     *
     * This method will:
     * - Normalize postal code (7 digits only)
     * - Resolve municipality and prefecture from postal code when possible
     * - Concatenate prefecture, municipality, ward and detailed address parts
     * - Exclude postal code and latitude/longitude from the final output
     *
     * @param  array|object  $source  Source data (e.g. Eloquent model or array) containing address fields
     * @return string|null Human-readable full address or null when no data
     */
    public static function buildFullAddress($source): ?string {
        // Normalize postal code (keep only digits, expect 7 digits)
        $postalRaw    = trim((string) get_value($source, 'postal_code', ''));
        $postalDigits = preg_replace('/\D/', '', $postalRaw);
        if (strlen($postalDigits) !== 7) {
            $postalDigits = '';
        }

        $prefectureName   = '';
        $municipalityName = '';

        // Resolve municipality and prefecture from postal code when available
        if ($postalDigits !== '') {
            /** @var PostNumber|null $postNumber */
            $postNumber = PostNumber::where('post_number', $postalDigits)->first();
            if ($postNumber) {
                /** @var Municipality|null $municipality */
                $municipality = Municipality::find($postNumber->municipality_id);
                if ($municipality) {
                    $municipalityName = (string) $municipality->name;

                    /** @var Prefecture|null $prefecture */
                    $prefecture = Prefecture::find($municipality->prefecture_id);
                    if ($prefecture) {
                        $prefectureName = (string) $prefecture->name;
                    }
                }
            }
        }

        // Basic address parts
        $ward     = trim((string) get_value($source, 'ward', ''));
        $address  = trim((string) get_value($source, 'address', ''));
        $chome    = trim((string) get_value($source, 'chome', ''));
        $ban      = trim((string) get_value($source, 'ban', ''));
        $go       = trim((string) get_value($source, 'go', ''));
        $building = trim((string) get_value($source, 'building', ''));
        $room     = trim((string) get_value($source, 'room', ''));

        $parts = [];

        if ($prefectureName !== '') {
            $parts[] = $prefectureName;
        }

        if ($municipalityName !== '') {
            $parts[] = $municipalityName;
        }

        // Avoid duplicating ward when it is already part of municipality name
        if ($ward !== '' && !str_contains($municipalityName, $ward)) {
            $parts[] = $ward;
        }

        // Detail parts follow after prefecture/municipality/ward
        $detailParts = array_filter([
            $address,
            $chome,
            $ban,
            $go,
            $building,
            $room,
        ], static function ($value) {
            return trim((string) $value) !== '';
        });

        if (!empty($detailParts)) {
            $parts[] = implode(' ', $detailParts);
        }

        if (empty($parts)) {
            return null;
        }

        return implode(' ', $parts);
    }
}
