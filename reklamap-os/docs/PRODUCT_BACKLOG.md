# Product Backlog — ReklaMap

Backlog produktowy zarządzany przez Agenta Biznesowego. Każdy pomysł ze scoringiem RICE.
`RICE = (Reach × Impact × Confidence) / Effort`. Sortowanie: malejący RICE.

Faza projektu: **budowanie podaży** (właściciele nośników) — patrz `reklamap-os/status/ANALYTICS_LOG.md`. Priorytetem są pomysły zwiększające liczbę i jakość ogłoszeń; monetyzacja (premium) czeka na gęstą bazę.

---

## TABELA PRIORYTETÓW

| # | Pomysł | Reach | Impact | Conf. | Effort | RICE | Status |
|---|---|---|---|---|---|---|---|
| B-1 | Zdarzenia per-krok w formularzu „dodaj ogłoszenie" (`add_listing_step_view/complete` + nr kroku) | ~150/mies. | 2 | 90% | 0.5 tyg | **540** | ✅ wdrożone 2026-05-12 (kod) — czeka na deploy + oznaczenie zdarzeń w GA4 |
| B-2 | Krok „opcjonalny" — wszystkie nieobowiązkowe pola na jednym, pomijalnym kroku + wskaźnik kompletności + prompt po publikacji | ~150/mies. | 3 | 75% | 1 tyg | **450** | TODO (zależne od danych z B-1) |
| B-3 | Zapis wersji roboczej + powrót do niedokończonego ogłoszenia (mail/link); publikacja bez zdjęcia z nudge'em | ~100/mies. (porzucający) | 2 | 60% | 1.5 tyg | **80** | TODO |
| B-4 | Konta operatorów + self-serve bulk import nośników z pliku (Excel/CSV) — z walidacją per wiersz, moderacją, weryfikacją konta | duzi operatorzy/agencje | 3 | 60% | 4–6 tyg | — | ICEBOX (faza monetyzacji podaży, nie teraz) |

---

## SZCZEGÓŁY

### B-1 — Zdarzenia per-krok w formularzu „dodaj ogłoszenie" — ✅ WDROŻONE 2026-05-12
- **PROBLEMATYKA:** Lejek `add_listing_start` (119/28 dni) → `add_listing_success` (40) = ≈34% ukończenia, ale formularz ma **6 kroków** (Podstawy → Cena → Lokalizacja → Opcje → Zdjęcie → Zgody) i nie wiemy, na którym ludzie odpadają. Działamy po omacku.
- **CO ZROBIONO:** W `frontend/src/views/AddAdPage.vue` + `frontend/src/utils/analytics.ts` dodano `analytics.addAdStepView(step, type)` → `add_listing_step_view` i `analytics.addAdStepComplete(step, type)` → `add_listing_step_complete`, oba z parametrami `step_number` (1–6) i `ad_type`. `step_view` leci przy wejściu na każdy krok (mount = krok 1, watcher na `currentStep`), `step_complete` w `nextStep()` po przejściu walidacji. Typecheck + testy czyste.
- **POZOSTAŁO:** deploy frontu; w GA4 oznaczyć `add_listing_success` jako kluczowe zdarzenie (reszta — `step_view`/`step_complete` — to zdarzenia analityczne, NIE oznaczać jako kluczowe). Po 1–2 tyg. danych → analiza, na którym kroku spadek → wejście w B-2.
- **MONETYZACJA:** Pośrednio — bez tego nie wiadomo, co optymalizować w B-2; każda kolejna decyzja o lejku byłaby zgadywaniem.
- **RICE:** Reach ~150 startów/mies. · Impact 2 · Confidence 90% · Effort ~0.5 tyg → **540**.

### B-2 — Krok „opcjonalny" (odchudzony lejek, tańszy wariant)
- **PROBLEMATYKA:** Właściciele nośników przychodzą z OLX — przyzwyczajeni do wystawienia ogłoszenia w 2 minuty. Nasz 6-krokowy formularz (`frontend/src/views/AddAdPage.vue`, ~3,4 tys. linii; krok 4 „Opcje" dla billboardu = natężenie ruchu, kierunek, typ, wymiary, warianty…) to dla nich mur. 2/3 odpada, śr. czas na stronie ~6 min.
- **PROPONOWANA FUNKCJA (zrewidowana — bez budowy dwóch ścieżek):**
  - Kroki **obowiązkowe** zostają (typ, lokalizacja, cena, kontakt, zgody — bez tego ogłoszenia nie da się sensownie pokazać ani znaleźć).
  - Wszystkie **opcjonalne parametry** (wymiary, natężenie/kierunek/typ ruchu, warianty, dodatkowy opis, kolejne zdjęcia) lądują na **jednym kroku wyraźnie oznaczonym „opcjonalne"** z przyciskiem „Pomiń" obok „Uzupełnij" i ramką: *„Im dokładniej opiszesz nośnik, tym łatwiej reklamodawca go znajdzie i tym wyżej pokaże się w wynikach."*
  - **Wskaźnik kompletności ogłoszenia** („uzupełnione w 60% — dokończ, żeby było wyżej") + **prompt po publikacji** w panelu właściciela („masz X odsłon, 0 zapytań — dodaj wymiary i drugie zdjęcie") — tu wpina się przyszły Indeks Popularności.
  - **Zdjęcie:** rozważyć dopuszczenie publikacji bez zdjęcia, ale z widocznym nudge'em i niższą widocznością (łączy się z B-3). To jedyne „core" pole warte ustępstwa — 94% ogłoszeń i tak ma zdjęcie.
  - **NIE robimy** ścieżki „dodaj ogłoszenie bez niczego" — obecne pola obowiązkowe TO JEST już minimalne sensowne ogłoszenie; niżej schodzić nie ma sensu (ogłoszenie bez lokalizacji nie wejdzie na mapę, bez ceny i zdjęcia jest bezwartościowe dla reklamodawcy, „sam telefon" = magnes na spam i utrata zaufania do platformy → zabija przyszłą stronę popytową).
- **MONETYZACJA:** Gęstsza i szybciej rosnąca baza nośników jest **warunkiem koniecznym każdej monetyzacji** (premium visibility, media planer, raporty OTS, bulk booking). Niedouzupełnione ogłoszenia + wskaźnik kompletności = naturalny haczyk premium/retencyjny później.
- **ZALEŻNOŚĆ:** ruszyć **po** zebraniu danych z B-1 — jeśli okaże się, że spadek jest na kroku „Opcje", przebudowa tego kroku załatwia sprawę; jeśli na uploadzie zdjęcia albo na zgodach — robimy co innego.
- **RICE:** Reach ~150 startów/mies. · Impact 3 (rdzeń platformy w fazie podaży) · Confidence 75% (znany pattern; punkt odpadania znany po B-1) · Effort ~1 tyg (restrukturyzacja jednego kroku + wskaźnik + prompt, bez drugiej ścieżki) → **450**.

### B-3 — Zapis wersji roboczej + powrót
- **PROBLEMATYKA:** Część porzuceń to nie „nie chcę", tylko „nie teraz / brakuje mi zdjęcia / przerwało mi". Dziś wyjście = utrata wszystkiego.
- **PROPONOWANA FUNKCJA:** Auto-zapis draftu (localStorage + opcjonalnie e-mail z linkiem powrotnym, skoro i tak zbieramy kontakt). „Masz niedokończone ogłoszenie — dokończ" przy następnej wizycie. Powiązane z B-2: dopuszczenie publikacji bez zdjęcia (z nudge'em) odzyskuje tych, którzy nie mają fotki pod ręką — mogą dodać później.
- **MONETYZACJA:** Pośrednio — odzyskane ogłoszenia = większa podaż.
- **RICE:** Reach ~100/mies. (porzucający) · Impact 2 · Confidence 60% · Effort ~1.5 tyg → **80**.

### B-4 — Konta operatorów + self-serve bulk import (ICEBOX)
- **PROBLEMATYKA:** Dziś podaż wchodzi albo (a) anonimowo przez formularz jeden-po-jednym, albo (b) my ręcznie z pliku agencji (concierge, darmowy teraz dla partnerów startowych). „Potem niech sami" przy 100+ nośnikach = mur, jeśli nie ma self-serve bulk uploadu → wypchnięcie największych dostawców. Konta są warunkiem nie tylko bulk uploadu, ale i przyszłych funkcji premium dla operatorów (dashboard analityczny / „Indeks Popularności", premium visibility, auto-sync API).
- **PROPONOWANA FUNKCJA:**
  - **Konto operatora — darmowe, ale zweryfikowane.** Weryfikacja = bramka jakościowa, NIE przychodowa. Poziomy (od lekkiego): (1) potwierdzenie e-maila — najlepiej firmowego, nie gmail/wp; (2) opcjonalnie SMS na numer; (3) jednorazowy lekki ręczny przegląd pierwszej partii ogłoszeń nowego konta przed publikacją („realny operator OOH z sensownymi nośnikami → zatwierdzam") — realny filtr na obecnej skali, nie KYC. Nie tworzyć osobnego „typu konta agencyjnego" z góry — weryfikację wyzwala akcja (wgrywasz 10+ nośników → „potwierdź, że jesteś operatorem").
  - **Odznaka „zweryfikowana agencja" (przez NIP) — DARMOWA, ale NIE auto-nadawana przy wpisaniu NIP-u.** NIP jest publiczny (faktury, KRS, GUS) → „wpisz NIP → zweryfikowany" jest do podrobienia, a w najgorszym razie podszywanie się pod konkurenta („Zweryfikowana: AMS Polska" na lewym koncie). Dlatego NIP = *wejście* do weryfikacji, nie weryfikacja: wpisanie NIP → zaciągnięcie z GUS API nazwy/adresu/statusu firmy → **lekki ręczny przegląd** (firma realna i aktywna? e-mail/telefon prawdopodobnie do niej należy? ew. dowód kontroli: faktura z tym NIP / screen GUS / pismo firmowe) → dopiero wtedy odznaka. Zgłoszenie podszywania się → szybka cofka. Walidacja NIP przez GUS API — teraz overkill, dorzucić gdy urośnie.
  - **Struktura wokół weryfikacji:** odznaka = darmowa; weryfikacja **odblokowuje funkcje** (bulk import tylko dla zweryfikowanych; niezweryfikowane → formularz jeden-po-jednym lub małe partie) — bramka jakościowa, nie przychodowa; weryfikacja jest **warunkiem koniecznym premium** (nie kupisz premium visibility bez weryfikacji) — czyli weryfikacja = podłoga (darmowa), premium = na wierzchu (płatne), premium wymaga podłogi. Ewentualny płatny add-on: „przyspieszona weryfikacja" (przeskoczenie kolejki) — nie sama odznaka.
  - **Self-serve bulk import** (Excel/CSV przez panel): walidacja per wiersz (te same reguły co formularz).
  - **Moderacja — każdy import przez stan „w weryfikacji" domyślnie:** operator wgrywa → nośniki w stanie „oczekuje na przegląd", on widzi „Twoich X nośników czeka na weryfikację — zwykle do 24–48h" → moderator zatwierdza partiami → publikacja. (Niekompletne wiersze → szkic, osobno.) To produktyzacja obecnego concierge'u (ręczny import = de facto już przegląd).
  - **Trust tiering:** nowe/niezweryfikowane konto → każdy nośnik przez przegląd; zaufane konto (zweryfikowane, czysta historia) → małe importy szybciej/auto, ale **duży import zawsze przez przegląd**, niezależnie od zaufania.
  - **Limity:** miękki cap na import (np. do N nośników per upload; powyżej → rozszerzona weryfikacja / „skontaktuj się"); rate limit (ile importów w okresie); niższy cap dla świeżych kont. Chroni przed wrzuceniem hurtem śmieci i przed gamingiem listy.
  - **Powiązane (projektować razem):** wykrywanie duplikatów przy ingest (ta sama lokalizacja w promieniu X m + ten sam typ + zbliżone wymiary/tytuł → flaga, nie publikuj drugiej kopii); sortowanie wyników, które NIE nagradza ilości (limit nośników jednego operatora obok siebie / zwijanie duplikatów / sort po trafności+aktywności, nie czystej dacie); opcja „refresh ogłoszenia co 30 dni" — z nudge'em o poprawie zamiast samego bumpu, później płatny bump jako premium. Wszystko to leży na warstwie moderacji + rankingu wyszukiwarki.
  - **Obsługa niekompletnych plików:** importuj WSZYSTKIE wiersze; rozróżnij pola twardo wymagane do bycia aktywnym/publicznym (typ, lokalizacja — min. miasto, najlepiej współrzędne, cena, kontakt: telefon lub e-mail) od opcjonalnych (wymiary, natężenie/typ ruchu, warianty, opis, dodatkowe zdjęcia). Wiersz z kompletem twardo wymaganych → status aktywny (+ wskaźnik kompletności jeśli brakuje opcjonalnych). Wiersz z brakiem twardo wymaganego → status `draft`/nieaktywny, niewidoczny publicznie, z flagą „do uzupełnienia: [lista braków]". Po uzupełnieniu (przez operatora w panelu albo przez nas) → automatyczna aktywacja. Po imporcie: raport podsumowujący („wgrano X, Y na żywo, Z szkiców — brakuje: …"). Zdjęcie — graniczne; dopuścić publikację bez, z flagą niższej widoczności (jak B-3).
  - **Anonimowy formularz jeden-po-jednym ZOSTAJE** równolegle dla zwykłych użytkowników. **Płatne tiery** (premium visibility, dashboard analityczny, auto-sync API, white-glove onboarding) siedzą NA WIERZCHU — nigdy nie są bramką do podstawowego uczestnictwa. Surowe statystyki (licznik odsłon) zostają darmowe; płatna jest warstwa wniosków/trendów/rekomendacji.
- **MONETYZACJA:** Sam bulk import — darmowy (to „wystawianie, ale wydajnie", nic Cię nie kosztuje per upload). Monetyzacja: białe rękawiczki (concierge robiony przez nas — dziś darmowy dla partnerów startowych, później płatna usługa), auto-sync API z systemem operatora (usługa cykliczna), premium visibility, dashboard analityczny.
- **ZALEŻNOŚĆ / TIMING:** Faza monetyzacji podaży — czyli **dopiero gdy będzie popyt** (reklamodawcy aktywnie szukają, marketplace dowozi leady) i będziesz mieć dźwignię. Self-serve bulk upload zbudować **zanim** odetniesz darmowy concierge, nie wcześniej. Teraz: nic z tego, dalej cold-calling + ręczny import, model anonimowy.
- **RICE:** nie scoruję — to icebox, faza projektu to budowanie podaży, nie monetyzacja. Wstępnie: Impact 3 (dla strony podażowej przy skali), Confidence ~60%, Effort ~4–6 tyg.

---

## NOTKA: linia podziału FREE / PREMIUM — statystyki i analityka operatora (icebox, faza monetyzacji podaży)

Zasada nadrzędna: **nigdy nie zabieramy tego, co już jest darmowe i dostępne** (wystawianie ogłoszeń, link zarządzający przez token, podstawowe statystyki, porównanie nośników obok siebie). Premium = warstwa, której jeszcze NIE MA, dobudowana NA WIERZCHU.

**DARMOWE na zawsze (panel przez token, bez konta — to, co jest dziś):**
- statystyki per nośnik z 30 dni: odsłony, kliknięcia w numer, wysłane wiadomości — na wykresach
- porównanie kilku nośników na jednym wykresie obok siebie
- edycja zdjęć / opisów / cen przez link zarządzający

**PREMIUM (płatne, wymaga konta operatora — do zbudowania):**
- pełna historia statystyk zamiast 30 dni (np. 12 mies.)
- warstwa wniosków/rekomendacji („1000 odsłon, 0 kontaktów → problem to zdjęcia", „cena 30% powyżej podobnych nośników w okolicy") — czyli ANALIZA, nie surowe liczby
- benchmark wobec rynku („Twój billboard w Warszawie: 2× więcej odsłon niż średni billboard w Warszawie") — z danych zagregowanych, net-new
- eksport raportów (PDF/Excel) — przydatne agencjom raportującym własnym klientom
- alerty („odsłony tego ogłoszenia spadły o 40% w tym tygodniu")
- później (gdy będzie popyt): dane popytowe — „X reklamodawców szukało [typ + miasto] w tym miesiącu"

**Linia:** surowe dane + podstawowe porównanie + 30-dniowa historia = darmowe; analiza / rekomendacje / benchmark / pełna historia / eksport / alerty = premium. Premium = coś *więcej* niż obecny panel, nigdy „obecny panel za pieniądze". Premium wymaga zweryfikowanego konta (patrz B-4). Premium = freemium (bez trialu dla wszystkich) albo trial 1× per zweryfikowany NIP — bez cyklicznego trialu, który zaprasza do multi-konta. Kohorta założycielska (operatorzy z ogłoszeniami kwiecień–maj 2026) dostaje odznakę „operator założycielski" (nadawana przy przejęciu konta, okno zamknięte) + dłuższy darmowy okres premium / free-forever jeśli kohorta mała — jednorazowo, jako podziękowanie, NIE komunikować z wyprzedzeniem.

---

## ZREALIZOWANE / ODRZUCONE
_(puste)_

---

## ODRZUCONE KIERUNKI MONETYZACJI (decyzje twarde)

### ❌ Prowizja od ogłoszeń / od transakcji (decyzja 2026-05-13)
**Kontekst:** Przedstawiciel dużej agencji OOH w rozmowie z founderem powiedział, że „aż głupio, że platforma jest za darmo" — sam zaproponował oddawanie części prowizji. Sygnał walidacyjny mocny (willingness-to-pay po stronie podaży, sami z siebie), ale **propozycji nie przyjmujemy**.

**Powody:**
1. Łamie publiczną obietnicę „wystawianie darmowe na zawsze" → ryzyko dla zaufania całego rynku, nie tylko tej agencji.
2. Faza projektu = budowanie podaży; każda bariera (nawet psychologiczna) tnie wzrost bazy.
3. Prowizja od transakcji = inny biznes (Booking-like). Wymaga infrastruktury, której nie ma: regulamin transakcji, mierzenie konwersji, fakturowanie, reklamacje, escrow.
4. Niemierzalność: prowizja od czego? Lead ≠ transakcja, transakcja platformowa ≠ relacja z bazy operatora. Spór gwarantowany.
5. Precedens cenowy — pierwsza stawka staje się kotwicą, każda zmiana to wojna.

**Zamiast prowizji oferujemy 3 ścieżki współpracy** (zgodne z modelem, nie łamią obietnicy):
- **Partner Założycielski** (darmowy): odznaka, ekspozycja marketingowa, pierwszeństwo i preferencyjne warunki w premium (gdy ruszy), dedykowany kontakt. W zamian: pełne portfolio na platformie, zgoda na logo/case study, pilotaże nowych funkcji.
- **Concierge** (płatna usługa, można uruchomić już teraz): import + comiesięczne aktualizacje portfolio + miesięczny raport + opiekun. Pricing usługowy (np. 1500–3000 PLN/mies. dla portfolio do 200 nośników — do dotłuczenia). Komunikat: *płacą za nasz czas, nie za platformę.*
- **Premium visibility** (gdy uruchomimy): featured listing, opt-in, abonament — Partnerzy Założycielscy mają pierwszeństwo.

**Co z tego sygnału płynie:** plan monetyzacji premium ma realne pokrycie w willingness-to-pay — to nie tylko PowerPoint. Pierwszy twardy dowód. Zapisać w `SALES_LOG.md` jako materiał argumentacyjny do kolejnych rozmów (pokazuje dyscyplinę modelu: „odmówiliśmy prowizji, bo trzymamy się słowa").
