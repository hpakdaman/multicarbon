<?php

declare(strict_types=1);

namespace MultiCarbon\Converters;

/**
 * Pure Hijri Qamari (Islamic Lunar) calendar conversion engine.
 *
 * Uses the standard tabular Islamic calendar algorithm.
 * Based on the well-tested algorithm used in ICU and similar libraries.
 * All methods are static — no state, no side effects.
 */
final class HijriConverter
{
    /**
     * Days in each Hijri month for a NON-leap year.
     * Months alternate 30/29; month 12 is 29 (30 in leap years).
     */
    private const HIJRI_DAYS_IN_MONTH = [30, 29, 30, 29, 30, 29, 30, 29, 30, 29, 30, 29];

    /**
     * Leap year positions within the 30-year cycle.
     */
    private const LEAP_YEARS_IN_CYCLE = [2, 5, 7, 10, 13, 16, 18, 21, 24, 26, 29];

    // ─── Gregorian → Hijri ──────────────────────────────────────

    /**
     * Convert a Gregorian date to Hijri using a direct arithmetic algorithm.
     *
     * @return array{0: int, 1: int, 2: int} [year, month, day]
     */
    public static function toHijri(int $gy, int $gm, int $gd): array
    {
        // Convert to Julian Day Number
        $jd = self::gregorianToJd($gy, $gm, $gd);

        // Days since the Hijri epoch (Julian day 1948439 = July 15, 622 CE Julian)
        // The epoch is the evening of July 15, 622 CE (Julian) = 1 Muharram 1 AH
        $daysSinceEpoch = $jd - 1948440;

        // 30-year cycle contains exactly 10631 days
        $cycles = intdiv($daysSinceEpoch, 10631);
        $remaining = $daysSinceEpoch % 10631;

        // Find the year within the 30-year cycle
        $year = $cycles * 30;
        $daysInYear = 0;
        for ($y = 1; $y <= 30; $y++) {
            $daysInYear = in_array($y, self::LEAP_YEARS_IN_CYCLE, true) ? 355 : 354;
            if ($remaining < $daysInYear) {
                $year += $y;
                break;
            }
            $remaining -= $daysInYear;
        }

        // Find the month
        $month = 1;
        for ($m = 1; $m <= 12; $m++) {
            $daysInMonth = self::daysInMonth($m, $year);
            if ($remaining < $daysInMonth) {
                $month = $m;
                break;
            }
            $remaining -= $daysInMonth;
        }

        $day = $remaining + 1;

        return [(int) $year, (int) $month, (int) $day];
    }

    // ─── Hijri → Gregorian ──────────────────────────────────────

    /**
     * Convert a Hijri date to Gregorian.
     *
     * @return array{0: int, 1: int, 2: int} [year, month, day]
     */
    public static function toGregorian(int $hy, int $hm, int $hd): array
    {
        // Calculate total days from Hijri epoch
        $daysSinceEpoch = 0;

        // Days for complete 30-year cycles
        $cycles = intdiv($hy - 1, 30);
        $daysSinceEpoch += $cycles * 10631;

        // Days for remaining years
        $yearInCycle = ($hy - 1) % 30;
        for ($y = 1; $y <= $yearInCycle; $y++) {
            $daysSinceEpoch += in_array($y, self::LEAP_YEARS_IN_CYCLE, true) ? 355 : 354;
        }

        // Days for complete months
        for ($m = 1; $m < $hm; $m++) {
            $daysSinceEpoch += self::daysInMonth($m, $hy);
        }

        // Add days
        $daysSinceEpoch += $hd - 1;

        // Convert back to Gregorian
        $jd = $daysSinceEpoch + 1948440;

        return self::jdToGregorian($jd);
    }

    // ─── Hijri Calendar Helpers ─────────────────────────────────

    /**
     * Check if a Hijri year is a leap year.
     */
    public static function isLeapYear(int $hy): bool
    {
        $pos = (($hy - 1) % 30) + 1;

        return in_array($pos, self::LEAP_YEARS_IN_CYCLE, true);
    }

    /**
     * Get the number of days in a Hijri month.
     */
    public static function daysInMonth(int $hm, int $hy): int
    {
        if ($hm === 12 && self::isLeapYear($hy)) {
            return 30;
        }

        return self::HIJRI_DAYS_IN_MONTH[$hm - 1];
    }

    /**
     * Get the day-of-year for a Hijri date (1-indexed).
     */
    public static function dayOfYear(int $hm, int $hd): int
    {
        $days = 0;
        for ($i = 1; $i < $hm; $i++) {
            $days += self::HIJRI_DAYS_IN_MONTH[$i - 1];
        }

        return $days + $hd;
    }

    /**
     * Get total days in a Hijri year.
     */
    public static function daysInYear(int $hy): int
    {
        return self::isLeapYear($hy) ? 355 : 354;
    }

    /**
     * Validate a Hijri date.
     */
    public static function isValidDate(int $hy, int $hm, int $hd): bool
    {
        return $hy >= 1
            && $hm >= 1 && $hm <= 12
            && $hd >= 1 && $hd <= self::daysInMonth($hm, $hy);
    }

    // ─── Julian Day Number conversions ──────────────────────────

    /**
     * Gregorian date → Julian Day Number (integer, noon-based).
     */
    private static function gregorianToJd(int $year, int $month, int $day): int
    {
        // Algorithm from Meeus, "Astronomical Algorithms"
        if ($month <= 2) {
            $year--;
            $month += 12;
        }

        $A = intdiv($year, 100);
        $B = 2 - $A + intdiv($A, 4);

        return (int) floor(365.25 * ($year + 4716))
            + (int) floor(30.6001 * ($month + 1))
            + $day + $B - 1524;
    }

    /**
     * Julian Day Number → Gregorian date.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    private static function jdToGregorian(int $jd): array
    {
        // Algorithm from Meeus
        $a = $jd;

        if ($jd >= 2299161) {
            $alpha = intdiv(4 * $jd - 7468861, 146097);
            $a = $jd + 1 + $alpha - intdiv($alpha, 4);
        }

        $b = $a + 1524;
        $c = intdiv(20 * $b - 2442, 7305);
        $d = intdiv(1461 * $c, 4);
        $e = intdiv(10000 * ($b - $d), 306001);

        $day   = $b - $d - intdiv(306001 * $e, 10000);
        $month = $e < 14 ? $e - 1 : $e - 13;
        $year  = $month > 2 ? $c - 4716 : $c - 4715;

        return [$year, $month, $day];
    }
}
