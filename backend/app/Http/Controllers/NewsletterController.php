<?php

namespace App\Http\Controllers;

use App\Models\Newsletter;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    /**
     * Unsubscribe from newsletter.
     */
    public function unsubscribe($token)
    {
        $subscriber = Newsletter::where('unsubscribe_token', $token)->first();

        if (!$subscriber) {
            return "Nieprawidłowy lub wygasły token subskrypcji.";
        }

        $subscriber->delete();

        return "Twój adres e-mail został pomyślnie usunięty z listy subskrybentów newslettera ReklaMap.";
    }
}
