<?php

namespace Modules\Admin\Providers;

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
        // Register a custom Vite macro for the Admin module
        Vite::macro('admin', function ($entryPoints) {
            return Vite::useHotFile('hot-admin')
                ->useBuildDirectory('build-admin')
                ->withEntryPoints($entryPoints)
                ->toHtml();
        });
    }
}
