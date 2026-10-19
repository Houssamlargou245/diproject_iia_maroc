<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Administration
|--------------------------------------------------------------------------
|
| Mirrors lang/fr/admin.php key for key. The shared `admin.*` namespace means
| `php artisan lang:missing` finds a translation that exists in one file and not
| in the others; the nested groups mirror the rail so `admin.enquiries.status`
| cannot read as a typo for `admin.submissions.status`.
*/

return [
    'title' => 'Administration',
    'skip_to_content' => 'Skip to content',
    'forbidden' => 'This page is for the organising team only.',

    'no_edition' => [
        'title' => 'No edition configured',
        'text' => 'Create an edition before registrations, the programme and submissions can be managed.',
    ],

    // --- The rail ----------------------------------------------------------
    // Accessible name for the rail. It has to be its own key: 'admin.nav'
    // is a group of labels, and using it as a scalar echoes an array
    // into aria-label, which a screen reader then reads as nothing.
    'nav_label' => 'Administration navigation',

    'nav' => [
        'dashboard' => 'Dashboard',
        'orders' => 'Registrations',
        'participants' => 'Attendees',
        'submissions' => 'Submissions',
        'enquiries' => 'Sponsorship',
        'messages' => 'Messages',
        'public_site' => 'View the site',
        'pending' => ':count pending',
    ],

    // --- The decision queues ----------------------------------------------
    'queue' => [
        'submissions' => 'talk submissions awaiting a decision',
        'enquiries' => 'sponsorship enquiries awaiting a decision',
        'messages' => 'unanswered messages',
    ],

    // --- Headline figures --------------------------------------------------
    'stats' => [
        'orders' => 'Registrations',
        'paid_orders' => 'of which paid',
        'participants' => 'Attendees',
        'checked_in' => 'checked in at the desk',
        'paid_rate' => 'Paid rate',
        'paid_rate_hint' => ':paid of :total registrations',
        'average_order' => 'Average order',
    ],

    'revenue' => 'Takings',

    // --- Registrations over time -------------------------------------------
    'trend' => [
        'title' => 'Registrations over time',
        'legend_orders' => 'Registrations',
        'legend_paid' => 'Paid',
        'total' => ':count registrations in total',
        'empty' => 'No registrations yet.',
    ],

    // --- Member / non-member split -----------------------------------------
    'members' => [
        'title' => 'Members and non-members',
        'member' => 'Members',
        'standard' => 'Non-members',
        'summary' => ':member members · :standard non-members',
        'none' => 'No places sold yet.',
    ],

    // --- Capacity per tariff -----------------------------------------------
    'capacity' => [
        'title' => 'Capacity by tariff',
        'sold' => ':sold sold',
        'sold_of' => ':sold / :max places',
        'remaining' => ':count places left',
        'full' => 'Full',
        'unlimited' => 'Unlimited',
        'empty' => 'No tariff defined for this edition.',
    ],

    // --- Where buyers come from --------------------------------------------
    'countries' => [
        'title' => 'Where buyers come from',
        'registrations' => 'Registrations',
        'empty' => 'No origin to show yet.',
    ],

    // --- Places sold per tariff --------------------------------------------
    'sales' => [
        'title' => 'Places sold by tariff',
        'tariff' => 'Tariff',
        'member' => 'Members',
        'standard' => 'Non-members',
        'total' => 'Total',
        'revenue' => 'Taken',
        'none' => 'No tariff defined for this edition.',
    ],

    'orders_by_status' => 'Registrations by status',

    'recent_orders' => [
        'title' => 'Latest registrations',
        'reference' => 'Reference',
        'buyer' => 'Buyer',
        'total' => 'Amount',
        'status' => 'Status',
        'placed_on' => 'Placed',
        'empty' => 'No registrations yet.',
        'view' => 'Open',
        'all' => 'All registrations',
    ],

    // --- Orders ------------------------------------------------------------
    'orders' => [
        'index' => [
            'title' => 'Registrations',
            'lede' => 'Registrations for the current edition. A status change is refused unless it is legal from the current state.',
            'filter' => 'Filter by status',
            'all' => 'All statuses',
            'empty' => 'No registration matches this filter.',
            'anonymous' => 'No account',
        ],
        'show' => [
            'title' => 'Order',
            'buyer' => 'Buyer',
            'contact' => 'Contact',
            'items' => 'Items',
            'participants' => 'Attendees',
            'payments' => 'Payments',
            'timeline' => 'History',
            'reference' => 'Reference',
            'placed_on' => 'Placed',
            'invoice' => 'Invoice number',
            'none' => 'No payment recorded.',
            'no_notes' => 'No notes.',
        ],
        'notes' => 'Note',
        'change_status' => 'Change status',
        'illegal_transition' => 'An order that is “:from” cannot move to “:to”.',
        'status_updated' => 'Order :reference is now “:status”.',
    ],

    // --- Speaker submissions -----------------------------------------------
    'submissions' => [
        'title' => 'Talk submissions',
        'lede' => 'A rejection has to be reasoned: the note is kept on the file and the speaker can read it.',
        'speaker' => 'Speaker',
        'track' => 'Requested track',
        'session' => 'Proposed title',
        'outline' => 'Outline',
        'submitted_on' => 'Received',
        'review_notes' => 'Review notes',
        'review' => 'Decide',
        'notes' => 'Notes',
        'status' => 'Decision',
        'notes_required' => 'A note is required to justify this decision.',
        'updated' => 'The submission has been updated.',
        'any_track' => 'No track requested',
        'empty' => 'No submission matches this filter.',
    ],

    // --- Sponsorship enquiries ---------------------------------------------
    'enquiries' => [
        'title' => 'Sponsorship enquiries',
        'lede' => 'An enquiry is not an order: there is nothing to take payment for, only a conversation to have.',
        'company' => 'Company',
        'contact' => 'Contact',
        'package' => 'Requested tier',
        'received' => 'Received',
        'handler' => 'Handled by',
        'unassigned' => 'Unassigned',
        'stage' => 'Stage',
        'notes' => 'Notes',
        'status' => 'Stage',
        'reason_required' => 'A note is required to close a lost enquiry.',
        'updated' => 'The enquiry from :company has been updated.',
        'no_package' => 'No tier',
        'empty' => 'No enquiry matches this filter.',
    ],

    // --- Contact messages ---------------------------------------------------
    'messages' => [
        'title' => 'Messages',
        'lede' => 'Nothing is emailed from this page: “mark answered” records the reply on the file, it does not send it.',
        'from' => 'Sender',
        'subject' => 'Subject',
        'body' => 'Message',
        'received' => 'Received',
        'handler' => 'Handled by',
        'unassigned' => 'Unassigned',
        'notes' => 'Internal notes',
        'status' => 'Status',
        'answer' => 'Mark as answered',
        'updated' => 'The message has been updated.',
        'wrote_in' => 'Wrote in',
        'empty' => 'No message matches this filter.',
    ],

    // --- Participants / the door list ---------------------------------------
    'participants' => [
        'title' => 'Attendees',
        'lede' => 'The list used at the desk. The search covers name, badge, email and phone.',
        'search' => 'Search',
        'placeholder' => 'Name, email or phone…',
        'search_hint' => 'At least two characters.',
        'name' => 'Name',
        'badge' => 'Badge',
        'job_title' => 'Job title',
        'status' => 'Arrival',
        'checked_in' => 'Checked in',
        'not_checked_in' => 'Not checked in',
        'check_in' => 'Check in',
        'check_out' => 'Undo check-in',
        'checked_in_done' => ':name was checked in.',
        'checked_out_done' => 'The check-in for :name was undone.',
        'empty' => 'No attendee matches.',
        'member' => 'Member',
        'total' => 'Attendees shown',
    ],

    // --- Status vocabulary for the three queues ----------------------------
    'stages' => [
        'submissions' => [
            'submitted' => 'Received',
            'under_review' => 'Under review',
            'accepted' => 'Accepted',
            'waitlisted' => 'Waitlisted',
            'rejected' => 'Rejected',
            'withdrawn' => 'Withdrawn',
        ],
        'enquiries' => [
            'new' => 'New',
            'contacted' => 'Contacted',
            'quoted' => 'Quote sent',
            'won' => 'Won',
            'lost' => 'Lost',
        ],
        'messages' => [
            'new' => 'New',
            'open' => 'In progress',
            'answered' => 'Answered',
            'spam' => 'Spam',
        ],
        'subjects' => [
            'registration' => 'Registration',
            'sponsoring' => 'Sponsorship',
            'speaker' => 'Talk',
            'press' => 'Press',
            'other' => 'Other',
        ],
        // Gateway vocabulary for reconciling against a bank statement. Not
        // shown on the public payment page, which speaks in outcomes
        // ("confirmed", "declined") rather than in settlement states.
        'payment_status' => [
            'pending' => 'Pending',
            'authorised' => 'Authorised',
            'captured' => 'Captured',
            'settled' => 'Settled',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            'disputed' => 'Disputed',
        ],
    ],
];
