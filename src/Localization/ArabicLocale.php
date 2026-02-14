<?php

declare(strict_types=1);

namespace MultiCarbon\Localization;

/**
 * Arabic locale data for the Hijri (Islamic Lunar) calendar.
 */
final class ArabicLocale
{
    // ─── Month Names ────────────────────────────────────────────

    private const MONTHS = [
        1  => 'محرم',
        2  => 'صفر',
        3  => 'ربیع‌الاول',
        4  => 'ربیع‌الثانی',
        5  => 'جمادی‌الاول',
        6  => 'جمادی‌الثانی',
        7  => 'رجب',
        8  => 'شعبان',
        9  => 'رمضان',
        10 => 'شوال',
        11 => 'ذیقعده',
        12 => 'ذیحجه',
    ];

    private const MONTHS_SHORT = [
        1  => 'محر',
        2  => 'صفر',
        3  => 'رب۱',
        4  => 'رب۲',
        5  => 'جم۱',
        6  => 'جم۲',
        7  => 'رجب',
        8  => 'شعب',
        9  => 'رمض',
        10 => 'شوا',
        11 => 'ذیق',
        12 => 'ذیح',
    ];

    // ─── Weekday Names ──────────────────────────────────────────

    /** Maps PHP's date('w') value (0=Sunday) to Arabic weekday */
    private const WEEKDAYS = [
        0 => 'الأحد',          // Sunday
        1 => 'الاثنین',        // Monday
        2 => 'الثلاثاء',       // Tuesday
        3 => 'الأربعاء',       // Wednesday
        4 => 'الخمیس',         // Thursday
        5 => 'الجمعه',         // Friday
        6 => 'السبت',          // Saturday
    ];

    private const WEEKDAYS_SHORT = [
        0 => 'أح',
        1 => 'اث',
        2 => 'ثل',
        3 => 'أر',
        4 => 'خم',
        5 => 'جم',
        6 => 'سب',
    ];

    // ─── AM/PM ──────────────────────────────────────────────────

    private const MERIDIEM = [
        'AM'       => 'صباحاً',
        'PM'       => 'مساءً',
        'AM_SHORT' => 'ص',
        'PM_SHORT' => 'م',
    ];

    // ─── Ordinal Suffix ─────────────────────────────────────────

    private const ORDINAL_SUFFIX = '';

    // ─── Digit Maps ─────────────────────────────────────────────

    private const ARABIC_DIGITS  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    private const LATIN_DIGITS   = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    // ─── Diff-for-Humans Translations ───────────────────────────

    private const DIFF_TRANSLATIONS = [
        'year'   => ['سنة', 'سنوات'],
        'month'  => ['شهر', 'أشهر'],
        'week'   => ['أسبوع', 'أسابیع'],
        'day'    => ['یوم', 'أیام'],
        'hour'   => ['ساعة', 'ساعات'],
        'minute' => ['دقیقة', 'دقائق'],
        'second' => ['ثانیة', 'ثوانٍ'],
        'ago'    => 'منذ',
        'from_now' => 'بعد',
        'just_now' => 'الآن',
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
     * Convert Latin digits to Arabic-Indic digits.
     */
    public static function toArabicDigits(string $str): string
    {
        return str_replace(self::LATIN_DIGITS, self::ARABIC_DIGITS, $str);
    }

    /**
     * Convert Arabic-Indic digits to Latin digits.
     */
    public static function toLatinDigits(string $str): string
    {
        return str_replace(self::ARABIC_DIGITS, self::LATIN_DIGITS, $str);
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

        $unitWords = self::DIFF_TRANSLATIONS[$unit] ?? [$unit, $unit];
        $unitWord  = $value <= 2 ? $unitWords[0] : $unitWords[1];
        $direction = $isPast ? self::DIFF_TRANSLATIONS['ago'] : self::DIFF_TRANSLATIONS['from_now'];

        return "{$direction} {$value} {$unitWord}";
    }
}
