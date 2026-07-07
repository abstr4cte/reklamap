import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '../services/api'
import type { NavCity, NavCombo } from '../types'

/**
 * Huby nawigacyjne (stopka + menu): miasta i kombinacje typ×miasto z REALNĄ podażą, z backendu
 * (`/api/listings/nav-hubs`). Zastępuje sztywne listy demand-miast (Warszawa/Kraków z 0 ofert) —
 * linkowanie wewnętrzne kieruje crawl/link-equity Google do stron, które MAJĄ treść i są `index`.
 *
 * SEO/hydratacja: prerender zaszywa listę w `window.__INITIAL_STATE__.nav` (kolektor w main.ts),
 * więc linki są w statycznym HTML dla bota. Seed → fetch (odświeżenie) → FALLBACK (gdyby oba padły,
 * stopka linkuje pewne huby, nie pustkę).
 */

// Bezpieczna lista awaryjna — realne huby podaży (używana tylko gdy brak seedu I fetch padł).
const FALLBACK_CITIES: NavCity[] = [
  { name: 'Kłodzko', slug: 'klodzko', count: 0 },
  { name: 'Koszalin', slug: 'koszalin', count: 0 },
  { name: 'Dąbrowa Górnicza', slug: 'dabrowa-gornicza', count: 0 },
  { name: 'Biała Podlaska', slug: 'biala-podlaska', count: 0 },
  { name: 'Ząbkowice Śląskie', slug: 'zabkowice-slaskie', count: 0 },
  { name: 'Poznań', slug: 'poznan', count: 0 },
  { name: 'Wrocław', slug: 'wroclaw', count: 0 },
  { name: 'Sosnowiec', slug: 'sosnowiec', count: 0 },
  { name: 'Kudowa-Zdrój', slug: 'kudowa-zdroj', count: 0 },
  { name: 'Bielawa', slug: 'bielawa', count: 0 },
]

const FALLBACK_COMBOS: NavCombo[] = FALLBACK_CITIES.slice(0, 8).map((c) => ({
  label: `Billboardy ${c.name}`,
  typeSlug: 'billboardy',
  citySlug: c.slug,
  count: 0,
}))

export const useNavStore = defineStore('nav', () => {
  const _ssr = (typeof window !== 'undefined'
    ? (window as any).__INITIAL_STATE__?.nav
    : null) as { cities?: NavCity[]; combos?: NavCombo[] } | null | undefined

  const _ssrCities = Array.isArray(_ssr?.cities) && _ssr!.cities!.length > 0 ? _ssr!.cities! : null
  const _ssrCombos = Array.isArray(_ssr?.combos) && _ssr!.combos!.length > 0 ? _ssr!.combos! : null

  const cities = ref<NavCity[]>(_ssrCities ?? FALLBACK_CITIES)
  const combos = ref<NavCombo[]>(_ssrCombos ?? FALLBACK_COMBOS)
  // true = mamy dane z seedu/fetcha (nie fallback). Zapobiega podwójnemu fetchowi.
  const hasLoaded = ref<boolean>(!!_ssrCities)

  const fetchNavHubs = async (): Promise<void> => {
    if (hasLoaded.value) return
    try {
      const data = await api.getNavHubs()
      if (Array.isArray(data?.cities) && data.cities.length > 0) cities.value = data.cities
      if (Array.isArray(data?.combos) && data.combos.length > 0) combos.value = data.combos
      hasLoaded.value = true
    } catch {
      // zostaje seed albo fallback — nigdy nie zerujemy linków
    }
  }

  return { cities, combos, hasLoaded, fetchNavHubs }
})
