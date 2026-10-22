<?php

declare(strict_types=1);

return [
    'orders' => 'Mes inscriptions',
    'totp_not_enrolled' => 'Application à configurer',
    'totp_enrolled' => 'Application configurée',
    'totp_label' => 'Application d\'authentification',
    'profile' => 'Profil',
    'title' => 'Mon compte',

    // L'état de confirmation de l'adresse e-mail, affiché à côté de l'adresse.
    // Il relève de `account` et non du fichier TOTP : il concerne l'e-mail, il se
    // trouve simplement sur la même page.
    'email_confirmed' => 'Cette adresse est confirmée.',
    'email_pending' => 'Cette adresse n\'est pas encore confirmée. Confirmez-la depuis le lien qui vous a été envoyé.',

    // --- La page de modification ---------------------------------------------
    //
    // `profile` ci-dessus est l'intitulé du lien *vers* cette page ; les clés
    // ci-dessous sont la page elle-même.

    'edit_title' => 'Modifier mon profil',
    'edit_lede' => 'Vos coordonnées figurent sur votre badge et sur vos factures. Gardez-les à jour.',
    'edit_details' => 'Mes coordonnées',
    'edit_details_lede' => 'Ces informations vous identifient sur place et sur chaque facture émise pour vos inscriptions.',
    'edit_save' => 'Enregistrer mes coordonnées',

    'edit_password' => 'Mot de passe',
    'edit_password_lede' => 'Choisissez un mot de passe que vous n\'utilisez nulle part ailleurs. Vous resterez connecté sur cet appareil.',
    'current_password' => 'Mot de passe actuel',
    'new_password' => 'Nouveau mot de passe',
    'confirm_password' => 'Confirmer le nouveau mot de passe',
    'password_submit' => 'Changer mon mot de passe',
    'current_password_wrong' => 'Ce n\'est pas votre mot de passe actuel.',
    'password_same' => 'Le nouveau mot de passe doit être différent de l\'actuel.',

    'profile_saved' => 'Vos coordonnées ont été enregistrées.',
    'password_saved' => 'Votre mot de passe a été modifié.',
    'email_reverify' => 'Nous vous avons envoyé un lien pour confirmer votre nouvelle adresse e-mail. Vos inscriptions ne sont pas affectées.',

    // --- Le tableau de bord -------------------------------------------------
    //
    // `lede` est le chapeau du tableau de bord ; `edit_lede` ci-dessus est celui
    // du formulaire. Deux pages, deux résumés.

    'lede' => 'Vos inscriptions, votre profil et l\'état de votre compte au même endroit.',
    'summary' => 'En un coup d\'œil',
    'summary_lede' => 'L\'état de votre compte. Les deux premières conditions sont vérifiées à chaque connexion ; la dernière est la vôtre.',

    'registrations_label' => 'Inscriptions',
    'email_label' => 'Adresse e-mail',

    'checklist_title' => 'Avant de pouvoir vous inscrire',
    'checklist_lede' => 'Trois conditions, vérifiées avant le paiement. Les deux premières se règlent en quelques minutes ; la troisième est un simple accord de votre part.',
    'checklist_done' => 'Tout est prêt : vous pouvez vous inscrire.',
    'step_email_title' => 'Confirmer votre adresse e-mail',
    'step_email_text' => 'Un lien de confirmation vous a été envoyé. Sans lui, aucun justificatif ne peut vous être envoyé.',
    'step_totp_title' => 'Configurer votre application d\'authentification',
    'step_totp_text' => 'Un code à six chiffres, vérifié à chaque connexion. C\'est ce qui remplace l\'SMS payant utilisé auparavant.',
    'step_terms_title' => 'Accepter les conditions de participation',
    'step_terms_text' => 'Le règlement, la politique de confidentialité et les conditions d\'annulation. Votre acceptation est horodatée.',
    'step_pending' => 'À faire',
    'step_done' => 'Fait',
    'step_action' => 'Commencer',
    'terms_label' => 'J\'accepte les conditions de participation',
    'terms_link' => 'Lire les conditions',
    'terms_accepted' => 'Conditions acceptées : votre compte peut maintenant passer commande.',
    'terms_not_accepted' => 'Vous n\'avez pas accepté les conditions. Cochez la case pour le faire.',
    'terms_accepted_on' => 'Acceptées le :date',

    'profile_lede' => 'Ces informations figurent sur votre badge et sur chaque facture.',
    'orders_lede' => 'Vos inscriptions, de la plus récente à la plus ancienne.',
    'order_places' => ':count place|:count places',
    'order_no_places' => 'Aucune place',
    'actions_title' => 'Raccourcis',
    'logout_confirm' => 'Se déconnecter de cet appareil ?',
    'promo_title'  => 'Votre code promo',
    'promo_lede'   => 'Partagez ce code pour gagner des récompenses. Il reste le même pour tout votre compte.',
    'promo_copy'   => 'Copier',
    'promo_copied' => 'Copié !',
];
