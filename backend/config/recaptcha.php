<?php

return [
    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Google reCAPTCHA v3
    | Get your keys from: https://www.google.com/recaptcha/admin
    |
    */

    'secret' => env('RECAPTCHA_SECRET_KEY'),
    'site_key' => env('VITE_RECAPTCHA_SITE_KEY'),
];
