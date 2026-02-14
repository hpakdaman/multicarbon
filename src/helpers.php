<?php

declare(strict_types=1);

use MultiCarbon\MultiCarbon;

if (!function_exists('jdate')) {
    /**
     * Get or format a Jalali date.
     *
     * Usage:
     *   jdate()                          → current Jalali date/time as MultiCarbon
     *   jdate('Y/m/d')                   → current Jalali date formatted
     *   jdate('Y/m/d', $timestamp)       → format a timestamp as Jalali
     *   jdate('Y/m/d', $timestamp, $tz)  → with timezone
     *
     * @param  string|null  $format    Format string (null returns the MultiCarbon instance)
     * @param  mixed        $timestamp Unix timestamp, DateTime, Carbon, or date string
     * @param  mixed        $tz        Timezone
     * @return MultiCarbon|string
     */
    function jdate(?string $format = null, $timestamp = null, $tz = null)
    {
        $mc = resolveMultiCarbonDate($timestamp, $tz);
        $mc->jalali();

        if ($format === null) {
            return $mc;
        }

        return $mc->format($format);
    }
}

if (!function_exists('hdate')) {
    /**
     * Get or format a Hijri date.
     *
     * @param  string|null  $format
     * @param  mixed        $timestamp
     * @param  mixed        $tz
     * @return MultiCarbon|string
     */
    function hdate(?string $format = null, $timestamp = null, $tz = null)
    {
        $mc = resolveMultiCarbonDate($timestamp, $tz);
        $mc->hijri();

        if ($format === null) {
            return $mc;
        }

        return $mc->format($format);
    }
}

if (!function_exists('multicarbon')) {
    /**
     * Create a new MultiCarbon instance (Jalali mode by default).
     *
     * @param  mixed  $time
     * @param  mixed  $tz
     * @return MultiCarbon
     */
    function multicarbon($time = null, $tz = null): MultiCarbon
    {
        if ($time === null) {
            return new MultiCarbon('now', $tz);
        }

        return resolveMultiCarbonDate($time, $tz);
    }
}

if (!function_exists('resolveMultiCarbonDate')) {
    /**
     * Resolve various date inputs into a MultiCarbon instance.
     *
     * @param  mixed  $date
     * @param  mixed  $tz
     * @return MultiCarbon
     */
    function resolveMultiCarbonDate($date = null, $tz = null): MultiCarbon
    {
        if ($date === null) {
            return new MultiCarbon('now', $tz);
        }

        if ($date instanceof MultiCarbon) {
            return $date->copy();
        }

        if ($date instanceof \DateTimeInterface) {
            return new MultiCarbon($date->format('Y-m-d H:i:s'), $date->getTimezone());
        }

        if (is_numeric($date)) {
            $mc = new MultiCarbon('now', $tz);
            $mc->setTimestamp((int) $date);
            return $mc;
        }

        return new MultiCarbon($date, $tz);
    }
}
