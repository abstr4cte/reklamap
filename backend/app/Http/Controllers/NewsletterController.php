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
            return redirect(config('app.frontend_url') . '/?blad=newsletter-token');
        }

        $subscriber->delete();

        return redirect(config('app.frontend_url') . '/?wypisano=newsletter');
    }
}
