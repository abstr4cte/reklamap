# ReklaMap - Pokrycie testów

## Łącznie: 223 testy passing

### Frontend (127 testów)

**Logika biznesowa — w pełni pokryta:**
- Konwersja wymiarów LED (mm↔m) — 12 testów
- Konwersja i wyświetlanie cen (z flagą "~") — 16 testów
- Filtrowanie ogłoszeń (wszystkie typy filtrów) — 21 testów
- Sortowanie z konwersją jednostek — 16 testów
- Ulubione i porównanie (limity, walidacja typu) — 14 testów
- Walidacja formularza dodawania ogłoszenia (5 kroków) — 48 testów

**Nie pokryte (niekriytyczne):**
- Komponenty Vue (rendering)
- Testy E2E (przepływ użytkownika)

### Backend (96 testów, 401 asercji)

**Pokryte:**
- Modele (Advertisement, AdvertisementDailyStat) — logika, obliczenia
- Wszystkie API endpoints — CRUD, walidacja, odpowiedzi
- Autoryzacja — X-App-Key, management token, wygaśnięcie
- Statystyki — inkrementacja, rate limiting, agregacja
- Blog — lista, filtrowanie, draft protection
- Management link — wysyłanie, walidacja, expiry
- PDF — generowanie dla różnych typów ogłoszeń
- Search alerts — tworzenie, wypisanie, rate limiting

**Nie pokryte:**
- Email delivery (mockowane przez Laravel)
- Integracja z zewnętrznym storage (S3/local)
