<?php

namespace App\Utils;

use Exception;

class CommonProcess {
    /**
     * Get array gender
     *
     * @return array Key=>Value array
     */
    public static function getArrayGender() {
        return [
            DomainConst::GENDER_MALE   => '男性',
            DomainConst::GENDER_FEMALE => '女性',
            DomainConst::GENDER_OTHER  => 'その他',
        ];
    }

    /**
     * Get value in array or object
     *
     * @param  mixed  $data  Array of data
     * @param  string  $key  Value of key
     * @param  mixed  $defaultValue  Default value
     * @return mixed Value after get from array
     */
    public static function getValue($data, $key, $defaultValue = '') {
        if (is_array($data) && isset($data[$key])) {
            return $data[$key];
        }

        if (is_object($data) && isset($data->$key)) {
            return $data->$key;
        }

        return $defaultValue;
    }

    /**
     * Generate temp password
     *
     * @return string
     */
    private static function generateTempPassword() {
        return uniqid(' ', true);
    }

    /**
     * Generate uniq id
     *
     * @param  int  $len  Length of return value
     * @return string
     */
    public static function generateUniqId($len = 13) {
        if (function_exists('random_bytes')) {
            $bytes = random_bytes(ceil($len / 2));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes(ceil($len / 2));
        } else {
            // Fallback for older PHP versions or systems without strong crypto
            return self::generateTempPassword();
        }

        $retVal = substr(bin2hex($bytes), 0, $len);

        return strtoupper($retVal);
    }

    /**
     * Get IP address of current user
     *
     * @return string IP address
     */
    public static function getUserIP(): string {
        return request()->ip();
    }

    /**
     * Get country of current user from IP address
     *
     * @param  string  $ip  IP address
     * @return string Country name
     */
    public static function getUserCountry(string $ip): string {
        if (empty($ip)) {
            return '';
        }

        try {
            $url     = 'http://www.geoplugin.net/json.gp?ip=' . urlencode($ip);
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                ],
            ]);
            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                return '';
            }

            $details = json_decode($response, false);

            return $details->geoplugin_countryName ?? '';
        } catch (Exception) {
            return '';
        }
    }
}
