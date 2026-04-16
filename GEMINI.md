# ReklaMap - Project Context for Gemini

ReklaMap is a Polish advertising surface marketplace ("OLX for advertising surfaces") where users can list and search for outdoor and mobile advertising spaces.

## Project Overview
- **Type:** Decoupled SPA (Single Page Application)
- **Backend:** Laravel 12 (PHP 8.2+)
- **Frontend:** Vue 3 (TypeScript, Vite, Tailwind CSS)
- **Key Feature:** "ReklaMap OS" — An AI-driven SEO and content management system located in `reklamap-os/`.

---

## Core Directories
- `/backend`: Laravel API, database migrations, and domain logic.
- `/frontend`: Vue 3 application, stores, and components.
- `/reklamap-os`: SEO strategy, AI agent instructions, and blog content management.

---

## Technical Stack & Architecture

### Backend (`/backend`)
- **Authentication:** Token-based management (no permanent user accounts).
  - `VerifyAppKey`: Global API security using `X-App-Key`.
  - `VerifyManagementToken`: Time-limited tokens for editing/deleting ads.
- **Logic:**
  - Dimensions are normalized to **meters** in the database (LED screens use mm in UI, converted before save).
  - Stats (views, clicks) are stored in `advertisement_daily_stats` (always sum totals).
  - PDF generation via `barryvdh/laravel-dompdf`.
- **Database:** SQLite for local dev/testing; migrations in `database/migrations/`.

### Frontend (`/frontend`)
- **State Management:** Pinia stores (`useSearchStore`, `usePreferencesStore`, `useAuthStore`).
- **Maps:** Leaflet for displaying advertising locations.
- **Dimensions:** Handles on-the-fly conversion between meters and mm (for LEDs).
- **Styles:** Tailwind CSS with custom configuration.

---

## Commands

### Global Dev (from `/backend`)
- `composer run dev`: Starts Laravel, Vite, queue, and logs concurrently.

### Backend (`/backend`)
- `php artisan serve`: Start PHP dev server (localhost:8000).
- `php artisan test`: Run all PHPUnit tests.
- `php artisan migrate`: Run database migrations.
- `php artisan db:seed --class=BlogPostsSeeder`: Sync blog posts from `reklamap-os/`.

### Frontend (`/frontend`)
- `npm run dev`: Start Vite dev server (localhost:5173).
- `npm run build`: Production build.
- `npm run test`: Run Vitest tests.

---

## ReklaMap OS (AI Workflow)

The project uses a multi-agent AI system for content creation. When asked to perform SEO or blog tasks, refer to `reklamap-os/ROUTING.md` and the following roles:

1.  **Strateg (`AGENT_STRATEG_SEO.md`):** Plans keywords and SEO gaps.
2.  **Pisarz (`AGENT_PISARZ.md`):** Writes the article draft based on facts.
3.  **Korektor (`AGENT_KOREKTOR.md`):** Audits SEO, removes "AI-slang," and verifies facts.
4.  **Grafik (`AGENT_GRAFIK.md`):** Generates prompts for visual assets.

**Workflow:** Always start with the Router or the specific agent instruction file before acting on blog content.

---

## Development Conventions

- **Naming:** PascalCase for Vue components (`AdDetailPage.vue`), `use` prefix for stores.
- **API:** Use `frontend/src/services/api.ts` for all frontend requests.
- **Dimensions:** Store everything in meters.
- **Testing:** New features MUST include tests (PHPUnit for backend, Vitest for frontend).
- **Validation:** Follow the "Adding a New Field" checklist in `CLAUDE.md`.

---

## Key Files for Reference
- `CLAUDE.md`: Comprehensive guide for coding standards and domain logic.
- `backend/routes/api.php`: API endpoint definitions.
- `frontend/src/types.ts`: Shared TypeScript interfaces.
- `reklamap-os/ROUTING.md`: Entry point for AI content agents.
