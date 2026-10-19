<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Administration
|--------------------------------------------------------------------------
|
| The staff area's own strings, deliberately separate from the public ones.
|
| `order.status.*` lives in the order file because the same label is shown to a
| delegate on their own order and to an operator on the list, and two spellings
| of "Payée" would be a support ticket. Everything below is text an operator
| reads and a delegate never does.
|
| This file is organised as three nested groups that mirror the rail: identity
| and chrome, then one group per queue, then the shared table vocabulary. The
| nesting is what keeps `admin.enquiries.status` from reading as a typo for
| `admin.submissions.status`.
*/

return [
    'title' => 'Administration',
    'skip_to_content' => 'Aller au contenu',
    'forbidden' => 'Cette page est réservée à l\'équipe d\'organisation.',

    // Rendered instead of the dashboard when no edition exists yet. Setup is a
    // legitimate state — an empty install should not show an error, and should
    // say plainly what is missing rather than rendering six zeroed cards.
    'no_edition' => [
        'title' => 'Aucune édition configurée',
        'text' => 'Créez une édition avant de pouvoir gérer les inscriptions, le programme et les demandes.',
    ],

    // --- The rail ----------------------------------------------------------
    // Accessible name for the rail. It has to be its own key: 'admin.nav'
    // is a group of labels, and using it as a scalar echoes an array
    // into aria-label, which a screen reader then reads as nothing.
    'nav_label' => 'Navigation de l"administration',

    'nav' => [
        'dashboard' => 'Tableau de bord',
        'orders' => 'Inscriptions',
        'participants' => 'Participants',
        'submissions' => 'Propositions',
        'enquiries' => 'Partenariats',
        'messages' => 'Messages',
        'public_site' => 'Voir le site',
        // `:count` appears after the number so it reads as a sentence in all
        // three languages, including Arabic where the number would otherwise
        // land on the wrong side of the noun.
        'pending' => ':count en attente',
    ],

    // --- The decision queues ----------------------------------------------
    // One per `DashboardController::queues()`. The badge count is rendered by
    // the rail; these strings are its accessible name.
    'queue' => [
        'submissions' => 'propositions de communication à traiter',
        'enquiries' => 'demandes de partenariat à traiter',
        'messages' => 'messages sans réponse',
    ],

    // --- Headline figures --------------------------------------------------
    'stats' => [
        'orders' => 'Inscriptions',
        'paid_orders' => 'dont payées',
        'participants' => 'Participants',
        'checked_in' => 'présents à l\'accueil',
        'paid_rate' => 'Taux de paiement',
        'paid_rate_hint' => ':paid sur :total inscriptions',
        'average_order' => 'Panier moyen',
    ],

    'revenue' => 'Encaissements',

    // --- Registrations over time -------------------------------------------
    'trend' => [
        'title' => 'Inscriptions dans le temps',
        'legend_orders' => 'Inscriptions',
        'legend_paid' => 'Payées',
        'total' => ':count inscriptions au total',
        'empty' => 'Aucune inscription pour le moment.',
    ],

    // --- Member / non-member split -----------------------------------------
    'members' => [
        'title' => 'Adhérents et non-adhérents',
        'member' => 'Adhérents',
        'standard' => 'Non-adhérents',
        'summary' => ':member adhérents · :standard non-adhérents',
        'none' => 'Aucune place vendue pour le moment.',
    ],

    // --- Capacity per tariff -----------------------------------------------
    'capacity' => [
        'title' => 'Remplissage par tarif',
        'sold' => ':sold vendues',
        'sold_of' => ':sold / :max places',
        'remaining' => ':count places restantes',
        'full' => 'Complet',
        'unlimited' => 'Sans limite',
        'empty' => 'Aucun tarif défini pour cette édition.',
    ],

    // --- Where buyers come from --------------------------------------------
    'countries' => [
        'title' => 'Provenance des acheteurs',
        'registrations' => 'Inscriptions',
        'empty' => 'Aucune provenance à afficher pour le moment.',
    ],

    // --- Places sold per tariff --------------------------------------------
    'sales' => [
        'title' => 'Places vendues par tarif',
        'tariff' => 'Tarif',
        'member' => 'Adhérents',
        'standard' => 'Non-adhérents',
        'total' => 'Total',
        'revenue' => 'Encaissé',
        'none' => 'Aucun tarif défini pour cette édition.',
    ],

    'orders_by_status' => 'Inscriptions par statut',

    'recent_orders' => [
        'title' => 'Dernières inscriptions',
        'reference' => 'Référence',
        'buyer' => 'Acheteur',
        'total' => 'Montant',
        'status' => 'Statut',
        'placed_on' => 'Passée le',
        'empty' => 'Aucune inscription pour le moment.',
        'view' => 'Ouvrir',
        'all' => 'Toutes les inscriptions',
    ],

    // --- Orders ------------------------------------------------------------
    'orders' => [
        'index' => [
            'title' => 'Inscriptions',
            'lede' => 'Les inscriptions de l\'édition en cours. Un changement de statut est refusé s\'il n\'est pas autorisé depuis l\'état actuel.',
            'filter' => 'Filtrer par statut',
            'all' => 'Tous les statuts',
            'empty' => 'Aucune inscription ne correspond à ce filtre.',
            'anonymous' => 'Sans compte',
        ],
        'show' => [
            'title' => 'Commande',
            'buyer' => 'Acheteur',
            'contact' => 'Contact',
            'items' => 'Articles',
            'participants' => 'Participants',
            'payments' => 'Paiements',
            'timeline' => 'Historique',
            'reference' => 'Référence',
            'placed_on' => 'Passée le',
            'invoice' => 'Numéro de facture',
            'none' => 'Aucun paiement enregistré.',
            'no_notes' => 'Aucune note.',
        ],
        'notes' => 'Note',
        'change_status' => 'Changer le statut',
        'illegal_transition' => 'Une commande « :from » ne peut pas passer à « :to ».',
        'status_updated' => 'La commande :reference est désormais « :status ».',
    ],

    // --- Speaker submissions -----------------------------------------------
    'submissions' => [
        'title' => 'Propositions de communication',
        'lede' => 'Un refus doit être motivé : la note est jointe au dossier et l\'intervenant peut la lire.',
        'speaker' => 'Intervenant',
        'track' => 'Axe demandé',
        'session' => 'Titre proposé',
        'outline' => 'Plan de la communication',
        'submitted_on' => 'Reçue le',
        'review_notes' => 'Notes de revue',
        'review' => 'Décider',
        'notes' => 'Notes',
        'status' => 'Décision',
        'notes_required' => 'Une note est obligatoire pour motiver cette décision.',
        'updated' => 'La proposition a été mise à jour.',
        'any_track' => 'Sans axe demandé',
        'empty' => 'Aucune proposition ne correspond à ce filtre.',
    ],

    // --- Sponsorship enquiries ---------------------------------------------
    'enquiries' => [
        'title' => 'Demandes de partenariat',
        'lede' => 'Une demande n\'est pas une commande : il n\'y a rien à encaisser, seulement un contact à mener.',
        'company' => 'Société',
        'contact' => 'Contact',
        'package' => 'Formule souhaitée',
        'received' => 'Reçue le',
        'handler' => 'Traité par',
        'unassigned' => 'Non attribué',
        'stage' => 'Étape',
        'notes' => 'Notes',
        'status' => 'Étape',
        'reason_required' => 'Une note est obligatoire pour clore une demande perdue.',
        'updated' => 'La demande de :company a été mise à jour.',
        'no_package' => 'Aucune formule',
        'empty' => 'Aucune demande ne correspond à ce filtre.',
    ],

    // --- Contact messages ---------------------------------------------------
    'messages' => [
        'title' => 'Messages',
        'lede' => 'Aucun mail n\'est envoyé depuis cette page : « répondre » enregistre la réponse dans le dossier, il ne l\'expédie pas.',
        'from' => 'Expéditeur',
        'subject' => 'Sujet',
        'body' => 'Message',
        'received' => 'Reçu le',
        'handler' => 'Traité par',
        'unassigned' => 'Non attribué',
        'notes' => 'Notes internes',
        'status' => 'Statut',
        'answer' => 'Marquer comme répondu',
        'updated' => 'Le message a été mis à jour.',
        'wrote_in' => 'A écrit en',
        'empty' => 'Aucun message ne correspond à ce filtre.',
    ],

    // --- Participants / the door list ---------------------------------------
    'participants' => [
        'title' => 'Participants',
        'lede' => 'La liste utilisée à l\'accueil. La recherche porte sur le nom, le badge, le courriel et le téléphone.',
        'search' => 'Rechercher',
        'placeholder' => 'Nom, courriel ou téléphone…',
        'search_hint' => 'Deux caractères au minimum.',
        'name' => 'Nom',
        'badge' => 'Badge',
        'job_title' => 'Fonction',
        'status' => 'Arrivée',
        'checked_in' => 'Présent',
        'not_checked_in' => 'Non pointé',
        'check_in' => 'Pointer',
        'check_out' => 'Annuler le pointage',
        'checked_in_done' => ':name a été pointé.',
        'checked_out_done' => 'Le pointage de :name a été annulé.',
        'empty' => 'Aucun participant ne correspond.',
        'member' => 'Adhérent',
        'total' => 'Participants affichés',
    ],

    // --- Status vocabulary for the three queues ----------------------------
    // Reused by the queue filters and the tables. Prefixed per queue rather than
    // shared, because "contacter" means one thing for a lead and another for a
    // submitted talk.
    'stages' => [
        'submissions' => [
            'submitted' => 'Reçue',
            'under_review' => 'En cours d\'examen',
            'accepted' => 'Acceptée',
            'waitlisted' => 'En liste d\'attente',
            'rejected' => 'Refusée',
            'withdrawn' => 'Retirée',
        ],
        'enquiries' => [
            'new' => 'Nouvelle',
            'contacted' => 'Contactée',
            'quoted' => 'Devis envoyé',
            'won' => 'Gagnée',
            'lost' => 'Perdue',
        ],
        'messages' => [
            'new' => 'Nouveau',
            'open' => 'En cours',
            'answered' => 'Répondu',
            'spam' => 'Indésirable',
        ],
        'subjects' => [
            'registration' => 'Inscription',
            'sponsoring' => 'Partenariat',
            'speaker' => 'Communication',
            'press' => 'Presse',
            'other' => 'Autre',
        ],
        // Payment states live here rather than beside `order.status.*` because
        // the public payment page never shows them: a delegate sees "your
        // payment is confirmed", not "Captured". These are gateway vocabulary
        // for the operator reconciling against a bank statement.
        //
        // The keys are the `PaymentStatus` backing values, so a state added to
        // the enum shows up as missing rather than as an untranslated literal.
        'payment_status' => [
            'pending' => 'En attente',
            'authorised' => 'Autorisé',
            'captured' => 'Encaissé',
            'settled' => 'Réglé',
            'failed' => 'Échec',
            'cancelled' => 'Annulé',
            'refunded' => 'Remboursé',
            'disputed' => 'Contesté',
        ],
    ],
];
