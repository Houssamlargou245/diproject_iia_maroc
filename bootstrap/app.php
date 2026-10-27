<?php

use App\Http\Middleware\EnsureTotpIsConfirmed;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\ExtendEnrolmentSession;
use App\Http\Middleware\LocalizeUrls;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'payment/cmi/callback',
        ]);
    })
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

        // Prepended, and the opposite reason: the session's expiry is read when
        // the session store is built, which happens inside StartSession. A
        // change made after that point would be too late to lengthen the life
        // of the session the visitor is already in.
        $middleware->web(prepend: [
            ExtendEnrolmentSession::class,
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

    $middleware->web(append: [
        \App\Http\Middleware\PreventBackHistory::class,
    ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A CSRF token that no longer matches the session is Laravel's 419, and
        // the default rendering of it is a dead end: a bare "Page Expired" page
        // with no way back to the form the visitor was filling in.
        //
        // The usual cause is a session that expired or was rotated while the
        // page sat open. On a two-hour session that is a delegate who put the
        // phone down to install an authenticator app, came back, typed the code
        // and was told the page had expired — with nothing to click and the
        // code they had just read thrown away. Sending them back to the form,
        // which now carries a token matching the session they are in, turns the
        // dead end into a retry.
        //
        // The check itself is untouched: a request without a valid token still
        // fails, it just fails with a way forward instead of a wall.
        //
        // The exception arrives already prepared — Laravel maps a token
        // mismatch to a 419 HttpException before the render callbacks run — so
        // this listens for the status code rather than for the exception class.
        //
        // The message is flashed, not written to the session here: the refusal
        // is raised inside the middleware stack, but the routing pipeline
        // catches it and lets the stack finish, so the session is written once
        // the request is done — after this callback has flashed the message.
        // Saving it here as well would age the flash straight away and the
        // notice would never reach the page.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return null;
            }

            return redirect()
                ->back()
                ->with('session_expired', true);
        });
    })->create();
