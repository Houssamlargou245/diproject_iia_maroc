<?php

declare(strict_types=1);

return [
    'orders' => 'My registrations',
    'totp_not_enrolled' => 'App not set up',
    'totp_enrolled' => 'App set up',
    'totp_label' => 'Authenticator app',
    'profile' => 'Profile',
    'title' => 'My account',

    // The confirmation state of the email address, next to the address itself.
    // It belongs in the account namespace rather than in the TOTP file: it is
    // about the email, it just happens to be read on the same page.
    'email_confirmed' => 'This address is confirmed.',
    'email_pending' => 'This address is not confirmed yet. Confirm it from the link we sent you.',

    // --- Editing the profile -------------------------------------------------
    //
    // 'profile' above is the label on the link *to* this page; the keys below
    // are the page itself.

    'edit_title' => 'Edit my profile',
    'edit_lede' => 'Your details appear on your badge and on the invoice. Keep them current.',
    'edit_details' => 'My details',
    'edit_details_lede' => 'These details identify you on site and on every invoice issued for your registrations.',
    'edit_save' => 'Save my details',

    'edit_password' => 'Password',
    'edit_password_lede' => 'Choose something you do not use anywhere else. You will stay signed in on this device.',
    'current_password' => 'Current password',
    'new_password' => 'New password',
    'confirm_password' => 'Confirm the new password',
    'password_submit' => 'Change my password',
    'current_password_wrong' => 'That is not your current password.',
    'password_same' => 'The new password must be different from the current one.',

    'profile_saved' => 'Your details have been saved.',
    'password_saved' => 'Your password has been changed.',
    'email_reverify' => 'We have sent you a link to confirm your new email address. Your registrations are unaffected.',

    // --- The dashboard -------------------------------------------------------
    //
    // 'lede' is the dashboard's standfirst; 'edit_lede' above belongs to the
    // form. Two pages, two summaries.

    'lede' => 'Your registrations, your profile and the state of your account in one place.',
    'summary' => 'At a glance',
    'summary_lede' => 'The state of your account. The first two requirements are checked on every sign-in; the last one is yours to give.',

    'registrations_label' => 'Registrations',
    'email_label' => 'Email address',

    'checklist_title' => 'Before you can register',
    'checklist_lede' => 'Three requirements, all checked before payment. The first two take a few minutes; the third is simply your agreement.',
    'checklist_done' => 'Everything is ready: you can register.',
    'step_email_title' => 'Confirm your email address',
    'step_email_text' => 'We sent you a confirmation link. Without it we cannot send you any receipt.',
    'step_totp_title' => 'Set up your authenticator app',
    'step_totp_text' => 'A six-digit code, checked on every sign-in. This replaces the paid SMS code used before.',
    'step_terms_title' => 'Accept the participation terms',
    'step_terms_text' => 'The regulations, the privacy policy and the cancellation terms. Your acceptance is timestamped.',
    'step_pending' => 'To do',
    'step_done' => 'Done',
    'step_action' => 'Start',
    'terms_label' => 'I accept the participation terms',
    'terms_link' => 'Read the terms',
    'terms_accepted' => 'Terms accepted: your account can now place an order.',
    'terms_not_accepted' => 'You have not accepted the terms. Tick the box to do so.',
    'terms_accepted_on' => 'Accepted on :date',

    'profile_lede' => 'These details appear on your badge and on every invoice.',
    'orders_lede' => 'Your registrations, most recent first.',
    'order_places' => ':count place|:count places',
    'order_no_places' => 'No places',
    'actions_title' => 'Shortcuts',
    'logout_confirm' => 'Sign out of this device?',
    'promo_title'  => 'Your promo code',
    'promo_lede'   => 'Share this code to earn rewards. It stays the same for your whole account.',
    'promo_copy'   => 'Copy',
    'promo_copied' => 'Copied!',
];
