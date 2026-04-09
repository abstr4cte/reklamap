# 🤖 Automatyczne Testy - ReklaMap

## ✅ Co zostało skonfigurowane?

Testy uruchamiają się **automatycznie** w dwóch momentach:

### 1. 🎣 Pre-commit Hook (lokalnie)
**Kiedy:** Przy każdym `git commit`  
**Co robi:** Uruchamia wszystkie 65 testów przed commitem  
**Czas:** ~1-2 sekundy

```bash
# Próbujesz zcommitować:
git commit -m "some changes"

# Automatycznie uruchamia się:
🧪 Uruchamiam testy przed commitem...
✓ tests/unit/dimensionConversion.test.ts (12)
✓ tests/unit/priceConversion.test.ts (16)
✓ tests/unit/stores/searchStore.filtering.test.ts (21)
✓ tests/unit/stores/searchStore.sorting.test.ts (16)

Test Files  4 passed (4)
Tests       65 passed (65)

✅ Wszystkie testy przeszły! Kontynuuję commit...
```

**Jeśli test failuje:**
```bash
❌ TESTY NIE PRZESZŁY!

Nie mogę zcommitować kodu z failującymi testami.
Napraw błędy i spróbuj ponownie.
```

### 2. 🔄 GitHub Actions (na serwerze)
**Kiedy:** Przy każdym `git push` do branchy `master` lub `develop`  
**Co robi:** Uruchamia testy na serwerze GitHub  
**Gdzie zobaczyć:** GitHub → zakładka "Actions"

**Sprawdza:**
- ✅ Czy testy przechodzą na Node 20.x
- ✅ Czy testy przechodzą na Node 21.x
- ✅ Generuje raport pokrycia kodu (coverage)

---

## 🚀 Jak to działa w praktyce?

### Normalny workflow:
```bash
# 1. Edytujesz kod
vim frontend/src/stores/useSearchStore.ts

# 2. Committujesz
git add .
git commit -m "Fix price filtering bug"

# Hook automatycznie uruchamia testy:
# - Jeśli PASS ✅ → commit się dzieje
# - Jeśli FAIL ❌ → commit jest blokowany

# 3. Pushujesz
git push

# GitHub Actions uruchamia testy na serwerze
# Możesz zobaczyć wyniki na GitHubie
```

### Jeśli testy failują:
```bash
git commit -m "broken code"

🧪 Uruchamiam testy przed commitem...
❌ TESTY NIE PRZESZŁY!

# Co teraz?
# 1. Napraw kod
# 2. Spróbuj ponownie
git commit -m "fixed code"
```

---

## 🛑 Jak ominąć hook (NIE POLECAM!)

**Tylko w wyjątkowych sytuacjach:**
```bash
# Commit bez uruchamiania testów
git commit --no-verify -m "your message"

# Ale naprawdę, nie rób tego 🙏
```

**Kiedy można to zrobić:**
- ⚠️ Committujesz tylko dokumentację (README.md)
- ⚠️ Zmiana w .gitignore
- ⚠️ Poprawka CSS/styling (ale lepiej uruchom testy!)

**Kiedy NIE wolno:**
- ❌ Zmiany w logice biznesowej
- ❌ Zmiany w store/composables
- ❌ Nowa feature
- ❌ Bug fix

---

## 📊 Monitoring

### Lokalnie:
```bash
# Sprawdź czy hook działa
.git/hooks/pre-commit

# Powinno uruchomić testy
```

### Na GitHubie:
1. Idź na https://github.com/abstr4cte/reklamap
2. Kliknij zakładkę "Actions"
3. Zobacz status ostatniego workflow
   - ✅ Zielony = wszystko OK
   - ❌ Czerwony = coś failuje

---

## 🔧 Troubleshooting

### Problem: Hook nie działa
```bash
# Sprawdź czy plik istnieje
ls -la .git/hooks/pre-commit

# Sprawdź uprawnienia
chmod +x .git/hooks/pre-commit

# Test ręcznie
.git/hooks/pre-commit
```

### Problem: Testy są wolne
```bash
# Normal: ~1-2 sekundy
# Jeśli > 5 sekund, może być problem

# Uruchom ręcznie żeby zobaczyć co jest wolne
cd frontend
npm run test -- --run
```

### Problem: GitHub Actions failują
1. Zobacz logi na GitHubie (Actions → ostatni workflow)
2. Sprawdź czy lokalnie testy przechodzą:
   ```bash
   npm run test -- --run
   ```
3. Jeśli lokalnie OK ale na GitHubie fail → może być problem z Node version

---

## 📁 Lokalizacja plików

```
reklamap/
├── .git/hooks/pre-commit              # Hook lokalny (automatyczny)
├── .github/workflows/test.yml         # GitHub Actions config
└── frontend/
    ├── tests/                          # Wszystkie testy
    │   ├── setup.ts
    │   └── unit/
    │       ├── dimensionConversion.test.ts
    │       ├── priceConversion.test.ts
    │       └── stores/
    │           ├── searchStore.filtering.test.ts
    │           └── searchStore.sorting.test.ts
    └── package.json                    # Test scripts
```

---

## 🎯 Co się dzieje przy commit?

```
git commit -m "your message"
         ↓
🎣 Pre-commit hook uruchamia się
         ↓
🧪 npm run test -- --run
         ↓
    ╔═══════════════╗
    ║ Testy PASS? ║
    ╚═══════════════╝
         ↓
    YES ✅           NO ❌
         ↓                ↓
  Commit OK       Commit BLOCKED
         ↓                ↓
   git push        Napraw kod
         ↓
🔄 GitHub Actions
         ↓
    Wyniki na GitHub
```

---

## 💡 Protip

Możesz też uruchamiać testy ręcznie w watch mode:
```bash
cd frontend
npm run test

# Testy auto-reload przy każdej zmianie
# Ctrl+C żeby wyjść
```

---

## ✅ Podsumowanie

- ✅ **Pre-commit hook** - blokuje commit jeśli testy failują
- ✅ **GitHub Actions** - uruchamia testy na serwerze przy push
- ✅ **65 testów** - ~1-2 sekundy execution time
- ✅ **Automatyczne** - nie musisz pamiętać o uruchamianiu
- ✅ **Chroni przed bugami** - nie da się spushować zepsutego kodu

**Od teraz możesz zapomnieć o ręcznym uruchamianiu testów!** 🎉

---

## 🆘 Potrzebujesz pomocy?

Zobacz dokumentację:
- `/frontend/tests/README.md` - szczegółowy przewodnik
- `/TEST_COVERAGE.md` - raport pokrycia
- `/TESTING_SUMMARY.md` - podsumowanie

Lub uruchom:
```bash
npm run test:ui  # Pretty UI dla testów
```
