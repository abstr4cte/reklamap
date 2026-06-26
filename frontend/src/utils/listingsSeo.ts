/**
 * Logika SEO listy ofert (ListingsPage) wydzielona do czystych funkcji — testowalna i odporna
 * na regresję.
 *
 * Tło (czerwiec 2026): prerender build-time serwuje botowi gotowy HTML z poprawnym `index`,
 * ale Google sam HYDRATUJE stronę (uruchamia JS). Gdy noindex liczono z `resultCount` w stanie
 * początkowym (isLoading=false, dane jeszcze nie przyszły) albo po błędzie fetcha, wychodziło
 * `resultCount=0` → fałszywy `noindex` u Googlebota → deindeksacja stron miast/kategorii.
 * Stąd bramka `hasLoaded`: liczbę ofert podajemy DOPIERO po udanym fetchu.
 */

export const THIN_PAGE_THRESHOLD = 3

/**
 * Liczba ofert dla potrzeb SEO (wzbogacenie opisu + thin-page noindex).
 * `null` = liczba NIEZNANA (nie pobrano / w trakcie / fetch padł) — NIGDY nie traktuj jako thin.
 * Liczbę zwracamy tylko po udanym fetchu (`hasLoaded`), by nie pomylić „0 bo brak danych"
 * z „API realnie zwróciło 0 ofert".
 */
export function computeListingResultCount(opts: {
  isLoading: boolean
  hasLoaded: boolean
  serverTotal: number
  listingsLength: number
}): number | null {
  if (opts.isLoading || !opts.hasLoaded) return null
  return opts.serverTotal > 0 ? opts.serverTotal : opts.listingsLength
}

/**
 * Czy strona listy ma dostać `noindex`:
 *  - thin-page: strona typu/miasta/typu×miasta z liczbą ofert poniżej progu (doorway/thin content),
 *  - lub strona z aktywnymi filtrami query (duplikaty/wąskie przecięcia).
 * `resultCount=null` (liczba nieznana) → NIE thin → strona zostaje `index`.
 */
export function shouldNoindexListing(opts: {
  resultCount: number | null
  isFilteredListPage: boolean
  hasExtraFilters: boolean
  thinThreshold?: number
}): boolean {
  const threshold = opts.thinThreshold ?? THIN_PAGE_THRESHOLD
  const isThinPage = opts.isFilteredListPage
    && opts.resultCount !== null
    && opts.resultCount < threshold
  return opts.hasExtraFilters || isThinPage
}
