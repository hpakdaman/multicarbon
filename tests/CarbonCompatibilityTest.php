<?php

declare(strict_types=1);

namespace Tests\MultiCarbon;

use MultiCarbon\CalendarMode;
use MultiCarbon\MultiCarbon;
use PHPUnit\Framework\TestCase;

/**
 * Tests adapted from Carbon's official test suite (CarbonPHP/carbon).
 *
 * Verifies that MultiCarbon behaves identically to Carbon in Gregorian mode,
 * and provides correct calendar-specific values in Jalali/Hijri modes.
 *
 * @see https://github.com/CarbonPHP/carbon/tree/master/tests/Carbon
 */
class CarbonCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        MultiCarbon::setDigitsType(MultiCarbon::DIGITS_LATIN);
    }

    // ═══════════════════════════════════════════════════════════
    //  GETTERS — Adapted from Carbon GettersTest.php
    // ═══════════════════════════════════════════════════════════

    public function test_gregorian_year_getter(): void
    {
        $d = new MultiCarbon('2025-02-14');
        $d->gregorian();
        $this->assertSame(2025, $d->year);
    }

    public function test_gregorian_month_getter(): void
    {
        $d = new MultiCarbon('2025-02-14');
        $d->gregorian();
        $this->assertSame(2, $d->month);
    }

    public function test_gregorian_day_getter(): void
    {
        $d = new MultiCarbon('2025-02-14');
        $d->gregorian();
        $this->assertSame(14, $d->day);
    }

    public function test_gregorian_hour_getter(): void
    {
        $d = new MultiCarbon('2025-02-14 15:30:45');
        $d->gregorian();
        $this->assertSame(15, $d->hour);
    }

    public function test_gregorian_minute_getter(): void
    {
        $d = new MultiCarbon('2025-02-14 15:30:45');
        $d->gregorian();
        $this->assertSame(30, $d->minute);
    }

    public function test_gregorian_second_getter(): void
    {
        $d = new MultiCarbon('2025-02-14 15:30:45');
        $d->gregorian();
        $this->assertSame(45, $d->second);
    }

    public function test_jalali_year_getter(): void
    {
        $d = new MultiCarbon('2025-02-14');
        $d->jalali();
        $this->assertSame(1403, $d->year);
    }

    public function test_jalali_month_getter(): void
    {
        $d = new MultiCarbon('2025-02-14');
        $d->jalali();
        $this->assertSame(11, $d->month);
    }

    public function test_jalali_day_getter(): void
    {
        $d = new MultiCarbon('2025-02-14');
        $d->jalali();
        $this->assertSame(26, $d->day);
    }

    public function test_hijri_year_getter(): void
    {
        $d = new MultiCarbon('2025-02-14');
        $d->hijri();
        $this->assertSame(1446, $d->year);
    }

    public function test_days_in_month_jalali(): void
    {
        // Farvardin has 31 days
        $d = MultiCarbon::createJalali(1404, 1, 1);
        $this->assertSame(31, $d->daysInMonth);

        // Mehr (month 7) has 30 days
        $d2 = MultiCarbon::createJalali(1404, 7, 1);
        $this->assertSame(30, $d2->daysInMonth);

        // Esfand (month 12) — 29 in non-leap, 30 in leap
        $d3 = MultiCarbon::createJalali(1404, 12, 1);
        $this->assertSame(29, $d3->daysInMonth);

        $d4 = MultiCarbon::createJalali(1403, 12, 1);
        $this->assertSame(30, $d4->daysInMonth);
    }

    public function test_day_of_year_jalali(): void
    {
        // Farvardin 1 = day 1
        $d = MultiCarbon::createJalali(1404, 1, 1);
        $this->assertSame(1, $d->dayOfYear);

        // Ordibehesht 1 = day 32
        $d2 = MultiCarbon::createJalali(1404, 2, 1);
        $this->assertSame(32, $d2->dayOfYear);
    }

    public function test_quarter_jalali(): void
    {
        // Month 1 → Q1
        $d = MultiCarbon::createJalali(1404, 1, 1);
        $this->assertSame(1, $d->quarter);

        // Month 4 → Q2
        $d2 = MultiCarbon::createJalali(1404, 4, 1);
        $this->assertSame(2, $d2->quarter);

        // Month 7 → Q3
        $d3 = MultiCarbon::createJalali(1404, 7, 1);
        $this->assertSame(3, $d3->quarter);

        // Month 10 → Q4
        $d4 = MultiCarbon::createJalali(1404, 10, 1);
        $this->assertSame(4, $d4->quarter);
    }

    public function test_is_leap_year_gregorian(): void
    {
        $d = new MultiCarbon('2024-01-01');
        $d->gregorian();
        $this->assertTrue($d->isLeapYear());

        $d2 = new MultiCarbon('2025-01-01');
        $d2->gregorian();
        $this->assertFalse($d2->isLeapYear());
    }

    public function test_month_name_jalali(): void
    {
        $d = MultiCarbon::createJalali(1404, 1, 1);
        $this->assertSame('فروردین', $d->monthName);
    }

    public function test_month_name_hijri(): void
    {
        $d = MultiCarbon::createHijri(1446, 1, 1);
        $this->assertSame('محرم', $d->monthName);
    }

    // ═══════════════════════════════════════════════════════════
    //  SETTERS — Adapted from Carbon SettersTest.php
    // ═══════════════════════════════════════════════════════════

    public function test_set_jalali_year(): void
    {
        $d = MultiCarbon::createJalali(1403, 6, 15);
        $d->year = 1404;
        $this->assertSame(1404, $d->year);
        $this->assertSame(6, $d->month);
        $this->assertSame(15, $d->day);
    }

    public function test_set_jalali_month(): void
    {
        $d = MultiCarbon::createJalali(1404, 1, 15);
        $d->month = 7;
        $this->assertSame(1404, $d->year);
        $this->assertSame(7, $d->month);
        $this->assertSame(15, $d->day);
    }

    public function test_set_jalali_day(): void
    {
        $d = MultiCarbon::createJalali(1404, 1, 1);
        $d->day = 25;
        $this->assertSame(1404, $d->year);
        $this->assertSame(1, $d->month);
        $this->assertSame(25, $d->day);
    }

    public function test_set_date_jalali(): void
    {
        $d = new MultiCarbon('2025-01-01');
        $d->jalali();
        $d->setDate(1404, 1, 1);

        $this->assertSame(1404, $d->year);
        $this->assertSame(1, $d->month);
        $this->assertSame(1, $d->day);

        // Verify Gregorian equivalence
        $d->gregorian();
        $this->assertSame(2025, $d->year);
        $this->assertSame(3, $d->month);
        $this->assertSame(21, $d->day);
    }

    public function test_set_date_time_jalali(): void
    {
        $d = new MultiCarbon();
        $d->jalali();
        $d->setDateTime(1404, 1, 1, 14, 30, 0);

        $this->assertSame(1404, $d->year);
        $this->assertSame(1, $d->month);
        $this->assertSame(1, $d->day);
        $this->assertSame(14, $d->hour);
        $this->assertSame(30, $d->minute);
    }

    // ═══════════════════════════════════════════════════════════
    //  ADD MONTHS — Adapted from Carbon AddMonthsTest.php
    // ═══════════════════════════════════════════════════════════

    public function test_add_month_no_overflow_jalali(): void
    {
        // Jalali: Shahrivar 31 (month 6, 31 days) + 1 month = Mehr 30 (month 7, 30 days)
        $d = MultiCarbon::createJalali(1404, 6, 31);
        $d->addMonth();
        $this->assertSame(1404, $d->year);
        $this->assertSame(7, $d->month);
        $this->assertSame(30, $d->day); // Clamped from 31 to 30
    }

    public function test_add_months_across_year_boundary_jalali(): void
    {
        // Esfand (month 12) + 1 month = Farvardin (month 1) of next year
        $d = MultiCarbon::createJalali(1403, 12, 1);
        $d->addMonth();
        $this->assertSame(1404, $d->year);
        $this->assertSame(1, $d->month);
        $this->assertSame(1, $d->day);
    }

    public function test_sub_month_jalali(): void
    {
        // Farvardin 1 - 1 month = Esfand of previous year
        $d = MultiCarbon::createJalali(1404, 1, 1);
        $d->subMonth();
        $this->assertSame(1403, $d->year);
        $this->assertSame(12, $d->month);
        $this->assertSame(1, $d->day);
    }

    public function test_add_multiple_months_jalali(): void
    {
        $d = MultiCarbon::createJalali(1404, 1, 15);
        $d->addMonths(6);
        $this->assertSame(1404, $d->year);
        $this->assertSame(7, $d->month);
        $this->assertSame(15, $d->day);
    }

    public function test_add_months_no_overflow_esfand_leap(): void
    {
        // Esfand 30 (leap year 1403) + 12 months = Esfand of 1404 (non-leap) → clamped to 29
        $d = MultiCarbon::createJalali(1403, 12, 30);
        $d->addMonths(12);
        $this->assertSame(1404, $d->year);
        $this->assertSame(12, $d->month);
        $this->assertSame(29, $d->day); // Non-leap year
    }

    public function test_add_month_hijri(): void
    {
        $d = MultiCarbon::createHijri(1446, 1, 15);
        $d->addMonth();
        $this->assertSame(1446, $d->year);
        $this->assertSame(2, $d->month);
        $this->assertSame(15, $d->day);
    }

    // ═══════════════════════════════════════════════════════════
    //  ADD YEARS — Year arithmetic
    // ═══════════════════════════════════════════════════════════

    public function test_add_year_jalali(): void
    {
        $d = MultiCarbon::createJalali(1403, 6, 15);
        $d->addYear();
        $this->assertSame(1404, $d->year);
        $this->assertSame(6, $d->month);
        $this->assertSame(15, $d->day);
    }

    public function test_add_year_clamps_leap_day(): void
    {
        // Esfand 30, 1403 (leap) + 1 year = Esfand 29, 1404 (non-leap)
        $d = MultiCarbon::createJalali(1403, 12, 30);
        $d->addYear();
        $this->assertSame(1404, $d->year);
        $this->assertSame(12, $d->month);
        $this->assertSame(29, $d->day);
    }

    public function test_sub_year_jalali(): void
    {
        $d = MultiCarbon::createJalali(1404, 1, 1);
        $d->subYear();
        $this->assertSame(1403, $d->year);
    }

    // ═══════════════════════════════════════════════════════════
    //  START/END OF — Adapted from Carbon StartEndOfTest.php
    // ═══════════════════════════════════════════════════════════

    public function test_start_of_month_jalali(): void
    {
        $d = MultiCarbon::createJalali(1404, 6, 15, 14, 30, 45);
        $d->startOfMonth();
        $this->assertSame(1404, $d->year);
        $this->assertSame(6, $d->month);
        $this->assertSame(1, $d->day);
        $this->assertSame(0, $d->hour);
        $this->assertSame(0, $d->minute);
        $this->assertSame(0, $d->second);
    }

    public function test_end_of_month_jalali(): void
    {
        $d = MultiCarbon::createJalali(1404, 6, 1, 0, 0, 0);
        $d->endOfMonth();
        $this->assertSame(1404, $d->year);
        $this->assertSame(6, $d->month);
        $this->assertSame(31, $d->day);
        $this->assertSame(23, $d->hour);
        $this->assertSame(59, $d->minute);
        $this->assertSame(59, $d->second);
    }

    public function test_end_of_month_esfand_leap(): void
    {
        // 1403 is leap → Esfand has 30 days
        $d = MultiCarbon::createJalali(1403, 12, 1);
        $d->endOfMonth();
        $this->assertSame(30, $d->day);
    }

    public function test_end_of_month_esfand_non_leap(): void
    {
        // 1404 is not leap → Esfand has 29 days
        $d = MultiCarbon::createJalali(1404, 12, 1);
        $d->endOfMonth();
        $this->assertSame(29, $d->day);
    }

    public function test_start_of_year_jalali(): void
    {
        $d = MultiCarbon::createJalali(1404, 6, 15, 14, 30, 0);
        $d->startOfYear();
        $this->assertSame(1404, $d->year);
        $this->assertSame(1, $d->month);
        $this->assertSame(1, $d->day);
        $this->assertSame(0, $d->hour);
    }

    public function test_end_of_year_jalali(): void
    {
        $d = MultiCarbon::createJalali(1404, 1, 1);
        $d->endOfYear();
        $this->assertSame(1404, $d->year);
        $this->assertSame(12, $d->month);
        $this->assertSame(29, $d->day); // 1404 is non-leap
        $this->assertSame(23, $d->hour);
    }

    public function test_end_of_year_leap_jalali(): void
    {
        $d = MultiCarbon::createJalali(1403, 1, 1);
        $d->endOfYear();
        $this->assertSame(1403, $d->year);
        $this->assertSame(12, $d->month);
        $this->assertSame(30, $d->day); // 1403 IS leap
    }

    public function test_start_of_month_hijri(): void
    {
        $d = MultiCarbon::createHijri(1446, 7, 15, 10, 0, 0);
        $d->startOfMonth();
        $this->assertSame(1446, $d->year);
        $this->assertSame(7, $d->month);
        $this->assertSame(1, $d->day);
        $this->assertSame(0, $d->hour);
    }

    public function test_end_of_month_hijri(): void
    {
        $d = MultiCarbon::createHijri(1446, 1, 1);
        $d->endOfMonth();
        $this->assertSame(1446, $d->year);
        $this->assertSame(1, $d->month);
        $this->assertSame(30, $d->day); // Muharram = 30 days
    }

    public function test_start_end_of_year_hijri(): void
    {
        $d = MultiCarbon::createHijri(1446, 6, 15);
        $d->startOfYear();
        $this->assertSame(1446, $d->year);
        $this->assertSame(1, $d->month);
        $this->assertSame(1, $d->day);

        $d2 = MultiCarbon::createHijri(1446, 6, 15);
        $d2->endOfYear();
        $this->assertSame(1446, $d2->year);
        $this->assertSame(12, $d2->month);
    }

    // ═══════════════════════════════════════════════════════════
    //  STRINGS — Adapted from Carbon StringsTest.php
    // ═══════════════════════════════════════════════════════════

    public function test_to_date_string_jalali(): void
    {
        $d = MultiCarbon::createJalali(1404, 1, 1);
        $this->assertSame('1404-01-01', $d->toDateString());
    }

    public function test_to_date_time_string_jalali(): void
    {
        $d = MultiCarbon::createJalali(1404, 1, 1, 14, 30, 0);
        $this->assertSame('1404-01-01 14:30:00', $d->toDateTimeString());
    }

    public function test_to_time_string_jalali(): void
    {
        $d = MultiCarbon::createJalali(1404, 1, 1, 14, 30, 45);
        $this->assertSame('14:30:45', $d->toTimeString());
    }

    public function test_to_string_cast_jalali(): void
    {
        $d = MultiCarbon::createJalali(1404, 1, 1, 14, 30, 0);
        $this->assertSame('1404/01/01 14:30:00', (string) $d);
    }

    public function test_to_date_string_gregorian_matches_carbon(): void
    {
        $d = new MultiCarbon('2025-03-21 10:30:00');
        $d->gregorian();
        $this->assertSame('2025-03-21', $d->toDateString());
        $this->assertSame('2025-03-21 10:30:00', $d->toDateTimeString());
    }

    public function test_set_to_string_format(): void
    {
        MultiCarbon::setToStringFormat('Y-m-d');
        $d = MultiCarbon::createJalali(1404, 1, 1);
        $this->assertSame('1404-01-01', (string) $d);
        MultiCarbon::resetToStringFormat();
    }

    public function test_reset_to_string_format(): void
    {
        MultiCarbon::setToStringFormat('Y');
        MultiCarbon::resetToStringFormat();
        $d = MultiCarbon::createJalali(1404, 1, 1, 10, 0, 0);
        $this->assertSame('1404/01/01 10:00:00', (string) $d);
    }

    public function test_format_characters_jalali(): void
    {
        $d = MultiCarbon::createJalali(1404, 1, 15, 14, 30, 0);

        // Y = 4-digit year
        $this->assertSame('1404', $d->format('Y'));

        // y = 2-digit year
        $this->assertSame('04', $d->format('y'));

        // m = zero-padded month
        $this->assertSame('01', $d->format('m'));

        // n = month without padding
        $this->assertSame('1', $d->format('n'));

        // d = zero-padded day
        $this->assertSame('15', $d->format('d'));

        // j = day without padding
        $this->assertSame('15', $d->format('j'));

        // F = full month name
        $this->assertSame('فروردین', $d->format('F'));

        // t = days in month
        $this->assertSame('31', $d->format('t'));

        // L = leap year (0 or 1)
        $this->assertSame('0', $d->format('L')); // 1404 is not leap

        // Time passes through
        $this->assertSame('14', $d->format('H'));
        $this->assertSame('30', $d->format('i'));
    }

    public function test_format_leap_year_flag(): void
    {
        $d = MultiCarbon::createJalali(1403, 1, 1);
        $this->assertSame('1', $d->format('L')); // 1403 IS leap
    }

    public function test_format_escaped_characters(): void
    {
        $d = MultiCarbon::createJalali(1404, 1, 1);
        $this->assertSame('Y', $d->format('\\Y'));
        $this->assertSame('1404Y', $d->format('Y\\Y'));
    }

    public function test_format_day_of_year(): void
    {
        // z is 0-indexed in PHP
        $d = MultiCarbon::createJalali(1404, 1, 1);
        $this->assertSame('0', $d->format('z')); // day 1 → z=0

        $d2 = MultiCarbon::createJalali(1404, 2, 1);
        $this->assertSame('31', $d2->format('z')); // day 32 → z=31
    }

    // ═══════════════════════════════════════════════════════════
    //  COMPARISON — Adapted from Carbon ComparisonTest.php
    // ═══════════════════════════════════════════════════════════

    public function test_is_same_day_jalali(): void
    {
        $a = MultiCarbon::createJalali(1404, 1, 1, 10, 0, 0);
        $b = MultiCarbon::createJalali(1404, 1, 1, 22, 0, 0);
        $c = MultiCarbon::createJalali(1404, 1, 2);

        $this->assertTrue($a->isSameDay($b));
        $this->assertFalse($a->isSameDay($c));
    }

    public function test_is_same_month_jalali(): void
    {
        $a = MultiCarbon::createJalali(1404, 6, 1);
        $b = MultiCarbon::createJalali(1404, 6, 30);
        $c = MultiCarbon::createJalali(1404, 7, 1);

        $this->assertTrue($a->isSameMonth($b));
        $this->assertFalse($a->isSameMonth($c));
    }

    public function test_is_same_month_different_year(): void
    {
        $a = MultiCarbon::createJalali(1403, 6, 1);
        $b = MultiCarbon::createJalali(1404, 6, 1);

        // Same month, different year — ofSameYear = true → false
        $this->assertFalse($a->isSameMonth($b, true));

        // Same month, different year — ofSameYear = false → true
        $this->assertTrue($a->isSameMonth($b, false));
    }

    public function test_is_same_year_jalali(): void
    {
        $a = MultiCarbon::createJalali(1404, 1, 1);
        $b = MultiCarbon::createJalali(1404, 12, 29);
        $c = MultiCarbon::createJalali(1403, 12, 30);

        $this->assertTrue($a->isSameYear($b));
        $this->assertFalse($a->isSameYear($c));
    }

    public function test_is_weekend_jalali(): void
    {
        // Friday is weekend in Iranian calendar
        // 2025-02-14 is Friday
        $d = new MultiCarbon('2025-02-14');
        $d->jalali();
        $this->assertTrue($d->isWeekend());

        // 2025-02-13 is Thursday — not weekend
        $d2 = new MultiCarbon('2025-02-13');
        $d2->jalali();
        $this->assertFalse($d2->isWeekend());
    }

    // ═══════════════════════════════════════════════════════════
    //  COPY — Adapted from Carbon CopyTest.php
    // ═══════════════════════════════════════════════════════════

    public function test_copy_returns_different_instance(): void
    {
        $d = MultiCarbon::createJalali(1404, 1, 1);
        $d2 = $d->copy();
        $this->assertNotSame($d, $d2);
    }

    public function test_copy_preserves_calendar_mode(): void
    {
        $d = MultiCarbon::createJalali(1404, 1, 1);
        $d2 = $d->copy();
        $this->assertTrue($d2->isJalali());
        $this->assertSame($d->getCalendar(), $d2->getCalendar());
    }

    public function test_copy_preserves_values(): void
    {
        $d = MultiCarbon::createJalali(1404, 6, 15, 14, 30, 45);
        $d2 = $d->copy();

        $this->assertSame($d->year, $d2->year);
        $this->assertSame($d->month, $d2->month);
        $this->assertSame($d->day, $d2->day);
        $this->assertSame($d->hour, $d2->hour);
    }

    public function test_copy_preserves_timezone(): void
    {
        $d = new MultiCarbon('2025-01-01 12:00:00', 'Asia/Tehran');
        $d->jalali();
        $d2 = $d->copy();

        $this->assertSame('Asia/Tehran', $d2->getTimezone()->getName());
    }

    public function test_clone_alias(): void
    {
        $d = MultiCarbon::createJalali(1404, 1, 1);
        $d2 = $d->clone();
        $this->assertNotSame($d, $d2);
        $this->assertTrue($d2->isJalali());
    }

    // ═══════════════════════════════════════════════════════════
    //  DIFF — Adapted from Carbon DiffTest.php
    // ═══════════════════════════════════════════════════════════

    public function test_diff_in_years_positive(): void
    {
        $a = new MultiCarbon('2020-01-01');
        $b = new MultiCarbon('2025-01-01');
        $this->assertEquals(5, $a->diffInYears($b));
    }

    public function test_diff_in_days_jalali(): void
    {
        // Farvardin 1 to Farvardin 11 = 10 days
        $a = MultiCarbon::createJalali(1404, 1, 1);
        $b = MultiCarbon::createJalali(1404, 1, 11);
        $this->assertEquals(10, $a->diffInDays($b));
    }

    public function test_diff_for_humans_jalali(): void
    {
        $now = new MultiCarbon('2025-02-14 12:00:00');
        $now->jalali();

        $past = new MultiCarbon('2025-02-13 12:00:00');
        $past->jalali();

        $result = $past->diffForHumans($now);
        $this->assertStringContainsString('پیش', $result); // "ago" in Persian
    }

    public function test_diff_for_humans_hijri(): void
    {
        $now = new MultiCarbon('2025-02-14 12:00:00');
        $now->hijri();

        $past = new MultiCarbon('2025-02-13 12:00:00');
        $past->hijri();

        $result = $past->diffForHumans($now);
        $this->assertStringContainsString('منذ', $result); // "ago" in Arabic
    }

    // ═══════════════════════════════════════════════════════════
    //  CREATE — Adapted from Carbon CreateTest.php
    // ═══════════════════════════════════════════════════════════

    public function test_create_jalali_with_all_params(): void
    {
        $d = MultiCarbon::createJalali(1404, 6, 15, 14, 30, 45);
        $this->assertSame(1404, $d->year);
        $this->assertSame(6, $d->month);
        $this->assertSame(15, $d->day);
        $this->assertSame(14, $d->hour);
        $this->assertSame(30, $d->minute);
        $this->assertSame(45, $d->second);
    }

    public function test_create_hijri_with_all_params(): void
    {
        $d = MultiCarbon::createHijri(1446, 7, 15, 10, 30, 0);
        $this->assertSame(1446, $d->year);
        $this->assertSame(7, $d->month);
        $this->assertSame(15, $d->day);
        $this->assertSame(10, $d->hour);
    }

    public function test_create_jalali_preserves_calendar_mode(): void
    {
        $d = MultiCarbon::createJalali(1404, 1, 1);
        $this->assertTrue($d->isJalali());
    }

    public function test_create_hijri_preserves_calendar_mode(): void
    {
        $d = MultiCarbon::createHijri(1446, 1, 1);
        $this->assertTrue($d->isHijri());
    }

    public function test_create_with_timezone(): void
    {
        $d = MultiCarbon::createJalali(1404, 1, 1, 0, 0, 0, 'Asia/Tehran');
        $this->assertSame('Asia/Tehran', $d->getTimezone()->getName());
    }

    // ═══════════════════════════════════════════════════════════
    //  WEEK — Adapted from Carbon WeekTest.php
    // ═══════════════════════════════════════════════════════════

    public function test_day_of_week_saturday(): void
    {
        // Saturday should be 0 in our Iranian calendar mapping
        // 2025-02-15 is Saturday
        $d = new MultiCarbon('2025-02-15');
        $d->jalali();
        $this->assertSame(MultiCarbon::SATURDAY, $d->dayOfWeek);
    }

    public function test_day_of_week_friday(): void
    {
        // Friday should be 6 in our Iranian calendar mapping
        // 2025-02-14 is Friday
        $d = new MultiCarbon('2025-02-14');
        $d->jalali();
        $this->assertSame(MultiCarbon::FRIDAY, $d->dayOfWeek);
    }

    public function test_start_of_week_saturday(): void
    {
        // Start of week (Saturday) from a Wednesday (2025-02-12)
        $d = new MultiCarbon('2025-02-12 14:00:00');
        $d->jalali();
        $d->startOfWeek();

        // Should go back to Saturday 2025-02-08
        $d->gregorian();
        $this->assertSame(8, $d->day);
        $this->assertSame(2, $d->month);
        $this->assertSame(0, $d->hour);
    }

    public function test_end_of_week_friday(): void
    {
        // End of week (Friday) from a Wednesday (2025-02-12)
        $d = new MultiCarbon('2025-02-12 14:00:00');
        $d->jalali();
        $d->endOfWeek();

        // Should go forward to Friday 2025-02-14
        $d->gregorian();
        $this->assertSame(14, $d->day);
        $this->assertSame(2, $d->month);
        $this->assertSame(23, $d->hour);
    }

    public function test_add_weekdays_skips_friday(): void
    {
        // Thursday 2025-02-13 + 1 weekday = Saturday 2025-02-15 (skips Friday)
        $d = new MultiCarbon('2025-02-13');
        $d->jalali();
        $d->addWeekdays(1);

        $d->gregorian();
        $this->assertSame(15, $d->day); // Saturday
    }

    // ═══════════════════════════════════════════════════════════
    //  DIGITS — Farsi and Arabic digit display
    // ═══════════════════════════════════════════════════════════

    public function test_farsi_digits(): void
    {
        MultiCarbon::setDigitsType(MultiCarbon::DIGITS_FARSI);
        $d = MultiCarbon::createJalali(1404, 1, 1);
        $this->assertSame('۱۴۰۴', $d->format('Y'));
        MultiCarbon::setDigitsType(MultiCarbon::DIGITS_LATIN);
    }

    public function test_arabic_digits(): void
    {
        MultiCarbon::setDigitsType(MultiCarbon::DIGITS_ARABIC);
        $d = MultiCarbon::createJalali(1404, 1, 1);
        $this->assertSame('١٤٠٤', $d->format('Y'));
        MultiCarbon::setDigitsType(MultiCarbon::DIGITS_LATIN);
    }

    public function test_latin_digits_default(): void
    {
        MultiCarbon::setDigitsType(MultiCarbon::DIGITS_LATIN);
        $d = MultiCarbon::createJalali(1404, 1, 1);
        $this->assertSame('1404', $d->format('Y'));
    }

    // ═══════════════════════════════════════════════════════════
    //  GREGORIAN MODE — Must behave like Carbon
    // ═══════════════════════════════════════════════════════════

    public function test_gregorian_mode_start_of_month(): void
    {
        $d = new MultiCarbon('2025-02-14 14:30:00');
        $d->gregorian();
        $d->startOfMonth();

        $this->assertSame(2025, $d->year);
        $this->assertSame(2, $d->month);
        $this->assertSame(1, $d->day);
    }

    public function test_gregorian_mode_end_of_month(): void
    {
        $d = new MultiCarbon('2025-02-14');
        $d->gregorian();
        $d->endOfMonth();

        $this->assertSame(28, $d->day);
    }

    public function test_gregorian_mode_add_months(): void
    {
        $d = new MultiCarbon('2025-01-31');
        $d->gregorian();
        $d->addMonths(1);

        // Carbon's default behavior: Jan 31 + 1 month = Mar 3 (overflow)
        $this->assertSame(3, $d->month);
        $this->assertSame(3, $d->day);
    }

    public function test_gregorian_mode_format(): void
    {
        $d = new MultiCarbon('2025-03-21 10:30:00');
        $d->gregorian();
        $this->assertSame('2025-03-21 10:30:00', $d->format('Y-m-d H:i:s'));
    }

    public function test_gregorian_mode_is_leap_year(): void
    {
        $d = new MultiCarbon('2024-06-01');
        $d->gregorian();
        $this->assertTrue($d->isLeapYear());
    }

    // ═══════════════════════════════════════════════════════════
    //  CALENDAR SWITCHING — Preserves underlying timestamp
    // ═══════════════════════════════════════════════════════════

    public function test_switching_calendars_preserves_timestamp(): void
    {
        $d = new MultiCarbon('2025-03-21 10:30:00');
        $ts = $d->getTimestamp();

        $d->jalali();
        $this->assertSame($ts, $d->getTimestamp());
        $this->assertSame(1404, $d->year);

        $d->hijri();
        $this->assertSame($ts, $d->getTimestamp());

        $d->gregorian();
        $this->assertSame($ts, $d->getTimestamp());
        $this->assertSame(2025, $d->year);
    }

    public function test_all_three_calendars_same_date(): void
    {
        // 2025-03-21 = Nowruz 1404 (Jalali)
        $d = new MultiCarbon('2025-03-21');

        $d->jalali();
        $this->assertSame(1404, $d->year);
        $this->assertSame(1, $d->month);
        $this->assertSame(1, $d->day);

        $d->gregorian();
        $this->assertSame(2025, $d->year);
        $this->assertSame(3, $d->month);
        $this->assertSame(21, $d->day);

        $d->hijri();
        $this->assertSame(1446, $d->year);
    }

    // ═══════════════════════════════════════════════════════════
    //  ROUND-TRIP INTEGRITY
    // ═══════════════════════════════════════════════════════════

    public function test_round_trip_jalali_set_get(): void
    {
        // Set a Jalali date, read it back, verify it matches
        for ($m = 1; $m <= 12; $m++) {
            $maxDay = $m <= 6 ? 31 : ($m <= 11 ? 30 : 29);
            $d = MultiCarbon::createJalali(1404, $m, $maxDay);
            $this->assertSame(1404, $d->year, "Year mismatch for month {$m}");
            $this->assertSame($m, $d->month, "Month mismatch for month {$m}");
            $this->assertSame($maxDay, $d->day, "Day mismatch for month {$m}");
        }
    }

    public function test_round_trip_hijri_set_get(): void
    {
        // Set a Hijri date, read it back, verify it matches
        for ($m = 1; $m <= 12; $m++) {
            $maxDay = ($m % 2 === 1) ? 30 : 29;
            $d = MultiCarbon::createHijri(1446, $m, $maxDay);
            $this->assertSame(1446, $d->year, "Year mismatch for month {$m}");
            $this->assertSame($m, $d->month, "Month mismatch for month {$m}");
            $this->assertSame($maxDay, $d->day, "Day mismatch for month {$m}");
        }
    }
}
