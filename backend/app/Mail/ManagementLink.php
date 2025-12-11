<?php

namespace App\Mail;

use App\Models\ManagementToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ManagementLink extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The token instance.
     *
     * @var \App\Models\ManagementToken
     */
    public $token;

    /**
     * Create a new message instance.
     */
    public function __construct(ManagementToken $token)
    {
        $this->token = $token;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Link do zarządzania ogłoszeniami - ReklaMap',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.management-link',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
