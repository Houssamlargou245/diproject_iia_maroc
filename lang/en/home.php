<?php

declare(strict_types=1);

return [
    // ---------------------------------------------------------------------
    // Landing page
    // ---------------------------------------------------------------------
    //
    // Copy for the redesigned home page, band by band, in the order the bands
    // appear. It lives in one group rather than being spread across the
    // pricing / programme / speakers files because a band can quote from
    // several of those, and a phrase that only ever appears on the landing page
    // does not belong in a file that also serves the standalone pages.
    //
    'landing' => [

        'hero' => [
            'kicker' => 'INTERNATIONAL CONFERENCE',
            'logos_alt' => 'ARABCIA 2026 and its organisers',
            'cta_programme' => 'Programme',
            'cta_register' => 'REGISTER NOW',
            'cta_sponsor' => 'Become a sponsor',
        ],

        'why' => [
            'eyebrow' => 'Why this conference?',
            'title' => 'A meeting to get ahead of what is coming',
            'lede' => 'The :year ARABCIA annual conference, organised by ARABCIA and hosted in Morocco by IIA Maroc, will bring together in Rabat the professionals and institutions that contribute to the development of Internal Audit across the Arab world and beyond. This edition carries the theme: "Internal Audit: a trusted partner in the transformation and resilience of organisations".',
            'body' => 'Faced with the rise of artificial intelligence, cyber threats, and economic, technological and social upheaval, organisations must rethink their approaches to governance and risk management. In this context, Internal Audit is evolving to bring a forward-looking perspective, support transformation and contribute to value creation.<br><br>The conference will explore concrete responses to these new challenges and show how Internal Audit can strengthen the confidence, resilience and performance of organisations, while supporting their transformation.',
            'image_alt' => 'Delegates in conversation on the exhibition floor.',
            'inset_alt' => 'An ARABCIA plenary session in progress.',
            'inset_title' => 'ARABCIA :year',
            'inset_subtitle' => 'Internal audit, a trusted partner',
        ],

        'inscription' => [
            'title' => 'Registration',
            'ttc' => 'Standard rate',
            'fx' => 'Rate in foreign currency',
            'members' => 'Members',
            'non_members' => 'Non-members',
            'reserve' => 'Reserve your place in :currency',
        ],

        'programme' => [
            'title' => 'Conference programme',
            'download' => 'Download',
            'more' => 'See more',
            'days_label' => 'Programme days',
            'day' => 'Day :number',
            'expand' => 'Show the session detail',
            'collapse' => 'Hide the session detail',
        ],

        'speakers' => [
            'eyebrow' => 'Speakers',
            'keynote' => 'Keynote',
            'speaker' => 'Speaker',
            'previous' => 'Previous speakers',
            'next' => 'Next speakers',
        ],

    ],
    'partners_title' => 'SPONSORS',

    'video_fallback' => 'Your browser cannot play this video.',

    'video_play' => 'Play the film',

    'video_lede' => 'What the 2026 edition is about, in the words of the organisers themselves.',

    'video_title' => 'The conference in ninety seconds',

    'count_seconds' => 'Seconds',

    'count_minutes' => 'Minutes',

    'count_hours' => 'Hours',

    'count_days' => 'Days',

    'countdown_label' => 'Time remaining until the conference',

    // --- Homepage counters -------------------------------------------------
    // The values come from the edition row and the published programme (see
    // HomeController), never from here — these are only the labels.
    'stats' => [
        'attendees' => 'Delegates',
        'workshops' => 'Workshops',
        'days' => 'Days',
    ],
    'about_subtitle' => 'A conference to',
    'about_title' => 'About',
    'audience_title' => 'Target audience',
    'contact_cta' => 'Contact us',
    'editions_alt' => 'Photograph from a previous ARABCIA edition, :number',
    'editions_caption' => 'ARABCIA — previous edition (:number)',
    'editions_lede' => 'Generations of internal auditors have gathered in Rabat, year after year. Here are a few moments from those previous editions.',
    'editions_title' => 'Previous editions',
    'organisers_eyebrow' => 'Organisers',
    'organisers_lede' => 'ARABCIA convenes the conference; IIA Maroc hosts it in the Kingdom of Morocco.',
    'organisers_title' => 'Who is behind ARABCIA 2026',
    'format_title' => 'Format',
    'hero_cta' => 'Register',
    'hero_secondary' => 'View the programme',
    'join_us' => 'Join us!',
    'programme_title' => 'Programme overview',
    'registration_title' => 'Registration',
    'speakers_more' => 'See all speakers',
    'speakers_title' => 'Internationally renowned experts',
    'sponsors_title' => 'Partners',
    'stats.attendees' => 'Expected participants',
    'stats.days' => 'Days',
    'stats.labs' => 'Innovation labs',
    'stats.workshops' => 'Workshops',
    'stats_title' => 'In figures',
    'theme_title' => 'Theme',
    'venue_title' => 'Venue and dates',
];
