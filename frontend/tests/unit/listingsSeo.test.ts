import { describe, it, expect } from 'vitest'
import { computeListingResultCount, shouldNoindexListing, THIN_PAGE_THRESHOLD } from '@/utils/listingsSeo'

/**
 * Regresja (GSC 2026-06-26): strony miast/kategorii (np. /powierzchnie-reklamowe/warszawa)
 * dostawały fałszywy `noindex` u Googlebota. Prerender build-time serwował poprawny `index`,
 * ale Google hydratuje stronę JS-em — a noindex liczono z resultCount w stanie początkowym
 * (isLoading=false, dane jeszcze nie przyszły) → resultCount=0 → thin → noindex.
 * Fix: bramka `hasLoaded` (resultCount=null dopóki nie ma POTWIERDZONEGO wyniku z API).
 * Maskował to prerender.io (runtime), do jego wygaśnięcia 2026-05-18.
 */

describe('computeListingResultCount', () => {
  it('null w trakcie ładowania (isLoading=true)', () => {
    expect(computeListingResultCount({ isLoading: true, hasLoaded: false, serverTotal: 0, listingsLength: 0 })).toBeNull()
  })

  it('REGRESJA: null w stanie początkowym (isLoading=false, ale hasLoaded=false — fetch nie wystartował)', () => {
    // Dokładnie ten stan dawał false-noindex po hydratacji u Googlebota.
    expect(computeListingResultCount({ isLoading: false, hasLoaded: false, serverTotal: 0, listingsLength: 0 })).toBeNull()
  })

  it('REGRESJA: null po błędzie fetcha (hasLoaded nigdy nie ustawione, choć isLoading=false)', () => {
    expect(computeListingResultCount({ isLoading: false, hasLoaded: false, serverTotal: 0, listingsLength: 12 })).toBeNull()
  })

  it('po udanym fetchu zwraca serverTotal gdy > 0', () => {
    expect(computeListingResultCount({ isLoading: false, hasLoaded: true, serverTotal: 316, listingsLength: 24 })).toBe(316)
  })

  it('po udanym fetchu bez serverTotal używa listings.length', () => {
    expect(computeListingResultCount({ isLoading: false, hasLoaded: true, serverTotal: 0, listingsLength: 5 })).toBe(5)
  })

  it('po udanym fetchu z realnie zero ofert zwraca 0 (nie null)', () => {
    expect(computeListingResultCount({ isLoading: false, hasLoaded: true, serverTotal: 0, listingsLength: 0 })).toBe(0)
  })
})

describe('shouldNoindexListing', () => {
  const filtered = { isFilteredListPage: true, hasExtraFilters: false }

  it('REGRESJA: resultCount=null (liczba nieznana) → NIE noindex, strona zostaje index', () => {
    expect(shouldNoindexListing({ ...filtered, resultCount: null })).toBe(false)
  })

  it('noindex gdy strona realnie cienka (resultCount < próg)', () => {
    expect(shouldNoindexListing({ ...filtered, resultCount: 0 })).toBe(true)
    expect(shouldNoindexListing({ ...filtered, resultCount: THIN_PAGE_THRESHOLD - 1 })).toBe(true)
  })

  it('NIE noindex gdy ofert >= próg', () => {
    expect(shouldNoindexListing({ ...filtered, resultCount: THIN_PAGE_THRESHOLD })).toBe(false)
    expect(shouldNoindexListing({ ...filtered, resultCount: 316 })).toBe(false)
  })

  it('noindex gdy aktywne filtry query (doorway), niezależnie od liczby ofert', () => {
    expect(shouldNoindexListing({ isFilteredListPage: true, hasExtraFilters: true, resultCount: 999 })).toBe(true)
  })

  it('strona nie-filtrowalna (/powierzchnie-reklamowe bez typu/miasta) nigdy nie jest thin', () => {
    expect(shouldNoindexListing({ isFilteredListPage: false, hasExtraFilters: false, resultCount: 0 })).toBe(false)
  })

  it('REGRESJA e2e: miasto z dużą podażą w stanie początkowym (resultCount=null) zostaje index', () => {
    // Warszawa: prerender ma index → hydratacja NIE może tego przeskoczyć na noindex.
    expect(shouldNoindexListing({ isFilteredListPage: true, hasExtraFilters: false, resultCount: null })).toBe(false)
  })
})
