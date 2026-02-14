<?php

declare(strict_types=1);

namespace Tests\MultiCarbon;

use MultiCarbon\Converters\JalaliConverter;
use PHPUnit\Framework\TestCase;

class JalaliConverterTest extends TestCase
{
    // ─── Gregorian → Jalali ─────────────────────────────────────

    public function test_known_conversions_to_jalali(): void
    {
        // Nowruz 1404 = March 21, 2025
        $this->assertSame([1404, 1, 1], JalaliConverter::toJalali(2025, 3, 21));

        // A mid-year date
        $this->assertSame([1403, 11, 26], JalaliConverter::toJalali(2025, 2, 14));

        // Last day of 1403 (1403 is NOT leap → Esfand 29)
        $this->assertSame([1403, 12, 29], JalaliConverter::toJalali(2025, 3, 19));

        // Day before Nowruz 1404 (1399 IS leap → so 1403/12/30 = 2025-03-20)
        $this->assertSame([1403, 12, 30], JalaliConverter::toJalali(2025, 3, 20));

        // Known leap year: 1399 is leap, Esfand 30 = 2021-03-20
        $this->assertSame([1399, 12, 30], JalaliConverter::toJalali(2021, 3, 20));

        // Nowruz 1400 = March 21, 2021
        $this->assertSame([1400, 1, 1], JalaliConverter::toJalali(2021, 3, 21));

        // Start of Gregorian year
        $this->assertSame([1383, 10, 12], JalaliConverter::toJalali(2005, 1, 1));
    }

    // ─── Jalali → Gregorian ─────────────────────────────────────

    public function test_known_conversions_to_gregorian(): void
    {
        $this->assertSame([2025, 3, 21], JalaliConverter::toGregorian(1404, 1, 1));
        $this->assertSame([2025, 2, 14], JalaliConverter::toGregorian(1403, 11, 26));
        $this->assertSame([2021, 3, 20], JalaliConverter::toGregorian(1399, 12, 30));
        $this->assertSame([2005, 1, 1], JalaliConverter::toGregorian(1383, 10, 12));
    }

    // ─── Round-Trip ─────────────────────────────────────────────

    public function test_round_trip_conversion(): void
    {
        // Jalali → Gregorian → Jalali
        $original = [1404, 6, 15];
        $greg = JalaliConverter::toGregorian(...$original);
        $this->assertSame($original, JalaliConverter::toJalali(...$greg));

        // Gregorian → Jalali → Gregorian
        $original = [2025, 7, 22];
        $jalali = JalaliConverter::toJalali(...$original);
        $this->assertSame($original, JalaliConverter::toGregorian(...$jalali));
    }

    // ─── Leap Year ──────────────────────────────────────────────

    public function test_leap_years(): void
    {
        // Known Jalali leap years
        $this->assertTrue(JalaliConverter::isLeapYear(1399));
        $this->assertTrue(JalaliConverter::isLeapYear(1403));
        $this->assertTrue(JalaliConverter::isLeapYear(1408));

        // Non-leap years
        $this->assertFalse(JalaliConverter::isLeapYear(1400));
        $this->assertFalse(JalaliConverter::isLeapYear(1401));
        $this->assertFalse(JalaliConverter::isLeapYear(1404));
    }

    // ─── Days in Month ──────────────────────────────────────────

    public function test_days_in_month(): void
    {
        // Months 1-6: 31 days
        for ($m = 1; $m <= 6; $m++) {
            $this->assertSame(31, JalaliConverter::daysInMonth($m, 1404));
        }

        // Months 7-11: 30 days
        for ($m = 7; $m <= 11; $m++) {
            $this->assertSame(30, JalaliConverter::daysInMonth($m, 1404));
        }

        // Month 12: 30 in leap (1403 IS leap), 29 in non-leap (1404)
        $this->assertSame(30, JalaliConverter::daysInMonth(12, 1403)); // leap
        $this->assertSame(29, JalaliConverter::daysInMonth(12, 1404)); // non-leap
    }

    // ─── Day of Year ────────────────────────────────────────────

    public function test_day_of_year(): void
    {
        $this->assertSame(1, JalaliConverter::dayOfYear(1, 1));      // Farvardin 1
        $this->assertSame(31, JalaliConverter::dayOfYear(1, 31));    // Farvardin 31
        $this->assertSame(32, JalaliConverter::dayOfYear(2, 1));     // Ordibehesht 1
        $this->assertSame(186, JalaliConverter::dayOfYear(6, 31));   // Shahrivar 31
        $this->assertSame(187, JalaliConverter::dayOfYear(7, 1));    // Mehr 1
        $this->assertSame(365, JalaliConverter::dayOfYear(12, 29));  // Esfand 29
        $this->assertSame(366, JalaliConverter::dayOfYear(12, 30));  // Esfand 30 (leap)
    }

    // ─── Validation ─────────────────────────────────────────────

    public function test_validation(): void
    {
        $this->assertTrue(JalaliConverter::isValidDate(1404, 1, 1));
        $this->assertTrue(JalaliConverter::isValidDate(1404, 6, 31));
        $this->assertTrue(JalaliConverter::isValidDate(1403, 12, 30)); // 1403 IS leap
        $this->assertTrue(JalaliConverter::isValidDate(1404, 12, 29)); // 1404 NOT leap, max 29

        $this->assertFalse(JalaliConverter::isValidDate(1404, 12, 30)); // non-leap, max 29
        $this->assertFalse(JalaliConverter::isValidDate(1404, 7, 31));  // max 30
        $this->assertFalse(JalaliConverter::isValidDate(1404, 13, 1));  // invalid month
        $this->assertFalse(JalaliConverter::isValidDate(1404, 0, 1));   // invalid month
    }
}
