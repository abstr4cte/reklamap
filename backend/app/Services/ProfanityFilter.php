<?php

namespace App\Services;

class ProfanityFilter
{
    protected array $profaneWords = [
        'kurw',
        'jeb',
        'chuj',
        'pizd',
        'pierdol',
        'spierdalaj',
        'cipa',
        'dupa',
        'skurwysyn',
        'cholera',
        'gówno',
    ];

    protected array $allowedWords = [
        'kurator',
        'kuracja',
        'skurcz',
        'pisklę',
        'chujnia',
    ];

    public function containsProfanity(string $text): bool
    {
        $normalizedText = $this->normalizeText($text);
        $words = explode(' ', $normalizedText);

        foreach ($words as $word) {
            if ($this->isProfane($word)) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeText(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[\.\s,]+/', '', $text);
        $text = str_replace(['0', '1', '3', '4', '5'], ['o', 'i', 'e', 'a', 's'], $text);
        return $text;
    }

    protected function isProfane(string $word): bool
    {
        if (in_array($word, $this->allowedWords)) {
            return false;
        }

        foreach ($this->profaneWords as $profaneWord) {
            if (str_contains($word, $profaneWord)) {
                return true;
            }
        }

        return false;
    }
}
