# Sales Log — ReklaMap

Rejestr wyników rozmów cold calling. Agent Marketing czyta ten plik przed generowaniem nowych skryptów.

| Data | Typ rozmówcy | Użyty argument | Wynik | Notatka |
|:---|:---|:---|:---|:---|
| — | — | — | — | Pierwszy wpis pojawi się po pierwszych rozmowach |

---

## Kampania 2026-05 — pozyskanie podaży: agencje OOH

**Cel:** wciągnąć agencje/operatorów OOH na platformę **zanim ruszy kampania skierowana do reklamodawców** — chcemy gęstą podaż, gdy popyt się pojawi.

**Mechanika oferty:** hurtowy import całej oferty operatora z jednego pliku (szablon `reklamap-os/templates/szablon-import-agencje.xlsx`), wykonywany przez zespół ReklaMap. **Darmowy teraz** dla partnerów startowych.

**Rozdział, który MUSI wybrzmieć w każdym kontakcie:** wystawianie ogłoszeń = darmowe na zawsze; tym, co się zmienia, jest tylko „hurtowy import za Was z pliku" (później płatny albo self-serve w panelu). Nie obiecywać konkretnych przyszłych funkcji ani cen; nie grozić („potem nigdy") — deadline miękki.

**Materiały (szablony maili w `reklamap-os/templates/`):** `email-oferta-agencje.html` (pierwszy kontakt) → `email-followup-agencje.html` / `email-wysylka-excela-agencje.html` (follow-up + szablon Excel) → **`email-deadline-import-agencje.html`** (follow-up z miękkim deadline'em „do końca maja robimy priorytetowo" — utworzony 2026-05-13). Plus `prezentacja-agencje.html`/`.pdf`, `qa-agencje.html`/`.pdf` na spotkania. Linki w mailach: kanoniczne `https://reklamap.pl/...` + UTM `utm_source=outreach&utm_medium=email&utm_campaign=agencje-import-maj` (mierzymy w GA4).

**Plan wysyłki follow-up deadline:** ~2026-05-19/20 (≈10 dni przed końcem maja). Targetowanie: agencje z pierwszej fali kontaktu, które po ogłoszeniach widać, że nie wystawiły żadnego nośnika.

**Proces ręcznego importu (concierge) — jak obsługiwać niekompletny plik agencji:**
- Importuj **wszystkie** nośniki z pliku — nie odrzucaj wiersza tylko dlatego, że czegoś brakuje (sens „zrobimy za Was" = wciągnięcie całej oferty).
- Twardo wymagane do **aktywacji** (publiczny nośnik): typ, lokalizacja (min. miasto, najlepiej współrzędne), cena, kontakt (telefon lub e-mail). Brak któregokolwiek → wystaw jako **szkic/nieaktywny** (niewidoczny publicznie).
- Opcjonalne (można aktywować bez nich): wymiary, natężenie/typ ruchu, warianty, opis, dodatkowe zdjęcia. Brak → nośnik idzie na żywo, ale z notką „uzupełnij, żeby był wyżej w wynikach". Zdjęcie — graniczne; bez zdjęcia można aktywować, ale z niższą widocznością.
- Po imporcie → **odpisz agencji z podsumowaniem**: „wgraliśmy X nośników, Y na żywo, Z czeka jako szkic, bo brakuje: [12× cena, 8× e-mail kontaktowy, …] — odeślijcie te dane, aktywujemy". To naturalny follow-up, który znowu angażuje.
- Docelowo (po fazie startowej) to samo robi self-serve bulk import — patrz `docs/PRODUCT_BACKLOG.md` B-4.

**Status / wyniki:** _(uzupełniać — kto dostał follow-up, kto odpowiedział, ile nośników wpłynęło)_

---

## 2026-05-13 — sygnał walidacyjny willingness-to-pay (duża agencja OOH)

Przedstawiciel dużej agencji OOH (ta sama, z którą founder ma poniedziałkowe spotkanie zarządu — patrz pitch ~2026-05-18) w rozmowie powiedział, że „aż głupio, że platforma jest za darmo" i sam zaproponował oddawanie części prowizji.

**Co to znaczy:** pierwszy twardy dowód willingness-to-pay po stronie podaży, *bez naszej inicjatywy* — wartość platformy odbierają wyżej niż cena (zero). Walidacja, że plan premium ma pokrycie w realnym popycie, nie tylko w PowerPoincie.

**Decyzja:** propozycji prowizji NIE przyjmujemy — uzasadnienie w `PRODUCT_BACKLOG.md` (sekcja „ODRZUCONE KIERUNKI MONETYZACJI"). Trzymamy fundament „wystawianie darmowe na zawsze".

**Argumentacja na poniedziałkowy pitch:** 3 ścieżki współpracy zamiast prowizji — Partner Założycielski (darmowy, status + ekspozycja), Concierge (płatna usługa za nasz czas, nie za platformę), Premium visibility (gdy uruchomimy, pierwszeństwo dla założycieli). Skrypt 60-sekundowy w odpowiedzi Agenta Biznesowego z 2026-05-13.

**Materiał argumentacyjny do kolejnych rozmów:** „inna agencja sama zaproponowała prowizję — odmówiliśmy, bo trzymamy się modelu" → buduje wiarygodność dyscypliny.

---

## 2026-06-02 — reaktywacja agencji „ciepłych ale martwych"

Problem: agencje z pierwszej fali były na „tak", ale nie wysłały Excela ani nie dodały żadnego nośnika.

**Diagnoza:** (1) entuzjazm ≠ zadanie na czyimś biurku — dodawanie to niczyja robota; (2) zrobili z tego projekt („przepisać całą bazę"), nie zadanie → utknęło na „kiedyś"; (3) **zero pilności, bo kampania do reklamodawców REALNIE nie ruszyła** — główne miasta nie mają jeszcze gęstości podaży. Agencja to wyczuwa.

**Dowód społeczny (świeży):** jedna agencja przysłała gotową listę **250 nośników jednym plikiem**. ⚠️ Jeszcze NIE wgrane — czekamy na zdjęcia. W rozmowach NIE mówić „są na mapie", tylko „przysłała całą listę, czekam na zdjęcia" (pokazuje skalę + że jak ktoś się zdecyduje, idzie szybko). Bez podawania nazwy.

**Uwaga procesowa:** import concierge jest u nas zablokowany na zdjęciach. Wg `MARKETING_ASSETS.md` nośnik bez zdjęcia można aktywować (niższa widoczność) lub trzymać jako szkic — decyzja foundera, żeby czekać na zdjęcia.

**Rozwiązanie → skrypt CC-04 w `MARKETING_ASSETS.md` (wersja ludzka):** grać w otwarte karty — „kampanii nie odpaliłem, bo miasta puste, dlatego do Was dzwonię". Zdejmuje poczucie winy z agencji, robi z nich brakujący element. Prosić o ICH miasto, dowód społeczny 250 nośników, fallback: Excel z 10 pozycjami albo 1 nośnik na żywo. Import biorę na siebie.

**Korekta wcześniejszego TODO:** hook „na początku maja ruszamy" z CC-01/02/03 jest fałszywy NIE dlatego, że maj minął — kampania w ogóle nie wystartowała (brak podaży w miastach). Mówić prawdę: „odpalę, gdy główne miasta się zapełnią".

---

## Operatorzy z zaimportowanym portfolio (podaż)

Rejestr realnych partnerów podażowych, których oferta trafiła do bazy. Uzupełniać przy każdym kolejnym imporcie.

### Optokom — ~2026-06-10 · 192 nośniki · ZAIMPORTOWANY (prod)

- **Skala:** 192 billboardy (~65% ówczesnego katalogu; baza urosła 104 → 296).
- **Charakterystyka:** wszystkie 5,04×2,38 m (12 m²), `status=reserved` ale `is_active=true`, opis **templatowany** (różni się adres/miasto/cena), wspólny obraz `advertisements/optokom-placeholder.jpg`.
- **Import:** `scripts/import_optokom.py` / `OptokomSeeder`. Robocze artefakty (geocache, draft maila) usunięte w commicie `4c368b1`.
- **Otwarty problem (SEO):** placeholder + thin/near-duplicate opisy → Google „crawled/discovered, not indexed" (skok niezaindeksowanych, audyt `SEO_TECH_AUDIT.md` 2026-06-15). **Najsilniejszy lewar: realne zdjęcia** (Optokom ma dosłać) — unikalny obraz = unikalna strona = odblokowana indeksacja. Wymusiło też B-6 (anty-flood sortu listy).
- **Aktualizacja 2026-06-15:** realne zdjęcia **pobrane ze strony optokom.pl** (`scripts/fetch_optokom_images.py` — crawl /lokalizacje → galerie po kodzie `ref`, fallback `design/{ref}.webp`, konwersja jpg+webp). **168/192 nośników ma teraz unikalne foto** (24 bez galerii zostają na placeholderze). `optokom.json` zaktualizowany, przeseedowano. To wprost adresuje lewar #1 z audytu SEO (unikalny obraz = unikalna strona).
- **TODO:** dopytać Optokom o foto dla pozostałych 24; zróżnicować opisy per nośnik (thin-content).

### Outdoor 3miasto — 2026-06-15 · 12 nośników · ZAIMPORTOWANY (lokalnie, test)

- **Kontakt:** Michał Parafianowicz (koordynator ds. administracji i marketingu), `koordynator@outdoor3miasto.com`. Wszedł po mailu reaktywacyjnym (`biuro@outdoor3miasto.com` był na liście 72) — odpisał **wypełnionym naszym Excelem + paczką zdjęć**.
- **Skala:** 12 wybranych billboardów (Pomorskie/Mazowieckie/Warmińsko-maz.: Chwaszczyno S6, Mińsk Maz. A2, Olsztyn, Toruń, Słupsk, Reda…). Część wielkoformatowa 12×6 m przy S6/A2 (3100–3600 zł/mc), reszta 5,04×2,38 m (~850 zł/mc).
- **Dane:** bogaty Excel (orientacja, klasa drogi, natężenie, kierunek/typ ruchu, opisy gotowe), część z gotowymi współrzędnymi WGS84. Wszystkie 12 ze **zdjęciem** (lokalne pliki z paczki → jpg+webp w storage). Wszystkie zgeokodowane.
- **Import:** `scripts/import_outdoor3miasto.py` (Excel→`outdoor3miasto.json`, mapowanie PL→kanoniczne, kopiowanie+konwersja zdjęć) → `Outdoor3miastoSeeder`. Wszystkie `status=active`.
- **Czeka na:** numer telefonu (podadzą później — kontakt na razie mailowy, NIE blokuje publikacji). Odpowiedź mailowa do Michała: panel = magic-link na `reklamap.pl/zarzadzaj` (per e-mail), oferta „więcej nośników w dowolnym formacie / ze strony pobierzemy sami".
- **Wzorzec:** druga agencja, która odpisała Excelem/linkiem (po reklama.ai). Warto dodać do `MARKETING_ASSETS.md` szablon „odpowiedź na zgłoszenie agencji".

### reklama.ai — 2026-06-15 · 411 nośników · ZAIMPORTOWANY (lokalnie, test)

- **Kontakt:** Ewelina@reklama.ai, tel. 602 534 843. Wszedł po mailu reaktywacyjnym (kampania 72 agencje, 2026-06-15) — odpisał linkiem do swojej strony z ofertą.
- **Skala:** 411 nośników (409 billboardów + 2 ekrany LED), 48 miejscowości, gros wokół Kłodzka/Dzierżoniowa. 36 wolnych, reszta zajęte.
- **Cena:** 45 zł/m² netto **/mc** → przeliczone na kwotę miesięczną per nośnik (270–6165 zł, mediana ~810). Wszystkie: **cena do negocjacji = tak**, **faktura VAT = tak**.
- **Pozyskanie danych:** scrapowane ze strony (statyczny HTML, paginacja) — `scripts/import_reklama_ai.py` → `reklamap-os/status/reklama_ai_nosniki.csv`. **Mają własne zdjęcia** (placeholder z logo niepotrzebny). Zgoda na pobranie: udzielona telefonicznie/mailowo.
- **Import do bazy:** `php artisan import:reklama-ai --purge` (lokalnie). 34 active (wolne) · 374 reserved (zajęte) · **3 draft/ukryte** (telebim1, telebim2, siatka 137 m² nr 461 — niepewna wycena /m²).
- **Czeka na:** mail do Eweliny — potwierdzenie stawki dla LED i siatki 137 m² (wtedy 3 drafty → active/reserved). Do produkcji: pobrać zdjęcia do własnego storage (teraz hotlink), dograć dokładne współrzędne (teraz geokod na poziomie miasta + offset).
