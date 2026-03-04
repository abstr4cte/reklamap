<?php

namespace App\Mail;

use App\Models\Advertisement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SearchAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ad;
    public $unsubscribeToken;
    public $adUrl;
    public $imageUrl;
    public $typeLabel;

    /**
     * Create a new message instance.
     */
    public function __construct(Advertisement $ad, string $unsubscribeToken)
    {
        $this->ad = $ad;
        $this->unsubscribeToken = $unsubscribeToken;

        $typeFormat = $this->mapTypeToUrlFormat($ad->type);
        $this->adUrl = config('app.frontend_url') . "/powierzchnia-reklamowa/{$typeFormat}/" . \Illuminate\Support\Str::slug($ad->city) . "/" . \Illuminate\Support\Str::slug($ad->title) . "-" . $ad->id;

        // Handle Image URL
        if ($ad->image_url) {
            if (str_starts_with($ad->image_url, 'http')) {
                $this->imageUrl = $ad->image_url;
            } else {
                $this->imageUrl = config('app.url') . '/storage/' . $ad->image_url;
            }
        }

        $this->typeLabel = $this->mapToTypeLabel($ad->type);
    }


    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pojawiła się nowa oferta: ' . $this->ad->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.search-alert',
        );
    }

    /**
     * Helper to map ad type to URL format (mirroring AdController).
     */
    private function mapTypeToUrlFormat($type)
    {
        $typeMapping = [
            'billboard' => 'billboardy',
            'citylight' => 'citylighty',
            'led_screen' => 'ekrany-led',
            'banner' => 'banery',
            'wall' => 'sciany-reklamowe',
            'totem' => 'totemy-reklamowe',
            'transport' => 'reklama-w-transporcie',
            'mobile' => 'reklama-mobilna',
            'other' => 'inne'
        ];

        return $typeMapping[$type] ?? 'inne';
    }

    private function mapToTypeLabel($type)
    {
        $labels = [
            'billboard' => 'Billboard',
            'citylight' => 'Citylight',
            'led_screen' => 'Ekran LED',
            'banner' => 'Baner',
            'wall' => 'Ściana reklamowa',
            'totem' => 'Totem',
            'transport' => 'Reklama w transporcie',
            'mobile' => 'Reklama mobilna',
            'other' => 'Inne'
        ];

        return $labels[$type] ?? 'Oferta';
    }
}

