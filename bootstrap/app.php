<?php

use App\Http\Middleware\EnsureTotpIsConfirmed;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\LocalizeUrls;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // The staff area is a second route file rather than a `prefix` on the
        // web group. It is registered inside the same `web` middleware stack —
        // so it gets sessions, CSRF and bindings — but it is loaded separately
        // so that the `{locale?}` prefix applied to the public site cannot
        // reach it by accident.
        then: function (): void {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Proxy trust is configured in AppServiceProvider::register(), not here:
        // this closure runs before the configuration is loaded, so config()
        // is not yet bound and the request would fail to resolve.

        // Appending rather than prepending: the session must exist before
        // SetLocale can read a stored preference from it, and authentication has
        // to run before EnsureTotpIsConfirmed can see who is asking.
        $middleware->web(append: [
            SetLocale::class,
            LocalizeUrls::class,
        ]);

        // The CMI server-to-server callback is the one route that cannot carry
        // a CSRF token: it is a request from a third-party server, with no
        // session and no form behind it. It is authenticated by the gateway's
        // signature instead, which PaymentController verifies before acting on
        // anything. Excluding it narrowly — by path, not wholesale — keeps
        // CSRF protection on every other POST in the application.
        //
        // The browser return/abandon pages are exempted for the same reason:
        // CMI posts the result to okUrl/failUrl, and that body is verified by
        // the gateway signature before it is read. Neither path settles an
        // order, so a forged POST there cannot mark anything paid.
        $middleware->validateCsrfTokens(except: [
            'payment/cmi/callback',
            'paiement/retour/*',
            '*/paiement/retour/*',
            'paiement/annule/*',
            '*/paiement/annule/*',
        ]);
        $cmiPaths = [
            'payment/cmi/callback',
            'paiement/retour/*',
            '*/paiement/retour/*',
            'paiement/annule/*',
            '*/paiement/annule/*',
        ];

        $middleware->convertEmptyStringsToNull(except: [
            fn (Request $request) => $request->is($cmiPaths),
        ]);

        $middleware->trimStrings(except: [
            fn (Request $request) => $request->is($cmiPaths),
        ]);
        $middleware->alias([
            'totp.confirmed' => EnsureTotpIsConfirmed::class,
            'admin' => EnsureUserIsAdmin::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
