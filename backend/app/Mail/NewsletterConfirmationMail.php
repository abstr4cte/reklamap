<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $unsubscribeToken;

    public function __construct(string $unsubscribeToken)
    {
        $this->unsubscribeToken = $unsubscribeToken;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Zapisano do newslettera ReklaMap!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.newsletter-confirmation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
