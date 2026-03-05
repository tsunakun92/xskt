<?php

namespace App\Datatables;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

use App\Datatables\Components\ArrayDatatables;
use App\Datatables\Components\ModelDatatables;
use App\Datatables\Components\SelectSearchLivewire;

/**
 * Datatables Service Provider
 *
 * Auto-registers all Livewire components and views for the datatables package.
 * This service provider makes the datatables package completely self-contained.
 */
class DatatablesServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__ . '/Config/datatables.php',
            'datatables'
        );

        // Register services
        $this->registerServices();
    }

    /**
     * Register datatable services
     */
    protected function registerServices(): void {
        $this->app->singleton(Services\ConfigurationService::class);
        $this->app->singleton(Services\ErrorHandlingService::class);
        $this->app->singleton(Services\CacheService::class);
        $this->app->singleton(Services\FilterSessionService::class);
        $this->app->singleton(Services\QueryBuilderService::class);
        $this->app->singleton(Services\PaginationService::class);
        $this->app->singleton(Services\OptionLoaderService::class);
        $this->app->singleton(Services\CollectionDataService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        // Register Livewire components
        $this->registerLivewireComponents();

        // Register views
        $this->registerViews();

        // Register translations
        $this->registerTranslations();

        // Publish configuration if needed
        $this->publishes([
            __DIR__ . '/Config/datatables.php' => config_path('datatables.php'),
        ], 'datatables-config');
    }

    /**
     * Register Livewire components for the datatables package
     */
    protected function registerLivewireComponents(): void {
        if (!class_exists('Livewire\Livewire')) {
            return;
        }

        // Register main datatables component
        Livewire::component('datatables', ModelDatatables::class);

        // Register select-search component - ensure it's available globally
        Livewire::component('select-search-livewire', SelectSearchLivewire::class);

        // Also register with alternative names for compatibility
        Livewire::component('datatables-select-search', SelectSearchLivewire::class);

        // Array/Collection-based datatables component
        Livewire::component('array-datatables', ArrayDatatables::class);
    }

    /**
     * Register views for the datatables package
     */
    protected function registerViews(): void {
        // Register view namespace for package views
        $this->loadViewsFrom(__DIR__ . '/Views', 'datatables');

        // Publish views if needed for customization
        $this->publishes([
            __DIR__ . '/Views' => resource_path('views/vendor/datatables'),
        ], 'datatables-views');
    }

    /**
     * Register translations for the datatables package
     */
    protected function registerTranslations(): void {
        // Load translation files from package lang directory
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'datatables');

        // Publish translations if needed for customization
        $this->publishes([
            __DIR__ . '/lang' => resource_path('lang/vendor/datatables'),
        ], 'datatables-lang');
    }
}
