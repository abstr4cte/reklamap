<?php

namespace App\Mail;

use App\Models\Advertisement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewAdvertisementNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Advertisement $ad) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[ReklaMap] Nowe ogłoszenie: ' . $this->ad->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-advertisement-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
