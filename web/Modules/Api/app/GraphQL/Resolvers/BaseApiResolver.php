<?php

namespace Modules\Api\GraphQL\Resolvers;

use Closure;
use DomainException;
use Exception;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

use App\Entities\Sanctum\PersonalAccessToken;
use Modules\Api\Utils\ApiLogger;
use Modules\Logging\Utils\LogHandler;

/**
 * Base resolver for API GraphQL resolvers.
 *
 * Provides common helpers for validation, error handling, logging and rate limiting.
 */
abstract class BaseApiResolver {
    /**
     * Execute resolver logic with standard try/catch and error logging.
     *
     * @param  string  $actionName
     * @param  array  $context
     * @param  Closure():array  $callback
     * @return array
     */
    protected function execute(string $actionName, array $context, Closure $callback): array {
        try {
            // Execute resolver logic
            $result = $callback();

            // Log API request/response to file and database
            ApiLogger::logOne(
                $actionName,
                request()->all(),
                $result,
                now()->toDateTimeString(),
                auth()->id()
            );

            return $result;
        } catch (Exception $e) {
            LogHandler::error($actionName . ' error occurred', array_merge($context, [
                'error' => $e->getMessage(),
            ]), LogHandler::CHANNEL_API);

            // if it's a domain exception return its message, otherwise generic
            if ($e instanceof DomainException) {
                return apiResponseError($e->getMessage());
            }

            return apiResponseError('An error occurred. Please try again later.');
        }
    }

    /**
     * Validate input data and return null on success or error response on failure.
     *
     * @param  array  $data
     * @param  array  $rules
     * @param  string  $logAction
     * @param  array  $logContext
     * @return array|null
     */
    protected function validateInput(array $data, array $rules, string $logAction, array $logContext = []): ?array {
        /** @var ValidatorContract $validator */
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            LogHandler::warning($logAction . ' validation failed', array_merge($logContext, [
                'errors' => $validator->errors()->toArray(),
            ]), LogHandler::CHANNEL_API);

            return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
        }

        return null;
    }

    /**
     * Check rate limiting using API module configuration with support for default values.
     *
     * @param  string  $configKey
     * @param  string  $rateKey
     * @param  string  $logAction
     * @param  array  $logContext
     * @return array|null
     */
    protected function checkRateLimitFromConfig(string $configKey, string $rateKey, string $logAction, array $logContext = []): ?array {
        $ip  = request()->ip();
        $key = $rateKey . ':' . $ip;

        $defaultMaxAttempts  = (int) config('api.rate_limit.default.max_attempts', 100);
        $defaultDecayMinutes = (int) config('api.rate_limit.default.decay_minutes', 1);

        $maxAttempts  = (int) config("api.rate_limit.{$configKey}.max_attempts", $defaultMaxAttempts);
        $decayMinutes = (int) config("api.rate_limit.{$configKey}.decay_minutes", $defaultDecayMinutes);
        $decaySeconds = $decayMinutes * 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            LogHandler::warning($logAction . ' rate limit exceeded', array_merge($logContext, [
                'ip'      => $ip,
                'seconds' => $seconds,
            ]), LogHandler::CHANNEL_API);

            return apiResponseError("Too many requests. Please try again in {$seconds} seconds.");
        }

        RateLimiter::hit($key, $decaySeconds);

        return null;
    }

    /**
     * Check rate limiting using API module configuration with email-based key.
     *
     * @param  string  $configKey
     * @param  string  $rateKey
     * @param  string  $email
     * @param  string  $logAction
     * @param  array  $logContext
     * @return array|null
     */
    protected function checkRateLimitFromConfigByEmail(string $configKey, string $rateKey, string $email, string $logAction, array $logContext = []): ?array {
        $key = $rateKey . ':' . strtolower($email);

        $defaultMaxAttempts  = (int) config('api.rate_limit.default.max_attempts', 100);
        $defaultDecayMinutes = (int) config('api.rate_limit.default.decay_minutes', 1);

        $maxAttempts  = (int) config("api.rate_limit.{$configKey}.max_attempts", $defaultMaxAttempts);
        $decayMinutes = (int) config("api.rate_limit.{$configKey}.decay_minutes", $defaultDecayMinutes);
        $decaySeconds = $decayMinutes * 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            LogHandler::warning($logAction . ' rate limit exceeded', array_merge($logContext, [
                'email'   => $email,
                'seconds' => $seconds,
            ]), LogHandler::CHANNEL_API);

            return apiResponseError("Too many attempts. Please try again in {$seconds} seconds.");
        }

        RateLimiter::hit($key, $decaySeconds);

        return null;
    }

    /**
     * Convert platform string to integer.
     *
     * @param  string  $platform
     * @return int
     */
    protected function convertPlatformToInt(string $platform): int {
        return PersonalAccessToken::convertStringToInt($platform);
    }

    /**
     * Build paginator info array for GraphQL response.
     *
     * @param  int  $total
     * @param  int  $page
     * @param  int  $limit
     * @return array
     */
    protected function buildPaginatorInfo(int $total, int $page, int $limit): array {
        return [
            'total'       => $total,
            'currentPage' => $page,
            'lastPage'    => (int) max(1, ceil($total / $limit)),
            'perPage'     => $limit,
        ];
    }
}
