<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SponsorshipRequestMail extends Mailable
{
    public function __construct(public array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->data['email'], $this->data['name'])],
            subject: 'Demande de partenariat ARABCIA : '.$this->data['company'],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.sponsorship-request');
    }
}
