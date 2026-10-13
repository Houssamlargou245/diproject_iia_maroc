{{--
    "Rejoignez-nous !" — the invitation on one side, the four headline figures
    on the other.

    The figures are counted, never typed: HomeController derives the workshop
    and lab counts from the published programme and the delegate count from
    config('conference.capacity'), so this band cannot contradict the
    programme an attendee is about to read, or the quota the checkout enforces.

    `data-count-to` is what the count-up reads. The final value is also the
    element's own text, so with scripting off the number is simply already
    there — no JavaScript is needed to read the page.

    The four cards are one `repeat()` of the same figure/label shape rather
    than three hand-written blocks plus a fourth: a count that changes shape
    with the edition should not need the markup rewritten with it.
--}}
@props(['stats', 'edition'])

@php
    $figures = [
        ['value' => $stats['workshops'], 'label' => __('home.stats.workshops')],
        ['value' => $stats['labs'], 'label' => __('home.stats.labs')],
        ['value' => $stats['days'], 'label' => __('home.stats.days')],
    ];
@endphp

<section class="h-join" aria-labelledby="join-title">
    <div class="container">
        <div class="h-join__grid">

            <h2 id="join-title" class="h-join__title">@lang('home.join_us')</h2>

            <ul class="h-figures">
                @foreach ($figures as $figure)
                    <li class="h-figure">
                        <span class="h-figure__value"
                              data-count-to="{{ $figure['value'] }}">{{ number_format($figure['value']) }}</span>
                        <span class="h-figure__label">{{ $figure['label'] }}</span>
                    </li>
                @endforeach
            </ul>

        </div>
    </div>
</section>