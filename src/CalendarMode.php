<?php

declare(strict_types=1);

namespace MultiCarbon;

/**
 * Enum-like class representing supported calendar systems.
 */
final class CalendarMode
{
    public const JALALI    = 'jalali';
    public const HIJRI     = 'hijri';
    public const GREGORIAN = 'gregorian';

    /** @var string[] All valid modes */
    public const ALL = [self::JALALI, self::HIJRI, self::GREGORIAN];

    public static function isValid(string $mode): bool
    {
        return in_array($mode, self::ALL, true);
    }
}
