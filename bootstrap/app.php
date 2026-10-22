<?php

use App\Console\Commands\BuildKnowledge;
use App\Console\Commands\CreateAdmin;
use App\Console\Commands\SyncKnowledge;
use App\Console\Commands\TestGemini;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'admin' => EnsureAdmin::class,
        ]);
        // Authorization runs before CSRF so anonymous admin API requests
        // consistently receive JSON 401 responses.
        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            ValidateCsrfToken::class
        );
        $middleware->prependToPriorityList(
            ValidateCsrfToken::class,
            EnsureAdmin::class
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withProviders([
        App\Providers\AppServiceProvider::class,
        App\Providers\ChatbotServiceProvider::class,  // registrasi provider chatbot
    ])
    ->withCommands([
        BuildKnowledge::class,
        CreateAdmin::class,
        SyncKnowledge::class,
        TestGemini::class,
    ])
    ->create();
