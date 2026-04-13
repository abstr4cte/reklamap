# ReklaMap - Podsumowanie testów

## Status: 223 testy passing (100%)

| Suite | Testy | Asercje |
|-------|-------|---------|
| Frontend (Vitest) | 127 | — |
| Backend Unit (PHPUnit) | 18 | — |
| Backend Feature (PHPUnit) | 78 | 401 |
| **Łącznie** | **223** | **401+** |

---

## Frontend (127 testów)

```bash
cd frontend && npm run test -- --run
```

| Plik | Testy | Co sprawdza |
|------|-------|-------------|
| dimensionConversion.test.ts | 12 | Konwersja mm↔m dla LED screen |
| priceConversion.test.ts | 16 | Konwersja jednostek ceny, flaga "~" |
| searchStore.filtering.test.ts | 21 | Logika filtrów (typ, wymiary, cena, miasto) |
| searchStore.sorting.test.ts | 16 | Sortowanie z konwersją jednostek |
| preferencesStore.test.ts | 14 | Ulubione, porównanie (limit 5, ten sam typ) |
| formValidation.test.ts | 48 | Walidacja 5 kroków formularza dodawania ogłoszenia |

---

## Backend (96 testów)

```bash
cd backend && php artisan test
```

| Plik | Testy | Co sprawdza |
|------|-------|-------------|
| AdvertisementTest.php | 9 | Logika modelu (powierzchnia, cena/m², wymiary) |
| AdvertisementDailyStatTest.php | 8 | Statystyki dzienne (inkrementacja, agregacja) |
| AdvertisementApiTest.php | 14 | CRUD API, walidacja pól |
| AdvertisementAuthorizationTest.php | 11 | X-App-Key, management token |
| AdvertisementStatsTest.php | 9 | Views/clicks endpoints, rate limiting |
| BlogTest.php | 10 | Blog API (lista, slug, kategoria, draft) |
| ManagementTest.php | 13 | Tokeny zarządzania, bezhasłowy auth |
| PdfGenerationTest.php | 10 | Generowanie PDF (ogłoszenie, porównanie) |
| SearchAlertTest.php | 10 | Alerty email (tworzenie, wypisanie, rate limit) |

---

## Uruchomienie

```bash
# Frontend
cd frontend && npm run test -- --run

# Backend
cd backend && php artisan test

# Oba (pre-commit hook robi to automatycznie)
```
