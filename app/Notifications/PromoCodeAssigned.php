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
        return (new MailMessage)
            ->subject('Bienvenue chez IAA Maroc - Votre Code Promo')
            ->greeting('Bonjour ' . $notifiable->first_name . ',')
            ->line('Merci de vous être inscrit sur notre plateforme.')
            ->line('Voici votre code promo personnel. Il restera le même pour tout votre compte.')
            ->line('**' . $this->promoCode . '**') // The code itself
            ->action('Accéder à mon profil', url('/compte'))
            ->line('Merci de votre confiance !');
    }
}