<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        App\Providers\FortifyServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'article.author' => \App\Http\Middleware\EnsureArticleAuthor::class,
            'block.suspicious' => \App\Http\Middleware\BlockSuspiciousIPs::class,
        ]);
        $middleware->validateCsrfTokens(
            // except: ['stripe/*']
        );
        $middleware->web(append: [
            //
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
