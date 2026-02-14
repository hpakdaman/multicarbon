<?php

declare(strict_types=1);

namespace Tests\MultiCarbon;

use MultiCarbon\Converters\HijriConverter;
use PHPUnit\Framework\TestCase;

class HijriConverterTest extends TestCase
{
    // ─── Gregorian → Hijri ──────────────────────────────────────

    public function test_known_conversions_to_hijri(): void
    {
        // Feb 14, 2025 ≈ 15 Sha'ban 1446
        $this->assertSame([1446, 8, 15], HijriConverter::toHijri(2025, 2, 14));

        // 1 Muharram 1446 = July 8, 2024
        $this->assertSame([1446, 1, 1], HijriConverter::toHijri(2024, 7, 8));
    }

    // ─── Hijri → Gregorian ──────────────────────────────────────

    public function test_known_conversions_to_gregorian(): void
    {
        // 1 Muharram 1446 → July 8, 2024
        $this->assertSame([2024, 7, 8], HijriConverter::toGregorian(1446, 1, 1));
    }

    // ─── Round-Trip ─────────────────────────────────────────────

    public function test_round_trip_conversion(): void
    {
        // Hijri → Gregorian → Hijri
        $original = [1446, 6, 15];
        $greg = HijriConverter::toGregorian(...$original);
        $back = HijriConverter::toHijri(...$greg);
        $this->assertSame($original, $back);

        // Gregorian → Hijri → Gregorian
        $original = [2025, 5, 10];
        $hijri = HijriConverter::toHijri(...$original);
        $back = HijriConverter::toGregorian(...$hijri);
        $this->assertSame($original, $back);
    }

    // ─── Leap Year ──────────────────────────────────────────────

    public function test_leap_years(): void
    {
        $this->assertTrue(HijriConverter::isLeapYear(1445));
        $this->assertTrue(HijriConverter::isLeapYear(1447));
        $this->assertFalse(HijriConverter::isLeapYear(1446));
    }

    // ─── Days in Month ──────────────────────────────────────────

    public function test_days_in_month(): void
    {
        // Odd months: 30 days, even months: 29 days
        $this->assertSame(30, HijriConverter::daysInMonth(1, 1446));  // Muharram
        $this->assertSame(29, HijriConverter::daysInMonth(2, 1446));  // Safar
        $this->assertSame(30, HijriConverter::daysInMonth(9, 1446));  // Ramadan

        // Month 12: 29 normally, 30 in leap year
        $this->assertSame(29, HijriConverter::daysInMonth(12, 1446)); // non-leap
        $this->assertSame(30, HijriConverter::daysInMonth(12, 1445)); // leap
    }

    // ─── Validation ─────────────────────────────────────────────

    public function test_validation(): void
    {
        $this->assertTrue(HijriConverter::isValidDate(1446, 1, 1));
        $this->assertTrue(HijriConverter::isValidDate(1446, 1, 30));
        $this->assertTrue(HijriConverter::isValidDate(1445, 12, 30)); // leap

        $this->assertFalse(HijriConverter::isValidDate(1446, 12, 30)); // non-leap
        $this->assertFalse(HijriConverter::isValidDate(1446, 13, 1));  // invalid month
        $this->assertFalse(HijriConverter::isValidDate(1446, 1, 31));  // max 30
    }
}
