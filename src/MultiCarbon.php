<?php

declare(strict_types=1);

namespace MultiCarbon;

use Carbon\Carbon;
use MultiCarbon\Converters\JalaliConverter;
use MultiCarbon\Converters\HijriConverter;
use MultiCarbon\Localization\PersianLocale;
use MultiCarbon\Localization\ArabicLocale;

/**
 * MultiCarbon — Multi-calendar extension for Carbon.
 *
 * Supports Jalali (Solar Hijri), Hijri Qamari (Islamic Lunar), and Gregorian calendars.
 * Carbon's internals remain completely untouched — all conversion happens at the
 * boundary between user code and Carbon's internal Gregorian engine.
 *
 * Strategy:
 *   When the USER calls a method   → returns values in the active calendar (Jalali/Hijri)
 *   When CARBON INTERNALLY calls   → returns Gregorian so parent logic works correctly
 *   Detection is done via debug_backtrace() in calledByParent()
 */
class MultiCarbon extends Carbon
{
    // ─── Calendar Mode ──────────────────────────────────────────

    /** @var string Active calendar mode for this instance */
    protected string $calendarMode = CalendarMode::JALALI;

    // ─── Digit Display ──────────────────────────────────────────

    public const DIGITS_LATIN = 'latin';
    public const DIGITS_FARSI = 'farsi';
    public const DIGITS_ARABIC = 'arabic';

    /** @var string Default digit display type */
    protected static string $defaultDigitsType = self::DIGITS_LATIN;

    // ─── Week Configuration (Iranian week) ──────────────────────

    public const SATURDAY  = 0;
    public const SUNDAY    = 1;
    public const MONDAY    = 2;
    public const TUESDAY   = 3;
    public const WEDNESDAY = 4;
    public const THURSDAY  = 5;
    public const FRIDAY    = 6;

    protected static $weekStartsAt = self::SATURDAY;
    protected static $weekEndsAt   = self::FRIDAY;
    protected static $weekendDays  = [self::FRIDAY];

    // ─── Default Format ─────────────────────────────────────────

    public const DEFAULT_TO_STRING_FORMAT = 'Y/m/d H:i:s';

    protected static $toStringFormat = self::DEFAULT_TO_STRING_FORMAT;

    // ═══════════════════════════════════════════════════════════
    //  CALENDAR MODE CONTROL
    // ═══════════════════════════════════════════════════════════

    /**
     * Set the active calendar mode.
     *
     * @return $this
     */
    public function setCalendar(string $mode): static
    {
        if (CalendarMode::isValid($mode)) {
            $this->calendarMode = $mode;
        }

        return $this;
    }

    /**
     * Get the active calendar mode.
     */
    public function getCalendar(): string
    {
        return $this->calendarMode;
    }

    /**
     * Switch to Jalali mode.
     *
     * @return $this
     */
    public function jalali(): static
    {
        return $this->setCalendar(CalendarMode::JALALI);
    }

    /**
     * Switch to Hijri mode.
     *
     * @return $this
     */
    public function hijri(): static
    {
        return $this->setCalendar(CalendarMode::HIJRI);
    }

    /**
     * Switch to Gregorian mode.
     *
     * @return $this
     */
    public function gregorian(): static
    {
        return $this->setCalendar(CalendarMode::GREGORIAN);
    }

    /**
     * Is the current mode Jalali?
     */
    public function isJalali(): bool
    {
        return $this->calendarMode === CalendarMode::JALALI;
    }

    /**
     * Is the current mode Hijri?
     */
    public function isHijri(): bool
    {
        return $this->calendarMode === CalendarMode::HIJRI;
    }

    /**
     * Is the current mode Gregorian?
     */
    public function isGregorian(): bool
    {
        return $this->calendarMode === CalendarMode::GREGORIAN;
    }

    // ═══════════════════════════════════════════════════════════
    //  DIGIT DISPLAY CONTROL
    // ═══════════════════════════════════════════════════════════

    /**
     * Set the global default digit type.
     */
    public static function setDigitsType(string $type): void
    {
        if (in_array($type, [self::DIGITS_LATIN, self::DIGITS_FARSI, self::DIGITS_ARABIC], true)) {
            self::$defaultDigitsType = $type;
        }
    }

    /**
     * Get the current default digit type.
     */
    public static function getDigitsType(): string
    {
        return self::$defaultDigitsType;
    }

    // ═══════════════════════════════════════════════════════════
    //  PARENT-CALL DETECTION (The Core Strategy)
    // ═══════════════════════════════════════════════════════════

    /** @var string|null Cached parent directory path */
    private static ?string $parentDir = null;

    /**
     * Get the parent (Carbon) source directory.
     */
    private static function getParentDir(): string
    {
        if (self::$parentDir === null) {
            self::$parentDir = dirname((new \ReflectionClass(Carbon::class))->getFileName());
        }

        return self::$parentDir;
    }

    /**
     * Detect if the current method was called by Carbon's parent class internals.
     *
     * In debug_backtrace(), each frame's 'file' is WHERE the call was made FROM.
     * So when __get() in Date.php calls $this->get(), frame[0] for get() shows
     * Date.php as the file — that's just the dispatch, not a real internal call.
     *
     * Backtrace for `$date->year` from user code:
     *   [0] get()  file=Date.php:822      ← __get dispatching to get() — skip
     *   [1] __get() file=user_code.php     ← actual origin → not Carbon → return false
     *
     * Backtrace for `$this->year` inside Carbon's startOfMonth():
     *   [0] get()  file=Date.php:822      ← __get dispatching to get() — skip
     *   [1] __get() file=Boundaries.php    ← actual origin → Carbon → return true
     */
    protected static function calledByParent(string $function): bool
    {
        $parentDir = self::getParentDir();
        $trace     = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        // Strategy: find the frame for $function itself. Its 'file' tells us
        // WHERE $function was called FROM. If that file is inside Carbon's
        // source directory, a parent method invoked us → return true.
        //
        // For magic dispatch chains (__get→get, __set→set), the $function
        // frame's file is Carbon's Date.php (because __get calls get()).
        // In that case we also need to check where __get was called from,
        // because __get is just a dispatcher. We walk up past any magic
        // dispatch frames to find the true originator.
        //
        // Example: user code → Carbon's __get (Date.php) → our get()
        //   Frame 0: calledByParent  file=MultiCarbon.php
        //   Frame 1: get             file=Date.php:822     ← called from __get
        //   Frame 2: __get           file=user_code.php    ← called from user
        //   → $function frame file = Date.php (parent), but __get was called
        //     from user code → return false
        //
        // Example: Carbon parent → Carbon's __get (Date.php) → our get()
        //   Frame 1: get             file=Date.php:822
        //   Frame 2: __get           file=Date.php         ← called from parent
        //   → return true
        //
        // Example: Carbon's setDateTime → our setDate()
        //   Frame 1: setDate         file=Date.php:1539    ← called from parent
        //   → $function frame file = Date.php → return true (no magic dispatch)

        $magicDispatchers = ['__get', '__set', '__call', '__callStatic'];
        $skipSelf         = ['calledByParent'];

        $functionFrameFile = null;

        for ($i = 0, $count = count($trace); $i < $count; $i++) {
            $func = $trace[$i]['function'] ?? '';

            // Skip our own frame
            if (in_array($func, $skipSelf, true)) {
                continue;
            }

            // Found the $function frame — record where it was called from
            if ($func === $function) {
                $functionFrameFile = $trace[$i]['file'] ?? '';

                // If called from a non-parent file, it's user code → false
                if (!str_starts_with($functionFrameFile, $parentDir)) {
                    return false;
                }

                // Called from parent — but might be via magic dispatcher.
                // Continue scanning to check if a magic dispatcher is next.
                continue;
            }

            // If we already found $function and this is a magic dispatcher,
            // check where the dispatcher was called from instead
            if ($functionFrameFile !== null && in_array($func, $magicDispatchers, true)) {
                $dispatcherFile = $trace[$i]['file'] ?? '';
                return str_starts_with($dispatcherFile, $parentDir);
            }

            // If we already found $function and the next real frame is NOT
            // a magic dispatcher, trust the $function frame's file
            if ($functionFrameFile !== null) {
                return str_starts_with($functionFrameFile, $parentDir);
            }
        }

        // If we found $function frame but nothing followed, use its file
        if ($functionFrameFile !== null) {
            return str_starts_with($functionFrameFile, $parentDir);
        }

        return false;
    }

    // ═══════════════════════════════════════════════════════════
    //  CALENDAR DATE RESOLUTION
    // ═══════════════════════════════════════════════════════════

    /**
     * Get the current date components in the active calendar.
     *
     * @return array{0: int, 1: int, 2: int} [year, month, day]
     */
    public function getCalendarDate(): array
    {
        $gy = (int) parent::rawFormat('Y');
        $gm = (int) parent::rawFormat('n');
        $gd = (int) parent::rawFormat('j');

        return match ($this->calendarMode) {
            CalendarMode::JALALI => JalaliConverter::toJalali($gy, $gm, $gd),
            CalendarMode::HIJRI  => HijriConverter::toHijri($gy, $gm, $gd),
            default              => [$gy, $gm, $gd],
        };
    }

    /**
     * Convert a date in the active calendar to Gregorian.
     *
     * @return array{0: int, 1: int, 2: int} [year, month, day]
     */
    protected function toGregorianDate(int $year, int $month, int $day): array
    {
        return match ($this->calendarMode) {
            CalendarMode::JALALI => JalaliConverter::toGregorian($year, $month, $day),
            CalendarMode::HIJRI  => HijriConverter::toGregorian($year, $month, $day),
            default              => [$year, $month, $day],
        };
    }

    // ═══════════════════════════════════════════════════════════
    //  PROPERTY ACCESS — __get() / get()
    // ═══════════════════════════════════════════════════════════

    /**
     * Intercept property reads to return active-calendar values.
     *
     * When Carbon internally reads $this->year, $this->month etc.,
     * calledByParent() detects it and returns Gregorian.
     * When user code reads the same properties, we return converted values.
     */
    public function get($name): mixed
    {
        // If Carbon is calling internally, return Gregorian (default behavior)
        if ($this->calendarMode === CalendarMode::GREGORIAN || static::calledByParent(__FUNCTION__)) {
            return parent::get($name);
        }

        // Calendar-aware properties
        switch ($name) {
            case 'year':
                return $this->getCalendarDate()[0];

            case 'month':
                return $this->getCalendarDate()[1];

            case 'day':
                return $this->getCalendarDate()[2];

            case 'daysInMonth':
                [$y, $m] = $this->getCalendarDate();
                return $this->getCalendarDaysInMonth($m, $y);

            case 'dayOfYear':
                [$y, $m, $d] = $this->getCalendarDate();
                return $this->getCalendarDayOfYear($m, $d);

            case 'daysInYear':
                $y = $this->getCalendarDate()[0];
                return $this->getCalendarDaysInYear($y);

            case 'quarter':
                $m = $this->getCalendarDate()[1];
                return (int) ceil($m / 3);

            case 'weekOfMonth':
                return (int) ceil($this->getCalendarDate()[2] / static::DAYS_PER_WEEK);

            case 'dayOfWeek':
                // Remap: PHP's 0=Sunday → Iranian 0=Saturday
                $phpDow = (int) parent::rawFormat('w');
                return ($phpDow + 1) % 7;

            case 'monthName':
                return $this->getCalendarMonthName($this->getCalendarDate()[1]);

            case 'shortMonthName':
                return $this->getCalendarMonthNameShort($this->getCalendarDate()[1]);

            case 'dayName':
                $phpDow = (int) parent::rawFormat('w');
                return $this->getCalendarWeekdayName($phpDow);

            case 'shortDayName':
                $phpDow = (int) parent::rawFormat('w');
                return $this->getCalendarWeekdayNameShort($phpDow);

            default:
                return parent::get($name);
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  PROPERTY WRITE — set()
    // ═══════════════════════════════════════════════════════════

    /**
     * Override set() to handle calendar-aware property writes.
     *
     * Carbon's set() internally reads current date via rawFormat('Y-n-j-G-i-s')
     * which is Gregorian, then calls setDateTime(). When the user writes
     * $date->year = 1404 (Jalali), we need to convert before storing.
     */
    public function set($name, $value = null): static
    {
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                $this->set($key, $val);
            }
            return $this;
        }

        if ($this->calendarMode === CalendarMode::GREGORIAN || static::calledByParent(__FUNCTION__)) {
            return parent::set($name, $value);
        }

        $dateUnits = ['year', 'month', 'day'];
        if (in_array($name, $dateUnits, true)) {
            [$cy, $cm, $cd] = $this->getCalendarDate();
            $$name = $value; // Overwrite the target unit with user value
            // $cy, $cm, $cd now have one modified by $$name
            $year  = $name === 'year'  ? $value : $cy;
            $month = $name === 'month' ? $value : $cm;
            $day   = $name === 'day'   ? $value : $cd;

            [$gy, $gm, $gd] = $this->toGregorianDate($year, $month, $day);
            [$hour, $minute, $second] = array_map('intval', explode('-', parent::rawFormat('G-i-s')));

            return parent::setDate($gy, $gm, $gd);
        }

        return parent::set($name, $value);
    }

    // ═══════════════════════════════════════════════════════════
    //  FORMAT — The Most Critical Override
    // ═══════════════════════════════════════════════════════════

    /**
     * Format the date using the active calendar system.
     *
     * Every format character that involves date components (Y, m, d, etc.)
     * is intercepted and converted. Time-only characters (H, i, s, etc.)
     * pass through to Carbon's native formatting.
     */
    public function format(string $format = self::DEFAULT_TO_STRING_FORMAT): string
    {
        // If called by Carbon internally, return Gregorian
        if ($this->calendarMode === CalendarMode::GREGORIAN || static::calledByParent(__FUNCTION__)) {
            return parent::format($format);
        }

        [$cy, $cm, $cd] = $this->getCalendarDate();
        $timestamp = parent::getTimestamp();

        $output = '';
        $len    = strlen($format);

        for ($i = 0; $i < $len; $i++) {
            $ch = $format[$i];

            // Escaped character — pass through literally
            if ($ch === '\\') {
                if ($i + 1 < $len) {
                    $output .= $format[$i + 1];
                    $i++;
                }
                continue;
            }

            // Time-only and timezone formats — pass through to PHP
            if (in_array($ch, ['B', 'h', 'H', 'g', 'G', 'i', 's', 'I', 'U', 'u', 'v', 'Z', 'O', 'P', 'p', 'T', 'e', 'c', 'r'], true)) {
                $output .= date($ch, $timestamp);
                continue;
            }

            switch ($ch) {
                // ── Year ────────────────────────────
                case 'Y':
                    $output .= $cy;
                    break;
                case 'y':
                    $output .= substr((string) $cy, -2);
                    break;

                // ── Month ───────────────────────────
                case 'm':
                    $output .= sprintf('%02d', $cm);
                    break;
                case 'n':
                    $output .= $cm;
                    break;
                case 'F':
                    $output .= $this->getCalendarMonthName($cm);
                    break;
                case 'M':
                    $output .= $this->getCalendarMonthNameShort($cm);
                    break;

                // ── Day ─────────────────────────────
                case 'd':
                    $output .= sprintf('%02d', $cd);
                    break;
                case 'j':
                    $output .= $cd;
                    break;

                // ── Day of Week ─────────────────────
                case 'D':
                    $phpDow = (int) date('w', $timestamp);
                    $output .= $this->getCalendarWeekdayNameShort($phpDow);
                    break;
                case 'l':
                    $phpDow = (int) date('w', $timestamp);
                    $output .= $this->getCalendarWeekdayName($phpDow);
                    break;
                case 'w':
                    // Remap to Iranian week: Sat=0, Sun=1, ..., Fri=6
                    $phpDow = (int) date('w', $timestamp);
                    $output .= ($phpDow + 1) % 7;
                    break;
                case 'N':
                    // ISO day-of-week remapped for Iranian calendar
                    $phpDow = (int) date('w', $timestamp);
                    $iranDow = ($phpDow + 1) % 7;
                    $output .= $iranDow === 0 ? 7 : $iranDow;
                    break;

                // ── AM/PM ───────────────────────────
                case 'A':
                    $output .= $this->getCalendarMeridiem(date('A', $timestamp), false);
                    break;
                case 'a':
                    $output .= $this->getCalendarMeridiem(date('A', $timestamp), true);
                    break;

                // ── Ordinal ─────────────────────────
                case 'S':
                    $output .= $this->getCalendarOrdinalSuffix();
                    break;

                // ── Days in Month ───────────────────
                case 't':
                    $output .= $this->getCalendarDaysInMonth($cm, $cy);
                    break;

                // ── Leap Year ───────────────────────
                case 'L':
                    $output .= $this->isCalendarLeapYear($cy) ? '1' : '0';
                    break;

                // ── Day of Year ─────────────────────
                case 'z':
                    $output .= $this->getCalendarDayOfYear($cm, $cd) - 1; // PHP's z is 0-indexed
                    break;

                // ── Week of Year ────────────────────
                case 'W':
                    $firstDayDow = $this->copy()->setCalendarDate($cy, 1, 1)->dayOfWeek;
                    $output .= (int) ceil(($this->getCalendarDayOfYear($cm, $cd) + $firstDayDow) / 7);
                    break;

                // ── Anything else — pass through literally
                default:
                    $output .= $ch;
            }
        }

        return $this->applyDigits($output);
    }

    // ═══════════════════════════════════════════════════════════
    //  DATE CREATION — Factory Methods
    // ═══════════════════════════════════════════════════════════

    /**
     * Create a new instance from calendar-specific date components.
     */
    public static function create($year = 0, $month = 1, $day = 1, $hour = 0, $minute = 0, $second = 0, $tz = null): ?static
    {
        if (static::calledByParent(__FUNCTION__)) {
            return parent::create($year, $month, $day, $hour, $minute, $second, $tz);
        }

        $instance = new static('now', $tz);

        $calYear  = $year  === 0 ? $instance->year  : $year;
        $calMonth = $month === 0 ? $instance->month : $month;
        $calDay   = $day   === 0 ? $instance->day   : $day;

        [$gy, $gm, $gd] = $instance->toGregorianDate((int) $calYear, (int) $calMonth, (int) $calDay);

        return parent::createFromFormat(
            'Y-n-j G:i:s',
            sprintf('%s-%s-%s %s:%02s:%02s', $gy, $gm, $gd, $hour, $minute, $second),
            $tz
        )->setCalendar($instance->calendarMode);
    }

    /**
     * Create from just a date (time set to now).
     */
    public static function createFromDate($year = null, $month = null, $day = null, $tz = null)
    {
        if (static::calledByParent(__FUNCTION__)) {
            return parent::createFromDate($year, $month, $day, $tz);
        }

        return static::create($year ?? 0, $month ?? 0, $day ?? 0, null, null, null, $tz);
    }

    /**
     * Create a Jalali instance directly.
     */
    public static function createJalali(int $year = 0, int $month = 1, int $day = 1, int $hour = 0, int $minute = 0, int $second = 0, $tz = null): static
    {
        $temp = new static('now', $tz);
        $temp->calendarMode = CalendarMode::JALALI;

        $calYear  = $year  === 0 ? $temp->year  : $year;
        $calMonth = $month === 0 ? $temp->month : $month;
        $calDay   = $day   === 0 ? $temp->day   : $day;

        [$gy, $gm, $gd] = JalaliConverter::toGregorian($calYear, $calMonth, $calDay);

        // Use Gregorian string directly to avoid our overridden format() interfering
        $new = new static(
            sprintf('%04d-%02d-%02d %02d:%02d:%02d', $gy, $gm, $gd, $hour, $minute, $second),
            $tz
        );
        $new->calendarMode = CalendarMode::JALALI;

        return $new;
    }

    /**
     * Create a Hijri instance directly.
     */
    public static function createHijri(int $year = 0, int $month = 1, int $day = 1, int $hour = 0, int $minute = 0, int $second = 0, $tz = null): static
    {
        $temp = new static('now', $tz);
        $temp->calendarMode = CalendarMode::HIJRI;

        $calYear  = $year  === 0 ? $temp->year  : $year;
        $calMonth = $month === 0 ? $temp->month : $month;
        $calDay   = $day   === 0 ? $temp->day   : $day;

        [$gy, $gm, $gd] = HijriConverter::toGregorian($calYear, $calMonth, $calDay);

        $new = new static(
            sprintf('%04d-%02d-%02d %02d:%02d:%02d', $gy, $gm, $gd, $hour, $minute, $second),
            $tz
        );
        $new->calendarMode = CalendarMode::HIJRI;

        return $new;
    }

    // ═══════════════════════════════════════════════════════════
    //  setDate / setDateTime — The Central Write Path
    // ═══════════════════════════════════════════════════════════

    /**
     * Set the date using active-calendar components.
     *
     * When Carbon internally calls setDate(), calledByParent() returns true
     * and we pass Gregorian values straight to DateTime.
     * When user code calls it, we convert from active calendar to Gregorian.
     */
    public function setDate(int $year, int $month, int $day): static
    {
        if ($this->calendarMode === CalendarMode::GREGORIAN || static::calledByParent(__FUNCTION__)) {
            return parent::setDate($year, $month, $day);
        }

        [$gy, $gm, $gd] = $this->toGregorianDate($year, $month, $day);

        return parent::setDate($gy, $gm, $gd);
    }

    /**
     * Set date using active-calendar components, exposed as a named method.
     */
    public function setCalendarDate(int $year, int $month, int $day): static
    {
        [$gy, $gm, $gd] = $this->toGregorianDate($year, $month, $day);
        parent::setDate($gy, $gm, $gd);

        return $this;
    }

    /**
     * Set the date and time using active-calendar components.
     */
    public function setDateTime(int $year, int $month, int $day, int $hour, int $minute, int $second = 0, int $microseconds = 0): static
    {
        if ($this->calendarMode === CalendarMode::GREGORIAN || static::calledByParent(__FUNCTION__)) {
            return parent::setDateTime($year, $month, $day, $hour, $minute, $second, $microseconds);
        }

        [$gy, $gm, $gd] = $this->toGregorianDate($year, $month, $day);

        return parent::setDateTime($gy, $gm, $gd, $hour, $minute, $second, $microseconds);
    }

    // ═══════════════════════════════════════════════════════════
    //  setUnit — Magic Setter Handler
    // ═══════════════════════════════════════════════════════════

    /**
     * Override setUnit to work with active calendar.
     *
     * Carbon's setUnit reads $this->year/month/day (which return calendar values
     * via our __get override), then calls setDate (which we convert back).
     * This creates a correct round-trip: calendar→read→modify→write→gregorian.
     */
    public function setUnit($unit, $value = null): static
    {
        if ($this->calendarMode === CalendarMode::GREGORIAN || static::calledByParent(__FUNCTION__)) {
            return parent::setUnit($unit, $value);
        }

        $unit = static::singularUnit($unit);
        $dateUnits = ['year', 'month', 'day'];

        if (in_array($unit, $dateUnits, true)) {
            [$cy, $cm, $cd] = $this->getCalendarDate();
            $components = ['year' => $cy, 'month' => $cm, 'day' => $cd];
            $components[$unit] = (int) $value;

            return $this->setCalendarDate($components['year'], $components['month'], $components['day']);
        }

        return parent::setUnit($unit, $value);
    }

    // ═══════════════════════════════════════════════════════════
    //  BOUNDARY METHODS — Calendar-Aware
    // ═══════════════════════════════════════════════════════════

    /**
     * Set to the start of the current month in the active calendar.
     */
    public function startOfMonth()
    {
        if ($this->calendarMode === CalendarMode::GREGORIAN) {
            return parent::startOfMonth();
        }

        [$cy, $cm] = $this->getCalendarDate();
        $this->setCalendarDate($cy, $cm, 1);

        return $this->startOfDay();
    }

    /**
     * Set to the end of the current month in the active calendar.
     */
    public function endOfMonth()
    {
        if ($this->calendarMode === CalendarMode::GREGORIAN) {
            return parent::endOfMonth();
        }

        [$cy, $cm] = $this->getCalendarDate();
        $lastDay = $this->getCalendarDaysInMonth($cm, $cy);
        $this->setCalendarDate($cy, $cm, $lastDay);

        return $this->endOfDay();
    }

    /**
     * Set to the start of the current year in the active calendar.
     */
    public function startOfYear()
    {
        if ($this->calendarMode === CalendarMode::GREGORIAN) {
            return parent::startOfYear();
        }

        $cy = $this->getCalendarDate()[0];
        $this->setCalendarDate($cy, 1, 1);

        return $this->startOfDay();
    }

    /**
     * Set to the end of the current year in the active calendar.
     */
    public function endOfYear()
    {
        if ($this->calendarMode === CalendarMode::GREGORIAN) {
            return parent::endOfYear();
        }

        $cy = $this->getCalendarDate()[0];
        $lastMonth = $this->calendarMode === CalendarMode::HIJRI ? 12 : 12;
        $lastDay = $this->getCalendarDaysInMonth($lastMonth, $cy);
        $this->setCalendarDate($cy, $lastMonth, $lastDay);

        return $this->endOfDay();
    }

    /**
     * Set to the start of the current week.
     */
    public function startOfWeek($weekStartsAt = null): static
    {
        if ($this->calendarMode === CalendarMode::GREGORIAN) {
            return parent::startOfWeek($weekStartsAt);
        }

        $weekStartsAt ??= self::SATURDAY;
        $dow = $this->dayOfWeek; // Returns remapped value: Sat=0..Fri=6

        $diff = ($dow - $weekStartsAt + 7) % 7;

        return $this->subDays($diff)->startOfDay();
    }

    /**
     * Set to the end of the current week.
     */
    public function endOfWeek($weekEndsAt = null): static
    {
        if ($this->calendarMode === CalendarMode::GREGORIAN) {
            return parent::endOfWeek($weekEndsAt);
        }

        $weekEndsAt ??= self::FRIDAY;
        $dow = $this->dayOfWeek;

        $diff = ($weekEndsAt - $dow + 7) % 7;

        return $this->addDays($diff)->endOfDay();
    }

    // ═══════════════════════════════════════════════════════════
    //  ADD / SUB — Calendar-Aware Month & Year Arithmetic
    // ═══════════════════════════════════════════════════════════

    /**
     * Add months in the active calendar.
     *
     * We cannot use Carbon's native addMonths because it operates on Gregorian
     * months (which have different lengths). We do calendar-native month math.
     */
    public function addMonths($value = 1)
    {
        if ($this->calendarMode === CalendarMode::GREGORIAN) {
            return parent::addMonths($value);
        }

        [$cy, $cm, $cd] = $this->getCalendarDate();
        $totalMonths = ($cy * 12) + ($cm - 1) + (int) $value;
        $newYear     = intdiv($totalMonths, 12);
        $newMonth    = ($totalMonths % 12) + 1;

        // Clamp the day if it exceeds the new month's max days
        $maxDay = $this->getCalendarDaysInMonth($newMonth, $newYear);
        $newDay = min($cd, $maxDay);

        return $this->setCalendarDate($newYear, $newMonth, $newDay);
    }

    /**
     * Subtract months.
     */
    public function subMonths($value = 1)
    {
        return $this->addMonths(-1 * (int) $value);
    }

    /**
     * Add a single month.
     */
    public function addMonth()
    {
        return $this->addMonths(1);
    }

    /**
     * Subtract a single month.
     */
    public function subMonth()
    {
        return $this->subMonths(1);
    }

    /**
     * Add years in the active calendar.
     */
    public function addYears($value = 1)
    {
        if ($this->calendarMode === CalendarMode::GREGORIAN) {
            return parent::addYears($value);
        }

        [$cy, $cm, $cd] = $this->getCalendarDate();
        $newYear = $cy + (int) $value;

        // Clamp the day (e.g. Esfand 30 in leap year → Esfand 29 in non-leap)
        $maxDay = $this->getCalendarDaysInMonth($cm, $newYear);
        $newDay = min($cd, $maxDay);

        return $this->setCalendarDate($newYear, $cm, $newDay);
    }

    /**
     * Subtract years.
     */
    public function subYears($value = 1)
    {
        return $this->addYears(-1 * (int) $value);
    }

    /**
     * Add a single year.
     */
    public function addYear()
    {
        return $this->addYears(1);
    }

    /**
     * Subtract a single year.
     */
    public function subYear()
    {
        return $this->subYears(1);
    }

    /**
     * Add months without overflow (day clamped to new month's max).
     */
    public function addMonthsNoOverflow($value = 1)
    {
        // Our addMonths already clamps the day, so identical behavior
        return $this->addMonths($value);
    }

    /**
     * Add weekdays (skip weekends based on active calendar).
     */
    public function addWeekdays($value = 1)
    {
        $i = 0;
        $step = $value > 0 ? 1 : -1;
        $count = abs((int) $value);

        while ($i < $count) {
            $this->addDays($step);
            if (!$this->isWeekend()) {
                $i++;
            }
        }

        return $this;
    }

    /**
     * Subtract weekdays.
     */
    public function subWeekdays($value = 1)
    {
        return $this->addWeekdays(-1 * (int) $value);
    }

    // ═══════════════════════════════════════════════════════════
    //  COMPARISON HELPERS
    // ═══════════════════════════════════════════════════════════

    /**
     * Check if this date is the same calendar-day as another.
     */
    public function isSameDay($date = null)
    {
        if ($this->calendarMode === CalendarMode::GREGORIAN) {
            return parent::isSameDay($date);
        }

        $other = $date instanceof self ? $date : new static($date);
        $other->calendarMode = $this->calendarMode;

        return $this->getCalendarDate() === $other->getCalendarDate();
    }

    /**
     * Check if this date is the same calendar-month as another.
     */
    public function isSameMonth($date = null, $ofSameYear = true): bool
    {
        if ($this->calendarMode === CalendarMode::GREGORIAN) {
            return parent::isSameMonth($date, $ofSameYear);
        }

        $other = $date instanceof self ? $date : new static($date);
        $other->calendarMode = $this->calendarMode;

        [$y1, $m1] = $this->getCalendarDate();
        [$y2, $m2] = $other->getCalendarDate();

        return $m1 === $m2 && (!$ofSameYear || $y1 === $y2);
    }

    /**
     * Check if this date is the same calendar-year as another.
     */
    public function isSameYear($date = null)
    {
        if ($this->calendarMode === CalendarMode::GREGORIAN) {
            return parent::isSameYear($date);
        }

        $other = $date instanceof self ? $date : new static($date);
        $other->calendarMode = $this->calendarMode;

        return $this->getCalendarDate()[0] === $other->getCalendarDate()[0];
    }

    /**
     * Check if today is a weekend day.
     */
    public function isWeekend(): bool
    {
        return in_array($this->dayOfWeek, static::$weekendDays, true);
    }

    /**
     * Check if the current calendar year is a leap year.
     */
    public function isLeapYear(): bool
    {
        if ($this->calendarMode === CalendarMode::GREGORIAN) {
            return parent::isLeapYear();
        }

        $cy = $this->getCalendarDate()[0];

        return $this->isCalendarLeapYear($cy);
    }

    // ═══════════════════════════════════════════════════════════
    //  DIFF FOR HUMANS — Localized
    // ═══════════════════════════════════════════════════════════

    /**
     * Human-readable diff in the active calendar's language.
     */
    public function diffForHumans($other = null, $syntax = null, $short = false, $parts = 1, $options = null): string
    {
        if ($this->calendarMode === CalendarMode::GREGORIAN) {
            return parent::diffForHumans($other, $syntax, $short, $parts, $options);
        }

        $now = $other ?? new static();
        if (!$now instanceof self) {
            $now = new static($now);
        }

        $diffSeconds = $now->getTimestamp() - $this->getTimestamp();
        $isPast      = $diffSeconds > 0;
        $diffSeconds = abs($diffSeconds);

        // Break down into largest meaningful unit
        [$unit, $value] = $this->resolveHumanDiffUnit($diffSeconds);

        return match ($this->calendarMode) {
            CalendarMode::JALALI => PersianLocale::diffForHumans($unit, $value, $isPast),
            CalendarMode::HIJRI  => ArabicLocale::diffForHumans($unit, $value, $isPast),
            default              => parent::diffForHumans($other, $syntax, $short, $parts, $options),
        };
    }

    /**
     * Resolve seconds into the most appropriate human-readable unit.
     *
     * @return array{0: string, 1: int} [unit, value]
     */
    protected function resolveHumanDiffUnit(int $seconds): array
    {
        if ($seconds < 60) {
            return ['second', $seconds];
        }
        if ($seconds < 3600) {
            return ['minute', intdiv($seconds, 60)];
        }
        if ($seconds < 86400) {
            return ['hour', intdiv($seconds, 3600)];
        }
        if ($seconds < 604800) {
            return ['day', intdiv($seconds, 86400)];
        }
        if ($seconds < 2592000) {
            return ['week', intdiv($seconds, 604800)];
        }
        if ($seconds < 31536000) {
            return ['month', intdiv($seconds, 2592000)];
        }

        return ['year', intdiv($seconds, 31536000)];
    }

    // ═══════════════════════════════════════════════════════════
    //  STRING OUTPUT
    // ═══════════════════════════════════════════════════════════

    public function __toString(): string
    {
        return $this->format(static::$toStringFormat);
    }

    public static function resetToStringFormat(): void
    {
        static::$toStringFormat = static::DEFAULT_TO_STRING_FORMAT;
    }

    public static function setToStringFormat($format): void
    {
        static::$toStringFormat = $format;
    }

    public function toDateString(): string
    {
        return $this->format('Y-m-d');
    }

    public function toDateTimeString(string $unitPrecision = 'second'): string
    {
        return $this->format('Y-m-d H:i:s');
    }

    public function toTimeString(string $unitPrecision = 'second'): string
    {
        return $this->format('H:i:s');
    }

    public function toFormattedDateString(): string
    {
        return $this->format('M j, Y');
    }

    public function toDayDateTimeString(): string
    {
        return $this->format('D, M j, Y g:i A');
    }

    // ═══════════════════════════════════════════════════════════
    //  COPY — Preserve Calendar Mode
    // ═══════════════════════════════════════════════════════════

    /**
     * Get a copy of the instance, preserving the calendar mode.
     */
    public function copy()
    {
        $copy = parent::copy();

        if ($copy instanceof static) {
            $copy->calendarMode = $this->calendarMode;
        }

        return $copy;
    }

    /**
     * Alias for copy().
     */
    public function clone()
    {
        return $this->copy();
    }

    // ═══════════════════════════════════════════════════════════
    //  CONVERSION & INTEROP
    // ═══════════════════════════════════════════════════════════

    /**
     * Create a MultiCarbon from a Carbon instance, preserving all date/time info.
     *
     * @param  \Carbon\Carbon  $carbon
     * @param  string          $calendar  Calendar mode to set (default: Jalali)
     */
    public static function fromCarbon(Carbon $carbon, string $calendar = CalendarMode::JALALI): static
    {
        $mc = new static($carbon->format('Y-m-d H:i:s'), $carbon->getTimezone());
        $mc->calendarMode = $calendar;

        return $mc;
    }

    /**
     * Create from a DateTime/DateTimeInterface instance.
     */
    public static function fromDateTime(\DateTimeInterface $dateTime, string $calendar = CalendarMode::JALALI): static
    {
        $mc = new static($dateTime->format('Y-m-d H:i:s'), $dateTime->getTimezone());
        $mc->calendarMode = $calendar;

        return $mc;
    }

    /**
     * Parse a date string with a given format in the active calendar.
     *
     * For example: MultiCarbon::parseFormat('Y/m/d', '1404/01/15')->jalali()
     * This treats the input as a Jalali date and converts internally.
     */
    public static function parseFormat(string $format, string $date, string $calendar = CalendarMode::JALALI, $tz = null): static
    {
        // For Gregorian, use Carbon's native parser
        if ($calendar === CalendarMode::GREGORIAN) {
            $result = parent::createFromFormat($format, $date, $tz);
            $mc = new static($result->format('Y-m-d H:i:s'), $result->getTimezone());
            $mc->calendarMode = CalendarMode::GREGORIAN;
            return $mc;
        }

        // Parse the format to extract date components
        $parsed = date_parse_from_format($format, $date);

        if ($parsed['error_count'] > 0) {
            throw new \InvalidArgumentException("Could not parse date '{$date}' with format '{$format}'");
        }

        $year  = $parsed['year']  !== false ? $parsed['year']  : 1;
        $month = $parsed['month'] !== false ? $parsed['month'] : 1;
        $day   = $parsed['day']   !== false ? $parsed['day']   : 1;
        $hour  = $parsed['hour']  !== false ? $parsed['hour']  : 0;
        $min   = $parsed['minute']!== false ? $parsed['minute']: 0;
        $sec   = $parsed['second']!== false ? $parsed['second']: 0;

        if ($calendar === CalendarMode::JALALI) {
            return static::createJalali($year, $month, $day, $hour, $min, $sec, $tz);
        }

        return static::createHijri($year, $month, $day, $hour, $min, $sec, $tz);
    }

    /**
     * Convert this instance to a plain Carbon (Gregorian).
     */
    public function toCarbon(): Carbon
    {
        return Carbon::parse(parent::format('Y-m-d H:i:s'), $this->getTimezone());
    }

    /**
     * Get the date/time components as an array in the active calendar.
     *
     * @return array{year: int, month: int, day: int, hour: int, minute: int, second: int}
     */
    public function toArray(): array
    {
        [$y, $m, $d] = $this->getCalendarDate();

        return [
            'year'   => $y,
            'month'  => $m,
            'day'    => $d,
            'hour'   => (int) parent::rawFormat('G'),
            'minute' => (int) parent::rawFormat('i'),
            'second' => (int) parent::rawFormat('s'),
        ];
    }

    /**
     * Get "time ago" string in the active calendar's language.
     */
    public function ago($syntax = null, $short = false, $parts = 1, $options = null)
    {
        if ($this->calendarMode === CalendarMode::GREGORIAN) {
            return parent::ago($syntax, $short, $parts, $options);
        }

        return $this->diffForHumans(null, $syntax, $short, $parts, $options);
    }

    // ═══════════════════════════════════════════════════════════
    //  ADDITIONAL COMPARISON METHODS
    // ═══════════════════════════════════════════════════════════

    /**
     * Is this date in the past?
     */
    public function isPast(): bool
    {
        return $this->getTimestamp() < time();
    }

    /**
     * Is this date in the future?
     */
    public function isFuture(): bool
    {
        return $this->getTimestamp() > time();
    }

    /**
     * Is this date today?
     */
    public function isToday(): bool
    {
        $today = new static();
        $today->calendarMode = $this->calendarMode;

        return $this->isSameDay($today);
    }

    /**
     * Is this date yesterday?
     */
    public function isYesterday(): bool
    {
        $yesterday = (new static())->subDay();
        $yesterday->calendarMode = $this->calendarMode;

        return $this->isSameDay($yesterday);
    }

    /**
     * Is this date tomorrow?
     */
    public function isTomorrow(): bool
    {
        $tomorrow = (new static())->addDay();
        $tomorrow->calendarMode = $this->calendarMode;

        return $this->isSameDay($tomorrow);
    }

    /**
     * Check if this date equals another.
     */
    public function equalsTo($other): bool
    {
        $other = $other instanceof self ? $other : new static($other);

        return $this->getTimestamp() === $other->getTimestamp();
    }

    /**
     * Check if this date is greater than another.
     */
    public function greaterThan($other): bool
    {
        $other = $other instanceof self ? $other : new static($other);

        return $this->getTimestamp() > $other->getTimestamp();
    }

    /**
     * Check if this date is less than another.
     */
    public function lessThan($other): bool
    {
        $other = $other instanceof self ? $other : new static($other);

        return $this->getTimestamp() < $other->getTimestamp();
    }

    /**
     * Check if this date is greater than or equal to another.
     */
    public function greaterThanOrEqualsTo($other): bool
    {
        $other = $other instanceof self ? $other : new static($other);

        return $this->getTimestamp() >= $other->getTimestamp();
    }

    /**
     * Check if this date is less than or equal to another.
     */
    public function lessThanOrEqualsTo($other): bool
    {
        $other = $other instanceof self ? $other : new static($other);

        return $this->getTimestamp() <= $other->getTimestamp();
    }

    /**
     * Check if this date is between two dates.
     */
    public function isBetween($date1, $date2, $equal = true): bool
    {
        $start = $date1 instanceof self ? $date1 : new static($date1);
        $end   = $date2 instanceof self ? $date2 : new static($date2);

        if ($equal) {
            return $this->getTimestamp() >= $start->getTimestamp()
                && $this->getTimestamp() <= $end->getTimestamp();
        }

        return $this->getTimestamp() > $start->getTimestamp()
            && $this->getTimestamp() < $end->getTimestamp();
    }

    // ═══════════════════════════════════════════════════════════
    //  DIFF METHODS
    // ═══════════════════════════════════════════════════════════

    /**
     * Difference in days between this and another date.
     */
    public function diffInDays($other = null, $absolute = true, $utc = false): float
    {
        $other = $other instanceof self ? $other : new static($other ?? 'now');
        $diff  = $this->getTimestamp() - $other->getTimestamp();

        $days = intdiv(abs($diff), 86400);

        return (float) ($absolute ? $days : ($diff >= 0 ? $days : -$days));
    }

    /**
     * Difference in months (approximate, based on 30-day months).
     */
    public function diffInMonths($other = null, $absolute = true, $utc = false): float
    {
        $other = $other instanceof self ? $other : new static($other ?? 'now');
        $diff  = $this->getTimestamp() - $other->getTimestamp();

        $months = intdiv(abs($diff), 2592000);

        return (float) ($absolute ? $months : ($diff >= 0 ? $months : -$months));
    }

    /**
     * Difference in years (approximate, based on 365-day years).
     */
    public function diffInYears($other = null, $absolute = true, $utc = false): float
    {
        $other = $other instanceof self ? $other : new static($other ?? 'now');
        $diff  = $this->getTimestamp() - $other->getTimestamp();

        $years = intdiv(abs($diff), 31536000);

        return (float) ($absolute ? $years : ($diff >= 0 ? $years : -$years));
    }

    // ═══════════════════════════════════════════════════════════
    //  ADDITIONAL GETTERS (morilog/jalali parity)
    // ═══════════════════════════════════════════════════════════

    /**
     * Get the Jalali/Hijri/Gregorian year.
     */
    public function getYear(): int
    {
        return $this->getCalendarDate()[0];
    }

    /**
     * Get the Jalali/Hijri/Gregorian month.
     */
    public function getMonth(): int
    {
        return $this->getCalendarDate()[1];
    }

    /**
     * Get the Jalali/Hijri/Gregorian day.
     */
    public function getDay(): int
    {
        return $this->getCalendarDate()[2];
    }

    /**
     * Get the hour.
     */
    public function getHour(): int
    {
        return (int) parent::rawFormat('G');
    }

    /**
     * Get the minute.
     */
    public function getMinute(): int
    {
        return (int) parent::rawFormat('i');
    }

    /**
     * Get the second.
     */
    public function getSecond(): int
    {
        return (int) parent::rawFormat('s');
    }

    /**
     * Get the days in the current calendar month.
     */
    public function getMonthDays(): int
    {
        [$y, $m] = $this->getCalendarDate();

        return $this->getCalendarDaysInMonth($m, $y);
    }

    /**
     * Get the localized month name.
     */
    public function getMonthName(): string
    {
        return $this->getCalendarMonthName($this->getCalendarDate()[1]);
    }

    /**
     * Get the day of week (Saturday=0 for Iranian calendar).
     */
    public function getDayOfWeek(): int
    {
        $phpDow = (int) parent::rawFormat('w');

        return ($phpDow + 1) % 7;
    }

    /**
     * Get the day of year in the active calendar.
     */
    public function getDayOfYear(): int
    {
        [$y, $m, $d] = $this->getCalendarDate();

        return $this->getCalendarDayOfYear($m, $d);
    }

    /**
     * Get the week of month.
     */
    public function getWeekOfMonth(): int
    {
        return (int) ceil($this->getCalendarDate()[2] / 7);
    }

    /**
     * Get the week of year.
     */
    public function getWeekOfYear(): int
    {
        [$cy, $cm, $cd] = $this->getCalendarDate();
        $firstDayDow = $this->copy()->setCalendarDate($cy, 1, 1)->getDayOfWeek();

        return (int) ceil(($this->getCalendarDayOfYear($cm, $cd) + $firstDayDow) / 7);
    }

    // ═══════════════════════════════════════════════════════════
    //  CALENDAR-SPECIFIC HELPERS (Internal Dispatch)
    // ═══════════════════════════════════════════════════════════

    protected function getCalendarDaysInMonth(int $month, int $year): int
    {
        return match ($this->calendarMode) {
            CalendarMode::JALALI => JalaliConverter::daysInMonth($month, $year),
            CalendarMode::HIJRI  => HijriConverter::daysInMonth($month, $year),
            default              => (int) parent::rawFormat('t'),
        };
    }

    protected function getCalendarDayOfYear(int $month, int $day): int
    {
        return match ($this->calendarMode) {
            CalendarMode::JALALI => JalaliConverter::dayOfYear($month, $day),
            CalendarMode::HIJRI  => HijriConverter::dayOfYear($month, $day),
            default              => 1 + (int) parent::rawFormat('z'),
        };
    }

    protected function getCalendarDaysInYear(int $year): int
    {
        return match ($this->calendarMode) {
            CalendarMode::JALALI => JalaliConverter::daysInYear($year),
            CalendarMode::HIJRI  => HijriConverter::daysInYear($year),
            default              => parent::isLeapYear() ? 366 : 365,
        };
    }

    protected function isCalendarLeapYear(int $year): bool
    {
        return match ($this->calendarMode) {
            CalendarMode::JALALI => JalaliConverter::isLeapYear($year),
            CalendarMode::HIJRI  => HijriConverter::isLeapYear($year),
            default              => parent::isLeapYear(),
        };
    }

    protected function getCalendarMonthName(int $month): string
    {
        return match ($this->calendarMode) {
            CalendarMode::JALALI => PersianLocale::monthName($month),
            CalendarMode::HIJRI  => ArabicLocale::monthName($month),
            default              => parent::rawFormat('F'),
        };
    }

    protected function getCalendarMonthNameShort(int $month): string
    {
        return match ($this->calendarMode) {
            CalendarMode::JALALI => PersianLocale::monthNameShort($month),
            CalendarMode::HIJRI  => ArabicLocale::monthNameShort($month),
            default              => parent::rawFormat('M'),
        };
    }

    protected function getCalendarWeekdayName(int $phpDayOfWeek): string
    {
        return match ($this->calendarMode) {
            CalendarMode::JALALI => PersianLocale::weekdayName($phpDayOfWeek),
            CalendarMode::HIJRI  => ArabicLocale::weekdayName($phpDayOfWeek),
            default              => parent::rawFormat('l'),
        };
    }

    protected function getCalendarWeekdayNameShort(int $phpDayOfWeek): string
    {
        return match ($this->calendarMode) {
            CalendarMode::JALALI => PersianLocale::weekdayNameShort($phpDayOfWeek),
            CalendarMode::HIJRI  => ArabicLocale::weekdayNameShort($phpDayOfWeek),
            default              => parent::rawFormat('D'),
        };
    }

    protected function getCalendarMeridiem(string $amPm, bool $short): string
    {
        return match ($this->calendarMode) {
            CalendarMode::JALALI => PersianLocale::meridiem($amPm, $short),
            CalendarMode::HIJRI  => ArabicLocale::meridiem($amPm, $short),
            default              => $short ? strtolower($amPm) : $amPm,
        };
    }

    protected function getCalendarOrdinalSuffix(): string
    {
        return match ($this->calendarMode) {
            CalendarMode::JALALI => PersianLocale::ordinalSuffix(),
            CalendarMode::HIJRI  => ArabicLocale::ordinalSuffix(),
            default              => parent::rawFormat('S'),
        };
    }

    // ─── Digit Conversion ───────────────────────────────────────

    protected function applyDigits(string $output): string
    {
        return match (self::$defaultDigitsType) {
            self::DIGITS_FARSI  => PersianLocale::toFarsiDigits($output),
            self::DIGITS_ARABIC => ArabicLocale::toArabicDigits($output),
            default             => $output,
        };
    }
}
