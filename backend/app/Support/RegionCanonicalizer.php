<?php

namespace App\Support;

/**
 * Kanonizacja nazwy województwa do ASCII-id używanego przez front
 * (`frontend/src/data/polishLocations.json` → `voivodeships[].id`).
 *
 * Po co: kolumna `advertisements.region` jest wypełniana z `address.state` z Nominatim
 * (`AddAdPage.vue:663,728`), które zwraca raz „śląskie”, raz „województwo dolnośląskie”,
 * a front filtruje po ASCII-id („slaskie”). Bez wspólnego kanonu zapis i odczyt się rozjeżdżają.
 *
 * Klasa jest czysta (bez I/O), więc nadaje się i do kontrolera, i do komendy backfillu, i do testów.
 */
final class RegionCanonicalizer
{
    /** ASCII-id 16 województw — MUSZĄ być zgodne z `polishLocations.json`. */
    public const IDS = [
        'dolnoslaskie', 'kujawsko-pomorskie', 'lubelskie', 'lubuskie',
        'lodzkie', 'malopolskie', 'mazowieckie', 'opolskie',
        'podkarpackie', 'podlaskie', 'pomorskie', 'slaskie',
        'swietokrzyskie', 'warminsko-mazurskie', 'wielkopolskie', 'zachodniopomorskie',
    ];

    private const PL_FOLD = [
        'ą' => 'a', 'Ą' => 'a', 'ć' => 'c', 'Ć' => 'c', 'ę' => 'e', 'Ę' => 'e',
        'ł' => 'l', 'Ł' => 'l', 'ń' => 'n', 'Ń' => 'n', 'ó' => 'o', 'Ó' => 'o',
        'ś' => 's', 'Ś' => 's', 'ź' => 'z', 'Ź' => 'z', 'ż' => 'z', 'Ż' => 'z',
    ];

    /**
     * Zwraca ASCII-id województwa albo null, gdy wejścia nie da się rozpoznać
     * (null = ZOSTAW jak jest, nigdy nie zgaduj — lepiej puste niż błędnie przypisane).
     */
    public static function canonicalize(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $s = mb_strtolower(strtr(trim($raw), self::PL_FOLD));
        $s = trim(preg_replace('/\s+/', ' ', $s) ?? '');
        $s = preg_replace('/^(wojewodztwo|woj\.?)\s+/', '', $s) ?? $s;
        $s = str_replace(' ', '-', $s);

        return in_array($s, self::IDS, true) ? $s : null;
    }
}
