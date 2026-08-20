import { describe, it, expect } from 'vitest'

/**
 * Tests for the "w okolicy" (nearby, 30km radius) section logic in useSearchStore.
 *
 * Pilot Katowice (CLAUDE.md „Geo-bucketing”): sekcja NIE może wpływać na `serverTotal`/
 * `hasLoaded` (podstawa THIN_PAGE_THRESHOLD/noindex w listingsSeo.ts) — testujemy tu w
 * izolacji dwie reguły z buildApiParams()/fetchListings() w useSearchStore.ts:
 * 1. `include_nearby=1` jest wysyłane TYLKO dla city-only, cityStrict, bez typu, bez map bounds.
 * 2. `nearby_listings` z odpowiedzi API jest parsowane osobno i nigdy nie zasila `listings`/`serverTotal`.
 */

type FiltersLike = {
  city: string | null
  cityStrict: boolean
  type: string | null
  mapBounds: unknown | null
}

// Replika warunku z buildApiParams() (useSearchStore.ts) — patrz komentarz przy include_nearby.
function shouldIncludeNearby(f: FiltersLike): boolean {
  return !!(f.city && f.cityStrict && !f.type && !f.mapBounds)
}

// Replika parsowania odpowiedzi z fetchListings() (useSearchStore.ts).
function parseNearbyListings(response: unknown): unknown[] {
  return response && typeof response === 'object' && Array.isArray((response as any).nearby_listings)
    ? (response as any).nearby_listings
    : []
}

describe('nearby listings (promień 30 km) logic', () => {
  it('wysyła include_nearby dla strony city-only z cityStrict', () => {
    expect(shouldIncludeNearby({ city: 'Katowice', cityStrict: true, type: null, mapBounds: null })).toBe(true)
  })

  it('NIE wysyła include_nearby, gdy jest wybrany typ (strona combo typ×miasto)', () => {
    expect(shouldIncludeNearby({ city: 'Katowice', cityStrict: true, type: 'billboard', mapBounds: null })).toBe(false)
  })

  it('NIE wysyła include_nearby bez cityStrict (dopasowanie LIKE, nie ścisłe miasto)', () => {
    expect(shouldIncludeNearby({ city: 'Katowice', cityStrict: false, type: null, mapBounds: null })).toBe(false)
  })

  it('NIE wysyła include_nearby, gdy aktywne są granice mapy', () => {
    expect(shouldIncludeNearby({ city: 'Katowice', cityStrict: true, type: null, mapBounds: { northEast: {}, southWest: {} } })).toBe(false)
  })

  it('NIE wysyła include_nearby bez wybranego miasta', () => {
    expect(shouldIncludeNearby({ city: null, cityStrict: false, type: null, mapBounds: null })).toBe(false)
  })

  it('parsuje nearby_listings z odpowiedzi paginowanej', () => {
    const response = { data: [], total: 3, current_page: 1, nearby_listings: [{ id: 1 }, { id: 2 }] }
    expect(parseNearbyListings(response)).toEqual([{ id: 1 }, { id: 2 }])
  })

  it('zwraca pustą tablicę, gdy odpowiedź nie ma pola nearby_listings (include_nearby nie było wysłane)', () => {
    const response = { data: [], total: 3, current_page: 1 }
    expect(parseNearbyListings(response)).toEqual([])
  })

  it('zwraca pustą tablicę dla odpowiedzi w formie zwykłej tablicy (legacy ids=)', () => {
    expect(parseNearbyListings([{ id: 1 }])).toEqual([])
  })
})

/**
 * Promień jest dynamiczny: backend schodzi do 50 km dla miast bez własnej podaży
 * (Kraków/Gdańsk/Łódź = 0 ofert na prodzie 2026-08-20), więc nagłówek sekcji nie może
 * mieć zaszytego „30 km". Replika parsowania z fetchListings().
 */
function parseNearbyRadius(response: unknown): number {
  return Number((response as any)?.nearby_radius_km) || 30
}

describe('nearby radius parsing', () => {
  it('czyta promień z odpowiedzi API', () => {
    expect(parseNearbyRadius({ nearby_radius_km: 50 })).toBe(50)
  })

  it('domyśla się 30 km, gdy pole nie przyszło (starsza odpowiedź API)', () => {
    expect(parseNearbyRadius({ data: [], total: 0 })).toBe(30)
  })

  it('domyśla się 30 km przy wartości bezsensownej', () => {
    expect(parseNearbyRadius({ nearby_radius_km: null })).toBe(30)
    expect(parseNearbyRadius({ nearby_radius_km: 'abc' })).toBe(30)
  })
})
