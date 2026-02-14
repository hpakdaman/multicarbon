<?php

declare(strict_types=1);

namespace MultiCarbon\Localization;

/**
 * Persian (Farsi) locale data for the Jalali calendar.
 */
final class PersianLocale
{
    // ─── Month Names ────────────────────────────────────────────

    private const MONTHS = [
        1  => 'فروردین',
        2  => 'اردیبهشت',
        3  => 'خرداد',
        4  => 'تیر',
        5  => 'مرداد',
        6  => 'شهریور',
        7  => 'مهر',
        8  => 'آبان',
        9  => 'آذر',
        10 => 'دی',
        11 => 'بهمن',
        12 => 'اسفند',
    ];

    private const MONTHS_SHORT = [
        1  => 'فرو',
        2  => 'ارد',
        3  => 'خرد',
        4  => 'تیر',
        5  => 'مرد',
        6  => 'شهر',
        7  => 'مهر',
        8  => 'آبا',
        9  => 'آذر',
        10 => 'دی',
        11 => 'بهم',
        12 => 'اسف',
    ];

    // ─── Weekday Names (Saturday = 0) ───────────────────────────

    /** Maps PHP's date('w') value (0=Sunday) to Persian weekday */
    private const WEEKDAYS = [
        0 => 'یکشنبه',       // Sunday
        1 => 'دوشنبه',       // Monday
        2 => 'سه‌شنبه',      // Tuesday
        3 => 'چهارشنبه',     // Wednesday
        4 => 'پنجشنبه',      // Thursday
        5 => 'جمعه',         // Friday
        6 => 'شنبه',         // Saturday
    ];

    private const WEEKDAYS_SHORT = [
        0 => 'ی',
        1 => 'د',
        2 => 'س',
        3 => 'چ',
        4 => 'پ',
        5 => 'ج',
        6 => 'ش',
    ];

    // ─── AM/PM ──────────────────────────────────────────────────

    private const MERIDIEM = [
        'AM'       => 'قبل از ظهر',
        'PM'       => 'بعد از ظهر',
        'AM_SHORT' => 'ق.ظ',
        'PM_SHORT' => 'ب.ظ',
    ];

    // ─── Ordinal Suffix ─────────────────────────────────────────

    private const ORDINAL_SUFFIX = 'ام';

    // ─── Digit Maps ─────────────────────────────────────────────

    private const FARSI_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    private const LATIN_DIGITS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    // ─── Diff-for-Humans Translations ───────────────────────────

    private const DIFF_TRANSLATIONS = [
        'year'   => ['سال', 'سال'],
        'month'  => ['ماه', 'ماه'],
        'week'   => ['هفته', 'هفته'],
        'day'    => ['روز', 'روز'],
        'hour'   => ['ساعت', 'ساعت'],
        'minute' => ['دقیقه', 'دقیقه'],
        'second' => ['ثانیه', 'ثانیه'],
        'ago'    => 'پیش',
        'from_now' => 'بعد',
        'just_now' => 'همین الان',
    ];

    // ─── Public API ─────────────────────────────────────────────

    public static function monthName(int $month): string
    {
        return self::MONTHS[$month] ?? '';
    }

    public static function monthNameShort(int $month): string
    {
        return self::MONTHS_SHORT[$month] ?? '';
    }

    /**
     * @param int $dayOfWeek PHP's date('w') value: 0=Sunday, 6=Saturday
     */
    public static function weekdayName(int $dayOfWeek): string
    {
        return self::WEEKDAYS[$dayOfWeek] ?? '';
    }

    /**
     * @param int $dayOfWeek PHP's date('w') value: 0=Sunday, 6=Saturday
     */
    public static function weekdayNameShort(int $dayOfWeek): string
    {
        return self::WEEKDAYS_SHORT[$dayOfWeek] ?? '';
    }

    public static function meridiem(string $amPm, bool $short = false): string
    {
        $amPm = strtoupper($amPm);

        if ($short) {
            return $amPm === 'PM' ? self::MERIDIEM['PM_SHORT'] : self::MERIDIEM['AM_SHORT'];
        }

        return $amPm === 'PM' ? self::MERIDIEM['PM'] : self::MERIDIEM['AM'];
    }

    public static function ordinalSuffix(): string
    {
        return self::ORDINAL_SUFFIX;
    }

    /**
     * Convert Latin digits to Farsi digits.
     */
    public static function toFarsiDigits(string $str): string
    {
        return str_replace(self::LATIN_DIGITS, self::FARSI_DIGITS, $str);
    }

    /**
     * Convert Farsi digits to Latin digits.
     */
    public static function toLatinDigits(string $str): string
    {
        return str_replace(self::FARSI_DIGITS, self::LATIN_DIGITS, $str);
    }

    /**
     * Get human-readable diff string.
     *
     * @param string $unit   One of: year, month, week, day, hour, minute, second
     * @param int    $value  The numeric value
     * @param bool   $isPast Whether the diff is in the past
     */
    public static function diffForHumans(string $unit, int $value, bool $isPast): string
    {
        if ($value === 0 && $unit === 'second') {
            return self::DIFF_TRANSLATIONS['just_now'];
        }

        $unitWord = self::DIFF_TRANSLATIONS[$unit][0] ?? $unit;
        $suffix   = $isPast ? self::DIFF_TRANSLATIONS['ago'] : self::DIFF_TRANSLATIONS['from_now'];

        return "{$value} {$unitWord} {$suffix}";
    }
}
