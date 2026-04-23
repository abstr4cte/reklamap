<?php

namespace App\Mail;

use App\Models\Advertisement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdRemovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Advertisement $ad,
        public string $reason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Twoje ogłoszenie zostało usunięte — ReklaMap',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ad-removed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
