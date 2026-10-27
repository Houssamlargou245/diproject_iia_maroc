{{--
    The hand-off to the payment gateway.

    A self-submitting POST form rather than a 302, because that is what the
    CMI integration expects and because some corporate proxies drop redirects
    to external hosts. This page must not be reached by a customer directly: it
    exists only to pass the signed payload on.

    The auto-submit is progressive enhancement. If scripting is off the button
    is still there, so the checkout is not a dead end for anyone — the 2024
    SendData.php had no such fallback and simply sat on a blank page.
--}}
<!DOCTYPE html>
<html lang="{{ $currentLocale->value }}" dir="{{ $currentLocale->direction() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@lang('order.payment.pending')</title>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body class="no-js">
    <div class="main-wrapper">
        <main id="main" class="container section-padding-04 text-center">

            <div class="d-inline-block spinner-border" role="status" aria-hidden="true"></div>

            <h1 class="h3 mt-4">@lang('order.payment.pending')</h1>
            <p class="text-muted">@lang('order.payment.pending_note')</p>
            <p class="text-muted">@lang('order.payment.secure')</p>

            <form id="gateway-form" method="{{ $redirect['method'] }}"
                  action="{{ $redirect['gateway_url'] }}">
                @foreach ($redirect['fields'] as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach

                {{-- Reachable by keyboard, by screen reader, and with
                     scripting disabled. --}}
                <noscript>
                    <button type="submit" class="btn-join btn-cirle mt-3">
                        @lang('order.place_order')
                    </button>
                </noscript>
            </form>

            @if ($errors->any())
                <div class="alert alert-danger mt-4" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </main>
    </div>

    @stack('scripts')
    <script>
        // Submit on the first visit. When the customer presses Back from CMI,
        // return to the restored checkout instead of posting the payment form
        // to CMI again.
        (function () {
            var form = document.getElementById('gateway-form');
            var checkoutUrl = @json(route('checkout'));
            var navigationEntries = window.performance && window.performance.getEntriesByType
                ? window.performance.getEntriesByType('navigation')
                : [];
            var navigationType = navigationEntries.length > 0 ? navigationEntries[0].type : null;

            if (navigationType === 'back_forward') {
                window.location.replace(checkoutUrl);

                return;
            }

            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    window.location.replace(checkoutUrl);
                }
            });

            if (form) {
                form.submit();
            }
        })();
    </script>
</body>
</html>
