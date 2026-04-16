<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Advertisement;

class AdvertisementStatsSeeder extends Seeder
{
    /**
     * Statystyki ogłoszeń od 1 do 15 kwietnia 2026.
     * Odzwierciedlają pierwsze tygodnie działania platformy —
     * ruch organiczny niewielki, ale zróżnicowany.
     */
    public function run(): void
    {
        $start = Carbon::create(2026, 4, 1);
        $end   = Carbon::create(2026, 4, 15);

        // Bazowa liczba odsłon dziennie per ogłoszenie.
        // Klucz = slug, wartość = mediana dzienna.
        // Ogłoszenia dodane później zaczynają zbierać statystyki od daty dodania.
        $baseViews = [
            // Warszawa
            'billboard-przy-modlinskiej-bialoleka-wyjazd-z-warszawy'        => 44,
            'ekran-led-targowek-skrzyzowanie-ze-swietlna'                    => 62,
            'citylight-brodno-ruchliwy-przystanek-kilku-linii'               => 28,
            'billboard-wylot-z-warszawy-na-marki-i-legionowo'               => 34,
            'billboard-premium-srodmiescie-warszawa-marszalkowska-swietokrzyska' => 78,

            // Kraków
            'wolny-billboard-nowa-huta-krakow'                              => 30,
            'citylight-na-przystanku-os-piastow-krakow'                     => 21,
            'wynajem-czasu-emisji-ekran-led-podgorze-krakow'                => 52,

            // Wrocław
            'billboard-dwustronny-przy-obwodnicy-wroclaw-fabryczna'         => 40,
            'panel-citylight-grabiszynska-wroclaw'                          => 18,
            'sciana-pod-siate-wielkoformatowa-centrum-wroclawia'            => 36,
            'citylight-przystanek-pilsudskiego-wroclaw-wysoki-ruch-uczciwa-cena' => 24,

            // Poznań
            'billboard-lazarz-poznan-blisko-uck'                            => 33,
            'pylon-przy-wjezdzie-do-centrum-ul-bukowska-jezyce-poznan'      => 25,

            // Gdańsk
            'billboard-dk7-gdansk-obok-stacji-paliw'                        => 37,
            'citylight-lostowice-gdansk-duze-osiedle'                       => 17,
            'ogrodzenie-pod-baner-strefa-portowa-nowy-port'                 => 13,

            // Łódź
            'billboard-widzew-lodz-dostepny-od-zaraz'                       => 26,

            // Katowice
            'billboard-poludnie-katowic-wyjazd-na-s86'                      => 30,
            'panel-na-przystanku-tramwajowym-koszutka-katowice'             => 18,

            // Szczecin
            'billboard-niebuszewo-szczecin-dostepny-od-zaraz'               => 20,

            // Lublin
            'billboard-wylot-z-lublina-droga-krajowa-na-krasnik'            => 24,
            'dwustronny-pylon-przy-galerii-atrium-lublin-czuby'             => 19,

            // Bydgoszcz
            'reklama-na-autobusach-mzk-bydgoszcz-80-pojazdow-cala-siec'    => 46,

            // Rzeszów
            'billboard-dwustronny-centrum-rzeszowa-przy-dworcu'             => 38,

            // Okolice Warszawy
            'billboard-przy-pulawskiej-piaseczno-korki-i-dluga-ekspozycja'  => 41,
            'citylight-pkp-pruszkow-tysiace-pasazerow-dziennie'             => 31,

            // Drogi krajowe
            'billboard-dk7-bialobrzegi-kierunek-warszawa'                   => 28,
            'billboard-przy-mop-adamowice-s8-45-km-od-warszawy'            => 48,
            'billboard-przy-dk1-za-piotrkowem-trasa-na-slask-wolny-po-8-latach' => 22,

            // Mniejsze miasta
            'ekran-led-centrum-kielc-przy-przejsciu-dla-pieszych'           => 33,
            'ogrodzenie-pod-baner-wjazd-do-tarnowa-od-strony-krakowa'      => 12,
            'citylight-centrum-olsztyna-ruchliwy-przystanek-tramwajowy'     => 17,
            'naklejka-reklamowa-na-taksowce-warszawa-centrum-i-mokotow-codziennie' => 38,
            'billboard-lubin-wjazd-od-a4-miasto-kghm'                      => 21,
            'sciana-na-kamienicy-przy-rynku-wieliczka-blisko-kopalni-soli'  => 43,

            // Budget / z charakterem
            'billboard-wjazd-do-nowego-sacza-od-strony-krakowa-tani'       => 10,
            'billboard-przy-dk8-pultusk-dobry-na-lato-tani-bo-zasloniety'  => 13,
        ];

        // Ogłoszenia dodane w połowie kwietnia — nie mają statystyk sprzed swojego dodania
        $adStartDates = [
            'billboard-premium-srodmiescie-warszawa-marszalkowska-swietokrzyska' => Carbon::create(2026, 4, 15),
            'billboard-wjazd-do-nowego-sacza-od-strony-krakowa-tani'            => Carbon::create(2026, 4, 15),
            'billboard-przy-dk8-pultusk-dobry-na-lato-tani-bo-zasloniety'      => Carbon::create(2026, 4, 15),
            'citylight-przystanek-pilsudskiego-wroclaw-wysoki-ruch-uczciwa-cena' => Carbon::create(2026, 4, 15),
        ];

        // Weekendy mają nieco niższy ruch (platforma B2B/propertytech)
        $dayMultiplier = [
            0 => 1.0,  // poniedziałek
            1 => 1.05,
            2 => 1.1,
            3 => 1.0,
            4 => 0.95, // piątek
            5 => 0.6,  // sobota
            6 => 0.5,  // niedziela
        ];

        $rows = [];

        foreach ($baseViews as $slug => $base) {
            $ad = Advertisement::where('slug', $slug)->first();
            if (!$ad) {
                continue;
            }

            $statsStart = $adStartDates[$slug] ?? $start;

            $current = $statsStart->copy();
            while ($current->lte($end)) {
                $multiplier = $dayMultiplier[$current->dayOfWeek] ?? 1.0;

                // Losowość ±30%, wynik clamped do przedziału 10-120
                $views = (int) round($base * $multiplier * (0.7 + lcg_value() * 0.6));
                $views = max(10, min(120, $views));

                // Kliknięcia formularza: ~1-3% odsłon, głównie w dni robocze
                $emailClicks = 0;
                if ($current->isWeekday() && $views > 20) {
                    $emailClicks = (lcg_value() < 0.25) ? rand(1, 2) : 0;
                }

                // Kliknięcia telefonu: bardzo rzadkie (contact_preference = form)
                $phoneClicks = (lcg_value() < 0.04) ? 1 : 0;

                $rows[] = [
                    'advertisement_id' => $ad->id,
                    'date'             => $current->toDateString(),
                    'views'            => $views,
                    'phone_clicks'     => $phoneClicks,
                    'email_clicks'     => $emailClicks,
                    'created_at'       => $current->copy()->setTime(3, 0),
                    'updated_at'       => $current->copy()->setTime(3, 0),
                ];

                $current->addDay();
            }
        }

        // Wstaw partiami, ignoruj duplikaty przy ponownym seedowaniu
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('advertisement_daily_stats')->upsert(
                $chunk,
                ['advertisement_id', 'date'],
                ['views', 'phone_clicks', 'email_clicks', 'updated_at']
            );
        }

        $this->command->info('Dodano statystyki dla ' . count($baseViews) . ' ogłoszeń (1–15 kwietnia 2026).');
    }
}
