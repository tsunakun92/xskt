<?php

return [
    /*
     * Determine if the http-logger middleware should be enabled.
     */
    'enabled'          => env('HTTP_LOGGER_ENABLED', true),

    /*
     * The log profile which determines whether a request should be logged.
     * It should implement `LogProfile`.
     *
     * Options:
     * - \Spatie\HttpLogger\LogNonGetRequests::class (only POST, PUT, PATCH, DELETE)
     * - \Modules\Logging\Http\Loggers\LogAllRequests::class (all requests including GET)
     */
    'log_profile'      => Modules\Logging\Http\Loggers\LogAllRequests::class,

    /*
     * The log writer used to write the request to a log.
     * It should implement `LogWriter`.
     *
     * Options:
     * - \Spatie\HttpLogger\DefaultLogWriter::class (default, no user info)
     * - \Modules\Logging\Http\Loggers\CustomLogWriter::class (includes user information)
     */
    'log_writer'       => Modules\Logging\Http\Loggers\CustomLogWriter::class,

    /*
     * The log channel used to write the request.
     */
    'log_channel'      => env('HTTP_LOG_CHANNEL', 'http'),

    /*
     * The log level used to log the request.
     */
    'log_level'        => env('HTTP_LOG_LEVEL', 'info'),

    /*
     * Filter out body fields which will never be logged.
     * Add any sensitive fields here to prevent them from being logged.
     */
    'except'           => [
        'password',
        'password_confirmation',
        'token',
        'api_key',
        'secret',
        'credit_card',
        'cvv',
        'ssn',
        'bank_account',
    ],

    /*
     * List of headers that will be sanitized. For example Authorization, Cookie, Set-Cookie...
     */
    'sanitize_headers' => [],
];
