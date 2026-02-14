<?php

declare(strict_types=1);

namespace MultiCarbon\Laravel;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use MultiCarbon\MultiCarbon;
use MultiCarbon\CalendarMode;

class MultiCarbonServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('multicarbon', function () {
            return new MultiCarbon();
        });
    }

    public function boot(): void
    {
        $this->registerBladeDirectives();
    }

    protected function registerBladeDirectives(): void
    {
        // @jalali($date, 'Y/m/d')
        Blade::directive('jalali', function (string $expression) {
            return "<?php echo \\MultiCarbon\\Laravel\\Helpers::jalali({$expression}); ?>";
        });

        // @hijri($date, 'Y/m/d')
        Blade::directive('hijri', function (string $expression) {
            return "<?php echo \\MultiCarbon\\Laravel\\Helpers::hijri({$expression}); ?>";
        });

        // @jdate('Y/m/d') — current date in Jalali
        Blade::directive('jdate', function (string $expression) {
            $expression = $expression ?: "'Y/m/d H:i:s'";
            return "<?php echo \\MultiCarbon\\Laravel\\Helpers::jdate({$expression}); ?>";
        });

        // @hdate('Y/m/d') — current date in Hijri
        Blade::directive('hdate', function (string $expression) {
            $expression = $expression ?: "'Y/m/d H:i:s'";
            return "<?php echo \\MultiCarbon\\Laravel\\Helpers::hdate({$expression}); ?>";
        });
    }
}
