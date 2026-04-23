<?php

namespace App\Mail;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminReportNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Report $report) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[ReklaMap] Zgłoszenie ogłoszenia #' . $this->report->advertisement_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-report-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
