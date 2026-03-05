<?php

namespace App\Entities\Sanctum;

use InvalidArgumentException;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

use App\Models\BaseModel;

/**
 * Extended PersonalAccessToken for API token management
 */
class PersonalAccessToken extends SanctumPersonalAccessToken {
    //-----------------------------------------------------
    // Constants
    //-----------------------------------------------------
    /**
     * Platform constants
     */
    public const PLATFORM_WEB     = 1;

    public const PLATFORM_ANDROID = 2;

    public const PLATFORM_IOS     = 3;

    public const PLATFORM_WINDOWS = 4;

    /**
     * Maximum number of mobile devices allowed per user
     */
    public const MAX_MOBILE_DEVICES = 3;

    /**
     * Mobile device platform constants
     */
    public const MOBILE_DEVICES = [
        self::PLATFORM_ANDROID,
        self::PLATFORM_IOS,
    ];

    //-----------------------------------------------------
    // Properties
    //-----------------------------------------------------
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'device_token',
        'platform',
        'status',
    ];

    //-----------------------------------------------------
    // Utility methods
    //-----------------------------------------------------
    /**
     * Get platform name
     *
     * @return string
     */
    public function getPlatformName() {
        return self::getArrayPlatform()[$this->platform] ?? 'Unknown';
    }

    /**
     * Determine if the access token is active.
     *
     * @return bool
     */
    public function isActive() {
        return $this->status != BaseModel::STATUS_INACTIVE;
    }

    /**
     * Custom logout function to update the status of the access token.
     *
     * @return void
     */
    public function logout() {
        $this->update(['status' => BaseModel::STATUS_INACTIVE]);
    }

    //-----------------------------------------------------
    // Static methods
    //-----------------------------------------------------
    /**
     * Get array platform
     *
     * @return array Key=>Value array
     */
    public static function getArrayPlatform() {
        return [
            self::PLATFORM_WEB     => 'Web',
            self::PLATFORM_ANDROID => 'Android',
            self::PLATFORM_IOS     => 'iOS',
            self::PLATFORM_WINDOWS => 'Windows',
        ];
    }

    /**
     * Check if platform string is valid.
     * Accepts both numeric strings ('1', '2', '3', '4') and text ('web', 'android', 'ios', 'windows').
     *
     * @param  string  $platform  Platform string
     * @return bool
     */
    public static function isValidPlatform(string $platform): bool {
        $validPlatforms = [
            // Numeric strings
            '1', '2', '3', '4',
            // Text strings
            'web', 'android', 'ios', 'windows',
        ];

        return in_array(strtolower($platform), $validPlatforms);
    }

    /**
     * Get validation rules for platform parameter.
     * Returns array that can be used in Laravel Validator.
     * Validates that platform is either numeric string ('1', '2', '3', '4') or text ('web', 'android', 'ios', 'windows').
     *
     * @return array
     */
    public static function getPlatformValidationRules(): array {
        return [
            'required',
            'string',
            'max:255',
            function ($attribute, $value, $fail) {
                if (!self::isValidPlatform($value)) {
                    $fail('Invalid platform');
                }
            },
        ];
    }

    /**
     * Convert platform string to integer.
     * Used when receiving platform from GraphQL API (string) and need to store as integer in DB.
     * Accepts both numeric strings ('1', '2', '3', '4') and text ('web', 'android', 'ios', 'windows').
     *
     * @param  string  $platform  Platform string (e.g., "web", "android", "ios", "windows", "1", "2", "3", "4")
     *
     * @throws InvalidArgumentException If platform is invalid
     *
     * @return int Platform integer constant
     */
    public static function convertStringToInt(string $platform): int {
        $platformLower = strtolower($platform);

        // Handle numeric strings
        if (in_array($platformLower, ['1', '2', '3', '4'])) {
            return (int) $platformLower;
        }

        // Handle text strings
        $platformMap = [
            'web'     => self::PLATFORM_WEB,
            'android' => self::PLATFORM_ANDROID,
            'ios'     => self::PLATFORM_IOS,
            'windows' => self::PLATFORM_WINDOWS,
        ];

        if (isset($platformMap[$platformLower])) {
            return $platformMap[$platformLower];
        }

        throw new InvalidArgumentException('Invalid platform: ' . $platform);
    }

    /**
     * Convert platform integer to string.
     * Used when reading platform from DB (integer) and need to return as string in API response.
     *
     * @param  int  $platform  Platform integer constant
     * @return string Platform string (e.g., "web", "android", "ios", "windows")
     */
    public static function convertIntToString(int $platform): string {
        $platformMap = [
            self::PLATFORM_WEB     => 'web',
            self::PLATFORM_ANDROID => 'android',
            self::PLATFORM_IOS     => 'ios',
            self::PLATFORM_WINDOWS => 'windows',
        ];

        return $platformMap[$platform] ?? 'web'; // Default to web if unknown
    }

    /**
     * Get mobile tokens by user id
     *
     * @param  int  $userId  User id
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getMobileTokensByUserId(int $userId) {
        // NOTE:
        // In some environments tokenable_type can differ (morph map / class aliasing),
        // so we intentionally filter by tokenable_id + platform only.
        return static::where('tokenable_id', $userId)
            ->whereIn('platform', self::MOBILE_DEVICES)
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
