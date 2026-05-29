# Log Analityczny — ReklaMap

Historia przeglądów danych (GSC / GA4 / stats.php) i rekomendacji Agenta Analityka.
Najnowszy wpis na górze. Nie nadpisuj — dopisuj.

---

## 2026-05-29 — pełny przegląd (pierwszy z danymi konwersji)

**Dane wejściowe:**
- `stats-2026-05-29.md` — 30 dni, tylko realne ogłoszenia (seed wykluczony)
- GSC: `imports/gsc-2026-05-29/` — Zapytania (≈230 fraz), Strony (124 URL), Wykres dzienny, Kraje, Urządzenia. Zakres: **ostatnie 3 miesiące** (bez kolumny porównania okresów, ale Wykres daje trend dzienny)
- GA4: `imports/ga4-2026-05-29/` — Pozyskiwanie ruchu (kanały), Zdarzenia, Strony i ekrany. Zakres: **2026-03-01 – 2026-05-29** (90 dni)

**Czego zabrakło:** brak rozkładu Referral po source/medium (nie wiadomo, skąd 10 sesji referral), brak eksportu ogłoszeń (nie powiązano konwersji z typem nośnika), GA4 zagregowane za 90 dni (bez trendu tygodniowego, ale to nieistotne — patrz niżej).

**Faza projektu:** nadal **PODAŻ** (cold-calling do agencji/sprzedawców OOH). Founder potwierdził: **ostatni tydzień bez telefonów = zastój** w pozyskiwaniu (to tłumaczy ewentualny spadek Direct w ostatnim tygodniu, NIE jest to problem kanału). Reklama dla reklamodawców (popyt) jeszcze nie ruszyła.

### Top wnioski
1. **🚨 SEO LECI W DÓŁ OD ~18.05 — najpewniej skutek wyłączenia prerendera.** `Wykres.csv`: wyświetlenia 08.05 = 347, szczyt 18.05 = 389, potem zjazd do ~80/dzień (27.05); śr. pozycja z ~24 → ~32. Spadek ~4–5× impresji + pogorszenie pozycji tuż po dacie padu prerender.io (18.05, [[project_prerender_disabled]]). Boty dostają goły SPA → Google przestaje renderować/wypada ze SERP. **To unieważnia sens pisania nowych treści, dopóki nie przywrócimy renderowania dla botów.** Priorytet #1.
2. **Marka i Direct ciągną wszystko; SEO non-brand prawie nie konwertuje.** GSC: `reklamap` 77/93 klików (CTR 83%) — reszta klików to pojedyncze sztuki. GA4: Direct 483 sesje (73%), 21 z 22 kluczowych zdarzeń, 210 s/sesję; Organic Search 153 sesje / 90 dni, ale **1 kluczowe zdarzenie**, 92 s/sesję. Silnik = telefony foundera, nie organic.
3. **🟢 Pierwsze realne dane popytowe — mimo braku akcji reklamowej.** Zdarzenia kontaktu wreszcie fire'ują: `contact_phone_click` 16 (4 userów), `contact_form_submit` 3 (2), `view_item` 122 (29). ~6/29 oglądających ogłoszenie podjęło kontakt (~20%). Liczby mikre, ale mechanizm pomiaru działa i jest wczesny sygnał popytu.
4. **⚠️ Zdarzenia per-krok lejka są BEZUŻYTECZNE.** `add_listing_step_view` 152 zdarzeń / **5 userów**, `add_listing_step_complete` 118 / **3 userów** — przy 49, którzy zaczęli (`add_listing_start`). Albo deploy świeższy niż 7 dni, albo eventy re-fire'ują (30–39 zdarzeń/usera). Najważniejsza dźwignia z 12.05 (diagnoza przecieku lejka) wciąż zablokowana danymi. → dev/Biznesowy.
5. **Lejek „dodaj ogłoszenie" zdrowy:** 49 userów start → 28 sukces = **57%** (user-level; event 279→127 = 45%). Lepiej niż 34% z 12.05 (inne zakresy, ale kierunek dodatni). `/dodaj-powierzchnie-reklamowa`: 612 s śr. zaangażowania, 22 kluczowe zdarzenia — strona-silnik podaży.
6. **Blog martwy w zaangażowaniu:** najlepsze artykuły 1–5 wyświetleń, czas 0,3–5,6 s (bounce) albo n=1. `billboard-reklama` mimo 348 impr w GSC (poz 12) → 2 kliki, 0,3 s. Częściowo skutek prerendera, ale blog dziś nie dowozi.
7. **Podaż rośnie:** 63 realne ogłoszenia, +56 w 30 dni, +13 w 7 dni (mimo zastoju w telefonach). 5 109 odsłon/30 dni, 1 zapytanie przez formularz (norma fazy podaży).

### ➡️ DLA STRATEGA (brief — po przywróceniu prerendera; sort wg potencjał/wysiłek)
| Priorytet | Fraza / temat | Potencjał (impr / poz, GSC 3 mies.) | Silos | Akcja |
|---|---|---|---|---|
| 1 | **ekran led cena / reklama led cena** + miasta LED | `reklama na ekranach led kraków` 41/14, `...warszawa` 39/15, `ekrany led kraków` 38/19; białe plamy cenowe `reklama led cena` poz 40, `ekrany reklamowe led cena` poz 63. Kategoria /ekrany-led: 278 impr GSC + 94 wejścia GA4 / 147 s | poradniki + kategorie | **DOKOŃCZYĆ artykuł `ekran-led-cena`** (briefem 25.05 #2, nigdy nie napisany — dane GSC teraz potwierdzają popyt). Plus rozbudowa kategorii `ekrany-led/krakow` i `ekrany-led/warszawa`. Walidacja Ahrefs: `ekran led cena` vs `reklama led cena`. |
| 2 | **reklama outdoor Lublin** | BIAŁA PLAMA: `powierzchnie reklamowe lublin` poz **7.7** / 41 impr (wisienka, 0 klików!), `reklama na ekranach led lublin` 23, `reklama na uczelniach lublin` poz 10.7, `citylight lublin` 10. 3 realne ogłoszenia w bazie, BRAK artykułu lokalizacyjnego | lokalizacje | Nowy artykuł `reklama-outdoor-lublin` (wzorzec Łódź/Olsztyn/Bydgoszcz). Pomiń ATP/Ahrefs (geo). Perplexity: ceny per dzielnica, MPK Lublin (trolejbusy!), uchwała krajobrazowa, **angle uczelniany** (`reklama na uczelniach lublin` poz 10.7 — UMCS/KUL, miasto studenckie), demografia. |
| 3 | **reklama mobilna (przyczepki) — pillar/poradnik** | Klaster popytu: `reklama mobilna bydgoszcz` **104**/16.7, `reklama mobilna kraków` 32/19, `reklama mobilna warszawa` 25/19, kategoria /reklama-mobilna 133 impr. Mamy `reklama-na-samochodzie`, ale brak treści pod mobilne przyczepki/billboardy LED na lawecie | poradniki | Rozbudowa istniejącego `reklama-na-samochodzie` lub nowy artykuł pod przyczepki reklamowe + wzmocnienie kategorii `reklama-mobilna/[miasto]`. Bydgoszcz (104 impr, 0 klików) — sprawdzić, czy artykuł `reklama-outdoor-bydgoszcz` dobrze pokrywa frazę mobilną. |
| 4 | **totemy reklamowe + miasta** | Wisienki: `totemy reklamowe wrocław` 39/12, `totemy reklamowe poznan` 24/17, `totemy reklamowe katowice` 12/9 | trendy/kategorie | Wzmocnienie kategorii `totemy-reklamowe/[miasto]` + link z artykułu `totem-reklamowy`. Niższy priorytet (mniejsze impresje). |

> Uwaga dla Stratega: temat #1 (`ekran-led-cena`) to dług z briefu 25.05 — był zatwierdzony, ale nie powstał. Dane GSC z 29.05 go potwierdzają mocniej. Sprawdź kolejkę prawno-regulacyjną w STRATEGY_LOG (pozwolenie/zgłoszenie tablicy) — te zostają, ale po LED/Lublin.

### ➡️ DLA UŻYTKOWNIKA (kanały — faza PODAŻY)
- **Wznów telefony.** Zastój = zatrzymanie jedynego działającego kanału podaży (Direct 73%, 21/22 kluczowych zdarzeń). To nie SEO Cię utrzymuje — to Twoje rozmowy.
- **UTM-y wciąż niewdrożone** — Direct nadal nierozdzielony (telefony vs prawdziwy direct). Powtórka rekomendacji z 12.05: linki do dzwonionych z `?utm_source=outreach&utm_medium=phone&utm_campaign=ooh-agencje`.
- **Organic Social = martwy** (7 sesji / 3 s). Nie inwestuj, dopóki nie ma gęstej bazy do pokazania.
- **Referral 10 sesji — zbadać źródło** (potrzebny eksport GA4 source/medium): jeśli to wartościowa domena branżowa, warto tam być.

### ➡️ DLA ARCHITEKTA SEO
- **🚨 PRZYWRÓCIĆ PRERENDERING — pilne.** Spadek impresji/pozycji od 18.05 = deindeksacja przez SPA dla botów. Nowy provider / self-host prerender / SSR. To blokuje całe SEO. ([[project_prerender_disabled]])
- **CTR-owe trupy (dobra pozycja, 0 klików):** `reklama tranzytowa kraków` poz **3.55**, `reklama citylight olsztyn` poz 7.8 / 64 impr, `powierzchnie reklamowe lublin` poz 7.7 / 41 impr, `citylighty warszawa` poz 8.1, `reklama na ekranach led katowice` poz 6.4. Title/description + dopasowanie URL do intencji. (Uwaga: część „0 klików" może być wtórna do deindeksacji — rewalidować po fixie prerendera.)
- **www vs non-www nadal w danych** (`/powierzchnie-reklamowe/poznan` www poz 5.5 vs non-www poz 10.1; analogicznie lodz, banery). Zweryfikować, czy 301 z 07.05 jest skonsolidowane przez Google.
- **Osierocone/błędne URL-e bloga w GA4:** `/blog/pozwolenie-na-billboard-jak-uzyskac`, `/blog/rynek-ooh/ooh-vs-digital-porownanie` (artykuł+kategoria spoza INDEX), `/blog/ile-kosztuje-reklama-outdoor` i `/blog/reklama-w-transporcie-publicznym` (bez segmentu kategorii). Sprawdzić 404 / niespójne linkowanie / stare ścieżki w sitemapie.

### ➡️ DLA BIZNESOWEGO
- **Zdarzenia per-krok lejka nie nadają się do analizy** (3–5 userów). Najważniejsza dźwignia z 12.05 (gdzie przecieka „dodaj ogłoszenie") wciąż zablokowana. Decyzja: zweryfikować implementację (re-fire?) + dać 2–3 tyg. zbierania, zanim B-2 ruszy.
- **Pierwsze sygnały popytu są** (16 klików w telefon, 3 formularze) mimo braku akcji reklamowej. Punkt decyzyjny: kiedy flip w fazę popytu — ale dopiero po (a) fixie prerendera, (b) gęstszej bazie.

### Status wdrożenia (do weryfikacji przy kolejnym przeglądzie)
- [ ] **🚨 Przywrócenie prerenderingu** — NOWE, priorytet #1 (Architekt/dev)
- [ ] **Zdarzenia per-krok lejka** — weryfikacja re-fire + dosbieranie danych (dev/Biznesowy)
- [ ] **UTM-y na outreachu** — wciąż niezrobione (zadanie usera)
- [→] **Brief 4 tematów dla Stratega** (LED cena + Lublin + reklama mobilna + totemy)
- [ ] **GA4: Referral source/medium** — dobrać przy następnym eksporcie
- [→] **Wznowienie cold-callingu** (user)

---

## 2026-05-25 — brief uzupełniający (bez świeżych eksportów)

**Dane wejściowe:** brak nowych eksportów (ostatni przegląd 13 dni temu — 12.05). Analiza oparta na pozostałych, niewykorzystanych sygnałach z briefu 12.05 + audyt luk w `blog/INDEX.md` i `STRATEGY_LOG.md`.

**Powód uzupełnienia:** user zlecił 2-3 nowe artykuły. Pełny refresh danych można zrobić, gdy będzie deploy zdarzeń kontaktu + UTM-y na outreachu (wtedy GSC/GA4 dadzą jakościowo nowe info). Na teraz wystarczą sygnały z 12.05, które nie zostały przerobione na blog.

### Top wnioski (uzupełnienie do 12.05)
1. **Olsztyn = mocny sygnał miejski, brak artykułu lokalizacyjnego.** `reklama citylight olsztyn` poz 6.8 / 37 impr (3. najmocniejsza fraza miejska po Poznaniu i Bydgoszczy). Strona kategorii dostała opis, ale silos `lokalizacje` nie ma `reklama-outdoor-olsztyn`. To pierwsze "średnie miasto" z potwierdzonym popytem — naturalne rozszerzenie silosu po pokryciu G8.
2. **Białe plamy cenowe dla LED.** `citylighty cena` (poz 10.4) wzmocnione refreshem `citylight-reklama` 12.05. Analogicznej, dedykowanej treści cenowej brakuje dla LED: mamy `telebim-ekran-led-reklama` (trendy/koncepcyjny), nie cenowy. Frazy `ekran led cena`, `reklama led cena`, `telebim cena` — do walidacji przez Stratega (intencja zakupowa, prawdopodobnie wisienki w GSC po 30+ dniach).
3. **DOOH/programmatic — dojrzało do walidacji.** Termin z `STRATEGY_LOG.md` (~26.05) zbiega się z dzisiejszym zleceniem. Silos `trendy` ma 3 artykuły — głodny. Strateg robi rewalidację Ahrefsem.

### ➡️ DLA STRATEGA (brief — 3 tematy do produkcji, wybrane przez foundera 2026-05-25)
| Priorytet | Fraza / temat | Potencjał (impr / poz) | Silos | Akcja |
|---|---|---|---|---|
| 1 | `reklama outdoor olsztyn` (+ frazy citylight/billboard Olsztyn) | wisienka GSC 12.05: `reklama citylight olsztyn` poz 6.8 / 37 impr | lokalizacje | Nowy artykuł `reklama-outdoor-olsztyn`. Pomiń ATP/Ahrefs (geo-frazy), idź w Perplexity: ceny per dzielnica, MZK Olsztyn (cennik tramwaj+autobus), uchwała krajobrazowa (czy jest?), populacja, ruch turystyczny (Mazury), największe ulice/centra handlowe. Wzorzec: artykuł Łódź/Katowice. |
| 2 | `ekran led cena` / `reklama led cena` / `telebim cena` | sygnał pośredni: `ekrany led kraków/warszawa` poz 13–19 (briefem 12.05); brak danych volume dla fraz cenowych | poradniki | Nowy artykuł — sugerowany slug `ekran-led-cena` (lub po walidacji `reklama-led-cena`). Walidacja Ahrefs: która z fraz ma >100 vol i Easy KD. Perplexity: cennik wynajmu LED (małe/średnie/duże), CPM digital vs static, formaty (mobile LED, citylight digital, telebim stadionowy), koszty produkcji spotu, formalności (>2,5 m² pozwolenie). Linki wewnętrzne: `telebim-ekran-led-reklama` (trendy), `citylight-reklama` (cena CL digital), `ile-kosztuje-reklama-outdoor` (pillar). |
| 3 | `dooh` / `programmatic outdoor` / `programmatic ooh` | z kolejki Stratega (termin ~26.05), brak danych GSC | trendy | Rewalidacja Ahrefs — jeśli wszystkie frazy <100 vol, piszemy pod topical authority (silos trendy = 3 art.). Perplexity: udział DOOH w rynku PL 2025/2026, programmatic OOH definicja, ekosystem (Hivestack/Vistar/Broadsign), case studies, prognoza wzrostu, audience-based buying, modele transakcyjne (private deal / open exchange). |

> Uwaga dla Stratega: temat #3 wymaga walidacji Ahrefs (już zaplanowanej w kolejce). Tematy #1 i #2 są zatwierdzone — przy #2 walidacja Ahrefs decyduje tylko o ostatecznym wyborze slug-a/keyworda głównego.

### ➡️ DLA UŻYTKOWNIKA
Bez nowości — kanały bez zmian względem 12.05 (faza PODAŻY, Direct/outreach to wciąż główny driver). Pełny przegląd po deployach: zdarzeń kontaktu (`contact_phone_click`/`contact_form_submit`) i lejka per krok `add_listing_step_*` — wtedy będzie sens uruchomić nowe eksporty GA4.

### Status wdrożenia
- [ ] Pełny przegląd danych (stats.php + GSC + GA4) — odłożony do momentu, aż na produkcji będą zdarzenia kontaktu i lejek per krok (≥7 dni od deployu)
- [→] Brief 3 tematów przekazany Strategowi (A + B + C)

---

## 2026-05-12 — pierwszy przegląd

**Dane wejściowe:**
- `stats-2026-05-12.md` — snapshot bazy, wyświetlenia 7 dni (tylko realne ogłoszenia, seed wykluczony)
- GSC: `imports/gsc-2026-05-12/` — Zapytania (241 fraz), Strony (90 URL), + kraje/urządzenia
- GA4: `imports/ga4-2026-05-12/` — Pozyskiwanie ruchu, Pozyskiwanie użytkowników, Strona docelowa, Strony i ekrany, Zdarzenia. Zakres: **2026-04-14 – 2026-05-11** (28 dni)

**Czego zabrakło:** brak porównania okresów w GSC (więc bez trendów), brak eksportu ogłoszeń (nie powiązano "co konwertuje" z typem nośnika), platforma istnieje ~6 tyg. — wszystkie wolumeny małe, wnioski wstępne.

**Faza projektu (kontekst od founder'a, kluczowy dla interpretacji):** ReklaMap jest teraz w fazie **budowania PODAŻY** — founder dzwoni do osób wystawiających ogłoszenia na OLX, żeby wciągnąć właścicieli nośników i zbudować bazę ogłoszeń. Strona popytowa (pozyskiwanie reklamodawców) jest dopiero w planach. Dlatego: (a) Direct = efekt tych telefonów, nie anomalia; (b) zerowy ruch na formularzu kontaktowym jest oczekiwany — to nie problem na teraz. Metryka sukcesu na ten etap = przyrost realnych ogłoszeń i konwersja lejka "dodaj ogłoszenie", NIE leady reklamodawców.

### Top wnioski
1. **Cold-calling do sprzedawców z OLX działa** — 278 z 414 sesji (67%) to Direct, czyli ludzie, do których founder zadzwonił, wchodzący bezpośrednio na stronę. Z tego ruchu: `add_listing_start` 119 → `add_listing_success` 40 w 28 dni (zgadza się z "nowe realne 30 dni = 41" w stats). To jest TERAZ najważniejszy lejek i on się kręci. **Co poprawić:** (a) tagować linki wysyłane w wiadomościach do sprzedawców UTM-em (`utm_source=outreach&utm_medium=...`), żeby oddzielić ich od prawdziwego Direct i mierzyć konwersję telefon→ogłoszenie; (b) filtr ruchu wewnętrznego w GA4 (wyklucz IP founder'a/dev), żeby Direct nie był sztucznie napompowany.
2. **Lejek "dodaj ogłoszenie": 119 startów → 40 sukcesów (≈34%).** Strona `/dodaj-powierzchnie-reklamowa`: 122 wyświetlenia, śr. zaangażowanie ~348 s — ludzie spędzają ~6 min i 2/3 odpada. To NAJWAŻNIEJSZA dźwignia na obecny etap: przy ~120 startach/mies. odzyskanie 10 pp = +12 ogłoszeń/mies. bez żadnego nowego ruchu. → obejrzeć UX formularza (długość, kroki, na czym ludzie utykają — warto dodać zdarzenia per-krok). (→ Biznesowy/dev)
3. **SEO: jesteśmy zaindeksowani, ale za nisko.** Mnóstwo fraz lokalnych (bilbordy/citylight/LED + miasto) ma impresje przy pozycji 13–70 i **0 kliknięć**. Strony kategorii istnieją, działa tylko ten URL, który wskoczył na 1. stronę (`/powierzchnie-reklamowe/poznan` — poz 5.5, CTR 34%, 9 klików). Wzorzec działa — trzeba go dociągnąć dla kolejnych miast. To inwestycja pod fazę popytową (reklamodawcy szukają w Google), ale robota dziś = efekt za kilka mies., więc warto zacząć.
4. **Duplikacja www vs non-www.** GSC pokazuje te same strony osobno jako `reklamap.pl/...` i `www.reklamap.pl/...` (np. `/powierzchnie-reklamowe/poznan`: non-www poz 9.1/96 impr/0 klików vs www poz 5.5/26 impr/9 klików; analogicznie `/banery/lodz`). Sygnały rozjeżdżają się między dwie wersje URL — to kanibalizacja/rozcieńczenie. → Architekt SEO (301 z jednej wersji na drugą, jedna kanoniczna).
5. **Pomiar pod fazę popytową — przygotować teraz, nie później.** 0 zapytań przez formularz i tylko 3× generyczny `click` w GA4; brak zdarzeń `click_phone` / `click_email` / `contact_form_submit`. Na teraz to nie boli (popytu jeszcze nie ma), ale gdy ruszysz pozyskiwanie reklamodawców, będziesz potrzebował tych zdarzeń od pierwszego dnia, żeby ocenić, czy marketplace dowozi kontakty. Tani task do zrobienia "z wyprzedzeniem". (→ Biznesowy/dev)
6. **CTR-owe absurdy w GSC:** `reklama tranzytowa kraków` poz 1.67 i 0 klików, `reklama tranzytowa poznan` poz 2.89 i 0 klików; `reklama citylight olsztyn` poz 6.81 i 0 klików (strona `/citylighty/olsztyn/...` poz 6.17, 70 impr, 0 klików). Pozycja jest, klika nie ma → problem z title/description albo z dopasowaniem URL-a do intencji. → Architekt SEO.
7. **Blog ledwo zipie, ale ma potencjał:** `/blog/poradniki/billboard-reklama` — 220 impresji, poz 13.2, CTR 0.9%, 2 kliki. Tuż za 1. stroną. Pojedynczy push (rozbudowa, lepszy title, linkowanie wewnętrzne) może go wepchnąć wyżej. To jedyny artykuł z realnymi impresjami — reszta bloga praktycznie niewidoczna w GSC.

### ➡️ DLA STRATEGA (brief — frazy/tematy wg potencjału wzrostu)
| Priorytet | Fraza / temat | Potencjał (impr / poz) | Akcja |
|---|---|---|---|
| 1 (wisienka) | `reklama mobilna bydgoszcz` | 42 impr / poz 18.1 | Strona kategorii `reklama-mobilna/bydgoszcz` — rozbudować treść/nagłówki, podlinkować z ogłoszenia przyczepki Bydgoszcz (które już ma 61 impr). Cel: wejść do top 10. |
| 2 (wisienka) | `reklama citylight olsztyn` | 37 impr / poz 6.8 | Strona już na poz 6 — problem nie w treści, tylko w CTR. Poprawić title/description (do Architekta), rozważyć dodanie krótkiego opisu lokalizacji. |
| 3 (wisienka) | `reklama na ekranach led warszawa` + `...led kraków` + `ekrany led kraków` | 25 / 14 / 14 impr, poz 13–19 | Strony `ekrany-led/warszawa` i `ekrany-led/krakow` — rozbudować (czym jest reklama na LED, orientacyjne stawki, przykłady), więcej linków wewnętrznych z `/ekrany-led`. |
| 4 (biała plama / artykuł) | `citylighty cena` (+ `citylight cena`, `ile kosztuje citylight`) | 9 impr / poz 10.4, frazy cenowe | Brak dedykowanej treści cenowej — artykuł poradnikowy "Ile kosztuje reklama na citylightach?" z widełkami stawek. Frazy "cena/ile kosztuje" konwertują dobrze (intencja zakupowa). |
| 5 (wisienka) | `reklama na przystankach lódz` | 9 impr / poz 8.9 | Doprecyzować, na którą stronę to ma celować (citylight Łódź / komunikacja Łódź); rozbudować. |
| 6 (wisienka, treść) | `billboard reklama` (artykuł blogowy) | 220 impr / poz 13.2 | Odświeżyć i rozbudować istniejący `/blog/poradniki/billboard-reklama` (FAQ, stawki, sekcje pod long-tail), podlinkować z kategorii billboardów. |

> Uwaga dla Stratega: zanim ruszysz research na AnswerThePublic/Ahrefs — te frazy są już POTWIERDZONE realnymi impresjami z GSC, więc mają pierwszeństwo przed nowymi pomysłami z zerowym wolumenem. Sprawdź `STRATEGY_LOG.md`, czy któraś nie jest już w planie.

### ➡️ DLA UŻYTKOWNIKA (kanały — w kontekście fazy PODAŻY)
- **To, co teraz działa = telefony do sprzedawców z OLX (Direct).** Nie zmieniaj kursu — to dowozi ogłoszenia. Ale: (a) wysyłając tym ludziom link (SMS/WhatsApp/mail), dawaj go z UTM-em `?utm_source=outreach&utm_medium=phone&utm_campaign=olx-supply` — wtedy zobaczysz, ilu z dzwonionych faktycznie wchodzi i ile z tego wystawia ogłoszenie (dziś to ginie w "Direct"); (b) ustaw filtr ruchu wewnętrznego w GA4 (wyklucz swoje IP), bo Direct jest zawyżony.
- **Kanały pozyskiwania PODAŻY do przetestowania** (właściciele nośników, nie reklamodawcy): grupy FB lokalnego biznesu / "wynajmę powierzchnię reklamową", lokalne grupy ogłoszeniowe, ew. inne portale ogłoszeniowe poza OLX. To bliżej roli **Agenta Marketera** (on robi skrypty i bazy) — Analityk tylko mierzy, który kanał daje ogłoszenia. Próg: po 30 dniach kanał ma dać > X nowych ogłoszeń, inaczej odpuszczamy.
- **Kanały popytowe (reklamodawcy) — jeszcze NIE teraz.** Grupy FB marketerów/agencji, LinkedIn, branżowe portale OOH, Google Ads na frazy cenowe z GSC (`citylighty cena`, `reklama mobilna [miasto]`) — to ma sens dopiero gdy baza nośników jest na tyle gęsta, że reklamodawca coś znajdzie, i gdy mierzysz konwersję kontaktu. Wrzucone do logu, żeby nie zapomnieć — uruchomić przy przejściu w fazę popytu, po konsultacji z Agentem Biznesowym.

### ➡️ DLA ARCHITEKTA SEO
- **Ujednolicić www vs non-www** — 301 na jedną wersję, self-referencing canonical. Teraz sygnały dzielą się na dwa URL-e (potwierdzone w GSC).
- **CTR-owe trupy** — `reklama tranzytowa kraków`/`poznan` (poz 1–3, 0 klików), `reklama citylight olsztyn` (poz 6, 0 klików): przejrzeć title/description tych URL-i, sprawdzić czy w SERP nie zjada ich feature i czy URL pasuje do intencji frazy.
- Sprawdzić działanie tagu GA4 (przy okazji — wcześniej raporty pokazywały "brak danych"; teraz dane są, ale warto potwierdzić, że tag jest stabilny i że strona ma poprawny Measurement ID).

### ➡️ DLA BIZNESOWEGO
- **Zdarzenia kontaktu nie trafiają do GA4** — UWAGA (ustalenie Architekta): są zdefiniowane w `frontend/src/utils/analytics.ts` (`contact_phone_click`, `contact_email_click`, `contact_form_submit`, `view_item`), ale **nie są wpięte w `AdDetailPage.vue`** — ten widok w ogóle nie importuje `analytics`. Stąd w GA4 brak tych zdarzeń. Fix: wpiąć wywołania w przyciski telefon/e-mail/formularz na stronie ogłoszenia + oznaczyć je jako kluczowe zdarzenia w GA4 Admin. Bez tego nie zmierzymy wartości marketplace ani konwersji żadnego kanału. (To pół-techniczne, ale decyzja "co liczymy jako konwersję" jest biznesowa.)
- **Lejek dodawania ogłoszenia ~34%** — czy to OK, czy formularz jest za długi? Przy ~120 startach/mies. odzyskanie nawet 10 pp = +12 ogłoszeń/mies.

### Status wdrożenia (aktualizacja 2026-05-12 wieczór)
- [ ] **GA4: filtr ruchu wewnętrznego (IP)** — instrukcja przekazana userowi, czeka na wykonanie w GA4 Admin
- [~] **GA4: zdarzenia kontaktu** — ✅ KOD wdrożony (`AdDetailPage.vue` + `AdContactForm.vue`: `view_item`, `contact_phone_click`, `contact_form_submit`); ⏳ czeka na **deploy frontu** + oznaczenie kluczowych zdarzeń w GA4 Admin (`add_listing_success` od razu; `contact_phone_click`/`contact_form_submit` po deployu)
- [x] **Architekt: 301 www→non-www** — ✅ ZAŁATWIONE: `frontend/public/.htaccess` zawiera już 301 `www.reklamap.pl/*` → `reklamap.pl/*` (dodane ~2026-05-07); potwierdzone w GSC (raport indeksowania: URL-e `www` mają status „zawiera przekierowanie"). `FRONTEND_URL` na prod = `https://reklamap.pl` ✓. Krok w panelu histido niepotrzebny. Google sam skonsoliduje stare wpisy.
- [~] **Architekt: title/description** — ✅ KOD wdrożony: szablonowy `<title>`/`<meta>` na stronie ogłoszenia (zamiast surowego tytułu wystawcy, fix dla `citylight olsztyn`); strony kategorii `reklama-w-transporcie/krakow` i `/poznan` dostały dedykowane opisy z frazą transakcyjną (Pisarz); syntezowany fallback opisu dla kombinacji typ×miasto bez ręcznego wpisu. Czeka na deploy.
- [~] **Strateg: brief → Pisarz** — ✅ ZROBIONE w zakresie blogowym: `billboard-reklama` (poz. 13/220 impr) i `citylight-reklama` (`citylighty cena` poz. 10.4) odświeżone i zrecenzowane (czekają na publikację w panelu). Pozostałe punkty briefu = strony kategorii (✅ opisy dodane: olsztyn/gdynia/bydgoszcz citylighty, transport krakow/poznan, mobilna bydgoszcz/warszawa/krakow, totemy poznan, banery lodz, miasta olsztyn/koszalin) lub robota Architekta (✅ kod). Nic z briefu nie wymaga już nowego artykułu blogowego.
- [ ] **UTM-y na linkach zewnętrznych** (`?utm_source=outreach&utm_medium=phone&utm_campaign=olx-supply` na linkach do sprzedawców) — zadanie behawioralne usera, nie kod
- [x] **B-1: zdarzenia per-krok formularza „dodaj ogłoszenie"** (`add_listing_step_view`/`add_listing_step_complete`) — ✅ KOD wdrożony w `AddAdPage.vue`; czeka na deploy (potem analiza, na którym kroku spadek → B-2)

**Legenda:** `[x]` zrobione · `[~]` kod gotowy, czeka na deploy/działanie usera · `[ ]` do zrobienia (po stronie usera).

---

<!-- SZABLON WPISU:

## [DATA] — przegląd [zakres dat danych]

**Dane wejściowe:** GSC (zapytania/strony/wisienki), GA4 (pozyskiwanie/strony/konwersje), stats.php Xd, eksport ogłoszeń: tak/nie
**Czego zabrakło:** ...

### Top wnioski
1. ...

### ➡️ DLA STRATEGA
| Priorytet | Fraza / temat | Potencjał (impresje/pozycja) | Akcja (nowy art. / sekcja / rozbudowa URL) |
|---|---|---|---|

### ➡️ DLA UŻYTKOWNIKA (kanały promocji)
- Dosypać: ...
- Przetestować (z UTM, próg decyzyjny): ...
- Uciąć: ...

### ➡️ DLA BIZNESOWEGO
- ...

### ➡️ DLA ARCHITEKTA SEO
- ...

### Status wdrożenia (uzupełniany przy kolejnym przeglądzie)
- [ ] ...
-->
