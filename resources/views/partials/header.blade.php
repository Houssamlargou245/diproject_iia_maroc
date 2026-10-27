{{--
    Site header, ported from includes/header.php and includes/header-mobile.php.

    The markup keeps the template's class names — .exvent-header-section,
    .header-wrap, .main-menu, .header-meta — because assets/css/style.css
    addresses those classes directly. Changing them to utility classes would
    mean restyling the design rather than reusing it.

    What changed against the 2024 build, and why:

      * The login form posts to the app's own route instead of an AJAX call to
        login_form.php. Two fields fit the modal; a failed post comes back as a
        redirect with errors, which is why the modal is reopened below when the
        error bag is not empty. Real server-side auth also means the session
        cookie, the CSRF token and the rate limiting all apply, none of which
        the AJAX version had.

      * Registration is a link, not a modal tab. The app asks for a first name,
        a last name, a phone and a password before it sends an OTP, and
        cramming that into a modal is how the 2024 version ended up with a form
        that validated in the browser and then lost half the fields.

      * The 2024 build gated the speaker submissions behind a hand-rolled access
        code checked by validate_access_code.php. That is a password in a query
        string with no rate limit and no revocation, so the link is simply gated
        on being signed in; the submissions page itself decides what an
        authenticated user may see.
--}}

{{-- Sign-in modal. Only rendered for guests: a signed-in visitor has no use for
     it, and shipping a login form to a logged-in page is how credential
     phishing gets a foothold. Reopened automatically when a post comes back
     with errors, which is the case where the visitor most needs to see it. --}}
@guest
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-4">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('nav.close')"></button>

                    <h3 class="text-center" id="loginModalLabel">@lang('action.login')</h3>

                    <div class="hero-form mt-0">
                        <form method="POST" action="{{ route('login.store') }}" class="exvent-form">
                            @csrf

                            <div class="row gy-3">
                                <div class="col-12">
                                    <div class="alert alert-warning" role="alert">
                                        <p>@lang('login.member_notice')</p>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="sr-only" for="login_email">@lang('register.email')</label>
                                    <input class="form-control w-100 @error('email') is-invalid @enderror"
                                           type="email"
                                           name="email"
                                           id="login_email"
                                           value="{{ old('email') }}"
                                           autocomplete="username"
                                           required
                                           autofocus
                                           placeholder="@lang('register.email')">
                                    @error('email')
                                        <span class="error_span" role="alert">{{ $message }}</span>
                                    @enderror
                                    @error('credentials')
                                        <span class="error_span" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label class="sr-only" for="login_password">@lang('register.password')</label>
                                    <input class="form-control w-100 @error('password') is-invalid @enderror"
                                           type="password"
                                           name="password"
                                           id="login_password"
                                           autocomplete="current-password"
                                           required
                                           placeholder="@lang('register.password')">
                                    @error('password')
                                        <span class="error_span" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-12 text-center">
                                    <button type="submit" class="btn-log-inscr">@lang('login.submit')</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endguest

{{-- The sticky bar.

     `data-header` is the hook design.js watches for scroll: past the hero the
     bar becomes an opaque, blurred strip. Before that it stays transparent, so
     the hero photograph runs to the top edge — a 72px band of 70%-opacity white
     sitting on the image greys out the top of it for no benefit. --}}
<header class="d-header" data-header>
    <div class="d-header__bar">

        {{-- The conference lockup for the active language, from the 2026 brand
             guidelines. The Arabic main version is roughly three times as wide
             as the Latin pair, so width/height come from Brand::logo() and the
             CSS caps the height only: capping one dimension alone keeps every
             version at its own aspect ratio. site_name is already "ARABCIA 2026"
             in all three locales, so the alt text needs no translation. --}}
        <a href="{{ route('home') }}" class="d-header__brand">
            <img src="{{ \App\Support\Brand::logoUrl() }}"
                 width="{{ \App\Support\Brand::logo()['width'] }}"
                 height="{{ \App\Support\Brand::logo()['height'] }}"
                 alt="{{ __('site.site_name') }}"
                 fetchpriority="high"
                 decoding="async">
        </a>

        <nav class="d-header__nav" aria-label="{{ __('nav.menu') }}">
            @include('partials.nav', ['variant' => 'bar'])
        </nav>

        <div class="d-header__actions">
            <x-locale-switcher />

                        @if(auth()->check() && auth()->user()->hasConfirmedTotp())
                {{-- The cart icon points at the basket, not the order history: it
                     is the shopping symbol, and the order list is one click away
                     from the account page. The count is the number of places in
                     the basket, which is what the icon means on every other shop
                     the visitor has used.

                     `aria-hidden` on the glyph: the count is repeated in the
                     visually hidden text, so a screen reader announcing it twice
                     would say the number with no context. --}}
                @php($basketCount = \App\Models\Cart::forSession(request()->session()->getId(), auth()->user())->items()->sum('quantity'))
                <a href="{{ route('cart') }}" class="header-cart">
                    <span class="cart-btn" aria-hidden="true">
                        <i class="flaticon-shopping-cart" style="color: wheat;"></i>
                        @if ($basketCount > 0)
                            <span class="count">{{ $basketCount }}</span>
                        @endif
                    </span>
                    <span class="visually-hidden">
                        @lang('order.cart.title')@if ($basketCount > 0) ({{ $basketCount }}) @endif
                    </span>
                </a>
            @endif

            {{-- The account control.

                 A guest gets a link to the sign-in page; a signed-in visitor
                 gets a link to their own account. Both are rendered as the
                 same pill so the bar does not change shape when someone signs
                 in, and both hide below the phone breakpoint, where the drawer
                 carries them instead — a control that is not visible is not a
                 control.

                 The signed-in label is `displayName()`, which is the person's
                 own name and needs no translation. The visually hidden text
                 names the destination, so the accessible name is "My account —
                 Ahmed Bennani" rather than the name alone. --}}
                        @if(auth()->check() && auth()->user()->hasConfirmedTotp())
                <a href="{{ route('account') }}" class="d-btn d-btn--outline d-btn--sm d-header__account">
                    <span class="visually-hidden">@lang('account.title')</span>
                    <i class="fas fa-user-circle" aria-hidden="true"></i>
                    <span class="d-header__account-name">{{ auth()->user()->displayName() }}</span>
                </a>
            @elseif(auth()->check() || request()->session()->has('pending_registration'))
                <a href="{{ route('totp.setup') }}" class="d-btn d-btn--outline d-btn--sm d-header__account">
                    <i class="fas fa-shield-alt" aria-hidden="true"></i>
                    <span class="d-header__account-name">@lang('totp.title')</span>
                </a>
            @else
                {{-- The visible label is the accessible name here, so there is no
                     second copy of it for a screen reader to hear twice. --}}
                <a href="{{ route('login') }}" class="d-btn d-btn--outline d-btn--sm d-header__account">
                    <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                    <span class="d-header__account-name">@lang('action.login')</span>
                </a>
            @endif

            {{-- The one thing the header is asking for. Present at every width
                 with room for it; below that it moves into the drawer rather
                 than shrinking into unreadability. --}}
            <!-- @if ($currentEdition?->registration_open)
                <a href="{{ auth()->check() ? route('pricing') : route('register') }}"
                   class="d-btn d-btn--primary d-btn--sm">
                    @lang('nav.registration')
                </a>
            @endif -->

            <button type="button"
                    class="d-burger"
                    data-drawer-open
                    aria-expanded="false"
                    aria-controls="d-drawer">
                <span></span>
                <span></span>
                <span></span>
                <span class="visually-hidden">@lang('nav.menu')</span>
            </button>
        </div>

    </div>
</header>

{{-- The scrim. A real <button> rather than a div: it is a control that
     closes the drawer, and a clickable div is neither focusable nor
     announced as a control by a screen reader.

     `tabindex="-1"` keeps it out of the tab order — Escape and the close
     button are the accessible ways out of the panel, and this one exists
     for the pointer. --}}
<button type="button" class="d-scrim" data-drawer-close tabindex="-1"></button>

{{-- The drawer. A real dialog: `aria-modal`, labelled by its own heading,
     and dismissed by Escape, by the scrim and by the close button — three
     ways out, because a panel that can be dismissed only one way traps
     anyone not using a mouse.

     Its links are the same list the desktop bar renders (partials.nav with
     variant="drawer"), so the two can never drift apart. --}}
<div id="d-drawer"
     class="d-drawer"
     role="dialog"
     aria-modal="true"
     aria-labelledby="d-drawer-title"
     data-drawer>

    <div class="d-drawer__head">
        <h2 id="d-drawer-title" class="d-footer__title mb-0">@lang('nav.menu')</h2>
        <button type="button" class="d-drawer__close" data-drawer-close>
            <i class="fas fa-times" aria-hidden="true"></i>
            <span class="visually-hidden">@lang('nav.close')</span>
        </button>
    </div>

    @include('partials.nav', ['variant' => 'drawer'])

    {{-- Language and the primary action live in the drawer as well as in the
         bar. On a short phone the bar has room for the burger and little else,
         and a control that is not visible is not a control. --}}
    <div>
        <x-locale-switcher />
    </div>

    @if ($currentEdition?->registration_open)
        <a href="{{ auth()->check() ? route('pricing') : route('register') }}"
           class="d-btn d-btn--primary w-100">
            @lang('nav.registration')
        </a>
    @endif

    {{-- The account block, at the foot of the drawer.

         `d-drawer__list` rather than the footer's list class: the footer sits on
         the mauve band and its links are white, so reusing that class here
         would put white text on the drawer's white panel — an invisible menu.

         Signing out is a POST, not a link, so it is a real form with a CSRF
         token. A link would be a GET, and a GET that mutates state is
         triggerable by a third-party page. --}}
        {{-- On affiche le menu invité si non connecté OU si 2FA non validé --}}
    @if(!auth()->check() || !auth()->user()->hasConfirmedTotp())
        <ul class="d-drawer__list d-drawer__list--account">
            <li><a href="{{ route('login') }}">@lang('action.login')</a></li>
            @if ($currentEdition?->registration_open)
                <li><a href="{{ route('register') }}">@lang('action.register')</a></li>
            @endif
        </ul>
    @else
        <ul class="d-drawer__list d-drawer__list--account">
            <li><a href="{{ route('account') }}">@lang('account.title')</a></li>
            <li><a href="{{ route('orders.index') }}">@lang('order.orders')</a></li>
            <li>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="d-drawer__btn">@lang('action.logout')</button>
                </form>
            </li>
        </ul>
    @endif
</div>


<button type="button" class="d-scrim" data-drawer-close tabindex="-1"></button>


@if ($errors->any() && request()->routeIs('login*') && auth()->guest())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var el = document.getElementById('loginModal');
            if (!el) return;
            var modal = bootstrap.Modal.getOrCreateInstance(el);
          //  modal.show();
        });
    </script>
@endif
