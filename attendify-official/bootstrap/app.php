<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Console\Scheduling\Schedule;
use App\Http\Middleware\RefreshFirebaseToken;
use App\Http\Middleware\EnforceFreshSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withCommands()
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        //api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'needs.firebase' => RefreshFirebaseToken::class,
            'fresh.session' => EnforceFreshSession::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('events:update-status')->everyMinute();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
