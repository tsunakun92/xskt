<?php

namespace App\Utils;

use Illuminate\Support\Str;

class StringExt {
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
            return Str::random($len);
        }

        $retVal = substr(bin2hex($bytes), 0, $len);

        return strtoupper($retVal);
    }
}
