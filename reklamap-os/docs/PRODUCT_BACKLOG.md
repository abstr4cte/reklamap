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

---

## ZREALIZOWANE / ODRZUCONE
_(puste)_
