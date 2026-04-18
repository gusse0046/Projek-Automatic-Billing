<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'sap.auth' => \App\Http\Middleware\SapAuthenticated::class,
            'setting.auth' => \App\Http\Middleware\SettingAuthenticated::class, // Tambahkan ini
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();