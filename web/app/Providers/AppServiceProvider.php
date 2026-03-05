<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

use App\Datatables\DatatablesServiceProvider;
use App\Entities\Sanctum\PersonalAccessToken;
use Modules\Admin\Services\Contracts\RoleServiceInterface;
use Modules\Admin\Services\Contracts\UserServiceInterface;
use Modules\Admin\Services\RoleService;
use Modules\Admin\Services\UserService;

/**
 * Application service provider.
 * Registers application services and bootstraps application components.
 */
class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void {
        $this->registerServiceProviders();
        $this->registerServiceBindings();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void {
        $this->configureSanctum();
    }

    /**
     * Register service providers.
     *
     * @return void
     */
    protected function registerServiceProviders(): void {
        $this->app->register(DatatablesServiceProvider::class);
    }

    /**
     * Register service interface bindings.
     *
     * @return void
     */
    protected function registerServiceBindings(): void {
        $this->app->singleton(
            UserServiceInterface::class,
            UserService::class
        );

        $this->app->singleton(
            RoleServiceInterface::class,
            RoleService::class
        );
    }

    /**
     * Configure Sanctum authentication.
     *
     * @return void
     */
    protected function configureSanctum(): void {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
