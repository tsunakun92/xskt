<?php

namespace App\Utils;

use Carbon\Carbon;

/**
 * Helper class for Policy-related operations
 * Handles translations and date formatting for policy pages
 */
class PolicyHelper {
    /**
     * Language codes supported by policy pages
     *
     * @var array<string>
     */
    private const SUPPORTED_LANGUAGES = ['en', 'ja', 'vn', 'vi'];

    /**
     * Language code to setting key mapping
     *
     * @var array<string, string>
     */
    private const LANGUAGE_SETTING_MAP = [
        'en' => 'policy_en',
        'ja' => 'policy_ja',
        'vn' => 'policy_vn',
    ];

    /**
     * Language display names
     *
     * @var array<string, string>
     */
    private const LANGUAGE_NAMES = [
        'en' => 'English',
        'ja' => '日本語',
        'vn' => 'Tiếng Việt',
        'vi' => 'Tiếng Việt',
    ];

    /**
     * Policy titles by language
     * Uses translation keys from lang files
     *
     * @var array<string, string>
     */
    private const POLICY_TITLES = [
        'en' => 'Privacy Policy',
        'ja' => 'プライバシーポリシー',
        'vn' => 'Chính Sách Bảo Mật',
        'vi' => 'Chính Sách Bảo Mật',
    ];

    /**
     * "Last updated" text by language
     * Uses translation keys from lang files
     *
     * @var array<string, string>
     */
    private const LAST_UPDATED_TEXTS = [
        'en' => 'Last updated',
        'ja' => '最終更新',
        'vn' => 'Cập nhật lần cuối',
        'vi' => 'Cập nhật lần cuối',
    ];

    /**
     * Date formats by language
     * Maps to DateTimeExt constants where applicable
     *
     * @var array<string, string>
     */
    private const DATE_FORMATS = [
        'en' => 'F d, Y',                    // February 02, 2026
        'ja' => 'Y年m月d日',                  // 2026年02月02日
        'vn' => DateTimeExt::DATE_FORMAT_3,  // 02/02/2026 (d/m/Y)
        'vi' => DateTimeExt::DATE_FORMAT_3,  // 02/02/2026 (d/m/Y)
    ];

    /**
     * Normalize language code
     * Converts language codes like 'en-US' to 'en' and validates against supported languages
     * Maps 'vn' to 'vi' for Laravel locale (lang directory uses 'vi')
     *
     * @param  string  $lang  Language code
     * @return string Normalized language code (default: 'en')
     */
    public static function normalizeLanguageCode(string $lang): string {
        $langCode = explode('-', $lang)[0];

        // Map 'vn' to 'vi' for Laravel locale (lang directory uses 'vi')
        if ($langCode === 'vn') {
            $langCode = 'vi';
        }

        return in_array($langCode, self::SUPPORTED_LANGUAGES) ? $langCode : 'en';
    }

    /**
     * Get setting key for a language code
     * Maps 'vi' to 'vn' for consistency with existing setting keys
     *
     * @param  string  $langCode  Language code
     * @return string Setting key
     */
    public static function getSettingKey(string $langCode): string {
        // Map 'vi' to 'vn' for setting keys (existing data uses 'vn')
        $key = ($langCode === 'vi') ? 'vn' : $langCode;

        return self::LANGUAGE_SETTING_MAP[$key] ?? self::LANGUAGE_SETTING_MAP['en'];
    }

    /**
     * Get all language setting keys
     *
     * @return array<string, string> Language code => setting key mapping
     */
    public static function getLanguageSettingMap(): array {
        return self::LANGUAGE_SETTING_MAP;
    }

    /**
     * Get language display name
     *
     * @param  string  $code  Language code
     * @return string Language display name
     */
    public static function getLanguageName(string $code): string {
        return self::LANGUAGE_NAMES[$code] ?? $code;
    }

    /**
     * Get policy title based on language
     *
     * @param  string  $code  Language code
     * @return string Policy title
     */
    public static function getPolicyTitle(string $code): string {
        return self::POLICY_TITLES[$code] ?? self::POLICY_TITLES['en'];
    }

    /**
     * Get "Last updated" text based on language
     *
     * @param  string  $code  Language code
     * @return string "Last updated" text
     */
    public static function getLastUpdatedText(string $code): string {
        return self::LAST_UPDATED_TEXTS[$code] ?? self::LAST_UPDATED_TEXTS['en'];
    }

    /**
     * Format date based on language
     * Uses DateTimeExt constants for date formatting where applicable
     *
     * @param  Carbon|null  $date  Date to format
     * @param  string  $code  Language code
     * @return string Formatted date
     */
    public static function formatDate(?Carbon $date, string $code): string {
        if (!$date) {
            $date = now();
        }

        // Special handling for Japanese date format
        if ($code === 'ja') {
            return $date->format('Y') . '年' . $date->format('m') . '月' . $date->format('d') . '日';
        }

        // Get format from constants (uses DateTimeExt constants where applicable)
        $format = self::DATE_FORMATS[$code] ?? self::DATE_FORMATS['en'];

        // Use Carbon format directly (DateTimeExt constants are already used in DATE_FORMATS)
        return $date->format($format);
    }

    /**
     * Get supported languages
     *
     * @return array<string> Supported language codes
     */
    public static function getSupportedLanguages(): array {
        return self::SUPPORTED_LANGUAGES;
    }
}
