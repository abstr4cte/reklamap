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

        return redirect(config('app.frontend_url') . '/?wypisano=newsletter');
    }
}
