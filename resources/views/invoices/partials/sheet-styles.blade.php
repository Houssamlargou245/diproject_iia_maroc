{{--
    Shared invoice-sheet styles, scoped under `.invoice-doc`.

    Used by the standalone print pages (pages/cart-print.blade.php) and by the
    in-app order page (pages/orders/show.blade.php) so the on-screen sheet and
    the printed output stay byte-for-byte the same design. Every selector is
    scoped under `.invoice-doc` (and its CSS variables are namespaced) so the
    sheet can be embedded inside the site layout without leaking styles.

    Layout:
        <div class="invoice-doc invoice-doc--page|--embedded">
            <div class="toolbar">...screen-only actions...</main>
            <main class="sheet"> ... </main>
        </div>
--}}
<style>
     @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap');
    .invoice-doc {
        --invoice-deep: #2b1a78;
        --invoice-violet: #5b36c4;
        --invoice-violet-2: #7b4fd8;
        --invoice-ink: #1f1a4d;
        --invoice-muted: #6f6a96;
        --invoice-card: rgb(238 234 252 / 72%);
        --invoice-line: rgb(91 54 196 / 14%);
        --invoice-grad: linear-gradient(135deg, #3a2394 0%, #5b36c4 55%, #7b4fd8 100%);
        color: var(--invoice-ink);
        font-family: 'Aptos', 'Cairo', 'AvenirArabic', system-ui, sans-serif;
        font-size: 13px;
        line-height: 1.5;
    }

    .invoice-doc, .invoice-doc * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    .invoice-doc--embedded { background: #f3f1fa; padding: calc(var(--header-h, 84px) + 2.5rem) 1rem 4rem; }
    .invoice-doc--page { background: #eceaf6; padding: 1.5rem 1rem 3rem; }

    .invoice-doc .ltr { direction: ltr; unicode-bidi: embed; text-align: left; }

    /* ---- The A4 sheet on the violet artwork ---- */
    .invoice-doc .sheet {
        background: url('{{ asset('assets/images/invoice/invoice-bg.png') }}') center / 100% 100% no-repeat;
        border-radius: 6px;
        box-shadow: 0 18px 48px rgb(43 26 120 / 22%);
        margin: 0 auto;
        min-height: 297mm;
        overflow: hidden;
        padding: 13mm 11mm 48mm;
        position: relative;
        width: 210mm;
    }

    /* ---- Brand + title ---- */
    .invoice-doc .brand { align-items: center; display: flex; gap: 10px; }
    .invoice-doc .brand__mark {
        background: var(--invoice-grad); border-radius: 4px; color: #fff;
        display: grid; font-size: 22px; font-weight: 800; height: 44px; place-items: center; width: 44px;
    }
    .invoice-doc .brand__name { color: var(--invoice-deep); font-size: 34px; font-weight: 900; letter-spacing: .5px; line-height: 1; }
    .invoice-doc .brand__name span { color: var(--invoice-violet-2); }
    .invoice-doc .brand__sub { color: var(--invoice-deep); font-size: 9px; letter-spacing: 5px; margin-top: 4px; text-transform: uppercase; }

    .invoice-doc .top { align-items: stretch; display: flex; gap: 14px; justify-content: space-between; margin-top: 50px; }
    .invoice-doc .top__title h1 {
        color: var(--invoice-deep); font-size: 40px; font-weight: 900; letter-spacing: -1px;
        line-height: 1; margin: 0; text-transform: uppercase;
    }
    .invoice-doc .top__ref { align-items: center; display: flex; gap: 12px; margin-top: 10px; }
    .invoice-doc .top__ref::after { background: var(--invoice-grad); border-radius: 3px; content: ''; height: 3px; width: 48px; }
    .invoice-doc .top__ref b { font-size: 17px; font-weight: 500; }

    .invoice-doc .pill {
        background: var(--invoice-grad); border-radius: 999px; box-shadow: 0 6px 14px rgb(91 54 196 / 30%);
        color: #fff; display: inline-block; font-size: 11px; font-weight: 800;
        letter-spacing: .5px; margin-top: 10px; padding: 7px 16px; text-transform: uppercase;
    }

    .invoice-doc .top__meta { border-inline-start: 2px solid var(--invoice-deep); display: grid; gap: 12px; padding-inline-start: 16px; }
    .invoice-doc .meta-item { align-items: center; display: flex; gap: 10px; }
    .invoice-doc .chip {
        background: rgb(255 255 255 / 80%); border: 1px solid var(--invoice-line); border-radius: 10px;
        color: var(--invoice-violet); display: grid; flex: none; height: 38px; place-items: center; width: 38px;
    }
    .invoice-doc .chip svg { height: 20px; width: 20px; }
    .invoice-doc .meta-item b { display: block; font-size: 12px; }
    .invoice-doc .meta-item span { color: var(--invoice-ink); font-size: 13px; }

    /* ---- Glass cards ---- */
    .invoice-doc .card {
        background: var(--invoice-card); border: 1px solid var(--invoice-line); border-radius: 12px;
        display: block; flex-direction: initial; padding: 12px 14px; break-inside: avoid;
    }
    .invoice-doc .card--white { background: rgb(255 255 255 / 88%); }
    .invoice-doc .card__head { align-items: center; color: var(--invoice-deep); display: flex; font-size: 14px; font-weight: 800; gap: 10px; margin-bottom: 8px; }
    .invoice-doc .card__ico {
        background: var(--invoice-grad); border-radius: 9px; color: #fff;
        display: grid; flex: none; height: 32px; place-items: center; width: 32px;
    }
    .invoice-doc .card__ico svg { height: 18px; width: 18px; }

    .invoice-doc .duo { display: grid; gap: 12px; grid-template-columns: 1fr 1fr; margin-top: 18px; }
    .invoice-doc .card p { margin: 0 0 2px; }
    .invoice-doc .card .k { color: var(--invoice-muted); font-size: 12px; }
    .invoice-doc .card .strong { font-weight: 800; }
    .invoice-doc .issuer { align-items: center; display: flex; gap: 10px; justify-content: space-between; }
    .invoice-doc .issuer img { border-radius: 6px; height: 26px; padding: 2px 4px; }

    /* ---- Items ---- */
    .invoice-doc .items-card { margin-top: 14px; }
    .invoice-doc table { border-collapse: collapse; width: 100%; }
    .invoice-doc .items { border-radius: 10px; overflow: hidden; }
    .invoice-doc .items th {
        background: var(--invoice-grad); color: #fff; font-size: 12px; font-weight: 700;
        padding: 9px 12px; text-align: start;
    }
    .invoice-doc .items td { background: rgb(255 255 255 / 70%); border-bottom: 1px solid var(--invoice-line); padding: 11px 12px; }
    .invoice-doc .items tr:last-child td { border-bottom: 0; }
    .invoice-doc .items .num { text-align: end; white-space: nowrap; }
    .invoice-doc .items td.num:last-child { font-weight: 800; }

    /* ---- Totals + notes ---- */
    .invoice-doc .bottom { align-items: start; display: grid; gap: 12px; grid-template-columns: 1fr 1fr; margin-top: 14px; }
    .invoice-doc .totals { background: var(--invoice-card); border: 1px solid var(--invoice-line); border-radius: 12px; padding: 12px; break-inside: avoid; }
    .invoice-doc .totals table th, .invoice-doc .totals table td { font-weight: 800; padding: 6px 8px; }
    .invoice-doc .totals table th { text-align: start; }
    .invoice-doc .totals table td { text-align: end; }
    .invoice-doc .totals tr + tr th, .invoice-doc .totals tr + tr td { border-top: 1px solid var(--invoice-line); }
    .invoice-doc .grand {
        align-items: center; background: var(--invoice-grad); border-radius: 12px; color: #fff;
        display: flex; font-size: 16px; font-weight: 800; justify-content: space-between;
        margin-top: 8px; padding: 11px 16px;
    }

    .invoice-doc .notes { color: var(--invoice-muted); font-size: 11.5px; }
    .invoice-doc .banner { border-inline-start: 4px solid var(--invoice-violet-2); margin-top: 14px; }
    .invoice-doc .banner strong { color: var(--invoice-deep); display: block; }

    /* ---- Footer band (sits on the artwork's violet band) ---- */
    .invoice-doc .foot {
        align-items: center; bottom: 0; color: #fff; display: flex; font-size: 11px;
        gap: 18px; height: 38mm; inset-inline: 0; justify-content: space-between;
        padding: 0 11mm; position: absolute;
    }
    .invoice-doc .foot__info { align-items: center; display: flex; gap: 12px; }
    .invoice-doc .foot__info svg { flex: none; height: 20px; width: 20px; }
    .invoice-doc .foot__tag {
        border-inline-start: 1px solid rgb(255 255 255 / 45%); font-size: 15px; font-style: italic;
        font-weight: 600; line-height: 1.3; max-width: 42mm; padding-inline-start: 16px;
    }

    /* ---- Screen-only toolbar ---- */
    .invoice-doc .toolbar { display: flex; flex-wrap: wrap; gap: .75rem; justify-content: center; margin: 0 auto 1.25rem; }
    .invoice-doc .btn {
        background: var(--invoice-grad); border: 0; border-radius: 999px; color: #fff; cursor: pointer;
        display: inline-block; font: inherit; font-size: 14px; font-weight: 700; line-height: 1.2;
        padding: .65rem 1.4rem; text-align: center; text-decoration: none;
    }
    .invoice-doc .btn--ghost { background: #fff; border: 1px solid var(--invoice-line); color: var(--invoice-deep); }

    @media (max-width: 800px) {
        .invoice-doc--embedded, .invoice-doc--page { padding: 1rem; }
        .invoice-doc .sheet { border-radius: 0; transform-origin: top; width: 100%; padding: 1.25rem 1rem 12rem; }
        .invoice-doc .top, .invoice-doc .duo, .invoice-doc .bottom { grid-template-columns: 1fr; flex-direction: column; }
        .invoice-doc .top__meta { border: 0; padding: 0; }
        .invoice-doc .foot { flex-direction: column; height: auto; padding: 1rem; position: static; background: var(--invoice-grad); margin: 1rem -1rem -12rem; }
    }

    /* @media print {
        @page { margin: 0; size: A4; }
        .invoice-doc--embedded, .invoice-doc--page { background: #fff; padding: 0; }
        .invoice-doc .container { max-width: none !important; padding: 0 !important; width: auto !important; }
        .invoice-doc .toolbar { display: none; }
        .invoice-doc .sheet { border-radius: 0; box-shadow: none; height: 297mm; min-height: 0; width: 210mm; }
        .invoice-doc tr { break-inside: avoid; }
    } */

         @media print {
        @page { margin: 0; size: A4; }
        body, html {
            background: #fff !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .invoice-doc--embedded, .invoice-doc--page { background: #fff !important; padding: 0 !important; }
        .invoice-doc .toolbar, .ux-notice, header, footer { display: none !important; }
        .invoice-doc .sheet { border-radius: 0; box-shadow: none; height: 297mm; min-height: 0; width: 210mm; margin: 0 auto; }
        .invoice-doc tr { break-inside: avoid; }
    }
</style>