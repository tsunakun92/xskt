<?php

namespace Modules\Api\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class ViteServiceProvider extends ServiceProvider {
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
        // Register a custom Vite macro for the Api module
        Vite::macro('api', function ($entryPoints) {
            return Vite::useHotFile('hot-api')
                ->useBuildDirectory('build-api')
                ->withEntryPoints($entryPoints)
                ->toHtml();
        });
    }
}
