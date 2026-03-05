<?php

namespace App\Utils;

use DateTime;
use Exception;
use Illuminate\Support\Carbon;

use Modules\Logging\Utils\LogHandler;

class DateTimeExt {
    //-----------------------------------------------------
    // Constants
    //-----------------------------------------------------
    /** Date format */
    const DATE_FORMAT_1 = 'Y-m-d H:i:s';

    /** Date format */
    const DATE_FORMAT_2 = 'dd/mm/yy';

    /** Date format */
    const DATE_FORMAT_3 = 'd/m/Y';

    /** Date format */
    const DATE_FORMAT_4 = 'Y-m-d';

    /** Date format */
    const DATE_FORMAT_5 = 'd \t\h\g m\, Y';

    /** Date format */
    const DATE_FORMAT_6 = 'Y/m/d';

    /** Date format */
    const DATE_FORMAT_7 = 'Y/m/d H:i:s';

    /** Date format */
    const DATE_FORMAT_8 = 'H:i\, d \t\h\g m\, Y';

    /** Date format */
    const DATE_FORMAT_9 = 'YmdHis';

    /** Date format */
    const DATE_FORMAT_10 = 'Ymd';

    /** Date format */
    const DATE_FORMAT_11 = 'd/m/Y H:i';

    /** Date format */
    const DATE_FORMAT_12 = 'yy-mm-dd';

    /** Date format */
    const DATE_FORMAT_13 = 'm/Y';

    /** Date format */
    const DATE_FORMAT_14 = 'mm/yy';

    /** Date format */
    const DATE_FORMAT_15 = 'yy';

    /** Date format */
    const DATE_FORMAT_16 = 'ddmmyyyy';

    /** Date format */
    const DATE_FORMAT_17 = 'dd/MM/yyyy';

    /** Date format */
    const DATE_FORMAT_18 = 'Y.m.d';

    /** Date format */
    const DATE_FORMAT_19 = 'm/d/Y H:i:s';

    /** Date format */
    const DATE_FORMAT_20 = 'Y/m/d H:i';

    /** Date format */
    const DATE_FORMAT_21 = 'H:i';

    /** Date format */
    const DATE_FORMAT_22 = 'Y';

    /** Date format */
    const DATE_FORMAT_23 = 'Y-m';

    /** Date format */
    const DATE_FORMAT_24 = 'm';

    /** Date format */
    const DATE_FORMAT_25 = 'd';

    /** Date format */
    const DATE_FORMAT_26 = 'm/d';

    /** Date format for Widget */
    const DATE_FORMAT_WIDGET_1 = 'dd/mm/yyyy hh:ii';

    /** Date format for Widget */
    const DATE_FORMAT_WIDGET_2 = 'yyyy-mm-dd hh:ii:ss';

    /** Date format for Widget */
    const DATE_FORMAT_WIDGET_3 = 'dd/mm/yyyy';

    /** Date default value null */
    const DATE_DEFAULT_NULL = '0000-00-00';

    /** Date default value null */
    const DATE_DEFAULT_NULL_FULL = '0000-00-00 00:00:00';

    /** Date default value null */
    const DATE_FORMAT_3_NULL = '00/00/0000';

    /** Date default year value null */
    const DATE_DEFAULT_YEAR_NULL = '0000';

    /** Time default value null */
    const TIME_DEFAULT_START_NULL = '00:00';

    /** Time default value null */
    const TIME_DEFAULT_END_NULL = '23:59';

    /** Date format main */
    const DATE_FORMAT_VIEW = self::DATE_FORMAT_5;

    /** Date format backend */
    const DF_BACK_END_SHOW = self::DATE_FORMAT_3;

    /** Date format db */
    const DF_BACK_END_SAVE = self::DATE_FORMAT_4;

    /** Default timezone */
    const DEFAULT_TIMEZONE = 'Asia/Tokyo';

    /**
     * Get value of current date time
     *
     * @param  string  $format  Date time format
     * @return string Date time string (default is DATE_FORMAT_1 - 'Y-m-d H:i:s')
     */
    public static function getCurrentDateTime(string $format = self::DATE_FORMAT_1): string {
        $carbon = new Carbon;
        $carbon->setTimezone(self::DEFAULT_TIMEZONE);

        return $carbon->now()->format($format);
    }

    /**
     * Get value of current date time (of real system)
     *
     * @return string Date time string (default is DATE_FORMAT_1 - 'Y-m-d H:i:s')
     */
    public static function getCurrentDateTimeSystem(): string {
        date_default_timezone_set(self::DEFAULT_TIMEZONE);

        return date(self::DATE_FORMAT_1);
    }

    /**
     * Format datetime
     *
     * @param  string  $date  datetime input
     * @param  string  $format  Date time format
     * @return string Date time string
     */
    public static function formatDateTime(string $date, string $format): string {
        $date = new Carbon($date);
        $date = $date->format($format);

        return $date;
    }

    /**
     * Add day
     *
     * @param  string  $date  The date to add days to in 'Y-m-d H:i:s' format.
     * @param  int  $number  The number of days to add.
     * @param  string  $format  The format of the returned date in 'Y-m-d H:i:s' format.
     * @return string Date time string
     */
    public static function addDays(string $date, int $number, string $format): string {
        return Carbon::parse($date)->addDays($number)->format($format);
    }

    /**
     * Sub day
     *
     * @param  string  $date  datetime input
     * @param  int  $number  number days want to sub
     * @param  string  $format  Date time format
     * @return string Date time string
     */
    public static function subDays(string $date, int $number, string $format): string {
        return Carbon::parse($date)->subDays($number)->format($format);
    }

    /**
     * Add month
     *
     * @param  string  $date  The date to add months to in 'Y-m-d H:i:s' format.
     * @param  int  $number  The number of months to add.
     * @param  string  $format  The format of the returned date in 'Y-m-d H:i:s' format.
     * @return string Date time string
     */
    public static function addMonths(string $date, int $number, string $format): string {
        return Carbon::parse($date)->addMonths($number)->format($format);
    }

    /**
     * Sub month
     *
     * @param  string  $date  datetime input
     * @param  int  $number  number days want to sub
     * @param  string  $format  Date time format
     * @return string Date time string
     */
    public static function subMonths(string $date, int $number, string $format): string {
        return Carbon::parse($date)->subMonths($number)->format($format);
    }

    /**
     * Add year
     *
     * @param  string  $date  The date to add years to in 'Y-m-d H:i:s' format.
     * @param  int  $number  The number of years to add.
     * @param  string  $format  The format of the returned date in 'Y-m-d H:i:s' format.
     * @return string Date time string
     */
    public static function addYears(string $date, int $number, string $format): string {
        return Carbon::parse($date)->addYears($number)->format($format);
    }

    /**
     * Sub year
     *
     * @param  string  $date  datetime input
     * @param  int  $number  number days want to sub
     * @param  string  $format  Date time format
     * @return string Date time string
     */
    public static function subYears(string $date, int $number, string $format): string {
        return Carbon::parse($date)->subYears($number)->format($format);
    }

    /**
     * Convert date time
     *
     * @param  string  $datetime  Date time value to convert
     * @param  string  $fromFormat  Convert from this format
     * @param  string  $toFormat  Convert to this format
     * @return string Date time value after convert
     */
    public static function convertDateTime(string $datetime, string $fromFormat, string $toFormat): string {
        date_default_timezone_set(self::DEFAULT_TIMEZONE);
        if (DateTimeExt::isDateNull($datetime)) {
            return '';
        }
        $converter = DateTime::createFromFormat($fromFormat, $datetime);
        if ($converter instanceof DateTime) {
            $result = $converter->format($toFormat);
            if ($result !== false) {
                return $result;
            }
        }

        LogHandler::warning('Failed to convert datetime', [
            'value'       => $datetime,
            'from_format' => $fromFormat,
            'to_format'   => $toFormat,
        ]);

        return '';
    }

    /**
     * Check if a date value from database (mysql) is null
     *
     * @param  string  $date  Date value
     * @return bool True if date is '0000-00-00', False otherwise
     */
    public static function isDateNull(?string $date): bool {
        return !isset($date) || $date == self::DATE_DEFAULT_NULL
            || $date == self::DATE_DEFAULT_NULL_FULL;
    }

    /**
     * Difference days between two date
     *
     * @param  string  $date1  Date time 1
     * @param  string  $date2  Date time 2
     * @return int Difference in days
     */
    public static function diffDate(string $date1, string $date2): int {
        $date1 = new Carbon($date1);
        $date2 = new Carbon($date2);

        return $date1->diffInDays($date2);
    }

    /**
     * Difference months between two date
     *
     * @param  string  $date1  Date time 1
     * @param  string  $date2  Date time 2
     * @return int Difference in months
     */
    public static function diffMonth(string $date1, string $date2): int {
        $date1 = new Carbon($date1);
        $date2 = new Carbon($date2);

        return $date1->diffInMonths($date2);
    }

    /**
     * Difference years between two date
     *
     * @param  string  $date1  Date time 1
     * @param  string  $date2  Date time 2
     * @return int Difference in years
     */
    public static function diffYear(string $date1, string $date2): int {
        $date1 = new Carbon($date1);
        $date2 = new Carbon($date2);

        return $date1->diffInYears($date2);
    }

    /**
     * Get last day of month
     *
     * @param  string  $date  Date time
     * @return string Last day of month
     */
    public static function getLastDayOfMonth(string $date, string $format = self::DATE_FORMAT_4): string {
        $date = new Carbon($date);

        return $date->endOfMonth()->format($format);
    }

    /**
     * Get first day of month
     *
     * @param  string  $date  Date time
     * @return string First day of month
     */
    public static function getFirstDayOfMonth(string $date, string $format = self::DATE_FORMAT_4): string {
        $date = new Carbon($date);

        return $date->startOfMonth()->format($format);
    }

    /**
     * Get first day of year
     *
     * @param  string  $date  Date time
     * @return string First day of year
     */
    public static function getFirstDayOfYear(string $date, string $format = self::DATE_FORMAT_4): string {
        $date = new Carbon($date);

        return $date->startOfYear()->format($format);
    }

    /**
     * Get last day of year
     *
     * @param  string  $date  Date time
     * @return string Last day of year
     */
    public static function getLastDayOfYear(string $date, string $format = self::DATE_FORMAT_4): string {
        $date = new Carbon($date);

        return $date->endOfYear()->format($format);
    }

    /**
     * Check if start date and start time are valid.
     *
     * @param  string  $startDate
     * @param  string  $startTime
     * @param  string  $endDate
     * @param  string  $endTime
     * @return bool
     */
    public static function validateStartBeforeEnd(string $startDate, string $startTime, string $endDate, string $endTime): bool {
        $startDateTime = new Carbon("{$startDate} {$startTime}");
        $endDateTime   = new Carbon("{$endDate} {$endTime}");

        return $startDateTime >= $endDateTime;
    }

    /**
     * Format a given datetime into a specific date and time format
     *
     * @param  mixed  $datetime
     * @param  mixed  $dateFormat
     * @param  mixed  $timeFormat
     * @return array{date: string, time: string}
     */
    public static function formatDateAndTime($datetime, $dateFormat = self::DATE_FORMAT_6, $timeFormat = self::DATE_FORMAT_21) {
        if (empty($datetime)) {
            return [
                'date' => '',
                'time' => '',
            ];
        }

        // Convert datetime string to timezone to DEFAULT_TIMEZONE
        $carbon = Carbon::parse($datetime)->setTimezone(self::DEFAULT_TIMEZONE);
        // dd($datetime);

        return [
            'date' => $carbon->translatedFormat($dateFormat),
            'time' => $carbon->format($timeFormat),
        ];
    }

    /**
     * Check if string is date
     *
     * @param  mixed  $date
     * @return bool
     */
    public static function isValidDate(mixed $date): bool {
        // Treat empty/placeholder values as NOT a valid date
        if ($date === null) {
            return false;
        }
        // Check if string is date
        if (is_string($date)) {
            $trimmed = trim($date);
            if ($trimmed === '' || $trimmed === '-' || $trimmed === self::DATE_DEFAULT_NULL || $trimmed === self::DATE_DEFAULT_NULL_FULL) {
                return false;
            }
        }
        // Check parseable by Carbon
        try {
            new Carbon($date);

            return true;
        } catch (Exception $e) {
            LogHandler::debug('Invalid date value in DateTimeExt::isValidDate', [
                'value'   => $date,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if a datetime is after or equal to current datetime.
     *
     * @param  string  $datetime  Datetime string to check
     * @return bool True if datetime is after or equal to current datetime, False otherwise
     */
    public static function isAfterOrEqualNow(string $datetime): bool {
        if (!self::isValidDate($datetime)) {
            return false;
        }

        // Get current datetime with timezone (Asia/Tokyo)
        $now = self::getCurrentDateTime(self::DATE_FORMAT_1);

        // Parse both datetimes with timezone consideration
        $datetimeObj = Carbon::parse($datetime)->setTimezone(self::DEFAULT_TIMEZONE);
        $nowObj      = Carbon::parse($now)->setTimezone(self::DEFAULT_TIMEZONE);

        // Compare: datetime must be >= now
        return $datetimeObj->gte($nowObj);
    }
}
