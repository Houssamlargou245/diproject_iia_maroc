{{--
    Session messages and the validation error summary.

    Kept at the top of <main> rather than inside each page, so a message survives
    whatever page the redirect lands on. The error block repeats the field-level
    messages, which are rendered next to their inputs further down: a summary
    alone is useless to someone using a screen reader, who cannot tell from
    "the form has errors" which field to go to.

    `tabindex="-1"` on the summary plus the autofocus in the script below is what
    moves the reading position to it. Without that, a screen reader announces
    success or failure only if the visitor happens to scroll back up.
--}}

@if (session('session_expired'))
    {{-- The session that held the form went away — usually it expired while
         the page sat open. The form itself is back and usable, so this is a
         notice, not an error: there is nothing to fix, only to resend. --}}
    <div class="container">
        <div class="alert alert-warning mt-4" role="alert">
            @lang('auth.session_expired')
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="container">
        <div class="alert alert-danger mt-4" role="alert" tabindex="-1" id="error-summary">
            <p class="mb-2 fw-bold">@lang('misc.errors_title', ['count' => $errors->count()])</p>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var summary = document.getElementById('error-summary');
            if (summary) { summary.focus(); }
        });
    </script>
@endif
