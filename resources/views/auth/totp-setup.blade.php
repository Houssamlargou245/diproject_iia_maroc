{{--
    Enrolling an authenticator app.

    The step numbers are not decoration. The failure this page has to survive is
    the visitor closing the tab halfway and coming back tomorrow, so it says
    plainly what has already happened and what has not: the account exists, the
    secret is not yet active, and nothing is lost by leaving and coming back.

    The QR code and the manual key are the same secret, printed side by side,
    rather than the key hidden behind a "can't scan?" link. The two people who
    need the key are exactly the ones who will not find the link: someone on a
    desktop with no phone camera to hand, and someone whose camera will not focus
    on a screen in a conference hall.

    The page reads left to right on a desktop: step 1 is a strip across the top
    (install an app), then scan, type the key, confirm sit side by side, so the
    whole procedure fits on one screen without scrolling. Below 992px it stacks.

    The example apps carry their logos from a public icon CDN. The requests carry
    only the logo's name: the secret lives in the QR data URL and the key text,
    never in a URL, and referrerpolicy="no-referrer" keeps this page's address
    out of those requests. If a logo fails to load, the tile quietly falls back
    to a neutral drawn icon, so the page never shows a broken image.
--}}
@extends('layouts.app')

@section('title', __('totp.title'))

@section('content')
    @php
        // Example authenticator apps. Add, remove or reorder freely.
        // 'logo'  : full https URL (online) or a path under public/ (local); null = drawn icon only.
        // 'glyph' : fallback line icon from $glyphs, 'from'/'to' : fallback tile gradient.
        $apps = [
            ['name' => 'Google Authenticator',    'platforms' => 'iOS · Android', 'logo' => 'https://cdn.simpleicons.org/googleauthenticator',                  'glyph' => 'shield', 'from' => '#4f8bf5', 'to' => '#2f5fd0'],
            ['name' => 'Microsoft Authenticator', 'platforms' => 'iOS · Android', 'logo' => 'https://cdn.simpleicons.org/microsoftauthenticator',                'glyph' => 'lock',   'from' => '#2aa3e0', 'to' => '#0b6bb3'],
        ];

        $glyphs = [
            'shield' => '<path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/>',
            'lock'   => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/>',
            'ring'   => '<path d="M12 3a9 9 0 109 9"/><circle cx="12" cy="12" r="2"/>',
            'grid'   => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><path d="M14 14h6v6h-6z"/>',
            'key'    => '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M16 7l3 3"/>',
            'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        ];

        // Optional online picture for the "confirm" step (https URL). Leave null to
        // show the drawn phone illustration. It can also be passed from the controller.
        $helpImage = $helpImage ?? null;

        // Base32 secret shown in groups of four: easier to read aloud and to type.
        // The copy button still copies the raw, ungrouped value.
        $manualKeyDisplay = trim(chunk_split((string) $manualKey, 4, ' '));
    @endphp

    <style>
        .totp-page {
            --ink: #1b1464;
            --brand: #4f3cc9;
            --deep: #2f1f9c;
            --muted: #5f6384;
            --line: #e4e1f4;
            --surface: #f6f5fd;
            --accent: #6d3bd6;
            --shadow: 0 10px 30px rgba(47, 31, 156, .10);
        }
        .totp-shell { max-width: 1120px; margin-inline: auto; }

        /* ---------- Header: title left, state of the account right ---------- */
        .totp-head {
            display: grid;
            gap: 1rem;
            margin-block-end: 1.25rem;
        }
        .totp-head .app-title,
        .totp-head .app-lede,
        .totp-head .app-notice { margin-block: 0; }
        .totp-head .app-lede { margin-block-start: .5rem; }

        /* ---------- Card + steps ---------- */
        .totp-card { padding: 0; overflow: hidden; }
        .totp-steps {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            grid-template-columns: 1fr;
            counter-reset: totp-step;
        }
        .totp-step {
            counter-increment: totp-step;
            min-width: 0;
            padding: 1.25rem 1.4rem;
        }
        .totp-step + .totp-step { border-block-start: 1px solid var(--line); }

        .totp-step__head {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            margin-block-end: .75rem;
        }
        .totp-step__head::before {
            content: counter(totp-step);
            flex: 0 0 auto;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: .85rem;
            color: #fff;
            background: linear-gradient(135deg, #2b1d9a, #3a27b3);
            box-shadow: 0 4px 12px rgba(47, 31, 156, .28);
        }
        .totp-step__title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.3;
            color: var(--ink);
            padding-block-start: .3rem;
        }
        .totp-step__lede {
            margin: 0 0 .75rem;
            font-size: .84rem;
            line-height: 1.55;
            color: var(--muted);
        }

        /* ---------- Example apps: one horizontal strip ---------- */
        .totp-sr {
            position: absolute;
            width: 1px; height: 1px;
            margin: -1px; padding: 0;
            overflow: hidden;
            clip: rect(0 0 0 0);
            white-space: nowrap;
            border: 0;
        }
        .totp-apps {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: .6rem;
        }
        .totp-app {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: .15rem;
            padding: .8rem .5rem;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 10px;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
        }
        .totp-app:hover {
            border-color: #cfc9f0;
            box-shadow: 0 6px 18px rgba(47, 31, 156, .10);
            transform: translateY(-1px);
        }
        .totp-app__icon {
            position: relative;
            width: 48px;
            height: 48px;
            margin-block-end: .4rem;
            border-radius: 12px;
            display: grid;
            place-items: center;
            color: #fff;
            background: linear-gradient(135deg, var(--from), var(--to));
            box-shadow: 0 4px 10px rgba(27, 20, 100, .18);
        }
        /* A loaded logo sits on a white tile; a failed one falls back to the gradient. */
        .totp-app__icon--logo:not(.is-fallback) {
            background: #fff;
            border: 1px solid var(--line);
            box-shadow: none;
        }
        .totp-app__icon img {
            display: block;
            width: 30px;
            height: 30px;
            object-fit: contain;
        }
        .totp-app__icon--logo:not(.is-fallback) svg { display: none; }
        .totp-app__icon.is-fallback img { display: none; }
        .totp-app__icon svg {
            width: 24px;
            height: 24px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .totp-app__name {
            font-size: .8rem;
            font-weight: 700;
            line-height: 1.2;
            color: var(--ink);
        }
        .totp-app__meta {
            font-size: .7rem;
            color: var(--muted);
        }
        .totp-apps-note {
            margin: .75rem 0 0;
            font-size: .78rem;
            color: var(--muted);
        }

        /* ---------- QR ---------- */
        .totp-qr {
            display: inline-block;
            padding: .65rem;
            /* Always white, even on a dark theme: a scanner needs the quiet zone. */
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        .totp-qr img { display: block; max-width: 100%; height: auto; }

        /* ---------- Manual key ---------- */
        .totp-keybox {
            display: flex;
            flex-direction: column;
            gap: .6rem;
        }
        .totp-key {
            margin: 0;
            padding: .75rem .9rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: .98rem;
            font-weight: 600;
            letter-spacing: .1em;
            line-height: 1.6;
            color: var(--deep);
            background: var(--surface);
            border: 1px dashed #cfc9f0;
            border-radius: 8px;
            overflow-wrap: anywhere;
            user-select: all;
        }
        .totp-copy {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            width: 100%;
            padding: .55rem 1rem;
            font-size: .8rem;
            font-weight: 600;
            color: var(--deep);
            background: #eef0fb;
            border: 1px solid transparent;
            border-radius: 999px;
            cursor: pointer;
            transition: background .15s ease;
        }
        .totp-copy:hover { background: #e2e5f9; }
        .totp-copy svg {
            width: 16px;
            height: 16px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .totp-copy.is-done { color: #0f7b4a; background: #e3f6ec; }
        .totp-copy-status {
            min-height: 1.1rem;
            margin: 0;
            font-size: .78rem;
            color: var(--muted);
        }

        /* ---------- Confirm + help picture ---------- */
        .totp-confirm .app-code {
            text-align: center;
            font-size: 1.5rem;
            letter-spacing: .4em;
            font-variant-numeric: tabular-nums;
        }
        .totp-help {
            margin: 1rem 0 0;
            padding: .9rem;
            display: flex;
            align-items: center;
            gap: .9rem;
            background: var(--surface);
            border-radius: 10px;
        }
        .totp-help svg,
        .totp-help img {
            flex: 0 0 auto;
            width: 84px;
            height: auto;
            max-height: 120px;
            border-radius: 8px;
            object-fit: cover;
        }
        .totp-help figcaption {
            font-size: .78rem;
            line-height: 1.5;
            color: var(--muted);
        }

        .totp-tradeoff { margin-block-start: 1rem; text-align: center; }

        /* ---------- Focus (visible, AA) ---------- */
        .totp-page a:focus-visible,
        .totp-page button:focus-visible,
        .totp-page input:focus-visible {
            outline: 3px solid var(--accent);
            outline-offset: 2px;
        }

        /* ---------- Responsive ---------- */
        @media (min-width: 576px) {
            .totp-apps { grid-template-columns: repeat(3, 1fr); }
        }
        @media (min-width: 992px) {
            .totp-head { grid-template-columns: 1.15fr 1fr; align-items: center; gap: 2rem; }
            .totp-apps { grid-template-columns: repeat(6, 1fr); }

            .totp-steps { grid-template-columns: repeat(3, 1fr); }
            /* Step 1 is the strip across the top; steps 2-4 sit side by side below it. */
            .totp-step:first-child { grid-column: 1 / -1; }
            .totp-step:nth-child(n+3) {
                border-inline-start: 1px solid var(--line);
            }
        }
        @media (max-width: 575.98px) {
            .totp-step { padding: 1rem; }
            .totp-key { font-size: .9rem; letter-spacing: .06em; }
        }
        @media (prefers-reduced-motion: reduce) {
            .totp-app { transition: none; }
            .totp-app:hover { transform: none; }
        }
    </style>

    <div class="section-padding-04 totp-page">
        <div class="container">
            <div class="app-shell totp-shell">

                <div class="totp-head">
                    <div>
                        <h1 class="app-title">@lang('totp.heading')</h1>
                        <p class="app-lede">@lang('totp.lede')</p>
                    </div>

                    {{-- The state of the account, stated before the instructions
                         rather than discovered at the end. --}}
                    <div class="app-notice" role="status">
                        @lang('totp.pending_notice')
                    </div>
                </div>

                <div class="app-card totp-card">
                    <ol class="totp-steps">

                        {{-- 1. Install --}}
                        <li class="totp-step">
                            <div class="totp-step__head">
                                <h2 class="totp-step__title">@lang('totp.step_install')</h2>
                            </div>
                            <p class="totp-step__lede">@lang('totp.step_install_lede')</p>

                            <p class="totp-sr" id="totp-apps-label">@lang('totp.apps_label')</p>
                            <ul class="totp-apps" aria-labelledby="totp-apps-label">
                                @foreach ($apps as $app)
                                    @php
                                        $logoUrl = ! empty($app['logo'])
                                            ? (str_starts_with($app['logo'], 'http') ? $app['logo'] : asset($app['logo']))
                                            : null;
                                    @endphp
                                    <li class="totp-app">
                                        <span class="totp-app__icon {{ $logoUrl ? 'totp-app__icon--logo' : 'is-fallback' }}"
                                              aria-hidden="true"
                                              style="--from: {{ $app['from'] }}; --to: {{ $app['to'] }};">
                                            @if ($logoUrl)
                                                <img src="{{ $logoUrl }}"
                                                     alt=""
                                                     width="30"
                                                     height="30"
                                                     loading="lazy"
                                                     decoding="async"
                                                     referrerpolicy="no-referrer"
                                                     data-totp-logo>
                                            @endif
                                            <svg viewBox="0 0 24 24" focusable="false">{!! $glyphs[$app['glyph']] !!}</svg>
                                        </span>
                                        <span class="totp-app__name">{{ $app['name'] }}</span>
                                        <span class="totp-app__meta">{{ $app['platforms'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            {{-- Runs right after the list so no image can fail before the listener exists. --}}
                            <script>
                                document.querySelectorAll('img[data-totp-logo]').forEach(function (img) {
                                    img.addEventListener('error', function () {
                                        img.parentNode.classList.add('is-fallback');
                                    });
                                });
                            </script>
                            <p class="totp-apps-note">@lang('totp.apps_note')</p>
                        </li>

                        {{-- 2. Scan --}}
                        <li class="totp-step">
                            <div class="totp-step__head">
                                <h2 class="totp-step__title">@lang('totp.step_scan')</h2>
                            </div>
                            <p class="totp-step__lede">@lang('totp.step_scan_lede', ['issuer' => $issuer])</p>

                            @if ($qrCode !== '')
                                <div class="totp-qr">
                                    {{-- Inline SVG data URL, never a served image:
                                         this URI contains the shared secret, and a
                                         separate request would leave it in a cache
                                         where a shared machine could read it back. --}}
                                    <img src="{{ $qrCode }}"
                                         alt="{{ __('totp.qr_alt', ['issuer' => $issuer]) }}"
                                         width="200"
                                         height="200">
                                </div>
                            @endif
                        </li>

                        {{-- 3. Manual key --}}
                        <li class="totp-step">
                            <div class="totp-step__head">
                                <h2 class="totp-step__title">@lang('totp.step_manual')</h2>
                            </div>
                            <p class="totp-step__lede">@lang('totp.step_manual_lede')</p>

                            <div class="totp-keybox">
                                {{-- Forced LTR: the secret is base32, and bidi would
                                     present it in reverse on the Arabic page. --}}
                                <p class="totp-key" id="totp-key" dir="ltr" aria-label="{{ __('totp.key_label') }}">{{ $manualKeyDisplay }}</p>

                                <button type="button"
                                        class="totp-copy"
                                        id="totp-copy"
                                        data-value="{{ $manualKey }}"
                                        data-label="{{ __('totp.copy') }}"
                                        data-done="{{ __('totp.copied') }}"
                                        data-failed="{{ __('totp.copy_failed') }}">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <rect x="9" y="9" width="11" height="11" rx="2"/>
                                        <path d="M5 15V6a2 2 0 012-2h9"/>
                                    </svg>
                                    <span data-copy-text>@lang('totp.copy')</span>
                                </button>

                                <p class="totp-copy-status" id="totp-copy-status" aria-live="polite"></p>
                            </div>
                        </li>

                        {{-- 4. Confirm --}}
                        <li class="totp-step">
                            <div class="totp-step__head">
                                <h2 class="totp-step__title">@lang('totp.step_confirm')</h2>
                            </div>

                            <form method="POST" action="{{ route('totp.confirm') }}" class="totp-confirm">
                                @csrf

                                <x-form.field name="code" :label="__('totp.code_label')" :help="__('totp.code_hint', ['digits' => $codeLength])" required>
                                    {{-- One field, not six boxes: six separate inputs break
                                         paste-to-advance, block paste, and lose the value
                                         when autofill fills the first box. --}}
                                    <input type="text"
                                           name="code"
                                           id="totp-code"
                                           required
                                           inputmode="numeric"
                                           pattern="[0-9]*"
                                           autocomplete="one-time-code"
                                           autocapitalize="off"
                                           spellcheck="false"
                                           maxlength="{{ $codeLength }}"
                                           dir="ltr"
                                           @class(['input', 'app-code', 'input-error' => $errors->has('code')])
                                           @if ($errors->has('code')) aria-invalid="true" aria-describedby="code-error" @endif>
                                </x-form.field>

                                <button type="submit" class="btn btn-primary w-100">@lang('totp.submit')</button>
                            </form>

                            {{-- A picture of what the visitor is about to read off their
                                 phone. Decorative: the caption says it in words. --}}
                            <figure class="totp-help">
                                @if ($helpImage)
                                    <img src="{{ $helpImage }}" alt="" width="84" height="120" loading="lazy" referrerpolicy="no-referrer">
                                @else
                                    <svg viewBox="0 0 84 120" aria-hidden="true" focusable="false">
                                        <rect x="6" y="2" width="72" height="116" rx="12" fill="#1b1464"/>
                                        <rect x="12" y="14" width="60" height="92" rx="6" fill="#fff"/>
                                        <rect x="32" y="6" width="20" height="4" rx="2" fill="#4f3cc9"/>
                                        <rect x="18" y="22" width="30" height="5" rx="2.5" fill="#cfc9f0"/>
                                        <text x="42" y="64" text-anchor="middle" font-family="ui-monospace, Menlo, Consolas, monospace" font-size="13" font-weight="700" fill="#2f1f9c" letter-spacing="1">482 916</text>
                                        <circle cx="42" cy="88" r="9" fill="none" stroke="#e4e1f4" stroke-width="3"/>
                                        <path d="M42 79a9 9 0 0 1 8.5 6" fill="none" stroke="#6d3bd6" stroke-width="3" stroke-linecap="round"/>
                                    </svg>
                                @endif
                                <figcaption>@lang('totp.help_caption')</figcaption>
                            </figure>
                        </li>
                    </ol>
                </div>

                {{-- Stated up front because it is the trade this method makes:
                     free and unlimited, but a delegate without a spare handset
                     and without the recovery codes is locked out. --}}
                <p class="app-note totp-tradeoff">@lang('totp.tradeoff')</p>
            </div>
        </div>
    </div>

    <script>
        (function () {
            'use strict';

            // Code field: digits only, whether typed or pasted ("123 456" works).
            var code = document.getElementById('totp-code');
            if (code) {
                code.addEventListener('input', function () {
                    var max = parseInt(code.getAttribute('maxlength'), 10) || 6;
                    var clean = code.value.replace(/\D/g, '').slice(0, max);
                    if (clean !== code.value) { code.value = clean; }
                });
            }

            // Copy the raw secret (not the grouped display) to the clipboard.
            var btn = document.getElementById('totp-copy');
            var status = document.getElementById('totp-copy-status');
            if (!btn) { return; }

            var label = btn.querySelector('[data-copy-text]');
            var timer = null;

            function say(message, ok) {
                status.textContent = message;
                label.textContent = ok ? btn.dataset.done : btn.dataset.label;
                btn.classList.toggle('is-done', ok);
                clearTimeout(timer);
                timer = setTimeout(function () {
                    status.textContent = '';
                    label.textContent = btn.dataset.label;
                    btn.classList.remove('is-done');
                }, 2500);
            }

            function legacyCopy(text) {
                var area = document.createElement('textarea');
                area.value = text;
                area.setAttribute('readonly', '');
                area.style.position = 'fixed';
                area.style.insetBlockStart = '-1000px';
                document.body.appendChild(area);
                area.select();
                var ok = false;
                try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
                document.body.removeChild(area);
                return ok;
            }

            btn.addEventListener('click', function () {
                var text = btn.dataset.value;
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(
                        function () { say(btn.dataset.done, true); },
                        function () {
                            legacyCopy(text) ? say(btn.dataset.done, true) : say(btn.dataset.failed, false);
                        }
                    );
                } else {
                    legacyCopy(text) ? say(btn.dataset.done, true) : say(btn.dataset.failed, false);
                }
            });
        })();
    </script>
@endsection