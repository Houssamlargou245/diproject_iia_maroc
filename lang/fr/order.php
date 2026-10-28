<?php

declare(strict_types=1);

/**
 * Order lifecycle labels.
 *
 * `OrderStatus::label()` reads `order.status.{value}`, and the view calls that
 * method rather than interpolating the key itself, so a new enum case without a
 * label here is visible in one place instead of leaking a raw key into a
 * customer's order history.
 *
 * French and Arabic are gendered for "inscription" (feminine), which is why
 * these read as adjectives agreeing with it rather than as bare participles.
 */
return [
    'status.awaiting_payment' => 'Paiement en cours',
    'status.cancelled' => 'Annulée',
    'status.expired' => 'Expirée',
    'status.failed' => 'Échec du paiement',
    'status.paid' => 'Payée',
    'status.partially_refunded' => 'Partiellement remboursée',
    'status.pending' => 'En attente',
    'status.refunded' => 'Remboursée',

    // Column and form labels for the account and admin screens.
    'reference' => 'Référence',
    'placed_on' => 'Passée le',
    'address' => 'Adresse',
    'amount' => 'Montant',
    'no_orders' => 'Aucune inscription pour le moment.',
    'pay_now' => 'Payer maintenant',
    'download_invoice' => 'Télécharger la facture',
    'phone_international' => 'Format international, par exemple +212 6 12 34 56 78 ou +966 5 12 34 56 78.',

    // --- Checkout ---------------------------------------------------------
    'title' => 'Inscription à la conférence',
    'ticket' => 'Tarif',
    'quantity' => 'Quantité',
    'member_places' => 'Places adhérent',
    'member_places_exceed' => 'Le nombre de places adhérent ne peut pas dépasser le nombre de places.',
    'participants' => 'Participants',
    'participant_name' => 'Nom et prénom',
    'participant_number' => 'Participant :number',
    'participant_help' => 'Renseignez une fiche par place réservée. Le tarif adhérent appliqué est celui que votre statut d\'adhérent actif justifie.',
    'seats_help' => 'Ajoutez ou retirez une place avant de renseigner les fiches. Le total est recalculé à chaque changement.',
    'seats_for' => 'Places pour :ticket',
    'add_participant' => 'Ajouter un participant',
    'remove_participant' => 'Retirer le dernier participant',
    'participant_added' => 'Participant ajouté.',
    'participant_removed' => 'Participant retiré.',
    'participant_count' => ':count participants',
    'participant_count_one' => '1 participant',
    'min_one_place' => 'Une place au minimum doit rester sur chaque tarif.',
    'max_places_reached' => 'Vous ne pouvez pas réserver plus de :max places sur un même tarif.',
    'cart_line_missing' => 'Ce tarif n\'est plus dans votre panier.',
    'items' => 'Détail de la commande',
    'total' => 'Total',
    'member_saving_applied' => 'Le tarif adhérent a été appliqué à :count place(s).',
    'participant_count_mismatch' => 'Vous avez réservé :expected places, mais :count formulaires de participants ont été soumis.',
    'participant_mismatch' => 'Le nombre de participants (:received) ne correspond pas au nombre de places réservées (:expected).',
    'billing_details' => 'Coordonnées de facturation',
    'place_order' => 'Valider et payer',
    'email_receipt' => 'Un e-mail de confirmation vous sera envoyé.',
    'summary' => 'Récapitulatif',
    'status' => 'Statut',

    // --- Invoice ---------------------------------------------------------
    // An order paid for before its seats were named: the attendee list is
    // collected by the organiser afterwards, so an empty one is expected
    // rather than a fault, and the page has to say so instead of showing
    // nothing under a heading.
    'participants_pending' => 'Les participants seront enregistrés avant la conférence. Pour les ajouter, contactez-nous.',

    // Closing band on the invoice: the purchase is finished, and what the
    // delegate wants next is the thing the ticket is for.
    'cta_lede' => 'Votre inscription est enregistrée. Retrouvez le programme de la conférence et les informations pratiques sur le lieu.',

    // Printed from the browser. The PDF download is the document to keep; this
    // is for the delegate who needs a copy today and would rather not open a
    // reader.
    'print' => 'Imprimer',
    'confirm_and_pay' => 'Confirmer la commande',
    'print_hint' => 'Imprime cette page en PDF depuis la fenêtre d’impression de votre navigateur.',

    // Shown before payment. The order can be opened, printed and paid at this
    // point; what does not exist yet is the *facture*, because issuing a
    // numbered invoice for money that has not arrived would put a document in
    // circulation the bank will not honour. The delegate can still print this
    // page as a receipt, so the wording promises exactly that.
    'unpaid_notice' => 'Cette inscription n’est pas encore réglée. Vous pouvez l’imprimer comme reçu et la payer maintenant ; la facture numérotée sera disponible après le paiement.',

    // --- The invoice document ----------------------------------------------
    // The heading, totals block and reassurance strip of the invoice page.
    // `invoice_heading` takes the reference as a :reference placeholder rather
    // than composing it in the view, so each language can place it in its own
    // word order — French and Arabic put it differently.
    'invoice_heading' => 'Facture numéro :reference',
    'paid_to' => 'Payé à',
    'payment_method' => 'Mode de paiement',
    'payment_method_cmi' => 'Cartes marocaines et internationales (CMI)',
    'subtotal' => 'Sous-total',
    'member_saving' => 'Remise adhérent',
    'email' => 'E-mail',
    'phone' => 'Téléphone',
    'contact_us' => 'Contacter ARABCIA',
    'vat' => 'TVA',
    'total_incl' => 'Total TTC',
    'offer' => 'Nom de l’offre',
    'participant_name' => 'Nom des participants',
    'date' => 'Date',
    'price_incl' => 'Prix TTC',
    'download_invoice_short' => 'Imprimer / Télécharger',
    'help_title' => 'Besoin d’aide ?',
    'help_text' => 'Pour toute question concernant votre commande ou le paiement, contactez l’équipe ARABCIA.',
    'assurances' => [
        'secure' => 'Paiement sécurisé',
        'secure_text' => 'Vos transactions sont protégées et sécurisées par CMI.',
        'cards' => 'Cartes marocaines et internationales',
        'cards_text' => 'Paiement par cartes bancaires marocaines et internationales (CMI).',
        'instant' => 'Facture instantanée',
        'instant_text' => 'Recevez votre facture immédiatement après votre paiement.',
        'support' => 'Assistance',
        'support_text' => 'Notre équipe reste à votre disposition en cas de besoin.',
    ],
    'sign_in_to_checkout' => 'Connectez-vous pour finaliser votre inscription.',
    'enroll_to_checkout' => 'Configurez votre application d’authentification avant de finaliser votre inscription.',
    'not_payable' => 'Cette commande ne peut plus être payée.',
    'capacity_reached' => 'Le nombre de places est atteint (:capacity places, :taken déjà réservées). Merci de nous contacter pour une inscription sur liste d\'attente.',

    // --- Cart -------------------------------------------------------------
    'orders' => 'Mes commandes',
    'cart_empty' => 'Votre panier est vide.',
    'cart_updated' => 'Votre panier a été mis à jour.',
    'cart_emptied' => 'Votre panier a été vidé.',

    'cart' => [
        'add' => 'Ajouter au panier',
        'title' => 'Mon panier',
        'subtitle' => 'Vérifiez les places que vous souhaitez réserver avant de poursuivre.',
        'estimate' => 'Total indicatif',
        'estimate_note' => 'Ce montant est indicatif. Le total définitif, qui tient compte de votre qualité d\'adhérent, est calculé à l\'étape suivante.',
        'summary_note' => 'Les participants sont renseignés à l\'étape suivante.',
        'continue' => 'Continuer mes inscriptions',
        'clear' => 'Vider le panier',
        'checkout' => 'Passer la commande',
        'sign_in_note' => 'Vous serez invité à vous connecter avant le paiement. Votre panier sera conservé.',

        // Point d'entrée vers la liste complète des inscriptions/paiements de
        // l'utilisateur connecté, affiché sur le panier.
        'my_payments' => 'Mes paiements',
        'my_payments_note' => 'Retrouvez vos commandes et l\'état de vos paiements.',

        // Le récapitulatif imprimable et téléchargeable du panier.
        //
        // Le panier est précisément l'endroit où ce document est utile : c'est
        // avant de commander qu'un delegate d'entreprise doit encore obtenir
        // l'accord de son service achats. D'où le titre « récapitulatif » et non
        // « facture » ; voir order.invoice.proforma_* pour le même motif côté
        // commande.

        'places' => ':count place',
        'places_plural' => ':count places',

        'member_places' => ':count place adhérent',
        'member_places_plural' => ':count places adhérent',

        'invoice' => 'Récapitulatif de commande',
        'invoice_lede' => 'Téléchargez ou imprimez un récapitulatif de votre panier pour votre service achats ou votre direction financière.',
        'invoice_download' => 'Télécharger le PDF',
        'invoice_print' => 'Imprimer le récapitulatif',
        'invoice_hint' => 'Document provisoire et non numéroté : il ne tient pas lieu de facture.',
        'payment_support' => "Si vous rencontrez des difficultés pour effectuer votre paiement sur la plateforme, nous vous invitons à nous contacter par e-mail :\n\nArab CIA : info@arabciia.org\nIia Maroc: Info@iiamaroc.org\n\nNous vous répondrons dans les plus brefs délais.",
    ],

    // --- Payment ----------------------------------------------------------
    'payment' => [
        'confirmed' => 'Paiement confirmé. Votre facture est disponible dans vos commandes.',
        'failed' => 'Le paiement n\'a pas abouti.',
        'generic_failure' => 'Le paiement a été refusé.',
        'pending' => 'Paiement en attente de confirmation par la banque.',
        'pending_note' => 'Si vous avez payé, votre inscription sera confirmée dans quelques instants. Cette page se met à jour automatiquement.',
        'cancelled' => 'Paiement annulé.',
        'cancelled_note' => 'Votre commande n\'a pas été débitée. Vous pouvez reprendre le paiement à tout moment.',
        'retry' => 'Reprendre le paiement',
        'secure' => 'Paiement sécurisé par CMI.',
        'last_attempt' => 'Dernière tentative de paiement',
        'test_title' => 'Page de test du moyen de paiement',
        'test_banner' => 'Environnement de test — aucun paiement réel ne sera effectué.',
        'test_intro' => 'Cette page permet de rejouer le parcours de paiement complet sans carte bancaire.',
        'test_approve' => 'Simuler un paiement accepté',
        'test_decline' => 'Simuler un paiement refusé',
        'test_note' => 'Les deux boutons envoient une charge signée au vrai point de callback, afin que le chemin de production soit réellement testé.',
    ],

    'invoice' => [
        'not_available' => 'Cette commande est close : aucune facture ne peut être émise.',
        'title' => 'Facture n° :number',
        'billed_to' => 'Facturé à',
        'details' => 'Informations',
        'issued_on' => 'Date d\'émission',
        'method' => 'Mode de paiement',
        'cmi' => 'Cartes marocaines et internationales (CMI)',
        'subtotal' => 'Sous-total',
        'discount' => 'Remise',
        'tax' => 'TVA',
        'rate' => 'Tarif',
        'footer' => 'Document généré par :organiser. Toute demande de modification doit être adressée à l\'organisation.',

        // --- La facture provisoire -----------------------------------------
        // Le message que la facture porte quand la commande n'est pas encore
        // réglée. Un delegate entreprise a besoin d'un document *avant* de payer
        // — un service achats ne libère pas de fonds sur la capture d'un
        // panier — mais ce document ne peut pas être une facture numérotée : un
        // numéro de facture est une pièce comptable, et émettre un numéro pour
        // une somme non encaissée met en circulation un papier que la banque
        // n'honorera pas. D'où le titre « provisoire », l'absence de numéro, et
        // un avertissement qui interdit explicitement de s'en servir comme
        // preuve de règlement.

        // 'proforma_title' => 'Facture provisoire',
        'proforma_banner_title' => 'Document provisoire — commande non réglée',
        'proforma_banner_text' => 'Ce document n\'est pas une facture. Il récapitule votre commande à titre indicatif ; la facture numérotée et définitive n\'est émise qu\'après le règlement.',
        'proforma_notes_title' => 'À savoir',
        'proforma_note_estimate' => 'Les montants sont indicatifs. Le tarif adhérent n\'est appliqué qu\'aux participants rapprochés d\'un enregistrement d\'adhésion actif au moment de la commande.',
        'proforma_note_issue' => 'Aucun numéro de facture n\'a été attribué à ce document, qui ne tient pas lieu de facture.',
        'billed_to_pending' => 'À compléter lors de la commande.',
        'paid_title' => 'FACTURE ACQUITTÉE',
    'proforma_title' => 'PRO FORMA INVOICE',
    'number' => 'Numéro de facture',
    'issue_date' => 'Date d\'émission',
    'issuer' => 'Émetteur',
    'bill_to' => 'Destinataire de la facturation',
    'conference_title' => 'Conférence annuelle ARABCIA 2026',
    'conference_venue' => '16 et 17 décembre 2026 · Rabat · Four Seasons Hotel at Kasr Al Bahr',
    'participant' => 'Participant',
    'description' => 'Désignation',
    'unit_price' => 'Prix unitaire',
    'total_estimate' => 'TOTAL ESTIMÉ',
    'total_paid' => 'TOTAL PAYÉ',
    'payment_method' => 'Mode de paiement',
    'payment_cmi' => 'Paiement par carte bancaire via CMI.',
    'questions_title' => 'Une question concernant votre facture ?',
    'questions_text' => 'Pour toute question ou demande de correction, veuillez contacter :',
    'questions_note' => 'Merci de préciser votre nom et le numéro de votre facture.',
    'payment_details' => 'Informations de paiement',
    'payment_date' => 'Date du paiement',
    'transaction_ref' => 'Référence de transaction',
    'payment_received' => 'PAIEMENT REÇU — SOLDE À RÉGLER',
    'confirmation_text' => 'Cette facture acquittée confirme le règlement de votre inscription à la conférence ARABCIA 2026. Elle est émise par IIA Maroc.',
    'footer_text' => 'Document émis par IIA Maroc. Pour toute demande de correction, veuillez contacter IIA Maroc.',
    ],

    // --- Mail -------------------------------------------------------------
    'mail' => [
        'paid_subject' => 'ARABCIA 2026 — Inscription confirmée (:reference)',
        'failed_subject' => 'ARABCIA 2026 — Paiement non abouti (:reference)',
        'paid_intro' => "Bonjour,\n\nVotre inscription à la conférence ARABCIA 2026 est confirmée. Nous avons bien enregistré votre paiement.",
        'failed_intro' => "Bonjour,\n\nLe paiement de votre inscription à la conférence ARABCIA 2026 n\'a pas abouti.",
        'invoice_attached' => 'La facture est jointe à ce message.',
        'invoice_online' => 'Vous pourrez télécharger votre facture depuis votre espace « Mes commandes ».',
        'failed_action' => 'Vous pouvez reprendre le paiement à tout moment depuis votre espace « Mes commandes ».',
        'footer' => 'Cordialement, l\'organisation ARABCIA 2026.',
    ],

    // --- Cancellation -----------------------------------------------------
    // A paid order is refunded, not cancelled. The wording is explicit
    // because "annulée" on a paid order would suggest the money had gone
    // back when nothing of the kind has happened.
    'cancel' => [
        'done' => 'Votre commande a été annulée.',
        'already' => 'Cette commande est déjà close.',
        'paid' => 'Cette commande est payée et ne peut pas être annulée. Contactez l\'organisation pour un remboursement.',
        'action' => 'Annuler la commande',
        'confirm' => 'Confirmer l\'annulation de cette commande ?',
    ],
];
