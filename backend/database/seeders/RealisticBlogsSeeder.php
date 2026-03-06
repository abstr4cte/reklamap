<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Support\Str;

class RealisticBlogsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sprawdź czy jest jakiś użytkownik, aby przypisać mu posty. Jeśli nie, utwórz admina.
        $user = User::first() ?? clone User::factory()->create([
            'name' => 'Redakcja ReklaMap',
            'email' => 'redakcja@reklamap.pl',
            'password' => bcrypt('password')
        ]);

        $blogs = [
            // PORADNIKI
            [
                'title' => 'Jak wybrać idealną lokalizację billboardu? Analiza krok po kroku',
                'category' => 'poradniki',
                'image' => 'https://images.unsplash.com/photo-1542362567-b07e54358753?q=80&w=1200',
                'content' => '
                    <p>Wybór lokalizacji dla Twojej reklamy zewnętrznej to absolutny fundament sukcesu każdej kampanii outdoorowej. Nawet najlepszy i najdroższy projekt graficzny nie przyniesie zwrotu z inwestycji (ROI), jeśli Twojego billboardu nikt nie zobaczy, albo zobaczy go nieodpowiednia grupa docelowa.</p>
                    <h2>1. Zrozumienie natężenia ruchu (OTS)</h2>
                    <p>OTS, czyli Opportunity To See (Szansa na Kontakt), to jeden z najważniejszych wskaźników w reklamie OOH (Out-of-Home). Analizując lokalizacje w <a href="/powierzchnie-reklamowe">naszej wyszukiwarce ReklaMap</a>, zwróć uwagę na "Szacowane dzienne wyświetlenia". Billboardy usytuowane przy drogach krajowych, autostradach lub głównych węzłach komunikacyjnych w miastach takich jak <a href="/powierzchnie-reklamowe/warszawa">Warszawa</a>, <a href="/powierzchnie-reklamowe/krakow">Kraków</a> czy <a href="/powierzchnie-reklamowe/wroclaw">Wrocław</a>, mają najwyższe OTS.</p>
                    <h2>2. Czas kontaktu (Dwell time) i środowisko</h2>
                    <p>Więcej nie zawsze znaczy lepiej. Szybka droga szybkiego ruchu to kierowcy pędzący 120 km/h - Twój przekaz musi być ultra-prosty i składać się z maksymalnie 3-5 słów. Zupełnie inaczej jest w przypadku nośników na skrzyżowaniach ze światłami lub przy przejazdach kolejowych. Tam <strong>czas kontaktu ulega wydłużeniu</strong> (dwell time), co pozwala na przekazanie bardziej złożonego komunikatu reklamowego, czy zapamiętanie numeru telefonu i adresu strony www.</p>
                    <h2>3. Profil demograficzny lokalizacji</h2>
                    <p>Musisz dopasować miejsce do profilu klienta. Skupiasz się na studentach i młodych pracownikach? Wybierz <a href="/powierzchnie-reklamowe/citylighty">Citylighty</a> w okolicach uczelni i biurowców. Prowadzisz kampanię B2B skupioną na menadżerach najwyższego szczebla? Postaw na lotniska, hotele lub <a href="/powierzchnie-reklamowe/ekrany-led">nowoczesne ekrany LED</a> zlokalizowane przy trasach wylotowych do zamożnych dzielnic podmiejskich.</p>
                    <h3>Podsumowanie</h3>
                    <p>Decyzja o wynajmie powierzchni reklamowej nie powinna być podyktowana tylko i wyłącznie ceną miesięczną. To, co liczy się najbardziej, to wskaźnik CPT (Cost Per Thousand - koszt dotarcia do 1000 odbiorców) powiązany z jakością tego ruchu. Odpowiednio wycelowany billboard potrafi wygenerować zyski sięgające tysięcy procent pokrywających z nawiązką budżet wydany na wynajem stelaża reklamowego.</p>
                ',
                'published_at' => now()->subDays(10),
            ],
            [
                'title' => 'Reklama na autobusach i tramwajach - czy to ma sens? Koszty i zalety reklamy mobilnej',
                'category' => 'poradniki',
                'image' => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?q=80&w=1200',
                'content' => '
                    <p>Gdy myślimy o reklamie zewnętrznej, przed oczami stają nam klasyczne billboardy i wielkie banery. Tymczasem <a href="/powierzchnie-reklamowe/reklama-w-transporcie">reklama w transporcie i reklama mobilna</a> rośnie w siłę z roku na rok. Postanowiliśmy przyjrzeć się, dlaczego coraz więcej firm okleja lokalne autobusy i tramwaje.</p>
                    <h2>Ogromny zasięg za relatywnie niską cenę</h2>
                    <p>Główną i niepodważalną zaletą oklejenia flotowego jest mobilność nośnika. W przeciwieństwie do billboardu, komunikacja dociera w różne części miasta. Rano Twoja reklama widoczna jest dla osób zjeżdżających do korporacyjnych "mordorów", a wieczorem trafia na osiedla mieszkalne z młodymi rodzinami. Koszt oklejenia typu "full-back" (cały tył autobusu) w miastach takich jak <a href="/powierzchnie-reklamowe/poznan">Poznań</a> rozpoczyna się już od około 2500 zł miesięcznie, co czyni go atrakcyjniejszą opcją dla MŚP, chociażby od prestiżowych nośników cyfrowych.</p>
                    <h2>Większa wiarygodność i uwaga</h2>
                    <ul>
                        <li>Pasażerowie poruszający się w środku komunikacji miejskiej, z powodu tak zwanej "nudy pasażerskiej" bardzo często <strong>z najwyższą uwagą</strong> przyswajają treści z naklejek wewnętrznych na szybach i oparciach foteli. Czas przebywania z tym nośnikiem to nierzadko 20-40 minut podróży.</li>
                        <li>Autobus i tramwaj są wpisane w miejski ekosystem. Ludzie mają do nich pewien rodzaj naturalnego zaufania jako formy sprawczej państwa – marka pojawiająca się na pojeździe użyteczności publicznej jest podświadomie odbierana jako pewna i ugruntowana na rynku.</li>
                    </ul>
                    <h2>Formaty reklamy na transporcie zbiorowym</h2>
                    <p>Oklejenia możemy podzielić na trzy kluczowe formaty:</p>
                    <ol>
                        <li><strong>Full vehicle (Całopojazdowe)</strong> - Najdroższe, najbardziej prestiżowe, dające efekt "WOW", ale często obarczone problemem projektowym we wpisaniu elementów drzwi i okien tak, by zgadzały się z normami danego MPK.</li>
                        <li><strong>Fullback (Cały tył) i Halfback (Pół tyłu)</strong> - Skierowane prosto w twarz kierowców jadących za pojazdem. Doskonałe dla formatów B2C. Gwarantowany wydłużony czas na przemyślenie oferty w korkach i przy światłach czerwonych.</li>
                        <li><strong>Standardy burtowe / Pasy środkowe</strong> - Budżetowe linie graficzne na bokach pojazdu dla kampanii wizerunkowej lub zasięgowej (kierujące do pieszych na chodnikach i przystankach).</li>
                    </ol>
                ',
                'published_at' => now()->subDays(20),
            ],

            // CASE STUDY
            [
                'title' => 'Ekrany LED. Jak digitalowa kampania OOH (DOOH) obniżyła koszty pozyskania klienta',
                'category' => 'case-study',
                'image' => 'https://images.unsplash.com/photo-1548685121-fe2a02330a8c?q=80&w=1200',
                'content' => '
                    <p>Digital Out of Home (DOOH), czyli reklama cyfrowa na ulicach, to najszybciej rozwijająca się gałąź współczesnego marketingu terenowego. Przedstawiamy krótkie studium przypadku ilustrujące, jak elastyczność cyfrowego nośnika odmienia bieg kampanii.</p>
                    <h2>Wyzwanie marketingowe we współczesnym środowisku miejskim</h2>
                    <p>Firma lokalna oferująca usługi wulkanizacyjne traciła budżety na reklamę banerową w okresach niezwiązanych z gwałtownymi zmianami temperatur. Wykupiony w listopadzie tradycyjny nośnik nie zwracał się z racji bardzo wczesnych i ciepłych jesieni. Klient potrzebował reakcji na "dziś na teraz" – pojawia się pierwszy śnieg, komunikat musi iść w miasto natychmiast.</p>
                    <h2>Rozwiązanie: Elastyczne planowanie i emisja zależna od pogody na Ekranach LED</h2>
                    <p>Wykorzystano <a href="/powierzchnie-reklamowe/ekrany-led">Ekrany LED</a> na wylotówce z <a href="/powierzchnie-reklamowe/gdansk">Gdańska</a> oraz Trójmiasta. Dzięki temu kampania klienta nie polegała już na drukowaniu i naklejaniu klasycznego plakatu. Stworzono dwie pętle reklamowe. Spot A (wymiana klocków i kół) był emulowany jako spadochron w ciepłe dni. Spot B wyświetlał ostrzegawcze czerwone napisy "Padasz? Zwolnij! Od razu masz u nas nowy komplet zimówek ze zniżką na hasło z ekranu!" - puszczano to wyłącznie wtedy, gdy lokalna stacja pogodowa wskazywała opady śniegu z deszczem powyżej ustalonego progu milimetrów.</p>
                    <h2>Rezultat obniżenia wskaźnika CAC (Customer Acquisition Cost)</h2>
                    <p>Poprzez targetowanie nośników w zależności od realnych zdarzeń "na ulicy", klient wydał per saldo tyle samo, ile na sztywny kontrakt billboardowy za kwartał zimowy. Przychód przypisany bezpośrednio do tej kampanii (dzięki analizie haseł promocyjnych) wyrósł w stosunku do lat ubiegłych o niewyobrażalne 160% z powodu idealnego dopasowania momentu podjęcia decyzji (tzw. Zero Moment of Truth).</p>
                    <h3>Klucz do wniosków B2B: Personalizacja przekazu ulicznego działa</h3>
                    <p>Reklama digital outdoor oferuje natychmiastowe zmiany. Odpowiednio wdrożona analityka na portalu <a href="/">ReklaMap.pl</a> pozwala już teraz firmom dobierać najbardziej odpowiednie, wysokorozdzielcze nośniki DOOH w Polsce.</p>
                ',
                'published_at' => now()->subDays(5),
            ],

            // TRENDY
            [
                'title' => 'Przyszłość reklamy zewnętrznej: 5 trendów, które odmienią polskie ulice w nadchodzących 3 latach',
                'category' => 'trendy',
                'image' => 'https://images.unsplash.com/photo-1542382156909-9ae37f3f5000?q=80&w=1200',
                'content' => '
                    <p>Ulica nie jest już martwym przekaźnikiem. Ulice naszych miast pulsują danymi i nowymi technologiami. Sprawdziliśmy, co czeka polski rynek reklamy OOH (Out-of-Home) w bardzo krótkim czasie ulegając transformacji w tzw. sprytne przestrzenie.</p>
                    <h2>1. Retargeting geolokalizacyjny (Mobile OOH Integration)</h2>
                    <p>Wielkie formaty łączyć się będą bezpośrednio z komórkami w naszych kieszeniach. Nowym standardem jest i będzie to, że odbiorca mijający billboard promujący ubezpieczenia samochodowe, chwilę później – z całkowitym zachowaniem standardów anonimowości (tzw. Audience Measurement Providers) – zobaczy podbijającą tę reklamę grafikę na swoim Facebooku lub Instagramie (tzw. cross-channel synergy).</p>
                    <h2>2. Anamorficzne iluzje optyczne 3D</h2>
                    <p>Krzywe ekrany w rogach galerii handlowych produkują uderzenie wizualne 3D dla stającego przed nimi pieszego (tzw. efekt iluzji głębi). Już teraz pierwsze takie gigantyczne LEDy z efektem sztuk "wychodzących w przestrzeń ekranu" zachwycają mieszkańców Azji, stając się nie tyle nośnikami co atrakcjami turystycznymi powielanymi wiralowo. Lada moment technologia pukać będzie do Polski na masową skalę.</p>
                    <h2>3. Eko-Ściany i Mural Reklamowy wracają do łask!</h2>
                    <p>Trend na zielone i zrównoważone budownictwo wyciągnął rynek z impasu starych "płacht" i "szmat" na ślepych ścianach. Ofert na wynajem ścian w kategoriach <a href="/powierzchnie-reklamowe/sciany-reklamowe">Ścian Reklamowych</a> robi się relatywnie więcej za sprawą mody na malowanie autorskich Murali komercyjnych. Zamiast oblepiać elewację banerem podświetlanym – sponsor, na wybranej ścianie funduje artyście dzieło wraz z oczyszczającą powietrze farbą antysmogową promując swoje działania CSR.</p>
                    <h2>4. Interaktywne wiaty przystankowe z kamerami</h2>
                    <p>Standardowe plakaty na <a href="/powierzchnie-reklamowe/citylighty">citylightach</a> uchodzą z życiem z racji piękna drukowanego obrazka, ale ich nowocześniejsze wersje oferować będą kamery (tzw. Computer Vision – oczywiście nie rejestrując tożsamości z racji RODO), pozwalając ekranom oceniać czy wpatruje się w nie kobieta, mężczyzna czy np. rodzina z dzieckiem i dynamicznie dopasowywać reklamę ze zmianą w ułamku sekundy.</p>
                    <h2>5. Zmierzch klasycznych nośników bez udowodnionej analityki? (pDOOH)</h2>
                    <p>Idziemy prosto w programmatic (pDOOH). Reklamodawcy jak w przypadku Adsów z Google czy Facebooka będą oczekiwać elastycznego wyświetlania reklam również na ulicach, płacąc per oszacowane obejrzenie bez wiązania umową długoterminową i ufać wyłącznie nośnikom, które połączone są z niezależnymi agencjami audytowymi pod kątem pomiarów natężeń ruchu.</p>
                ',
                'published_at' => now()->subDays(2),
            ],

            // NOWOŚCI
            [
                'title' => 'Digitalizacja polskiego rynku OOH. Mierzymy się z rozproszonym rynkiem',
                'category' => 'nowosci',
                'image' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?q=80&w=1200',
                'content' => '
                    <p>Polski rynek reklamy zewnętrznej od lat borykał się z problemem rozproszenia i braku centralizacji danych. Uruchomienie platformy takiej jak <strong>ReklaMap</strong> to odpowiedź na rosnące zapotrzebowanie domów mediowych, lokalnych przedsiębiorców oraz samych właścicieli nośników na jedno spójne, transparentne i zdigitalizowane narzędzie do zarządzania siatką outdoorową.</p>
                    <h2>Analiza problemu: Papierowe katalogi i ukryte stawki</h2>
                    <p>W przeszłości proces rezerwacji nośnika, takiego jak <a href="/powierzchnie-reklamowe/sciany-reklamowe">wielkoformatowa ściana</a> czy standardowy billboard, wiązał się z przesyłaniem ciężkich plików PDF w arkuszach i długich negocjacji cenowych. Brak precyzyjnych filtrów i ustrukturyzowanych parametrów (np. typ oświetlenia, estymowane dzienne widowni – OTS) znacznie przedłużał cykl sprzedażowy kampanii. Ponadto dla małych przedsiębiorstw – dysponujących raptem kilkoma atrakcyjnymi lokacjami pod wynajem reklamy – bariera wejścia do dużych podmiotów biznesowych była często nie do przejścia.</p>
                    <h2>Demokratyzacja wynajmu poprzez technologię Map-First</h2>
                    <p>Zasada działania oparta bezpośrednio na interaktywnych mapach to nowy standard europejski. Podejście "Map-First" pozwala zminimalizować tarcia decyzyjne z perspektywy domów mediowych. Zespół planujący kampanię dla nowej sieci sklepów może jednym kliknięciem wyfiltrować lokalizacje w odległości 3 kilometrów od planowanych placówek biznesowych.</p>
                    <p>Z kolei mechanizm wprowadzony dla właścicieli nośników, pozwalający na dodawanie ofert opartych na unikalnych i zaszyfrowanych linkach weryfikacyjnych (eliminujących narzut posiadania autoryzowanego panelu dla tzw. okazjonalnych najemców), przyśpiesza proces podaży wolnych płacht i stelaży w skali całego kraju.</p>
                    <h2>Podsumowanie danych i prognoza Rynkowa</h2>
                    <p>Oddając w ręce specjalistów i klientów narzędzie cyfrowe do tak tradycyjnego medium jak ulica, wierzymy, że podnosimy rentowność (yield) wynajmu po obu stronach. Skrócenie czasu dopasowania tablicy reklamowej do kampanii, połączone z twardymi parametrami oceny nośnika stanowi naturalny, niezbędny krok w dojrzewaniu branży reklamowej na naszym, lokalnym rynku.</p>
                ',
                'published_at' => now()->subHours(2),
            ],
            // START ANNOUNCEMENT
            [
                'title' => 'Start platformy ReklaMap. Nowy standard wynajmu powierzchni outdoorowej w Polsce',
                'category' => 'nowosci',
                'image' => 'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?q=80&w=1200',
                'content' => '
                    <p>Z satysfakcją informujemy o oficjalnym uruchomieniu platformy <strong>ReklaMap</strong> – nowoczesnego narzędzia, które ma na celu uporządkowanie i ułatwienie procesu wynajmu oraz sprzedaży nośników reklamowych. Naszym celem jest stworzenie pierwszego tak zintegrowanego ekosystemu łączącego właścicieli powierzchni reklamowych z reklamodawcami operującymi na rynku polskim.</p>
                    <h2>Dlaczego stworzyliśmy ReklaMap?</h2>
                    <p>Przez lata obserwowaliśmy rynek reklamy OOH (Out-of-Home), który mimo znacznego potencjału i wejścia w erę cyfrową, w wielu miejscach oparty jest na starych, nieoptymalnych procesach. Właściciele mniejszych i średnich sieci billboardów nierzadko narzekali na problemy z dotarciem do dużych domów mediowych. Z kolei ci drudzy zmuszeni byli do żmudnej analizy setek luźnych arkuszy kalkulacyjnych z ofertami od rozproszonych agencji. <strong>ReklaMap rozwiązuje ten problem</strong> agregując cały potencjał na intuicyjnym interfejsie mapowym, wyposażonym w solidny mechanizm filtrowania.</p>
                    <h2>Bez kont i formularzy rejestracyjnych</h2>
                    <p>Z punktu widzenia właścicieli nośników jedną z najważniejszych funkcji jest <a href="/dodaj-ogloszenie">brak wbudowanych barier wejścia</a>. Usunęliśmy całkowicie konieczność zakładania stałych kont w serwisie. Dodawanie, jak i zarządzanie ofertami (edycja, zdjęcia przestrzenne) jest chronione poprzez system bezpiecznych jednorazowych odnośników weryfikujących tożsamość użytkownika. Każde ogłoszenie w serwisie korzysta również z zabezpieczonych formularzy zapytaniowych (ukrywających adres e-mail, zabezpieczonych przez reCAPTCHA), co znacząco obniża zasiewanie skrzynek biurowych spamem.</p>
                    <h2>Perspektywy i dalsze plany</h2>
                    <p>Wczesne wejście w projekt pozwala nam budować jego ostateczną funkcjonalność bezpośrednio razem z pierwszą grupą docelową. Już teraz umożliwiamy porównywanie wybranych placówek. Niebawem przewidujemy zaprezentowanie zaawansowanych algorytmów estymacji zasięgów – liczących realną wartość danego panelu citylight, uwzględniającego m.in. miejskie potoki ruchu oraz tzw. stop-index widza pieszego. Oddajemy naszą platformę i serdecznie zapraszamy wszystkie polskie i europejskie oddziały OOH do kooperacji.</p>
                ',
                'published_at' => now()->subDays(30), // najstarszy wpis
            ],
        ];

        foreach ($blogs as $blog) {
            $baseData = [
                'slug' => Str::slug($blog['title']),
                'user_id' => $user->id,
                'status' => 'published',
            ];

            BlogPost::updateOrCreate(
                ['slug' => $baseData['slug']],
                array_merge($baseData, $blog)
            );
        }
    }
}
