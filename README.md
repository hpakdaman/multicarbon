# MultiCarbon

**A multi-calendar extension for [Carbon](https://carbon.nesbot.com/) — seamlessly work with Jalali (Solar Hijri), Hijri (Islamic Lunar), and Gregorian calendars using the same familiar Carbon API.**

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-8892BF.svg)](https://php.net)

---

## Why MultiCarbon?

Carbon is the gold standard for date/time in PHP. But if you work with Persian (Jalali) or Islamic (Hijri) calendars, you've had to choose between limited wrappers or entirely separate libraries that throw away everything Carbon gives you.

**MultiCarbon _is_ Carbon.** It extends `Carbon\Carbon` directly, which means every single Carbon feature — timezone handling, diffing, serialization, immutability, macros, Laravel integration — works exactly as you expect. You don't lose anything. You just gain Jalali and Hijri support on top.

Every method you already know (`format()`, `addMonths()`, `diffForHumans()`, `startOfMonth()`, `addDays()`, `isPast()`, ...) works natively in your calendar of choice. Switch between calendars on the same instance, and the underlying timestamp never changes.

```php
// Create a date in Jalali calendar — the most natural way
$date = MultiCarbon::createJalali(1404, 1, 1); // Nowruz 1404

echo $date->format('Y/m/d');                   // "1404/01/01"
echo $date->format('l j F Y');                  // "جمعه 1 فروردین 1404"

// Switch between calendars on the same instance
echo $date->hijri()->format('Y/m/d');          // "1446/08/21"
echo $date->gregorian()->format('Y/m/d');      // "2025/03/21"

// Or start from a Gregorian string — works seamlessly
echo (new MultiCarbon('2025-03-21'))->jalali()->format('Y/m/d'); // "1404/01/01"
```

### It's Just Carbon

Because MultiCarbon extends Carbon, every Carbon method works out of the box — even in Jalali or Hijri mode:

```php
$date = MultiCarbon::createJalali(1404, 6, 15, 14, 30, 0);

// All of Carbon's methods work — you lose nothing
$date->addDays(10);                    // add 10 days (Carbon native)
$date->addMonths(2);                   // add 2 Jalali months (calendar-aware)
$date->isPast();                       // true/false
$date->isWeekend();                    // Friday detection (Iranian week)
$date->diffForHumans();                // "۳ ماه پیش" (localized Persian)
$date->getTimestamp();                 // Unix timestamp — always correct
$date->timezone('Asia/Tehran');        // Carbon timezone support
$date->toIso8601String();             // ISO output via Carbon
$date->copy()->startOfMonth();        // 1404/08/01 00:00:00

// Switch to Gregorian anytime — full Carbon, no restrictions
$date->gregorian()->format('Y-m-d');  // "2025-11-05"

// Pass it anywhere that expects Carbon — it just works
function logDate(Carbon\Carbon $date) { /* ... */ }
logDate($date); // MultiCarbon IS Carbon
```

## Features

- **Full Carbon compatibility** — extends `Carbon\Carbon`, so every Carbon method works out of the box
- **Three calendar systems** — Jalali (Solar Hijri), Hijri Qamari (Islamic Lunar), and Gregorian
- **Fluent calendar switching** — `->jalali()`, `->hijri()`, `->gregorian()` on any instance
- **Calendar-aware arithmetic** — `addMonths()`, `addYears()`, `startOfMonth()`, `endOfYear()` respect calendar boundaries
- **Localized output** — Persian and Arabic month names, weekday names, AM/PM, and "diff for humans"
- **Digit display** — Latin, Farsi (`۱۲۳۴`), or Arabic-Indic (`١٢٣٤`) digits globally
- **Iranian week** — Saturday as first day, Friday as weekend
- **Laravel integration** — auto-discovery, Facade, Blade directives (`@jalali`, `@hijri`, `@jdate`, `@hdate`)
- **Global helpers** — `jdate()`, `hdate()`, `multicarbon()` available everywhere
- **Zero dependencies** beyond Carbon itself

## Installation

```bash
composer require hpakdaman/multicarbon
```

**Requirements:** PHP 8.1+ and Carbon 2.x or 3.x.

### Laravel

The package auto-discovers its ServiceProvider and Facade. No manual registration needed.

## Quick Start

### Create Dates

```php
use MultiCarbon\MultiCarbon;

// From now
$date = new MultiCarbon();

// From a Gregorian string (defaults to Jalali mode)
$date = new MultiCarbon('2025-03-21');
echo $date->year;   // 1404
echo $date->month;  // 1
echo $date->day;    // 1

// Create directly in Jalali
$date = MultiCarbon::createJalali(1404, 1, 1, 12, 0, 0);

// Create directly in Hijri
$date = MultiCarbon::createHijri(1446, 9, 1);
```

### Switch Calendars

```php
$date = new MultiCarbon('2025-03-21');

// Fluent switching — same underlying moment, different calendar view
echo $date->jalali()->format('Y/m/d');     // "1404/01/01"
echo $date->hijri()->format('Y/m/d');      // "1446/08/21"
echo $date->gregorian()->format('Y/m/d');  // "2025/03/21"

// The timestamp never changes
echo $date->jalali()->timestamp === $date->gregorian()->timestamp;  // true
```

### Format Dates

All of PHP's date format characters are supported:

```php
$date = MultiCarbon::createJalali(1404, 1, 15, 14, 30, 0);

$date->format('Y-m-d H:i:s');  // "1404-01-15 14:30:00"
$date->format('l j F Y');      // "شنبه 15 فروردین 1404"
$date->format('D, M j, Y');    // "ش, فرو 15, 1404"

// Hijri formatting
$hijri = MultiCarbon::createHijri(1446, 9, 1);
$hijri->format('F Y');          // "رمضان 1446"
```

### Date Arithmetic

Month and year arithmetic respects calendar boundaries:

```php
$date = MultiCarbon::createJalali(1404, 6, 31);  // Shahrivar 31

$date->addMonth();
echo $date->format('Y/m/d');  // "1404/07/30" — clamped to Mehr's max (30 days)

$date = MultiCarbon::createJalali(1403, 12, 30);  // Esfand 30 (leap year)
$date->addYear();
echo $date->format('Y/m/d');  // "1404/12/29" — clamped (1404 is not leap)
```

### Boundary Methods

```php
$date = MultiCarbon::createJalali(1404, 6, 15, 14, 30, 0);

$date->startOfMonth();  // 1404/06/01 00:00:00
$date->endOfMonth();    // 1404/06/31 23:59:59
$date->startOfYear();   // 1404/01/01 00:00:00
$date->endOfYear();     // 1404/12/29 23:59:59

$date->startOfWeek();   // Previous Saturday 00:00:00
$date->endOfWeek();     // Next Friday 23:59:59
```

### Comparison

```php
$a = MultiCarbon::createJalali(1404, 1, 1);
$b = MultiCarbon::createJalali(1404, 1, 25);

$a->isSameMonth($b);   // true
$a->isSameDay($b);     // false
$a->lessThan($b);      // true
$a->diffInDays($b);    // 24
```

### Diff for Humans

Localized in Persian and Arabic:

```php
// Persian
$date = MultiCarbon::createJalali(1403, 1, 1);
echo $date->diffForHumans();  // "1 سال پیش"

// Arabic
$date = MultiCarbon::createHijri(1445, 1, 1);
echo $date->diffForHumans();  // "منذ 1 سنة"
```

### Digit Display

```php
MultiCarbon::setDigitsType(MultiCarbon::DIGITS_FARSI);
echo MultiCarbon::createJalali(1404, 1, 1)->format('Y/m/d');
// "۱۴۰۴/۰۱/۰۱"

MultiCarbon::setDigitsType(MultiCarbon::DIGITS_ARABIC);
echo MultiCarbon::createHijri(1446, 9, 1)->format('Y/m/d');
// "١٤٤٦/٠٩/٠١"

MultiCarbon::setDigitsType(MultiCarbon::DIGITS_LATIN);  // default
```

### Conversion & Interop

```php
use Carbon\Carbon;
use MultiCarbon\MultiCarbon;

// From Carbon
$carbon = Carbon::parse('2025-03-21');
$mc = MultiCarbon::fromCarbon($carbon);  // Jalali mode by default

// From DateTime
$dt = new \DateTime('2025-03-21');
$mc = MultiCarbon::fromDateTime($dt);

// Back to Carbon
$carbon = $mc->toCarbon();

// Parse a calendar-specific string
$mc = MultiCarbon::parseFormat('Y/m/d', '1404/01/15');  // Jalali
$mc = MultiCarbon::parseFormat('Y/m/d', '1446/07/15', CalendarMode::HIJRI);

// To array
$mc->toArray();
// ['year' => 1404, 'month' => 1, 'day' => 15, 'hour' => 0, 'minute' => 0, 'second' => 0]
```

### Global Helpers

```php
// Jalali
jdate();                  // MultiCarbon instance in Jalali mode
jdate('Y/m/d');           // "1404/01/15"
jdate('Y/m/d', $timestamp);

// Hijri
hdate();                  // MultiCarbon instance in Hijri mode
hdate('Y/m/d');           // "1446/08/15"

// General
multicarbon();            // MultiCarbon instance
```

### Laravel Blade Directives

```blade
{{-- Current Jalali date --}}
@jdate('Y/m/d H:i:s')

{{-- Current Hijri date --}}
@hdate('Y/m/d')

{{-- Convert a variable --}}
@jalali($user->created_at, 'Y/m/d')
@hijri($post->published_at, 'Y/m/d')
```

## API Reference

### Calendar Mode

| Method | Description |
|---|---|
| `->jalali()` | Switch to Jalali (Solar Hijri) mode |
| `->hijri()` | Switch to Hijri (Islamic Lunar) mode |
| `->gregorian()` | Switch to Gregorian mode |
| `->isJalali()` | Check if in Jalali mode |
| `->isHijri()` | Check if in Hijri mode |
| `->isGregorian()` | Check if in Gregorian mode |
| `->getCalendar()` | Get current calendar mode string |

### Factory Methods

| Method | Description |
|---|---|
| `MultiCarbon::createJalali($y, $m, $d, $h, $min, $s, $tz)` | Create from Jalali components |
| `MultiCarbon::createHijri($y, $m, $d, $h, $min, $s, $tz)` | Create from Hijri components |
| `MultiCarbon::fromCarbon($carbon, $calendar)` | Create from a Carbon instance |
| `MultiCarbon::fromDateTime($dateTime, $calendar)` | Create from a DateTime instance |
| `MultiCarbon::parseFormat($format, $date, $calendar, $tz)` | Parse a calendar-specific date string |

### Getters

| Property / Method | Description |
|---|---|
| `->year`, `->getYear()` | Year in active calendar |
| `->month`, `->getMonth()` | Month in active calendar |
| `->day`, `->getDay()` | Day in active calendar |
| `->monthName`, `->getMonthName()` | Localized month name |
| `->dayName` | Localized weekday name |
| `->dayOfWeek`, `->getDayOfWeek()` | Day of week (Saturday=0) |
| `->dayOfYear`, `->getDayOfYear()` | Day of year (1-indexed) |
| `->daysInMonth`, `->getMonthDays()` | Days in current month |
| `->quarter` | Quarter (1-4) |
| `->isLeapYear()` | Is the current year a leap year? |

### Arithmetic

| Method | Description |
|---|---|
| `->addMonths($n)` / `->subMonths($n)` | Add/subtract months (calendar-aware) |
| `->addYears($n)` / `->subYears($n)` | Add/subtract years (calendar-aware) |
| `->addDays($n)` / `->subDays($n)` | Add/subtract days (inherited from Carbon) |
| `->addWeekdays($n)` / `->subWeekdays($n)` | Add/subtract weekdays (skips weekends) |

### Boundaries

| Method | Description |
|---|---|
| `->startOfMonth()` / `->endOfMonth()` | Calendar-aware month boundaries |
| `->startOfYear()` / `->endOfYear()` | Calendar-aware year boundaries |
| `->startOfWeek()` / `->endOfWeek()` | Week boundaries (Saturday-Friday) |

## How It Works

MultiCarbon extends Carbon and intercepts property access (`year`, `month`, `day`) and key methods (`format()`, `setDate()`, `addMonths()`, etc.). It uses `debug_backtrace()` to detect whether a method was called by user code or by Carbon's internal engine:

- **User code calls** `$date->year` → returns the value in the active calendar (Jalali/Hijri)
- **Carbon internally calls** `$this->year` → returns Gregorian so parent logic works correctly

This means all of Carbon's existing methods (diffing, timezone handling, serialization) continue to work without modification. The conversion only happens at the boundary between your code and Carbon.

## Testing

```bash
composer install
./vendor/bin/phpunit
```

## License

MIT License. See [LICENSE](LICENSE) for details.
