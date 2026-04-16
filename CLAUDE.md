# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**ReklaMap** is a Polish advertising surface marketplace ("OLX for advertising surfaces"). Users list and search for outdoor/mobile advertising space. The app is a decoupled SPA: a Laravel 12 backend API and a Vue 3 + TypeScript frontend.

---

## Commands

### Frontend (`/frontend`)
```bash
npm run dev           # Dev server at localhost:5173 (proxies /api to localhost:8000)
npm run build         # Production build
npm run test          # Vitest in watch mode
npm run test -- --run # Run tests once (used by pre-commit hook)
npm run test:coverage # Coverage report
npm run test:ui       # Vitest UI
```

### Backend (`/backend`)
```bash
php artisan serve                  # Dev server at localhost:8000
php artisan test                   # Run all PHPUnit tests
php artisan test --testsuite=Unit  # Run only unit tests
php artisan test tests/Unit/AdvertisementTest.php  # Run a single test file
php artisan migrate                # Run pending migrations
php artisan migrate:fresh          # Wipe and re-run all migrations
php artisan make:migration <name>  # Create a new migration
```

### Combined dev (from `/backend`)
```bash
composer run dev  # Starts Laravel, queue, logs, and Vite concurrently
```

---

## Architecture

### Backend (Laravel 12, PHP 8.2+)
- **Auth**: No user accounts. Two authentication layers:
  - `VerifyAppKey` middleware — all routes require `X-App-Key` header (`INTERNAL_APP_KEY` env var)
  - `VerifyManagementToken` middleware — edit/delete routes require a time-limited token sent via email
- **API routes**: `backend/routes/api.php` — all under `/api/`, no auth:sanctum for most routes
- **Key controllers**: `AdvertisementController` (main CRUD + stats + PDF + contact), `ManagementController` (token send/validate), `StorageController` (image upload), `BlogController`, `SearchAlertController`, `NewsletterController`
- **reCAPTCHA**: `VerifyRecaptcha` middleware applied to contact/newsletter/management-link routes
- **PDF generation**: DomPDF via Blade templates in `resources/views/pdf/`
- **Testing**: PHPUnit with in-memory SQLite (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`)

### Frontend (Vue 3, TypeScript, Vite)
- **API calls**: All through `frontend/src/services/api.ts` — sets `X-App-Key` header on every request
- **State**: Three Pinia stores — `useSearchStore` (search/filter/listings), `usePreferencesStore` (favorites/comparison in localStorage), `useAuthStore`
- **Routing**: `frontend/src/router.ts`
- **Types**: All TypeScript interfaces in `frontend/src/types.ts`
- **Path alias**: `@` maps to `frontend/src/`
- **Testing**: Vitest + happy-dom, setup in `frontend/tests/setup.ts`
- **Test files**: `frontend/tests/unit/` — covers dimension conversion, price conversion, search store filtering/sorting

### CI/CD
- **Pre-commit hook**: Runs all tests (frontend + backend) before every commit
- **GitHub Actions**: Runs tests on push to `master`/`develop`

---

## Key Domain Logic

### Dimension normalization
All dimensions stored in the database as **meters**. LED screens are the exception: users input/see values in **mm**, which must be converted to meters before saving and back to mm for display. Filters must also convert mm → meters before comparison.

### Price display
One price + one unit stored per ad (`price` + `price_unit`). Frontend converts on-the-fly to display in any of the 6 units (`/dzień`, `/tydzień`, `/miesiąc`, `/rok`, `/m²`, `/kampania`). Estimated prices are marked with `~`.

### Stats tracking
Views, phone clicks, and email clicks are tracked in the `advertisement_daily_stats` table (daily granularity). There are **no** stats columns on the `advertisements` table. Always sum from `advertisement_daily_stats`.

### Traffic fields
`traffic_intensity`, `traffic_direction`, and `traffic_type` apply to **all outdoor types**: billboard, banner, wall, totem — not billboard only.

### Variants
Only types with meaningful physical configurations have variants. `banner` and `wall` have no variants.

---

## Adding a New Field to Advertisements

1. `php artisan make:migration add_<field>_to_advertisements` → update `backend/database/migrations/`
2. Update `$fillable` and `$casts` in `backend/app/Models/Advertisement.php`
3. Update validation in `backend/app/Http/Controllers/AdvertisementController.php`
4. Update `frontend/src/types.ts`
5. Update forms: `AddAdPage.vue`, `ManagementPage.vue`
6. Update display: `AdDetailPage.vue`, `ComparisonPage.vue`, `frontend/src/config/comparisonFields.ts`
7. Update PDFs: `backend/resources/views/pdf/`

---

## Blog Agent (SEO Strategy & Writing)

Projekt posiada moduł SEO w `reklamap-os/`. Proces jest dwuetapowy i opiera się na dwóch instrukcjach systemowych:

### Agenci i ich role
- **Strateg:** `reklamap-os/agents/AGENT_STRATEG_SEO.md` — Analizuje luki, planuje frazy i prowadzi użytkownika przez research (AnswerThePublic, Ahrefs, Perplexity). NIE pisze artykułów.
- **Pisarz:** `reklamap-os/agents/AGENT_PISARZ.md` — Tworzy posty SEO na podstawie danych z brudnopisu. NIE prowadzi samodzielnego researchu.

### Procedura pracy (Workflow)

1. **Inicjacja — tryb ze Strategiem:** Gdy użytkownik chce znaleźć najlepszy temat lub nie podaje gotowej frazy — wczytaj `AGENT_STRATEG_SEO.md`, `INDEX.md` oraz `STRATEGY_LOG.md` i przeprowadź użytkownika przez Etapy 0–4.
2. **Inicjacja — tryb szybki:** Gdy użytkownik podaje gotowy temat/frazę z danymi — pomiń Stratega i przejdź bezpośrednio do pisania z `AGENT_PISARZ.md`.
3. **Research krok po kroku:** Postępuj ściśle według etapów ze instrukcji Stratega. **Zadawaj użytkownikowi jedno zadanie naraz** i czekaj na dane z zewnętrznych narzędzi.
4. **Archiwizacja:** Wszystkie sprawdzone frazy dopisuj do tabeli w `reklamap-os/status/STRATEGY_LOG.md` (status: Odrzucone / Do napisania). Fakty z Perplexity lądują w `reklamap-os/status/BRUDNOPIS_SEO.md`.
5. **Pisanie:** Po zebraniu danych przełącz się na `AGENT_PISARZ.md` i wygeneruj artykuł — **wyłącznie** na podstawie danych z `BRUDNOPIS_SEO.md`.
6. **Finalizacja:**
   - Zapisz post w `reklamap-os/blog/posts/{YYYYMMDDHHMMSS}_{slug}.md`
   - Zaktualizuj tabelę w `reklamap-os/blog/INDEX.md`
   - Dodaj wpis do `$posts` w `backend/database/seeders/BlogPostsSeeder.php` (status: `draft`)
   - Poinformuj użytkownika: `php artisan db:seed --class=BlogPostsSeeder`

Seeder używa `updateOrCreate` — można go uruchamiać wielokrotnie.

---

## Conventions

- **Component naming**: PascalCase; views have `Page` suffix (`ListingsPage.vue`)
- **Store naming**: `use` prefix (`useSearchStore.ts`)
- **Vue SFC order**: `<script setup lang="ts">` → `<template>` → `<style scoped>`
- **Toast notifications**: Use the global `useToast` composable (set in `App.vue`), never create local instances
- **Mobile breakpoint**: 768px
