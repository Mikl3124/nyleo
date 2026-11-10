<?php

use App\Http\Middleware\FrenchTypography;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [FrenchTypography::class]);
        // ?copie=1 est ajouté aux liens de la copie envoyée à Mickaël : il ne casse pas la signature.
        $middleware->validateSignatures(except: ['copie']);
        // Stripe appelle le webhook sans jeton CSRF : la signature Stripe-Signature est vérifiée à la place.
        $middleware->validateCsrfTokens(except: ['stripe/webhook']);
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('espace*')
            ? route('portal.login')
            : route('filament.admin.auth.login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->is('espace*')
            ? route('portal.home')
            : '/admin');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
