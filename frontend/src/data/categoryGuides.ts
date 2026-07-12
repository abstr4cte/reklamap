/**
 * Powiązane artykuły blogowe per typ nośnika — do sekcji „Przewodniki” na stronach kategorii
 * (ListingsPage). Zamyka silos kategoria→blog (audyt widoczności 2026-07-12: strony kategorii
 * NIE linkowały do bloga). Kierunek link-equity: z silnych, rankujących stron kategorii DO bloga,
 * który jest naszym rankującym assetem informacyjnym (rankuje bez podaży). Wzmacnia m.in.
 * `billboard-reklama` (poz 18, 396 wyśw) linkiem z `/powierzchnie-reklamowe/billboardy`.
 *
 * Slugi zweryfikowane wobec reklamap-os/blog/INDEX.md. URL artykułu: /blog/{category}/{slug}.
 */
export interface GuideLink {
  slug: string
  category: string
  label: string
}

// Klucz = slug typu (jak w URL); '' = strona ogólna (miasto bez typu / wszystkie powierzchnie).
export const categoryGuides: Record<string, GuideLink[]> = {
  '': [
    { slug: 'jak-wybrac-powierzchnie-reklamowa', category: 'poradniki', label: 'Jak wybrać powierzchnię reklamową' },
    { slug: 'ile-kosztuje-reklama-outdoor', category: 'poradniki', label: 'Ile kosztuje reklama outdoor' },
    { slug: 'reklama-zewnetrzna', category: 'poradniki', label: 'Reklama zewnętrzna — przewodnik' },
  ],
  billboardy: [
    { slug: 'billboard-reklama', category: 'poradniki', label: 'Billboard reklamowy — kompletny przewodnik' },
    { slug: 'ile-kosztuje-reklama-outdoor', category: 'poradniki', label: 'Ile kosztuje reklama outdoor' },
    { slug: 'tablica-reklamowa', category: 'poradniki', label: 'Tablica reklamowa — co warto wiedzieć' },
  ],
  citylighty: [
    { slug: 'citylight-reklama', category: 'poradniki', label: 'Citylight reklamowy — poradnik' },
    { slug: 'ile-kosztuje-reklama-outdoor', category: 'poradniki', label: 'Ile kosztuje reklama outdoor' },
  ],
  'ekrany-led': [
    { slug: 'ekran-led-cena', category: 'poradniki', label: 'Ekran LED — cena i wynajem' },
    { slug: 'telebim-ekran-led-reklama', category: 'trendy', label: 'Telebim i reklama na ekranach LED' },
    { slug: 'dooh-reklama-programatyczna', category: 'trendy', label: 'DOOH — reklama programatyczna' },
  ],
  banery: [
    { slug: 'baner-reklamowy-cena', category: 'poradniki', label: 'Baner reklamowy — cena i montaż' },
    { slug: 'reklama-na-ogrodzeniu', category: 'poradniki', label: 'Reklama na ogrodzeniu' },
  ],
  'sciany-reklamowe': [
    { slug: 'murale-reklamowe', category: 'trendy', label: 'Murale reklamowe' },
    { slug: 'reklama-na-elewacji-wspolnoty', category: 'poradniki', label: 'Reklama na elewacji wspólnoty' },
  ],
  'totemy-reklamowe': [
    { slug: 'totem-reklamowy', category: 'trendy', label: 'Totem reklamowy — pylon przy galerii' },
  ],
  'reklama-mobilna': [
    { slug: 'reklama-na-samochodzie', category: 'poradniki', label: 'Reklama na samochodzie i przyczepie' },
  ],
  'reklama-w-transporcie': [
    { slug: 'reklama-w-transporcie-publicznym', category: 'poradniki', label: 'Reklama w transporcie publicznym' },
  ],
}

/** Zwraca przewodniki dla danego slugu typu; fallback do ogólnych ('') gdy typ nieznany/brak. */
export function getCategoryGuides(typeSlug?: string): GuideLink[] {
  if (typeSlug && categoryGuides[typeSlug]) return categoryGuides[typeSlug]
  return categoryGuides['']
}
