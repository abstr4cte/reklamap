<?php

namespace App\Mail;

use App\Models\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminFeedbackNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Feedback $feedback) {}

    public function envelope(): Envelope
    {
        $typeLabel = match ($this->feedback->type) {
            'bug'        => 'Błąd',
            'suggestion' => 'Sugestia',
            'question'   => 'Pytanie',
            default      => $this->feedback->type,
        };

        return new Envelope(
            subject: '[ReklaMap] Nowy feedback: ' . $typeLabel . ' od ' . $this->feedback->email,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-feedback-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
