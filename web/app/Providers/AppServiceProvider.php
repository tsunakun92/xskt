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
use Modules\Crm\Services\Contracts\CrmBookingServiceInterface;
use Modules\Crm\Services\Contracts\CrmRoomTypeServiceInterface;
use Modules\Crm\Services\CrmBookingService;
use Modules\Crm\Services\CrmRoomTypeService;

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

        $this->app->singleton(
            CrmBookingServiceInterface::class,
            CrmBookingService::class
        );

        $this->app->singleton(
            CrmRoomTypeServiceInterface::class,
            CrmRoomTypeService::class
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
