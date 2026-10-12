<?php

declare(strict_types=1);

return [
    'page' => [
        'intro_title' => 'Programme',
        'intro_heading' => 'Two days at the heart of Internal Audit\'s challenges',
        'intro_p1' => 'The annual ARABCIA :year Conference offers two days of reflection, exchange and shared experience on the role of Internal Audit in the face of the transformations and new risks organizations are confronting.',
        'intro_p2' => 'The programme combines keynotes, panels, hands-on workshops and Innovation Labs, with sessions devoted in particular to artificial intelligence, resilience, governance, risk and the evolution of the internal auditor\'s profession.',

        'tracks_lede' => 'The workshops are organized around three complementary tracks:',
        'track_ai' => 'Exploring the impact of artificial intelligence and new technologies on organizations, governance and Internal Audit practices.',
        'track_res' => 'Addressing the challenges of risk and uncertainty, and the ability of organizations to anticipate, adapt and sustain their performance.',
        'track_aud' => 'Reflecting on how the profession is evolving, on the new competencies required and on the internal auditor\'s place in a changing environment.',
        'lab_text' => 'A space dedicated to exploring the new approaches, technologies and practices transforming internal audit. An interactive experience to stimulate innovation and open new perspectives.',

        'day1_date' => 'Wednesday, 16 December 2026',
        'day2_date' => 'Thursday, 17 December 2026',

        'cta_title' => 'Join us for two days of exchange, inspiration and collaboration.',

        'title' => 'Scientific programme',
        'hero_lede' => 'Two days of keynotes, workshops and parallel sessions. Browse the schedule day by day and pick the sessions you want to be in the room for.',
        'provisional_notice' => 'The programme is provisional and may change.',
        'tracks_title' => 'Three workshop tracks',
        'track_ai_title' => 'Internal Audit and transformation',
        'track_resilience_title' => 'Resilience',
        'track_auditor_title' => 'The auditor of tomorrow',
        'lab_title' => 'The Innovation Lab',
        'day' => 'Day :day',
        'notice' => 'The programme is provisional. Times, rooms and speakers may still change, so please treat this page as a guide rather than as a final timetable.',
        'qr_title' => 'The programme as a PDF',
        'qr_text' => 'Scan this code with your phone to download the full programme.',
        'qr_download' => 'Download the programme',

        's' => [
            'welcome1' => ['title' => 'Welcome, registration and networking'],
            'welcome2' => ['title' => 'Welcome of participants'],
            'ceremony' => [
                'title' => 'Opening ceremony',
                'desc' => 'Official opening by IIA Morocco, ARABCIA and The IIA',
            ],
            'trophies' => ['title' => 'Certification trophies award'],
            'break' => ['title' => 'Coffee break'],
            'lunch' => ['title' => 'Lunch'],
            'workshops' => ['title' => 'Workshops — Three tracks'],

            'pl1' => [
                'title' => 'Keynote 1',
                'subtitle' => 'Footprint: building trust in the age of accelerating AI',
                'desc' => 'Showing how Internal Audit anticipates risks, supports governance and strengthens resilience.',
            ],
            'pl2' => [
                'title' => 'Keynote 2',
                'subtitle' => 'Leading through the uncertainty of a fragmented world: the new global and regional risk landscape',
                'desc' => 'Linking AI, cyber threats, geopolitical instability, financial crime, digital transformation and supply chains.',
            ],
            'pl3' => [
                'title' => 'Keynote 3 — Panel',
                'subtitle' => 'What boards expect from Internal Audit in a period of continuous disruption',
                'desc' => 'Available resources and the practices that build trust.',
            ],
            'pl4' => [
                'title' => 'Keynote 4',
                'desc' => 'The human advantage in the age of AI: the resilient auditor and what technology will never replace.',
            ],
            'pl5' => [
                'title' => 'Keynote 5 — Panel',
                'desc' => 'Women leading the transformation of Internal Audit.',
            ],
            'pl6' => [
                'title' => 'Keynote 6',
                'desc' => 'From coordinating the Three Lines to integrated assurance: building an assurance map that serves resilience.',
            ],
            'pl7' => [
                'title' => 'Keynote 7 — Panel',
                'desc' => 'Beyond assurance: how Internal Audit creates strategic value in a changing world.',
            ],

            'lab1' => [
                'title' => 'Innovation Lab',
                'bullets' => [
                    'Continuous Monitoring',
                    'AI Copilots for Internal Audit',
                    'GRC platforms',
                ],
            ],
            'lab2' => [
                'title' => 'Innovation Lab',
                'bullets' => [
                    'SAP Monitoring',
                    'Process Mining',
                    'Fraud detection and predictive analysis',
                ],
            ],
        ],

        // Workshop sessions: 3 time slots per day, one session per track.
        'ws' => [
            'd1' => [
                [
                    'ai' => 'Generative AI applied to Internal Audit: opportunities and limits',
                    'res' => 'Cyber-resilience: beyond cybersecurity',
                    'aud' => 'The essential skills of tomorrow\'s internal auditor',
                ],
                [
                    'ai' => 'AI governance and auditing responsible AI frameworks',
                    'res' => 'Operational resilience and critical processes',
                    'aud' => 'Leadership and influence in an environment of continuous disruption',
                ],
                [
                    'ai' => 'Auditing algorithms and artificial intelligence models',
                    'res' => 'Organizational culture and resilience: what should Internal Audit assess?',
                    'aud' => 'Strategic thinking and understanding of business challenges',
                ],
            ],
            'd2' => [
                [
                    'ai' => 'Auditing digital transformation programmes',
                    'res' => 'Geopolitical risks: what role for Internal Audit?',
                    'aud' => 'Attracting, retaining and developing Internal Audit talent',
                ],
                [
                    'ai' => 'Data governance and data quality',
                    'res' => 'Public sector resilience',
                    'aud' => 'Storytelling and data visualization to strengthen the impact of Internal Audit',
                ],
                [
                    'ai' => 'Auditing emerging technologies: cloud, blockchain, automation and agentic AI',
                    'res' => 'Digital trust and the resilience of digital ecosystems',
                    'aud' => 'Agility and adaptability in a continuously evolving environment',
                ],
            ],
        ],
    ],
];
