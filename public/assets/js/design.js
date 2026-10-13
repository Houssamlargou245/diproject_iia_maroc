/* ==========================================================================
   ARABCIA 2026 — interaction
   ==========================================================================
   Five features, each isolated in its own `safely()` block so one failure
   cannot take the others with it.

   Progressive enhancement throughout: with this file blocked, missing or
   throwing, the page is complete and readable. That is not a nicety — the
   countdown, the drawer, the count-up and the video button are all
   enhancements on markup that already says the same thing without them:

     - the countdown renders "—" per unit, and the date is in the hero badge
     - the drawer is a stack of links; the bar carries the same links
     - the stats print their final number in the markup
     - the video shows the native browser controls

   Two rules this file follows without exception:

     1. Honour `prefers-reduced-motion`. Every moving feature checks it first.

     2. Never throw. Each feature is wrapped, and `safely()` catches the rest.

   No dependencies on purpose: jQuery 1.12 is already on the page, and a second
   runtime beside it is how two libraries end up fighting over one property.
   ========================================================================== */

(function () {
    'use strict';

    var motionQuery = window.matchMedia
        ? window.matchMedia('(prefers-reduced-motion: reduce)')
        : { matches: false };

    function prefersReducedMotion() {
        return motionQuery.matches;
    }

    /* Isolated, so one failure cannot take the others with it. */
    function safely(label, fn) {
        try {
            fn();
        } catch (error) {
            if (window.console && window.console.warn) {
                window.console.warn('[design] ' + label + ' failed:', error);
            }
        }
    }

    function each(selector, callback, scope) {
        var nodes = (scope || document).querySelectorAll(selector);

        for (var i = 0; i < nodes.length; i++) {
            callback(nodes[i], i);
        }
    }

    /* =====================================================================
       Sticky header

       One class, toggled once past the hero rather than on every scroll
       event. The handler is passive because it only reads scrollY — a
       non-passive scroll listener blocks the compositor on every frame, which
       is the single most common cause of a janky page.
       ===================================================================== */

    safely('sticky header', function () {
        var header = document.querySelector('[data-header]');

        if (!header) {
            return;
        }

        var ticking = false;

        function update() {
            // Past the hero, not past a magic pixel count: the hero's height is
            // a function of the viewport, and a fixed threshold gets it wrong on
            // a short laptop and on a tall monitor.
            //
            // `[data-hero]` rather than a class, so the hook belongs to whichever
            // hero the page ships and neither file has to know the other's name.
            var hero = document.querySelector('[data-hero]');
            var threshold = hero ? Math.min(hero.offsetHeight - 120, 240) : 120;

            header.classList.toggle('is-stuck', window.scrollY > threshold);

            ticking = false;
        }

        // The threshold is a function of the hero's height, which changes with the
        // viewport. Without this the bar keeps the state it had at the width the
        // page was loaded at, and a rotation across the breakpoint leaves it stuck
        // or transparent in the wrong place.
        window.addEventListener('resize', update, { passive: true });

        update();

        window.addEventListener('scroll', function () {
            if (ticking) {
                return;
            }

            ticking = true;
            window.requestAnimationFrame(update);
        }, { passive: true });
    });

    /* =====================================================================
       Mobile drawer

       A modal dialog done properly, which means three things the naive version
       misses:

         1. Focus moves into the panel on open and returns to the button on
            close. Without this a keyboard user opens the drawer and focus
            stays behind it on the page underneath, which they cannot see.

         2. Tab is trapped inside it. `aria-modal` tells a screen reader the
            rest of the page is unavailable; only actually trapping focus makes
            that true for a keyboard.

         3. Escape closes it. A panel with no keyboard exit is a trap.

       Body scroll is locked while open because on iOS the page scrolls *under*
       a fixed panel and drags it along.
       ===================================================================== */

    safely('drawer', function () {
        var drawer = document.querySelector('[data-drawer]');
        var scrim = document.querySelector('.d-scrim');
        var opener = document.querySelector('[data-drawer-open]');

        if (!drawer || !opener) {
            return;
        }

        var FOCUSABLE = 'a[href], button:not([disabled]), input:not([type="hidden"]), select, textarea, [tabindex]:not([tabindex="-1"])';

        function isOpen() {
            return drawer.classList.contains('is-open');
        }

        function open() {
            drawer.classList.add('is-open');
            document.body.classList.add('drawer-open');
            opener.setAttribute('aria-expanded', 'true');

            if (scrim) {
                scrim.classList.add('is-open');
            }

            var first = drawer.querySelector(FOCUSABLE);

            if (first) {
                first.focus();
            }
        }

        function close() {
            drawer.classList.remove('is-open');
            document.body.classList.remove('drawer-open');
            opener.setAttribute('aria-expanded', 'false');

            if (scrim) {
                scrim.classList.remove('is-open');
            }

            // Focus goes back where it came from, which is the only way a
            // keyboard user knows where they are afterwards.
            opener.focus();
        }

        opener.addEventListener('click', function () {
            isOpen() ? close() : open();
        });

        each('[data-drawer-close]', function (node) {
            node.addEventListener('click', close);
        });

        document.addEventListener('keydown', function (event) {
            if (!isOpen()) {
                return;
            }

            if (event.key === 'Escape') {
                close();
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            // Focus trap: cycle within the panel rather than letting Tab walk
            // out into the page behind it.
            var items = drawer.querySelectorAll(FOCUSABLE);

            if (items.length === 0) {
                return;
            }

            var first = items[0];
            var last = items[items.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });
    });


    /* =====================================================================
       Countdown

       Driven from the edition's own start date, carried on the element as an
       ISO 8601 string rather than hardcoded — the next edition updates this by
       changing a row, not by editing a template.

       With scripting off the units stay at "—", which is honest: a dash is not
       a wrong number, it is an absence. The date itself is in the hero badge
       directly above, so nothing is actually lost.

       Under reduced motion the clock still ticks, because information is
       changing rather than decoration moving. It simply does not animate.
       ===================================================================== */

    safely('countdown', function () {
        each('[data-countdown]', function (node) {
            var target = new Date(node.getAttribute('data-countdown'));

            if (isNaN(target.getTime())) {
                return;
            }

            var units = {
                days: node.querySelector('[data-count="days"]'),
                hours: node.querySelector('[data-count="hours"]'),
                minutes: node.querySelector('[data-count="minutes"]'),
                seconds: node.querySelector('[data-count="seconds"]')
            };

            function pad(value) {
                return value < 10 ? '0' + value : String(value);
            }

            function tick() {
                var remaining = target.getTime() - Date.now();

                if (remaining <= 0) {
                    // The event has started. Remove the block rather than
                    // leaving a permanent "00 00 00 00" under a conference
                    // that has already happened.
                    node.remove();
                    return true;
                }

                var seconds = Math.floor(remaining / 1000);

                if (units.days) {
                    units.days.textContent = String(Math.floor(seconds / 86400));
                }

                if (units.hours) {
                    units.hours.textContent = pad(Math.floor((seconds % 86400) / 3600));
                }

                if (units.minutes) {
                    units.minutes.textContent = pad(Math.floor((seconds % 3600) / 60));
                }

                if (units.seconds) {
                    units.seconds.textContent = pad(seconds % 60);
                }

                return false;
            }

            if (tick()) {
                return;
            }

            var timer = window.setInterval(function () {
                if (tick()) {
                    window.clearInterval(timer);
                }
            }, 1000);
        });
    });

    /* =====================================================================
       Count-up

       Animates from zero when the card scrolls into view. The final value is
       already in the markup, so this is decoration on top of content rather
       than the content itself — which is why a reduced-motion visitor simply
       sees the number, immediately, and a no-JS visitor does too.

       `requestAnimationFrame` rather than `setInterval`: the animation
       repaints a text node once per frame either way, and rAF stops when the
       tab is backgrounded instead of queueing a thousand callbacks.
       ===================================================================== */

    safely('count up', function () {
        var targets = document.querySelectorAll('[data-count-to]');

        if (targets.length === 0) {
            return;
        }

        function animate(node) {
            var to = parseFloat(node.getAttribute('data-count-to'));

            if (isNaN(to)) {
                return;
            }

            if (prefersReducedMotion()) {
                node.textContent = to.toLocaleString();
                return;
            }

            var duration = 1200;
            var start = null;

            function frame(timestamp) {
                if (start === null) {
                    start = timestamp;
                }

                var progress = Math.min((timestamp - start) / duration, 1);

                // easeOutCubic: fast at first, settling into the target. A
                // linear ramp looks mechanical; this is the difference between
                // "a number" and "counting to something".
                var eased = 1 - Math.pow(1 - progress, 3);

                node.textContent = Math.round(to * eased).toLocaleString();

                if (progress < 1) {
                    window.requestAnimationFrame(frame);
                }
            }

            window.requestAnimationFrame(frame);
        }

        if (!('IntersectionObserver' in window)) {
            each(targets, animate);
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                animate(entry.target);
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.4 });

        each(targets, function (node) {
            observer.observe(node);
        });
    });

    /* =====================================================================
       Video

       Replaces the browser's start control with a real button, then gets out
       of the way. The native controls keep working underneath, so pause,
       volume and fullscreen are all still available once it is playing.
       ===================================================================== */

    safely('video play button', function () {
        each('[data-video]', function (wrapper) {
            var video = wrapper.querySelector('video');
            var button = wrapper.querySelector('[data-video-play]');

            if (!video || !button) {
                return;
            }

            button.addEventListener('click', function () {
                if (video.paused) {
                    video.play();
                }
            });

            video.addEventListener('play', function () {
                wrapper.classList.add('is-playing');
            });

            // Return the button when the video ends, so the page is not left
            // showing an invisible control over a stopped video.
            video.addEventListener('ended', function () {
                wrapper.classList.remove('is-playing');
            });
        });
    });

    /* =====================================================================
       Cookie notice

       Shown only when no answer has been stored, so a returning visitor never
       sees it flash. The choice lives in `localStorage` rather than a cookie:
       it is a preference about this browser that the server never needs to
       read, and setting a cookie here would be the very act the banner asks
       about.

       localStorage throws outright in private browsing on some browsers, so
       every access is wrapped — an exception here would take the rest of the
       page's script with it.
       ===================================================================== */

    safely('cookie notice', function () {
        var node = document.querySelector('[data-cookie]');

        if (!node) {
            return;
        }

        var KEY = 'arabcia.consent';

        function read() {
            try {
                return window.localStorage.getItem(KEY);
            } catch (error) {
                // Private browsing, or storage disabled. Treat as "not asked
                // yet" and show the banner — an unanswered question is better
                // than a remembered wrong answer.
                return null;
            }
        }

        function write(value) {
            try {
                window.localStorage.setItem(KEY, value);
            } catch (error) {
                // Nothing to do. The banner will ask again next visit, which is
                // an acceptable outcome for a storage-less browser.
            }
        }

        if (read() !== null) {
            return;
        }

        node.hidden = false;

        function dismiss(value) {
            write(value);
            node.hidden = true;
        }

        var accept = node.querySelector('[data-cookie-accept]');
        var decline = node.querySelector('[data-cookie-decline]');
        var close = node.querySelector('[data-cookie-dismiss]');

        if (accept) {
            accept.addEventListener('click', function () {
                dismiss('accepted');
            });
        }

        if (decline) {
            decline.addEventListener('click', function () {
                dismiss('declined');
            });
        }

        if (close) {
            close.addEventListener('click', function () {
                dismiss('dismissed');
            });
        }
    });

    /* =====================================================================
       Back to top

       Scrolls, rather than jumping with `href="#top"`, so the motion can be
       skipped under reduced motion and so the focus ring does not teleport to
       the document root.
       ===================================================================== */

    safely('back to top', function () {
        var button = document.querySelector('[data-back-to-top]');

        if (!button) {
            return;
        }

        button.addEventListener('click', function () {
            window.scrollTo({
                top: 0,
                behavior: prefersReducedMotion() ? 'auto' : 'smooth'
            });
        });
    });

    /* =====================================================================
       Programme day tabs

       The tabs are real links with `?day=` on them and every day's timetable
       already in the document, so this is an upgrade rather than the mechanism:
       it swaps which panel is visible instead of reloading the page, and
       rewrites the query string so the address bar still names the day that is
       open. Without this file the links navigate and the server does exactly
       the same thing, so nothing here is load-bearing.

       `replaceState`, not `pushState`: the day is a view of the page rather
       than a new page, and pushing would put an entry per tab in the history
       stack so that the back button walked through days instead of leaving.

       The arrow keys are here because that is what a tablist is expected to do
       and because `role="tab"` promises it — the browser's default for a link
       is Enter-to-follow, not arrow navigation.
       ===================================================================== */

    safely('programme day tabs', function () {
        var list = document.querySelector('[data-day-tabs]');

        if (!list) {
            return;
        }

        var tabs = list.querySelectorAll('[data-day-tab]');
        var announcer = document.getElementById('ux-live');

        if (!tabs.length) {
            return;
        }

        function panelFor(tab) {
            var id = tab.getAttribute('data-day-tab');

            return id ? document.getElementById(id) : null;
        }

        function select(tab, moveFocus) {
            var target = panelFor(tab);

            // A tab pointing at a panel that is not there is worse than no tab:
            // return instead of marking everything selected and showing nothing.
            if (!target) {
                return false;
            }

            for (var i = 0; i < tabs.length; i++) {
                var other = tabs[i];
                var otherPanel = panelFor(other);
                var isCurrent = other === tab;

                other.setAttribute('aria-selected', isCurrent ? 'true' : 'false');

                /* Roving tabindex, which is what a tablist is: only the selected
                   tab is in the tab order, the rest are reached with the arrow
                   keys from it. Leaving every tab focusable turns the tab strip
                   into two stops in the page's tab order before any real content
                   — so a keyboard user walks the two days again on every pass
                   through the page. */
                other.setAttribute('tabindex', isCurrent ? '0' : '-1');

                if (isCurrent) {
                    other.setAttribute('aria-current', 'true');
                } else {
                    other.removeAttribute('aria-current');
                }

                if (otherPanel) {
                    otherPanel.hidden = !isCurrent;
                }
            }

            // The date is the only part of the tab that changes meaning between
            // days, so it is what gets announced: `aria-selected` alone tells a
            // screen-reader user that a tab is now selected and not which one.
            if (announcer && target.querySelector('.h-schedule__date')) {
                announcer.textContent = target.querySelector('.h-schedule__date').textContent.trim();
            }

            if (moveFocus) {
                tab.focus();
            }

            return true;
        }

        function bind(tab) {
            tab.addEventListener('click', function (event) {
                // Modifier-clicking a day link is "open this day in a new tab",
                // and it has to keep doing that: only a plain left click is a
                // tab switch.
                if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) {
                    return;
                }

                if (!select(tab, false)) {
                    return;
                }

                event.preventDefault();

                if (window.history && window.history.replaceState) {
                    window.history.replaceState({}, '', tab.getAttribute('href'));
                }
            });

            tab.addEventListener('keydown', function (event) {
                var index = -1;

                for (var i = 0; i < tabs.length; i++) {
                    if (tabs[i] === tab) {
                        index = i;
                    }
                }

                var next = null;

                switch (event.key) {
                    case 'ArrowRight':
                    case 'ArrowDown':
                        next = tabs[(index + 1) % tabs.length];
                        break;
                    case 'ArrowLeft':
                    case 'ArrowUp':
                        next = tabs[(index - 1 + tabs.length) % tabs.length];
                        break;
                    case 'Home':
                        next = tabs[0];
                        break;
                    case 'End':
                        next = tabs[tabs.length - 1];
                        break;
                    default:
                        return;
                }

                if (next && select(next, true)) {
                    event.preventDefault();
                }
            });
        }

        for (var t = 0; t < tabs.length; t++) {
            bind(tabs[t]);
        }
    });

    /* =====================================================================
       Session detail — the "+" on a programme row

       A disclosure, and therefore three things rather than one: the button owns
       `aria-expanded`, it names the panel with `aria-controls`, and the panel
       it opens is real markup in the document. So the row stays readable with
       scripting off, and `hidden` is what collapses it — which is also why
       design.css has to let `[hidden]` beat `display: grid`.
       ===================================================================== */

    safely('session detail', function () {
        each('[data-slot-toggle]', function (button) {
            var id = button.getAttribute('aria-controls');
            var panel = id ? document.getElementById(id) : null;

            if (!panel) {
                // A control pointing at nothing is worse than no control. The
                // button is removed rather than left inert, so the row does not
                // advertise a detail it cannot open.
                button.remove();
                return;
            }

            var showLabel = button.textContent;
            var closeLabel = button.getAttribute('data-label-close');
            var caption = button.querySelector('.visually-hidden');

            button.addEventListener('click', function () {
                var open = button.getAttribute('aria-expanded') === 'true';

                button.setAttribute('aria-expanded', open ? 'false' : 'true');
                panel.hidden = open;

                // The name says what pressing it will do, which `aria-expanded`
                // alone does not — it states the state, not the action.
                if (closeLabel && caption) {
                    caption.textContent = open ? showLabel : closeLabel;
                }
            });
        });
    });

    /* =====================================================================
       Speaker rail

       The rail is already a scroll container; these buttons are a second route
       to it, not the only one. They are revealed *only* when the rail actually
       overflows, and disabled at each end, so there is never a pair of arrows
       sitting there with nothing to scroll and never a lit arrow that does
       nothing.

       One card-width per press rather than a page: a rail of eight cards has
       room for one card to be the answer, and jumping four at a time skips
       the contents of the ones in between.
       ===================================================================== */

    safely('speaker rail', function () {
        each('[data-rail-track]', function (track) {
            var name = track.getAttribute('data-rail-track');
            var prev = document.querySelector('[data-rail-prev][data-rail="' + name + '"]');
            var next = document.querySelector('[data-rail-next][data-rail="' + name + '"]');

            if (!prev && !next) {
                return;
            }

            var isRtl = document.documentElement.dir === 'rtl';

            function step() {
                var card = track.firstElementChild;

                return card ? card.getBoundingClientRect().width + 16 : track.clientWidth;
            }

            function overflows() {
                // One pixel of slack: a fractional layout width otherwise makes
                // this true on a rail that does not scroll, which leaves two
                // permanently enabled arrows that move nothing.
                return track.scrollWidth - track.clientWidth > 1;
            }

            function sync() {
                var scrollable = overflows();

                // `hidden` is in the markup, so the arrows are absent until this
                // decides they are needed.
                prev.hidden = !scrollable;
                next.hidden = !scrollable;

                if (!scrollable) {
                    return;
                }

                // The scroll position is RTL-negative in an Arabic document, so
                // "at the start" is not always the smaller number.
                var max = Math.abs(track.scrollWidth - track.clientWidth);
                var position = Math.abs(track.scrollLeft);

                prev.disabled = position <= 1;
                next.disabled = position >= max - 1;
            }

            function scrollBy(direction) {
                track.scrollBy({
                    left: direction * step(),
                    behavior: prefersReducedMotion() ? 'auto' : 'smooth'
                });
            }

            if (prev) {
                prev.addEventListener('click', function () {
                    scrollBy(isRtl ? 1 : -1);
                });
            }

            if (next) {
                next.addEventListener('click', function () {
                    scrollBy(isRtl ? -1 : 1);
                });
            }

            track.addEventListener('scroll', sync, { passive: true });
            window.addEventListener('resize', sync, { passive: true });

            sync();
        });
    });

    /* =====================================================================
       Quantity stepper

       The buttons edit the number input; they never replace it. The input stays
       a real input so the value is submitted and announced, and the page is
       complete without this block — a delegate who prefers typing can, and one
       whose browser blocked the file loses nothing but the shortcut.

       Two rules the arithmetic follows:

         - the value is clamped to the input's own min and max, read from the
           markup rather than hard-coded here, so the server's bounds and the
           control's bounds cannot drift apart
         - the member quantity is pulled down with the total, because the
           controller rejects a line whose member places exceed its quantity and
           a stepper click that always fails is worse than no stepper
       ===================================================================== */

    safely('quantity stepper', function () {
        each('[data-stepper-input]', function (input) {
            var stepper = input.closest('.d-stepper');

            if (!stepper) {
                return;
            }

            var down = stepper.querySelector('[data-stepper-down]');
            var up = stepper.querySelector('[data-stepper-up]');
            /* Looked up inside the line's own form. Every line has exactly one
               hidden member_quantity, and reaching for it by id across the page
               would break the moment a second basket appeared on a page. */
            var member = input.form
                ? input.form.querySelector('input[name="member_quantity"]')
                : null;

            /* The down button is rendered disabled at one place, and disabled
               again here after a typed value, so both paths end in the same
               state. */
            function floor() {
                var min = input.min === '' ? 0 : parseInt(input.min, 10);

                return isNaN(min) ? 0 : min;
            }

            function ceiling() {
                var max = input.max === '' ? 99 : parseInt(input.max, 10);

                return isNaN(max) ? 99 : max;
            }

            function clamp(value) {
                var min = floor();
                var max = ceiling();

                /* An empty or half-typed field is left alone. Rewriting it while
                   someone is on their way to typing 12 into a field that reads 1
                   is the classic way to make a form unusable. */
                if (value === '' || isNaN(value)) {
                    return;
                }

                var next = Math.min(Math.max(parseInt(value, 10), min), max);

                if (next === parseInt(input.value, 10)) {
                    return;
                }

                input.value = next;
            }

            function sync() {
                var value = parseInt(input.value, 10);

                if (down) {
                    /* One, not zero: zero removes the line, and that has its own
                       button. A decrement that deletes is the kind of thing a
                       delegate discovers by accident on a phone. */
                    down.disabled = isNaN(value) || value <= Math.max(floor(), 1);
                }

                if (up) {
                    up.disabled = isNaN(value) || value >= ceiling();
                }

                if (member) {
                    var places = parseInt(member.value, 10);

                    if (!isNaN(places) && !isNaN(value) && places > value) {
                        member.value = value;
                    }
                }
            }

            function nudge(direction) {
                var step = parseInt(input.step, 10);

                if (isNaN(step) || step < 1) {
                    step = 1;
                }

                var current = parseInt(input.value, 10);

                if (isNaN(current)) {
                    current = floor();
                }

                clamp(String(current + direction * step));
                sync();
            }

            if (down) {
                down.addEventListener('click', function () {
                    nudge(-1);
                });
            }

            if (up) {
                up.addEventListener('click', function () {
                    nudge(1);
                });
            }

            input.addEventListener('input', sync);

            /* Enter submits the surrounding form, which is the keyboard
               equivalent of the Update button beside it. */
            input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    var form = input.form;

                    if (form) {
                        event.preventDefault();
                        form.submit();
                    }
                }
            });

            sync();
        });
    });

    /* Password reveal. Delegated to each toggle in the markup so login,
       register and any future password field share one behaviour. The field
       itself carries the state, so a password manager or a failed submit
       never loses it. */
    safely('password reveal', function () {
        each('[data-pw-toggle]', function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.getAttribute('data-pw-toggle'));

                if (!input) {
                    return;
                }

                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';

                var icon = button.querySelector('i');

                if (icon) {
                    icon.className = show ? 'far fa-eye-slash' : 'far fa-eye';
                }

                var label = show
                    ? button.getAttribute('data-label-hide')
                    : button.getAttribute('data-label-show');

                if (label) {
                    button.setAttribute('aria-label', label);
                }

                button.setAttribute('aria-pressed', show ? 'true' : 'false');
            });
        });
    });

    /* =====================================================================
       Partner logo slider

       Swiper drives the partner wall: infinite loop, arrows, drag. The `.is-ready`
       hook is only set once Swiper has taken over, so the CSS fallback (a
       scrollable row) stays in force if Swiper is absent or throws.

       Reduced motion drops the autoplay but keeps the arrows and drag — the user
       still moves the slider, it just never moves on its own.
       ===================================================================== */

    safely('logo slider', function () {
        if (!window.Swiper) {
            return;
        }

        each('[data-logo-marquee]', function (root) {
            var viewport = root.querySelector('.swiper');

            if (!viewport) {
                return;
            }

            /* The gap is a plain px custom property (not a clamp) precisely so it
               can be read here and handed to Swiper, which needs a number. */
            var gap = parseFloat(window.getComputedStyle(root).getPropertyValue('--lm-gap')) || 20;

            var options = {
                loop: true,
                slidesPerView: 'auto',
                spaceBetween: gap,
                speed: parseInt(root.getAttribute('data-logo-speed'), 10) || 3200,
                grabCursor: true,
                /* A transition is always running on the gliding wall, and Swiper
                   refuses `slideNext`/`slidePrev` mid-transition unless this is
                   off — which is exactly why the arrows did nothing. */
                loopPreventsSlide: false
            };

            if (!prefersReducedMotion()) {
                /* `delay: 0` keeps a transition running at all times, so with the
                   linear timing in CSS the wall glides continuously rather than
                   stepping and waiting — an endless marquee that can still be
                   dragged or advanced with the arrows. */
                options.autoplay = {
                    delay: 0,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true
                };
            }

            var swiper = new window.Swiper(viewport, options);

            root.classList.add('is-ready');

            /* The arrows run their own fast step instead of Swiper's `navigation`,
               which would reuse the slow glide `speed` — on an always-moving wall
               that reads as "nothing happened". The glide is paused while the user
               steps, then resumed shortly after they stop clicking, so the arrows
               advance a full card each time instead of being dragged back to the
               same snap by the ongoing glide. */
            var hasAutoplay = !!options.autoplay;
            var STEP = 300;
            var RESUME_AFTER = 1600;
            var resumeTimer = null;

            function step(direction) {
                if (hasAutoplay && swiper.autoplay.running) {
                    swiper.autoplay.stop();
                }

                if (direction > 0) {
                    swiper.slideNext(STEP, true);
                } else {
                    swiper.slidePrev(STEP, true);
                }

                if (!hasAutoplay) {
                    return;
                }

                window.clearTimeout(resumeTimer);
                resumeTimer = window.setTimeout(function () {
                    resumeTimer = null;

                    if (!swiper.el.matches(':hover')) {
                        swiper.autoplay.start();
                    }
                }, RESUME_AFTER);
            }

            var prev = root.querySelector('[data-logo-prev]');
            var next = root.querySelector('[data-logo-next]');

            if (prev) {
                prev.addEventListener('click', function () { step(-1); });
            }

            if (next) {
                next.addEventListener('click', function () { step(1); });
            }
        });
    });
}());

/* =====================================================================
       Checkout places — add or remove a participant without a reload

       An upgrade on the form, not a replacement for it. The buttons are
       real submit buttons carrying `formaction` and the intent in their own
       `value`, and this block only intercepts the click, posts the same
       request with `Accept: application/json`, and swaps in what the server
       sends back. Blocked, throttled or throwing, the post and the redirect
       still register the place — nothing here is load-bearing.

       Why the server still decides everything:

         - the price of a place is not computable in the browser, because the
           member rate depends on an active Membership record
         - the edition's remaining capacity is not knowable from the page
         - so the count and the total that come back are the authoritative
           ones, not a local guess that has to be reconciled later

       The whole form is posted, not just the button. The buyer has usually
       typed names into the seats that already exist, and re-rendering the
       fields from the server would wipe them unless they travelled with the
       request.

       The steppers are updated in place rather than re-rendered wholesale:
       replacing them would destroy the very button that was clicked and throw
       away the keyboard focus sitting on it.

       One request in flight at a time. Two clicks racing on the same line
       would both read the same quantity and the second would silently undo the
       first — the visible count would disagree with the basket.
       ===================================================================== */

    safely('checkout places', function () {
        var root = document.querySelector('[data-seats]');

        if (!root || !window.fetch || !window.FormData) {
            return;
        }

        var form = root.closest('form');
        var participants = document.querySelector('[data-participants]');
        var summary = document.querySelector('[data-summary]');
        var errorSlot = document.querySelector('[data-seats-error]');
        var announcer = document.getElementById('ux-live');
        var token = document.querySelector('meta[name="csrf-token"]');
        var busy = false;

        if (!form) {
            return;
        }

        function say(message) {
            if (announcer && message) {
                announcer.textContent = message;
            }
        }

        function showError(message) {
            if (!errorSlot) {
                return;
            }

            errorSlot.innerHTML = '';

            if (!message) {
                return;
            }

            var p = document.createElement('p');

            /* `role="alert"` on the element as it is inserted, so the refusal
               is announced as it appears. A message that only becomes visible
               is a message assistive tech is never told about. */
            p.className = 'field-error';
            p.setAttribute('role', 'alert');
            p.textContent = message;
            errorSlot.appendChild(p);
        }

        /* Read back from the server rather than incremented here: this is also
           how the disabled states get corrected, since availability depends on
           the cap and on the buyer's membership. */
        function applySeats(seats) {
            if (!seats) {
                return;
            }

            for (var i = 0; i < seats.length; i++) {
                var seat = seats[i];
                var line = root.querySelector('[data-seats-line="' + seat.ticket_type_id + '"]');

                if (!line) {
                    continue;
                }

                var count = line.querySelector('[data-seats-count]');

                if (count) {
                    count.textContent = seat.quantity;
                }

                each('[data-seats-btn]', function (button) {
                    var intent = button.getAttribute('data-seats-btn');

                    if (intent.charAt(0) === '+') {
                        button.disabled = !seat.can_add;
                    } else {
                        button.disabled = !seat.can_remove;
                    }
                }, line);
            }
        }

        function applyTotal(message) {
            if (summary && message && message.summary) {
                summary.innerHTML = message.summary;
            }

            var total = root.querySelector('[data-seats-total]');

            /* Both forms of the count are in the markup, because choosing between
               them is the script's job once the number can change without a
               reload — and "1 participants" is the kind of thing a buyer notices
               while deciding whether to add anyone. */
            if (total && typeof message.count === 'number') {
                var one = total.getAttribute('data-count-one');
                var other = total.getAttribute('data-count-other');

                if (one && other) {
                    total.textContent = (message.count === 1 ? one : other)
                        .replace(':count', message.count);
                }
            }
        }

        each('[data-seats-btn]', function (button) {
            button.addEventListener('click', function (event) {
                /* Modifier-click and middle-click mean "open this elsewhere" and
                   "don't fire handlers"; a plain left click or Enter is a seat
                   change. */
                if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
                    return;
                }

                if (busy) {
                    event.preventDefault();
                    return;
                }

                var action = button.getAttribute('formaction');

                if (!action) {
                    return;
                }

                event.preventDefault();
                busy = true;

                /* `FormData(form)` omits submit buttons — only the one used to
                   submit is a successful control — so the intent is added back
                   explicitly. `_token` comes along with the rest of the form. */
                var body = new FormData(form);

                body.append('adjust', button.value);

                button.classList.add('is-busy');

                window.fetch(action, {
                    method: 'POST',
                    body: body,
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
                    }
                }).then(function (response) {
                    /* A 419 or a 422 is a real answer from the server, not a
                       transport failure, so it is parsed like any other body
                       rather than reported as "the network is down". */
                    return response.json().then(function (payload) {
                        return { ok: response.ok, payload: payload };
                    });
                }).then(function (result) {
                    var payload = result.payload || {};
                    var message = payload.message;

                    if (!result.ok) {
                        /* Fall back to the full round trip on anything the JSON
                           contract does not cover. A 419 from an expired page
                           would otherwise leave the buyer with a button that
                           appears broken; reloading re-establishes the form and
                           its token, which is what actually fixes it. */
                        if (payload.participants === undefined) {
                            window.location.reload();
                            return;
                        }
                    }

                    showError(payload.ok ? null : message);

                    if (payload.ok) {
                        applySeats(payload.seats);
                        applyTotal(payload);

                        if (participants && payload.participants !== undefined) {
                            participants.innerHTML = payload.participants;
                        }

                        say(message);
                    }
                }).catch(function () {
                    /* Offline, DNS failure, the JSON contract broken. Anything
                       guessed here could disagree with the server's price, so the
                       honest response is to reload and let the form do the work
                       it was always able to do. */
                    window.location.reload();
                }).then(function () {
                    busy = false;

                    each('[data-seats-btn]', function (other) {
                        other.classList.remove('is-busy');
                    });
                });
            });
        });
    });
