<?php

namespace App\Rules;

use App\Services\ProfanityFilter;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ProfanityRule implements ValidationRule
{
    protected $profanityFilter;

    public function __construct()
    {
        $this->profanityFilter = new ProfanityFilter();
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->profanityFilter->containsProfanity($value)) {
            $fail('Pole :attribute zawiera niedozwolone słowa.');
        }
    }
}
