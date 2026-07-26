# BRIEFING — audyt ReklaMap 2026-07-25 (dla agentów workflow)

Dziś: **2026-07-25**. Repo: `/var/www/html/reklamap`. Prod: `reklamap.pl` (front), `api.reklamap.pl` (backend, Hostido).
**Baza lokalna ≠ produkcja** — diagnozy rób przeciw prodowi.

## Model biznesowy (KRYTYCZNE — nie pomyl)
ReklaMap to **platforma ogłoszeniowa**, NIE agencja/broker OOH. Founder **nie** przygotowuje ofert i nie odpowiada na zapytania ofertowe. Kontakt ma iść **bezpośrednio reklamodawca → właściciel nośnika**. Zapytanie ofertowe na `kontakt@reklamap.pl` = **sygnał porażki samoobsługi**, nie lead. Nie rekomenduj „odpisz klientowi", „przygotuj cennik", „zbuduj dział obsługi".
Faza projektu: budowa **PODAŻY**. Zerowy popyt nie jest alarmem sam w sobie.

## Dostępy (używaj ich sam, nie proś użytkownika)
- Skrypt API: `/home/dev/.cache/tmp/claude-1000/-var-www-html-reklamap/c5eba6f1-6c3b-4328-8daf-c28ac65521ea/scratchpad/gapi.py`
  Funkcje: `gsc_query(site,start,end,dims,row_limit,filters)`, `gsc_inspect(site,page)`, `ga4_report(prop,start,end,dims,mets,limit,order)`, `gsc_sites()`, `ga4_accounts()`.
  GSC site = `sc-domain:reklamap.pl`; GA4 property = `526431028`.
- Bing API key: `/home/dev/.config/reklamap/bing-api-key.txt`; endpoint `https://ssl.bing.com/webmaster/api.svc/json/{Method}?apikey=..&siteUrl=https://reklamap.pl/`
- Prod API ogłoszeń: `https://api.reklamap.pl/api/listings?per_page=200&page=N`, nagłówek `X-App-Key` = `INTERNAL_APP_KEY` z `backend/.env`.
- PageSpeed Insights API: `https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=..&strategy=mobile` (bez klucza działa przy małym wolumenie).

## Dane już pobrane (NIE pobieraj ponownie)
- `reklamap-os/stats/imports/gsc-2026-07-25/api/` — `q3m__{query,page,query_page,date,device,appearance}.json`, `last28__{query,page,date}.json`, `prev28__{query,page,date}.json` (format: lista `{keys:[...], clicks, impressions, ctr, position}`)
- `reklamap-os/stats/imports/gsc-2026-07-25/*.csv` — eksport UI z porównaniem okresów
- `reklamap-os/stats/imports/ga4-2026-07-25/` — `kanaly, source_med, first_source, strony, landing, zdarzenia, zdarz_dzien` (`{dims:[],mets:[],rows:[{k:[],m:[]}]}`)
- `reklamap-os/stats/imports/bing-2026-07-25/` — `GetQueryStats, GetPageStats, GetRankAndTrafficStats, GetCrawlStats, GetLinkCounts`
- `reklamap-os/stats/stats-2026-07-25.md` — podaż (827 ogł., 349 aktywnych)

## Ustalenia z tej sesji (zweryfikowane — buduj na nich, nie powtarzaj)

### Ruch / widoczność
- GSC 3 mies. (26.04–25.07): **179 klik / 9 790 wyśw / poz. 31,4 / CTR 1,8%**. Poprzedni przegląd 12.07: 174/6 975/31,1.
- Brand `reklamap` = **110 z 179 klików**. Non-brand: **695 fraz → 6 klików / 7 954 wyśw** (w eksporcie; część zanonimizowana).
- Trend dzienny: pozycja **59,5 (05.07) → 26,4 (23.07)**, wyświetlenia 100 → 361/dzień. Recovery przekroczyło poziom sprzed deindeksu (szczyt sprzed awarii ~300/dz.).
- 28 dni vs poprzednie 28: wyśw. **898 → 3 448 (+284%)**, kliki **38 → 30 (spadek)**.
- Segmentacja stron (3 mies.): kategorie 3 758 wyśw / 11 klik (CTR 0,29%); kombinacje typ×miasto 3 284 / 14 (0,43%); blog 1 775 / 8 (0,45%); home 634 / 112 (**17,67%**); leafy 538 / 11 (2,04%); hub `/powierzchnie-reklamowe` 331 / 0.
- Największe wzrosty 28d: `/powierzchnie-reklamowe/poznan` +513 wyśw (poz **67**, 0 klik), `/blog/poradniki/billboard-reklama` +488 (poz 31←90), `/powierzchnie-reklamowe/citylighty` +371 (poz 40), blog lokalizacje: Kraków +238, Poznań +122, Gdańsk +115, Łódź +107.
- Największy spadek: `/powierzchnie-reklamowe/warszawa` −164 (213→49 wyśw, poz 42→80) — thin/noindex, 2 nośniki.

### Znaleziska techniczne (potwierdzone, do weryfikacji adwersaryjnej i wyceny)
1. **Filtr województwa zwraca 0.** `AdvertisementController.php:256` → `where('region', $request->input('region'))` exact match. Pole `region` puste w **480/827 (58%)**, formaty niespójne (`śląskie` vs `województwo śląskie`). Prod: `region=dolnoslaskie` → **0 ofert**, `region=dolnośląskie` → 0, `region=województwo dolnośląskie` → 10, podczas gdy realnie (wg lat/lng) w dolnośląskim jest **407 nośników**. Front (`HeroBanner.vue`) podpowiada województwa z `polishLocations.json`, gdzie id = ASCII (`dolnoslaskie`). To ta sama klasa błędu co naprawiony 07.07 `city_strict`.
2. **`contact_email_click` to martwy kod.** Zdefiniowane w `frontend/src/utils/analytics.ts:30` jako `analytics.clickEmail`, **nigdzie nie wywołane**. Powód: na stronie ogłoszenia **nie ma linku `mailto:` do wystawcy** — jest telefon (`AdDetailPage.vue:81,91`) i formularz (`AdContactForm.vue:65`). GA4 potwierdza: `contact_phone_click` 39/7 userów, `contact_form_submit` 4/3, `contact_email_click` **nie istnieje**.
3. **Guard `?_v=` przecieka do botów.** Bing crawl 91 dni: `BlockedByRobotsTxt` = **5 255** (przy `Code2xx` 14 656). W top stronach Binga jest `/blog/prawo-i-regulacje/oplata-reklamowa?_v=1781487146877`. GSC: „Strona zindeksowana, ale zablokowana przez robots.txt" = 8 URL, trend rosnący, start 18.06 (2 dni po wdrożeniu prerenderu build-time 16.06). Bramka UA w `frontend/index.html:36` najwyraźniej nie chroni.
   Zablokowane URL-e to strony kluczowe: `/powierzchnie-reklamowe/poznan`, `/powierzchnie-reklamowe/citylighty`, `/blog/poradniki/citylight-reklama`, `/blog/poradniki/ile-kosztuje-reklama-outdoor`, `/powierzchnie-reklamowe/billboardy/dabrowa-gornicza` + 3 leafy.
4. **5xx dotyczą wyłącznie Googlebota.** GSC: 24 strony „Błąd serwera (5xx)". Bing za 91 dni: `Code5xx = 0` na 14 656 pobrań. Podejrzenie: WAF na `api.reklamap.pl` odrzuca IP renderujące Google (zapisana wcześniej diagnoza `cors_render_ip_block`).
5. **`www.reklamap.pl` NIE jest skonsolidowane** mimo 301 z 07.05. `totemy reklamowe wrocław`: www 73 wyśw / poz 10,9 vs non-www 26 wyśw / poz 11,4 (www **wygrywa**). Podobnie `ekrany-led/poznan`: www 69 vs non-www 13.
6. **Kanibalizacja fraz miejskich.** `bilbordy gdańsk` — 138 wyśw na **9 stronach** (billboardy/gdansk, sciany-reklamowe/gdansk, totemy/gdansk, reklama-mobilna/gdansk…), najlepsza poz 31,7. `billboardy łódź` — 170 wyśw na 7 stronach. `powierzchnie reklamowe` — 110 wyśw na 9 stronach.
7. **Rozjazd podaż↔popyt.** Google pokazuje nas na miasta bez podaży: `billboardy łódź` 150 wyśw / **0 nośników w Łodzi**; `powierzchnie reklamowe białystok` 117 / 0; `reklama led poznań` 121 / 18 nośników w Poznaniu ale **0 LED**; `powierzchnie reklamowe lublin` 120 / 1 nośnik. Nasza podaż: Kłodzko 138, Koszalin 70, Dąbrowa Górnicza 60 — miasta o znikomym search volume.
8. **56% bazy ma status `reserved`** (463/827). W dolnośląskim 356/396 billboardów, Biała Podlaska 30/32. Nie wiadomo, czy to realne rezerwacje, czy artefakt importu Big Group — **pytanie otwarte do founder'a, nie zakładaj odpowiedzi**.
9. **Duble miast dzielą podaż:** `Polanica Zdrój` vs `Polanica-Zdrój`, `Wilków Wielki` vs `Wilków wielki`, `Powodów Trzeci` vs `Powoów Trzeci` (literówka), `Duszniki` vs `Duszniki-Zdrój`, `Ząbkowice` vs `Ząbkowice Śląskie`.
10. **Bing** = 145 wyśw / 3 kliki / 48 fraz w 3 mies. Cały ruch niesie blog lokalizacyjny: `reklama outdoor baner billboard krakow` 53 wyśw poz **1,2**; wrocław 26 wyśw poz 3,5. Brak IndexNow (`indexnow.txt` → SPA-fallback), brak `BingSiteAuth.xml`. Bing sam rekomenduje: IndexNow, za mało linków przychodzących, za dużo thin content.
11. **GA4 kanały (3 mies.):** Direct 467 sesji / 235 userów / 30 kluczowych zdarzeń; Organic Search 231/80/**5**; Email (`outreach/email`) 45/17/3 przy **najwyższym engagement 70%**; Referral 15/3/0; Organic Social 7/7/0. `bing/organic` = 2 sesje.
12. **GA4 lejek podaży:** `add_listing_start` 56 userów → `add_listing_success` 31 userów (**55%**). `view_item` 255 zdarzeń / 72 userów → `contact_phone_click` 7 userów (**~10%**).
13. Drobiazg: w GA4 landing pages jest `/reklamap.pl:443` z 5 sesjami — zepsuty URL, źródło nieznane.

## Kontekst projektu (przeczytaj, jeśli dotyczy Twojego wymiaru)
- `/var/www/html/reklamap/CLAUDE.md` — niezmienniki SEO/deploy (prerender, seed `__INITIAL_STATE__`, `THIN_PAGE_THRESHOLD=3` w 3 miejscach, sitemap, tripwire)
- `reklamap-os/status/ANALYTICS_LOG.md` — poprzednie przeglądy (12.07, 13.07, 29.05, 25.05, 12.05)
- `reklamap-os/status/SEO_TECH_AUDIT.md` — audyty Architekta, w tym otwarte pozycje z 07.07
- `reklamap-os/agents/AGENT_*.md` — persony (Analityk, Architekt SEO, Strateg, Biznesowy)
- `reklamap-os/blog/INDEX.md` — stan bloga

## Zasady
- **Liczby, nie wrażenia.** Każdy wniosek z konkretną liczbą i źródłem (plik/endpoint/`plik:linia`).
- Nie zmyślaj brakujących danych — napisz „brak danych".
- Nie proponuj zmian sprzecznych z niezmiennikami w CLAUDE.md (seed, próg thin, kierunek 301, `reserved` w sitemapie).
- Nie commituj, nie deployuj, nie seeduj produkcji, nie wysyłaj maili.
