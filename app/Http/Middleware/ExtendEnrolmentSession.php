<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keep the session alive while an authenticator app is being set up.
 *
 * Enrolling takes longer than any other step in the registration. The visitor
 * has to install an app, grant it permission to read the code, and then read
 * six digits off a screen and type them back. That is the one place in the
 * whole flow where a person legitimately puts the phone down for a while.
 *
 * The default session lives two hours, and a session that dies in the middle
 * of that leaves a form whose token no longer matches: the submission is
 * refused with a 419, which the browser shows as "Page Expired", and the code
 * that had just been typed is lost with it.
 *
 * So the enrolment addresses get their own, longer window. The value is applied
 * before the session is opened, because the expiry is read when the session
 * store is built — inside StartSession. A change made later in the request
 * would come too late to lengthen the life of the session the visitor is
 * already in, which is why this middleware is prepended to the web group
 * rather than appended to it.
 */
class ExtendEnrolmentSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isEnrolmentRequest($request)) {
            config(['session.lifetime' => (int) config('totp.session_lifetime', 600)]);
        }

        return $next($request);
    }

    /**
     * The enrolment addresses, with and without a language prefix.
     *
     * Matched on the path rather than on the route name, because this runs
     * before the route is resolved.
     */
    private function isEnrolmentRequest(Request $request): bool
    {
        return $request->is(
            'totp/setup',
            'totp/setup/*',
            '*/totp/setup',
            '*/totp/setup/*',
        );
    }
}
