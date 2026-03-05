<?php

return [
    /*
     * HTTP Logging Channel
     * Logs are stored in storage/logs/http/ directory
     */
    'http'     => [
        'driver' => 'daily',
        'path'   => storage_path('logs/http/http.log'),
        'level'  => env('HTTP_LOG_LEVEL', 'info'),
        'days'   => env('HTTP_LOG_DAYS', 30), // Keep logs for 30 days
    ],

    /*
     * Database Logging Channel
     * Logs are stored in storage/logs/database/ directory
     */
    'database' => [
        'driver' => 'daily',
        'path'   => storage_path('logs/database/database.log'),
        'level'  => env('DATABASE_LOG_LEVEL', 'debug'),
        'days'   => env('DATABASE_LOG_DAYS', 30), // Keep logs for 30 days
    ],

    /*
     * Cache Logging Channel
     * Logs are stored in storage/logs/cache/ directory
     */
    'cache'    => [
        'driver' => 'daily',
        'path'   => storage_path('logs/cache/cache.log'),
        'level'  => env('CACHE_LOG_LEVEL', 'info'),
        'days'   => env('CACHE_LOG_DAYS', 14), // Keep logs for 14 days
    ],

    /*
     * API Logging Channel
     * Logs are stored in storage/logs/api/ directory
     */
    'api'      => [
        'driver' => 'daily',
        'path'   => storage_path('logs/api/api.log'),
        'level'  => env('API_LOG_LEVEL', 'info'),
        'days'   => env('API_LOG_DAYS', 30), // Keep logs for 30 days
    ],
];
