# Deploy frontu (ReklaMap)

## Jak wdrożyć

```bash
cd frontend && ./deploy.sh
```

Skrypt robi:
1. `npm run build:seo` — `vite build` + prerender (puppeteer) wszystkich tras z sitemapy → statyczny HTML w `dist/`.
2. `rsync dist/` → **realny docroot na Hostido**.
3. weryfikację, że bot dostaje treść (unikalny `<title>`).

## Ważne fakty

- **Realny docroot reklamap.pl:** `/home/host831115/domains/reklamap.pl/reklamap/frontend/dist`
  (NIE `public_html` — to nieużywany symlink; serwer czyta z `dist` gitowego checkoutu).
- **Build+prerender MUSZĄ lecieć lokalnie**, nie w GitHub Actions: prerender robi ~435 żądań
  do `api.reklamap.pl`; z IP runnerów GitHuba Hostido blokuje to jako nadużycie (sitemap/API
  zwraca 0 → pusty build). Workflow `.github/workflows/deploy-frontend.yml` jest dlatego
  `workflow_dispatch` only (ręczny, na wypadek przyszłego self-hosted runnera / allowlisty IP).
- SSH deploy-key: `~/.ssh/reklamap_deploy` (bez passphrase), port `64321`.
- Strony typ/miasto bez ogłoszeń (np. transport/Kraków) nie są w sitemapie → SPA-fallback
  (treść strony głównej). Wracają, gdy pojawi się tam nośnik.

## Po wdrożeniu — SEO (Google Search Console)

Prerender daje botom treść, ale Google trzeba „szturchnąć", żeby przeczytał na nowo:

1. **„Sprawdź URL"** dla nowych/zmienionych stron → **„Poproś o zaindeksowanie"**.
   - Zgłoszone 2026-06-16: strona główna + `/powierzchnie-reklamowe/billboardy/koszalin`.
   - Przy kolejnych: zgłaszaj najważniejsze strony miast/typów, które dostały treść.
2. Po większym imporcie nośników **ponów zgłoszenie `sitemap.xml`** w GSC (Mapy witryny).
3. Obserwuj 1–2 tyg.: powrót fraz miast/typów i zniknięcie „W przypadku tej strony
   informacje nie są dostępne".
