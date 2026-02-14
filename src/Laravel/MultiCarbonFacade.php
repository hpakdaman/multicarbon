<?php

declare(strict_types=1);

namespace MultiCarbon\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \MultiCarbon\MultiCarbon jalali()
 * @method static \MultiCarbon\MultiCarbon hijri()
 * @method static \MultiCarbon\MultiCarbon gregorian()
 * @method static \MultiCarbon\MultiCarbon createJalali(int $year, int $month, int $day, int $hour = 0, int $minute = 0, int $second = 0, $tz = null)
 * @method static \MultiCarbon\MultiCarbon createHijri(int $year, int $month, int $day, int $hour = 0, int $minute = 0, int $second = 0, $tz = null)
 *
 * @see \MultiCarbon\MultiCarbon
 */
class MultiCarbonFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'multicarbon';
    }
}
