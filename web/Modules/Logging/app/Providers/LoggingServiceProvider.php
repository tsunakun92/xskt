<?php

namespace Modules\Logging\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use Modules\Logging\Console\Commands\LoggingConfigCommand;
use Modules\Logging\Utils\LogHandler;

class LoggingServiceProvider extends ServiceProvider {
    use PathNamespace;

    protected string $name = 'Logging';

    protected string $nameLower = 'logging';

    /**
     * Boot the application events.
     */
    public function boot(): void {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        $this->registerHttpLoggerMiddleware();

        // Merge logging channels into main logging config after boot
        $loggingChannelsPath = module_path($this->name, 'config/logging-channels.php');
        if (file_exists($loggingChannelsPath)) {
            $loggingChannels = require $loggingChannelsPath;
            $this->app->booted(function () use ($loggingChannels) {
                $loggingConfig = config('logging', []);
                if (!isset($loggingConfig['channels'])) {
                    $loggingConfig['channels'] = [];
                }
                $loggingConfig['channels'] = array_merge($loggingConfig['channels'], $loggingChannels);
                config(['logging' => $loggingConfig]);
            });
        }
    }

    /**
     * Register the service provider.
     */
    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        // Register LogHandler as singleton
        $this->app->singleton(LogHandler::class, function ($app) {
            return new LogHandler;
        });

        // Merge log-viewer config
        $logViewerPath = module_path($this->name, 'config/log-viewer.php');
        if (file_exists($logViewerPath)) {
            $moduleConfig = require $logViewerPath;
            foreach ($moduleConfig as $key => $value) {
                config(["log-viewer.{$key}" => $value]);
            }
        }

        // Merge http-logger config and register bindings
        $httpLoggerPath = module_path($this->name, 'config/http-logger.php');
        if (file_exists($httpLoggerPath)) {
            $moduleConfig = require $httpLoggerPath;
            foreach ($moduleConfig as $key => $value) {
                config(["http-logger.{$key}" => $value]);
            }
            config(['http-logger.enabled' => true]);
        }

        // Register LogProfile and LogWriter before Spatie service provider
        $this->app->singleton(
            \Spatie\HttpLogger\LogProfile::class,
            function ($app) {
                $logProfileClass = config('http-logger.log_profile', \Spatie\HttpLogger\LogNonGetRequests::class);

                return new $logProfileClass;
            }
        );

        $this->app->singleton(
            \Spatie\HttpLogger\LogWriter::class,
            function ($app) {
                $logWriterClass = config('http-logger.log_writer', \Spatie\HttpLogger\DefaultLogWriter::class);

                return new $logWriterClass;
            }
        );
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void {
        $this->commands([
            LoggingConfigCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void {
        $langPath = resource_path('lang/modules/' . $this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void {
        $relativeConfigPath = config('modules.paths.generator.config.path');
        $configPath         = module_path($this->name, $relativeConfigPath);

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $relativePath = str_replace($configPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $configKey    = $this->nameLower . '.' . str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $relativePath);
                    $key          = ($relativePath === 'config.php') ? $this->nameLower : $configKey;

                    $this->publishes([$file->getPathname() => config_path($relativePath)], 'config');
                    $this->mergeConfigFrom($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Register views.
     */
    public function registerViews(): void {
        $viewPath   = resource_path('views/modules/' . $this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        $componentNamespace = $this->module_namespace($this->name, $this->app_path(config('modules.paths.generator.component-class.path')));
        Blade::componentNamespace($componentNamespace, $this->nameLower);

        // Override log-viewer views from module
        $logViewerViewsPath = module_path($this->name, 'resources/views/vendor/log-viewer');
        if (is_dir($logViewerViewsPath)) {
            $this->app->booted(function () use ($logViewerViewsPath) {
                $this->loadViewsFrom($logViewerViewsPath, 'log-viewer');
            });
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array {
        return [];
    }

    /**
     * Register HTTP logging middleware automatically
     */
    protected function registerHttpLoggerMiddleware(): void {
        $this->app->booted(function () {
            $middlewareClass = \Modules\Logging\Http\Middleware\HttpLoggerMiddleware::class;
            $router          = $this->app->make('router');
            $webMiddlewares  = $router->getMiddlewareGroups()['web'] ?? [];

            if (!in_array($middlewareClass, $webMiddlewares)) {
                $router->pushMiddlewareToGroup('web', $middlewareClass);
            }
        });
    }

    private function getPublishableViewPaths(): array {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->nameLower)) {
                $paths[] = $path . '/modules/' . $this->nameLower;
            }
        }

        return $paths;
    }
}
