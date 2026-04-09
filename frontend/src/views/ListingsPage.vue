<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, onActivated, nextTick, watch } from 'vue'

defineOptions({
  name: 'listings'
})
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useSearchStore, typeColors, typeLabels, type LocationSuggestion, popularLocations, variantLabels } from '../stores/useSearchStore'
import { usePreferencesStore } from '../stores/usePreferencesStore'
import { getFullImageUrl } from '../services/api'
import { type LocationResult, debouncedSearchLocations } from '../services/locationService'
import { slugify, deslugify } from '../utils/slugify'
import { mapTypeToUrlFormat } from '../utils/typeMapping'
import { filtersToQueryParams } from '../utils/filterUtils'
import polishLocations from '../data/polishLocations.json'
import { categoryDescriptions, cityDescriptions } from '../data/categoryDescriptions'
import type * as LType from 'leaflet'

import Pagination from '../components/Pagination.vue'
import Breadcrumbs from '../components/Breadcrumbs.vue'
import CategoryDescription from '../components/CategoryDescription.vue'
import SearchAlertModal from '../components/SearchAlertModal.vue'
import SearchAlertBox from '../components/SearchAlertBox.vue'
import SkeletonCard from '../components/SkeletonCard.vue'
import AdCard from '../components/AdCard.vue'

// Store and Routing
const searchStore = useSearchStore()
const prefStore = usePreferencesStore()
const route = useRoute()
const router = useRouter()
const {
  filters,
  sortBy,
  viewMode,
  currentPage,
  isLoading,
  listings,
  sortedAndFilteredListings: filteredListings,
  paginatedListings,
  totalPages,
  activeFiltersCount,
  itemsPerPage,
  pathParamsFilters
} = storeToRefs(searchStore)

const totalFiltersCount = computed(() => {
  let count = activeFiltersCount.value
  if (pathParamsFilters.value.type) count++
  if (pathParamsFilters.value.city) count++
  return count
})

// UI State
const isInitialized = ref(false)
const showFiltersModal = ref(false)

watch(showFiltersModal, (isOpen) => {
  if (isOpen) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})
const tempFilters = ref<any>(null)
const mapContainer = ref<HTMLElement | null>(null)
let map: LType.Map | null = null
let resizeObserver: ResizeObserver | null = null
const markers: Map<string, LType.Marker> = new Map()
// let markerClusterGroup: any = null
const showMapOnMobile = ref(false)
const showSortPanel = ref(false)
const showSearchAlertModal = ref(false)
const hasShownAlertModal = ref(localStorage.getItem('search_alert_shown') === 'true')
const alertModalTimer = ref<number | null>(null)
const isStatusMenuOpen = ref(false)
const statusMultiselect = ref<HTMLElement | null>(null)
const hoveredAdId = ref<string | null>(null)
const selectedAdId = ref<string | null>(null)
const isMobile = ref(false)
const isLegendVisible = ref(false)
const showMapButton = ref(true)
const listContainerRef = ref<HTMLElement | null>(null)
const showListScrollTop = ref(false)
const isProgrammaticMove = ref(false)
const isMapActive = ref(false)

// Scroll position management
const LISTINGS_SCROLL_KEY = 'listings_scroll_position'
const LISTINGS_PAGE_KEY = 'listings_current_page'

// Zapisz pozycję scrolla przed opuszczeniem strony (przejście do ogłoszenia)
onBeforeRouteLeave((to, _from, next) => {
  // Wyczyść timer alertu przed opuszczeniem strony
  if (alertModalTimer.value) {
    clearTimeout(alertModalTimer.value)
    alertModalTimer.value = null
  }
  
  // Zapisz tylko jeśli przechodzimy do szczegółów ogłoszenia
  if (to.path.includes('/powierzchnia-reklamowa/')) {
    // Zapisz scroll kontenera listy (nie window, bo listings-list-container ma overflow-y: auto)
    const scrollTop = listContainerRef.value?.scrollTop || 0
    const windowScrollY = window.scrollY || window.pageYOffset
    sessionStorage.setItem(LISTINGS_SCROLL_KEY, JSON.stringify({ container: scrollTop, window: windowScrollY }))
    sessionStorage.setItem(LISTINGS_PAGE_KEY, currentPage.value.toString())
  }
  next()
})

// Przywróć pozycję scrolla po powrocie na stronę (keep-alive)
onActivated(() => {
  const savedScroll = sessionStorage.getItem(LISTINGS_SCROLL_KEY)
  const savedPage = sessionStorage.getItem(LISTINGS_PAGE_KEY)
  
  if (savedPage) {
    const page = parseInt(savedPage, 10)
    if (page && page !== currentPage.value) {
      searchStore.setCurrentPage(page)
    }
  }
  
  if (savedScroll) {
    try {
      const { container, window: windowY } = JSON.parse(savedScroll)
      // Użyj setTimeout zamiast nextTick, aby uniknąć wyścigu z scrollBehavior routera
      setTimeout(() => {
        // Przywróć scroll kontenera listy
        if (listContainerRef.value && container) {
          listContainerRef.value.scrollTop = container
        }
        // Przywróć scroll okna (na mobile lista jest w normalnym flow)
        if (windowY) {
          window.scrollTo({ top: windowY, behavior: 'instant' })
        }
        // Wyczyść zapisaną pozycję po przywróceniu
        sessionStorage.removeItem(LISTINGS_SCROLL_KEY)
        sessionStorage.removeItem(LISTINGS_PAGE_KEY)
      }, 50)
    } catch (e) {
      sessionStorage.removeItem(LISTINGS_SCROLL_KEY)
      sessionStorage.removeItem(LISTINGS_PAGE_KEY)
    }
  }
  
  // Odśwież mapę po powrocie (keep-alive może powodować problemy z rozmiarem)
  if (map) {
    setTimeout(() => {
      map?.invalidateSize()
    }, 100)
  }
})
const regionCoordinates: Record<string, { lat: number; lng: number; zoom: number }> = {
  'dolnoslaskie': { lat: 51.1079, lng: 17.0385, zoom: 8 },
  'kujawsko-pomorskie': { lat: 53.1235, lng: 18.0084, zoom: 8 },
  'lubelskie': { lat: 51.2465, lng: 22.5684, zoom: 8 },
  'lubuskie': { lat: 52.2297, lng: 15.2365, zoom: 8 },
  'lodzkie': { lat: 51.7592, lng: 19.4560, zoom: 8 },
  'malopolskie': { lat: 49.85, lng: 20.2, zoom: 8 },
  'mazowieckie': { lat: 52.2297, lng: 21.0122, zoom: 8 },
  'opolskie': { lat: 50.6751, lng: 17.9213, zoom: 9 },
  'podkarpackie': { lat: 50.0412, lng: 21.9991, zoom: 8 },
  'podlaskie': { lat: 53.1325, lng: 23.1688, zoom: 8 },
  'pomorskie': { lat: 54.3520, lng: 18.6466, zoom: 8 },
  'slaskie': { lat: 50.2649, lng: 19.0238, zoom: 9 },
  'swietokrzyskie': { lat: 50.8661, lng: 20.6286, zoom: 9 },
  'warminsko-mazurskie': { lat: 53.7784, lng: 20.4801, zoom: 8 },
  'wielkopolskie': { lat: 52.4064, lng: 16.9252, zoom: 8 },
  'zachodniopomorskie': { lat: 53.4285, lng: 14.5528, zoom: 8 }
}


const scrollListToTop = () => {
  listContainerRef.value?.scrollTo({ top: 0, behavior: 'smooth' })
}

// Leaflet
let L: typeof LType | null = null
const loadLeaflet = async () => {
  if (L) return L
  const LModule = await import('leaflet')
  L = LModule.default || LModule
  await import('leaflet/dist/leaflet.css')
  // @ts-ignore
  // await import('leaflet.markercluster')
  // await import('leaflet.markercluster/dist/MarkerCluster.css')
  // await import('leaflet.markercluster/dist/MarkerCluster.Default.css')
  return L
}

// Helpers from store
const formatLocation = (loc: string, city: string) => searchStore.formatLocation(loc, city)
const getTypeLabel = (type: string) => searchStore.getTypeLabel(type)



const breadcrumbs = computed(() => {
  const items = [{ label: 'Strona główna', path: '/' }, { label: 'Powierzchnie reklamowe' }]
  if (route.params.type) {
    items[items.length - 1].path = '/powierzchnie-reklamowe'
    items.push({ label: getTypeLabel(route.params.type as string) })
  }
  if (route.params.city) {
    items[items.length - 1].path = route.params.type ? `/powierzchnie-reklamowe/${route.params.type}` : '/powierzchnie-reklamowe'
    items.push({ label: deslugify(route.params.city as string) })
  }
  return items
})

const currentDescription = computed(() => {
  const city = route.params.city as string
  const type = route.params.type as string
  return (city && cityDescriptions[city]) || (type && categoryDescriptions[type]) || categoryDescriptions['']
})

const seoInfo = computed(() => {
  const type = route.params.type as string
  const city = route.params.city as string
  let title = 'Powierzchnie Reklamowe w Polsce'
  let description = 'Billboardy, citylighty i inne.'
  if (type && city) {
    const tl = getTypeLabel(type); const cn = deslugify(city)
    title = `${tl} ${cn} - Wynajem | ReklaMap`; description = `Wynajmij ${tl.toLowerCase()} w ${cn}. ${listings.value.length} ofert.`
  } else if (type) {
    const tl = getTypeLabel(type)
    title = `${tl} - Wynajem w Polsce | ReklaMap`; description = `Promuj się na ${tl.toLowerCase()}. ${listings.value.length} ofert.`
  } else if (city) {
    const cn = deslugify(city)
    title = `Powierzchnie Reklamowe ${cn} | ReklaMap`; description = `Oferty z ${cn}. ${listings.value.length} ofert.`
  }
  return { title, description, keywords: 'powierzchnie reklamowe' }
})

// Location suggestions
const locationQuery = ref('')
const tempLocationQuery = ref('')
const isLocationMenuOpen = ref(false)
const apiLocationResults = ref<LocationResult[]>([])
const isLoadingLocations = ref(false)

// popularLocations imported from store

const locationSuggestions = computed(() => {
  const query = (showFiltersModal.value ? tempLocationQuery.value : locationQuery.value).toLowerCase()
  if (!query) return popularLocations
  
  const matchingRegions = polishLocations.voivodeships
    .filter((r: any) => r.name.toLowerCase().includes(query))
    .map((r: any) => ({ type: 'region' as const, value: r.id, label: r.name }))
  
  const apiSuggestions = searchStore.processLocationSuggestions(apiLocationResults.value)
  
  return [...matchingRegions, ...apiSuggestions].slice(0, 10)
})

const selectLocation = (suggestion: LocationSuggestion) => {
  const target = showFiltersModal.value ? tempFilters.value : filters.value
  const displayLabel = searchStore.selectLocationSuggestion(suggestion, target)
  
  if (showFiltersModal.value) {
    tempLocationQuery.value = displayLabel
  } else {
    locationQuery.value = displayLabel
  }
  
  isLocationMenuOpen.value = false
}

const handleLocationInput = () => {
  const query = showFiltersModal.value ? tempLocationQuery.value : locationQuery.value
  if (query.length >= 2) {
    isLoadingLocations.value = true
    debouncedSearchLocations(query, (res) => { apiLocationResults.value = res; isLoadingLocations.value = false })
  } else {
    apiLocationResults.value = []
    isLoadingLocations.value = false
  }
  const target = showFiltersModal.value ? tempFilters.value : filters.value
  if (target) {
    target.city = query;
    target.locationLabel = query;
    target.region = '';
    target.street = '';
    target.selectedLocationCoords = null;
    target.cityStrict = false
  }
}

const clearLocation = () => {
  const target = showFiltersModal.value ? tempFilters.value : filters.value
  if (showFiltersModal.value) tempLocationQuery.value = ''
  else locationQuery.value = ''
  
  if (target) {
    target.city = ''
    target.region = ''
    target.street = ''
    target.locationLabel = ''
    target.selectedLocationCoords = null
    target.cityStrict = false
  }
}

const handleLocationFocus = () => { isLocationMenuOpen.value = true }
const handleLocationBlur = () => { setTimeout(() => { isLocationMenuOpen.value = false }, 200) }

const syncLocationQuery = () => {
  if (filters.value.locationLabel) {
    locationQuery.value = filters.value.locationLabel
  } else if (filters.value.city) {
    locationQuery.value = filters.value.city
  } else if (filters.value.region) {
    const region = polishLocations.voivodeships.find(v => v.id === filters.value.region)
    if (region) locationQuery.value = region.name
  } else {
    locationQuery.value = ''
  }
}

watch([() => filters.value.city, () => filters.value.region], () => {
  syncLocationQuery()
}, { immediate: true })

const sortOptions = searchStore.sortOptions

const statusLabel = computed(() => {
  const currentFilters = tempFilters.value || filters.value
  if (!currentFilters.status || currentFilters.status.length === 0) return 'Wszystkie'
  const map: Record<string, string> = { active: 'Wolne', reserved: 'Zarezerwowane', soon_available: 'Wkrótce' }
  return currentFilters.status.map((s: string) => map[s] || s).join(', ')
})

// Computed properties for status checkboxes
const isStatusActive = computed({
  get: () => tempFilters.value?.status?.includes('active') || false,
  set: (val: boolean) => {
    if (!tempFilters.value) return
    if (val) {
      if (!tempFilters.value.status.includes('active')) {
        tempFilters.value.status = [...tempFilters.value.status, 'active']
      }
    } else {
      tempFilters.value.status = tempFilters.value.status.filter((s: string) => s !== 'active')
    }
  }
})

const isStatusReserved = computed({
  get: () => tempFilters.value?.status?.includes('reserved') || false,
  set: (val: boolean) => {
    if (!tempFilters.value) return
    if (val) {
      if (!tempFilters.value.status.includes('reserved')) {
        tempFilters.value.status = [...tempFilters.value.status, 'reserved']
      }
    } else {
      tempFilters.value.status = tempFilters.value.status.filter((s: string) => s !== 'reserved')
    }
  }
})

const isStatusSoon = computed({
  get: () => tempFilters.value?.status?.includes('soon_available') || false,
  set: (val: boolean) => {
    if (!tempFilters.value) return
    if (val) {
      if (!tempFilters.value.status.includes('soon_available')) {
        tempFilters.value.status = [...tempFilters.value.status, 'soon_available']
      }
    } else {
      tempFilters.value.status = tempFilters.value.status.filter((s: string) => s !== 'soon_available')
    }
  }
})

const getCurrentPageAds = () => paginatedListings.value

const transportScopeOptions = computed(() => {
  const v = tempFilters.value?.variant || filters.value.variant
  if (v === 'stop') return [{ value: 'internal', label: 'Wewnętrzna' }, { value: 'external', label: 'Zewnętrzna' }]
  return [{ value: 'internal', label: 'Wewnętrzna' }, { value: 'external', label: 'Zewnętrzna' }, { value: 'full_vehicle', label: 'Całopojazdowa' }]
})

const getVariantOptions = (type: string) => {
  const labels = variantLabels[type] || {}
  return Object.entries(labels).map(([value, label]) => ({ value, label: label as string }))
}


const getEnvironmentOptions = (type: string): { value: string, label: string }[] => {
  if (type === 'citylight') return [{ value: 'street', label: 'Ulica' }, { value: 'mall', label: 'Galeria' }, { value: 'station', label: 'Dworzec' }]
  return []
}

// Actions

const openFiltersModal = () => { tempFilters.value = JSON.parse(JSON.stringify(filters.value)); tempLocationQuery.value = locationQuery.value; showFiltersModal.value = true }
const closeFiltersModal = () => { showFiltersModal.value = false; tempFilters.value = null }
const applyFilters = () => { 
  // Wyczyść mapBounds przy aplikowaniu filtrów, aby mapa mogła przybliżyć się do miasta/regionu
  const filtersWithoutMapBounds = { ...tempFilters.value, mapBounds: null }
  
  // WAŻNE: Jeśli type lub city się zmieniły względem path params - przekieruj na nowy path
  const typeChanged = filtersWithoutMapBounds.type && filtersWithoutMapBounds.type !== pathParamsFilters.value.type
  const cityChanged = filtersWithoutMapBounds.city && filtersWithoutMapBounds.city !== pathParamsFilters.value.city
  
  if (typeChanged || cityChanged) {
    // Generuj nowy path na podstawie wybranych type/city
    let newPath = '/powierzchnie-reklamowe'
    
    // Dodaj type do path (jeśli jest)
    if (filtersWithoutMapBounds.type) {
      const typeSlug = mapTypeToUrlFormat(filtersWithoutMapBounds.type)
      newPath += '/' + typeSlug
    }
    
    // Dodaj city do path (jeśli jest)
    if (filtersWithoutMapBounds.city) {
      const citySlug = slugify(filtersWithoutMapBounds.city)
      newPath += '/' + citySlug
    }
    
    // Przygotuj pozostałe filtry jako query params (bez type/city)
    const otherFilters = { ...filtersWithoutMapBounds }
    delete otherFilters.type
    delete otherFilters.city
    delete otherFilters.cityStrict
    delete otherFilters.mapBounds
    
    const queryParams = filtersToQueryParams(otherFilters)
    const queryString = new URLSearchParams(queryParams).toString()
    const fullPath = queryString ? newPath + '?' + queryString : newPath
    
    // Zapisz filtry do localStorage PRZED przekierowaniem
    try {
      const filtersToSave = { ...filtersWithoutMapBounds }
      if ((filtersToSave.priceFrom !== null || filtersToSave.priceTo !== null) && filtersToSave.priceUnit) {
        filtersToSave._priceDisplayUnit = filtersToSave.priceUnit
      }
      localStorage.setItem('reklamap_last_search', JSON.stringify(filtersToSave))
      // Ustaw flagę user_initiated_search
      localStorage.setItem('user_initiated_search', 'true')
    } catch (error) {
      // Silently fail
    }
    
    // Przekieruj na nowy path
    router.push(fullPath)
    showFiltersModal.value = false
    return // Przerwij dalsze wykonanie
  }
  
  // Jeśli type/city NIE uległy zmianie - kontynuuj normalnie
  
  // Jeśli użytkownik wpisał cenę, ustaw priceDisplay na wybraną jednostkę
  if ((filtersWithoutMapBounds.priceFrom !== null || filtersWithoutMapBounds.priceTo !== null) && filtersWithoutMapBounds.priceUnit) {
    searchStore.priceDisplay = filtersWithoutMapBounds.priceUnit
  }
  
  // Apply filters (dimension conversion for LED screens happens in searchStore)
  searchStore.applyFilters(filtersWithoutMapBounds)
  locationQuery.value = tempLocationQuery.value
  showFiltersModal.value = false
  
  // Sprawdź czy użytkownik dodał jakiekolwiek filtry poza path params (type/city)
  const hasManualFilters = Object.entries(filtersWithoutMapBounds).some(([key, value]) => {
    // Pomiń path params i pola pomocnicze
    if (['type', 'city', 'cityStrict', 'mapBounds', '_priceDisplayUnit'].includes(key)) {
      return false
    }
    
    // Sprawdź czy pole ma wartość
    if (value === null || value === undefined || value === '' || value === false) {
      return false
    }
    
    // Dla tablic sprawdź czy nie są puste
    if (Array.isArray(value) && value.length === 0) {
      return false
    }
    
    return true
  })
  
  // Przygotuj filtry do URL (query params)
  // ZAWSZE usuń path params z query params (type/city są już w path)
  const filtersForUrl = { ...filtersWithoutMapBounds }
  if (pathParamsFilters.value.type && filtersForUrl.type === pathParamsFilters.value.type) {
    filtersForUrl.type = ''
  }
  if (pathParamsFilters.value.city && filtersForUrl.city === pathParamsFilters.value.city) {
    filtersForUrl.city = ''
    filtersForUrl.cityStrict = false
  }
  
  // Zaktualizuj URL z query params (bez path params)
  const queryParams = filtersToQueryParams(filtersForUrl)
  
  // Zapisz flagę w localStorage jeśli użytkownik dodał ręczne filtry
  if (hasManualFilters) {
    try {
      localStorage.setItem('user_initiated_search', 'true')
    } catch (e) { /* ignore */ }
  }
  
  // Don't add page=1 to URL (it's the default)
  // Only add page if > 1
  if (currentPage.value > 1) {
    queryParams.page = currentPage.value.toString()
  }
  const queryString = new URLSearchParams(queryParams).toString()
  const newUrl = queryString ? window.location.pathname + '?' + queryString : window.location.pathname
  window.history.replaceState({}, document.title, newUrl)
  
  // Zapisz do localStorage
  // Jeśli użytkownik DODAŁ ręczne filtry, zapisz WSZYSTKO (type + city + ręczne filtry)
  // Jeśli NIE dodał ręcznych filtrów, wyczyść localStorage
  try {
    let filtersToSave
    if (hasManualFilters) {
      // Zapisz wszystko - użytkownik chce te filtry na HomePage
      filtersToSave = { ...filtersWithoutMapBounds }
      
      // WAŻNE: Jeśli type/city są puste stringi ale są w path params, użyj path params
      if ((!filtersToSave.type || filtersToSave.type === '') && pathParamsFilters.value.type) {
        filtersToSave.type = pathParamsFilters.value.type
      }
      if ((!filtersToSave.city || filtersToSave.city === '') && pathParamsFilters.value.city) {
        filtersToSave.city = pathParamsFilters.value.city
        filtersToSave.cityStrict = true
      }
    } else {
      // Nie zapisuj nic - użytkownik tylko przegląda kategorię bez filtrów
      filtersToSave = {}
    }
    
    // Dodaj _priceDisplayUnit jeśli użytkownik wpisał cenę
    if ((filtersToSave.priceFrom !== null || filtersToSave.priceTo !== null) && filtersToSave.priceUnit) {
      filtersToSave._priceDisplayUnit = filtersToSave.priceUnit
    }
    
    if (Object.keys(filtersToSave).length > 0) {
      localStorage.setItem('reklamap_last_search', JSON.stringify(filtersToSave))
    } else {
      // Jeśli nie ma żadnych filtrów, wyczyść localStorage
      localStorage.removeItem('reklamap_last_search')
    }
  } catch (error) {
    // Silently fail
  }
}
const resetFilters = () => { 
  searchStore.resetFilters()
  locationQuery.value = ''
  showFiltersModal.value = false
  
  // Wyczyść URL
  const newUrl = window.location.pathname
  window.history.replaceState({}, document.title, newUrl)
  
  // searchStore.resetFilters() automatycznie czyści localStorage i flagę user_initiated_search
}
const clearFilters = resetFilters

const handleSortOptionClick = (val: string) => { searchStore.sortBy = val; showSortPanel.value = false; searchStore.applyFilters({}) }
const toggleMobileMap = () => { 
  showMapOnMobile.value = !showMapOnMobile.value
  if (showMapOnMobile.value) nextTick(() => initMap())
  handleScroll() // Update scroll button visibility immediately
}

const scrollToAd = (adId: string) => {
  const element = document.getElementById(`ad-${adId}`)
  const container = document.querySelector('.listings-list-container')
  if (element && container) {
    const containerRect = container.getBoundingClientRect()
    const elementRect = element.getBoundingClientRect()
    container.scrollBy({ top: (elementRect.top - containerRect.top) - (containerRect.height / 2) + (elementRect.height / 2), behavior: 'smooth' })
  }
}

const handleAdHover = (adId: string | null) => {
  hoveredAdId.value = adId
  if (adId && markers.has(adId)) {
    const ad = listings.value.find(a => a.id === adId)
    if (ad) {
      const marker = markers.get(adId)!
      const icon = createCustomIcon(ad.type, true, selectedAdId.value === adId)
      if (icon) marker.setIcon(icon)
    }
  }
  markers.forEach((marker, id) => {
    if (id !== adId) {
      const ad = listings.value.find(a => a.id === id)
      if (ad) {
        const icon = createCustomIcon(ad.type, false, selectedAdId.value === id)
        if (icon) marker.setIcon(icon)
      }
    }
  })
}

const handleAdLeave = () => handleAdHover(null)
const handleSortButtonClick = () => { showSortPanel.value = true }
const changeViewMode = (mode: 'grid' | 'list') => { searchStore.setViewMode(mode) }

// Favorites and Comparison handlers
const handleToggleFavorite = async (id: string) => {
  await prefStore.toggleFavorite(id)
}

const handleToggleComparison = async (id: string) => {
  await prefStore.toggleComparison(id)
}

// Map Logic
const createCustomIcon = (type: string, hovered: boolean = false, selected: boolean = false) => {
  if (!L || !L.divIcon) return null
  const color = typeColors[type] || '#6B7280'
  const isHovered = hovered || selected
  const scale = isHovered ? 1.3 : 1
  const zIndex = isHovered ? 1000 : 500
  
  return L.divIcon({
    className: 'custom-marker',
    html: `
      <div style="
        background: ${color};
        width: ${32 * scale}px;
        height: ${32 * scale}px;
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        border: 3px solid white;
        box-shadow: 0 ${3 * scale}px ${10 * scale}px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        z-index: ${zIndex};
      ">
        <div style="
          width: ${12 * scale}px;
          height: ${12 * scale}px;
          background: white;
          border-radius: 50%;
          transform: rotate(45deg);
        "></div>
      </div>
    `,
    iconSize: [32 * scale, 32 * scale],
    iconAnchor: [16 * scale, 32 * scale],
    popupAnchor: [0, -32 * scale]
  })
}

const updateMarkers = () => {
  if (!map) return
  
  // Clear existing markers
  markers.forEach(marker => map?.removeLayer(marker))
  markers.clear()

  filteredListings.value.forEach(ad => {
    const icon = createCustomIcon(ad.type, hoveredAdId.value === ad.id, selectedAdId.value === ad.id)
    if (!icon) return
    const marker = L!.marker([ad.latitude, ad.longitude], { icon })
    const citySlug = slugify(ad.city)
    const titleSlug = slugify(ad.title)
    const typeSlug = mapTypeToUrlFormat(ad.type)
    const adUrl = `/powierzchnia-reklamowa/${typeSlug}/${citySlug}/${titleSlug}-${ad.id}`

    const imageUrl = ad.image_url ? getFullImageUrl(ad.image_url) : ''
    
    const displayUnit = searchStore.computedPriceDisplayUnit || ad.price_unit || 'month'
    const displayPrice = searchStore.getPrice(ad, displayUnit as any)
    const isEstimated = displayUnit !== (ad.price_unit || 'month')
    
    const popupContent = `
      <div style="width: 220px;">
        <a href="${adUrl}" style="text-decoration: none; color: inherit; display: block; padding: 4px;">
          ${imageUrl ? `
            <div style="margin: -10px -10px 10px -10px; overflow: hidden; border-radius: 8px 8px 0 0;">
              <img src="${imageUrl}" alt="${ad.title}" style="width: 100%; height: 110px; object-fit: cover; display: block;" />
            </div>
          ` : ''}
          <h4 style="margin: 0 0 6px 0; font-size: 1rem; font-weight: 700; color: var(--text-main, #1F2937); line-height: 1.3;">
            ${ad.title}
          </h4>
          <div style="display: flex; flex-direction: column; gap: 4px; font-size: 0.85rem;">
            <div style="color: var(--text-muted, #6B7280); display: flex; align-items: flex-start; gap: 4px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-top: 2px; flex-shrink: 0;">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                <circle cx="12" cy="10" r="3" />
              </svg>
              <span>${formatLocation(ad.location, ad.city)}</span>
            </div>
            <div style="font-weight: 800; color: #667eea; font-size: 1rem; margin-top: 4px; display: flex; align-items: baseline; gap: 4px;">
              ${isEstimated ? '<span style="color: #F59E0B; font-weight: bold; margin-right: 2px;">~</span>' : ''}
              <span>${Math.round(displayPrice).toLocaleString('pl-PL')} PLN</span>
              <span style="font-size: 0.75rem; font-weight: 500; color: var(--text-muted);">
                ${searchStore.getPriceLabel(displayUnit as any, ad)}
                ${isEstimated ? '<span style="color: #F59E0B; margin-left: 2px;">(szac.)</span>' : ''}
              </span>
            </div>
          </div>
        </a>
      </div>
    `

    marker.bindPopup(popupContent, { 
      maxWidth: 220,
      maxHeight: 250,
      autoPan: true,
      autoPanPadding: [10, 10]
    })
    marker.on('click', () => { selectedAdId.value = ad.id; scrollToAd(ad.id) })
    marker.on('mouseover', () => { 
      if (!isMobile.value) {
        const i = createCustomIcon(ad.type, true, selectedAdId.value === ad.id); 
        if (i) marker.setIcon(i) 
      }
    })
    marker.on('mouseout', () => { 
      if (!isMobile.value && hoveredAdId.value !== ad.id) { 
        const i = createCustomIcon(ad.type, false, selectedAdId.value === ad.id); 
        if (i) marker.setIcon(i) 
      }
    })
    
    marker.addTo(map!)
    markers.set(ad.id, marker)
  })
}

const initMap = async () => {
  if (!mapContainer.value) return
  await loadLeaflet(); if (!L) return

  if (map) {
    map.invalidateSize()
    return
  }

  const pCenter: [number, number] = [52.0, 19.0]; const pBounds = L.latLngBounds([45.5, 10.0], [58.5, 28.0])
  isProgrammaticMove.value = true
  map = L.map(mapContainer.value, { scrollWheelZoom: false, maxBounds: pBounds, minZoom: 5 }).setView(pCenter, 6)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map)
  
  // markerClusterGroup = (L as any).markerClusterGroup({
  //   showCoverageOnHover: false,
  //   spiderfyOnMaxZoom: true,
  //   zoomToBoundsOnClick: true,
  //   maxClusterRadius: 50
  // })
  // map.addLayer(markerClusterGroup)

  // Enable scroll wheel zoom on click
  map.on('click', () => {
    if (map && !map.scrollWheelZoom.enabled()) {
      map.scrollWheelZoom.enable()
      isMapActive.value = true
    }
  })

  // Disable scroll wheel zoom when mouse leaves (desktop only)
  if (mapContainer.value && !isMobile.value) {
    mapContainer.value.addEventListener('mouseleave', () => {
      if (map && map.scrollWheelZoom.enabled()) {
        map.scrollWheelZoom.disable()
        isMapActive.value = false
      }
    })
  }

  updateMarkers()
  syncMapToFilters()

  if (window.ResizeObserver && mapContainer.value) {
    resizeObserver = new ResizeObserver(() => {
      if (map) map.invalidateSize()
    })
    resizeObserver.observe(mapContainer.value)
  }

  const handleUserMapInteraction = () => {
    if (!map) return
    
    // Skip updating mapBounds if this was a programmatic move (e.g., zoom to city)
    if (isProgrammaticMove.value) {
      // Don't reset to false yet - some programmatic moves trigger multiple events
      return
    }
    
    // Skip updating mapBounds if map is hidden on mobile
    if (isMobile.value && !showMapOnMobile.value) {
      return
    }
    
    const bounds = map.getBounds()
    
    // Update map bounds to filter results, but keep text filter values intact
    const updates: any = {
      mapBounds: {
        northEast: { lat: bounds.getNorthEast().lat, lng: bounds.getNorthEast().lng },
        southWest: { lat: bounds.getSouthWest().lat, lng: bounds.getSouthWest().lng }
      }
    }

    searchStore.applyFilters(updates)
  }

  // Use moveend which is safer and fires once after any movement/zoom
  map.on('moveend', () => {
    if (isProgrammaticMove.value) {
      // Small timeout to allow all programmatic events to settle
      setTimeout(() => {
        isProgrammaticMove.value = false
      }, 100)
    } else {
      handleUserMapInteraction()
    }
  })
}

const syncMapToFilters = () => {
  if (!map || !L) return
  
  if (filters.value.selectedLocationCoords) {
    // Priority 1: If exact coordinates are provided, zoom to them
    const zoomLevel = isMobile.value ? 12 : 13
    isProgrammaticMove.value = true
    map.setView([filters.value.selectedLocationCoords.lat, filters.value.selectedLocationCoords.lng], zoomLevel)
  } else if (filters.value.city && markers.size > 0) {
    // Priority 2: If city is selected, fit bounds to markers (likely clustered in that city)
    const bounds = new (L as any).FeatureGroup(Array.from(markers.values())).getBounds()
    if (bounds.isValid()) {
      const maxZoom = isMobile.value ? 11 : 12
      isProgrammaticMove.value = true
      map.fitBounds(bounds, { padding: [50, 50], maxZoom })
    }
  } else if (filters.value.region && regionCoordinates[filters.value.region]) {
    // Priority 3: If region is selected (and no city), zoom to region center
    const region = regionCoordinates[filters.value.region]
    const zoomLevel = isMobile.value ? region.zoom - 1 : region.zoom
    isProgrammaticMove.value = true
    map.setView([region.lat, region.lng], zoomLevel)
  } else if (!searchStore.filters.mapBounds) {
    // Default: Always show full Poland ONLY when no mapBounds are set
    const defaultZoom = isMobile.value ? 5 : 6
    isProgrammaticMove.value = true
    map.setView([52.0, 19.0], defaultZoom)
  }
}

watch(() => filters.value.selectedLocationCoords, () => {
  // Only sync map if mapBounds is not active (user hasn't scrolled/zoomed manually)
  if (!searchStore.filters.mapBounds) {
    syncMapToFilters()
  }
}, { deep: true })

watch(() => filters.value.city, () => {
  // Only sync map if mapBounds is not active (user hasn't scrolled/zoomed manually)
  if (!searchStore.filters.mapBounds) {
    syncMapToFilters()
  }
})

watch(() => filters.value.region, () => {
  // Only sync map if mapBounds is not active (user hasn't scrolled/zoomed manually)
  if (!searchStore.filters.mapBounds) {
    syncMapToFilters()
  }
})

// Watch for mapBounds being cleared (e.g., when user clicks "Apply filters")
watch(() => searchStore.filters.mapBounds, (newBounds, oldBounds) => {
  // If mapBounds was cleared (null) and we have location filters, zoom to them
  if (!newBounds && oldBounds && (filters.value.city || filters.value.region || filters.value.selectedLocationCoords)) {
    syncMapToFilters()
  }
})

watch(filteredListings, () => {
  updateMarkers()
  // If we have a location filter but haven't zoomed/panned manually, ensure map view matches
  if ((filters.value.city || filters.value.region || filters.value.selectedLocationCoords) && !searchStore.filters.mapBounds) {
    syncMapToFilters()
  }
}, { deep: true })

// Wyczyść filtry specyficzne dla typu gdy typ się zmieni w modalu
watch(() => tempFilters.value?.type, (newType, oldType) => {
  // Nie rób nic jeśli tempFilters nie istnieje lub typ się nie zmienił
  if (!tempFilters.value || !oldType || newType === oldType) return
  
  // Filtry ogólne które zachowujemy
  const generalFilters = {
    type: tempFilters.value.type,
    keyword: tempFilters.value.keyword,
    city: tempFilters.value.city,
    region: tempFilters.value.region,
    street: tempFilters.value.street,
    locationLabel: tempFilters.value.locationLabel,
    selectedLocationCoords: tempFilters.value.selectedLocationCoords,
    cityStrict: tempFilters.value.cityStrict,
    priceFrom: tempFilters.value.priceFrom,
    priceTo: tempFilters.value.priceTo,
    priceUnit: tempFilters.value.priceUnit,
    status: tempFilters.value.status,
    onlyWithImage: tempFilters.value.onlyWithImage,
    hasVatInvoice: tempFilters.value.hasVatInvoice,
    priceIncludesPrint: tempFilters.value.priceIncludesPrint,
    priceIncludesMounting: tempFilters.value.priceIncludesMounting,
    graphicDesignHelp: tempFilters.value.graphicDesignHelp,
    hasBacklight: tempFilters.value.hasBacklight,
    hasLightingTypeBanner: tempFilters.value.hasLightingTypeBanner,
    hasLightingTypeBillboard: tempFilters.value.hasLightingTypeBillboard,
    ambientLightControl: tempFilters.value.ambientLightControl,
    mapBounds: tempFilters.value.mapBounds
  }
  
  // Resetuj wszystkie filtry specyficzne dla typu
  Object.assign(tempFilters.value, {
    ...generalFilters,
    widthFrom: null,
    widthTo: null,
    heightFrom: null,
    heightTo: null,
    surfaceFrom: null,
    surfaceTo: null,
    orientation: '',
    variant: '',
    trafficIntensity: '',
    trafficDirection: '',
    trafficType: '',
    roadClass: '',
    environment: '',
    transportScope: '',
    vehicleCountFrom: null,
    vehicleCountTo: null,
    mobileExposureMode: '',
    operatingHours: '',
    routeArea: '',
    estimatedDailyViewsFrom: null,
    estimatedDailyViewsTo: null,
    pixelPitchFrom: null,
    pixelPitchTo: null,
    brightnessFrom: null,
    brightnessTo: null,
    operatingZone: '',
    offerType: '',
    campaignDuration: null,
    rentalPeriod: ''
  })
})

const checkIfMobile = () => { isMobile.value = window.innerWidth < 768 }
const handleScroll = () => {
  const footer = document.querySelector('footer')
  const descriptionWrapper = document.querySelector('.description-wrapper')
  const contentWrapper = document.querySelector('.content-wrapper')
  
  // Check if bottom sections (description or footer) are visible
  // Added a 40px threshold to prevent it being "visible" when perfectly aligned at the bottom (scrollY=0)
  let isBottomSectionVisible = false
  
  if (descriptionWrapper) {
    const descRect = descriptionWrapper.getBoundingClientRect()
    if (descRect.top <= window.innerHeight - 40) {
      isBottomSectionVisible = true
    }
  }
  
  if (footer && !isBottomSectionVisible) {
    const footerRect = footer.getBoundingClientRect()
    if (footerRect.top <= window.innerHeight - 40) {
      isBottomSectionVisible = true
    }
  }
  
  // For mobile: hide map button when bottom sections visible
  if (isMobile.value) {
    if (contentWrapper) {
      const contentRect = contentWrapper.getBoundingClientRect()
      const inContentSection = contentRect.bottom > 0
      showMapButton.value = inContentSection && !isBottomSectionVisible
    }
    // Na komórce też chcemy przycisk przewijania listy, ale tylko gdy nie widzimy mapy
    showListScrollTop.value = !showMapOnMobile.value && !isBottomSectionVisible && (listContainerRef.value?.scrollTop || 0) > 500
  } else {
    // For desktop: always show map button, but hide list scroll button when bottom sections visible
    showMapButton.value = true
    showListScrollTop.value = !isBottomSectionVisible && (listContainerRef.value?.scrollTop || 0) > 500
  }
}

const handleClickOutside = (e: MouseEvent) => {
  if (statusMultiselect.value && !statusMultiselect.value.contains(e.target as Node)) {
    isStatusMenuOpen.value = false
  }
}

const handleNumberInput = (val: string, allowFloat: boolean = true) => {
  let cleaned = val.replace(/[^0-9.,]/g, '').replace(',', '.')
  if (!allowFloat) cleaned = cleaned.replace(/\..*/, '')
  return cleaned
}

const getAvailablePriceUnits = (type: string) => searchStore.getAvailablePriceUnits(type)

const showEquipmentSectionInModal = computed(() => tempFilters.value && ['billboard', 'citylight', 'banner', 'wall', 'led_screen'].includes(tempFilters.value.type))

onMounted(async () => {
  // Pokaż skeleton od samego początku (szczególnie ważne przy przejściach SPA)
  searchStore.isLoading = true
  
  checkIfMobile()
  window.addEventListener('resize', checkIfMobile)
  window.addEventListener('scroll', handleScroll)
  handleScroll() // Ustaw stan początkowy
  if (!isMobile.value) { setTimeout(() => initMap(), 100) }
  document.addEventListener('click', handleClickOutside)
  
  try {
    // 1. Sprawdź czy type/city są w query params - jeśli tak, przekieruj na path params
    const queryType = route.query.type as string | undefined
    const queryCity = route.query.city as string | undefined
    const pathType = route.params.type as string | undefined
    const pathCity = route.params.city as string | undefined
    
    // Jeśli type lub city są w query params ale NIE w path params - przekieruj
    if ((queryType && !pathType) || (queryCity && !pathCity)) {
      let newPath = '/powierzchnie-reklamowe'
      
      const finalType = queryType || pathType
      if (finalType) {
        const typeSlug = mapTypeToUrlFormat(finalType)
        newPath += '/' + typeSlug
      }
      
      const finalCity = queryCity || pathCity
      if (finalCity) {
        const citySlug = slugify(finalCity)
        newPath += '/' + citySlug
      }
      
      const otherQuery = { ...route.query }
      delete otherQuery.type
      delete otherQuery.city
      
      const queryString = new URLSearchParams(otherQuery as any).toString()
      const fullPath = queryString ? newPath + '?' + queryString : newPath
      
      searchStore.syncFromUrl(route.query as Record<string, string>, route.params as Record<string, string>)
      
      router.replace(fullPath)
      return 
    }

    // 2. Sprawdź czy jest flaga user_initiated_search w localStorage
    const isUserSearch = localStorage.getItem('user_initiated_search') === 'true'
    
    // 3. Jeśli JEST flaga user_initiated_search → załaduj filtry z localStorage jako bazę
    if (isUserSearch) {
      const saved = localStorage.getItem('reklamap_last_search')
      if (saved) {
        const lastSearch = JSON.parse(saved)
        searchStore.applyFilters(lastSearch)
        
        if (lastSearch._priceDisplayUnit) {
          searchStore.priceDisplay = lastSearch._priceDisplayUnit
        }
      }
    }

    // 4. Nadpisz filtrami z URL
    searchStore.syncFromUrl(route.query as Record<string, string>, route.params as Record<string, string>)
    syncLocationQuery()

    // 5. Pobierz ogłoszenia (wymuszamy fetch mimo isLoading, żeby mieć pewność świeżych danych)
    await searchStore.fetchListings()
    
  } catch (error) {
    console.error('Error initializing listings page:', error)
  } finally {
    searchStore.isLoading = false
    isInitialized.value = true
  }

  // 6. Proactive search alert modal
  startAlertTimer()
})

// Przy powrocie do cache'owanej strony (keep-alive) wymuś synchronizację filtrów z URL
// onMounted nie odpala się ponownie dla cache'owanych instancji
onActivated(() => {
  // Guard: tylko dla tras ogłoszeń (keep-alive aktywuje wszystkie cache'owane instancje)
  if (!route.path.startsWith('/powierzchnie-reklamowe')) return

  // Synchronizuj filtry z aktualnym URL
  searchStore.syncFromUrl(route.query as Record<string, string>, route.params as Record<string, string>)
  syncLocationQuery()

  // Upewnij się, że dane są w sklepie (np. po bezpośrednim wejściu na link)
  if (listings.value.length === 0) {
    searchStore.fetchListings()
  }
})

const startAlertTimer = () => {
  if (hasShownAlertModal.value) return
  
  if (alertModalTimer.value) {
    clearTimeout(alertModalTimer.value)
  }

  alertModalTimer.value = setTimeout(() => {
    // Show only if user has type selected, hasn't seen it yet, AND is still on ListingsPage
    const hasType = !!(filters.value.type || pathParamsFilters.value.type)
    if (!hasShownAlertModal.value && hasType && router.currentRoute.value.path.includes('/powierzchnie-reklamowe')) {
      showSearchAlertModal.value = true
      hasShownAlertModal.value = true
      localStorage.setItem('search_alert_shown', 'true')
    }
  }, 20000) as unknown as number // 20 seconds
}

// Add watch for route changes to handle navigation between categories/locations
watch(
  () => [route.params, route.query],
  () => {
    // Guard: watcher jest aktywny przez keep-alive nawet poza stroną ogłoszeń - ignoruj inne strony
    if (!route.path.startsWith('/powierzchnie-reklamowe')) return

    searchStore.syncFromUrl(route.query as Record<string, string>, route.params as Record<string, string>)
    syncLocationQuery()
    
    // Pobierz ogłoszenia jeśli ich nie ma (np. po odświeżeniu specyficznego URL)
    if (listings.value.length === 0) {
      searchStore.fetchListings()
    }

    // Clear and restart alert timer when user navigates between categories or changes filters
    startAlertTimer()
    
    // Scroll to top of the listings when category/location changes
    nextTick(() => {
      scrollListToTop()
    })
  },
  { deep: true }
)

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('resize', checkIfMobile)
  window.removeEventListener('scroll', handleScroll)
  document.body.style.overflow = ''
  if (resizeObserver) {
    resizeObserver.disconnect()
  }
})

const handleSearchAlertSubmit = () => { /* Alert logic */ }
</script>

<template>
  <div>
    <div class="listings-page">
    <!-- SEO Breadcrumbs -->
    <Breadcrumbs :items="breadcrumbs" />

    <h1 class="listings-title sr-only">{{ seoInfo.title.split(' | ')[0] }}</h1>
    
    <!-- Search and Filters Bar -->
    <div class="search-bar">
      <!-- Desktop View -->
      <div class="desktop-search">
        <div class="search-container">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
            <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <input 
            v-model="filters.keyword" 
            type="text" 
            placeholder="Szukaj po tytule..."
            class="search-input"
          />
        </div>
        
        <button @click="openFiltersModal" class="filters-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
          </svg>
          <span>Filtruj</span>
          <span v-if="totalFiltersCount > 0" class="filter-badge">{{ totalFiltersCount }}</span>
        </button>

        <select v-model="sortBy" class="sort-select">
          <option value="newest">Najnowsze</option>
          <option value="oldest">Najstarsze</option>
          <option value="name-asc">Nazwa A-Z</option>
          <option value="name-desc">Nazwa Z-A</option>
          <optgroup label="Cena za dzień">
            <option value="price-day-asc">Cena za dzień rosnąco</option>
            <option value="price-day-desc">Cena za dzień malejąco</option>
          </optgroup>
          <optgroup label="Cena za tydzień">
            <option value="price-week-asc">Cena za tydzień rosnąco</option>
            <option value="price-week-desc">Cena za tydzień malejąco</option>
          </optgroup>
          <optgroup label="Cena za miesiąc">
            <option value="price-month-asc">Cena za miesiąc rosnąco</option>
            <option value="price-month-desc">Cena za miesiąc malejąco</option>
          </optgroup>
          <optgroup label="Cena za rok">
            <option value="price-year-asc">Cena za rok rosnąco</option>
            <option value="price-year-desc">Cena za rok malejąco</option>
          </optgroup>
          <optgroup label="Cena za m²">
            <option value="price-sqm-asc">Cena za m² rosnąco</option>
            <option value="price-sqm-desc">Cena za m² malejąco</option>
          </optgroup>
          <optgroup label="Cena za kampanię">
            <option value="price-campaign-asc">Cena za kampanię rosnąco</option>
            <option value="price-campaign-desc">Cena za kampanię malejąco</option>
          </optgroup>
        </select>

        <div class="view-toggle">
          <button 
            @click="changeViewMode('grid')" 
            class="view-btn"
            :class="{ active: viewMode === 'grid' }"
            title="Widok kafelków"
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
              <rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
              <rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
              <rect x="14" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
            </svg>
          </button>
          <button 
            @click="changeViewMode('list')" 
            class="view-btn"
            :class="{ active: viewMode === 'list' }"
            title="Widok listy"
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <rect x="3" y="5" width="18" height="4" rx="1" stroke="currentColor" stroke-width="2"/>
              <rect x="3" y="11" width="18" height="4" rx="1" stroke="currentColor" stroke-width="2"/>
              <rect x="3" y="17" width="18" height="4" rx="1" stroke="currentColor" stroke-width="2"/>
            </svg>
          </button>
        </div>

      </div>

      <!-- Mobile View -->
      <div class="mobile-search">
        <div class="search-container">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
            <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <input 
            v-model="filters.keyword" 
            type="text" 
            placeholder="Szukaj..."
            class="search-input"
          />
        </div>
        
        <button
          @click.stop="handleSortButtonClick"
          class="mobile-action-btn"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18"/>
            <path d="M6 12h12"/>
            <path d="M10 18h4"/>
          </svg>
          <span>Sortuj</span>
        </button>
        
        <button @click.stop="openFiltersModal" class="mobile-action-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
          </svg>
          <span>Filtruj</span>
          <span v-if="totalFiltersCount > 0" class="mobile-filter-badge">{{ totalFiltersCount }}</span>
        </button>
      </div>

      <div class="results-count">
        {{ filteredListings.length }} ogłoszeń
      </div>
      
      <!-- Sort Panel -->
      <Teleport to="body">
        <div class="overlay" v-show="showSortPanel" @click="showSortPanel = false"></div>
        <div ref="sortPanelRef" class="sort-panel" :class="{ 'is-open': showSortPanel }">
          <div class="sort-panel-header">
            <h3 class="sort-panel-title">Sortuj według</h3>
            <button @click="showSortPanel = false" class="sort-panel-close" aria-label="Zamknij">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          
          <div class="sort-options">
            <button 
              v-for="option in sortOptions" 
              :key="option.value"
              @click.stop="handleSortOptionClick(option.value)"
              class="sort-option"
              :class="{ active: sortBy === option.value }"
            >
              <span class="option-label">{{ option.label }}</span>
              <span class="option-desc">{{ option.description }}</span>
            </button>
          </div>
        </div>
      </Teleport>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper">
      <!-- List Sidebar -->
      <div 
        ref="listContainerRef" 
        class="listings-list-container" 
        :class="{ 'hidden-mobile': showMapOnMobile }"
        @scroll="handleScroll"
      >
        <div v-if="isLoading" class="listings-list" :class="viewMode">
          <SkeletonCard v-for="i in itemsPerPage" :key="i" />
        </div>

        <div v-else-if="filteredListings.length === 0" class="empty-state">
          <svg width="80" height="80" viewBox="0 0 24 24" fill="none">
            <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
            <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
            <path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2"/>
          </svg>
          <h3>Brak ogłoszeń</h3>
          <p>Nie znaleziono ogłoszeń pasujących do wyszukiwania</p>
          
          <SearchAlertBox 
            v-if="totalFiltersCount > 0"
            :location-label="filters?.city || (route.params.city ? deslugify(route.params.city as string) : '')"
            :ad-type-label="filters?.type ? getTypeLabel(filters.type) : 'ogłoszenie'"
            @click="showSearchAlertModal = true"
          />
        </div>

        <template v-else>
          <div class="listings-list" :class="viewMode">
            <AdCard 
              v-for="ad in getCurrentPageAds()"
              :key="ad.id"
              :ad="ad"
              :view-mode="viewMode"
              :price-display="searchStore.computedPriceDisplayUnit"
              @toggle-favorite="handleToggleFavorite"
              @toggle-comparison="handleToggleComparison"
              @hover-start="handleAdHover"
              @hover-end="handleAdLeave"
            />
          </div>
          
          <Pagination 
            v-if="!isLoading && filteredListings.length > 0"
            :current-page="currentPage"
            :total-pages="totalPages"
            :total-items="filteredListings.length"
            :items-per-page="itemsPerPage"
            @update:current-page="searchStore.setCurrentPage($event); scrollListToTop()"
          />
          
          <SearchAlertBox 
            v-if="!isLoading && listings.length > 0 && totalFiltersCount > 0" 
            class="listings-alert" 
            :location-label="filters?.city || (route.params.city ? deslugify(route.params.city as string) : '')"
            :ad-type-label="filters?.type ? getTypeLabel(filters.type) : 'ogłoszenie'"
            @click="showSearchAlertModal = true"
          />
        </template>

        <!-- List Scroll to Top Button -->
        <Transition name="fade">
          <button 
            v-if="showListScrollTop" 
            @click="scrollListToTop" 
            class="list-scroll-top"
          >
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 15l-6-6-6 6"/>
            </svg>
          </button>
        </Transition>
      </div>

      <!-- Mobile toggle button -->
      <button v-if="isMobile && showMapButton" @click="toggleMobileMap" class="mobile-map-toggle">
        <span>{{ showMapOnMobile ? 'Pokaż listę' : 'Pokaż mapę' }}</span>
        <svg v-if="!showMapOnMobile" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
          <circle cx="12" cy="10" r="3"></circle>
        </svg>
        <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="3" y1="9" x2="21" y2="9"></line>
          <line x1="9" y1="21" x2="9" y2="9"></line>
        </svg>
      </button>

      <!-- Map Container -->
      <div class="map-container-wrapper" :class="{ 'mobile-visible': showMapOnMobile, 'mobile-hidden': isMobile && !showMapOnMobile }">
        <div ref="mapContainer" class="map-container">
          <!-- Map hint overlay -->
          <div v-if="!isMapActive" class="map-hint-overlay">
            <div class="map-hint-message">
              Kliknij, aby móc przybliżyć mapę
            </div>
          </div>
          
          <!-- Legend Toggle Button -->
          <button 
            class="legend-toggle-button"
            @click="isLegendVisible = !isLegendVisible"
            :aria-expanded="isLegendVisible"
            :class="{ 'is-active': isLegendVisible }"
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M3 12h18M3 6h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Legenda</span>
          </button>
        </div>
        
        <!-- Legend Overlay/Panel here if needed, but we used the one in PolandMap or local? -->
        <!-- The original code had local legend here -->
        <div class="map-legend" :class="{ 'is-visible': isLegendVisible }">
          <div class="legend-header">
            <h3 class="legend-title">Legenda</h3>
            <button class="close-legend" @click="isLegendVisible = false">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>
          <div class="legend-items">
            <div v-for="(color, type) in typeColors" :key="type" class="legend-item">
              <div class="legend-marker" :style="{ background: color }"></div>
              <span class="legend-label">{{ typeLabels[type] || type }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>

    <!-- Filters Modal -->
    <Teleport to="body">
      <div v-if="showFiltersModal" class="modal-overlay" @click.self="closeFiltersModal">
        <div class="modal-content">
        <div class="modal-header">
          <h2>Filtry</h2>
          <button @click="closeFiltersModal" class="close-btn">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
              <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <!-- SEKCJA: Podstawowe filtry -->
          <div class="filter-section">
            <h4 class="section-title">Podstawowe</h4>
            
            <!-- Type Filter -->
            <div class="filter-group">
              <label class="filter-label">Typ powierzchni</label>
              <select v-model="tempFilters.type" class="filter-select" v-if="tempFilters">
                <option value="">Wszystkie typy</option>
                <option value="billboard">Billboardy</option>
                <option value="citylight">Citylighty</option>
                <option value="led_screen">Ekrany LED</option>
                <option value="banner">Banery</option>
                <option value="wall">Ściany reklamowe</option>
                <option value="totem">Totemy reklamowe</option>
                <option value="transport">Reklama w transporcie</option>
                <option value="mobile">Reklama mobilna</option>
                <option value="other">Inne</option>
              </select>
            </div>

            <!-- Location Filter -->
            <div class="filter-group">
              <label class="filter-label">Lokalizacja</label>
              <div class="location-autocomplete">
                <div class="input-with-clear">
                  <input
                    v-model="tempLocationQuery"
                    type="text"
                    placeholder="Wpisz region, miasto lub ulicę"
                    class="filter-input"
                    @focus="handleLocationFocus"
                    @blur="handleLocationBlur"
                    @input="handleLocationInput"
                    autocomplete="off"
                  />
                  <button 
                    v-if="tempLocationQuery" 
                    type="button" 
                    class="clear-button" 
                    @click.stop="clearLocation"
                    @mousedown.prevent
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path d="M18 6L6 18M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                </div>
                <div v-if="isLocationMenuOpen" class="location-suggestions">
                  <div v-if="isLoadingLocations" class="loading-state compact">
                    <div class="loading-spinner"></div>
                    <span>Szukam...</span>
                  </div>
                  <div v-else-if="!tempLocationQuery" class="suggestion-section">
                    <div class="suggestion-header">Popularne lokalizacje</div>
                    <div
                      v-for="suggestion in locationSuggestions"
                      :key="suggestion.value"
                      class="location-suggestion"
                      @click="selectLocation(suggestion)"
                    >
                      <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M8 8C8.82843 8 9.5 7.32843 9.5 6.5C9.5 5.67157 8.82843 5 8 5C7.17157 5 6.5 5.67157 6.5 6.5C6.5 7.32843 7.17157 8 8 8Z" stroke="#6B7280" stroke-width="1.2"/>
                        <path d="M8 14C8 14 12 10.5 12 6.5C12 4.01472 10.2091 2 8 2C5.79086 2 4 4.01472 4 6.5C4 10.5 8 14 8 14Z" stroke="#6B7280" stroke-width="1.2"/>
                      </svg>
                      {{ suggestion.label }}
                    </div>
                  </div>
                  <div v-else>
                    <div
                      v-for="suggestion in locationSuggestions"
                      :key="suggestion.value + suggestion.type"
                      class="location-suggestion"
                      @click="selectLocation(suggestion)"
                    >
                      <svg v-if="suggestion.type === 'region'" width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <rect x="2" y="2" width="12" height="12" rx="1.5" stroke="#6B7280" stroke-width="1.2"/>
                        <path d="M2 6H14M6 2V14" stroke="#6B7280" stroke-width="1.2"/>
                      </svg>
                      <svg v-else width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M8 8C8.82843 8 9.5 7.32843 9.5 6.5C9.5 5.67157 8.82843 5 8 5C7.17157 5 6.5 5.67157 6.5 6.5C6.5 7.32843 7.17157 8 8 8Z" stroke="#6B7280" stroke-width="1.2"/>
                        <path d="M8 14C8 14 12 10.5 12 6.5C12 4.01472 10.2091 2 8 2C5.79086 2 4 4.01472 4 6.5C4 10.5 8 14 8 14Z" stroke="#6B7280" stroke-width="1.2"/>
                      </svg>
                      <span class="suggestion-text">
                        <span class="suggestion-name">{{ suggestion.label }}</span>
                        <span v-if="suggestion.type === 'region'" class="suggestion-type">Województwo</span>
                        <span v-else-if="suggestion.subtitle" class="suggestion-type">{{ suggestion.subtitle }}</span>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Price Range with Unit -->
            <div class="filter-group">
              <label class="filter-label">Cena</label>
              <div class="price-filter-group">
                <div class="range-inputs">
                  <input 
                    :value="tempFilters?.priceFrom"
                    @input="(e) => { if (tempFilters) { const val = handleNumberInput((e.target as HTMLInputElement).value, true); tempFilters.priceFrom = val ? parseFloat(val) : null } }"
                    type="text" 
                    placeholder="Od"
                    class="filter-input"
                    v-if="tempFilters"
                  />
                  <span>-</span>
                  <input 
                    :value="tempFilters?.priceTo"
                    @input="(e) => { if (tempFilters) { const val = handleNumberInput((e.target as HTMLInputElement).value, true); tempFilters.priceTo = val ? parseFloat(val) : null } }"
                    type="text" 
                    placeholder="Do"
                    class="filter-input"
                    v-if="tempFilters"
                  />
                </div>
                <select v-model="tempFilters.priceUnit" class="filter-select price-unit-select" v-if="tempFilters">
                  <option v-for="unit in getAvailablePriceUnits(tempFilters.type)" :key="unit.value" :value="unit.value">
                    {{ unit.label }}
                  </option>
                </select>
              </div>
            </div>
          </div>

        <!-- SEKCJA: Wymiary i powierzchnia -->
        <div v-if="tempFilters && ['billboard', 'citylight', 'banner', 'wall', 'led_screen'].includes(tempFilters.type)" class="filter-section">
          <h4 class="section-title">Wymiary i powierzchnia</h4>
          <div class="filter-row">
            <div class="filter-group">
              <label class="filter-label">Szerokość ({{ tempFilters && tempFilters.type === 'led_screen' ? 'mm' : 'm' }})</label>
              <div class="range-inputs">
                <input 
                  :value="tempFilters?.widthFrom"
                  @input="(e) => { if (tempFilters) { const val = handleNumberInput((e.target as HTMLInputElement).value, tempFilters.type === 'led_screen' ? false : true); tempFilters.widthFrom = val ? parseFloat(val) : null } }"
                  type="text" 
                  placeholder="Od"
                  class="filter-input"
                />
                <span>-</span>
                <input 
                  :value="tempFilters?.widthTo"
                  @input="(e) => { if (tempFilters) { const val = handleNumberInput((e.target as HTMLInputElement).value, tempFilters.type === 'led_screen' ? false : true); tempFilters.widthTo = val ? parseFloat(val) : null } }"
                  type="text" 
                  placeholder="Do"
                  class="filter-input"
                />
              </div>
            </div>

            <div class="filter-group">
              <label class="filter-label">Wysokość ({{ tempFilters && tempFilters.type === 'led_screen' ? 'mm' : 'm' }})</label>
              <div class="range-inputs">
                <input 
                  :value="tempFilters?.heightFrom"
                  @input="(e) => { if (tempFilters) { const val = handleNumberInput((e.target as HTMLInputElement).value, tempFilters.type === 'led_screen' ? false : true); tempFilters.heightFrom = val ? parseFloat(val) : null } }"
                  type="text" 
                  placeholder="Od"
                  class="filter-input"
                />
                <span>-</span>
                <input 
                  :value="tempFilters?.heightTo"
                  @input="(e) => { if (tempFilters) { const val = handleNumberInput((e.target as HTMLInputElement).value, tempFilters.type === 'led_screen' ? false : true); tempFilters.heightTo = val ? parseFloat(val) : null } }"
                  type="text" 
                  placeholder="Do"
                  class="filter-input"
                />
              </div>
            </div>
          </div>

          <!-- Row 2: Orientation and Surface -->
          <div class="filter-row">
            <div class="filter-group">
              <label class="filter-label">Orientacja</label>
              <select v-model="tempFilters.orientation" class="filter-select" v-if="tempFilters">
                <option value="">Wszystkie</option>
                <option value="vertical">Pion</option>
                <option value="horizontal">Poziom</option>
              </select>
            </div>

            <div class="filter-group">
              <label class="filter-label">Powierzchnia (m²)</label>
              <div class="range-inputs">
                <input 
                  :value="tempFilters?.surfaceFrom"
                  @input="(e) => { if (tempFilters) { const val = handleNumberInput((e.target as HTMLInputElement).value, true); tempFilters.surfaceFrom = val ? parseFloat(val) : null } }"
                  type="text" 
                  placeholder="Od"
                  class="filter-input"
                />
                <span>-</span>
                <input 
                  :value="tempFilters?.surfaceTo"
                  @input="(e) => { if (tempFilters) { const val = handleNumberInput((e.target as HTMLInputElement).value, true); tempFilters.surfaceTo = val ? parseFloat(val) : null } }"
                  type="text" 
                  placeholder="Do"
                  class="filter-input"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Info message when no type selected -->
        <div v-if="tempFilters && !tempFilters.type" class="info-message">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="#3B82F6" stroke-width="2"/>
            <path d="M12 16v-4M12 8h.01" stroke="#3B82F6" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <span>Wybierz typ powierzchni, aby zobaczyć więcej filtrów specyficznych dla danego typu</span>
        </div>

        <!-- SEKCJA: Opcje specyficzne dla typu -->
        <div v-if="tempFilters.type" class="filter-section">
          <h4 class="section-title">Opcje specyficzne dla typu</h4>
          
          <!-- L1. Location Tier Filter (Billboard only) -->
          <div v-if="tempFilters && tempFilters.type === 'billboard'" class="filter-group">
            <label class="filter-label">Klasa lokalizacji</label>
            <select v-model="tempFilters.locationTier" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option value="PREMIUM">Premium</option>
              <option value="STANDARD">Standard</option>
            </select>
          </div>
          
          <!-- 1. Road Class Filter (Billboard only) -->
          <div v-if="tempFilters && tempFilters.type === 'billboard'" class="filter-group">
            <label class="filter-label">Klasa drogi</label>
            <select v-model="tempFilters.roadClass" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option value="highway">Autostrada</option>
              <option value="expressway">Droga ekspresowa</option>
              <option value="national">Droga krajowa</option>
              <option value="regional">Droga wojewódzka</option>
              <option value="local">Droga lokalna</option>
              <option value="urban">Droga miejska</option>
            </select>
          </div>



          <!-- 2. Traffic Intensity (all outdoor types) -->
          <div v-if="tempFilters && ['billboard', 'banner', 'wall', 'totem'].includes(tempFilters.type)" class="filter-group">
            <label class="filter-label">Natężenie ruchu</label>
            <select v-model="tempFilters.trafficIntensity" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option value="low">Niskie</option>
              <option value="medium">Średnie</option>
              <option value="high">Wysokie</option>
            </select>
          </div>

          <!-- 3. Kierunek ruchu (all outdoor types) -->
          <div v-if="tempFilters && ['billboard', 'banner', 'wall', 'totem'].includes(tempFilters.type)" class="filter-group">
            <label class="filter-label">Kierunek ruchu</label>
            <select v-model="tempFilters.trafficDirection" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option value="entry">Wjazd</option>
              <option value="exit">Wyjazd</option>
              <option value="both">Oba kierunki</option>
            </select>
          </div>

          <!-- 4. Rodzaj ruchu (all outdoor types) -->
          <div v-if="tempFilters && ['billboard', 'banner', 'wall', 'totem'].includes(tempFilters.type)" class="filter-group">
            <label class="filter-label">Rodzaj ruchu</label>
            <select v-model="tempFilters.trafficType" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option value="pedestrian">Pieszy</option>
              <option value="vehicular">Samochodowy</option>
              <option value="both">Oba rodzaje</option>
            </select>
          </div>

          <!-- 5. Variant Filter -->
          <div v-if="tempFilters && tempFilters.type && getVariantOptions(tempFilters.type).length > 0" class="filter-group">
            <label class="filter-label">Wariant</label>
            <select v-model="tempFilters.variant" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option v-for="variant in getVariantOptions(tempFilters.type)" :key="variant.value" :value="variant.value">
                {{ variant.label }}
              </option>
            </select>
          </div>

          <!-- 6. Environment Filter -->
          <div v-if="tempFilters && ['citylight', 'led_screen', 'totem', 'other'].includes(tempFilters.type)" class="filter-group">
            <label class="filter-label">Środowisko</label>
            <select v-model="tempFilters.environment" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option v-for="env in getEnvironmentOptions(tempFilters.type)" :key="env.value" :value="env.value">
                {{ env.label }}
              </option>
            </select>
          </div>

          <!-- 7. Billboard - Lighting Type -->
          <div v-if="tempFilters && tempFilters.type === 'billboard'" class="filter-group">
            <label class="filter-label">Typ oświetlenia</label>
            <select v-model="(tempFilters as any).lightingType" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option value="led">LED</option>
              <option value="fluorescent">Fluorescencyjne</option>
              <option value="natural">Naturalne</option>
              <option value="none">Brak</option>
            </select>
          </div>

          <!-- 8. LED Screen parametry -->
          <div v-if="tempFilters && tempFilters.type === 'led_screen'" class="filter-group">
            <label class="filter-label">Pixel Pitch (mm)</label>
            <div class="range-inputs">
              <input 
                :value="tempFilters?.pixelPitchFrom"
                @input="(e) => { if (tempFilters) { const val = handleNumberInput((e.target as HTMLInputElement).value, true); tempFilters.pixelPitchFrom = val ? parseFloat(val) : null } }"
                type="text" 
                placeholder="Od"
                class="filter-input"
              />
              <span>-</span>
              <input 
                :value="tempFilters?.pixelPitchTo"
                @input="(e) => { if (tempFilters) { const val = handleNumberInput((e.target as HTMLInputElement).value, true); tempFilters.pixelPitchTo = val ? parseFloat(val) : null } }"
                type="text" 
                placeholder="Do"
                class="filter-input"
              />
            </div>
          </div>
          <div v-if="tempFilters && tempFilters.type === 'led_screen'" class="filter-group">
            <label class="filter-label">Jasność (nits)</label>
            <div class="range-inputs">
              <input 
                :value="tempFilters?.brightnessFrom"
                @input="(e) => { if (tempFilters) { const val = handleNumberInput((e.target as HTMLInputElement).value, false); tempFilters.brightnessFrom = val ? parseInt(val) : null } }"
                type="text" 
                placeholder="Od"
                class="filter-input"
              />
              <span>-</span>
              <input 
                :value="tempFilters?.brightnessTo"
                @input="(e) => { if (tempFilters) { const val = handleNumberInput((e.target as HTMLInputElement).value, false); tempFilters.brightnessTo = val ? parseInt(val) : null } }"
                type="text" 
                placeholder="Do"
                class="filter-input"
              />
            </div>
          </div>

          <!-- 9. Transport parametry -->
          <div v-if="tempFilters && tempFilters.type === 'transport'" class="filter-group">
            <label class="filter-label">Zakres reklamy</label>
            <select v-model="tempFilters.transportScope" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option v-for="option in transportScopeOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </div>
          <div v-if="tempFilters && tempFilters.type === 'transport' && tempFilters.variant !== 'stop'" class="filter-group">
            <label class="filter-label">Liczba pojazdów</label>
            <div class="range-inputs">
              <input 
                :value="tempFilters?.vehicleCountFrom"
                @input="(e) => { if (tempFilters) { const val = handleNumberInput((e.target as HTMLInputElement).value, false); tempFilters.vehicleCountFrom = val ? parseInt(val) : null } }"
                type="text" 
                placeholder="Od"
                class="filter-input"
              />
              <span>-</span>
              <input 
                :value="tempFilters?.vehicleCountTo"
                @input="(e) => { if (tempFilters) { const val = handleNumberInput((e.target as HTMLInputElement).value, false); tempFilters.vehicleCountTo = val ? parseInt(val) : null } }"
                type="text" 
                placeholder="Do"
                class="filter-input"
              />
            </div>
          </div>
          <div v-if="tempFilters && tempFilters.type === 'transport'" class="filter-group">
            <label class="filter-label">Liczba pasażerów dziennie</label>
            <div class="range-inputs">
              <input 
                :value="(tempFilters as any)?.dailyPassengersFrom"
                @input="(e) => { if (tempFilters) { const val = handleNumberInput((e.target as HTMLInputElement).value, false); (tempFilters as any).dailyPassengersFrom = val ? parseInt(val) : null } }"
                type="text" 
                placeholder="Od"
                class="filter-input"
              />
              <span class="separator">-</span>
              <input 
                :value="(tempFilters as any)?.dailyPassengersTo"
                @input="(e) => { if (tempFilters) { const val = handleNumberInput((e.target as HTMLInputElement).value, false); (tempFilters as any).dailyPassengersTo = val ? parseInt(val) : null } }"
                type="text" 
                placeholder="Do"
                class="filter-input"
              />
            </div>
          </div>

          <!-- 10. Mobile Filters -->
          <div v-if="tempFilters && tempFilters.type === 'mobile'" class="filter-group">
            <label class="filter-label">Tryb ekspozycji</label>
            <select v-model="tempFilters.mobileExposureMode" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option value="moving">Jeżdżąca</option>
              <option value="stationary">Stojąca</option>
              <option value="mixed">Mieszana</option>
            </select>
          </div>
          <div v-if="tempFilters && tempFilters.type === 'mobile'" class="filter-group">
            <label class="filter-label">Strefa operacyjna</label>
            <select v-model="(tempFilters as any).operatingZone" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option value="center">Centrum</option>
              <option value="periphery">Peryferia</option>
              <option value="agglomeration">Cała aglomeracja</option>
            </select>
          </div>
        </div>

        <!-- SEKCJA: Wyposażenie i dodatki -->
        <div v-if="showEquipmentSectionInModal" class="filter-section">
          <h4 class="section-title">Wyposażenie i dodatki</h4>
          
          <div v-if="tempFilters && ['billboard', 'banner'].includes(tempFilters.type)" class="filter-group">
            <label class="checkbox-option">
              <input v-model="tempFilters.priceIncludesPrint" type="checkbox" v-if="tempFilters" />
              <span>Cena zawiera druk</span>
            </label>
          </div>

          <div v-if="tempFilters && ['billboard', 'banner', 'wall'].includes(tempFilters.type)" class="filter-group">
            <label class="checkbox-option">
              <input v-model="tempFilters.priceIncludesMounting" type="checkbox" v-if="tempFilters" />
              <span>Cena zawiera montaż</span>
            </label>
          </div>

          <div v-if="tempFilters && ['billboard', 'banner', 'wall'].includes(tempFilters.type)" class="filter-group">
            <label class="checkbox-option">
              <input v-model="tempFilters.graphicDesignHelp" type="checkbox" v-if="tempFilters" />
              <span>Pomoc przy projekcie graficznym</span>
            </label>
          </div>

          <div v-if="tempFilters && ['citylight', 'totem'].includes(tempFilters.type)" class="filter-group">
            <label class="checkbox-option">
              <input v-model="tempFilters.hasBacklight" type="checkbox" v-if="tempFilters" />
              <span>Podświetlenie</span>
            </label>
          </div>

          <div v-if="tempFilters && ['banner', 'wall'].includes(tempFilters.type)" class="filter-group">
            <label class="checkbox-option">
              <input v-model="(tempFilters as any).hasLightingTypeBanner" type="checkbox" v-if="tempFilters" />
              <span>Podświetlenie</span>
            </label>
          </div>

          <div v-if="tempFilters && tempFilters.type === 'billboard'" class="filter-group">
            <label class="checkbox-option">
              <input v-model="(tempFilters as any).hasLightingTypeBillboard" type="checkbox" v-if="tempFilters" />
              <span>Podświetlenie</span>
            </label>
          </div>

          <!-- LED Screen - Ambient Light Control -->
          <div v-if="tempFilters && tempFilters.type === 'led_screen'" class="filter-group">
            <label class="checkbox-option">
              <input v-model="(tempFilters as any).ambientLightControl" type="checkbox" v-if="tempFilters" />
              <span>Dostosowanie do otoczenia</span>
            </label>
          </div>
        </div>

        <!-- SEKCJA: Dostępność -->
        <div class="filter-section">
          <h4 class="section-title">Dostępność</h4>
          
          <!-- Rental Period -->
          <div class="filter-group">
            <label class="filter-label">Czas wynajmu</label>
            <select v-model="tempFilters.rentalPeriod" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option value="short_term">Krótkoterminowy (&lt;1 miesiąc)</option>
              <option value="long_term">Długoterminowy</option>
            </select>
          </div>

          <!-- Status Multiselect -->
          <div class="filter-group">
            <label class="filter-label">Status</label>
            <div class="multiselect-wrapper" ref="statusMultiselect">
              <div class="filter-select multiselect-trigger" @click="isStatusMenuOpen = !isStatusMenuOpen">
                <span class="selected-text">{{ statusLabel }}</span>
                <svg class="arrow" :class="{ open: isStatusMenuOpen }" width="10" height="6" viewBox="0 0 10 6" fill="none">
                  <path d="M1 1L5 5L9 1" stroke="#6B7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <div v-if="isStatusMenuOpen" class="multiselect-dropdown">
                <label class="checkbox-option">
                  <input type="checkbox" v-model="isStatusActive">
                  <span>Wolne</span>
                </label>
                <label class="checkbox-option">
                  <input type="checkbox" v-model="isStatusReserved">
                  <span>Zarezerwowane</span>
                </label>
                <label class="checkbox-option">
                  <input type="checkbox" v-model="isStatusSoon">
                  <span>Wkrótce dostępne</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- SEKCJA: Typ oferty i formalności -->
        <div class="filter-section">
          <h4 class="section-title">Typ oferty i formalności</h4>
          
          <!-- Offer Type -->
          <div class="filter-group">
            <label class="filter-label">Rodzaj oferty</label>
            <select v-model="tempFilters.offerType" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option value="owner">Właściciel</option>
              <option value="agency">Agencja</option>
              <option value="sublease">Podnajem</option>
            </select>
          </div>

          <div class="filter-group">
            <label class="checkbox-option">
              <input v-model="tempFilters.hasVatInvoice" type="checkbox" v-if="tempFilters" />
              <span>Faktura VAT</span>
            </label>
          </div>
        </div>

        <!-- Only with Image Toggle -->
        <div class="filter-section">
          <div style="display: flex; align-items: center; gap: 0.75rem;">
            <input v-model="tempFilters.onlyWithImage" type="checkbox" class="toggle-switch" style="display: none;" v-if="tempFilters" />
            <span class="toggle-switch-display" :class="{ active: tempFilters.onlyWithImage }" @click="tempFilters.onlyWithImage = !tempFilters.onlyWithImage" v-if="tempFilters"></span>
            <label style="margin: 0; cursor: pointer; font-size: 0.875rem; color: #4B5563; font-weight: 500;" @click="tempFilters.onlyWithImage = !tempFilters.onlyWithImage">Tylko ze zdjęciem</label>
          </div>
        </div>
        </div>

        <div class="modal-footer">
          <button @click="clearFilters" class="btn-secondary">Wyczyść</button>
          <button @click="applyFilters" class="btn-primary">Zastosuj</button>
        </div>
      </div>
    </div>
    </Teleport>

    <!-- Search Alert Global Modal -->
    <Teleport to="body">
      <SearchAlertModal 
        v-if="showSearchAlertModal && filters"
        :active-filters="filters"
        :location-label="locationQuery || (route.params.city ? deslugify(route.params.city as string) : '')"
        @close="showSearchAlertModal = false"
        @submit="handleSearchAlertSubmit"
      />

    </Teleport>


    <!-- Category/City Description for SEO - poza listings-page -->
    <div v-if="currentDescription" class="description-wrapper">
      <CategoryDescription 
        :description="currentDescription"
      />
    </div>
  </div>
</template>

<style scoped>
/* Mobile Search Bar */
.mobile-search {
  display: none;
  width: 100%;
  gap: 8px;
  align-items: center;
}

.mobile-search .search-container {
  flex: 1;
  margin-right: 0;
}

.mobile-action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  border-radius: 8px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  color: white;
  cursor: pointer;
  position: relative;
  transition: all 0.2s ease;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  font-weight: 600;
  font-size: 0.9rem;
  white-space: nowrap;
}

.mobile-action-btn:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(102, 126, 234, 0.5);
}

.mobile-action-btn:active {
  transform: translateY(-2px);
}

.mobile-action-btn svg {
  flex-shrink: 0;
}

.mobile-filter-badge {
  position: absolute;
  top: -6px;
  right: -6px;
  background: #EF4444;
  color: white;
  border-radius: 50%;
  width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 700;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  border: 2px solid white;
}

/* Overlay */
.overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  opacity: 1;
  pointer-events: auto;
  transition: opacity 0.2s ease;
}

/* Overlay transition classes (optional, kept minimal) */
.overlay-enter-from,
.overlay-leave-to {
  opacity: 0;
}

/* Sort Panel */
.sort-panel {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--card-bg, white);
  border-radius: 16px 16px 0 0;
  padding: 20px;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
  z-index: 1100;
  transform: translateY(100%);
  transition: transform 0.3s ease;
  max-height: 80vh;
  overflow-y: auto;
  touch-action: manipulation;
}

.sort-panel.is-open {
  transform: translateY(0);
}

.sort-panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid #e5e7eb;
}

.sort-panel-title {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.sort-panel-close {
  background: none;
  border: none;
  color: #6b7280;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
}

.sort-panel-close:hover {
  background: #f3f4f6;
}

.sort-options {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.sort-option {
  padding: 12px 16px;
  border-radius: 8px;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  text-align: left;
  cursor: pointer;
  transition: all 0.2s ease;
}

.sort-option:hover {
  background: #f3f4f6;
  border-color: #d1d5db;
}

.sort-option.active {
  background: #e0e7ff;
  border-color: #a5b4fc;
  color: #4f46e5;
  font-weight: 500;
}

.sort-option .option-label {
  display: block;
  font-size: 15px;
  margin-bottom: 2px;
}

.sort-option .option-desc {
  display: block;
  font-size: 13px;
  color: #6b7280;
}

.sort-option.active .option-desc {
  color: #6366f1;
}

/* Responsive adjustments */
@media (max-width: 767px) {
  .desktop-search {
    display: none;
  }
  
  .mobile-search {
    display: flex;
  }
  
  .results-count {
    display: none;
  }
}

@media (min-width: 768px) and (max-width: 1180px) {
  .desktop-search {
    display: none;
  }
  
  .mobile-search {
    display: flex;
  }
  
  .results-count {
    display: none;
  }
}

@media (min-width: 1180px) {
  .mobile-search {
    display: none;
  }
  
  .desktop-search {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
  }
  
  .search-container {
    flex: 1;
    max-width: 400px;
  }
  
  .sort-select {
    min-width: 200px;
  }
}

/* Existing styles below */
.listings-page {
  height: calc(100vh - 72px);
  padding-top: 1.5rem;
  background: var(--bg-secondary, #f9fafb);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Description Wrapper - poza głównym kontenerem */
.description-wrapper {
  padding: 2rem;
  background: var(--card-bg, white);
  width: 100%;
  box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.05);
}

/* Search Bar */
.listings-title {
  font-size: 2.25rem;
  color: var(--text-main, #111827);
  font-weight: 800;
  margin: 1.5rem 0 2rem 0;
  letter-spacing: -0.025em;
  padding: 0 1rem;
}

/* Visually hidden but readable by search engines and screen readers */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

/* Hide h1 on mobile */
@media (max-width: 768px) {
  .listings-title {
    display: none;
  }
}

.search-bar {
  background: var(--card-bg, white);
  border-bottom: 2px solid var(--border-color, #e5e7eb);
  padding: 1rem 2rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  height: 70px;
  flex-shrink: 0;
}

.search-container {
  flex: 1;
  max-width: 500px;
  position: relative;
}

.search-container svg {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #6b7280;
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 3rem;
  border: 2px solid var(--border-color, #e5e7eb);
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.2s;
  background: var(--card-bg, white);
  color: var(--text-main, #111827);
}

.search-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filters-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-size: 0.95rem;
  font-weight: 600;
  transition: all 0.2s ease;
  position: relative;
}

.filters-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.filter-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background: #EF4444;
  color: white;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  border: 2px solid white;
}

.sort-select {
  padding: 0.75rem 1rem;
  background: var(--card-bg, white);
  border: 2px solid var(--border-color, #e5e7eb);
  border-radius: 10px;
  font-weight: 600;
  color: var(--text-main, #374151);
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.95rem;
  min-width: 180px;
}

.sort-select:hover {
  border-color: #667eea;
}

.sort-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.results-count {
  color: var(--text-muted, #6b7280);
  font-weight: 600;
  white-space: nowrap;
}

.view-toggle {
  display: flex;
  gap: 0.25rem;
  background: var(--card-bg, white);
  border: 2px solid var(--border-color, #e5e7eb);
  border-radius: 10px;
  padding: 0.25rem;
}

.view-btn {
  padding: 0.5rem;
  background: transparent;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
}

.view-btn:hover {
  background: var(--bg-tertiary, #f3f4f6);
  color: var(--text-main, #374151);
}

.view-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.view-btn.active:hover {
  background: linear-gradient(135deg, #5568d3 0%, #65408b 100%);
}

/* Content Wrapper - 50/50 Split */
.content-wrapper {
  display: grid;
  grid-template-columns: 1fr 1fr;
  flex: 1;
  overflow: hidden;
  height: calc(100vh - 70px); /* Odejmujemy wysokość paska wyszukiwania */
  position: relative;
}

.listings-list-container {
  background: var(--card-bg, white);
  border-right: 2px solid var(--border-color, #e5e7eb);
  overflow-y: auto;
  height: 100%;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  position: relative; /* Add relative for child positioning */
}

.list-scroll-top {
  position: sticky;
  bottom: 1.5rem;
  align-self: flex-end;
  right: 1.5rem;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--primary-gradient, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
  color: white;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  z-index: 10;
  margin-top: -44px; /* Pull it up to overlay if possible, or use absolute */
  transition: all 0.3s ease;
}

.list-scroll-top:hover {
  transform: translateY(-4px) scale(1.05);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.5);
}

/* Use fixed for the button relative to the viewport on desktop */
.listings-list-container .list-scroll-top {
  position: fixed;
  bottom: 2rem;
  left: calc(50vw - 60px); /* Position it near the divider on desktop */
  z-index: 100;
}

@media (max-width: 768px) {
  .listings-list-container .list-scroll-top {
    position: fixed;
    bottom: 5rem; /* Above the mobile map toggle */
    right: 1.25rem;
    left: auto;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    z-index: 1001;
    display: flex;
  }
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 2rem;
  text-align: center;
  min-height: 400px;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid var(--bg-tertiary, #f3f4f6);
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state svg {
  color: #d1d5db;
  margin-bottom: 1.5rem;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  color: var(--text-main, #1f2937);
  font-size: 1.25rem;
}

.empty-state p {
  margin: 0;
  color: #6b7280;
}

.listings-list {
  padding: 1rem;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

/* Widok listy */
.listings-list.list {
  grid-template-columns: 1fr;
}

/* List View Card Styles */
.listings-list.list .listing-card {
  flex-direction: row;
  height: auto;
}

.listings-list.list .card-image {
  width: 280px;
  height: 200px;
  flex-shrink: 0;
}

.listings-list.list .card-content {
  flex: 1;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.listings-list.list .card-title {
  font-size: 1.5rem;
  margin-bottom: 0.75rem;
}

.listings-list.list .card-location,
.listings-list.list .card-dimensions {
  font-size: 1rem;
}

.listings-list.list .card-footer {
  margin-top: auto;
}

@media (max-width: 1024px) {
  .listings-list.list .listing-card {
    flex-direction: column;
  }

  .listings-list.list .card-image {
    width: 100%;
    height: 220px;
  }
}

.listing-card {
  background: var(--card-bg, white);
  border-radius: 12px;
  overflow: hidden;
  box-shadow: var(--card-shadow, 0 2px 8px rgba(0, 0, 0, 0.08));
  transition: all 0.3s ease;
  height: 100%;
  display: flex;
  flex-direction: column;
  text-decoration: none;
  color: var(--text-main, inherit);
  border: 2px solid transparent;
}

.listing-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.listing-card.hovered,
.listing-card.selected {
  border-color: #667eea;
  box-shadow: 0 6px 16px rgba(102, 126, 234, 0.25);
}

.card-image {
  position: relative;
  width: 100%;
  height: 220px;
  overflow: hidden;
}

.card-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.listing-card:hover .card-image img {
  transform: scale(1.05);
}

.no-image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background-color: var(--bg-tertiary, #f3f4f6);
  color: var(--text-light, #9ca3af);
}

.no-image-placeholder svg {
  margin-bottom: 0.75rem;
}

.no-image-placeholder span {
  font-size: 0.875rem;
  font-weight: 500;
}

.card-badge {
  position: absolute;
  top: 1rem;
  left: 1rem;
  color: white;
  padding: 0.375rem 0.875rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  backdrop-filter: blur(8px);
}

.status-badge {
  position: absolute;
  bottom: 1rem;
  left: 1rem;
  color: white;
  padding: 0.375rem 0.875rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.status-badge::before {
  content: '';
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: white;
  display: inline-block;
}

.card-actions {
  position: absolute;
  top: 1rem;
  right: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  z-index: 10;
}

.card-content {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
  flex: 1;
}

.card-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1F2937;
  margin: 0;
  line-height: 1.3;
}

.card-location,
.card-dimensions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #6B7280;
  font-size: 0.9rem;
}

.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
  padding-top: 1rem;
  border-top: 1px solid #F3F4F6;
}

.card-price {
  display: flex;
  flex-direction: column;
}

.price-amount {
  font-size: 1.5rem;
  font-weight: 800;
  color: #4F46E5;
}

.price-period {
  font-size: 0.8rem;
  color: #9CA3AF;
}

.action-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(8px);
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.action-btn:hover {
  background: rgba(0, 0, 0, 0.6);
  transform: scale(1.1);
}

.favorite-btn.active {
  background: rgba(255, 255, 255, 0.95);
}

.favorite-btn.active:hover {
  background: white;
}

.comparison-btn.active {
  background: rgba(255, 255, 255, 0.95);
}

.comparison-btn.active:hover {
  background: white;
}

.negotiable-badge {
  font-size: 0.75rem;
  color: #10B981;
  font-weight: 600;
  margin-top: 0.25rem;
  display: inline-block;
}

.missing-data-badge {
  font-size: 0.9rem;
  color: #EF4444;
  font-weight: 600;
  padding: 0.5rem 1rem;
  background: #FEE2E2;
  border-radius: 6px;
  display: inline-block;
}

.estimated-label {
  font-size: 1.2rem;
  color: #F59E0B;
  margin-right: 0.25rem;
}

.estimated-info {
  font-size: 0.7rem;
  color: #F59E0B;
  font-weight: 500;
}

.map-container-wrapper {
  height: 100%;
  position: relative;
  background: var(--bg-tertiary, #f3f4f6);
  display: flex;
  flex-direction: column;
}

.map-container {
  flex: 1;
  min-height: 0; /* Allows the container to shrink below its content size */
  width: 100%;
  position: relative;
}

/* Map Hint Overlay */
.map-hint-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
  z-index: 999;
  opacity: 0;
  transition: opacity 0.3s;
}

.map-container:hover .map-hint-overlay {
  opacity: 1;
}

.map-hint-message {
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  white-space: nowrap;
}

/* Legend Toggle Button */
.legend-toggle-button {
  position: absolute;
  top: 1rem;
  right: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--card-bg, rgba(255, 255, 255, 0.75));
  border: 2px solid var(--border-color, rgba(229, 231, 235, 0.75));
  border-radius: 8px;
  padding: 0.75rem 1.25rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--text-main, #374151);
  cursor: pointer;
  z-index: 1000;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
  backdrop-filter: blur(4px);
}

.legend-toggle-button:hover {
  background: rgba(255, 255, 255, 0.9);
  border-color: rgba(209, 213, 219, 0.9);
  transform: translateY(-1px);
}

.legend-toggle-button:active {
  transform: translateY(0);
}

.legend-toggle-button.is-active {
  background: rgba(255, 255, 255, 0.9);
  border-color: rgba(156, 163, 175, 0.9);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.legend-toggle-button svg {
  flex-shrink: 0;
  transition: transform 0.2s ease;
}

.legend-toggle-button.is-active svg {
  transform: rotate(90deg);
}

/* Side Panel Legend */
.legend-side-panel {
  position: fixed;
  top: 0;
  right: 0;
  width: 280px;
  height: 100vh;
  background: var(--card-bg, white);
  box-shadow: -4px 0 20px rgba(0, 0, 0, 0.1);
  z-index: 1100;
  transition: transform 0.3s ease-in-out;
  display: flex;
  flex-direction: column;
  transform: translateX(100%);
}

.legend-side-panel.is-visible {
  transform: translateX(0);
}

.legend-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem;
  border-bottom: 1px solid var(--border-color, #E5E7EB);
  background: var(--bg-secondary, #f9fafb);
  flex-shrink: 0;
}

.legend-header h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-main, #111827);
}

.close-legend {
  background: none;
  border: none;
  color: #6B7280;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.2s ease;
  z-index: 1;
  position: relative;
}

.close-legend:hover {
  background: #F3F4F6;
  color: #111827;
}

.legend-content {
  flex: 1;
  overflow-y: auto;
  padding: 1.25rem 1.5rem;
}

.legend-items {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0.5rem;
  border-radius: 6px;
  transition: background-color 0.2s ease;
}

.legend-item:hover {
  background-color: #F9FAFB;
}

.legend-marker {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
  flex-shrink: 0;
}

.legend-label {
  font-size: 0.9rem;
  color: #374151;
  line-height: 1.4;
}

/* Desktop Legend */
.map-legend {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: var(--card-bg, rgba(255, 255, 255, 0.8));
  border: 1px solid var(--border-color, rgba(229, 231, 235, 0.8));
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  padding: 0.75rem 1rem;
  z-index: 1001;
  backdrop-filter: blur(4px);
  opacity: 0;
  visibility: hidden;
  transform: translateY(-8px);
  transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
  pointer-events: none;
  display: flex;
  flex-direction: column;
  max-height: calc(100% - 2rem);
}

.map-legend.is-visible {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
  pointer-events: auto;
}

@media (min-width: 768px) {
  .legend-side-panel {
    display: none;
  }
  
  .legend-toggle-button {
    display: flex;
  }
  
  .map-legend {
    display: flex;
  }
}

/* Legend items for desktop */
.legend-items {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.4rem;
  overflow-y: auto;
  padding-right: 0.25rem;
  flex: 1;
  min-height: 0;
}

/* Mobile legend items - single row */
@media (max-width: 767px) {
  .legend-side-panel .legend-items {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    max-height: none;
    overflow-y: visible;
    padding: 0.5rem 1rem 1rem;
  }
  
  .legend-side-panel .legend-item {
    padding: 0.25rem 0.5rem;
  }
  
  .legend-side-panel .legend-label {
    white-space: normal;
  }
}

/* Extra small screens */
@media (max-width: 480px) {
  .legend-header {
    padding: 1rem 0.75rem;
  }
  
  .close-legend {
    padding: 0.5rem;
    flex-shrink: 0;
  }
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.2rem 0.25rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.legend-marker {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
  flex-shrink: 0;
}

.legend-label {
  font-size: 0.8rem;
  color: var(--text-muted, #4B5563);
  font-weight: 500;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  padding-top: 80px;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  backdrop-filter: blur(4px);
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-content {
  background: var(--card-bg, white);
  border-radius: 16px;
  width: 90%;
  max-width: 600px;
  max-height: calc(100vh - 120px);
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header {
  padding: 1.5rem 2rem;
  border-bottom: 2px solid var(--border-color, #e5e7eb);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-main, #1f2937);
}

.close-btn {
  background: transparent;
  border: none;
  color: #6b7280;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 8px;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-btn:hover {
  background: #f3f4f6;
  color: #1f2937;
}

.modal-body {
  padding: 2rem;
  overflow-y: auto;
  flex: 1;
}

.filter-section {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--border-color, #e5e7eb);
}

.toggle-switch {
  display: none !important;
}

.toggle-switch-display {
  display: inline-block;
  width: 50px;
  height: 28px;
  background: var(--bg-tertiary, linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%));
  border-radius: 14px;
  cursor: pointer;
  position: relative;
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.05);
  flex-shrink: 0;
}

.toggle-switch-display::before {
  content: '';
  position: absolute;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: white;
  top: 3px;
  left: 3px;
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15), 0 1px 3px rgba(0, 0, 0, 0.1);
}

.toggle-switch-display:hover {
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.05);
}

.toggle-switch-display.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1), 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.toggle-switch-display.active::before {
  left: 25px;
  box-shadow: 0 3px 8px rgba(102, 126, 234, 0.3), 0 1px 3px rgba(0, 0, 0, 0.1);
}

.toggle-switch-display.active:hover {
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1), 0 0 0 3px rgba(102, 126, 234, 0.15);
}

.filter-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.info-message {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  background: var(--bg-tertiary, #EFF6FF);
  border: 1px solid var(--border-color, #BFDBFE);
  border-radius: 8px;
  color: var(--text-main, #1E40AF);
  font-size: 0.95rem;
  line-height: 1.5;
  margin-bottom: 1.5rem;
}

.info-message svg {
  flex-shrink: 0;
}

.info-message span {
  flex: 1;
}

.section-title {
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #4f46e5;
}

.filter-group {
  margin-bottom: 1.5rem;
}

.filter-row {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.filter-row .filter-group {
  flex: 1;
  margin-bottom: 0;
}

.filter-label {
  display: block;
  font-weight: 600;
  color: var(--text-main, #374151);
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.filter-input,
.filter-select {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid var(--border-color, #e5e7eb);
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.2s;
  background: var(--input-bg, white);
  color: var(--text-main, #111827);
}

.filter-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.range-inputs {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.range-inputs input {
  flex: 1;
}

.range-inputs span {
  color: #6b7280;
  font-weight: 600;
}

.price-filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.price-unit-select {
  width: 100%;
}

.status-checkboxes {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.multiselect-wrapper {
  position: relative;
  width: 100%;
}

.multiselect-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  user-select: none;
}

.multiselect-trigger .selected-text {
  flex: 1;
}

.multiselect-trigger .arrow {
  transition: transform 0.2s;
  flex-shrink: 0;
  margin-left: 0.5rem;
}

.multiselect-trigger .arrow.open {
  transform: rotate(180deg);
}

.multiselect-dropdown {
  position: absolute;
  top: calc(100% + 0.5rem);
  left: 0;
  right: 0;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 100;
  max-height: 200px;
  overflow-y: auto;
}

.checkbox-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  cursor: pointer;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  transition: all 0.2s;
}

.checkbox-option:hover {
  border-color: #10B981;
  background: #f0fdf4;
}


.checkbox-option span {
  color: #374151;
  font-weight: 500;
  font-size: 0.95rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  padding: 0.75rem;
  border-radius: 8px;
  transition: background 0.2s;
}

.checkbox-label:hover {
  background: #f9fafb;
}

.filter-checkbox {
  width: 20px;
  height: 20px;
  cursor: pointer;
}

.checkbox-label span {
  color: #374151;
  font-weight: 500;
}

/* Styles for location autocomplete */
.location-autocomplete {
  position: relative;
  width: 100%;
}

.input-with-clear {
  position: relative;
  width: 100%;
}

.clear-button {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: #9ca3af;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  width: 20px;
  height: 20px;
  border-radius: 50%;
}

.clear-button:hover {
  color: #4b5563;
  background-color: #f3f4f6;
}

.location-suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  margin-top: 0.25rem;
  padding: 0.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 100;
  max-height: 300px;
  overflow-y: auto;
  min-width: 250px;
}

.suggestion-section {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

/* Upewnij się, że loading-state ma row jako kierunek i jest wyśrodkowany */
.loading-state {
  flex-direction: row !important;
  justify-content: center !important;
  width: 100%;
}

.suggestion-header {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-muted, #6b7280);
  padding: 0.5rem;
}

.location-suggestion {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  cursor: pointer;
  border-radius: 6px;
  transition: background-color 0.2s;
}

.location-suggestion:hover {
  background-color: var(--bg-tertiary, #f3f4f6);
}

.suggestion-text {
  display: flex;
  flex-direction: column;
}

.suggestion-name {
  font-size: 0.95rem;
  color: var(--text-main, #1f2937);
  font-weight: 500;
}

.suggestion-type {
  font-size: 0.75rem;
  color: var(--text-light, #9ca3af);
  font-weight: 500;
}

.loading-state {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  padding: 1rem;
  gap: 0.5rem;
  color: #6b7280;
}

.loading-state.compact {
  padding: 0.5rem 1rem;
  min-height: 40px;
}

.loading-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #f3f4f6;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.modal-footer {
  padding: 1.5rem 2rem;
  border-top: 2px solid var(--border-color, #e5e7eb);
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.btn-primary,
.btn-secondary {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
  background: var(--input-bg, white);
  color: var(--text-main, #374151);
  border: 2px solid var(--border-color, #e5e7eb);
}

.btn-secondary:hover {
  background: var(--bg-secondary, #f9fafb);
  border-color: var(--text-light, #d1d5db);
}

/* Leaflet Overrides */
:deep(.custom-marker) {
  background: transparent;
  border: none;
}

:deep(.leaflet-popup-content-wrapper) {
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

:deep(.leaflet-popup-content) {
  margin: 1rem;
}

:deep(.leaflet-control-attribution) {
  display: none !important;
}

/* Responsive */
@media (max-width: 1024px) {
  .content-wrapper {
    grid-template-columns: 1fr;
    grid-template-rows: 1fr 400px;
    height: calc(100vh - 70px);
  }

  .ads-list-container {
    border-right: none;
    border-bottom: 2px solid #e5e7eb;
    height: auto;
    max-height: calc(100vh - 470px); /* 70px nagłówek + 400px mapa */
  }
  
  .map-container-wrapper {
    height: 400px;
  }
}

/* Mobile Map Toggle Button */
.mobile-map-toggle {
  display: none; /* Hidden by default, shown only on mobile */
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 1000;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 24px;
  padding: 10px 20px;
  font-size: 15px;
  font-weight: 500;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  opacity: 0.9;
  white-space: nowrap;
}

.mobile-map-toggle:hover {
  background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
  transform: translateX(-50%) translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
  opacity: 1;
}

.mobile-map-toggle:active {
  transform: translateX(-50%) translateY(0);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

@media (max-width: 767px) {
  .mobile-map-toggle {
    display: flex;
    opacity: 0.9;
  }
  
  .mobile-map-toggle span {
    display: inline-block;
  }
  
  .content-wrapper {
    flex-direction: column;
    height: auto;
    min-height: calc(100vh - 200px);
  }
  
  .listings-list-container {
    border-right: none;
    border-bottom: 1px solid #e5e7eb;
    min-height: calc(100vh - 250px); /* Adjust based on your header/footer */
    transition: opacity 0.3s ease, height 0.3s ease;
    
    &.mobile-hidden {
      display: none;
    }
  }
  
  .map-container-wrapper {
    display: none;
    height: calc(100vh - 250px); /* Adjust based on your header/footer */
    transition: opacity 0.3s ease, height 0.3s ease;
    
    &.mobile-visible {
      display: block;
      opacity: 1;
    }
    
    &.mobile-hidden {
      display: none;
    }
  }
}

@media (max-width: 640px) {
  .description-wrapper {
    padding: 1rem;
  }
  
  .search-bar {
    flex-direction: column;
    align-items: stretch;
    padding: 1rem;
    height: auto;
  }

  .search-container {
    max-width: none;
  }

  .results-count {
    text-align: center;
  }

  .content-wrapper {
    grid-template-columns: 1fr;
    grid-template-rows: 1fr;
    height: calc(100vh - 70px);
  }
  
  .ads-list-container {
    max-height: none;
  }

  .map-container-wrapper {
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: none;
  }

  .map-container-wrapper.mobile-visible {
    display: flex;
  }

  .listings-list-container.mobile-hidden {
    display: none;
  }

  .ad-image {
    width: 80px;
    height: 80px;
  }

  .ad-title {
    font-size: 1rem;
  }

  .modal-content {
    width: 100%;
    max-height: 85vh;
    border-radius: 20px 20px 0 0;
    position: fixed;
    bottom: 0;
    top: auto;
    left: 0;
    transform: none;
  }

  .modal-header,
  .modal-body,
  .modal-footer {
    padding: 1rem;
  }
}
@media (max-width: 1331px) {
  .view-toggle {
    display: none;
  }
}

@media (max-width: 564px) {
  .mobile-action-btn span:not(.mobile-filter-badge) {
    display: none;
  }
}

.pagination-bottom {
  margin-top: 1.5rem;
  margin-bottom: 1.5rem;
  width: 100%;
}

.pagination-bottom .pagination-container {
  padding-top: 0;
  padding-bottom: 0;
}
</style>