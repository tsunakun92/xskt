<?php

namespace Modules\Api\Utils;

use Illuminate\Support\Facades\Log;

use App\Utils\CommonProcess;
use Modules\Api\Models\ApiRequestLog;

/**
 * API Logger utility class
 * Replaces ApiRequestLog with logging channel "api"
 */
class ApiLogger {
    /**
     * Create a log entry for an API request
     *
     * @param  int|null  $userId  The ID of the user making the request
     * @param  string  $method  The method being called
     * @param  array  $args  The arguments to log
     * @param  string|null  $ipAddress  The IP address of the request
     * @param  string|null  $country  The country of the request
     * @return void
     */
    public static function logRequest($userId, $method, $args, $ipAddress = null, $country = null) {
        // Get IP address from request
        $ipAddress = $ipAddress ?? CommonProcess::getUserIP();
        // Get country from request
        $country = $country ?? CommonProcess::getUserCountry($ipAddress);

        // Mask sensitive fields in arguments
        $maskedArgs = self::maskSensitiveJson(json_encode($args));

        $context = [
            'user_id'    => $userId,
            'method'     => $method,
            'content'    => $maskedArgs,
            'ip_address' => $ipAddress,
            'country'    => $country,
            'type'       => 'request',
        ];

        Log::channel('api')->info('API Request', $context);

        // Also store request log in database
        ApiRequestLog::create([
            'ip_address' => $ipAddress,
            'country'    => $country,
            'user_id'    => $userId,
            'method'     => $method,
            'content'    => $maskedArgs,
            'status'     => ApiRequestLog::STATUS_ACTIVE,
        ]);
    }

    /**
     * Update the log entry with the response data
     *
     * @param  string  $method  The method that was called
     * @param  mixed  $req  The request data to log
     * @param  mixed  $res  The response data to log
     * @param  string  $resDate  The time response
     * @param  mixed  $userId  The ID of the user making the request
     * @return void
     */
    public static function logResponse($method, $req, $res, $resDate, $userId = null, ?string $country = null) {
        // Get IP address and country
        $ipAddress = CommonProcess::getUserIP();
        $country   = $country ?? CommonProcess::getUserCountry($ipAddress);
        // Mask sensitive data in request and response
        $maskedReq = self::maskSensitiveJson(is_string($req) ? $req : json_encode($req));
        $maskedRes = self::maskSensitiveJson(is_string($res) ? $res : json_encode($res));

        $context = [
            'user_id'        => $userId,
            'method'         => $method,
            'content'        => $maskedReq,
            'response'       => $maskedRes,
            'responsed_date' => $resDate,
            'ip_address'     => $ipAddress,
            'country'        => $country,
            'type'           => 'response',
        ];

        Log::channel('api')->info('API Response', $context);

        // Also store response log in database
        ApiRequestLog::create([
            'ip_address'     => $ipAddress,
            'country'        => $country,
            'user_id'        => $userId,
            'method'         => $method,
            'content'        => $maskedReq,
            'response'       => $maskedRes,
            'status'         => ApiRequestLog::STATUS_ACTIVE,
            'responsed_date' => $resDate,
        ]);
    }

    /**
     * Create a complete log entry (request + response)
     *
     * @param  string  $method  The method being called
     * @param  mixed  $req  The request data to log
     * @param  mixed  $res  The response data to log
     * @param  string  $resDate  The time response
     * @param  mixed  $userId  The ID of the user making the request
     * @return void
     */
    public static function logOne($method, $req, $res, $resDate, $userId = null, ?string $country = null) {
        // Get IP address and country
        $ipAddress = CommonProcess::getUserIP();
        $country   = $country ?? CommonProcess::getUserCountry($ipAddress);
        // Mask sensitive data in request and response
        $maskedReq = self::maskSensitiveJson(is_string($req) ? $req : json_encode($req));
        $maskedRes = self::maskSensitiveJson(is_string($res) ? $res : json_encode($res));

        // If content contains GraphQL query, also mask GraphQL query string
        $decodedReq = json_decode($maskedReq, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($decodedReq['query']) && is_string($decodedReq['query'])) {
            $decodedReq['query'] = self::maskSensitiveGraphQL($decodedReq['query']);
            $maskedReq           = json_encode($decodedReq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $context = [
            'user_id'        => $userId,
            'method'         => $method,
            'content'        => $maskedReq,
            'response'       => $maskedRes,
            'responsed_date' => $resDate,
            'ip_address'     => $ipAddress,
            'country'        => $country,
            'type'           => 'complete',
        ];

        Log::channel('api')->info('API Request/Response', $context);

        // Also store complete request/response log in database
        ApiRequestLog::create([
            'ip_address'     => $ipAddress,
            'country'        => $country,
            'user_id'        => $userId,
            'method'         => $method,
            'content'        => $maskedReq,
            'response'       => $maskedRes,
            'status'         => ApiRequestLog::STATUS_ACTIVE,
            'responsed_date' => $resDate,
        ]);
    }

    /**
     * Mask sensitive fields in array/object data
     *
     * @param  mixed  $data  Array or object data
     * @return mixed Masked data
     */
    public static function maskSensitiveData($data) {
        $sensitiveFields = ['password', 'password_confirmation', 'current_password', 'new_password', 'pwd', 'token', 'api_key', 'secret'];

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (in_array(strtolower($key), $sensitiveFields)) {
                    if (!empty($value) && is_string($value)) {
                        $data[$key] = '******';
                    }
                } elseif (is_array($value) || is_object($value)) {
                    $data[$key] = self::maskSensitiveData($value);
                }
            }
        } elseif (is_object($data)) {
            foreach ($data as $key => $value) {
                if (in_array(strtolower($key), $sensitiveFields)) {
                    if (!empty($value) && is_string($value)) {
                        $data->$key = '******';
                    }
                } elseif (is_array($value) || is_object($value)) {
                    $data->$key = self::maskSensitiveData($value);
                }
            }
        }

        return $data;
    }

    /**
     * Mask sensitive fields in JSON string
     *
     * @param  string  $jsonString  JSON string to mask
     * @return string Masked JSON string
     */
    public static function maskSensitiveJson($jsonString) {
        if (empty($jsonString)) {
            return $jsonString;
        }

        // Try to decode JSON first
        $decoded = json_decode($jsonString, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Mask sensitive data in decoded array
            $masked = self::maskSensitiveData($decoded);

            // Re-encode to JSON
            return json_encode($masked, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // If not valid JSON, use regex pattern matching
        return self::maskPasswordInQueryString($jsonString);
    }

    /**
     * Mask sensitive fields in GraphQL query string
     *
     * @param  string  $query  GraphQL query string
     * @return string Masked GraphQL query
     */
    public static function maskSensitiveGraphQL($query) {
        if (empty($query)) {
            return $query;
        }

        $sensitiveFields = ['password', 'password_confirmation', 'current_password', 'new_password', 'pwd', 'token', 'api_key', 'secret'];

        // Pattern to match field names in GraphQL: field_name: "value"
        foreach ($sensitiveFields as $field) {
            // Match: field_name: "value" or field_name: "value"
            $pattern = '/(\b' . preg_quote($field, '/') . '\s*:\s*)"([^"]+)"/i';
            $query   = preg_replace($pattern, '$1"******"', $query);
        }

        return $query;
    }

    /**
     * Masks the password in a query string (legacy method, kept for backward compatibility)
     *
     * @param  string  $query  The query string containing the password
     * @return string The modified query string with the password masked if it exists
     */
    public static function maskPasswordInQueryString($query) {
        // Hide fields
        $sensitiveFields = ['password', 'password_confirmation', 'current_password', 'new_password', 'pwd', 'token', 'api_key', 'secret'];

        // Pattern to match field names followed by colon and quoted value
        // Matches: "password": "value" or password: "value"
        $pattern = '/(["\']?)(' . implode('|', array_map('preg_quote', $sensitiveFields)) . ')(["\']?):\s*"([^"]*)"/i';

        return preg_replace_callback($pattern, function ($matches) {
            // Check if the password is not empty, if so, replace it with '******', otherwise keep it unchanged
            $quoteBefore = $matches[1];
            $fieldName   = $matches[2];
            $quoteAfter  = $matches[3];
            $value       = $matches[4];

            if (!empty($value)) {
                return $quoteBefore . $fieldName . $quoteAfter . ': "******"';
            }

            return $matches[0];
        }, $query);
    }
}
