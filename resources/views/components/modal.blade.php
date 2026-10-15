{{--
    A generic modal dialog.

    The sponsorship form introduced this shell (an overlay that blurs the page,
    a rounded panel that pops in, a close button that turns under the cursor).
    It lived inside that one page; this component lifts it out so any page can
    open a dialog with the same weight, the same motion and the same escape
    affordances.

    It is a plain shell, deliberately: it renders the frame and a slot, and
    knows nothing about what sits inside. Opening and closing are wired by the
    `modals` block in design.js through `[data-d-modal-open="<id>"]` triggers
    and `[data-d-modal-close]` anywhere inside — so a link, a button or the
    overlay itself all close it without this file knowing they exist.

    Props:
      id     — the id a trigger names; also the dialog's anchor
      label  — accessible name, announced when the dialog opens
      open   — render visible on load (used when the server wants the dialog up)

    Written by hand rather than via a `$attributes->class()` merge so `id`
    stays where a hash link can reach it, whatever else a caller passes.
--}}
@props([
    'id',
    'label' => null,
    'open' => false,
])

<div class="d-modal" id="{{ $id }}" data-d-modal @if (! $open) hidden @endif>
    <div class="d-modal__overlay" data-d-modal-close></div>

    <div class="d-modal__dialog"
         role="dialog"
         aria-modal="true"
         @if ($label) aria-label="{{ $label }}" @endif
         tabindex="-1">
        <button type="button" class="d-modal__close" data-d-modal-close aria-label="@lang('action.close')">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>

        <div class="d-modal__scroll">
            {{ $slot }}
        </div>
    </div>
</div>
