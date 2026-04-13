<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Advertisement;
use Illuminate\Support\Str;

class AdvertisementsSeeder extends Seeder
{
    /**
     * Startowe ogłoszenia platformy.
     * Email zarządzania: ogloszenia@reklamap.pl
     * Zdjęcia dodaj ręcznie przez panel /zarzadzaj
     */
    public function run(): void
    {
        $ads = [

            // ══════════════════ WARSZAWA ══════════════════

            [
                'title' => 'Billboard jednostronny - ul. Modlińska, Białołęka',
                'type' => 'billboard',
                'location' => 'ul. Modlińska 223',
                'city' => 'Warszawa',
                'region' => 'mazowieckie',
                'latitude' => 52.3156,
                'longitude' => 20.9887,
                'description' => "Mam wolny billboard przy Modlińskiej na Białołęce, wyjazd w stronę Nieporętu. Dużo aut rano i po południu, bo ludzie dojeżdżają z tamtych okolic do pracy. Sam mieszkam niedaleko i powiem szczerze — każdy kto jedzie tą drogą mija ten baner. Minimalny wynajem miesiąc, cena do ustalenia przy dłuższej umowie. Kontakt przez formularz.",
                'price' => 1400,
                'price_unit' => 'month',
                'width' => 5.04,
                'height' => 2.38,
                'variant' => 'standard',
                'road_class' => 'urban',
                'traffic_intensity' => 'medium',
                'traffic_type' => ['car'],
                'has_backlight' => true,
                'offer_type' => 'owner',
                'price_negotiable' => true,
                'environment' => 'urban',
            ],
            [
                'title' => 'Ekran LED - ul. Głębocka / Targówek',
                'type' => 'led_screen',
                'location' => 'ul. Głębocka 13',
                'city' => 'Warszawa',
                'region' => 'mazowieckie',
                'latitude' => 52.2872,
                'longitude' => 21.0743,
                'description' => "Oferujemy czas antenowy na ekranie LED przy ul. Głębockiej na Targówku. Nośnik zlokalizowany przy skrzyżowaniu z długim cyklem sygnalizacji świetlnej, co przekłada się na ponadprzeciętny dwell time dla kierowców. Parametry: rozdzielczość Full HD, automatyczna regulacja jasności, praca 24/7. Dostępne pakiety: wyłączność dobowa, emisja w rotacji (do 6 klientów). Minimalna długość kampanii: 2 tygodnie. Zapraszamy do przesłania zapytania.",
                'price' => 3200,
                'price_unit' => 'month',
                'width' => 6.00,
                'height' => 3.00,
                'resolution' => '1920x1080',
                'pixel_pitch' => '8',
                'brightness' => '6000',
                'ambient_light_control' => true,
                'traffic_intensity' => 'medium',
                'traffic_type' => ['car', 'pedestrian'],
                'has_backlight' => true,
                'offer_type' => 'agency',
                'variant' => 'standard',
            ],
            [
                'title' => 'Citylight - przystanek Kondratowicza / Bródno',
                'type' => 'citylight',
                'location' => 'ul. Kondratowicza 37',
                'city' => 'Warszawa',
                'region' => 'mazowieckie',
                'latitude' => 52.2803,
                'longitude' => 21.0612,
                'description' => "Wiata autobusowa przy ul. Kondratowicza, przystanek jest tu bardzo ruchliwy bo obsługuje kilka linii z Bródna. Citylight podświetlany, format 120x180. Dużo ludzi czeka tu na autobus zwłaszcza rano. Interesuje mnie najem miesięczny lub dłużej.",
                'price' => 850,
                'price_unit' => 'month',
                'width' => 1.20,
                'height' => 1.80,
                'variant' => 'single_sided',
                'traffic_intensity' => 'medium',
                'traffic_type' => ['pedestrian'],
                'has_backlight' => true,
                'offer_type' => 'owner',
            ],
            [
                'title' => 'Billboard - ul. Płochocińska / wyjazd z Warszawy',
                'type' => 'billboard',
                'location' => 'ul. Płochocińska 85',
                'city' => 'Warszawa',
                'region' => 'mazowieckie',
                'latitude' => 52.3089,
                'longitude' => 21.0534,
                'description' => "Billboard przy wyjeździe z Warszawy w stronę Marek i Legionowa — ulica Płochocińska. Sporo aut wcześnie rano i późnym popołudniem. Jednostronny, format 504x238 cm, oświetlony. Dobry na lokalną firmę albo dewelopera z tamtych okolic. Piszcie na formularz.",
                'price' => 1100,
                'price_unit' => 'month',
                'width' => 5.04,
                'height' => 2.38,
                'variant' => 'standard',
                'road_class' => 'urban',
                'traffic_intensity' => 'medium',
                'traffic_type' => ['car'],
                'has_backlight' => true,
                'offer_type' => 'owner',
                'price_negotiable' => true,
                'environment' => 'urban',
            ],

            // ══════════════════ KRAKÓW ══════════════════

            [
                'title' => 'Billboard - ul. Półkole / Nowa Huta',
                'type' => 'billboard',
                'location' => 'ul. Półkole 18',
                'city' => 'Kraków',
                'region' => 'malopolskie',
                'latitude' => 50.0769,
                'longitude' => 20.0521,
                'description' => "Wstawiłem billboard przy Półkolu w Nowej Hucie jakieś 2 lata temu i teraz go udostępniam. Dużo aut tamtędy jedzie bo to dojazd z osiedli do centrum i do obwodnicy. Oświetlony od zachodu słońca, stan dobry. Odezwijcie się przez wiadomość to dogadamy szczegóły.",
                'price' => 1300,
                'price_unit' => 'month',
                'width' => 5.04,
                'height' => 2.38,
                'variant' => 'standard',
                'road_class' => 'urban',
                'traffic_intensity' => 'medium',
                'traffic_type' => ['car'],
                'has_backlight' => true,
                'offer_type' => 'owner',
                'price_negotiable' => true,
                'environment' => 'urban',
            ],
            [
                'title' => 'Citylight - przystanek os. Piastów, Kraków',
                'type' => 'citylight',
                'location' => 'os. Piastów 52',
                'city' => 'Kraków',
                'region' => 'malopolskie',
                'latitude' => 50.0812,
                'longitude' => 20.0253,
                'description' => "Mam citylight na przystanku przy os. Piastów w Nowej Hucie. Dużo starszych mieszkańców i dojeżdżających do pracy — przystanek obsługuje linie tramwajowe i autobusowe. Cena za miesiąc z wliczonym utrzymaniem. Jak chcesz dłużej to negocjujemy.",
                'price' => 750,
                'price_unit' => 'month',
                'width' => 1.20,
                'height' => 1.80,
                'variant' => 'single_sided',
                'traffic_intensity' => 'medium',
                'traffic_type' => ['pedestrian'],
                'has_backlight' => true,
                'offer_type' => 'owner',
                'price_negotiable' => true,
            ],
            [
                'title' => 'Ekran LED - ul. Kamieńskiego / Podgórze',
                'type' => 'led_screen',
                'location' => 'ul. Kamieńskiego 11',
                'city' => 'Kraków',
                'region' => 'malopolskie',
                'latitude' => 50.0284,
                'longitude' => 19.9623,
                'description' => "Agencja AdPoint udostępnia powierzchnię na ekranie DOOH przy ul. Kamieńskiego w Krakowie-Podgórzu. Lokalizacja przy węźle tramwajowym, szacowany dzienny OTS: 8 000–11 000 osób. Profil odbiorcy: mieszkańcy rozwijającej się dzielnicy, pracownicy pobliskich biur i usług. Ekran pracuje całą dobę z automatyczną optymalizacją jasności. Możliwość zakupu emisji w modelu tygodniowym lub miesięcznym. Szczegółowa oferta i media kit dostępne po kontakcie.",
                'price' => 2800,
                'price_unit' => 'month',
                'width' => 4.00,
                'height' => 2.50,
                'resolution' => '1280x720',
                'pixel_pitch' => '10',
                'ambient_light_control' => true,
                'traffic_intensity' => 'medium',
                'traffic_type' => ['car', 'pedestrian'],
                'has_backlight' => true,
                'offer_type' => 'agency',
                'variant' => 'standard',
            ],

            // ══════════════════ WROCŁAW ══════════════════

            [
                'title' => 'Billboard - ul. Kosmonautów / Fabryczna',
                'type' => 'billboard',
                'location' => 'ul. Kosmonautów 51',
                'city' => 'Wrocław',
                'region' => 'dolnoslaskie',
                'latitude' => 51.0989,
                'longitude' => 16.9823,
                'description' => "Powierzchnia reklamowa przy ul. Kosmonautów we Wrocławiu, w bezpośrednim sąsiedztwie wjazdu na obwodnicę i trasę do autostrady A4. Nośnik dwustronny 6x3m skierowany na ruch w obu kierunkach — centrum oraz węzeł autostradowy. Profil ruchu: mieszany (osobowy, dostawczy, ciężarowy). Oświetlenie LED, ekspozycja całodobowa. Nośnik zarządzany przez Grupę Reklam Zewnętrznych Południe Sp. z o.o. Minimalna umowa: 1 miesiąc. Faktura VAT.",
                'price' => 2600,
                'price_unit' => 'month',
                'width' => 6.00,
                'height' => 3.00,
                'variant' => 'two_sided',
                'road_class' => 'urban',
                'traffic_intensity' => 'high',
                'traffic_type' => ['car'],
                'has_backlight' => true,
                'offer_type' => 'sublease',
                'price_negotiable' => true,
                'environment' => 'urban',
            ],
            [
                'title' => 'Citylight - ul. Grabiszyńska / Grabiszyn',
                'type' => 'citylight',
                'location' => 'ul. Grabiszyńska 195',
                'city' => 'Wrocław',
                'region' => 'dolnoslaskie',
                'latitude' => 51.0894,
                'longitude' => 17.0021,
                'description' => "Panel na przystanku przy Grabiszyńskiej niedaleko cmentarza. Brzmi niepoważnie, ale tam naprawdę jest duży ruch — blisko centrum, dużo linii tramwajowych. Citylight jednostronny, podświetlany. Dobra lokalizacja dla usług lokalnych.",
                'price' => 700,
                'price_unit' => 'month',
                'width' => 1.20,
                'height' => 1.80,
                'variant' => 'single_sided',
                'traffic_intensity' => 'medium',
                'traffic_type' => ['pedestrian'],
                'has_backlight' => true,
                'offer_type' => 'owner',
            ],
            [
                'title' => 'Siatka na ścianie - ul. Świdnicka / centrum',
                'type' => 'wall',
                'location' => 'ul. Świdnicka 12',
                'city' => 'Wrocław',
                'region' => 'dolnoslaskie',
                'latitude' => 51.1064,
                'longitude' => 17.0278,
                'description' => "Oferujemy wynajem powierzchni ściennej (siatka wielkoformatowa) na kamienicy przy ul. Świdnickiej we Wrocławiu. Ekspozycja 8x6m skierowana na ruch pieszy i samochodowy. Lokalizacja w obrębie centrum, wysoka intensywność ruchu pieszego. Właściciel nieruchomości prowadzi obsługę prawno-administracyjną. Cena netto, faktura VAT 23%. Preferowany wynajem kwartalny lub roczny — przy umowie rocznej rabat 15%. Kontakt w sprawie szczegółów technicznych i wizualizacji przez formularz.",
                'price' => 3200,
                'price_unit' => 'month',
                'width' => 8.00,
                'height' => 6.00,
                'traffic_intensity' => 'medium',
                'traffic_type' => ['pedestrian', 'car'],
                'has_backlight' => false,
                'lighting_type_banner' => 'none',
                'offer_type' => 'owner',
            ],

            // ══════════════════ POZNAŃ ══════════════════

            [
                'title' => 'Billboard - ul. Dąbrowskiego / Łazarz',
                'type' => 'billboard',
                'location' => 'ul. Dąbrowskiego 87',
                'city' => 'Poznań',
                'region' => 'wielkopolskie',
                'latitude' => 52.3921,
                'longitude' => 16.9012,
                'description' => "Billboard przy Dąbrowskiego na Łazarzu, jednostronny wychodzi na ulicę w kierunku centrum. Sporo tramwajów i aut, blisko UCK i kilku szkół. Sam stąd jestem i wiem że ruch jest tam zawsze. Oświetlony, dobry stan techniczny. Piszcie przez formularz.",
                'price' => 1700,
                'price_unit' => 'month',
                'width' => 5.04,
                'height' => 2.38,
                'variant' => 'standard',
                'road_class' => 'urban',
                'traffic_intensity' => 'medium',
                'traffic_type' => ['car', 'pedestrian'],
                'has_backlight' => true,
                'offer_type' => 'owner',
                'environment' => 'urban',
            ],
            [
                'title' => 'Totem reklamowy - ul. Bukowska / Jeżyce',
                'type' => 'totem',
                'location' => 'ul. Bukowska 27',
                'city' => 'Poznań',
                'region' => 'wielkopolskie',
                'latitude' => 52.4098,
                'longitude' => 16.8912,
                'description' => "Pylon 1x3,5m przy Bukowskiej na Jeżycach, tuż przy wjeździe do centrum. Bardzo modna dzielnica, kawiarnie, sklepy — dobrze tu się pokazać jeśli kierujesz markę do aktywnych trzydziestolatków. Podświetlany, stan bdb. Dostępny od początku przyszłego miesiąca.",
                'price' => 1500,
                'price_unit' => 'month',
                'width' => 1.00,
                'height' => 3.50,
                'variant' => 'standard',
                'traffic_intensity' => 'medium',
                'traffic_type' => ['pedestrian', 'car'],
                'has_backlight' => true,
                'offer_type' => 'owner',
                'price_negotiable' => true,
            ],

            // ══════════════════ GDAŃSK ══════════════════

            [
                'title' => 'Billboard - ul. Kartuska / Chełm',
                'type' => 'billboard',
                'location' => 'ul. Kartuska 245',
                'city' => 'Gdańsk',
                'region' => 'pomorskie',
                'latitude' => 54.3421,
                'longitude' => 18.5932,
                'description' => "Nośnik przy ul. Kartuskiej — drodze krajowej nr 7 w kierunku Kartuz. Lokalizacja przy stacji paliw i serwisie, co dodatkowo wydłuża ekspozycję dla zatrzymujących się kierowców. Szacowany DEC (Daily Effective Circulation): 14 000 pojazdów. Billboard jednostronny 6x3m z oświetleniem LED. Dedykowany branżom: motoryzacja, serwis, budownictwo, usługi lokalne. Zarządca nośnika: OutMedia Gdańsk. Umowa od 1 miesiąca, możliwa płatność kwartalna z rabatem.",
                'price' => 1900,
                'price_unit' => 'month',
                'width' => 6.00,
                'height' => 3.00,
                'variant' => 'standard',
                'road_class' => 'national',
                'traffic_intensity' => 'medium',
                'traffic_type' => ['car'],
                'has_backlight' => true,
                'offer_type' => 'owner',
                'environment' => 'suburban',
            ],
            [
                'title' => 'Citylight - przystanek Łostowice Świętokrzyska',
                'type' => 'citylight',
                'location' => 'ul. Łostowicka 93',
                'city' => 'Gdańsk',
                'region' => 'pomorskie',
                'latitude' => 54.3312,
                'longitude' => 18.5821,
                'description' => "Panel na przystanku na Łostowicach — duże osiedle, mnóstwo rodzin i pracujących. Autobusy kursują często, więc przy przystanku zawsze ktoś stoi. Podświetlany, format standardowy. Fajne miejsce pod sklepy spożywcze, siłownie, usługi dla mieszkańców.",
                'price' => 650,
                'price_unit' => 'month',
                'width' => 1.20,
                'height' => 1.80,
                'variant' => 'single_sided',
                'traffic_intensity' => 'medium',
                'traffic_type' => ['pedestrian'],
                'has_backlight' => true,
                'offer_type' => 'owner',
            ],
            [
                'title' => 'Baner na ogrodzeniu - Port Gdański / Nowy Port',
                'type' => 'banner',
                'location' => 'ul. Majowa 2',
                'city' => 'Gdańsk',
                'region' => 'pomorskie',
                'latitude' => 54.3891,
                'longitude' => 18.6612,
                'description' => "Przestrzeń banerowa na ogrodzeniu przy ul. Majowej w Nowym Porcie — strefa przemysłowo-portowa Gdańska. Format do 12x2m, mocowanie do istniejącej konstrukcji. Ruch docelowy: pojazdy ciężarowe, pracownicy portu, transport lokalny. Ekspozycja dzienna. Idealne zastosowanie: branża logistyczna, spedycja, wynajem maszyn, usługi B2B. Warunki: wynajem miesięczny lub kwartalny, montaż i demontaż grafiki we własnym zakresie lub na zlecenie (wycena na zapytanie).",
                'price' => 900,
                'price_unit' => 'month',
                'width' => 12.00,
                'height' => 2.00,
                'traffic_intensity' => 'medium',
                'traffic_type' => ['car'],
                'has_backlight' => false,
                'lighting_type_banner' => 'none',
                'offer_type' => 'owner',
                'price_negotiable' => true,
            ],

            // ══════════════════ ŁÓDŹ ══════════════════

            [
                'title' => 'Billboard - ul. Brzezińska / Widzew',
                'type' => 'billboard',
                'location' => 'ul. Brzezińska 37',
                'city' => 'Łódź',
                'region' => 'lodzkie',
                'latitude' => 51.7712,
                'longitude' => 19.5034,
                'description' => "Billboard przy Brzezińskiej na Widzewie — ulica jest mocno zakorkowana wieczorami bo to główny dojazd ze wschodnich części Łodzi. Jednostronny, oświetlony, standard 504x238. Mam możliwość wymiany grafiki co miesiąc, sam organizuję druk za dodatkową opłatą jeśli potrzeba.",
                'price' => 1100,
                'price_unit' => 'month',
                'width' => 5.04,
                'height' => 2.38,
                'variant' => 'standard',
                'road_class' => 'urban',
                'traffic_intensity' => 'medium',
                'traffic_type' => ['car'],
                'has_backlight' => true,
                'offer_type' => 'owner',
                'price_negotiable' => true,
                'environment' => 'urban',
            ],

            // ══════════════════ KATOWICE ══════════════════

            [
                'title' => 'Billboard - ul. Murckowska / Muchowiec',
                'type' => 'billboard',
                'location' => 'ul. Murckowska 14',
                'city' => 'Katowice',
                'region' => 'slaskie',
                'latitude' => 50.2398,
                'longitude' => 19.0412,
                'description' => "Nośnik reklamowy przy ul. Murckowskiej w Katowicach, w pobliżu kompleksu sportowo-rekreacyjnego Muchowiec. Lokalizacja obsługuje ruch z osiedli południa Katowic w kierunku drogi S86 i DTŚ. Szacowane dzienne przejazdy: 9 000–12 000 pojazdów. Billboard jednostronny 5,04x2,38m, podświetlany LED. Zarządca: Śląska Agencja Mediów Zewnętrznych. Minimalna umowa 3 miesiące, faktura VAT, możliwość pomocy przy organizacji druku.",
                'price' => 1600,
                'price_unit' => 'month',
                'width' => 5.04,
                'height' => 2.38,
                'variant' => 'standard',
                'road_class' => 'urban',
                'traffic_intensity' => 'medium',
                'traffic_type' => ['car'],
                'has_backlight' => true,
                'offer_type' => 'owner',
                'environment' => 'suburban',
            ],
            [
                'title' => 'Citylight - przystanek Rolna / Koszutka',
                'type' => 'citylight',
                'location' => 'ul. Rolna 43',
                'city' => 'Katowice',
                'region' => 'slaskie',
                'latitude' => 50.2589,
                'longitude' => 19.0078,
                'description' => "Panel citylight przy przystanku tramwajowym na Rolnej, dzielnica Koszutka. Sporo pracujących ludzi wsiada tam rano, blisko kilku osiedli. Podświetlany, dostępny od zaraz. Dobra lokalizacja dla aptek, sklepów spożywczych, usług zdrowotnych.",
                'price' => 680,
                'price_unit' => 'month',
                'width' => 1.20,
                'height' => 1.80,
                'variant' => 'single_sided',
                'traffic_intensity' => 'medium',
                'traffic_type' => ['pedestrian'],
                'has_backlight' => true,
                'offer_type' => 'owner',
            ],

            // ══════════════════ SZCZECIN ══════════════════

            [
                'title' => 'Billboard - ul. Struga / Niebuszewo',
                'type' => 'billboard',
                'location' => 'ul. Andrzeja Struga 15',
                'city' => 'Szczecin',
                'region' => 'zachodniopomorskie',
                'latitude' => 53.4312,
                'longitude' => 14.5678,
                'description' => "Billboard przy ul. Struga na Niebuszewie. Spokojniejsza dzielnica ale tranzyt stąd do centrum jest spory. Jednostronny, stan dobry, oświetlenie działa. Właśnie skończyła się umowa poprzedniego klienta więc jest od razu dostępny. Wolę kontakt przez wiadomość.",
                'price' => 1200,
                'price_unit' => 'month',
                'width' => 5.04,
                'height' => 2.38,
                'variant' => 'standard',
                'road_class' => 'urban',
                'traffic_intensity' => 'low',
                'traffic_type' => ['car'],
                'has_backlight' => true,
                'offer_type' => 'owner',
                'price_negotiable' => true,
                'environment' => 'urban',
            ],

        ];

        $defaults = [
            'owner_email'             => 'ogloszenia@reklamap.pl',
            'phone'                   => '',
            'contact_preference'      => 'email',
            'is_active'               => true,
            'is_verified'             => true,
            'has_image'               => false,
            'images'                  => [],
            'has_vat_invoice'         => true,
            'price_includes_print'    => false,
            'price_includes_mounting' => false,
            'price_negotiable'        => false,
            'status'                  => 'active',
        ];

        // Różne daty dodania (od 4 miesięcy wstecz do niedawna)
        $createdDates = [
            now()->subDays(118),
            now()->subDays(103),
            now()->subDays(91),
            now()->subDays(84),
            now()->subDays(76),
            now()->subDays(68),
            now()->subDays(61),
            now()->subDays(54),
            now()->subDays(47),
            now()->subDays(41),
            now()->subDays(35),
            now()->subDays(29),
            now()->subDays(24),
            now()->subDays(19),
            now()->subDays(15),
            now()->subDays(12),
            now()->subDays(9),
            now()->subDays(7),
            now()->subDays(5),
            now()->subDays(3),
            now()->subDays(2),
            now()->subDays(1),
        ];

        foreach ($ads as $index => $ad) {
            $data = array_merge($defaults, $ad);

            if (isset($data['width'], $data['height'])) {
                $data['orientation'] = $data['width'] >= $data['height'] ? 'horizontal' : 'vertical';
            }

            $data['slug'] = Str::slug($data['title']);

            // Przypisz datę dodania
            $createdAt = $createdDates[$index] ?? now()->subDays(rand(1, 30));

            // Kilka ogłoszeń jako dostępne od przyszłej daty
            if (in_array($index, [2, 7, 12, 17])) {
                $data['available_from'] = now()->addDays(rand(10, 25));
            }

            $ad = Advertisement::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );

            // Ustaw datę created_at ręcznie (poza fillable)
            $ad->timestamps = false;
            $ad->created_at = $createdAt;
            $ad->updated_at = $createdAt;
            $ad->save();
            $ad->timestamps = true;
        }

        $this->command->info('Dodano ' . count($ads) . ' ogłoszeń. Email zarządzania: ogloszenia@reklamap.pl');
    }
}
