<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'app.key' => \App\Http\Middleware\VerifyAppKey::class,
            'management.token' => \App\Http\Middleware\VerifyManagementToken::class,
            'verify.recaptcha' => \App\Http\Middleware\VerifyRecaptcha::class,
        ]);
        $middleware->appendToGroup('api', 'app.key');
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $seconds = $e->getHeaders()['Retry-After'] ?? null;
                return response()->json([
                    'message' => $seconds 
                        ? "Zbyt wiele prób. Spróbuj ponownie za $seconds sek." 
                        : "Zbyt wiele prób. Spróbuj ponownie za chwilę.",
                ], 429);
            }
        });
    })->create();
