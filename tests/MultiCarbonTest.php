<?php

declare(strict_types=1);

namespace Tests\MultiCarbon;

use Carbon\Carbon;
use MultiCarbon\CalendarMode;
use MultiCarbon\MultiCarbon;
use PHPUnit\Framework\TestCase;

class MultiCarbonTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        MultiCarbon::setDigitsType(MultiCarbon::DIGITS_LATIN);
    }

    // ═══════════════════════════════════════════════════════════
    //  CALENDAR MODE SWITCHING
    // ═══════════════════════════════════════════════════════════

    public function test_default_mode_is_jalali(): void
    {
        $date = new MultiCarbon();
        $this->assertTrue($date->isJalali());
        $this->assertSame(CalendarMode::JALALI, $date->getCalendar());
    }

    public function test_switch_to_hijri(): void
    {
        $date = (new MultiCarbon())->hijri();
        $this->assertTrue($date->isHijri());
    }

    public function test_switch_to_gregorian(): void
    {
        $date = (new MultiCarbon())->gregorian();
        $this->assertTrue($date->isGregorian());
    }

    public function test_fluent_calendar_switching(): void
    {
        $date = new MultiCarbon('2025-02-14');
        $jalaliYear = $date->jalali()->year;
        $hijriYear  = $date->hijri()->year;
        $gregYear   = $date->gregorian()->year;

        $this->assertSame(1403, $jalaliYear);
        $this->assertSame(2025, $gregYear);
        $this->assertSame(1446, $hijriYear);
    }

    // ═══════════════════════════════════════════════════════════
    //  PROPERTY ACCESS (__get)
    // ═══════════════════════════════════════════════════════════

    public function test_jalali_year_month_day(): void
    {
        // Nowruz 1404 = March 21, 2025
        $date = new MultiCarbon('2025-03-21');
        $date->jalali();

        $this->assertSame(1404, $date->year);
        $this->assertSame(1, $date->month);
        $this->assertSame(1, $date->day);
    }

    public function test_hijri_year_month_day(): void
    {
        // 1 Muharram 1446 = July 8, 2024 (tabular algorithm)
        $date = new MultiCarbon('2024-07-08');
        $date->hijri();

        $this->assertSame(1446, $date->year);
        $this->assertSame(1, $date->month);
        $this->assertSame(1, $date->day);
    }

    public function test_gregorian_mode_returns_standard_values(): void
    {
        $date = new MultiCarbon('2025-02-14');
        $date->gregorian();

        $this->assertSame(2025, $date->year);
        $this->assertSame(2, $date->month);
        $this->assertSame(14, $date->day);
    }

    public function test_days_in_month_jalali(): void
    {
        // Farvardin has 31 days: 1404/01/01 = 2025-03-21
        $date = new MultiCarbon('2025-03-21');
        $date->jalali();
        $this->assertSame(31, $date->daysInMonth);

        // Mehr (month 7) has 30 days
        $date2 = MultiCarbon::createJalali(1404, 7, 1);
        $this->assertSame(30, $date2->daysInMonth);
    }

    public function test_day_of_year_jalali(): void
    {
        // 1404/01/01 = 2025-03-21
        $date = new MultiCarbon('2025-03-21');
        $date->jalali();
        $this->assertSame(1, $date->dayOfYear);
    }

    public function test_quarter_jalali(): void
    {
        // 1404/01/01 → Q1
        $date = MultiCarbon::createJalali(1404, 1, 1);
        $this->assertSame(1, $date->quarter);

        // Month 4 (Tir) → Q2
        $date2 = MultiCarbon::createJalali(1404, 4, 1);
        $this->assertSame(2, $date2->quarter);
    }

    public function test_is_leap_year_jalali(): void
    {
        // 1403 IS a leap year in Jalali
        $date = new MultiCarbon('2025-02-14'); // 1403/11/26
        $date->jalali();
        $this->assertTrue($date->isLeapYear());

        // 1404 is NOT leap
        $date2 = new MultiCarbon('2025-03-21'); // 1404/01/01
        $date2->jalali();
        $this->assertFalse($date2->isLeapYear());
    }

    public function test_month_name_jalali(): void
    {
        $date = new MultiCarbon('2025-03-21'); // 1404/01/01
        $date->jalali();
        $this->assertSame('فروردین', $date->monthName);
    }

    public function test_day_name_jalali(): void
    {
        // 2025-03-21 is a Friday
        $date = new MultiCarbon('2025-03-21');
        $date->jalali();
        $this->assertSame('جمعه', $date->dayName);
    }

    // ═══════════════════════════════════════════════════════════
    //  FORMAT
    // ═══════════════════════════════════════════════════════════

    public function test_format_jalali_date(): void
    {
        $date = new MultiCarbon('2025-03-21');
        $date->jalali();

        $this->assertSame('1404/01/01', $date->format('Y/m/d'));
    }

    public function test_format_jalali_full(): void
    {
        $date = new MultiCarbon('2025-03-21 14:30:00');
        $date->jalali();

        $this->assertSame('1404-01-01 14:30:00', $date->format('Y-m-d H:i:s'));
    }

    public function test_format_month_name(): void
    {
        $date = new MultiCarbon('2025-03-21');
        $date->jalali();

        $this->assertSame('فروردین', $date->format('F'));
        $this->assertSame('فرو', $date->format('M'));
    }

    public function test_format_day_name(): void
    {
        // 2025-03-21 is a Friday
        $date = new MultiCarbon('2025-03-21');
        $date->jalali();

        $this->assertSame('جمعه', $date->format('l'));
        $this->assertSame('ج', $date->format('D'));
    }

    public function test_format_am_pm_persian(): void
    {
        $date = new MultiCarbon('2025-03-21 14:30:00');
        $date->jalali();

        $this->assertSame('بعد از ظهر', $date->format('A'));
        $this->assertSame('ب.ظ', $date->format('a'));

        $date2 = new MultiCarbon('2025-03-21 09:00:00');
        $date2->jalali();

        $this->assertSame('قبل از ظهر', $date2->format('A'));
        $this->assertSame('ق.ظ', $date2->format('a'));
    }

    public function test_format_escaped_characters(): void
    {
        $date = new MultiCarbon('2025-03-21');
        $date->jalali();

        $this->assertSame('Y: 1404', $date->format('\Y: Y'));
    }

    public function test_format_leap_and_days_in_month(): void
    {
        // 1403 is leap, check Esfand
        $date = MultiCarbon::createJalali(1403, 12, 1);
        $this->assertSame('1', $date->format('L'));  // is leap
        $this->assertSame('30', $date->format('t')); // Esfand in leap has 30 days

        // 1404 is NOT leap
        $date2 = MultiCarbon::createJalali(1404, 12, 1);
        $this->assertSame('0', $date2->format('L'));
        $this->assertSame('29', $date2->format('t'));
    }

    public function test_format_gregorian_mode(): void
    {
        $date = new MultiCarbon('2025-02-14');
        $date->gregorian();

        $this->assertSame('2025/02/14', $date->format('Y/m/d'));
    }

    public function test_farsi_digits(): void
    {
        MultiCarbon::setDigitsType(MultiCarbon::DIGITS_FARSI);

        $date = new MultiCarbon('2025-03-21');
        $date->jalali();

        $this->assertSame('۱۴۰۴/۰۱/۰۱', $date->format('Y/m/d'));

        MultiCarbon::setDigitsType(MultiCarbon::DIGITS_LATIN);
    }

    // ═══════════════════════════════════════════════════════════
    //  TO STRING
    // ═══════════════════════════════════════════════════════════

    public function test_to_date_string(): void
    {
        $date = new MultiCarbon('2025-03-21');
        $date->jalali();

        $this->assertSame('1404-01-01', $date->toDateString());
    }

    public function test_to_date_time_string(): void
    {
        $date = new MultiCarbon('2025-03-21 14:30:00');
        $date->jalali();

        $this->assertSame('1404-01-01 14:30:00', $date->toDateTimeString());
    }

    // ═══════════════════════════════════════════════════════════
    //  CREATE
    // ═══════════════════════════════════════════════════════════

    public function test_create_jalali(): void
    {
        $date = MultiCarbon::createJalali(1404, 1, 1, 12, 0, 0);

        $this->assertSame(1404, $date->year);
        $this->assertSame(1, $date->month);
        $this->assertSame(1, $date->day);
        $this->assertSame(12, $date->hour);

        // Verify underlying Gregorian is correct
        $date->gregorian();
        $this->assertSame(2025, $date->year);
        $this->assertSame(3, $date->month);
        $this->assertSame(21, $date->day);
    }

    public function test_create_hijri(): void
    {
        $date = MultiCarbon::createHijri(1446, 1, 1, 0, 0, 0);

        $this->assertSame(1446, $date->year);
        $this->assertSame(1, $date->month);
        $this->assertSame(1, $date->day);
    }

    // ═══════════════════════════════════════════════════════════
    //  setDate / setDateTime
    // ═══════════════════════════════════════════════════════════

    public function test_set_date_jalali(): void
    {
        $date = new MultiCarbon('2025-01-01');
        $date->jalali();
        $date->setDate(1404, 1, 1);

        $this->assertSame(1404, $date->year);
        $this->assertSame(1, $date->month);
        $this->assertSame(1, $date->day);

        // Verify Gregorian underneath
        $date->gregorian();
        $this->assertSame(2025, $date->year);
        $this->assertSame(3, $date->month);
        $this->assertSame(21, $date->day);
    }

    public function test_set_date_gregorian_mode(): void
    {
        $date = new MultiCarbon('2025-01-01');
        $date->gregorian();
        $date->setDate(2025, 6, 15);

        $this->assertSame(2025, $date->year);
        $this->assertSame(6, $date->month);
        $this->assertSame(15, $date->day);
    }

    // ═══════════════════════════════════════════════════════════
    //  PROPERTY WRITE (__set)
    // ═══════════════════════════════════════════════════════════

    public function test_set_year_jalali(): void
    {
        $date = MultiCarbon::createJalali(1404, 6, 15);
        $date->year = 1403;

        $this->assertSame(1403, $date->year);
        $this->assertSame(6, $date->month);
        $this->assertSame(15, $date->day);
    }

    public function test_set_month_jalali(): void
    {
        $date = MultiCarbon::createJalali(1404, 1, 15);
        $date->month = 6;

        $this->assertSame(1404, $date->year);
        $this->assertSame(6, $date->month);
        $this->assertSame(15, $date->day);
    }

    // ═══════════════════════════════════════════════════════════
    //  BOUNDARY METHODS
    // ═══════════════════════════════════════════════════════════

    public function test_start_of_month_jalali(): void
    {
        $date = MultiCarbon::createJalali(1404, 6, 15, 14, 30, 0);
        $date->startOfMonth();

        $this->assertSame(1404, $date->year);
        $this->assertSame(6, $date->month);
        $this->assertSame(1, $date->day);
        $this->assertSame(0, $date->hour);
        $this->assertSame(0, $date->minute);
    }

    public function test_end_of_month_jalali(): void
    {
        $date = MultiCarbon::createJalali(1404, 1, 15, 10, 0, 0);
        $date->endOfMonth();

        $this->assertSame(1404, $date->year);
        $this->assertSame(1, $date->month);
        $this->assertSame(31, $date->day); // Farvardin has 31 days
        $this->assertSame(23, $date->hour);
        $this->assertSame(59, $date->minute);
    }

    public function test_end_of_month_esfand_leap(): void
    {
        // 1403 IS leap, Esfand has 30 days
        $date = MultiCarbon::createJalali(1403, 12, 10);
        $date->endOfMonth();

        $this->assertSame(1403, $date->year);
        $this->assertSame(12, $date->month);
        $this->assertSame(30, $date->day);
    }

    public function test_end_of_month_esfand_non_leap(): void
    {
        // 1404 is NOT leap, Esfand has 29 days
        $date = MultiCarbon::createJalali(1404, 12, 10);
        $date->endOfMonth();

        $this->assertSame(1404, $date->year);
        $this->assertSame(12, $date->month);
        $this->assertSame(29, $date->day);
    }

    public function test_start_of_year_jalali(): void
    {
        $date = MultiCarbon::createJalali(1404, 6, 15, 14, 30, 0);
        $date->startOfYear();

        $this->assertSame(1404, $date->year);
        $this->assertSame(1, $date->month);
        $this->assertSame(1, $date->day);
        $this->assertSame(0, $date->hour);
    }

    public function test_end_of_year_jalali(): void
    {
        // 1404 is NOT leap → Esfand 29
        $date = MultiCarbon::createJalali(1404, 6, 15);
        $date->endOfYear();

        $this->assertSame(1404, $date->year);
        $this->assertSame(12, $date->month);
        $this->assertSame(29, $date->day); // 1404 is NOT leap
        $this->assertSame(23, $date->hour);
    }

    // ═══════════════════════════════════════════════════════════
    //  ADD / SUB MONTHS & YEARS
    // ═══════════════════════════════════════════════════════════

    public function test_add_months_jalali(): void
    {
        $date = MultiCarbon::createJalali(1404, 1, 15);
        $date->addMonths(3);

        $this->assertSame(1404, $date->year);
        $this->assertSame(4, $date->month);
        $this->assertSame(15, $date->day);
    }

    public function test_add_months_jalali_crosses_year(): void
    {
        $date = MultiCarbon::createJalali(1404, 11, 15);
        $date->addMonths(3);

        $this->assertSame(1405, $date->year);
        $this->assertSame(2, $date->month);
        $this->assertSame(15, $date->day);
    }

    public function test_sub_months_jalali(): void
    {
        $date = MultiCarbon::createJalali(1404, 4, 10);
        $date->subMonths(2);

        $this->assertSame(1404, $date->year);
        $this->assertSame(2, $date->month);
        $this->assertSame(10, $date->day);
    }

    public function test_sub_months_jalali_crosses_year(): void
    {
        $date = MultiCarbon::createJalali(1404, 2, 10);
        $date->subMonths(4);

        $this->assertSame(1403, $date->year);
        $this->assertSame(10, $date->month);
        $this->assertSame(10, $date->day);
    }

    public function test_add_months_day_clamping(): void
    {
        // Shahrivar 31 (month 6) + 1 month → Mehr (month 7, max 30) → day clamped to 30
        $date = MultiCarbon::createJalali(1404, 6, 31);
        $date->addMonths(1);

        $this->assertSame(1404, $date->year);
        $this->assertSame(7, $date->month);
        $this->assertSame(30, $date->day); // Clamped from 31 to 30
    }

    public function test_add_years_jalali(): void
    {
        $date = MultiCarbon::createJalali(1404, 6, 15);
        $date->addYears(2);

        $this->assertSame(1406, $date->year);
        $this->assertSame(6, $date->month);
        $this->assertSame(15, $date->day);
    }

    public function test_sub_years_jalali(): void
    {
        $date = MultiCarbon::createJalali(1404, 6, 15);
        $date->subYears(1);

        $this->assertSame(1403, $date->year);
        $this->assertSame(6, $date->month);
        $this->assertSame(15, $date->day);
    }

    public function test_add_years_leap_day_clamping(): void
    {
        // 1403 IS leap, Esfand 30 exists
        // 1404 is NOT leap, Esfand has 29 → clamp
        $date = MultiCarbon::createJalali(1403, 12, 30);
        $date->addYears(1);

        $this->assertSame(1404, $date->year);
        $this->assertSame(12, $date->month);
        $this->assertSame(29, $date->day); // Clamped
    }

    // ═══════════════════════════════════════════════════════════
    //  COMPARISON
    // ═══════════════════════════════════════════════════════════

    public function test_is_same_day(): void
    {
        $date1 = MultiCarbon::createJalali(1404, 1, 1);
        $date2 = new MultiCarbon('2025-03-21');
        $date2->jalali();

        $this->assertTrue($date1->isSameDay($date2));
    }

    public function test_is_same_month(): void
    {
        $date1 = MultiCarbon::createJalali(1404, 1, 1);
        $date2 = MultiCarbon::createJalali(1404, 1, 25);

        $this->assertTrue($date1->isSameMonth($date2));
    }

    public function test_is_same_year(): void
    {
        $date1 = MultiCarbon::createJalali(1404, 1, 1);
        $date2 = MultiCarbon::createJalali(1404, 12, 29);

        $this->assertTrue($date1->isSameYear($date2));
    }

    public function test_is_weekend(): void
    {
        // Find a Friday: 2025-03-21 is a Friday
        $date = new MultiCarbon('2025-03-21');
        $date->jalali();

        $this->assertTrue($date->isWeekend()); // Friday is weekend in Iranian calendar
    }

    // ═══════════════════════════════════════════════════════════
    //  DIFF FOR HUMANS
    // ═══════════════════════════════════════════════════════════

    public function test_diff_for_humans_jalali_past(): void
    {
        $now = new MultiCarbon('2025-02-14 12:00:00');
        $now->jalali();

        $past = new MultiCarbon('2025-02-14 10:00:00');
        $past->jalali();

        $result = $past->diffForHumans($now);
        $this->assertStringContainsString('ساعت', $result);
        $this->assertStringContainsString('پیش', $result);
    }

    public function test_diff_for_humans_jalali_future(): void
    {
        $now = new MultiCarbon('2025-02-14 12:00:00');
        $now->jalali();

        $future = new MultiCarbon('2025-02-14 15:00:00');
        $future->jalali();

        $result = $future->diffForHumans($now);
        $this->assertStringContainsString('ساعت', $result);
        $this->assertStringContainsString('بعد', $result);
    }

    // ═══════════════════════════════════════════════════════════
    //  COPY PRESERVES CALENDAR MODE
    // ═══════════════════════════════════════════════════════════

    public function test_copy_preserves_calendar(): void
    {
        $date = MultiCarbon::createJalali(1404, 6, 15);
        $copy = $date->copy();

        $this->assertTrue($copy->isJalali());
        $this->assertSame($date->year, $copy->year);
        $this->assertSame($date->month, $copy->month);
        $this->assertSame($date->day, $copy->day);
    }

    // ═══════════════════════════════════════════════════════════
    //  CALLED BY PARENT SAFETY — Internal Carbon Operations
    // ═══════════════════════════════════════════════════════════

    public function test_carbon_diff_operations_work(): void
    {
        $date1 = MultiCarbon::createJalali(1404, 1, 1);
        $date2 = MultiCarbon::createJalali(1404, 1, 11);

        $this->assertEquals(10, $date1->diffInDays($date2));
    }

    public function test_carbon_add_days_works(): void
    {
        $date = MultiCarbon::createJalali(1404, 1, 1);
        $date->addDays(10);

        $this->assertSame(1404, $date->year);
        $this->assertSame(1, $date->month);
        $this->assertSame(11, $date->day);
    }

    public function test_carbon_sub_days_works(): void
    {
        $date = MultiCarbon::createJalali(1404, 1, 11);
        $date->subDays(10);

        $this->assertSame(1404, $date->year);
        $this->assertSame(1, $date->month);
        $this->assertSame(1, $date->day);
    }

    public function test_carbon_add_hours_works(): void
    {
        $date = MultiCarbon::createJalali(1404, 1, 1, 10, 0, 0);
        $date->addHours(5);

        $this->assertSame(15, $date->hour);
    }

    public function test_timestamp_unchanged(): void
    {
        $date = new MultiCarbon('2025-02-14 12:00:00');

        $ts_jalali = $date->jalali()->timestamp;
        $ts_hijri  = $date->hijri()->timestamp;
        $ts_greg   = $date->gregorian()->timestamp;

        $this->assertSame($ts_jalali, $ts_hijri);
        $this->assertSame($ts_jalali, $ts_greg);
    }

    // ═══════════════════════════════════════════════════════════
    //  MULTI-CALENDAR ON SAME INSTANCE
    // ═══════════════════════════════════════════════════════════

    public function test_switch_between_calendars_on_same_date(): void
    {
        $date = new MultiCarbon('2025-03-21'); // Nowruz 1404

        // Jalali
        $date->jalali();
        $this->assertSame(1404, $date->year);
        $this->assertSame(1, $date->month);
        $this->assertSame(1, $date->day);

        // Gregorian
        $date->gregorian();
        $this->assertSame(2025, $date->year);
        $this->assertSame(3, $date->month);
        $this->assertSame(21, $date->day);

        // Hijri
        $date->hijri();
        $this->assertSame(1446, $date->year);
    }

    // ═══════════════════════════════════════════════════════════
    //  HIJRI-SPECIFIC TESTS
    // ═══════════════════════════════════════════════════════════

    public function test_create_hijri_instance(): void
    {
        $date = MultiCarbon::createHijri(1446, 9, 1); // 1 Ramadan 1446

        $this->assertSame(1446, $date->year);
        $this->assertSame(9, $date->month);
        $this->assertSame(1, $date->day);
    }

    public function test_hijri_format(): void
    {
        $date = MultiCarbon::createHijri(1446, 9, 1);

        $this->assertSame('1446/09/01', $date->format('Y/m/d'));
        $this->assertSame('رمضان', $date->format('F'));
    }

    public function test_hijri_add_months(): void
    {
        $date = MultiCarbon::createHijri(1446, 9, 1);
        $date->addMonths(3);

        $this->assertSame(1446, $date->year);
        $this->assertSame(12, $date->month);
    }

    public function test_hijri_start_of_month(): void
    {
        $date = MultiCarbon::createHijri(1446, 9, 15, 14, 30, 0);
        $date->startOfMonth();

        $this->assertSame(1446, $date->year);
        $this->assertSame(9, $date->month);
        $this->assertSame(1, $date->day);
        $this->assertSame(0, $date->hour);
    }

    public function test_hijri_end_of_month(): void
    {
        $date = MultiCarbon::createHijri(1446, 1, 15);
        $date->endOfMonth();

        $this->assertSame(1446, $date->year);
        $this->assertSame(1, $date->month);
        $this->assertSame(30, $date->day); // Muharram always has 30 days
    }

    public function test_hijri_diff_for_humans(): void
    {
        $now = new MultiCarbon('2025-02-14 12:00:00');
        $now->hijri();

        $past = new MultiCarbon('2025-02-13 12:00:00');
        $past->hijri();

        $result = $past->diffForHumans($now);
        $this->assertStringContainsString('یوم', $result);
        $this->assertStringContainsString('منذ', $result);
    }

    public function test_hijri_is_leap_year(): void
    {
        $date = MultiCarbon::createHijri(1445, 1, 1);
        $this->assertTrue($date->isLeapYear()); // 1445 % 30 = 5 → leap

        $date2 = MultiCarbon::createHijri(1446, 1, 1);
        $this->assertFalse($date2->isLeapYear()); // 1446 % 30 = 6 → not leap
    }

    // ═══════════════════════════════════════════════════════════
    //  CONVERSION & INTEROP
    // ═══════════════════════════════════════════════════════════

    public function test_from_carbon(): void
    {
        $carbon = Carbon::parse('2025-03-21');
        $mc = MultiCarbon::fromCarbon($carbon);

        $this->assertSame(CalendarMode::JALALI, $mc->getCalendar());
        $this->assertSame(1404, $mc->year);
        $this->assertSame(1, $mc->month);
        $this->assertSame(1, $mc->day);
    }

    public function test_from_carbon_hijri(): void
    {
        $carbon = Carbon::parse('2025-02-14');
        $mc = MultiCarbon::fromCarbon($carbon, CalendarMode::HIJRI);

        $this->assertSame(CalendarMode::HIJRI, $mc->getCalendar());
        $this->assertSame(1446, $mc->year);
    }

    public function test_from_date_time(): void
    {
        $dt = new \DateTime('2025-03-21');
        $mc = MultiCarbon::fromDateTime($dt);

        $this->assertSame(1404, $mc->year);
        $this->assertSame(1, $mc->month);
        $this->assertSame(1, $mc->day);
    }

    public function test_to_carbon(): void
    {
        $mc = MultiCarbon::createJalali(1404, 1, 1);
        $carbon = $mc->toCarbon();

        $this->assertInstanceOf(Carbon::class, $carbon);
        $this->assertSame('2025-03-21', $carbon->format('Y-m-d'));
    }

    public function test_to_array(): void
    {
        $mc = MultiCarbon::createJalali(1404, 1, 1, 10, 30, 45);
        $arr = $mc->toArray();

        $this->assertSame(1404, $arr['year']);
        $this->assertSame(1, $arr['month']);
        $this->assertSame(1, $arr['day']);
        $this->assertSame(10, $arr['hour']);
        $this->assertSame(30, $arr['minute']);
        $this->assertSame(45, $arr['second']);
    }

    public function test_parse_format_jalali(): void
    {
        $mc = MultiCarbon::parseFormat('Y/m/d', '1404/01/15');

        $this->assertSame(1404, $mc->year);
        $this->assertSame(1, $mc->month);
        $this->assertSame(15, $mc->day);
    }

    public function test_parse_format_hijri(): void
    {
        $mc = MultiCarbon::parseFormat('Y/m/d', '1446/07/15', CalendarMode::HIJRI);

        $this->assertSame(1446, $mc->year);
        $this->assertSame(7, $mc->month);
        $this->assertSame(15, $mc->day);
    }

    // ═══════════════════════════════════════════════════════════
    //  EXPLICIT GETTERS
    // ═══════════════════════════════════════════════════════════

    public function test_explicit_getters(): void
    {
        $mc = MultiCarbon::createJalali(1404, 6, 15, 14, 30, 45);

        $this->assertSame(1404, $mc->getYear());
        $this->assertSame(6, $mc->getMonth());
        $this->assertSame(15, $mc->getDay());
        $this->assertSame(14, $mc->getHour());
        $this->assertSame(30, $mc->getMinute());
        $this->assertSame(45, $mc->getSecond());
    }

    public function test_get_month_days(): void
    {
        $mc = MultiCarbon::createJalali(1404, 1, 1);
        $this->assertSame(31, $mc->getMonthDays());

        $mc2 = MultiCarbon::createJalali(1404, 12, 1);
        $this->assertSame(29, $mc2->getMonthDays()); // 1404 is not leap

        $mc3 = MultiCarbon::createJalali(1403, 12, 1);
        $this->assertSame(30, $mc3->getMonthDays()); // 1403 IS leap
    }

    public function test_get_month_name(): void
    {
        $mc = MultiCarbon::createJalali(1404, 1, 1);
        $this->assertSame('فروردین', $mc->getMonthName());

        $mc2 = MultiCarbon::createJalali(1404, 7, 1);
        $this->assertSame('مهر', $mc2->getMonthName());
    }

    public function test_get_day_of_year(): void
    {
        $mc = MultiCarbon::createJalali(1404, 1, 1);
        $this->assertSame(1, $mc->getDayOfYear());

        $mc2 = MultiCarbon::createJalali(1404, 2, 1);
        $this->assertSame(32, $mc2->getDayOfYear());
    }

    public function test_get_week_of_year(): void
    {
        $mc = MultiCarbon::createJalali(1404, 1, 1);
        $weekOfYear = $mc->getWeekOfYear();
        $this->assertGreaterThanOrEqual(1, $weekOfYear);
    }

    // ═══════════════════════════════════════════════════════════
    //  COMPARISON METHODS
    // ═══════════════════════════════════════════════════════════

    public function test_is_past_and_future(): void
    {
        $past = new MultiCarbon('2020-01-01');
        $this->assertTrue($past->isPast());
        $this->assertFalse($past->isFuture());

        $future = new MultiCarbon('2030-01-01');
        $this->assertTrue($future->isFuture());
        $this->assertFalse($future->isPast());
    }

    public function test_is_today_yesterday_tomorrow(): void
    {
        $today = new MultiCarbon();
        $this->assertTrue($today->isToday());
        $this->assertFalse($today->isYesterday());
        $this->assertFalse($today->isTomorrow());
    }

    public function test_equals_to(): void
    {
        $a = new MultiCarbon('2025-02-14 12:00:00');
        $b = new MultiCarbon('2025-02-14 12:00:00');
        $c = new MultiCarbon('2025-02-15 12:00:00');

        $this->assertTrue($a->equalsTo($b));
        $this->assertFalse($a->equalsTo($c));
    }

    public function test_greater_less_than(): void
    {
        $a = new MultiCarbon('2025-02-14');
        $b = new MultiCarbon('2025-02-15');

        $this->assertTrue($a->lessThan($b));
        $this->assertTrue($b->greaterThan($a));
        $this->assertTrue($a->lessThanOrEqualsTo($a));
        $this->assertTrue($a->greaterThanOrEqualsTo($a));
    }

    public function test_is_between(): void
    {
        $date  = new MultiCarbon('2025-02-14');
        $start = new MultiCarbon('2025-02-10');
        $end   = new MultiCarbon('2025-02-20');

        $this->assertTrue($date->isBetween($start, $end));
        $this->assertFalse($date->isBetween($end, new MultiCarbon('2025-02-25')));
    }

    // ═══════════════════════════════════════════════════════════
    //  DIFF METHODS
    // ═══════════════════════════════════════════════════════════

    public function test_diff_in_days(): void
    {
        $a = new MultiCarbon('2025-02-14');
        $b = new MultiCarbon('2025-02-17');

        $this->assertEquals(3, $a->diffInDays($b));
        $this->assertEquals(3, $b->diffInDays($a));
    }

    public function test_diff_in_months(): void
    {
        $a = new MultiCarbon('2025-01-01');
        $b = new MultiCarbon('2025-04-01');

        $this->assertEquals(3, $a->diffInMonths($b)); // 90 days / 30 = 3
    }

    public function test_diff_in_years(): void
    {
        $a = new MultiCarbon('2020-01-01');
        $b = new MultiCarbon('2025-01-01');

        $this->assertEquals(5, $a->diffInYears($b)); // 1827 days / 365 = 5
    }

    // ═══════════════════════════════════════════════════════════
    //  GLOBAL HELPER FUNCTIONS
    // ═══════════════════════════════════════════════════════════

    public function test_jdate_helper_returns_instance(): void
    {
        $mc = jdate();
        $this->assertInstanceOf(MultiCarbon::class, $mc);
        $this->assertTrue($mc->isJalali());
    }

    public function test_jdate_helper_with_format(): void
    {
        $result = jdate('Y', mktime(0, 0, 0, 3, 21, 2025));
        $this->assertSame('1404', $result);
    }

    public function test_hdate_helper_returns_instance(): void
    {
        $mc = hdate();
        $this->assertInstanceOf(MultiCarbon::class, $mc);
        $this->assertTrue($mc->isHijri());
    }

    public function test_hdate_helper_with_format(): void
    {
        $result = hdate('Y', mktime(0, 0, 0, 7, 8, 2024));
        $this->assertSame('1446', $result);
    }

    public function test_multicarbon_helper(): void
    {
        $mc = multicarbon();
        $this->assertInstanceOf(MultiCarbon::class, $mc);
    }

    // ═══════════════════════════════════════════════════════════
    //  AGO METHOD
    // ═══════════════════════════════════════════════════════════

    public function test_ago_jalali(): void
    {
        $date = new MultiCarbon('2020-01-01');
        $date->jalali();
        $result = $date->ago();

        // Should be in Persian, containing 'پیش' (ago)
        $this->assertStringContainsString('پیش', $result);
    }

    public function test_ago_hijri(): void
    {
        $date = new MultiCarbon('2020-01-01');
        $date->hijri();
        $result = $date->ago();

        // Should be in Arabic, containing 'منذ' (ago)
        $this->assertStringContainsString('منذ', $result);
    }
}
