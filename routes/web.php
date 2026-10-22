<?php

declare(strict_types=1);

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PresentationController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SpeakersController;
use App\Http\Controllers\SponsorsController;
use App\Http\Controllers\SponsorshipRequestController;
use App\Http\Controllers\TotpController;
use App\Http\Controllers\VenueController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Localised public routes
|--------------------------------------------------------------------------
|
| Every page is served at two addresses:
|
|   /programme          the default language (Arabic), unprefixed
|   /fr/programme       French
|   /en/programme       English
|
| Arabic is the default, so /ar/programme is deliberately NOT an address: the
| prefix belongs to the languages that are not the default, and honouring /ar/
| as well would leave one page with two canonical addresses for a search engine
| to choose between.
|
| Each page is therefore registered twice, and the two registrations are not
| interchangeable. That is not a stylistic choice, it is a framework constraint:
| a route URI with an *optional leading* segment, `{locale?}/programme`, compiles
| to a pattern where the separator after the segment is mandatory. It matches
| /en/programme and /ar/programme, and it does not match /programme at all â€”
| the empty-prefix case produces a double slash that the pattern rejects. The
| probe in the test suite asserts this, because the failure mode is a 404 on
| every unprefixed link with no error anywhere.
|
| So the pair is:
|
|   '/programme'          name 'programme.ar'  â€” matching only, no prefix
|   '/{locale?}/programme' name 'programme'     â€” matching, and the name every
|                                                   `route()` call in the app uses
|
| The canonical name belongs to the prefixed route because that is the one that
| has a `{locale}` parameter to fill. `LocalizeUrls` sets `URL::defaults(['locale'
| => $code])`, and a null default omits the segment, so the same call yields
| /programme in Arabic and /en/programme in English with no branching at the call
| site. Attaching the canonical name to the unprefixed route instead would make
| the default inert and every link Arabic-only.
|
| `Route::pattern` is the security-relevant part. The constraint is what stops a
| request such as /xx/anything from being swallowed by the prefix and reaching a
| route it was never meant to reach. Arabic is deliberately absent from the
| list, which also makes /ar/anything a clean 404 rather than a redirect loop
| back to the default.
|
*/

Route::pattern('locale', 'en|fr');

/**
 * Register a page at its unprefixed address and at its prefixed addresses.
 *
 * Wrapping the pair in one call rather than repeating the pattern inline is what
 * keeps the two registrations from drifting apart: the failure mode of getting it
 * wrong is a page that works in one language and 404s in the other, and it is a
 * failure no type checker or linter reports.
 *
 * @param  class-string|array{0: class-string, 1: string}|callable  $action
 * @param  array<string>  $methods
 */
$page = function (string $name, string $path, string|array|callable $action, array $methods = ['GET']): void {
    // Route::match() has no invokable shorthand the way Route::get() does, so an
    // invokable controller is expanded to its [class, '__invoke'] pair here.
    $action = is_string($action) ? [$action, '__invoke'] : $action;

    // The unprefixed address. Named `<name>.ar` so `route:list` still shows
    // something meaningful; nothing links to it by name, because generating the
    // default-locale URL is what the canonical name below is for.
    Route::match($methods, '/'.ltrim($path, '/'), $action)
        ->name($name.'.ar');

    // The prefixed address, which also matches the bare path when the prefix is
    // absent only in generated URLs, not in incoming requests.
    Route::match($methods, '/{locale?}/'.ltrim($path, '/'), $action)
        ->name($name);
};

// --- Public pages ----------------------------------------------------------

$page('home', '', HomeController::class);
$page('presentation', 'presentation', PresentationController::class);
$page('programme', 'programme', ProgrammeController::class);
$page('speakers', 'speakers', SpeakersController::class);

// Both spellings exist because the 2024 site linked /tarifs and inbound links
// point at /pricing. They are separate names pointing at one action, so the
// canonical URL can be declared in a <link rel="canonical"> without collapsing the
// address people already have in their bookmarks or in printed material.
$page('pricing', 'tarifs', PricingController::class);
Route::get('/pricing', PricingController::class)->name('pricing-alt.ar');
Route::get('/{locale?}/pricing', PricingController::class)->name('pricing-alt');

$page('venue', 'lieu', VenueController::class);
Route::get('/venue', VenueController::class)->name('venue-alt.ar');
Route::get('/{locale?}/venue', VenueController::class)->name('venue-alt');

// The canonical address is /sponsoring, which matches what the page is now
// about and what the navigation calls it. /partenaires and /sponsors both stay
// registered rather than redirected: they are printed in the 2024 sponsorship
// deck and quoted in inbound links, and a page that 301s a visitor who followed
// an old link is a worse outcome than a second name for the same page.
//
// Only the canonical name carries the `sponsors` prefix the templates link to;
// the two legacy spellings are named for what they are, so `route:list` reads
// honestly and nothing links to them by name.
$page('sponsors', 'sponsoring', SponsorsController::class);
Route::get('/partenaires', SponsorsController::class)->name('sponsors-legacy.ar');
Route::get('/{locale?}/partenaires', SponsorsController::class)->name('sponsors-legacy');
Route::get('/sponsors', SponsorsController::class)->name('sponsors-alt.ar');
Route::get('/{locale?}/sponsors', SponsorsController::class)->name('sponsors-alt');

$page('contact', 'contact', [ContactController::class, 'create']);
Route::match(['POST'], '/contact', [ContactController::class, 'store'])->name('contact.store.ar');
Route::post('/{locale?}/contact', [ContactController::class, 'store'])->name('contact.store');

// Constrained separately rather than through the helper, because the pattern has
// to apply to both registrations and the helper does not chain. A year is four
// digits and nothing else, so /archive/2024 is the archive and /archive/latest is
// a 404 rather than a query for a null edition.
Route::get('/archive/{year?}', [ArchiveController::class, '__invoke'])
    ->where('year', '\d{4}')
    ->name('archive.ar');
Route::get('/{locale?}/archive/{year?}', [ArchiveController::class, '__invoke'])
    ->where('year', '\d{4}')
    ->name('archive');

// --- The /ar prefix --------------------------------------------------------
//
// Arabic is the default language of this site, so its canonical address is the
// UNPREFIXED one: /presentation, never /ar/presentation. That is why
// `Route::pattern('locale', 'en|fr')` above deliberately excludes `ar` — serving
// both would leave every Arabic page with two addresses and hand a search
// engine a duplicate to arbitrate.
//
// Correct for the index, wrong for a person. Somebody who types /ar, or follows
// a link written that way, currently gets a 404 for a page that plainly exists.
// The fix is deliberately NOT a second registration of every page — that is the
// duplicate the canonical rule exists to prevent — but a single permanent
// redirect that strips the prefix and hands the visitor to the address that is
// already canonical. One rule covers every page, including pages added later,
// and a crawler following it ends up on the address the rest of the file wants
// indexed rather than on a second copy it would have to discount.

Route::get('/ar/{path?}', function (Request $request, string $path = ''): RedirectResponse {
    // `/ar` and `/ar/` both mean the Arabic home page, so an empty capture
    // becomes the site root rather than an empty redirect.
    $target = '/'.ltrim($path, '/');

    // `.*` matches anything at all, including a traversal sequence, an absolute
    // URL or a scheme — and `redirect()` passes its target straight to the
    // `Location` header. So the capture is reduced to a plain in-app path before
    // it is used, and anything that could leave the application is a 404 rather
    // than a redirect to somewhere else on the internet.
    if ($target !== '/' && preg_match('#^/(?:[a-z0-9\-]+/?)+$#i', $target) !== 1) {
        abort(404);
    }

    // The query string is a visitor's state (?day=2, a filter), not part of the
    // address, and dropping it would silently change which page they land on.
    $query = $request->getQueryString();

    return redirect($target.($query === null || $query === '' ? '' : '?'.$query), 301);
})
    ->where('path', '.*')
    ->name('locale.ar');

// --- Account creation and sign-in -----------------------------------------
//
// Guest-only, and inside the locale prefix so a code sent in Arabic is read on
// an Arabic page rather than on a French one.

Route::middleware('guest')->group(function () use ($page): void {
    $page('register', 'register', [RegisterController::class, 'create']);
    Route::match(['POST'], '/register', [RegisterController::class, 'store'])->name('register.store.ar');
    Route::post('/{locale?}/register', [RegisterController::class, 'store'])->name('register.store');

    $page('login', 'login', [SessionController::class, 'create']);
    Route::match(['POST'], '/login', [SessionController::class, 'store'])->name('login.store.ar');
    Route::post('/{locale?}/login', [SessionController::class, 'store'])->name('login.store');
});

Route::match(['POST'], '/logout', SessionController::class)
    ->middleware('auth')
    ->name('logout.ar');
Route::post('/{locale?}/logout', SessionController::class)
    ->middleware('auth')
    ->name('logout');

// --- The signed-in delegate area ------------------------------------------

Route::middleware('auth')->group(function () use ($page): void {
    $page('account', 'compte', [AccountController::class, 'show']);
    Route::get('/account', [AccountController::class, 'show'])->name('account-alt.ar');
    Route::get('/{locale?}/account', [AccountController::class, 'show'])->name('account-alt');

    // Profile editing. Two separate pages rather than one: the identity fields
    // and the password are two different acts with two different risks, and a
    // single combined form would put "save my details" next to "change my
    // password" for someone who only wanted the first.
    $page('account.edit', 'compte/profil', [AccountController::class, 'edit']);

    // POST for both writes, and CSRF-protected as every form on the site is.
    // The action name carries no identifier: the account is the session's, so a
    // request body has nothing to point a write at.
    Route::post('/compte/profil', [AccountController::class, 'update'])->name('account.update.ar');
    Route::post('/{locale?}/compte/profil', [AccountController::class, 'update'])->name('account.update');

    Route::post('/compte/mot-de-passe', [AccountController::class, 'updatePassword'])->name('account.password.ar');
    Route::post('/{locale?}/compte/mot-de-passe', [AccountController::class, 'updatePassword'])->name('account.password');

    // Accepting the terms. Its own action rather than a checkbox inside the
    // profile form, because it is the one field on the account that is a consent
    // record rather than a detail: it has to be timestamped on its own, it must
    // not be settable by an incidental save of the identity fields, and the
    // dashboard is where the delegate is told what is still missing.
    Route::post('/conditions', [AccountController::class, 'acceptTerms'])->name('account.terms.ar');
    Route::post('/{locale?}/conditions', [AccountController::class, 'acceptTerms'])->name('account.terms');
});

// --- Authenticator app (TOTP) ----------------------------------------------
//
// Reachable while authenticated but not yet enrolled — that is the whole point,
// since a half-finished registration has to be resumable. Nothing here reads an
// identifier from the request: the account is whatever the session says it is,
// so a challenge can never be aimed at a third party's device.
//
// Ordering is gated separately by the `totp.confirmed` middleware, applied per
// route where it is needed. Applying it to this group would make /totp/setup
// unreachable, because that page *is* the un-enrolled state.
//
// The old /verify-phone/* addresses are gone rather than aliased. They named a
// mechanism — proving a phone number — that no longer exists anywhere in the
// application, so keeping them would promise a page that proves nothing.

Route::middleware('auth')->group(function () use ($page): void {
    $page('totp.setup', 'totp/setup', [TotpController::class, 'show']);
    $page('totp.recovery-codes', 'totp/setup/recovery-codes', [TotpController::class, 'recoveryCodes']);
    $page('totp.done', 'totp/setup/done', [TotpController::class, 'done']);

    Route::match(['POST'], '/totp/setup', [TotpController::class, 'confirm'])
        // Throttled because this is the one endpoint where guessing pays: six
        // digits is a small space, and there is no resend to wait out.
        ->middleware('throttle:10,1')
        ->name('totp.confirm.ar');
    Route::post('/{locale?}/totp/setup', [TotpController::class, 'confirm'])
        ->middleware('throttle:10,1')
        ->name('totp.confirm');
});

// --- The basket -------------------------------------------------------------
//
// Reachable while signed out: a visitor fills a basket and is asked to sign in
// at the checkout, and the basket must survive that. The 2024 build only showed
// the basket to a signed-in user, so the pricing page's "add to cart" buttons
// silently did nothing for anyone not already logged in.

$page('cart', 'panier', [CartController::class, 'show']);
Route::post('/panier', [CartController::class, 'store'])->name('cart.store.ar');
Route::post('/{locale?}/panier', [CartController::class, 'store'])->name('cart.store');

Route::post('/panier/ligne/{cart}/{ticketType}', [CartController::class, 'update'])->name('cart.update.ar');
Route::post('/{locale?}/panier/ligne/{cart}/{ticketType}', [CartController::class, 'update'])->name('cart.update');

Route::post('/panier/ligne/{cart}/{ticketType}/supprimer', [CartController::class, 'destroy'])->name('cart.destroy.ar');
Route::post('/{locale?}/panier/ligne/{cart}/{ticketType}/supprimer', [CartController::class, 'destroy'])->name('cart.destroy');

Route::post('/panier/vider', [CartController::class, 'clear'])->name('cart.clear.ar');
Route::post('/{locale?}/panier/vider', [CartController::class, 'clear'])->name('cart.clear');

// The basket summary, as a PDF to forward and as a page for the print dialog.
//
// GET, not POST: neither action changes anything. Both read the basket that
// belongs to this session, and both are behind no middleware because the basket
// itself is reachable while signed out — the checkout is what asks for an
// account. A delegate who needs a document before they sign in therefore still
// gets one.
$page('cart.proforma', 'panier/recapitulatif', [CartController::class, 'proforma']);
$page('cart.printable', 'panier/imprimer', [CartController::class, 'printable']);

// --- The checkout -----------------------------------------------------------

$page('checkout', 'inscription', [CheckoutController::class, 'create']);
Route::post('/inscription', [CheckoutController::class, 'store'])->name('checkout.store.ar');
Route::post('/{locale?}/inscription', [CheckoutController::class, 'store'])->name('checkout.store');

Route::get('/inscription/payer/{order}', [CheckoutController::class, 'pay'])->name('checkout.pay.ar');
Route::get('/{locale?}/inscription/payer/{order}', [CheckoutController::class, 'pay'])->name('checkout.pay');

// Admin-only rehearsal: raise a real 1 MAD gateway order. POST, because it
// writes an order and clears nothing else; the tester account is enforced in
// the controller, so this is safe to leave on the public router.
Route::post('/inscription/test', [CheckoutController::class, 'testPayment'])->name('checkout.test.ar');
Route::post('/{locale?}/inscription/test', [CheckoutController::class, 'testPayment'])->name('checkout.test');

// The same rehearsal in EUR (0,09 €, about 1 MAD), to exercise the
// international-card path and the gateway's non-MAD currency display.
Route::post('/inscription/test-eur', [CheckoutController::class, 'testPaymentEur'])->name('checkout.test.eur.ar');
Route::post('/{locale?}/inscription/test-eur', [CheckoutController::class, 'testPaymentEur'])->name('checkout.test.eur');

// The same rehearsal at 0,50 MAD, for a sub-unit amount.
Route::post('/inscription/test-half', [CheckoutController::class, 'testPaymentHalf'])->name('checkout.test.half.ar');
Route::post('/{locale?}/inscription/test-half', [CheckoutController::class, 'testPaymentHalf'])->name('checkout.test.half');

// Add or remove a place on a basket line, from the checkout page.
//
// Its own route rather than a field on `checkout.store`, because the two
// disagree about validation: storing the order insists on one participant per
// place, while this adjusts how many places there are in the first place. The
// buttons reach it through `formaction`, so the form still posts the names the
// buyer has already typed and they survive the round trip.
Route::post('/inscription/places', [CheckoutController::class, 'seats'])->name('checkout.seats.ar');
Route::post('/{locale?}/inscription/places', [CheckoutController::class, 'seats'])->name('checkout.seats');

// --- Orders -----------------------------------------------------------------
//
// Behind auth, and additionally scoped to the signed-in buyer inside each
// controller. The 2024 `mescommandes.php` listed orders by session but took the
// id for the invoice and the cancellation from `$_POST['id']`, so any signed-in
// visitor could open and void somebody else's order.

Route::middleware('auth')->group(function () use ($page): void {
    $page('orders.index', 'mes-commandes', [OrderController::class, 'index']);
    Route::get('/commandes', [OrderController::class, 'index'])->name('orders.alt.ar');
    Route::get('/{locale?}/commandes', [OrderController::class, 'index'])->name('orders.alt');

    // The `{order}` segment is part of the path, not a query parameter.
    //
    // It was missing here, so `route('orders.show', ['order' => $id])` generated
    // `/commande?order=21`: the id had nowhere to go in the URI, so the URL
    // generator appended it as a query string. The route then matched `/commande`,
    // route-model binding had no `order` to resolve, and the ownership check
    // compared a null order against the signed-in user — a 403 for the legitimate
    // owner on every single invoice. The delegate could never open their own.
    $page('orders.show', 'commande/{order}', [OrderController::class, 'show']);

    // The English spelling. Kept as a separate registration rather than a
    // redirect, for the same reason /sponsoring keeps /partenaires: a URL
    // printed in 2024 material should keep resolving to the page.
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.alt-show.ar');
    Route::get('/{locale?}/orders/{order}', [OrderController::class, 'show'])->name('orders.alt-show');

    Route::post('/commande/{order}/facture', [OrderController::class, 'invoice'])->name('orders.invoice.ar');
    Route::post('/{locale?}/commande/{order}/facture', [OrderController::class, 'invoice'])->name('orders.invoice');

    Route::post('/commande/{order}/annuler', [OrderController::class, 'cancel'])->name('orders.cancel.ar');
    Route::post('/{locale?}/commande/{order}/annuler', [OrderController::class, 'cancel'])->name('orders.cancel');

    // CMI posts the result to okUrl/failUrl as well as to the server-to-server
    // callback, so both verbs are accepted here. The payload is verified by
    // signature before it is read, and this path never settles an order — the
    // callback does — so accepting a POST costs nothing.
    Route::match(['get', 'post'], '/paiement/retour/{order}', [PaymentController::class, 'return'])->name('payment.return.ar');
    Route::match(['get', 'post'], '/{locale?}/paiement/retour/{order}', [PaymentController::class, 'return'])->name('payment.return');

    Route::match(['get', 'post'], '/paiement/annule/{order}', [PaymentController::class, 'cancel'])->name('payment.cancel.ar');
    Route::match(['get', 'post'], '/{locale?}/paiement/annule/{order}', [PaymentController::class, 'cancel'])->name('payment.cancel');
});

// The CMI server-to-server callback. Deliberately NOT localised and NOT behind
// auth: it is a POST from the gateway's own servers, with no session, and it
// answers in CMI's protocol rather than in HTML. The gateway signature is what
// authenticates it, and it is verified before anything is written.
Route::post('/payment/cmi/callback', [PaymentController::class, 'callback'])
    ->name('payment.callback');

// The local rehearsal page, refused unless PAYMENT_DRIVER=test, so it can never
// become a way to settle an order in production. Not localised: a developer
// affordance, not a page a delegate ever sees.
Route::get('/payment/test/{uuid}', [PaymentController::class, 'testGatewayPage'])
    ->middleware('auth')
    ->name('payment.test');

// --- Documents --------------------------------------------------------------
//
// `Document::downloadUrl()` has always pointed at `documents.download`; before
// this route existed that call raised a RouteNotFoundException and any page
// listing a document was a 500.

Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
    ->name('documents.download');
Route::get('/{locale?}/documents/{document}/download', [DocumentController::class, 'download'])
    ->name('documents.download.locale');

Route::get('/presentations/{file}/download', [DocumentController::class, 'presentation'])
    ->middleware('auth')
    ->name('presentations.download');
Route::get('/{locale?}/presentations/{file}/download', [DocumentController::class, 'presentation'])
    ->middleware('auth')
    ->name('presentations.download.locale');

// --- The language switcher ------------------------------------------------
//
// POST, not GET: it writes to the session and to the user's stored preference,
// so a GET would let a third-party page flip a visitor's language with an <img>
// tag. It is CSRF-protected for the same reason.
//
// Not localised: there is nothing to localise *about* the switcher, and a
// prefixed POST target would make the form in the layout depend on the current
// page's prefix for no gain.

Route::post('/locale', LocaleController::class)->name('locale.switch');

use App\Http\Controllers\NewsletterController;

Route::post('/newsletter', [NewsletterController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('newsletter.store');

// Signed link sent by e-mail. {subscriber} resolves to NewsletterSubscriber by id.
Route::get('/newsletter/confirm/{subscriber}', [NewsletterController::class, 'confirm'])
    ->middleware(['signed', 'throttle:20,1'])
    ->name('newsletter.confirm');

Route::post('/sponsoring/demande', [SponsorshipRequestController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('sponsoring.request');
