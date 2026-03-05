<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeServiceProvider extends ServiceProvider {
    /**
     * Register services.
     */
    public function register(): void {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {
        // Register Blade directive for permission check
        // Usage: @canAccess('route.name') ... @endcanAccess
        Blade::if('canAccess', function (string $permission): bool {
            return can_access($permission);
        });

        Blade::directive('viteAdmin', function ($expression) {
            return "<?php echo Vite::admin($expression); ?>";
        });
    }
}
