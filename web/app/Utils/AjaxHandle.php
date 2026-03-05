<?php

namespace App\Utils;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;
use ReflectionClass;
use Throwable;

use Modules\Logging\Utils\LogHandler;

class AjaxHandle {
    //-----------------------------------------------------
    // Constants
    //-----------------------------------------------------
    /** Status for success response */
    public const STATUS_SUCCESS = 1;

    /** Status for failure response */
    public const STATUS_FAILURE = 0;

    //-----------------------------------------------------
    // Methods
    //-----------------------------------------------------
    /**
     * Return success response and log info
     *
     * @param  string  $message  Success message
     * @param  mixed  $data  Response data (array, object, collection, etc.)
     * @param  array  $custom  Additional custom fields to merge into response
     * @return JsonResponse
     */
    public static function success(string $message = 'Success', $data = [], array $custom = []): JsonResponse {
        // Ensure message is not empty
        $message = $message ?: 'Success';

        // Build response with status field
        $response = array_merge([
            'status'  => self::STATUS_SUCCESS,
            'message' => $message,
            'data'    => $data,
        ], $custom);

        // Log success with truncated data
        $logData = self::truncateDataForLog($data);
        LogHandler::info('Ajax success response', [
            'message'      => $message,
            'data_summary' => $logData,
            'data_type'    => gettype($data),
            'data_size'    => self::getDataSize($data),
        ]);

        return response()->json($response);
    }

    /**
     * Return error response and log error
     *
     * @param  string  $message  Error message
     * @param  Throwable|string|null  $exception  Exception object or error string (optional)
     * @param  array  $custom  Additional custom fields to merge into response
     * @param  int  $code  HTTP status code (default: 500)
     * @return JsonResponse
     */
    public static function error(
        string $message = 'An error occurred',
        $exception = null,
        array $custom = [],
        int $code = 500
    ): JsonResponse {
        // Ensure message is not empty
        $message = $message ?: 'An error occurred';

        // Build response
        $response = array_merge([
            'status'  => self::STATUS_FAILURE,
            'message' => $message,
        ], $custom);

        // Log error with exception details
        $logContext = [
            'message' => $message,
            'code'    => $code,
        ];

        if ($exception instanceof Throwable) {
            // Log with exception object details
            $logContext['exception'] = [
                'message' => $exception->getMessage(),
                'code'    => $exception->getCode(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => self::truncateDataForLog($exception->getTraceAsString()),
            ];
            LogHandler::error('Ajax error response: ' . $exception->getMessage(), $logContext);
        } elseif (is_string($exception) && !empty($exception)) {
            // Log with string error message
            $logContext['error'] = $exception;
            LogHandler::error('Ajax error response: ' . $exception, $logContext);
        } else {
            // Log with message only
            LogHandler::error('Ajax error response', $logContext);
        }

        return response()->json($response, $code);
    }

    /**
     * Return validation error response and log warning
     *
     * @param  array|Validator  $errors  Validation errors array or Validator instance
     * @param  string  $message  Error message (optional)
     * @param  array  $custom  Additional custom fields to merge into response
     * @return JsonResponse
     */
    public static function validationError($errors = [], string $message = 'Validation failed', array $custom = []): JsonResponse {
        // Ensure message is not empty
        $message = $message ?: 'Validation failed';

        // Convert Validator instance to errors array
        if ($errors instanceof Validator) {
            $errors = $errors->errors()->toArray();
        }

        // Ensure errors is an array
        if (!is_array($errors)) {
            $errors = [];
        }

        // Build response
        $response = array_merge([
            'status'  => self::STATUS_FAILURE,
            'message' => $message,
            'errors'  => $errors,
        ], $custom);

        // Log validation warning with truncated errors
        $logErrors = self::truncateDataForLog($errors);
        LogHandler::warning('Ajax validation error response', [
            'message'        => $message,
            'errors_summary' => $logErrors,
            'errors_count'   => count($errors, COUNT_RECURSIVE),
        ]);

        return response()->json($response, 422);
    }

    /**
     * Truncate data for logging purposes to prevent log files from growing too large
     *
     * Handles different data types:
     * - Arrays/Collections: Log structure (keys, count) instead of all items if too large
     * - Objects: Log class name and properties summary
     * - Strings: Truncate and add "... (truncated)" if exceeds maxLength
     *
     * @param  mixed  $data  Data to truncate
     * @param  int  $maxLength  Maximum length (default: DomainConst::MAX_LENGTH_LOG_DATA)
     * @return mixed Truncated data summary
     */
    protected static function truncateDataForLog($data, ?int $maxLength = null): mixed {
        if ($maxLength === null) {
            $maxLength = DomainConst::MAX_LENGTH_LOG_DATA;
        }

        // Handle null
        if ($data === null) {
            return null;
        }

        // Handle arrays and collections
        if (is_array($data) || (is_object($data) && method_exists($data, 'toArray'))) {
            $array = is_array($data) ? $data : $data->toArray();

            // Try to convert to JSON string to check size
            $jsonString   = json_encode($array, JSON_UNESCAPED_UNICODE);
            $stringLength = mb_strlen($jsonString);

            if ($stringLength > $maxLength) {
                // Log structure summary instead of full data
                $count = count($array);
                $keys  = array_keys(array_slice($array, 0, 10, true)); // First 10 keys

                return [
                    'type'        => 'array',
                    'count'       => $count,
                    'sample_keys' => $keys,
                    'size_bytes'  => $stringLength,
                    'truncated'   => true,
                ];
            }

            // Use existing helper function for recursive trimming
            return trim_array_object($array, DomainConst::MAX_LENGTH_LOG_DATA, 10, 3);
        }

        // Handle objects
        if (is_object($data)) {
            $className = get_class($data);

            // Try to get object summary
            try {
                $reflection    = new ReflectionClass($data);
                $properties    = $reflection->getProperties();
                $propertyNames = array_map(fn($prop) => $prop->getName(), $properties);

                // Try to serialize to check size
                $serialized       = serialize($data);
                $serializedLength = mb_strlen($serialized);

                if ($serializedLength > $maxLength) {
                    return [
                        'type'             => 'object',
                        'class'            => $className,
                        'properties'       => array_slice($propertyNames, 0, 10),
                        'properties_count' => count($propertyNames),
                        'size_bytes'       => $serializedLength,
                        'truncated'        => true,
                    ];
                }
            } catch (Throwable $e) {
                // If reflection fails, just return class name
                return [
                    'type'  => 'object',
                    'class' => $className,
                    'error' => 'Could not analyze object',
                ];
            }

            // For smaller objects, convert to array and trim
            if (method_exists($data, 'toArray')) {
                return trim_array_object($data->toArray(), DomainConst::MAX_LENGTH_LOG_DATA, 10, 3);
            }

            return [
                'type'  => 'object',
                'class' => $className,
            ];
        }

        // Handle strings
        if (is_string($data)) {
            if (mb_strlen($data) > $maxLength) {
                return mb_substr($data, 0, $maxLength) . '... (truncated, ' . (mb_strlen($data) - $maxLength) . ' more chars)';
            }

            return $data;
        }

        // For other types (int, float, bool), return as is
        return $data;
    }

    /**
     * Get data size information for logging
     *
     * @param  mixed  $data  Data to analyze
     * @return array Size information
     */
    protected static function getDataSize($data): array {
        $size = [
            'type' => gettype($data),
        ];

        if (is_array($data) || (is_object($data) && method_exists($data, 'toArray'))) {
            $array                   = is_array($data) ? $data : $data->toArray();
            $size['count']           = count($array);
            $jsonString              = json_encode($array, JSON_UNESCAPED_UNICODE);
            $size['json_size_bytes'] = mb_strlen($jsonString);
        } elseif (is_string($data)) {
            $size['length']     = mb_strlen($data);
            $size['size_bytes'] = mb_strlen($data);
        } elseif (is_object($data)) {
            try {
                $serialized                    = serialize($data);
                $size['serialized_size_bytes'] = mb_strlen($serialized);
            } catch (Throwable $e) {
                $size['error'] = 'Could not serialize';
            }
        }

        return $size;
    }
}
