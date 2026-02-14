<?php

declare(strict_types=1);

namespace MultiCarbon\Laravel;

use MultiCarbon\MultiCarbon;
use MultiCarbon\CalendarMode;

/**
 * Helper methods for Laravel integration.
 * Used by Blade directives and global helper functions.
 */
class Helpers
{
    /**
     * Format a date/timestamp as Jalali.
     *
     * @param mixed  $date   A Carbon instance, DateTime, timestamp, or date string
     * @param string $format The format string (default: 'Y/m/d H:i:s')
     */
    public static function jalali($date = null, string $format = 'Y/m/d H:i:s'): string
    {
        $mc = self::resolve($date);
        $mc->jalali();

        return $mc->format($format);
    }

    /**
     * Format a date/timestamp as Hijri.
     *
     * @param mixed  $date   A Carbon instance, DateTime, timestamp, or date string
     * @param string $format The format string (default: 'Y/m/d H:i:s')
     */
    public static function hijri($date = null, string $format = 'Y/m/d H:i:s'): string
    {
        $mc = self::resolve($date);
        $mc->hijri();

        return $mc->format($format);
    }

    /**
     * Get current Jalali date formatted.
     */
    public static function jdate(string $format = 'Y/m/d H:i:s'): string
    {
        return self::jalali(null, $format);
    }

    /**
     * Get current Hijri date formatted.
     */
    public static function hdate(string $format = 'Y/m/d H:i:s'): string
    {
        return self::hijri(null, $format);
    }

    /**
     * Resolve any input into a MultiCarbon instance.
     */
    private static function resolve($date = null): MultiCarbon
    {
        if ($date === null) {
            return new MultiCarbon();
        }

        if ($date instanceof MultiCarbon) {
            return $date->copy();
        }

        if ($date instanceof \DateTimeInterface) {
            return new MultiCarbon($date->format('Y-m-d H:i:s'), $date->getTimezone());
        }

        if (is_numeric($date)) {
            $mc = new MultiCarbon();
            $mc->setTimestamp((int) $date);
            return $mc;
        }

        return new MultiCarbon($date);
    }
}
