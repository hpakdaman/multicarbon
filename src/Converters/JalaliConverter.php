<?php

declare(strict_types=1);

namespace MultiCarbon\Converters;

/**
 * Pure Jalali (Solar Hijri) calendar conversion engine.
 *
 * Algorithm by Roozbeh Pournader and Mohammad Toosi.
 * All methods are static — no state, no side effects.
 */
final class JalaliConverter
{
    /** @var int[] Days in each Jalali month (1-indexed logical, 0-indexed array) */
    private const JALALI_DAYS_IN_MONTH = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

    /** @var int[] Days in each Gregorian month */
    private const GREGORIAN_DAYS_IN_MONTH = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

    // ─── Gregorian → Jalali ─────────────────────────────────────

    /**
     * Convert a Gregorian date to Jalali.
     *
     * @return array{0: int, 1: int, 2: int} [year, month, day]
     */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        $gy2 = $gy - 1600;
        $gm2 = $gm - 1;
        $gd2 = $gd - 1;

        $gDayNo = 365 * $gy2
            + self::div($gy2 + 3, 4)
            - self::div($gy2 + 99, 100)
            + self::div($gy2 + 399, 400);

        for ($i = 0; $i < $gm2; $i++) {
            $gDayNo += self::GREGORIAN_DAYS_IN_MONTH[$i];
        }

        // Leap year adjustment after February
        if ($gm2 > 1 && (($gy2 % 4 === 0 && $gy2 % 100 !== 0) || ($gy2 % 400 === 0))) {
            $gDayNo++;
        }

        $gDayNo += $gd2;
        $jDayNo = $gDayNo - 79;

        $jNp    = self::div($jDayNo, 12053); // 12053 = 365*33 + 32/4
        $jDayNo = $jDayNo % 12053;

        $jy     = 979 + 33 * $jNp + 4 * self::div($jDayNo, 1461); // 1461 = 365*4 + 4/4
        $jDayNo %= 1461;

        if ($jDayNo >= 366) {
            $jy     += self::div($jDayNo - 1, 365);
            $jDayNo  = ($jDayNo - 1) % 365;
        }

        for ($i = 0; $i < 11 && $jDayNo >= self::JALALI_DAYS_IN_MONTH[$i]; $i++) {
            $jDayNo -= self::JALALI_DAYS_IN_MONTH[$i];
        }

        return [$jy, $i + 1, $jDayNo + 1];
    }

    // ─── Jalali → Gregorian ─────────────────────────────────────

    /**
     * Convert a Jalali date to Gregorian.
     *
     * @return array{0: int, 1: int, 2: int} [year, month, day]
     */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        $jy2 = $jy - 979;
        $jm2 = $jm - 1;
        $jd2 = $jd - 1;

        $jDayNo = 365 * $jy2
            + self::div($jy2, 33) * 8
            + self::div($jy2 % 33 + 3, 4);

        for ($i = 0; $i < $jm2; $i++) {
            $jDayNo += self::JALALI_DAYS_IN_MONTH[$i];
        }

        $jDayNo += $jd2;
        $gDayNo  = $jDayNo + 79;

        $gy     = 1600 + 400 * self::div($gDayNo, 146097); // 146097 = 365*400 + 97
        $gDayNo = $gDayNo % 146097;

        $leap = true;

        if ($gDayNo >= 36525) { // 36525 = 365*100 + 25
            $gDayNo--;
            $gy     += 100 * self::div($gDayNo, 36524); // 36524 = 365*100 + 24
            $gDayNo  = $gDayNo % 36524;

            if ($gDayNo >= 365) {
                $gDayNo++;
            } else {
                $leap = false;
            }
        }

        $gy     += 4 * self::div($gDayNo, 1461); // 1461 = 365*4 + 1
        $gDayNo %= 1461;

        if ($gDayNo >= 366) {
            $leap    = false;
            $gDayNo--;
            $gy     += self::div($gDayNo, 365);
            $gDayNo  = $gDayNo % 365;
        }

        for ($i = 0; $gDayNo >= self::GREGORIAN_DAYS_IN_MONTH[$i] + ($i === 1 && $leap ? 1 : 0); $i++) {
            $gDayNo -= self::GREGORIAN_DAYS_IN_MONTH[$i] + ($i === 1 && $leap ? 1 : 0);
        }

        return [$gy, $i + 1, $gDayNo + 1];
    }

    // ─── Jalali Calendar Helpers ────────────────────────────────

    /**
     * Check if a Jalali year is a leap year.
     *
     * Derived from the conversion algorithm itself: a year is leap if
     * Esfand 30 of that year maps to a valid Gregorian date that maps back.
     * Uses the arithmetic cycle: years where ((25 * jy + 11) % 33) < 8 are leap.
     */
    public static function isLeapYear(int $jy): bool
    {
        // The 33-year sub-cycle algorithm — matches the Pournader/Toosi conversion exactly.
        // Leap years are those where: ((25 * jy + 11) mod 33) < 8
        return ((25 * $jy + 11) % 33) < 8;
    }

    /**
     * Get the number of days in a Jalali month.
     */
    public static function daysInMonth(int $jm, int $jy): int
    {
        if ($jm <= 6) {
            return 31;
        }

        if ($jm <= 11) {
            return 30;
        }

        // Month 12 (Esfand)
        return self::isLeapYear($jy) ? 30 : 29;
    }

    /**
     * Get the day-of-year for a Jalali date (1-indexed).
     */
    public static function dayOfYear(int $jm, int $jd): int
    {
        if ($jm <= 6) {
            return ($jm - 1) * 31 + $jd;
        }

        return 186 + ($jm - 7) * 30 + $jd;
    }

    /**
     * Get total days in a Jalali year.
     */
    public static function daysInYear(int $jy): int
    {
        return self::isLeapYear($jy) ? 366 : 365;
    }

    /**
     * Validate a Jalali date.
     */
    public static function isValidDate(int $jy, int $jm, int $jd): bool
    {
        return $jy >= 1
            && $jm >= 1 && $jm <= 12
            && $jd >= 1 && $jd <= self::daysInMonth($jm, $jy);
    }

    // ─── Internal ───────────────────────────────────────────────

    private static function div(int $a, int $b): int
    {
        return intdiv($a, $b);
    }
}
