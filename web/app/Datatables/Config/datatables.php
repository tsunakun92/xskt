<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Datatables Package Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the datatables package functionality.
    | These settings control default behaviors and can be customized per project.
    |
     */

    /**
     * Default pagination settings
     */
    'pagination'  => [
        'default_page_size'      => 10,
        'page_size_options'      => [5, 10, 25, 50, 100],
        'pagination_range'       => 1,
        'show_ellipsis'          => true,
        'min_pages_for_ellipsis' => 7,
    ],

    /**
     * Default component settings
     */
    'defaults'    => [
        'empty_message'         => 'No data available',
        'loading_message'       => 'Loading...',
        'error_message'         => 'An error occurred',
        'not_found_message'     => 'Not found',
        'please_select_message' => 'Please select',
    ],

    /**
     * Cache settings for select-search components
     */
    'cache'       => [
        'enabled' => true,
        'ttl'     => 300, // 5 minutes
    ],

    /**
     * Session settings for filter state storage
     */
    'session'     => [
        'enabled'           => true,
        'ttl'               => 3600,          // 1 hour
        'max_sessions'      => 50,       // Maximum sessions per user
        'cleanup_threshold' => 100, // Cleanup when metadata exceeds this
    ],

    /**
     * Performance settings
     */
    'performance' => [
        'enable_loading_states' => false, // Disabled for better UX
        'max_parallel_requests' => 3,
    ],

    /**
     * View settings
     */
    'views'       => [
        'theme'     => 'tailwind',
        'namespace' => 'datatables',
    ],
];
