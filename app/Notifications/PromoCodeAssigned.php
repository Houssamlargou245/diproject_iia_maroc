<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PromoCodeAssigned extends Notification
{
    use Queueable;

    public function __construct(
        public string $promoCode
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
   {
    $profileUrl = url('/compte');

    return (new MailMessage)
        ->subject('Bienvenue chez ARABCIA 2026 — Votre code promo personnel')
        ->greeting('Bonjour ' . $notifiable->first_name . ',')
        ->line('Nous vous remercions pour votre inscription sur notre plateforme !')
        ->line('Voici votre code promo personnel, qui restera le même pour votre compte.')
        ->line('**' . $this->promoCode . '**')
        ->line('Merci pour votre confiance!')
        ->line('Cordialement,')
        ->line('ARABCIA 2026')
        ->action('Accéder à mon profil', $profileUrl)
        ->line('Si vous rencontrez des difficultés pour cliquer sur le bouton « Accéder à mon profil », copiez et collez le lien ci-dessous dans votre navigateur :')
        ->line($profileUrl);
}
}