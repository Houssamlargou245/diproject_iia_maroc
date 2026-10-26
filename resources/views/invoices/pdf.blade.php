<!DOCTYPE html>
<html lang="{{ $payload['invoiceLocale'] }}" dir="{{ $payload['invoiceLocale'] === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $heading }}</title>
    <style>
        @page { margin: 0; size: A4 portrait; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }
        .page-container {
            padding: 15mm 15mm;
            position: relative;
            z-index: 10;
        }
    </style>
</head>
<body>
    @php
        // Look for the background artwork in the public folder
        $art = null;
        foreach (['invoice-bg-print.png', 'invoice-bg-print.jpg', 'invoice-bg.png'] as $candidate) {
            $path = public_path('assets/images/invoice/'.$candidate);
            if (is_file($path)) { $art = $path; break; }
        }
    @endphp

    @if ($art)
        {{-- The absolute positioned background image --}}
        <img src="{{ $art }}" style="position: absolute; left: 0; top: 0; width: 210mm; height: 297mm; z-index: -1;" alt="">
    @endif

    <div class="page-container">
        @include('invoices.partials.sheet')
    </div>
</body>
</html>