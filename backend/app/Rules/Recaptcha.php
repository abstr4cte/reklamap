<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Recaptcha implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!config('services.recaptcha.secret')) {
            return;
        }

        if (!$value) {
            $fail('Weryfikacja reCAPTCHA jest wymagana.');
            return;
        }

        $response = \Illuminate\Support\Facades\Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('services.recaptcha.secret'),
            'response' => $value,
        ]);

        $data = $response->json();

        if (!$data['success'] || ($data['score'] ?? 1) < 0.5) {
            $fail('Weryfikacja reCAPTCHA nie powiodła się. Jesteś botem? ;)');
        }
    }
}
