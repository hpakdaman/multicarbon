# MultiCarbon - AI Context File

This file provides context for AI assistants working with the MultiCarbon codebase.

## What is MultiCarbon?

MultiCarbon is a PHP library that extends `nesbot/carbon` (the standard PHP date/time library) to add native support for **Jalali (Solar Hijri)**, **Hijri (Islamic Lunar)**, and **Gregorian** calendars. It is not a wrapper or adapter — it directly extends `Carbon\Carbon`, so all Carbon methods work seamlessly.

## Architecture

### Core Strategy

MultiCarbon intercepts property access and key methods using `debug_backtrace()` to detect whether a call originates from user code or Carbon's internal engine:

- **User code** calls `$date->year` → returns the value in the active calendar (Jalali/Hijri)
- **Carbon internally** calls `$this->year` → returns Gregorian so parent logic works correctly

This is implemented in `calledByParent()` in `src/MultiCarbon.php`.

### Directory Structure

```
src/
├── MultiCarbon.php              # Main class (extends Carbon\Carbon)
├── CalendarMode.php             # Calendar mode constants (jalali, hijri, gregorian)
├── helpers.php                  # Global helper functions (jdate, hdate, multicarbon)
├── Converters/
│   ├── JalaliConverter.php      # Pure Jalali ↔ Gregorian conversion (stateless)
│   └── HijriConverter.php       # Pure Hijri ↔ Gregorian conversion (stateless)
├── Localization/
│   ├── PersianLocale.php        # Persian month names, weekdays, AM/PM, digits, diff translations
│   └── ArabicLocale.php         # Arabic month names, weekdays, AM/PM, digits, diff translations
└── Laravel/
    ├── MultiCarbonServiceProvider.php  # Laravel auto-discovery, Blade directives
    ├── MultiCarbonFacade.php           # Laravel Facade
    └── Helpers.php                     # Blade directive implementations

tests/
├── MultiCarbonTest.php          # Main test suite (calendar switching, format, arithmetic, etc.)
├── CarbonCompatibilityTest.php  # Tests adapted from Carbon's own test suite
├── JalaliConverterTest.php      # Unit tests for Jalali conversion algorithm
└── HijriConverterTest.php       # Unit tests for Hijri conversion algorithm
```

### Namespace

- **PSR-4 root**: `MultiCarbon\` → `src/`
- **Test namespace**: `Tests\MultiCarbon\` → `tests/`

### Key Classes

| Class | Purpose |
|---|---|
| `MultiCarbon\MultiCarbon` | Main class. Extends `Carbon\Carbon`. All calendar-aware logic lives here. |
| `MultiCarbon\CalendarMode` | Constants: `JALALI`, `HIJRI`, `GREGORIAN` |
| `MultiCarbon\Converters\JalaliConverter` | Stateless Jalali ↔ Gregorian conversion. Algorithm by Pournader/Toosi. |
| `MultiCarbon\Converters\HijriConverter` | Stateless Hijri ↔ Gregorian conversion. Tabular Islamic calendar (30-year cycle). |
| `MultiCarbon\Localization\PersianLocale` | Persian month names, weekday names, Farsi digits, diff-for-humans translations |
| `MultiCarbon\Localization\ArabicLocale` | Arabic month names, weekday names, Arabic-Indic digits, diff-for-humans translations |

### Calendar Mode

Each `MultiCarbon` instance has a `$calendarMode` property (default: `jalali`). Methods like `->jalali()`, `->hijri()`, `->gregorian()` switch the mode fluently. The underlying timestamp (Gregorian internally in PHP's `DateTime`) never changes — only the presentation layer changes.

### Overridden Methods

These Carbon methods are overridden to be calendar-aware:
- `get()` / `set()` — property access (`$date->year`, `$date->month`, etc.)
- `format()` — date formatting with calendar-specific values and localized names
- `setDate()` / `setDateTime()` / `setUnit()` — date mutation
- `create()` / `createFromDate()` — factory methods
- `addMonths()` / `subMonths()` / `addYears()` / `subYears()` — calendar-aware arithmetic
- `startOfMonth()` / `endOfMonth()` / `startOfYear()` / `endOfYear()` — calendar boundaries
- `startOfWeek()` / `endOfWeek()` — Iranian week (Saturday-Friday)
- `isSameDay()` / `isSameMonth()` / `isSameYear()` / `isLeapYear()` / `isWeekend()` — comparisons
- `diffForHumans()` — localized in Persian/Arabic
- `diffInDays()` / `diffInMonths()` / `diffInYears()` — difference calculations
- `copy()` — preserves calendar mode

### Iranian Week Convention

- Week starts on **Saturday** (0)
- Week ends on **Friday** (6)
- Weekend days: **Friday** only
- Day-of-week mapping: `Saturday=0, Sunday=1, Monday=2, Tuesday=3, Wednesday=4, Thursday=5, Friday=6`

## Development

```bash
composer install
./vendor/bin/phpunit
```

**Test count**: 191 tests, 567 assertions

### Dependencies

- **Runtime**: `php ^8.1`, `nesbot/carbon ^2.0|^3.0`
- **Dev**: `phpunit/phpunit ^10.0|^11.0`
- **No framework dependency** — Laravel integration is optional (auto-discovered)
