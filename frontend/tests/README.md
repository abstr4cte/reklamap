# ReklaMap - Test Suite

## Uruchomienie testów

```bash
# Uruchom wszystkie testy
npm run test

# Uruchom testy w trybie watch (auto-reload)
npm run test

# Uruchom testy z pokryciem kodu
npm run test:coverage

# Uruchom testy z UI
npm run test:ui
```

## Struktura testów

```
frontend/tests/
├── setup.ts                                    # Konfiguracja środowiska testowego
├── unit/                                       # Testy jednostkowe
│   ├── dimensionConversion.test.ts            # Konwersje wymiarów LED (mm ↔️ m)
│   ├── priceConversion.test.ts                # Konwersje jednostek ceny
│   ├── formValidation.test.ts                 # Walidacja formularza dodawania ogłoszenia
│   └── stores/                                 # Testy logiki store
│       ├── searchStore.filtering.test.ts      # Filtrowanie ogłoszeń
│       ├── searchStore.sorting.test.ts        # Sortowanie ogłoszeń
│       └── preferencesStore.test.ts           # Ulubione i porównanie
└── README.md                                   # Ten plik
```

## Co jest testowane?

### ✅ Konwersje wymiarów LED Screen (mm ↔️ m)

**Dlaczego?** Historycznie był to największy problem - wymiary były konwertowane w wielu miejscach, powodując:
- Błędne wyniki filtrów
- Złe wartości po przeładowaniu strony  
- Niespójne dane w query params

**Testy sprawdzają:**
- UI Input → DB Storage (2000mm → 2m)
- DB → Display (2m → 2000mm dla LED, 6m dla billboardów)
- Filter → Comparison (konwersja przed porównaniem)
- Surface calculation (zawsze w m²)
- Price per m² calculation

### ✅ Konwersje jednostek ceny

**Dlaczego?** Użytkownicy widzieli szacunkowe ceny jako dokładne (brak "~").

**Testy sprawdzają:**
- Konwersje podstawowe (day→month, month→week, year→month)
- Detekcja szacunkowych cen (estimated flag)
- Filtrowanie z konwersją cen
- Format wyświetlania (z/bez "~")
- Edge cases (0 PLN, bardzo małe/duże kwoty)
- Real-world scenarios (Banner 50 PLN/day = 1500 PLN/month)

### ✅ Logika filtrowania (searchStore)

**Dlaczego?** Filtry są kluczowe dla UX - 80% użytkowników używa minimum 2 filtrów.

**Testy sprawdzają:**
- Type filtering (billboard, LED, banner, etc.)
- Dimension filtering z konwersją LED (mm→m)
- Surface area calculation and filtering
- Price filtering z konwersją jednostek
- Traffic filtering (intensity, direction, type)
- Status filtering (active, rented, soon_available)
- Boolean flags (backlight, images, print, etc.)
- City/location filtering (substring, case-insensitive)
- Combined filters (real-world scenarios)

**Przykład:** LED w Warszawie, szerokość 2000-3000mm, cena 4000-6000 PLN/miesiąc

### ✅ Logika sortowania (searchStore)

**Dlaczego?** Sortowanie musi uwzględniać różne jednostki ceny.

**Testy sprawdzają:**
- Price sorting (ascending/descending) z konwersją jednostek
- Sortowanie po różnych jednostkach (day, week, month, year)
- Date sorting (newest/oldest first)
- Surface area sorting
- Alphabetical sorting (A-Z, Z-A)
- Edge cases (empty array, single item, identical values)

**Przykład:** 100 PLN/dzień = 3000 PLN/miesiąc > 2000 PLN/miesiąc

### ✅ Ulubione i porównanie (preferencesStore)

**Dlaczego?** Użytkownicy muszą móc zapisywać i porównywać ogłoszenia bez błędów.

**Testy sprawdzają:**
- Dodawanie/usuwanie z ulubionych
- Dodawanie/usuwanie z porównania
- **Limit 5 ogłoszeń** w porównaniu
- **Walidacja tego samego typu** w porównaniu (nie można porównać billboardu z LED)
- Weryfikacja aktywności ogłoszenia (nie można dodać nieaktywnego)
- Czyszczenie porównania
- Synchronizacja z localStorage
- Obsługa błędów API

### ✅ Walidacja formularza dodawania ogłoszenia

**Dlaczego?** Walidacja musi być spójna i zapobiegać błędnym danym.

**Testy sprawdzają:**
- **Step 1:** Email (wymagany, format), tytuł (wymagany, max 200 znaków), opis (wymagany, max 5000 znaków), rodzaj powierzchni
- **Step 2:** Cena (wymagana, max 999,999 zł), czas trwania kampanii (dla `priceUnit === 'campaign'`)
- **Step 3:** Wymiary (wymagane dla outdoor, max wartości różne dla LED/billboard), lokalizacja, telefon (dokładnie 9 cyfr)
- **Step 4:** Klasa drogi (billboard), natężenie ruchu (outdoor), wariant (dla typów które go wymagają), status dostępności
- **Step 5:** Akceptacja regulaminu

## Pokrycie kodu

Obecne pokrycie skupia się na **krytycznej logice biznesowej**:
- ✅ Konwersje wymiarów (12 testów)
- ✅ Konwersje cen (16 testów)
- ✅ Logika filtrowania (21 testów)
- ✅ Logika sortowania (16 testów)
- ✅ Ulubione i porównanie (14 testów)
- ✅ Walidacja formularza (48 testów)
- ⏳ Komponenty (TODO)
- ⏳ E2E flows (TODO)

**Total: 127 testów jednostkowych (100% passing)**

## Dodawanie nowych testów

### Test jednostkowy (Unit Test)

```typescript
// tests/unit/myFeature.test.ts
import { describe, it, expect } from 'vitest'

describe('My Feature', () => {
  it('does what I expect', () => {
    const result = myFunction(input)
    expect(result).toBe(expectedOutput)
  })
})
```

### Test komponentu (Component Test)

```typescript
// tests/component/MyComponent.test.ts
import { mount } from '@vue/test-utils'
import MyComponent from '@/components/MyComponent.vue'

describe('MyComponent', () => {
  it('renders correctly', () => {
    const wrapper = mount(MyComponent, {
      props: { title: 'Test' }
    })
    expect(wrapper.text()).toContain('Test')
  })
})
```

## Najlepsze praktyki

### ✅ DO:
- Testuj logikę biznesową (konwersje, kalkulacje)
- Dodaj komentarze wyjaśniające "dlaczego" ten test istnieje
- Testuj edge cases (0, null, undefined, bardzo duże wartości)
- Używaj opisowych nazw testów
- Grupuj powiązane testy w `describe` blocks

### ❌ DON'T:
- Nie testuj implementacji frameworka (Vue, Pinia)
- Nie duplikuj testów
- Nie pisz testów "dla pokrycia" - pisz dla wartości biznesowej
- Nie hardcoduj wartości - używaj zmiennych z wyraźnymi nazwami

## Bug Prevention

Te testy zapobiegają powrotowi następujących bugów:

1. **LED Screen Dimensions Bug** (2026-02-XX)
   - Problem: Wymiary konwertowane mm→m w wielu miejscach
   - Rozwiązanie: Konwersja tylko na granicach (UI/DB)
   - Test: `dimensionConversion.test.ts`

2. **Price Estimation Bug** (2026-02-XX)
   - Problem: Brak "~" przy szacunkowych cenach
   - Rozwiązanie: Tracking price_unit i flag isEstimated
   - Test: `priceConversion.test.ts`

3. **Price Filtering Bug** (2026-02-XX)
   - Problem: Filtrowanie po cenie bez konwersji jednostek
   - Rozwiązanie: Konwersja przed porównaniem
   - Test: `priceConversion.test.ts` → Price Filtering

## Roadmap

### Faza 1: ✅ Critical Logic (DONE)
- [x] Dimension conversions (12 testów)
- [x] Price conversions (16 testów)
- [x] Test environment setup

### Faza 2: ✅ Store Logic (DONE)
- [x] searchStore filtering logic (21 testów)
- [x] searchStore sorting logic (16 testów)
- [x] preferencesStore - favorites, comparison (14 testów)

### Faza 3: ✅ Form Validation (DONE)
- [x] Add advertisement form validation (48 testów)
- [x] All 5 steps validated (email, price, dimensions, features, terms)

### Faza 4: ⏳ Components (TODO)
- [ ] HeroBanner filters
- [ ] AdCard price display
- [ ] ListingsPage modal

### Faza 5: ⏳ E2E (TODO)
- [ ] Search flow (select type, filter, view results)
- [ ] Add listing flow
- [ ] Comparison flow

## CI/CD Integration

```yaml
# .github/workflows/test.yml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - run: npm install
      - run: npm run test
```

## Troubleshooting

### Problem: "Cannot find module '@/...'"
**Rozwiązanie:** Sprawdź `vite.config.ts` - alias `@` musi wskazywać na `./src`

### Problem: "ReferenceError: window is not defined"
**Rozwiązanie:** Dodaj mock w `tests/setup.ts`

### Problem: "TypeError: Cannot read property '...' of undefined"
**Rozwiązanie:** Sprawdź czy mockujesz wszystkie zależności komponentu

## Resources

- [Vitest Documentation](https://vitest.dev/)
- [Vue Test Utils](https://test-utils.vuejs.org/)
- [Testing Best Practices](https://github.com/goldbergyoni/javascript-testing-best-practices)
