# Backend Tests - ReklaMap

## Status: 96 testów passing

```bash
php artisan test
```

```
Tests:    96 passed (401 assertions)
Duration: ~93s
```

---

## Struktura testów

```
backend/tests/
├── Feature/
│   ├── AdvertisementApiTest.php           # CRUD endpoints (14 testów)
│   ├── AdvertisementAuthorizationTest.php # Bezpieczeństwo / tokeny (11 testów)
│   ├── AdvertisementStatsTest.php         # Statystyki views/clicks (9 testów)
│   ├── BlogTest.php                       # Blog API (10 testów)
│   ├── ManagementTest.php                 # Tokeny zarządzania (13 testów)
│   ├── PdfGenerationTest.php              # Generowanie PDF (10 testów)
│   ├── SearchAlertTest.php                # Alerty wyszukiwania (10 testów)
│   └── ExampleTest.php
├── Unit/
│   ├── AdvertisementTest.php              # Logika modelu (9 testów)
│   ├── AdvertisementDailyStatTest.php     # Logika statystyk (8 testów)
│   └── ExampleTest.php
├── TestCase.php
└── README.md
```

---

## Co jest testowane?

### Unit Tests (18 testów)

**AdvertisementTest.php**
- Obliczanie sum statystyk dziennych
- Obliczanie powierzchni (LED, billboard)
- Obliczanie ceny za m²
- Filtrowanie po statusie
- Wymagane pola modelu
- Wymiary LED w metrach (nie mm)
- Opcjonalne pola mogą być null
- Cena numeryczna i dodatnia

**AdvertisementDailyStatTest.php**
- Tworzenie rekordu dla dzisiejszego dnia
- Inkrementacja views, phone_clicks, email_clicks
- Statystyki ostatnich 30 dni
- Agregacja sum z wielu dni
- Oddzielne statystyki per ogłoszenie
- Statystyki nie mogą być ujemne

### Feature Tests (78 testów)

**AdvertisementApiTest.php** — tworzenie, pobieranie, walidacja ogłoszeń
- Tworzenie z poprawnymi danymi
- Wymiary LED zapisywane w metrach
- Walidacja pól (cena, wymiary, typ, email)
- Pobieranie ogłoszenia po ID
- Lista tylko aktywnych ogłoszeń
- Poprawna struktura odpowiedzi

**AdvertisementAuthorizationTest.php** — middleware X-App-Key i management token
- Wymagany nagłówek X-App-Key
- Edycja/usuwanie wymaga tokenu zarządzania
- Wygaśnięty token zwraca 401
- Nieprawidłowy / nieswój token zwraca 401
- Udana edycja i usunięcie z poprawnym tokenem

**AdvertisementStatsTest.php** — stats endpoints
- Inkrementacja views tworzy rekord dzienny
- Rate limiting inkrementacji
- Inkrementacja phone_clicks i email_clicks
- GET stats zwraca podsumowanie
- Agregacja przez wiele dni
- 404 dla nieistniejącego ogłoszenia

**BlogTest.php** — API blogów
- Lista opublikowanych postów
- Posty draft nie są zwracane
- Pobieranie po slug
- 404 dla nieistniejącego / draft posta
- Filtrowanie po kategorii
- Kolejność po published_at
- Szacowanie czasu czytania
- Wymagany X-App-Key

**ManagementTest.php** — tokeny zarządzania (bezhasłowy auth)
- Wysłanie linku zarządzania na email
- Wysłanie usuwa stare tokeny
- Rate limiting wysyłania linku
- Walidacja poprawnego tokenu
- Token wygasły / nieistniejący zwraca 401
- Token zwraca statystyki 30 dni
- Token zwraca tylko własne ogłoszenia
- Token wygasa po 30 dniach

**PdfGenerationTest.php** — generowanie PDF przez DomPDF
- PDF jednego ogłoszenia
- PDF porównania wielu ogłoszeń
- 404 dla nieistniejącego ogłoszenia
- Poprawność danych w PDF
- Różne typy ogłoszeń
- Wymagany X-App-Key

**SearchAlertTest.php** — alerty email
- Tworzenie alertu z filtrami
- Tworzenie z minimalnymi danymi
- Duplikat alertu — informacja zamiast błędu
- Wiele alertów z różnymi filtrami
- Walidacja email (wymagany, format)
- Wypisanie przez token
- Rate limiting
- Filtry zapisywane jako JSON

---

## Uruchomienie testów

```bash
cd backend

# Wszystkie testy
php artisan test

# Tylko Unit
php artisan test --testsuite=Unit

# Tylko Feature
php artisan test --testsuite=Feature

# Jeden plik
php artisan test tests/Feature/AdvertisementApiTest.php

# Z detalami
php artisan test --testdox
```

---

## Konfiguracja

Testy używają SQLite in-memory (szybkie, izolowane):

```xml
<!-- phpunit.xml -->
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

Każdy test z `RefreshDatabase` zaczyna z czystą bazą.

---

## Dodawanie nowych testów

```php
// tests/Feature/MyApiTest.php
public function test_api_endpoint(): void
{
    $response = $this->postJson('/api/endpoint', [
        'data' => 'value'
    ], [
        'X-App-Key' => config('app.internal_app_key')
    ]);

    $response->assertStatus(201);
}
```
