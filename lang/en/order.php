<?php

declare(strict_types=1);

/**
 * Order lifecycle labels. See lang/fr/order.php for the rationale; the statuses
 * are shared but the surrounding column labels are not, so a French string is
 * not simply reused here.
 */
return [
    'status.awaiting_payment' => 'Awaiting payment',
    'status.cancelled' => 'Cancelled',
    'status.expired' => 'Expired',
    'status.failed' => 'Payment failed',
    'status.paid' => 'Paid',
    'status.partially_refunded' => 'Partially refunded',
    'status.pending' => 'Pending',
    'status.refunded' => 'Refunded',

    'reference' => 'Reference',
    'placed_on' => 'Placed on',
    'address' => 'Address',
    'amount' => 'Amount',
    'no_orders' => 'No registrations yet.',
    'pay_now' => 'Pay now',
    'download_invoice' => 'Download invoice',
    'phone_international' => 'International format, for example +212 6 12 34 56 78 or +966 5 12 34 56 78.',

    // --- Checkout ---------------------------------------------------------
    'title' => 'Conference registration',
    'ticket' => 'Ticket',
    'quantity' => 'Quantity',
    'member_places' => 'Member places',
    'member_places_exceed' => 'Member places cannot exceed the total number of places.',
    'participants' => 'Participants',
    'participant_name' => 'Full name',
    'participant_number' => 'Participant :number',
    'participant_help' => 'One entry per reserved place. The member rate applied is the one your active membership entitles you to.',
    'seats_help' => 'Add or remove a place before filling in the entries. The total is recalculated on every change.',
    'seats_for' => 'Places for :ticket',
    'add_participant' => 'Add a participant',
    'remove_participant' => 'Remove the last participant',
    'participant_added' => 'Participant added.',
    'participant_removed' => 'Participant removed.',
    'participant_count' => ':count participants',
    'participant_count_one' => '1 participant',
    'min_one_place' => 'At least one place must remain on each tariff.',
    'max_places_reached' => 'You cannot reserve more than :max places on the same tariff.',
    'cart_line_missing' => 'That tariff is no longer in your basket.',
    'items' => 'Order details',
    'total' => 'Total',
    'member_saving_applied' => 'The member rate has been applied to :count place(s).',
    'participant_count_mismatch' => 'You reserved :expected places, but :count participant forms were submitted.',
    'participant_mismatch' => 'The number of participants (:received) does not match the number of places reserved (:expected).',
    'billing_details' => 'Billing details',
    'place_order' => 'Confirm and pay',
    'email_receipt' => 'A confirmation e-mail will be sent to you.',
    'summary' => 'Summary',
    'status' => 'Status',

    // --- Invoice ---------------------------------------------------------
    // An order paid for before its seats were named: the attendee list is
    // collected by the organiser afterwards, so an empty one is expected
    // rather than a fault, and the page has to say so instead of showing
    // nothing under a heading.
    'participants_pending' => 'Participants will be registered before the conference. To add them, please contact us.',

    // Closing band on the invoice: the purchase is finished, and what the
    // delegate wants next is the thing the ticket is for.
    'cta_lede' => 'Your registration is recorded. Find the conference programme and practical information about the venue.',

    // Printed from the browser. The PDF download is the document to keep; this
    // is for the delegate who needs a copy today and would rather not open a
    // reader.
    'print' => 'Print',
    'confirm_and_pay' => 'Confirm the order',
    'print_hint' => 'Print this page to PDF from your browser’s print dialog.',

    // Shown before payment. The order can be opened, printed and paid at this
    // point; what does not exist yet is the *facture*, because issuing a
    // numbered invoice for money that has not arrived would put a document in
    // circulation the bank will not honour. The delegate can still print this
    // page as a receipt, so the wording promises exactly that.
    'unpaid_notice' => 'This order has not been paid yet. You can print it as a receipt and pay it now; the numbered invoice becomes available once payment has gone through.',

    // --- The invoice document ----------------------------------------------
    // The heading, totals block and reassurance strip of the invoice page.
    // `invoice_heading` takes the reference as a :reference placeholder rather
    // than composing it in the view, so each language can place it in its own
    // word order — French and Arabic put it differently.
    'invoice_heading' => 'Invoice number :reference',
    'paid_to' => 'Paid to',
    'payment_method' => 'Payment method',
    'payment_method_cmi' => 'Moroccan and international cards (CMI)',
    'subtotal' => 'Subtotal',
    'member_saving' => 'Member discount',
    'email' => 'Email',
    'phone' => 'Phone',
    'contact_us' => 'Contact ARABCIA',
    'vat' => 'VAT',
    'total_incl' => 'Total incl. tax',
    'offer' => 'Offer name',
    'participant_name' => 'Participant name',
    'date' => 'Date',
    'price_incl' => 'Price incl. tax',
    'download_invoice_short' => 'Print / Download',
    'help_title' => 'Need a hand?',
    'help_text' => 'For any question about your order or the payment, contact the ARABCIA team.',
    'assurances' => [
        'secure' => 'Secure payment',
        'secure_text' => 'Your transactions are protected and secured by CMI.',
        'cards' => 'Moroccan and international cards',
        'cards_text' => 'Pay with Moroccan and international bank cards (CMI).',
        'instant' => 'Instant invoice',
        'instant_text' => 'Receive your invoice immediately after payment.',
        'support' => 'Support',
        'support_text' => 'Our team stays available should you need anything.',
    ],
    'sign_in_to_checkout' => 'Sign in to complete your registration.',
    'enroll_to_checkout' => 'Set up your authenticator app before completing your registration.',
    'not_payable' => 'This order can no longer be paid.',
    'capacity_reached' => 'The venue is full (:capacity places, :taken already taken). Please contact us to join the waiting list.',

    // --- Cart -------------------------------------------------------------
    'orders' => 'My orders',
    'cart_empty' => 'Your basket is empty.',
    'cart_updated' => 'Your basket has been updated.',
    'cart_emptied' => 'Your basket has been emptied.',

    'cart' => [
        'add' => 'Add to basket',
        'title' => 'My basket',
        'subtitle' => 'Review the places you wish to reserve before continuing.',
        'estimate' => 'Estimated total',
        'estimate_note' => 'This figure is indicative. The final total, which takes your membership status into account, is calculated at the next step.',
        'summary_note' => 'Participants are named at the next step.',
        'continue' => 'Continue registering',
        'clear' => 'Empty the basket',
        'checkout' => 'Proceed to checkout',
        'sign_in_note' => 'You will be asked to sign in before payment. Your basket will be kept.',

        // Entry point to the signed-in buyer's full list of orders/payments,
        // shown on the basket.
        'my_payments' => 'My payments',
        'my_payments_note' => 'Review your orders and the status of your payments.',

        // The printable, downloadable summary of the basket.
        //
        // The basket is precisely where this document earns its place: it is
        // before placing an order that a company delegate still has to get
        // approval from their procurement office. Hence "summary" and not
        // "invoice"; see order.invoice.proforma_* for the same reasoning on the
        // order side.

        'places' => ':count place',
        'places_plural' => ':count places',

        'member_places' => ':count member place',
        'member_places_plural' => ':count member places',

        'invoice' => 'Order summary',
        'invoice_lede' => 'Download or print a summary of your basket for your procurement or finance department.',
        'invoice_download' => 'Download the PDF',
        'invoice_print' => 'Print the summary',
        'invoice_hint' => 'A provisional, unnumbered document: this is not an invoice.',
    ],

    // --- Payment ----------------------------------------------------------
    'payment' => [
        'confirmed' => 'Payment confirmed. Your invoice is available in your orders.',
        'failed' => 'The payment did not go through.',
        'generic_failure' => 'The payment was declined.',
        'pending' => 'Payment awaiting confirmation from the bank.',
        'pending_note' => 'If you have paid, your registration will be confirmed within moments. This page updates itself.',
        'cancelled' => 'Payment cancelled.',
        'cancelled_note' => 'Your order has not been charged. You can resume the payment at any time.',
        'retry' => 'Resume payment',
        'secure' => 'Secure payment via CMI.',
        'last_attempt' => 'Latest payment attempt',
        'test_title' => 'Payment gateway test page',
        'test_banner' => 'Test environment — no real payment will be taken.',
        'test_intro' => 'This page rehearses the complete payment flow without a card.',
        'test_approve' => 'Simulate an approved payment',
        'test_decline' => 'Simulate a declined payment',
        'test_note' => 'Both buttons post a signed payload to the real callback endpoint, so the production code path is genuinely exercised.',
    ],

    'invoice' => [
        'not_available' => 'This order is closed: no invoice can be issued for it.',
        'title' => 'Invoice no. :number',
        'billed_to' => 'Billed to',
        'details' => 'Details',
        'issued_on' => 'Issue date',
        'method' => 'Payment method',
        'cmi' => 'Moroccan and international cards (CMI)',
        'subtotal' => 'Subtotal',
        'discount' => 'Discount',
        'tax' => 'Tax',
        'rate' => 'Rate',
        'footer' => 'Generated by :organiser. Any correction must be requested from the organisers.',

        // --- The proforma --------------------------------------------------
        // The wording the document carries while the order is unpaid. A company
        // delegate needs a paper *before* paying — a procurement office will not
        // release funds against a screenshot of a basket — but that paper cannot
        // be a numbered invoice: an invoice number is an accounting record, and
        // issuing one for money that has not arrived puts a document the bank
        // will not honour into circulation. Hence the "proforma" heading, the
        // absence of a number, and the warning that this is not proof of
        // payment.

        'proforma_title' => 'Proforma invoice',
        'proforma_banner_title' => 'Provisional document — order not paid',
        'proforma_banner_text' => 'This is not an invoice. It summarises your order for information; the numbered, definitive invoice is issued only once payment has been received.',
        'proforma_notes_title' => 'Please note',
        'proforma_note_estimate' => 'The figures are indicative. The member rate is applied only to participants matched against an active membership record at the moment the order is placed.',
        'proforma_note_issue' => 'No invoice number has been assigned to this document, which does not stand in for an invoice.',
        'billed_to_pending' => 'To be completed at checkout.',
    ],

    // --- Mail -------------------------------------------------------------
    'mail' => [
        'paid_subject' => 'ARABCIA 2026 — Registration confirmed (:reference)',
        'failed_subject' => 'ARABCIA 2026 — Payment unsuccessful (:reference)',
        'paid_intro' => "Hello,\n\nYour registration for the ARABCIA 2026 conference is confirmed. We have recorded your payment.",
        'failed_intro' => "Hello,\n\nThe payment for your ARABCIA 2026 conference registration did not go through.",
        'invoice_attached' => 'The invoice is attached to this message.',
        'invoice_online' => 'You can download your invoice from your "My orders" area.',
        'failed_action' => 'You can resume payment at any time from your "My orders" area.',
        'footer' => 'Kind regards, the ARABCIA 2026 organisation.',
    ],

    'cancel' => [
        'done' => 'Your order has been cancelled.',
        'already' => 'This order is already closed.',
        'paid' => 'This order is paid and cannot be cancelled. Please contact the organisers for a refund.',
        'action' => 'Cancel the order',
        'confirm' => 'Confirm cancellation of this order?',
    ],
];
