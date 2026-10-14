<?php

declare(strict_types=1);

return [
    'become_partner' => 'Devenir partenaire',
    'contact' => 'Nous contacter',
    'from' => 'À partir de :amount',
    'meta_description' => 'Devenez partenaire d’ARABCIA :year : associez votre marque à un rendez-vous régional et international de référence de l’audit interne.',
    'no_sponsors' => 'La liste des partenaires sera communiquée prochainement.',
    'plate_activations' => 'Activations et visibilité',
    'plate_activations_lede' => 'Une présence de marque dans le lieu, sur les canaux numériques et pendant les temps forts de la conférence.',
    'plate_packages' => 'Les formules',
    'plate_packages_lede' => 'Les quatre niveaux de partenariat, côte à côte : prestations, tarifs et volumes.',
    'plate_timeline' => 'Conditions et calendrier',
    'plate_timeline_lede' => 'Acompte, solde et échéances recommandées pour réserver un emplacement.',
    'plates_lede' => 'L’offre de partenariat, telle que la publie le comité organisateur.',
    'plates_title' => 'Ce que comprend le partenariat',
    'previous' => 'Partenaires des éditions précédentes',
    'tier.bronze' => 'Bronze',
    'tier.gold' => 'Or',
    'tier.institutional' => 'Institutionnel',
    'tier.partner' => 'Partenaire',
    'tier.platinum' => 'Platine',
    'tier.previous' => 'Édition précédente',
    'tier.silver' => 'Argent',
    'title' => 'Sponsoring',

    'hero' => [
        'eyebrow' => 'CONFÉRENCE ANNUELLE ARABCIA : 2026',
        'title_lead' => 'Devenez partenaire',
        'title_accent' => 'd’un rendez-vous régional et international de référence',
        'lede' => 'Associez votre marque à la Conférence annuelle ARABCIA :year et bénéficiez d’une visibilité privilégiée auprès de près de 300 décideurs et professionnels de l’audit interne, des risques, de la conformité et de la gouvernance.',
        'image_alt' => 'Des participants échangent autour d’un stand ARABCIA pendant la conférence',
    ],

    // The two buttons of the hero, side by side under the pitch.
    'hero_actions' => [
        'download' => 'Télécharger le dossier de sponsoring',
        'partner' => 'Devenir partenaire',
    ],

    'why' => [
        'eyebrow' => 'Pourquoi devenir partenaire ?',
        'title' => '5 bonnes raisons de nous rejoindre',
        'lede' => 'Soutenez un événement majeur de la profession et bénéficiez d’une visibilité auprès d’une audience qualifiée, tout en contribuant au partage d’expérience et au développement de la fonction audit interne.',
        'reach' => [
            'title' => 'Accéder à une audience qualifiée',
            'text' => 'Près de 300 dirigeants, experts et professionnels du Maroc, du monde arabe, d’Afrique et d’autres régions.',
        ],
        'profiles' => [
            'title' => 'Valoriser votre expertise',
            'text' => 'Présentez vos solutions, vos cas d’usage et vos innovations.',
        ],
        'decision' => [
            'title' => 'Rencontrer des décideurs',
            'text' => 'Développez des relations professionnelles avec les secteurs public et privé.',
        ],
        'visibility' => [
            'title' => 'Renforcer votre visibilité',
            'text' => 'Associez votre marque à un événement de référence dans la région.',
        ],
        'community' => [
            'title' => 'Contribuer à la communauté',
            'text' => 'Soutenez le développement de la profession d’audit interne et la promotion des bonnes pratiques.',
        ],
        // Kept so a stale key never blanks a card: the third reason of the
        // original three-card row, now folded into `decision`.
        'theme' => [
            'title' => 'Thème :year et visibilité à Rabat',
            'text' => 'Associez votre marque à un thème d’actualité dans un cadre prestigieux.',
        ],
    ],

    // ------------------------------------------------------------------
    // The four sponsorship formulas.
    // ------------------------------------------------------------------
    // `features` is a flat list: each entry is one bullet, and the card draws
    // them in order. `limit` is the scarcity line under the price — it is what
    // makes the decision urgent, so it sits next to the figure rather than in
    // the description.
    'packages' => [
        'eyebrow' => 'Nos formules de sponsoring',
        'title' => 'Des opportunités adaptées à vos objectifs',
        'compare' => 'Comparer les formules',
        'per' => 'HT',

        'platinum' => [
            'name' => 'PLATINUM PARTNER',
            'price' => '100 000 MAD',
            'limit' => 'Maximum 2 partenaires',
            'summary' => 'La formule premium pour une présence forte avant, pendant et après la conférence.',
            'cta' => 'Devenir partenaire Platinum',
            'features' => [
                'Double stand dans l’espace exposition',
                '10 accès conférence',
                '4 badges exposants',
                'Innovation Lab de 45 min inclus',
                'Film institutionnel de 90 sec.',
                '1 page publicitaire + 1 page de contenu expert dans le guide digital',
                '2 publications digitales dédiées',
                'Insertion dans le Welcome Pack',
                'Visibilité premium sur les principaux supports de la conférence',
            ],
        ],

        'gold' => [
            'name' => 'GOLD PARTNER',
            'price' => '50 000 MAD',
            'limit' => 'Maximum 4 partenaires',
            'summary' => 'Une visibilité renforcée associant présence de marque et accès privilégié à la communauté professionnelle.',
            'cta' => 'Devenir partenaire Gold',
            'features' => [
                'Stand standard',
                '5 accès conférence',
                '3 badges exposants',
                'Film institutionnel de 60 sec.',
                '½ page publicitaire dans le guide digital',
                'Insertion dans le Welcome Pack',
                '1 publication digitale dédiée',
                'Visibilité renforcée du logo',
                'Accès prioritaire à la réservation d’un Innovation Lab',
            ],
        ],

        'silver' => [
            'name' => 'SILVER PARTNER',
            'price' => '25 000 MAD',
            'limit' => 'Maximum 8 partenaires',
            'summary' => 'Une formule efficace pour développer votre visibilité et rencontrer les participants pendant la conférence.',
            'cta' => 'Devenir partenaire Silver',
            'features' => [
                'Stand compact',
                '2 accès conférence',
                '2 badges exposants',
                '¼ de page publicitaire dans le guide digital',
                'Présence du logo sur les principaux supports',
                'Mention sur les canaux digitaux',
                'Possibilité de réserver un Innovation Lab ou une activation complémentaire',
            ],
        ],

        'lab' => [
            'name' => 'INNOVATION LAB PARTNER',
            'price' => '25 000 MAD',
            'limit' => 'Maximum 6 sessions',
            'summary' => '45 minutes pour présenter votre expertise, une solution ou un cas d’usage à une audience professionnelle ciblée.',
            'cta' => 'Réserver un Innovation Lab',
            'features' => [
                'Session dédiée de 45 min.',
                'Présence dans le programme officiel',
                'Branding de la salle',
                '2 accès conférence + 2 badges équipe',
                '1 publication digitale dédiée',
                'Présentation mise à disposition des participants',
                'Contacts recueillis pour le Lab, sous réserve de leur consentement',
            ],
        ],
    ],

    // ------------------------------------------------------------------
    // À-la-carte activations, under the four formulas.
    // ------------------------------------------------------------------
    'activations' => [
        'eyebrow' => 'Complétez votre visibilité',
        'title' => 'Des activations complémentaires à la carte',
        'measure' => 'À la carte',

        'items' => [
            ['name' => 'EXPOSANT', 'price' => '10 000 MAD', 'features' => ['Stand standard', '2 badges exposants', '1 accès conférence']],
            ['name' => 'PARTENAIRE DÉJEUNER', 'price' => '40 000 MAD / jour', 'features' => ['Visibilité dédiée pendant le déjeuner et présence sur les supports associés.']],
            ['name' => 'PARTENAIRE PAUSE-CAFÉ', 'price' => '30 000 MAD / jour', 'features' => ['Branding et visibilité pendant les pauses-café.']],
            ['name' => 'BADGES & LANYARDS', 'price' => '10 000 MAD', 'features' => ['Co-branding sur les badges et/ou tours de cou.']],
            ['name' => 'WELCOME PACK', 'price' => '10 000 MAD', 'features' => ['Insertion d’un document ou objet promotionnel dans le Welcome Pack.']],
            ['name' => 'PARTENAIRE INTERPRÉTATION', 'price' => '60 000 MAD / jour', 'features' => ['Reconnaissance en tant que partenaire linguistique avec visibilité dédiée.']],
        ],
    ],

    'bespoke' => [
        'title' => 'Formules sur mesure',
        'text' => 'Des partenariats en nature ou des dispositifs personnalisés peuvent également être étudiés lorsqu’ils répondent à un besoin identifié de la conférence. Leur valorisation et les contreparties associées sont définies préalablement avec les organisateurs.',
    ],

    'build' => [
        'eyebrow' => 'Construisons votre partenariat',
        'title' => 'Une opération sur mesure',
        'lede' => 'Les opportunités de sponsoring ARABCIA :year sont définies en fonction de vos objectifs de visibilité et de partenariat.',
        'cta' => 'Contacter ARABCIA et IIA Maroc',
    ],

    'dossier' => [
        'title' => 'Téléchargez le dossier de sponsoring ARABCIA :year',
        'lede' => 'Retrouvez toutes les informations sur les opportunités de sponsoring, les modalités de partenariat et les formats de visibilité.',
        'cta' => 'Télécharger le dossier de sponsoring',
    ],

    'roster' => [
        'eyebrow' => 'Sponsors ARABCIA :year',
        'title' => 'Nos sponsors de l’édition :year',
        'lede' => 'Les logos seront affichés au fur et à mesure de leur confirmation.',
        'pending' => 'Logo à venir',
        'empty' => 'Les sponsors de l’édition :year seront annoncés prochainement.',
    ],

    'allies' => [
        'eyebrow' => 'Ils nous ont fait confiance',
        'title' => 'Les partenaires des précédentes éditions',
        'lede' => 'Un grand merci à nos partenaires des éditions précédentes.',
        'empty' => 'La liste des partenaires des éditions précédentes sera communiquée prochainement.',
    ],

    'gallery' => [
        'eyebrow' => 'Retour en images',
        'title' => 'Les moments forts des précédentes éditions',
        'alt' => 'Photographie de la conférence ARABCIA, photo :number',
    ],

    'cta' => [
        'title' => 'Rejoignez-nous',
        'lede' => 'Associez votre organisation à la Conférence annuelle ARABCIA :year.',
        'text' => 'Bénéficiez d’une plateforme privilégiée pour valoriser votre expertise, développer votre visibilité et créer des connexions avec une communauté de décideurs et de professionnels.',
        'button' => 'Devenez partenaire',
    ],

    'rail' => [
        'previous' => 'Éléments précédents',
        'next' => 'Éléments suivants',
    ],
    'tier_platinum' => 'Partenaire Platine',
    'tier_gold' => 'Partenaires Gold',
    'tier_silver' => 'Partenaires Argent',
    'hesitate' => [
        'title' => 'Vous hésitez entre plusieurs formules ?',
        'text' => 'Notre équipe est à votre disposition pour vous orienter vers le partenariat le plus adapté à vos objectifs de visibilité et de développement.',
        'button' => 'Échanger avec notre équipe sponsoring',
    ],
    'form' => [
        'kicker' => 'Devenez partenaire',
        'banner_title' => 'Associez votre marque à un événement d\'envergure internationale',
        'banner_text' => 'Rejoignez les partenaires de la Conférence ARABCIA :year, :dates à :venue. Notre équipe vous accompagne dans le choix de la formule la plus adaptée à vos objectifs.',
        'title' => 'Votre demande de partenariat',
        'lede' => 'Complétez ce formulaire pour être recontacté par notre équipe sponsoring.',
        'company' => 'Entreprise / Organisation',
        'company_ph' => 'Nom de votre organisation',
        'name' => 'Nom et prénom',
        'name_ph' => 'Votre nom complet',
        'email' => 'Adresse e-mail',
        'phone' => 'Téléphone',
        'formula' => 'Formule de sponsoring souhaitée',
        'message' => 'Votre message (facultatif)',
        'message_ph' => 'Précisez vos attentes ou posez vos questions...',
        'consent' => 'J\'accepte que mes coordonnées soient utilisées pour répondre à ma demande de partenariat.',
        'submit' => 'Envoyer',
        'success' => 'Merci pour votre intérêt. Notre équipe prendra contact avec vous prochainement.',
    ],
];
