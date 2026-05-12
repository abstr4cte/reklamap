# Product Backlog — ReklaMap

Backlog produktowy zarządzany przez Agenta Biznesowego. Każdy pomysł ze scoringiem RICE.
`RICE = (Reach × Impact × Confidence) / Effort`. Sortowanie: malejący RICE.

Faza projektu: **budowanie podaży** (właściciele nośników) — patrz `reklamap-os/status/ANALYTICS_LOG.md`. Priorytetem są pomysły zwiększające liczbę i jakość ogłoszeń; monetyzacja (premium) czeka na gęstą bazę.

---

## TABELA PRIORYTETÓW

| # | Pomysł | Reach | Impact | Conf. | Effort | RICE | Status |
|---|---|---|---|---|---|---|---|
| B-1 | Zdarzenia per-krok w formularzu „dodaj ogłoszenie" (`add_listing_step_view/complete` + nr kroku) | ~150/mies. | 2 | 90% | 0.5 tyg | **540** | TODO |
| B-2 | „Szybkie ogłoszenie" — odchudzony lejek (typ + miasto + zdjęcie + cena + telefon), reszta po publikacji | ~150/mies. | 3 | 70% | 2.5 tyg | **126** | TODO |
| B-3 | Zapis wersji roboczej + powrót do niedokończonego ogłoszenia (mail/link) | ~100/mies. (porzucający) | 2 | 60% | 1.5 tyg | **80** | TODO |

---

## SZCZEGÓŁY

### B-1 — Zdarzenia per-krok w formularzu „dodaj ogłoszenie"
- **PROBLEMATYKA:** Lejek `add_listing_start` (119/28 dni) → `add_listing_success` (40) = ≈34% ukończenia, ale formularz ma **6 kroków** (Podstawy → Cena → Lokalizacja → Opcje → Zdjęcie → Zgody) i nie wiemy, na którym ludzie odpadają. Działamy po omacku.
- **PROPONOWANA FUNKCJA:** GA4 events `add_listing_step_view` i `add_listing_step_complete` z parametrem `step_number` (1–6) i `ad_type`. Opcjonalnie `add_listing_field_blur` na najcięższych polach kroku 4. Oznaczyć `add_listing_success` jako kluczowe zdarzenie.
- **MONETYZACJA:** Pośrednio — bez tego nie wiadomo, co optymalizować w B-2; każda kolejna decyzja produktowa o lejku jest zgadywaniem. Tani, odblokowuje resztę.
- **RICE:** Reach ~150 startów/mies. (rośnie z cold-callingiem) · Impact 2 (sam pomiar nie konwertuje, ale warunkuje B-2/B-3) · Confidence 90% · Effort ~0.5 tyg → **540**. Robić pierwsze.

### B-2 — „Szybkie ogłoszenie" (odchudzony lejek)
- **PROBLEMATYKA:** Właściciele nośników przychodzą z OLX — przyzwyczajeni do wystawienia ogłoszenia w 2 minuty. Nasz 6-krokowy formularz (`frontend/src/views/AddAdPage.vue`, ~3,4 tys. linii; krok 4 „Opcje" dla billboardu = natężenie ruchu, kierunek, typ, wymiary, warianty…) to dla nich mur. 2/3 odpada, śr. czas na stronie ~6 min.
- **PROPONOWANA FUNKCJA:** Ścieżka „Szybkie ogłoszenie" z minimalnym zestawem pól: **typ nośnika + miasto/lokalizacja + 1 zdjęcie + cena + telefon kontaktowy**. Reszta (wymiary, natężenie ruchu, warianty, opis, dodatkowe zdjęcia) przeniesiona do edycji po publikacji, z wyraźnym promptem „uzupełnij, żeby ogłoszenie było lepiej widoczne" (tu wpina się przyszły Indeks Popularności). Zachować obecny „pełny" formularz jako opcję dla zaawansowanych. Kolejność kroków: zacząć od tego, co właściciel ma w głowie (co to jest, gdzie, ile), zdjęcie + zgody na końcu.
- **MONETYZACJA:** Gęstsza i szybciej rosnąca baza nośników jest **warunkiem koniecznym każdej monetyzacji** (premium visibility, media planer, raporty OTS, bulk booking — wszystko bezwartościowe bez podaży). Dodatkowo: niedouzupełnione ogłoszenia tworzą naturalny haczyk premium/retencyjny później.
- **RICE:** Reach ~150 startów/mies. · Impact 3 (rdzeń platformy w fazie podaży — wprost liczba ogłoszeń) · Confidence 70% (znany pattern, ale dokładny punkt odpadania znamy dopiero po B-1) · Effort ~2.5 tyg (refactor dużego formularza + nowa ścieżka) → **126**.

### B-3 — Zapis wersji roboczej + powrót
- **PROBLEMATYKA:** Część porzuceń to nie „nie chcę", tylko „nie teraz / brakuje mi zdjęcia / przerwało mi". Dziś wyjście = utrata wszystkiego.
- **PROPONOWANA FUNKCJA:** Auto-zapis draftu (localStorage + opcjonalnie e-mail z linkiem powrotnym, skoro i tak zbieramy kontakt). „Masz niedokończone ogłoszenie — dokończ" przy następnej wizycie.
- **MONETYZACJA:** Pośrednio — odzyskane ogłoszenia = większa podaż.
- **RICE:** Reach ~100/mies. (porzucający) · Impact 2 · Confidence 60% · Effort ~1.5 tyg → **80**.

---

## ZREALIZOWANE / ODRZUCONE
_(puste)_
