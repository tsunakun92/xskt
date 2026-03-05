<?php

return [
    'name'       => 'Api',
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for rate limiting in API endpoints.
    | These values can be overridden in .env file using API_RATE_LIMIT_* keys.
    |
    */

    'rate_limit' => [
        // Default rate limit configuration for APIs
        'default'                  => [
            'max_attempts'  => (int) env('API_RATE_LIMIT_ATTEMPTS', 100),
            'decay_minutes' => (int) env('API_RATE_LIMIT_DECAY_MINUTES', 1),
        ],

        // Specific overrides for each API (optional)
        'register'                 => [
            'max_attempts'  => (int) env('API_RATE_LIMIT_REGISTER_ATTEMPTS', 5),
            'decay_minutes' => (int) env('API_RATE_LIMIT_REGISTER_DECAY', 1),
        ],
        'register_verify'          => [
            'max_attempts'  => (int) env('API_RATE_LIMIT_REGISTER_VERIFY_ATTEMPTS', 10),
            'decay_minutes' => (int) env('API_RATE_LIMIT_REGISTER_VERIFY_DECAY', 1),
        ],
        'send_otp_forgot_password' => [
            'max_attempts'  => (int) env('API_RATE_LIMIT_SEND_OTP_FORGOT_PASSWORD_ATTEMPTS', 5),
            'decay_minutes' => (int) env('API_RATE_LIMIT_SEND_OTP_FORGOT_PASSWORD_DECAY', 1),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for OTP (One-Time Password) expiration time by type.
    | Each OTP type can have different expiration time.
    | These values can be overridden in .env file using API_OTP_EXPIRATION_* keys.
    |
    | OTP Types:
    | - register: OTP for user registration (TYPE_REGISTER = 1)
    | - forgot_password: OTP for password reset (TYPE_FORGOT_PASSWORD = 2)
    |
    */

    'otp'        => [
        'expiration_minutes' => [
            'register'        => (int) env('API_OTP_EXPIRATION_REGISTER_MINUTES', 2),
            'forgot_password' => (int) env('API_OTP_EXPIRATION_FORGOT_PASSWORD_MINUTES', 10),
            'default'         => (int) env('API_OTP_EXPIRATION_MINUTES', 30),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Key Configuration
    |--------------------------------------------------------------------------
    |
    | API key for validating X-API-KEY header in API requests.
    | Set API_KEY in your .env file to enable API key validation.
    | If not set, API key validation will be skipped.
    |
    */

    'api_key'    => env('API_KEY', null),
];
