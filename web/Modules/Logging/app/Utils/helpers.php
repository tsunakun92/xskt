<?php

/**
 * Override helpers to fix deprecation warnings
 * This file is loaded before vendor autoload to override function
 *
 * Note: Fixes PHP 8.x deprecation warning about nullable parameter types
 * by using explicit nullable type (?string) instead of implicit nullable
 */
if (!function_exists('laravel_version')) {
    /**
     * Get laravel version or check if the same version
     *
     * @param  string|null  $version
     * @return string|bool
     */
    function laravel_version(?string $version = null): string|bool {
        $appVersion = app()->version();

        if (is_null($version)) {
            return $appVersion;
        }

        return substr($appVersion, 0, strlen($version)) === $version;
    }
}
