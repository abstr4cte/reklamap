<?php

namespace App\Mail;

use App\Models\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeedbackConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Feedback $feedback,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Dziękujemy za Twoje zgłoszenie — ReklaMap',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.feedback-confirmation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
