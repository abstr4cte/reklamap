<script setup lang="ts">
import { ref, onMounted, computed, watch, nextTick, onBeforeUnmount, onActivated } from 'vue'
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router'
import EmailModal from '../components/EmailModal.vue'
import HeroBanner from '../components/HeroBanner.vue'
import PolandMap from '../components/PolandMap.vue'
import AdGrid from '../components/AdGrid.vue'
import Pagination from '../components/Pagination.vue'
import SearchAlertBox from '../components/SearchAlertBox.vue'
import SearchAlertModal from '../components/SearchAlertModal.vue'
import { filtersToQueryParams, queryParamsToFilters } from '../utils/filterUtils'
import { useSeo } from '../composables/useSeo'
import polishLocations from '../data/polishLocations.json'
import { useSearchStore } from '../stores/useSearchStore'
import { usePreferencesStore } from '../stores/usePreferencesStore'
import { storeToRefs } from 'pinia'
import type { FilterParams } from '../types/filters'

// Define component name for keep-alive
defineOptions({
  name: 'HomePage'
})

const searchStore = useSearchStore()
const prefStore = usePreferencesStore()
const { filters, sortBy, priceDisplay, isLoading, currentPage, itemsPerPage, viewMode } = storeToRefs(searchStore)

// Helper to map type to Polish label
const getTypeLabel = (type: string): string => searchStore.getTypeLabel(type)

const route = useRoute()
const router = useRouter()

const isModalOpen = ref(false)
const hoveredAdId = ref<string | null>(null)

const showSearchAlertModal = ref(false)
const hasShownAlertModal = ref(localStorage.getItem('search_alert_shown') === 'true')
const alertModalTimer = ref<number | null>(null)

// Scroll position management
const SCROLL_POSITION_KEY = 'homepage_scroll_position'

// Favorites and Comparison handlers
const handleToggleFavorite = async (id: string) => {
  await prefStore.toggleFavorite(id)
}

const handleToggleComparison = async (id: string) => {
  await prefStore.toggleComparison(id)
}

const sortedAndFilteredListings = computed(() => searchStore.sortedAndFilteredListings)

const activeFiltersCount = computed(() => searchStore.activeFiltersCount)
const totalPages = computed(() => searchStore.totalPages)
const paginatedListings = computed(() => searchStore.paginatedListings)

const handlePageChange = async (page: number) => {
  searchStore.setCurrentPage(page)
  
  // Poczekaj na aktualizację DOM przed scrollowaniem
  await nextTick()
  
  // Scroll to top of ads section z offsetem
  const adsSection = document.querySelector('.listings-section')
  if (adsSection) {
    const elementPosition = adsSection.getBoundingClientRect().top + window.pageYOffset
    const offsetPosition = elementPosition - 32 // 2rem = 32px offset
    
    window.scrollTo({
      top: offsetPosition,
      behavior: 'smooth'
    })
  }
}

const handleSearch = (searchFilters: FilterParams & { _priceDisplayUnit?: string }) => {
  // Wyczyść mapBounds przy nowym wyszukiwaniu, aby mapa mogła przybliżyć się do miasta/regionu
  const filtersWithoutMapBounds = { ...searchFilters, mapBounds: null }
  searchStore.applyFilters(filtersWithoutMapBounds)
  
  if (searchFilters._priceDisplayUnit) {
    priceDisplay.value = searchFilters._priceDisplayUnit as any
  }
  
  // Zaktualizuj URL (pozostając na stronie głównej) dodając zdefiniowane filtry do query params
  // Użyj przekazanych searchFilters bezpośrednio, aby uniknąć ewentualnych opóźnień reaktywności store
  const queryParams = filtersToQueryParams(searchFilters)
  
  // Użyj router.replace zamiast natywnego history.replaceState (Vue Router może to nadpisać)
  router.replace({ query: queryParams })

  // Restart alert timer from the moment of selection/search
  startAlertTimer()
}

const isResettingFilters = ref(false)

// Zmienne do zarządzania widocznością kafelków na urządzeniach mobilnych
watch(showSearchAlertModal, (isOpen) => {
  if (isOpen) {
    document.documentElement.style.overflow = 'hidden'
    document.body.style.overflow = 'hidden'
  } else {
    document.documentElement.style.overflow = ''
    document.body.style.overflow = ''
  }
})

const showAllCategories = ref(false)
const showAllCities = ref(false)
const isMobile = ref(false)

// Posortowane kategorie w stałej kolejności
const orderedCategories = computed(() => {
  const order = ['billboardy', 'banery', 'citylighty']
  return [...categories].sort((a, b) => {
    const indexA = order.indexOf(a.slug)
    const indexB = order.indexOf(b.slug)
    
    // Jeśli obie kategorie są w liście priorytetowej, sortuj według kolejności
    if (indexA !== -1 && indexB !== -1) {
      return indexA - indexB
    }
    // Jeśli tylko A jest w liście priorytetowej, daj A pierwszeństwo
    if (indexA !== -1) return -1
    // Jeśli tylko B jest w liście priorytetowej, daj B pierwszeństwo
    if (indexB !== -1) return 1
    // W przeciwnym razie zachowaj oryginalną kolejność
    return categories.indexOf(a) - categories.indexOf(b)
  })
})

// Sprawdź czy to urządzenie mobilne
const checkIfMobile = () => {
  isMobile.value = window.innerWidth <= 768
}

const getCurrentLocationLabel = computed(() => {
  if (filters.value.city) return filters.value.city
  if (filters.value.region) {
    const region = (polishLocations as any).voivodeships.find((v: any) => v.id === filters.value.region)
    return region ? region.name : filters.value.region
  }
  return ''
})

// Przełącz widoczność wszystkich kategorii
const toggleShowAllCategories = () => {
  showAllCategories.value = !showAllCategories.value
}

// Zapisz pozycję scrolla przed opuszczeniem strony (przejście do ogłoszenia)
onBeforeRouteLeave((to, _from, next) => {
  // Wyczyść timer alertu przed opuszczeniem strony
  if (alertModalTimer.value) {
    clearTimeout(alertModalTimer.value)
    alertModalTimer.value = null
  }
  
  // Zapisz tylko jeśli przechodzimy do szczegółów ogłoszenia
  if (to.path.includes('/powierzchnia-reklamowa/')) {
    const scrollY = window.scrollY || window.pageYOffset
    sessionStorage.setItem(SCROLL_POSITION_KEY, scrollY.toString())
  }
  next()
})

// Przywróć pozycję scrolla po powrocie na stronę
onActivated(() => {
  const savedPosition = sessionStorage.getItem(SCROLL_POSITION_KEY)
  if (savedPosition) {
    const position = parseInt(savedPosition, 10)
    setTimeout(() => {
      window.scrollTo({
        top: position,
        behavior: 'instant'
      })
      sessionStorage.removeItem(SCROLL_POSITION_KEY)
    }, 50)
  }

  // Synchronizuj filtry przy powrocie (keep-alive)
  // Jeśli wracamy na stronę główną bez flagi aktywnego wyszukiwania i bez parametrów URL, czyścimy stan
  const isUserSearch = localStorage.getItem('user_initiated_search') === 'true'
  if (Object.keys(route.query).length === 0 && !isUserSearch) {
    searchStore.resetFilters()
  }
})

// Obsługa zmiany rozmiaru okna
onMounted(() => {
  checkIfMobile()
  window.addEventListener('resize', checkIfMobile)
  
  // Sprawdź czy jest zapisana pozycja scrolla przy pierwszym załadowaniu
  const savedPosition = sessionStorage.getItem(SCROLL_POSITION_KEY)
  if (savedPosition) {
    const position = parseInt(savedPosition, 10)
    nextTick(() => {
      window.scrollTo({
        top: position,
        behavior: 'instant'
      })
      sessionStorage.removeItem(SCROLL_POSITION_KEY)
    })
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', checkIfMobile)
  document.body.style.overflow = ''
})

const handleReset = () => {
  // Wyczyść filtry w store (to automatycznie czyści localStorage + flagę user_initiated_search)
  searchStore.resetFilters()
  
  // Zresetuj sortowanie do domyślnego
  sortBy.value = 'newest'
  priceDisplay.value = 'day'
  
  // Wyczyść parametry URL - użyj router.replace, aby zachować zgodność z vue-router
  router.replace({ query: {} })
  
  // Smooth scroll do góry
  window.scrollTo({ top: 0, behavior: 'smooth' })
  
  // Zresetuj flagę po zakończeniu
  setTimeout(() => {
    isResettingFilters.value = false
  }, 0)
}

const loadAdvertisements = async () => {
  await searchStore.fetchListings()
}

// Watch for URL query parameter changes
watch(() => route.query, (newQuery) => {
  // Guard: watcher jest aktywny przez keep-alive nawet poza stroną główną - ignoruj inne strony
  if (route.name !== 'home') return

  // Jeśli query jest puste, a mamy flagę zapisanego wyszukiwania, przywróć je do URL
  // (to rozwiązuje problem klikania logo na Home, które czyści URL ale nie powinno czyścić filtrów)
  const isUserSearch = localStorage.getItem('user_initiated_search') === 'true'
  if (Object.keys(newQuery).length === 0) {
    if (isUserSearch) {
      const queryParams = filtersToQueryParams(filters.value)
      if (Object.keys(queryParams).length > 0) {
        router.replace({ query: queryParams }).catch(() => {})
      }
    } else {
      // Wejście na czystą stronę główną bez flagi szukania -> upewnij się, że store jest czysty
      searchStore.resetFilters()
    }
    return
  }
  
  // Aktualizuj numer strony
  const page = parseInt(newQuery.page as string) || 1
  if (page !== currentPage.value && page >= 1 && page <= totalPages.value) {
    searchStore.setCurrentPage(page)
  }
  
  // Aktualizuj filtry na podstawie query params
  const queryFilters = queryParamsToFilters(newQuery as Record<string, string>)
  
  // Aktualizuj tylko jeśli są różnice w filtrach
  if (JSON.stringify(queryFilters) !== JSON.stringify(filters.value)) {
    // Ustaw tylko niepuste wartości, aby nie nadpisywać domyślnych
    Object.entries(queryFilters).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '' && 
          (Array.isArray(value) ? value.length > 0 : true)) {
        // @ts-ignore - dynamiczny dostęp do właściwości
        filters.value[key] = value
      }
    })
  }
}, { deep: true })

// SEO Meta Tags
useSeo({
  title: 'ReklaMap - Wynajem Powierzchni Reklamowych w Polsce | Billboardy, Citylighty, Banery',
  description: 'Znajdź i wynajmij powierzchnie reklamowe w całej Polsce. Billboardy, citylighty, banery, ściany reklamowe. Porównuj oferty, sprawdzaj ceny i lokalizacje na mapie.',
  keywords: 'powierzchnie reklamowe, billboardy, citylighty, banery reklamowe, wynajem billboardu, reklama zewnętrzna, outdoor, powierzchnie OOH',
  ogType: 'website',
  ogImage: `${typeof window !== 'undefined' ? window.location.origin : 'https://reklamap.pl'}/og-image.png`,
  ogImageWidth: '1200',
  ogImageHeight: '630',
  ogImageAlt: 'ReklaMap – platforma powierzchni reklamowych w Polsce',
  canonical: typeof window !== 'undefined' ? window.location.origin : 'https://reklamap.pl',
  structuredData: {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    'name': 'ReklaMap',
    'url': typeof window !== 'undefined' ? window.location.origin : 'https://reklamap.pl',
    'description': 'Platforma do wynajmu powierzchni reklamowych w całej Polsce',
    'potentialAction': {
      '@type': 'SearchAction',
      'target': {
        '@type': 'EntryPoint',
        'urlTemplate': `${typeof window !== 'undefined' ? window.location.origin : 'https://reklamap.pl'}/powierzchnie-reklamowe?keyword={search_term_string}`
      },
      'query-input': 'required name=search_term_string'
    }
  }
})

const categories = [
  {
    name: 'Billboardy',
    slug: 'billboardy',
    icon: 'billboard.svg',
    description: 'Duże formaty przy drogach krajowych i autostradach, zapewniające wysoką widoczność dla kampanii wizerunkowych.'
  },
  {
    name: 'Banery',
    slug: 'banery',
    icon: 'banner.svg',
    description: 'Elastyczne powierzchnie montowane na budynkach i płotach, łatwe do dopasowania do dostępnej przestrzeni.'
  },
  {
    name: 'Citylighty',
    slug: 'citylighty',
    icon: 'citylight.svg',
    description: 'Podświetlane witryny w centrach miast, przy przystankach i galeriach, gwarantujące stałą ekspozycję.'
  },
  {
    name: 'Ekrany LED',
    slug: 'ekrany-led',
    icon: 'ekran-led.svg',
    description: 'Cyfrowe wyświetlacze dynamiczne umożliwiające animacje i spoty wideo, idealne do nowoczesnych kampanii.'
  },
  {
    name: 'Ściany reklamowe',
    slug: 'sciany-reklamowe',
    icon: 'sciana.svg',
    description: 'Murale i reklamy wielkoformatowe na elewacjach budynków, przyciągające uwagę w przestrzeni miejskiej.'
  },
  {
    name: 'Totemy reklamowe',
    slug: 'totemy-reklamowe',
    icon: 'totem.svg',
    description: 'Wysokie, wolnostojące słupy w centrach handlowych i placach, skuteczne w zwiększaniu rozpoznawalności marki.'
  },
  {
    name: 'Reklama w transporcie',
    slug: 'reklama-w-transporcie',
    icon: 'transport.svg',
    description: 'Nośniki umieszczone na autobusach, tramwajach, metrze i przystankach, docierające do szerokiego grona odbiorców.'
  },
  {
    name: 'Reklama mobilna',
    slug: 'reklama-mobilna',
    icon: 'mobilna.svg',
    description: 'Ruchome formaty, takie jak przyczepki i samochody firmowe, pozwalające dotrzeć z przekazem tam, gdzie jest grupa docelowa.'
  },
  {
    name: 'Inne',
    slug: 'inne',
    icon: 'inna.svg',
    description: 'Niestandardowe i uzupełniające formy reklamy, w tym digital signage, reklama ambientowa czy neony'
  }
]

const popularCities = [
  { name: 'Warszawa', slug: 'warszawa', region: 'mazowieckie' },
  { name: 'Kraków', slug: 'krakow', region: 'małopolskie' },
  { name: 'Wrocław', slug: 'wroclaw', region: 'dolnośląskie' },
  { name: 'Poznań', slug: 'poznan', region: 'wielkopolskie' },
  { name: 'Gdańsk', slug: 'gdansk', region: 'pomorskie' },
  { name: 'Łódź', slug: 'lodz', region: 'łódzkie' },
  { name: 'Katowice', slug: 'katowice', region: 'śląskie' },
  { name: 'Szczecin', slug: 'szczecin', region: 'zachodniopomorskie' },
  { name: 'Bydgoszcz', slug: 'bydgoszcz', region: 'kujawsko-pomorskie' },
  { name: 'Lublin', slug: 'lublin', region: 'lubelskie' },
  { name: 'Białystok', slug: 'bialystok', region: 'podlaskie' },
  { name: 'Gdynia', slug: 'gdynia', region: 'pomorskie' }
]

onMounted(() => {
  loadAdvertisements()
  
  // Sprawdź flagę user_initiated_search - TYLKO jeśli jest, ładuj filtry z localStorage
  const isUserSearch = localStorage.getItem('user_initiated_search') === 'true'
  
  // Jeśli są parametry w URL, zastosuj je jako filtry
  // (Pominięte automatyczne zapisywanie do localStorage dla param. w URL, żeby nie nadpisywać 
  // globalnych preferencji po kliknięciu udostępnionego linku)
  if (Object.keys(route.query).length > 0) {
    const queryFilters = queryParamsToFilters(route.query as Record<string, string>)
    // Połącz z domyślnymi filtrami
    filters.value = { ...filters.value, ...queryFilters }
  } else if (isUserSearch) {
    // Jeśli NIE ma query params w URL, ale jest flaga user_initiated_search
    // User wraca na stronę główną (bez kliknięcia Wyczyść i bez linku kategorii)
    // Załaduj filtry z localStorage i dodaj do URL jako query params
    try {
      const saved = localStorage.getItem('reklamap_last_search')
      if (saved) {
        const lastSearch = JSON.parse(saved)
        
        // Sprawdź czy są jakiekolwiek filtry
        const hasAnyFilters = Object.entries(lastSearch).some(([key, value]) => {
          if (['mapBounds', '_priceDisplayUnit'].includes(key)) return false
          if (value === null || value === undefined || value === '' || value === false) return false
          if (Array.isArray(value) && value.length === 0) return false
          return true
        })
        
        if (hasAnyFilters) {
          filters.value = { ...filters.value, ...lastSearch }
          
          if (lastSearch._priceDisplayUnit) {
            priceDisplay.value = lastSearch._priceDisplayUnit as any
          }
          
          // Dodaj filtry do URL jako query params (miasto/lokalizacja jako query params na stronie głównej)
          // Użyj lastSearch bezpośrednio, aby mieć pewność że booleany (ze zdjęciem, faktura VAT) trafią do URL
          const queryParams = filtersToQueryParams(lastSearch as any)
          
          nextTick(() => {
            router.replace({ query: queryParams }).catch(() => {
              // Ignore NavigationDuplicated error silently
            })
          })
        }
      }
    } catch (error) {
      // Silently fail
    }
  } else {
    // Wejście bez query params i bez flagi wyszukiwania. Oczyść ewentualne resztki w Pinia.
    searchStore.resetFilters()
  }
  // Jeśli NIE ma flagi user_initiated_search i NIE ma query params → czyste wejście, nie ładuj nic

  // Logic for showing the search alert modal after 20 seconds
  startAlertTimer()
})

const startAlertTimer = () => {
  if (hasShownAlertModal.value) return
  
  if (alertModalTimer.value) {
    clearTimeout(alertModalTimer.value)
  }

  alertModalTimer.value = setTimeout(() => {
    // Show only if user has type selected, hasn't seen it yet, AND is still on HomePage
    if (!hasShownAlertModal.value && filters.value.type && router.currentRoute.value.path === '/') {
      showSearchAlertModal.value = true
      hasShownAlertModal.value = true
      localStorage.setItem('search_alert_shown', 'true')
    }
  }, 20000) as unknown as number // 20 seconds
}

const handleSearchAlertSubmit = () => {
  // Alert saved
}

// Wyczyść flagę user_initiated_search gdy użytkownik wchodzi z linku kategorii/miasta
const clearSearchFlag = () => {
  try {
    localStorage.removeItem('user_initiated_search')
    localStorage.removeItem('reklamap_last_search')
    // Zresetuj też stan w store, aby UI (np. HeroBanner) odświeżyło się natychmiast
    searchStore.resetFilters()
  } catch (e) { /* ignore */ }
}

</script>

<template>
  <div>
    <!-- SVG Gradient Definition -->
    <svg width="0" height="0" style="position: absolute;">
      <defs>
        <linearGradient id="icon-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" style="stop-color:#667eea;stop-opacity:1" />
          <stop offset="100%" style="stop-color:#764ba2;stop-opacity:1" />
        </linearGradient>
      </defs>
    </svg>
    
    <EmailModal :is-open="isModalOpen" @close="isModalOpen = false" />
    <HeroBanner data-hero-banner @search="handleSearch" @reset="handleReset" />
    
    <!-- Categories Section -->
    <section class="categories-section">
      <div class="categories-container">
        <h2 class="categories-title">Przeglądaj kategorie powierzchni reklamowych</h2>
        <div class="categories-grid">
          <template v-for="category in isMobile && !showAllCategories ? orderedCategories.slice(0, 3) : orderedCategories" :key="category.slug">
            <router-link
              :to="`/powierzchnie-reklamowe/${category.slug}`"
              class="category-card"
              :class="{ 'mobile-category': isMobile }"
              @click="clearSearchFlag"
            >
              <div class="category-icon">
                <div class="icon-mask" :style="{ '-webkit-mask-image': `url(/icons/${category.icon})`, 'mask-image': `url(/icons/${category.icon})` }" :aria-label="category.name"></div>
              </div>
              <h3 class="category-name">{{ category.name }}</h3>
              <p class="category-description">{{ category.description }}</p>
              <div class="category-arrow">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <path d="M5 12h14m-7-7l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
            </router-link>
          </template>
          
          <!-- Przycisk Pokaż więcej/mniej -->
          <button 
            v-if="isMobile && orderedCategories.length > 3" 
            @click="toggleShowAllCategories" 
            class="show-more-button"
            :aria-expanded="showAllCategories"
          >
            {{ showAllCategories ? 'Pokaż mniej' : `Pokaż więcej (${orderedCategories.length - 3})` }}
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" :class="{ 'rotate-180': showAllCategories }">
              <path d="M19 9l-7 7-7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
      </div>
    </section>

    <!-- Popular Cities Section -->
    <section class="cities-section">
      <div class="cities-container">
        <h2 class="cities-title">Popularne miasta</h2>
        <p class="cities-subtitle">Znajdź powierzchnie reklamowe w największych miastach Polski</p>
        <div class="cities-grid">
          <template v-for="city in isMobile && !showAllCities ? popularCities.slice(0, 6) : popularCities" :key="city.slug">
            <router-link
              :to="`/powierzchnie-reklamowe/${city.slug}`"
              class="city-card"
              :class="{ 'mobile-city': isMobile }"
              @click="clearSearchFlag"
            >
              <div class="city-name">{{ city.name }}</div>
              <div class="city-region">{{ city.region }}</div>
              <div class="city-arrow">→</div>
            </router-link>
          </template>
          
          <!-- Przycisk Pokaż więcej/mniej dla miast -->
          <button 
            v-if="isMobile && popularCities.length > 6" 
            @click="showAllCities = !showAllCities" 
            class="show-more-button show-more-cities"
            :aria-expanded="showAllCities"
          >
            {{ showAllCities ? 'Pokaż mniej' : `Pokaż więcej (${popularCities.length - 6})` }}
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" :class="{ 'rotate-180': showAllCities }">
              <path d="M19 9l-7 7-7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
      </div>
    </section>
    
    <PolandMap 
      data-poland-map
      :listings="sortedAndFilteredListings" 
      :selected-region="filters.region"
      :selected-city="filters.city"
      :selected-location-coords="filters.selectedLocationCoords"
      :hovered-ad-id="hoveredAdId"
    />
    <AdGrid
      :listings="paginatedListings"
      :is-loading="isLoading"
      :view-mode="viewMode"
      :sort-by="sortBy"
      :price-display="priceDisplay"
      :active-filters-count="activeFiltersCount"
      @toggle-favorite="handleToggleFavorite"
      @toggle-comparison="handleToggleComparison"
      @update:view-mode="viewMode = $event"
      @update:sort-by="sortBy = $event"
      @update:hovered-ad-id="hoveredAdId = $event"
    >
      <template #empty-content>
        <SearchAlertBox 
          v-if="activeFiltersCount > 0"
          :location-label="getCurrentLocationLabel"
          :ad-type-label="filters.type ? getTypeLabel(filters.type) : 'ogłoszenie'"
          @click="showSearchAlertModal = true"
        />
      </template>
    </AdGrid>
    <Pagination
      v-if="!isLoading && paginatedListings.length > 0"
      :current-page="currentPage"
      :total-pages="totalPages"
      :total-items="sortedAndFilteredListings.length"
      :items-per-page="itemsPerPage"
      :show-info="true"
      :scroll-to-top="false"
      @update:current-page="handlePageChange"
    />

    <!-- End of List Search Alert CTA -->
    <div class="home-alert-container">
      <SearchAlertBox 
        v-if="activeFiltersCount > 0 && paginatedListings.length > 0"
        :location-label="getCurrentLocationLabel"
        :ad-type-label="filters.type ? getTypeLabel(filters.type) : 'ogłoszenie'"
        @click="showSearchAlertModal = true"
      />
    </div>

    <!-- Search Alert Global Modal -->
    <Teleport to="body">
      <SearchAlertModal 
        v-if="showSearchAlertModal"
        :active-filters="filters"
        :location-label="getCurrentLocationLabel"
        @close="showSearchAlertModal = false"
        @submit="handleSearchAlertSubmit"
      />
    </Teleport>
  </div>
</template>


<style scoped>
.categories-section {
  background: var(--bg-secondary, linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%));
  padding: 5rem 0;
  margin: 0;
  position: relative;
  overflow: hidden;
  transition: background 0.3s ease;
}

.categories-section::before {
  content: '';
  position: absolute;
  width: 500px;
  height: 500px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
  top: -250px;
  right: -250px;
  animation: float 20s ease-in-out infinite;
}

.categories-section::after {
  content: '';
  position: absolute;
  width: 400px;
  height: 400px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(118, 75, 162, 0.1) 0%, transparent 70%);
  bottom: -200px;
  left: -200px;
  animation: float 15s ease-in-out infinite reverse;
}

.categories-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
  position: relative;
  z-index: 1;
}

.categories-title {
  font-size: 2.5rem;
  font-weight: 800;
  text-align: center;
  color: var(--text-main, #1f2937);
  margin-bottom: 3.5rem;
  animation: fadeInDown 0.6s ease-out;
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 2.5rem;
}

.category-card {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.12) 0%, rgba(118, 75, 162, 0.08) 50%, rgba(255, 255, 255, 0.05) 100%);
  backdrop-filter: blur(15px);
  border-radius: 24px;
  padding: 2.5rem;
  text-decoration: none;
  color: var(--text-main, inherit);
  transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
  box-shadow: 0 10px 40px -10px rgba(102, 126, 234, 0.15);
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  animation: fadeInUp 0.6s ease-out backwards;
  border: 1px solid rgba(102, 126, 234, 0.2);
}

.category-card:nth-child(1) { animation-delay: 0.1s; }
.category-card:nth-child(2) { animation-delay: 0.2s; }
.category-card:nth-child(3) { animation-delay: 0.3s; }
.category-card:nth-child(4) { animation-delay: 0.4s; }
.category-card:nth-child(5) { animation-delay: 0.5s; }
.category-card:nth-child(6) { animation-delay: 0.6s; }

.category-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 4px;
  background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
  background-size: 200% 100%;
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1);
  z-index: 3;
}

.category-card::after {
  content: '';
  position: absolute;
  top: 0;
  left: -150%;
  width: 150%;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(255, 255, 255, 0.4) 50%,
    transparent 100%
  );
  transform: skewX(-25deg);
  transition: 0s;
  z-index: 2;
  pointer-events: none;
}

.category-card:hover {
  transform: translateY(-8px) scale(1.02);
  box-shadow: 0 30px 60px -15px rgba(102, 126, 234, 0.3);
  border-color: rgba(102, 126, 234, 0.4);
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(118, 75, 162, 0.15) 100%);
}

.category-card:hover::before {
  transform: scaleX(1);
  animation: flowGradient 3s linear infinite;
}

.category-card:hover::after {
  left: 150%;
  transition: left 0.8s ease-in-out;
}

@keyframes flowGradient {
  0% { background-position: 0% 50%; }
  100% { background-position: 200% 50%; }
}

.category-card:hover .category-arrow {
  transform: translateX(5px);
  opacity: 1;
}

.category-icon {
  width: 80px;
  height: 80px;
  margin-bottom: 1rem;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
  border-radius: 20px;
  padding: 1rem;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.category-icon .icon-mask {
  width: 100%;
  height: 100%;
  background-color: #8a6fe6;
  -webkit-mask-size: contain;
  mask-size: contain;
  -webkit-mask-repeat: no-repeat;
  mask-repeat: no-repeat;
  -webkit-mask-position: center;
  mask-position: center;
}

.category-card:hover .category-icon {
  transform: scale(1.15) rotate(5deg);
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.25);
}


.category-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-main, #1f2937);
  margin: 0;
  position: relative;
  z-index: 1;
  transition: all 0.3s ease;
  letter-spacing: -0.02em;
}

.category-card:hover .category-name {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.category-description {
  font-size: 0.95rem;
  color: var(--text-muted, #6b7280);
  margin: 0;
  flex: 1;
  position: relative;
  z-index: 1;
  line-height: 1.7;
  transition: color 0.3s ease;
}

.category-card:hover .category-description {
  color: var(--text-main, #4b5563);
}

.category-arrow {
  color: #667eea;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  position: relative;
  z-index: 1;
  display: inline-flex;
  margin-top: auto;
  padding-top: 1rem;
}

.category-card:hover .category-arrow {
  transform: translateX(5px);
  opacity: 1;
}

@media (max-width: 768px) {
  .categories-section {
    padding: 3rem 0;
  }
  
  .categories-section::before,
  .categories-section::after {
    display: none;
  }

  .categories-title {
    font-size: 1.75rem;
    margin-bottom: 2rem;
  }

  .categories-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
    max-width: 100%;
    width: 100%;
  }
  
  .category-card {
    padding: 1.25rem;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    width: 100%;
    box-sizing: border-box;
  }
  
  .category-icon {
    width: 60px;
    height: 60px;
    margin-bottom: 0.75rem;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    border-radius: 16px;
    padding: 0.75rem;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .category-name {
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
  }
  
  .category-description {
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 1rem;
  }
  
  .show-more-button {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem;
    background: var(--bg-tertiary, rgba(102, 126, 234, 0.1));
    border: 1px solid var(--border-color, rgba(102, 126, 234, 0.2));
    border-radius: 12px;
    color: var(--text-main, #4F46E5);
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-top: 0.5rem;
  }
  
  .show-more-button:hover {
    background: rgba(102, 126, 234, 0.15);
    border-color: rgba(102, 126, 234, 0.3);
  }
  
  .show-more-button svg {
    transition: transform 0.3s ease;
  }
  
  .show-more-button .rotate-180 {
    transform: rotate(180deg);
  }
}

/* Cities Section Styles */
.cities-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 5rem 0;
  margin: 0;
  position: relative;
  overflow: hidden;
}

.cities-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
  opacity: 0.3;
}

.cities-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
  position: relative;
  z-index: 1;
}

.cities-title {
  font-size: 2.5rem;
  font-weight: 800;
  text-align: center;
  color: white;
  margin-bottom: 0.75rem;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  animation: fadeInDown 0.6s ease-out;
}

.cities-subtitle {
  text-align: center;
  color: rgba(255, 255, 255, 0.9);
  font-size: 1.15rem;
  margin-bottom: 3.5rem;
  animation: fadeInDown 0.6s ease-out 0.1s backwards;
}

.cities-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 2rem;
}

.city-card {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
  backdrop-filter: blur(12px);
  border-radius: 20px;
  padding: 2.25rem 2rem;
  text-decoration: none;
  color: #ffffff;
  transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  position: relative;
  overflow: hidden;
  animation: fadeInUp 0.6s ease-out backwards;
  border: 1px solid rgba(255, 255, 255, 0.2);
  min-height: 120px;
}

.city-card:nth-child(1) { animation-delay: 0.1s; }
.city-card:nth-child(2) { animation-delay: 0.15s; }
.city-card:nth-child(3) { animation-delay: 0.2s; }
.city-card:nth-child(4) { animation-delay: 0.25s; }
.city-card:nth-child(5) { animation-delay: 0.3s; }
.city-card:nth-child(6) { animation-delay: 0.35s; }
.city-card:nth-child(7) { animation-delay: 0.4s; }
.city-card:nth-child(8) { animation-delay: 0.45s; }
.city-card:nth-child(9) { animation-delay: 0.5s; }
.city-card:nth-child(10) { animation-delay: 0.55s; }
.city-card:nth-child(11) { animation-delay: 0.6s; }
.city-card:nth-child(12) { animation-delay: 0.65s; }

.city-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 4px;
  background: linear-gradient(90deg, #ffffff, rgba(255, 255, 255, 0.5), #ffffff);
  background-size: 200% 100%;
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1);
  z-index: 3;
}

.city-card::after {
  content: '';
  position: absolute;
  top: 0;
  left: -150%;
  width: 150%;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(255, 255, 255, 0.3) 50%,
    transparent 100%
  );
  transform: skewX(-25deg);
  transition: 0s;
  z-index: 2;
  pointer-events: none;
}

.city-card:hover {
  transform: translateY(-8px) scale(1.02);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
  border-color: rgba(255, 255, 255, 0.5);
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%);
}

.city-card:hover::before {
  transform: scaleX(1);
}

.city-card:hover::after {
  left: 150%;
  transition: left 0.7s ease-in-out;
}

.city-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: #ffffff;
  position: relative;
  z-index: 1;
  transition: all 0.3s ease;
  letter-spacing: -0.02em;
  line-height: 1.3;
}

.city-card:hover .city-name {
  color: #ffffff;
  transform: translateX(4px);
  text-shadow: 0 0 15px rgba(255, 255, 255, 0.4);
}

.city-region {
  font-size: 0.95rem;
  color: rgba(255, 255, 255, 0.7);
  text-transform: capitalize;
  position: relative;
  z-index: 1;
  font-weight: 500;
  line-height: 1.4;
}

.city-arrow {
  position: absolute;
  top: 2rem;
  right: 1.75rem;
  font-size: 2rem;
  color: #ffffff;
  opacity: 0;
  transform: translateX(-15px) rotate(-45deg);
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  z-index: 1;
  filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.5));
}

.city-card:hover .city-arrow {
  opacity: 1;
  transform: translateX(0) rotate(0deg);
}

@media (max-width: 1050px) {
  .cities-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .cities-section {
    padding: 3.5rem 0;
  }

  .cities-title {
    font-size: 1.75rem;
  }

  .cities-subtitle {
    font-size: 1rem;
    margin-bottom: 2.5rem;
  }

  .cities-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
  }

  .city-card {
    padding: 1.5rem;
  }
  
  .city-name {
    font-size: 1.15rem;
  }
  
  .city-region {
    font-size: 0.85rem;
  }
}

@media (max-width: 768px) {
  .cities-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }
  
  .city-card {
    padding: 1.25rem;
    transition: all 0.3s ease;
  }
  
  .city-name {
    font-size: 1.2rem;
  }
  
  .city-region {
    font-size: 0.8rem;
  }
  
  .show-more-cities {
    grid-column: 1 / -1;
    margin-top: 0.5rem;
    background: rgba(255, 255, 255, 0.1) !important;
    border-color: rgba(255, 255, 255, 0.2) !important;
    color: white !important;
  }
  
  .show-more-cities:hover {
    background: rgba(255, 255, 255, 0.15) !important;
    border-color: rgba(255, 255, 255, 0.3) !important;
  }
}

@media (max-width: 480px) {
  .cities-grid {
    grid-template-columns: 1fr;
  }
}

/* Animations */
@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes float {
  0%, 100% {
    transform: translate(0, 0);
  }
  50% {
    transform: translate(30px, 30px);
  }
}

.home-alert-container {
  max-width: 1400px;
  margin: 0 auto 4rem auto;
  padding: 0 2rem;
}
</style>



