# Log Analityczny — ReklaMap

Historia przeglądów danych (GSC / GA4 / stats.php) i rekomendacji Agenta Analityka.
Najnowszy wpis na górze. Nie nadpisuj — dopisuj.

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

### Status wdrożenia (do uzupełnienia przy kolejnym przeglądzie)
- [ ] GA4: filtr ruchu wewnętrznego (IP)
- [ ] GA4: zdarzenia `click_phone` / `click_email` / `contact_form_submit` + oznaczenie kluczowych zdarzeń
- [ ] Architekt: 301 www→non-www (lub odwrotnie)
- [ ] Architekt: poprawa title/description dla `reklama tranzytowa krakow/poznan`, `citylight olsztyn`
- [ ] Strateg: brief powyżej → research → Pisarz
- [ ] UTM-y na linkach zewnętrznych

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
