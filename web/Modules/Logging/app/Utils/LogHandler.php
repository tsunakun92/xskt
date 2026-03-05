<?php

namespace Modules\Logging\Utils;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Centralized logging handler for the application
 * Provides standardized logging with user context
 */
class LogHandler {
    /**
     * Log levels
     */
    const LEVEL_DEBUG    = 'debug';

    const LEVEL_INFO     = 'info';

    const LEVEL_WARNING  = 'warning';

    const LEVEL_ERROR    = 'error';

    const LEVEL_CRITICAL = 'critical';

    /**
     * Log channels
     */
    const CHANNEL_DEFAULT  = 'stack';

    const CHANNEL_HTTP     = 'http';

    const CHANNEL_DATABASE = 'database';

    const CHANNEL_CACHE    = 'cache';

    const CHANNEL_API      = 'api';

    /**
     * Log debug message
     *
     * @param  string  $message
     * @param  array  $context
     * @param  string|null  $channel
     */
    public static function debug(string $message, array $context = [], ?string $channel = null) {
        self::log(self::LEVEL_DEBUG, $message, $context, $channel);
    }

    /**
     * Log info message
     *
     * @param  string  $message
     * @param  array  $context
     * @param  string|null  $channel
     */
    public static function info(string $message, array $context = [], ?string $channel = null) {
        self::log(self::LEVEL_INFO, $message, $context, $channel);
    }

    /**
     * Log warning message
     *
     * @param  string  $message
     * @param  array  $context
     * @param  string|null  $channel
     */
    public static function warning(string $message, array $context = [], ?string $channel = null) {
        self::log(self::LEVEL_WARNING, $message, $context, $channel);
    }

    /**
     * Log error message
     *
     * @param  string  $message
     * @param  array  $context
     * @param  string|null  $channel
     */
    public static function error(string $message, array $context = [], ?string $channel = null) {
        self::log(self::LEVEL_ERROR, $message, $context, $channel);
    }

    /**
     * Log critical message
     *
     * @param  string  $message
     * @param  array  $context
     * @param  string|null  $channel
     */
    public static function critical(string $message, array $context = [], ?string $channel = null) {
        self::log(self::LEVEL_CRITICAL, $message, $context, $channel);
    }

    /**
     * Log database error
     *
     * @param  string  $message
     * @param  Exception|null  $exception
     * @param  array  $context
     */
    public static function databaseError(string $message, ?Exception $exception = null, array $context = []) {
        $context['exception'] = $exception ? [
            'message' => self::sanitizeExceptionMessage($exception->getMessage()),
            'code'    => $exception->getCode(),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
        ] : null;

        self::log(self::LEVEL_ERROR, $message, $context, self::CHANNEL_DATABASE);
    }

    /**
     * Log cache operation
     *
     * @param  string  $message
     * @param  array  $context
     */
    public static function cache(string $message, array $context = []) {
        self::log(self::LEVEL_INFO, $message, $context, self::CHANNEL_CACHE);
    }

    /**
     * Main log method with user context
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @param  string|null  $channel
     */
    protected static function log(string $level, string $message, array $context = [], ?string $channel = null) {
        // Add user context if available
        $user = Auth::user();
        if ($user) {
            $context['user'] = [
                'id'       => $user->id,
                'username' => $user->username ?? $user->name ?? $user->email ?? 'N/A',
            ];
        }

        // Add request context
        if (app()->runningInConsole()) {
            $context['environment'] = 'console';
        } else {
            $request = request();
            if ($request) {
                $context['request'] = [
                    'method' => $request->method(),
                    'uri'    => $request->path(),
                    'ip'     => $request->ip(),
                ];
            }
        }

        // Log with channel
        $logger = $channel ? Log::channel($channel) : Log::getFacadeRoot();
        $logger->log($level, $message, $context);
    }

    /**
     * Remove raw SQL portions from exception messages
     *
     * @param  string  $message
     * @return string
     */
    protected static function sanitizeExceptionMessage(string $message): string {
        return preg_replace('/\s*\(SQL:.*$/s', '', $message) ?? $message;
    }
}
