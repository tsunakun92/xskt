<?php

namespace Tests\Unit\Utils;

use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\Utils\DateTimeExt;

class DateTimeExtTest extends TestCase {
    #[Test]
    public function it_gets_current_date_time() {
        $result = DateTimeExt::getCurrentDateTime();

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);
    }

    #[Test]
    public function it_gets_current_date_time_with_custom_format() {
        $result = DateTimeExt::getCurrentDateTime(DateTimeExt::DATE_FORMAT_4);

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $result);
    }

    #[Test]
    public function it_gets_current_date_time_system() {
        $result = DateTimeExt::getCurrentDateTimeSystem();

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);
    }

    #[Test]
    public function it_formats_date_time() {
        $date = '2024-01-15 10:30:00';
        // formatDateTime takes date and format, not fromFormat and toFormat
        $result = DateTimeExt::formatDateTime($date, DateTimeExt::DATE_FORMAT_3);

        $this->assertEquals('15/01/2024', $result);
    }

    #[Test]
    public function it_adds_days() {
        $date   = '2024-01-15';
        $result = DateTimeExt::addDays($date, 5, DateTimeExt::DATE_FORMAT_4);

        $this->assertEquals('2024-01-20', $result);
    }

    #[Test]
    public function it_subs_days() {
        $date   = '2024-01-15';
        $result = DateTimeExt::subDays($date, 5, DateTimeExt::DATE_FORMAT_4);

        $this->assertEquals('2024-01-10', $result);
    }

    #[Test]
    public function it_adds_months() {
        $date   = '2024-01-15';
        $result = DateTimeExt::addMonths($date, 2, DateTimeExt::DATE_FORMAT_4);

        $this->assertEquals('2024-03-15', $result);
    }

    #[Test]
    public function it_subs_months() {
        $date   = '2024-03-15';
        $result = DateTimeExt::subMonths($date, 2, DateTimeExt::DATE_FORMAT_4);

        $this->assertEquals('2024-01-15', $result);
    }

    #[Test]
    public function it_adds_years() {
        $date   = '2024-01-15';
        $result = DateTimeExt::addYears($date, 1, DateTimeExt::DATE_FORMAT_4);

        $this->assertEquals('2025-01-15', $result);
    }

    #[Test]
    public function it_subs_years() {
        $date   = '2024-01-15';
        $result = DateTimeExt::subYears($date, 1, DateTimeExt::DATE_FORMAT_4);

        $this->assertEquals('2023-01-15', $result);
    }

    #[Test]
    public function it_converts_date_time() {
        $datetime = '2024-01-15 10:30:00';
        $result   = DateTimeExt::convertDateTime($datetime, DateTimeExt::DATE_FORMAT_1, DateTimeExt::DATE_FORMAT_3);

        $this->assertEquals('15/01/2024', $result);
    }

    #[Test]
    public function it_converts_date_time_returns_empty_for_null_date() {
        $result = DateTimeExt::convertDateTime(DateTimeExt::DATE_DEFAULT_NULL, DateTimeExt::DATE_FORMAT_4, DateTimeExt::DATE_FORMAT_3);

        $this->assertEquals('', $result);
    }

    #[Test]
    public function it_checks_if_date_is_null() {
        $this->assertTrue(DateTimeExt::isDateNull(null));
        $this->assertTrue(DateTimeExt::isDateNull(DateTimeExt::DATE_DEFAULT_NULL));
        $this->assertTrue(DateTimeExt::isDateNull(DateTimeExt::DATE_DEFAULT_NULL_FULL));
        $this->assertFalse(DateTimeExt::isDateNull('2024-01-15'));
    }

    #[Test]
    public function it_calculates_diff_date() {
        $date1  = '2024-01-15';
        $date2  = '2024-01-20';
        $result = DateTimeExt::diffDate($date1, $date2);

        $this->assertEquals(5, $result);
    }

    #[Test]
    public function it_calculates_diff_month() {
        $date1  = '2024-01-15';
        $date2  = '2024-03-15';
        $result = DateTimeExt::diffMonth($date1, $date2);

        $this->assertEquals(2, $result);
    }

    #[Test]
    public function it_calculates_diff_year() {
        $date1  = '2022-01-15';
        $date2  = '2024-01-15';
        $result = DateTimeExt::diffYear($date1, $date2);

        $this->assertEquals(2, $result);
    }

    #[Test]
    public function it_gets_last_day_of_month() {
        $date   = '2024-01-15';
        $result = DateTimeExt::getLastDayOfMonth($date);

        $this->assertEquals('2024-01-31', $result);
    }

    #[Test]
    public function it_gets_first_day_of_month() {
        $date   = '2024-01-15';
        $result = DateTimeExt::getFirstDayOfMonth($date);

        $this->assertEquals('2024-01-01', $result);
    }

    #[Test]
    public function it_gets_first_day_of_year() {
        $date   = '2024-06-15';
        $result = DateTimeExt::getFirstDayOfYear($date);

        $this->assertEquals('2024-01-01', $result);
    }

    #[Test]
    public function it_gets_last_day_of_year() {
        $date   = '2024-06-15';
        $result = DateTimeExt::getLastDayOfYear($date);

        $this->assertEquals('2024-12-31', $result);
    }

    #[Test]
    public function it_validates_start_before_end() {
        // Start is before end - should return false (start < end)
        $this->assertFalse(DateTimeExt::validateStartBeforeEnd('2024-01-15', '10:00', '2024-01-15', '11:00'));

        // Start is after end - should return true (start >= end)
        $this->assertTrue(DateTimeExt::validateStartBeforeEnd('2024-01-15', '11:00', '2024-01-15', '10:00'));

        // Same time - should return true (start >= end)
        $this->assertTrue(DateTimeExt::validateStartBeforeEnd('2024-01-15', '10:00', '2024-01-15', '10:00'));
    }

    #[Test]
    public function it_formats_date_and_time() {
        $datetime = '2024-01-15 10:30:00';
        $result   = DateTimeExt::formatDateAndTime($datetime);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('time', $result);
        // Time format is DATE_FORMAT_21 which is 'H:i'
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $result['time']);
    }

    #[Test]
    public function it_formats_date_and_time_with_empty_datetime() {
        $result = DateTimeExt::formatDateAndTime('');

        $this->assertIsArray($result);
        $this->assertEquals('', $result['date']);
        $this->assertEquals('', $result['time']);
    }

    #[Test]
    public function it_validates_date() {
        $this->assertTrue(DateTimeExt::isValidDate('2024-01-15'));
        $this->assertTrue(DateTimeExt::isValidDate('2024-01-15 10:30:00'));
        $this->assertFalse(DateTimeExt::isValidDate('invalid-date'));
        $this->assertFalse(DateTimeExt::isValidDate(''));
    }

    #[Test]
    public function it_logs_warning_when_datetime_conversion_fails() {
        // Mock LogHandler to verify warning is logged
        $logManager = Mockery::mock('Illuminate\Log\LogManager');
        $logManager->shouldReceive('log')
            ->once()
            ->with('warning', 'Failed to convert datetime', Mockery::type('array'));

        \Illuminate\Support\Facades\Log::swap($logManager);
        \Illuminate\Support\Facades\Auth::shouldReceive('user')->andReturn(null);

        // Try to convert invalid datetime format
        $result = DateTimeExt::convertDateTime('invalid-date', DateTimeExt::DATE_FORMAT_1, DateTimeExt::DATE_FORMAT_3);

        $this->assertEquals('', $result);
    }

    #[Test]
    public function it_handles_datetime_conversion_with_false_format_result() {
        // Mock LogHandler to verify warning is logged
        $logManager = Mockery::mock('Illuminate\Log\LogManager');
        $logManager->shouldReceive('log')
            ->once()
            ->with('warning', 'Failed to convert datetime', Mockery::type('array'));

        \Illuminate\Support\Facades\Log::swap($logManager);
        \Illuminate\Support\Facades\Auth::shouldReceive('user')->andReturn(null);

        // This should trigger the false check in convertDateTime
        // We'll use an edge case that might cause format() to return false
        $result = DateTimeExt::convertDateTime('invalid', 'Y-m-d H:i:s', 'Y-m-d');

        $this->assertEquals('', $result);
    }
}
