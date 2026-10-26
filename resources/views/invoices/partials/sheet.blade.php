{{--
    Facture Acquittée / Facture Provisoire / Paid Invoice / فاتورة مسددة
--}}
@php
    $locale = $payload['invoiceLocale'] ?? 'fr';
    $edition = $payload['edition'] ?? null;
    $organiser = $edition?->organiser ?? 'ARABCIA';
    $currency = $payload['currency'] ?? 'MAD';
    
    $fmt = fn ($value) => \App\Support\Money::format($value, $currency, $locale);

    $reference = $payload['reference'] ?? '—';
    $issuedOn = $payload['issuedOn'] ?? '—';
    $participantName = $payload['participant_name'] ?? '—';
    $paymentDate = $payload['payment_date'] ?? null;
    $transactionRef = $payload['transaction_reference'] ?? '—';
    
    $dir = $locale === 'ar' ? 'rtl' : 'ltr';
    $alignStart = $dir === 'rtl' ? 'right' : 'left';
    $alignEnd = $dir === 'rtl' ? 'left' : 'right';

    // Brand Colors
    $colorPrimary = '#2b1a78'; // Dark Purple for titles
    $colorAccent = '#5b36c4';  // Medium Purple for table headers
    $colorTotal = '#3a2394';   // Solid Purple for total bar
@endphp

<article class="sheet" role="document" dir="{{ $dir }}" style="font-family: sans-serif; color: #333;">

    {{-- HEADER --}}
    <header style="margin-bottom: 20px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; vertical-align: top; text-align: {{ $alignStart }};">
                    <h1 style="font-size: 26px; margin: 0 0 10px 0; color: {{ $colorPrimary }}; text-transform: uppercase;">
                        {{ $isProforma ? __('order.invoice.proforma_title', [], $locale) : __('order.invoice.paid_title', [], $locale) }}
                    </h1>
                    <p style="margin: 0; font-size: 14px;">
    <strong>{{ __('order.invoice.number', [], $locale) }} :</strong> 
    <span class="ltr">
        {{-- If it's a paid invoice, use the official number, otherwise use the order reference --}}
        {{ $isProforma ? $payload['reference'] : ($order->invoice_number ?? $payload['reference']) }}
    </span><br>
    <strong>{{ __('order.invoice.issue_date', [], $locale) }} :</strong> <span class="ltr">{{ $issuedOn }}</span>
</p>
                </td>
                <td style="width: 50%; text-align: {{ $alignEnd }}; vertical-align: top;">
                    <img src="{{ asset('assets/images/invoice/logo-iia-maroc.png') }}" alt="{{ $organiser }}" width="150">
                </td>
            </tr>
        </table>
    </header>

    <hr style="border: 1px solid #d6cdf2; margin-bottom: 20px;">

    {{-- ISSUER & RECIPIENT --}}
    <section style="margin-bottom: 20px;">
        <table style="width: 100%; border-collapse: collapse; font-size: 12px; line-height: 1.6;">
            <tr>
                <td style="width: 48%; vertical-align: top; padding: 10px; background-color: rgba(238, 234, 252, 0.5); border-radius: 8px;">
                    <strong style="font-size: 13px; color: {{ $colorPrimary }};">{{ __('order.invoice.issuer', [], $locale) }}</strong><br>
                    <strong>IIA Maroc</strong><br>
                    @if($locale === 'ar')
                        شارع سيدي محمد بن عبد الله<br>
                        إقامة بيل فيو، عمارة B<br>
                        الطابق الثاني، المكتب 1 — الدار البيضاء<br>
                    @elseif($locale === 'en')
                        Bd Sidi Mohamed Ben Abdellah<br>
                        Residence Belle vue, Imm. B Etage 2, Bureau 1 - Casablanca<br>
                    @else
                        Bd Sidi Mohamed Ben Abdellah<br>
                        Résidence Belle vue, Imm. B Étage 2, Bureau 1 — Casablanca<br>
                    @endif
                    <span class="ltr">+212 6 13 32 32 93</span><br>
                    <span class="ltr">info@iiamaroc.org</span>
                </td>
                <td style="width: 4%;"></td>
                <td style="width: 48%; vertical-align: top; padding: 10px; background-color: rgba(238, 234, 252, 0.5); border-radius: 8px;">
                    <strong style="font-size: 13px; color: {{ $colorPrimary }};">{{ __('order.invoice.bill_to', [], $locale) }}</strong><br>
                    @foreach ($payload['billedTo'] ?? [] as $line)
                        <span class="{{ $loop->first ? '' : 'ltr' }}">{{ $line }}</span><br>
                    @endforeach
                </td>
            </tr>
        </table>
    </section>

    {{-- CONFERENCE DETAILS --}}
    <section style="margin-bottom: 20px; font-size: 13px; text-align: {{ $alignStart }};">
        <strong style="color: {{ $colorPrimary }};">{{ __('order.invoice.conference_title', [], $locale) }}</strong><br>
        {{ __('order.invoice.conference_venue', [], $locale) }}<br>
        <strong>{{ __('order.invoice.participant', [], $locale) }} :</strong> {{ $participantName }}
    </section>

    {{-- ITEMS TABLE --}}
    <section style="margin-bottom: 20px;">
        <strong style="font-size: 13px; color: {{ $colorPrimary }};">{{ __('order.invoice.details', [], $locale) }}</strong>
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px;">
            <thead>
                <tr style="background-color: {{ $colorAccent }}; color: #fff; text-align: {{ $alignStart }};">
                    <th style="padding: 8px; border: 1px solid {{ $colorAccent }};">{{ __('order.invoice.description', [], $locale) }}</th>
                    <th style="padding: 8px; border: 1px solid {{ $colorAccent }}; text-align: center;">{{ __('order.quantity', [], $locale) }}</th>
                    <th style="padding: 8px; border: 1px solid {{ $colorAccent }}; text-align: {{ $alignEnd }};">{{ __('order.invoice.unit_price', [], $locale) }}</th>
                    <th style="padding: 8px; border: 1px solid {{ $colorAccent }}; text-align: {{ $alignEnd }};">{{ __('order.amount', [], $locale) }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payload['lines'] ?? [] as $line)
                    <tr style="background-color: #fbfaff;">
                        <td style="padding: 8px; border: 1px solid #d6cdf2;">{{ $line['label'] }}</td>
                        <td style="padding: 8px; border: 1px solid #d6cdf2; text-align: center;">{{ $line['quantity'] }}</td>
                        <td style="padding: 8px; border: 1px solid #d6cdf2; text-align: {{ $alignEnd }};">
                            {{ $line['unit_price'] ?? $line['total'] }} 
                        </td>
                        <td style="padding: 8px; border: 1px solid #d6cdf2; text-align: {{ $alignEnd }};">{{ $line['total'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    {{-- TOTAL BAR --}}
    <section style="text-align: {{ $alignStart }}; margin-bottom: 20px;">
        <div style="background-color: {{ $colorTotal }}; color: #fff; padding: 12px 15px; border-radius: 6px; display: inline-block; font-size: 16px; font-weight: bold; width: 100%; box-sizing: border-box;">
            {{ $isProforma ? __('order.invoice.total_estimate', [], $locale) : __('order.invoice.total_paid', [], $locale) }} : {{ $fmt($payload['total'] ?? 0) }}
        </div>
    </section>

    {{-- HELP & PAYMENT INFO --}}
    <section style="font-size: 11px; line-height: 1.6; margin-bottom: 20px; text-align: {{ $alignStart }};">
        <strong style="color: {{ $colorPrimary }}; font-size: 13px;">{{ __('order.invoice.payment_method', [], $locale) }}</strong><br>
        {{ __('order.invoice.payment_cmi', [], $locale) }}<br><br>

        <div style="background-color: #fdf3ee; border: 1px solid #e39374; border-radius: 6px; padding: 10px;">
            <strong style="color: #8a4622;">{{ __('order.invoice.questions_title', [], $locale) }}</strong><br>
            <span style="color: #8a4622;">{{ __('order.invoice.questions_text', [], $locale) }}</span><br>
            <span class="ltr" style="color: #8a4622; text-decoration: underline;">ARABCIA: info@arabcia.org | IIA Maroc: info@iiamaroc.org</span><br>
            <span style="color: #8a4622;">{{ __('order.invoice.questions_note', [], $locale) }}</span>
        </div>
    </section>

    {{-- PAYMENT DETAILS & BANNER --}}
    @if (!$isProforma)
        <section style="font-size: 11px; margin-bottom: 20px; text-align: {{ $alignStart }};">
            <strong style="color: {{ $colorPrimary }}; font-size: 13px;">{{ __('order.invoice.payment_details', [], $locale) }}</strong><br>
            {{ __('order.invoice.payment_date', [], $locale) }} : <span class="ltr">{{ $paymentDate ?? $issuedOn }}</span> — {{ __('order.invoice.transaction_ref', [], $locale) }} : <span class="ltr">{{ $transactionRef }}</span>
        </section>

        <div style="border: 2px solid {{ $colorPrimary }}; padding: 10px; text-align: center; font-weight: bold; margin-bottom: 15px; color: {{ $colorPrimary }}; border-radius: 6px;">
            {{ __('order.invoice.payment_received', [], $locale) }} : 0 {{ $currency }}
        </div>
        <p style="font-size: 11px; text-align: center; color: #6f6a96;">
            {{ __('order.invoice.confirmation_text', [], $locale) }}
        </p>
    @endif

    {{-- FOOTER --}}
    <footer style="margin-top: 40px; padding-top: 10px; font-size: 10px; text-align: center; color: #6f6a96;">
        {{ __('order.invoice.footer_text', [], $locale) }}
    </footer>

</article>