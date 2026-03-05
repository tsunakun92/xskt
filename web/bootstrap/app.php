<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware
        $middleware->web([
            App\Http\Middleware\CheckLocale::class,
        ]);
        // Global middleware aliases
        $middleware->alias([
            'permission'        => App\Http\Middleware\CheckRoutePermission::class,
            'module_permission' => App\Http\Middleware\CheckModulePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
