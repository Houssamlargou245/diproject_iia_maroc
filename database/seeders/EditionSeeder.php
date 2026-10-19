<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EditionStatus;
use App\Models\ContactMessage;
use App\Models\ContactRoute;
use App\Models\ContentBlock;
use App\Models\Country;
use App\Models\Edition;
use App\Models\Organisation;
use App\Models\Room;
use App\Models\TeamMember;
use App\Models\TicketType;
use App\Models\Track;
use Illuminate\Database\Seeder;

/**
 * The 2026 edition and the data every page depends on.
 *
 * The rule followed throughout: nothing a visitor reads as a fact about the
 * conference lives in a template. The year, the venue, the dates, the theme, the
 * rates and the room list are rows here, which is what makes the 2024 edition a
 * second set of rows rather than a second copy of the site.
 *
 * Anything the brief has not confirmed is left empty or explicitly marked
 * provisional. The scientific programme is not seeded with placeholder sessions
 * and the sponsor list is not seeded with invented companies, because a filled-in
 * page that is wrong is worse than an empty page that is honest.
 */
class EditionSeeder extends Seeder
{
    public function run(): void
    {
        $this->countries();
        $this->edition2026();
        $this->organisations();
        $this->teamMembers();
        $this->rooms();
        $this->tracks();
        $this->ticketTypes();
        $this->content();

        // After the edition, because the routes point at its contact address.
        $this->contactRoutes();
    }

    /**
     * A short, deliberately incomplete country list.
     *
     * The 2024 database had no countries table at all; the registration form
     * therefore stored a free-text string that nothing could validate or group
     * by. Seeding the whole ISO list is a data task, and the form degrades
     * gracefully to a nullable field, so the countries the 2026 brief names plus
     * the Maghreb region is enough to be useful today.
     */
    private function countries(): void
    {
        $countries = [
            ['MA', 'MAR', '+212', 'Maroc', 'Morocco', 'المغرب'],
            ['FR', 'FRA', '+33', 'France', 'France', 'فرنسا'],
            ['DZ', 'DZA', '+213', 'Algérie', 'Algeria', 'الجزائر'],
            ['TN', 'TUN', '+216', 'Tunisie', 'Tunisia', 'تونس'],
            ['ES', 'ESP', '+34', 'Espagne', 'Spain', 'إسبانيا'],
            ['PT', 'PRT', '+351', 'Portugal', 'Portugal', 'البرتغال'],
            ['GB', 'GBR', '+44', 'Royaume-Uni', 'United Kingdom', 'المملكة المتحدة'],
            ['US', 'USA', '+1', 'États-Unis', 'United States', 'الولايات المتحدة'],
            ['CA', 'CAN', '+1', 'Canada', 'Canada', 'كندا'],
            ['AE', 'ARE', '+971', 'Émirats arabes unis', 'United Arab Emirates', 'الإمارات العربية المتحدة'],
            ['SA', 'SAU', '+966', 'Arabie saoudite', 'Saudi Arabia', 'السعودية'],
            ['DE', 'DEU', '+49', 'Allemagne', 'Germany', 'ألمانيا'],
            ['IT', 'ITA', '+39', 'Italie', 'Italy', 'إيطاليا'],
            ['BE', 'BEL', '+32', 'Belgique', 'Belgium', 'بلجيكا'],
            ['NL', 'NLD', '+31', 'Pays-Bas', 'Netherlands', 'هولندا'],
            ['NG', 'NGA', '+234', 'Nigéria', 'Nigeria', 'نيجيريا'],
            ['SN', 'SEN', '+221', 'Sénégal', 'Senegal', 'السنغال'],
            ['CI', 'CIV', '+225', 'Côte d\'Ivoire', 'Ivory Coast', 'ساحل العاج'],
            ['EG', 'EGY', '+20', 'Égypte', 'Egypt', 'مصر'],
            ['QA', 'QAT', '+974', 'Qatar', 'Qatar', 'قطر'],
            ['TR', 'TUR', '+90', 'Turquie', 'Türkiye', 'تركيا'],
            ['RU', 'RUS', '+7', 'Russie', 'Russia', 'روسيا'],
        ];

        foreach ($countries as [$iso2, $iso3, $phone, $fr, $en, $ar]) {
            Country::query()->updateOrCreate(
                ['iso2' => $iso2],
                [
                    'iso3' => $iso3,
                    'phone_code' => $phone,
                    'dial_code' => $phone,
                    'name_fr' => $fr,
                    'name_en' => $en,
                    'name_ar' => $ar,
                    'is_active' => true,
                ],
            );
        }
    }

    private function edition2026(): void
    {
        Edition::query()->updateOrCreate(
            ['year' => 2026],
            [
                'code' => 'ARABCIA2026',
                'organiser' => 'ARABCIA',
                'host_institute' => 'IIA Maroc',

                'title' => [
                    'fr' => 'Conférence internationale ARABCIA 2026',
                    'en' => 'International ARABCIA Conference 2026',
                    'ar' => 'المؤتمر الدولي ARABCIA 2026',
                ],

                // The theme is quoted from the 2026 brief. It is stored as data
                // rather than typed into the hero so the organisers can amend the
                // wording without a deploy.
                'theme' => [
                    'fr' => 'L\'Audit Interne : partenaire de confiance dans la transformation et la résilience des organisations',
                    'en' => 'Internal Audit : A Trusted Partner in Organizational Transformation and Resilience',
                    'ar' => 'التدقيق الداخلي: شريك موثوق في تحوّل المؤسسات وتعزيز قدرتها على الصمود',
                ],

                'introduction' => [
                    'fr' => "La Conférence annuelle ARABCIA 2026, organisée par l'ARABCIA et accueillie au Maroc par l'IIA Maroc, réunira à Rabat les professionnels et les institutions qui contribuent au développement de l'Audit Interne dans le monde arabe et au-delà. Cette édition aura pour thème : « L'Audit Interne : partenaire de confiance dans la transformation et la résilience des organisations ».

Face à l'essor de l'intelligence artificielle, aux cybermenaces et aux bouleversements économiques et sociaux, les organisations doivent anticiper les risques, s'adapter et préserver la confiance. La conférence explorera les réponses concrètes à ces défis et montrera comment l'Audit Interne, fort de son indépendance et de son jugement professionnel, peut contribuer à la gouvernance, à la résilience et à la création de valeur durable.",
                    'en' => 'The ARABCIA 2026 Annual Conference, organized by ARABCIA and hosted in Morocco by the IIA Morocco, will bring together professionals and institutions contributing to the development of Internal Audit in the Arab world and beyond. This edition will have the theme: "Internal Audit : A Trusted Partner in Organizational Transformation and Resilience".

Facing the rise of artificial intelligence, cyber threats and economic and social upheavals, organizations must anticipate risks, adapt and preserve trust. The conference will explore concrete responses to these challenges and show how Internal Audit, with its independence and professional judgment, can contribute to governance, resilience and sustainable value creation.',
                    'ar' => 'تُعقد المؤتمر السنوي ARABCIA 2026، بتنظيم من الاتحاد العربي لمعاهد التدقيق الداخلي (ARABCIA) وباستضافة من المعهد الدولي للتدقيق الداخلي IIA المغرب، في مدينة الرباط بالمملكة المغربية. ويلتقي فيها المهنيون والمؤسسات التي تسهم في تطوير التدقيق الداخلي في العالم العربي وما وراءه، وتُدار حول موضوع: «التدقيق الداخلي: شريك موثوق في تحوّل المؤسسات وتعزيز قدرتها على الصمود».

 أمام تسارع الذكاء الاصطناعي، وتزايد التهديدات الإلكترونية، والاضطرابات الاقتصادية والاجتماعية، بات على المؤسسات أن تستبق المخاطر، وأن تتكيف، وأن تحافظ على الثقة. وسيناقش المؤتمر الحلول العملية لهذه التحديات، ويبيّن كيف يمكن للتدقيق الداخلي، باستقلاله وحكمه المهني، أن يسهم في الحوكمة والمرونة وخلق قيمة مستدامة.',
                ],

                'city' => 'Rabat',
                'country_iso2' => 'MA',
                'venue_name' => 'Four Seasons Hotel at Kasr Al Bahr',
                // The hotel's own published address. The map link is derived
                // from the coordinates below, so the pin is exact.
                'venue_address' => 'Ancien Hôpital Marie Feuillet, Avenue Brahim Roudani, Quartier de l’Océan, Rabat 10000',
                'venue_lat' => 34.0206634,
                'venue_lng' => -6.8545618,
                'venue_map_url' => null,

                // Confirmed in the brief.
                'starts_on' => '2026-12-16',
                'ends_on' => '2026-12-17',

                'languages' => ['fr', 'en', 'ar'],
                'target_audience' => [
                    'fr' => [
                        'Directeurs et responsables de l\'audit interne',
                        'Cadres financiers et comptables',
                        'Consultants en management et organisation',
                        'Représentants des institutions de contrôle',
                        'Universitaires et chercheurs',
                    ],
                    'en' => [
                        'Chief audit executives and internal audit leaders',
                        'Finance and accounting officers',
                        'Management and organisation consultants',
                        'Representatives of oversight institutions',
                        'Academics and researchers',
                    ],
                    'ar' => [
                        'مديرو ومسؤولو التدقيق الداخلي',
                        'الإطارون الماليون والمحاسبون',
                        'استشاريو الإدارة والتنظيم',
                        'ممثلو هيئات الرقابة',
                        'الأكاديميون والباحثون',
                    ],
                ],

                'status' => EditionStatus::Published,
                'is_current' => true,

                // Registration is not open at build time. Opening it is a single
                // flag, and a registration button on a closed edition generates
                // support mail rather than delegates.
                'registration_open' => true,

                // The secretariat of the host institute. The 2026 dossier closes
                // its contact section with this exact mailbox and line — "for any
                // further information about the conference" — so it is the
                // edition's general contact, not an invented one. The six named
                // officers are rows in `teamMembers()` below.
                'contact_email' => 'info@iiamaroc.org',
                'contact_phone' => '+212 6 13 32 32 93',

                'organiser_contact_name' => 'ARABCIA — Arab Confederation of Internal Auditors',
                'organiser_contact_email' => 'contact@arabcia.org',
                'host_contact_name' => 'Hasnae MELHAOUI, Secrétaire Générale — IIA Maroc',
                'host_contact_email' => 'info@iiamaroc.org',

                'hero_image_path' => null,
                'logo_path' => null,
                'archive_note' => null,
            ],
        );
    }

    private function organisations(): void
    {
        $organisations = [
            [
                'code' => 'ARABCIA',
                'name' => [
                    'fr' => 'ARABCIA',
                    'en' => 'ARABCIA',
                    'ar' => 'ARABCIA',
                ],
                // Per the 2026 dossier, p.12: ARABCIA is the Arab Confederation
                // of Internal Auditors — a non-profit, financially independent
                // international professional organisation founded in December 2022
                // and established in the Kingdom of Saudi Arabia, federating the
                // internal audit institutes of thirteen Arab countries. The 2024
                // text called it a regional association of French-speaking African
                // audit directors, which is a different organisation entirely, and
                // it was being printed on the partners page.
                'description' => [
                    'fr' => 'Confédération arabe des instituts d\'Audit Interne. Organisation professionnelle internationale à but non lucratif, créée en décembre 2022 et établie au Royaume d\'Arabie saoudite, elle fédère les instituts d\'Audit Interne de treize pays arabes.',
                    'en' => 'Arab Confederation of Internal Auditors. An independent international non-profit professional organisation, founded in December 2022 and established in the Kingdom of Saudi Arabia, federating the internal audit institutes of thirteen Arab countries.',
                    'ar' => 'الاتحاد العربي لمعاهد التدقيق الداخلي. منظمة مهنية دولية غير ربحية ومستقلة، أُنشئت في ديسمبر 2022 ومقرها المملكة العربية السعودية، وتجمع معاهد التدقيق الداخلي من ثلاثة عشر دولة عربية.',
                ],
                'role' => [
                    'fr' => 'Organisateur',
                    'en' => 'Organiser',
                    'ar' => 'المنظم',
                ],
                'sort_order' => 1,
            ],
            [
                'code' => 'IIA_MAROC',
                'name' => [
                    'fr' => 'Institut des Auditeurs Internes du Maroc — IIA Maroc',
                    'en' => 'Institute of Internal Auditors — Morocco (IIA Maroc)',
                    'ar' => 'المعهد الدولي للتدقيق الداخلي — IIA المغرب',
                ],
                'description' => [
                    'fr' => 'L\'institut qui accueille la conférence au Royaume du Maroc et qui organise chaque année des événements de référence pour la profession.',
                    'en' => 'The institute that hosts the conference in the Kingdom of Morocco and that organises the profession\'s flagship events each year.',
                    'ar' => 'المعهد الذي يستضيف المؤتمر في المملكة المغربية وينظّم الأحداث الكبرى للمهنة كل عام.',
                ],
                'role' => [
                    'fr' => 'Institut hôte',
                    'en' => 'Host institute',
                    'ar' => 'المعهد المضيف',
                ],
                'sort_order' => 2,
            ],
        ];

        $edition = Edition::forYear(2026);

        foreach ($organisations as $organisation) {
            Organisation::query()->updateOrCreate(
                ['code' => $organisation['code']],
                array_merge($organisation, [
                    'edition_id' => $edition?->getKey(),
                    'is_published' => true,
                ]),
            );
        }
    }

    /**
     * Rooms.
     *
     * The brief caps concurrency at four parallel sessions, so four rooms is the
     * shape of the grid the programme view renders.
     *
     * `name` is translated like every other visible string, and `floor` is a
     * level number rather than a label: "Rez-de-chaussée" would need translating
     * and sorting by it would be alphabetical, so the level is stored and the
     * wording lives in the view.
     */
    /**
     * The organising committee, as published in the 2026 dossier and the
     * sponsorship pack.
     *
     * These are the six named officers of IIA Maroc — the host institute — with
     * the direct line and mailbox the organisers themselves print on their
     * documents. They are rows rather than template text for the same reason
     * everything else here is: when the committee changes after the next
     * edition, this is five rows to edit and no deploy, and the contact page
     * cannot keep showing a president who left the office.
     *
     * The pairing of each name to each telephone number and mailbox was
     * cross-checked between the two source documents: the French dossier lists
     * the secretariat's line inline, and the sponsorship pack lists the numbers
     * in a separate column. Every mailbox here either carries the holder's own
     * name or states the office they hold, which is what makes the pairing
     * verifiable rather than a guess about column order.
     *
     * `TEAM_ORGANISING` because every one of them sits on the organising
     * committee; `sort_order` follows the order the dossier prints them, which
     * is by seniority.
     */
    private function teamMembers(): void
    {
        $edition = Edition::forYear(2026);

        if ($edition === null) {
            return;
        }

        $members = [
            [
                'name' => 'Deyaa ABBAD EL ANDALOUSSI',
                'role' => ['fr' => 'Présidente', 'en' => 'President', 'ar' => 'رئيسة'],
                'email' => 'president@iiamaroc.org',
                'phone' => '+212 661 122 044',
            ],
            [
                'name' => 'Meriam LIAFI',
                'role' => ['fr' => 'Vice-Présidente', 'en' => 'Vice President', 'ar' => 'نائبة الرئيس'],
                'email' => 'vice.president@iiamaroc.org',
                'phone' => '+212 661 077 672',
            ],
            [
                'name' => 'Hasnae MELHAOUI',
                'role' => ['fr' => 'Secrétaire Générale', 'en' => 'Secretary General', 'ar' => 'الأمينة العامة'],
                // The secretariat is the address the dossiers themselves give for
                // "any further information", so it is also the edition's general
                // contact number.
                'email' => 'info@iiamaroc.org',
                'phone' => '+212 6 13 32 32 93',
            ],
            [
                'name' => 'Khadija El Idrissi',
                'role' => ['fr' => 'Directrice Déléguée', 'en' => 'Executive Director', 'ar' => 'المديرة التنفيذية'],
                'email' => 'directeur.delegue@iiamaroc.org',
                'phone' => '+212 665 229 929',
            ],
            [
                'name' => 'Zineb EDDAYA',
                'role' => ['fr' => 'Trésorière', 'en' => 'Treasurer', 'ar' => 'أمينة الصندوق'],
                'email' => 'zineb.eddaya@gmail.com',
                'phone' => '+212 661 291 258',
            ],
            [
                'name' => 'Abdelmounim ZAGHLOUL',
                'role' => ['fr' => 'Président sortant', 'en' => 'Immediate Past President', 'ar' => 'الرئيس السابق'],
                'email' => 'zmounim@gmail.com',
                'phone' => '+212 661 318 247',
            ],
        ];

        foreach ($members as $index => $member) {
            TeamMember::query()->updateOrCreate(
                [
                    'edition_id' => $edition->getKey(),
                    'email' => $member['email'],
                ],
                array_merge($member, [
                    'team' => TeamMember::TEAM_ORGANISING,
                    // Every officer is an IIA Maroc officer: the committee belongs
                    // to the host institute, and repeating the institute on each
                    // row is what lets the card say so under the name.
                    'organisation' => 'IIA Maroc',
                    'is_published' => true,
                    'sort_order' => $index + 1,
                ]),
            );
        }

        // updateOrCreate() matches on the mailbox, so a member who is replaced by
        // someone else at the same office leaves the old row behind — and the
        // contact page would then offer a visitor to email an officer who has
        // left. Rows this method owns are removed first, by the same rule the
        // track seeder uses.
        TeamMember::query()
            ->where('edition_id', $edition->getKey())
            ->where('team', TeamMember::TEAM_ORGANISING)
            ->whereNotIn('email', array_column($members, 'email'))
            ->delete();
    }

    private function rooms(): void
    {
        $edition = Edition::forYear(2026);

        if ($edition === null) {
            return;
        }

        $rooms = [
            [
                'code' => 'PLENARY',
                'name' => ['fr' => 'Salle plénière', 'en' => 'Plenary hall', 'ar' => 'القاعة العامة'],
                'capacity' => 300,
                'floor' => 0,
                'sort_order' => 1,
            ],
            [
                'code' => 'ATELIER_1',
                'name' => ['fr' => 'Atelier 1', 'en' => 'Workshop room 1', 'ar' => 'قاعة الورشات 1'],
                'capacity' => 60,
                'floor' => 1,
                'sort_order' => 2,
            ],
            [
                'code' => 'ATELIER_2',
                'name' => ['fr' => 'Atelier 2', 'en' => 'Workshop room 2', 'ar' => 'قاعة الورشات 2'],
                'capacity' => 60,
                'floor' => 1,
                'sort_order' => 3,
            ],
            [
                'code' => 'ATELIER_3',
                'name' => ['fr' => 'Atelier 3', 'en' => 'Workshop room 3', 'ar' => 'قاعة الورشات 3'],
                'capacity' => 40,
                'floor' => 1,
                'sort_order' => 4,
            ],
            [
                'code' => 'LAB_1',
                'name' => ['fr' => 'Innovation Lab 1', 'en' => 'Innovation Lab 1', 'ar' => 'معمل الابتكار 1'],
                'capacity' => 30,
                'floor' => 1,
                'sort_order' => 5,
            ],
            [
                'code' => 'LAB_2',
                'name' => ['fr' => 'Innovation Lab 2', 'en' => 'Innovation Lab 2', 'ar' => 'معمل الابتكار 2'],
                'capacity' => 30,
                'floor' => 1,
                'sort_order' => 6,
            ],
            [
                'code' => 'LAB_3',
                'name' => ['fr' => 'Innovation Lab 3', 'en' => 'Innovation Lab 3', 'ar' => 'معمل الابتكار 3'],
                'capacity' => 30,
                'floor' => 1,
                'sort_order' => 7,
            ],
            [
                'code' => 'LAB_4',
                'name' => ['fr' => 'Innovation Lab 4', 'en' => 'Innovation Lab 4', 'ar' => 'معمل الابتكار 4'],
                'capacity' => 30,
                'floor' => 1,
                'sort_order' => 8,
            ],
            [
                'code' => 'LAB_5',
                'name' => ['fr' => 'Innovation Lab 5', 'en' => 'Innovation Lab 5', 'ar' => 'معمل الابتكار 5'],
                'capacity' => 30,
                'floor' => 1,
                'sort_order' => 9,
            ],
            [
                'code' => 'LAB_6',
                'name' => ['fr' => 'Innovation Lab 6', 'en' => 'Innovation Lab 6', 'ar' => 'معمل الابتكار 6'],
                'capacity' => 30,
                'floor' => 1,
                'sort_order' => 10,
            ],
        ];

        foreach ($rooms as $room) {
            Room::query()->updateOrCreate(
                ['edition_id' => $edition->getKey(), 'code' => $room['code']],
                array_merge($room, ['is_published' => true]),
            );
        }
    }

    /**
     * Programme tracks.
     *
     * The themes are the broad subject areas rather than the sessions; the brief
     * asks for 18 workshops and 6 innovation labs across them.
     */
    private function tracks(): void
    {
        $edition = Edition::forYear(2026);

        if ($edition === null) {
            return;
        }

        $tracks = [
            ['code' => 'GOUVERNANCE', 'name' => ['fr' => 'Gouvernance et conformité', 'en' => 'Governance and compliance', 'ar' => 'الحوكمة والامتثال'], 'sort_order' => 1],
            ['code' => 'NUMERIQUE', 'name' => ['fr' => 'Transformation numérique', 'en' => 'Digital transformation', 'ar' => 'التحول الرقمي'], 'sort_order' => 2],
            ['code' => 'DURABILITE', 'name' => ['fr' => 'Développement durable', 'en' => 'Sustainable development', 'ar' => 'التنمية المستدامة'], 'sort_order' => 3],
            ['code' => 'RISQUE', 'name' => ['fr' => 'Gestion des risques', 'en' => 'Risk management', 'ar' => 'إدارة المخاطر'], 'sort_order' => 4],
            ['code' => 'COMPETENCES', 'name' => ['fr' => 'Compétences et professions', 'en' => 'Skills and the profession', 'ar' => 'الكفاءات والمهنة'], 'sort_order' => 5],
        ];

        foreach ($tracks as $track) {
            Track::query()->updateOrCreate(
                ['edition_id' => $edition->getKey(), 'code' => $track['code']],
                array_merge($track, [
                    'description' => ['fr' => null, 'en' => null, 'ar' => null],
                    'is_published' => true,
                ]),
            );
        }

        // The programme in the 2026 dossier organises its parallel workshops
        // into three named "parcours", and those are the three tracks the
        // programme grid uses. `AUDIT_TRANSFO` replaces the earlier `IA_TRANSFO`
        // code because the client's wording for that parcours is "Audit Interne
        // et transformation", not "IA et transformation"; keeping the old code
        // under the new name would have left the two disagreeing.
        $axes = [
            ['code' => 'AUDIT_TRANSFO', 'name' => ['fr' => 'Audit Interne et transformation', 'en' => 'Internal audit and transformation', 'ar' => 'التدقيق الداخلي والتحول'], 'sort_order' => 6],
            ['code' => 'RESILIENCE', 'name' => ['fr' => 'Résilience', 'en' => 'Resilience', 'ar' => 'المتانة'], 'sort_order' => 7],
            ['code' => 'AUDITOR_DAME', 'name' => ['fr' => 'L\'auditeur de demain', 'en' => 'The auditor of tomorrow', 'ar' => 'المدقق القادم'], 'sort_order' => 8],
        ];

        foreach ($axes as $axis) {
            Track::query()->updateOrCreate(
                ['edition_id' => $edition->getKey(), 'code' => $axis['code']],
                array_merge($axis, [
                    'description' => ['fr' => null, 'en' => null, 'ar' => null],
                    'is_published' => true,
                ]),
            );
        }

        // updateOrCreate() matches on the code, so renaming a code leaves the
        // old row behind rather than replacing it: re-seeding would otherwise
        // keep a stale IA_TRANSFO track that no session references. Only codes
        // this method owns are considered, and only for this edition.
        //
        // Safe to run before ProgrammeSeeder: conference_sessions.track_id is
        // nullOnDelete, and the programme seeder rebuilds every session anyway.
        Track::query()
            ->where('edition_id', $edition->getKey())
            ->whereNotIn('code', array_merge(
                array_column($tracks, 'code'),
                array_column($axes, 'code'),
            ))
            ->delete();
    }

    /**
     * Ticket types.
     *
     * Prices are stored in minor units (centimes). `7500.00 MAD` is `750000`, and
     * storing the float instead is how the 2024 build ended up with totals
     * off by a centime after a few percentage calculations.
     *
     * The member rate is not a discount the visitor applies: it is a second
     * column, and `TicketType::priceFor()` chooses between them by reading a
     * Membership record. The 2024 site took the choice from a form field, which
     * is the vulnerability this column layout exists to prevent.
     */
    private function ticketTypes(): void
    {
        $edition = Edition::forYear(2026);

        if ($edition === null) {
            return;
        }

        $types = [
            [
                'code' => 'STANDARD',
                'name' => ['fr' => 'Inscription standard', 'en' => 'Standard registration', 'ar' => 'تسجيل عادي'],
                'description' => [
                    'fr' => 'Accès à l\'ensemble des sessions et au cocktail de clôture.',
                    'en' => 'Access to all sessions and to the closing reception.',
                    'ar' => 'الوصول إلى جميع الجلسات وحفل الاستقبال الختامي.',
                ],
                'includes' => [
                    'fr' => ['Accès aux plénières', 'Accès aux ateliers (sur inscription)', 'Déjeuner les deux jours', 'Support de la conférence', 'Attestation de participation'],
                    'en' => ['Access to plenaries', 'Access to workshops (on registration)', 'Lunch on both days', 'Conference proceedings', 'Certificate of participation'],
                    'ar' => ['الوصول إلى الجلسات العامة', 'الوصول إلى الورشات (بالتسجيل)', 'الغداء خلال اليومين', 'وثائق المؤتمر', 'شهادة المشاركة'],
                ],
                // Brief, sheet "Modifications 2026" row 15:
                // "7 500/8 500 MAD et 750/850 USD". Lower figure is the IIA
                // member rate, higher is standard. Minor units, so MAD is x100
                // and USD is x100 (cents), which is why 7 500 MAD is 750000 and
                // 750 USD is 75000. The member rate is a separate column and is
                // not a percentage of the standard rate.
                'price_member' => 750000,
                'price_standard' => 850000,
                'currency' => 'MAD',
                'currency_numeric' => 504,
                'sort_order' => 1,
            ],
            [
                'code' => 'STANDARD_USD',
                'name' => ['fr' => 'Inscription standard (USD)', 'en' => 'Standard registration (USD)', 'ar' => 'تسجيل عادي (دولار)'],
                'description' => [
                    'fr' => 'Pour les participants hors zone francophone.',
                    'en' => 'For participants outside the Francophone region.',
                    'ar' => 'للمشاركين من خارج المنطقة الناطقة بالفرنسية.',
                ],
                'includes' => [
                    'fr' => ['Accès aux plénières', 'Accès aux ateliers (sur inscription)', 'Déjeuner les deux jours', 'Support de la conférence', 'Attestation de participation'],
                    'en' => ['Access to plenaries', 'Access to workshops (on registration)', 'Lunch on both days', 'Conference proceedings', 'Certificate of participation'],
                    'ar' => ['الوصول إلى الجلسات العامة', 'الوصول إلى الورشات (بالتسجيل)', 'الغداء خلال اليومين', 'وثائق المؤتمر', 'شهادة المشاركة'],
                ],
                // Brief row 15: 750/850 USD, minor units.
                'price_member' => 75000,
                'price_standard' => 85000,
                'currency' => 'USD',
                'currency_numeric' => 840,
                'sort_order' => 2,
            ],
        ];

        foreach ($types as $type) {
            TicketType::query()->updateOrCreate(
                ['edition_id' => $edition->getKey(), 'code' => $type['code']],
                array_merge($type, [
                    'colour' => 'brand',
                    'max_quantity' => 10,
                    'is_active' => true,
                    'sales_start_at' => null,
                    'sales_end_at' => null,
                ]),
            );
        }
    }

    /**
     * Editorial content blocks.
     *
     * Pages that are prose rather than data — the sponsoring tiers, the practical
     * notes — are content blocks so an editor can change them without a deploy.
     * They are seeded empty where the brief has no wording yet, rather than with
     * text invented to fill the space.
     */
    private function content(): void
    {
        $edition = Edition::forYear(2026);

        if ($edition === null) {
            return;
        }

        $blocks = [
            [
                'group' => 'sponsoring',
                'key' => 'platinum',
                'value' => ['fr' => null, 'en' => null, 'ar' => null],
            ],
            [
                'group' => 'sponsoring',
                'key' => 'gold',
                'value' => ['fr' => null, 'en' => null, 'ar' => null],
            ],
            [
                'group' => 'sponsoring',
                'key' => 'silver',
                'value' => ['fr' => null, 'en' => null, 'ar' => null],
            ],
            [
                'group' => 'sponsoring',
                'key' => 'bronze',
                'value' => ['fr' => null, 'en' => null, 'ar' => null],
            ],
            [
                'group' => 'practical',
                'key' => 'registration_closes',
                'value' => [
                    'fr' => 'Les inscriptions en ligne sont ouvertes jusqu\'au 30 novembre 2026.',
                    'en' => 'Online registration is open until 30 November 2026.',
                    'ar' => 'التسجيل الإلكتروني مفتوح حتى 30 نوفمبر 2026.',
                ],
            ],
            [
                'group' => 'practical',
                'key' => 'cancellation',
                'value' => [
                    'fr' => 'Toute annulation doit être signalée par écrit au moins quinze jours avant la conférence.',
                    'en' => 'Any cancellation must be notified in writing at least fifteen days before the conference.',
                    'ar' => 'يجب إبلاغ أي إلغاء كتابيًا قبل خمسة عشر يومًا على الأقل من انعقاد المؤتمر.',
                ],
            ],
            [
                'group' => 'sponsoring',
                'key' => 'sponsor_tiers',
                'value' => [
                    'fr' => 'Trois catégories de parrainage sont disponibles : Platine, Or, Argent et Bronze, avec des avantages croissants à chaque niveau.',
                    'en' => 'Three sponsorship categories are available: Platinum, Gold, Silver and Bronze, with increasing benefits at each level.',
                    'ar' => 'تتوفر فئات من الرعاية: بلاتين، ذهبي، فضي وبرونزي، مع زيادة المزايا في كل مستوى.',
                ],
            ],
        ];

        foreach ($blocks as $block) {
            ContentBlock::query()->updateOrCreate(
                ['edition_id' => $edition->getKey(), 'group' => $block['group'], 'key' => $block['key']],
                [
                    'value' => $block['value'],
                    'is_published' => true,
                ],
            );
        }
    }

    /**
     * Contact-form routing.
     *
     * All five routes start pointed at the edition's `contact_email`, because the
     * brief names no departmental addresses. Splitting them out now means the
     * communications team only has to edit five rows in the admin panel to route
     * registration, sponsoring, speaker, press and general enquiries to different
     * people — no deploy, no code change.
     */
    private function contactRoutes(): void
    {
        $edition = Edition::forYear(2026);

        if ($edition === null || blank($edition->contact_email)) {
            return;
        }

        $routes = [
            [ContactMessage::SUBJECT_REGISTRATION, 'Inscription', 'Registration', 'التسجيل', 1],
            [ContactMessage::SUBJECT_SPONSORING, 'Partenariat', 'Sponsoring', 'الرعاية', 2],
            [ContactMessage::SUBJECT_SPEAKER, 'Intervenants', 'Speakers', 'المتدخلون', 3],
            [ContactMessage::SUBJECT_PRESS, 'Presse', 'Press', 'الصحافة', 4],
            // Must exist and must be active: ContactRoute::forSubject() falls back
            // to it, so a missing row is what makes an enquiry unroutable.
            [ContactMessage::SUBJECT_OTHER, 'Autres demandes', 'Other enquiries', 'طلبات أخرى', 99],
        ];

        foreach ($routes as [$type, $fr, $en, $ar, $order]) {
            ContactRoute::query()->updateOrCreate(
                ['subject_type' => $type],
                [
                    'label_fr' => $fr,
                    'label_en' => $en,
                    'label_ar' => $ar,
                    'to_email' => $edition->contact_email,
                    'cc_email' => null,
                    'sort_order' => $order,
                    'is_active' => true,
                ],
            );
        }
    }
}
