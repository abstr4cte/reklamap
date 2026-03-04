<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api, getFullImageUrl } from '../services/api'
import { slugify, deslugify } from '../utils/slugify'
import type { Advertisement } from '../types'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { filtersToQueryParams, queryParamsToFilters, normalizePolishChars } from '../utils/filterUtils'
import polishLocations from '../data/polishLocations.json'
import { debouncedSearchLocations, type LocationResult } from '../services/locationService'
import Pagination from '../components/Pagination.vue'
import Breadcrumbs from '../components/Breadcrumbs.vue'
import CategoryDescription from '../components/CategoryDescription.vue'
import { useSeo } from '../composables/useSeo'
import { categoryDescriptions, cityDescriptions } from '../data/categoryDescriptions'
import { mapTypeToUrlFormat } from '../utils/typeMapping'
import SearchAlertModal from '../components/SearchAlertModal.vue'
import SearchAlertBox from '../components/SearchAlertBox.vue'


// Funkcja formatująca adres i miasto, taka sama jak w AdCard
const formatLocation = (location: string, city: string) => {
  // Extract street and number from full address
  const parts = location.split(',').map(p => p.trim())
  
  let streetWithNumber = ''
  
  if (parts.length >= 2) {
    const firstPart = parts[0]
    const secondPart = parts[1]
    
    // Check if first part is a number
    if (/^\d+/.test(firstPart)) {
      streetWithNumber = `${secondPart} ${firstPart}`
    } else {
      streetWithNumber = firstPart
    }
  } else {
    streetWithNumber = parts[0] || location
  }
  
  return `${streetWithNumber}, ${city}`
}

// Funkcja zwracająca etykietę jednostki ceny
const getPriceUnitLabel = (ad: Advertisement): string => {
  const unit = (ad.price_unit as 'day' | 'week' | 'month' | 'year' | 'sqm' | 'campaign') || 'month'
  switch (unit) {
    case 'day': return 'zł/dzień'
    case 'week': return 'zł/tydzień'
    case 'month': return 'zł/mies.'
    case 'year': return 'zł/rok'
    case 'campaign': return 'zł/kampania'
    default: return 'zł/mies.'
  }
}

const listings = ref<Advertisement[]>([])
const isLoading = ref(true)
const hoveredAdId = ref<string | null>(null)
const selectedAdId = ref<string | null>(null)
const searchQuery = ref('')
const showFiltersModal = ref(false)
const tempFilters = ref<any>(null) // Tymczasowe filtry do edycji w modalu
const mapContainer = ref<HTMLElement | null>(null)
let map: L.Map | null = null
const markers: Map<string, L.Marker> = new Map()
const isMapActive = ref(false)
// Get saved view mode from localStorage or default to grid
const savedViewMode = typeof window !== 'undefined' ? window.localStorage.getItem('adsViewMode') : null
const viewMode = ref<'grid' | 'list'>(savedViewMode === 'list' ? 'list' : 'grid')
const isMobile = ref(false)
const showMapOnMobile = ref(false)
const showSortPanel = ref(false)
const isLegendVisible = ref(false)
const showMapButton = ref(false)
const showSearchAlertModal = ref(false)
const hasShownAlertModal = ref(localStorage.getItem('search_alert_shown') === 'true')


const sortOptions = [
  { value: 'newest', label: 'Najnowsze', description: 'Od najnowszych' },
  { value: 'oldest', label: 'Najstarsze', description: 'Od najstarszych' },
  { value: 'name-asc', label: 'Nazwa A-Z', description: 'Alfabetycznie rosnąco' },
  { value: 'name-desc', label: 'Nazwa Z-A', description: 'Alfabetycznie malejąco' },
  { value: 'price-day-asc', label: 'Cena za dzień', description: 'Od najtańszych' },
  { value: 'price-day-desc', label: 'Cena za dzień', description: 'Od najdroższych' },
  { value: 'price-month-asc', label: 'Cena za miesiąc', description: 'Od najtańszych' },
  { value: 'price-month-desc', label: 'Cena za miesiąc', description: 'Od najdroższych' },
  { value: 'price-sqm-asc', label: 'Cena za m²', description: 'Od najtańszych' },
  { value: 'price-sqm-desc', label: 'Cena za m²', description: 'Od najdroższych' },
  { value: 'price-campaign-asc', label: 'Cena za kampanię', description: 'Od najtańszych' },
  { value: 'price-campaign-desc', label: 'Cena za kampanię', description: 'Od najdroższych' }
]

const handleSortButtonClick = () => {
  showSortPanel.value = true
}

const handleSortOptionClick = (value: string) => {
  selectSortOption(value)
}

const selectSortOption = (value: string) => {
  // Update the sortBy ref with the new value
  sortBy.value = value
  // Close the sort panel
  showSortPanel.value = false
  
  // Force Vue to recognize the change immediately
  nextTick(() => {
    // This ensures the UI updates with the new sort
  })
}

// Check if mobile on mount and on resize
const checkIfMobile = () => {
  isMobile.value = window.innerWidth < 768
  if (!isMobile.value) {
    showMapOnMobile.value = false
  }
}

const handleScroll = () => {
  const listingsContainer = document.querySelector('.listings-list-container')
  const mapContainer = document.querySelector('.map-container-wrapper')
  const footer = document.querySelector('footer')
  
  if (listingsContainer && mapContainer) {
    const listingsRect = listingsContainer.getBoundingClientRect()
    const mapRect = mapContainer.getBoundingClientRect()
    const footerRect = footer?.getBoundingClientRect()
    
    // Show button when:
    // 1. We're in listings or map section (not at footer)
    // 2. Footer is not visible
    const inListingsSection = listingsRect.top < window.innerHeight && listingsRect.bottom > 0
    const inMapSection = mapRect.top < window.innerHeight && mapRect.bottom > 0
    const footerIsVisible = footerRect && footerRect.top < window.innerHeight
    
    const shouldShowButton = (inListingsSection || inMapSection) && !footerIsVisible
    
    if (isMobile.value) {
      showMapButton.value = shouldShowButton
    }
  }
}

// Toggle between list and map on mobile
const toggleMobileMap = () => {
  showMapOnMobile.value = !showMapOnMobile.value
  if (showMapOnMobile.value && mapContainer.value && !map) {
    nextTick(() => {
      initMap()
    })
  }
}

// Function to change view mode and save to localStorage
const changeViewMode = (mode: 'grid' | 'list') => {
  viewMode.value = mode
  if (typeof window !== 'undefined') {
    window.localStorage.setItem('adsViewMode', mode)
  }
}
const sortBy = ref('newest')
// Funkcja do pobierania ceny w zależności od wybranego okresu
const getPrice = (ad: Advertisement, period: 'day' | 'week' | 'month' | 'year' | 'sqm' | 'campaign') => {
  const basePrice = ad.price
  const adPriceUnit = ad.price_unit || 'month'

  // If the ad's unit matches the requested period, return the price as-is
  if (adPriceUnit === period) {
    return basePrice
  }

  // Convert from ad's unit to requested period
  let pricePerMonth = basePrice
  
  // First convert to monthly price
  switch (adPriceUnit) {
    case 'day':
      pricePerMonth = basePrice * 30
      break
    case 'week':
      pricePerMonth = basePrice * 4
      break
    case 'month':
      pricePerMonth = basePrice
      break
    case 'year':
      pricePerMonth = basePrice / 12
      break
    case 'campaign':
      pricePerMonth = basePrice
      break
  }

  // Then convert from monthly to requested period
  switch (period) {
    case 'day':
      return pricePerMonth / 30
    case 'week':
      return pricePerMonth / 4
    case 'month':
      return pricePerMonth
    case 'year':
      return pricePerMonth * 12
    case 'campaign':
      // If ad has campaign_duration, calculate based on days
      if (ad.campaign_duration) {
        return pricePerMonth * (ad.campaign_duration / 30)
      }
      // If no duration, return a very high number to sort at the end
      return Number.MAX_SAFE_INTEGER
    case 'sqm':
      const area = ad.width * ad.height
      return area > 0 ? pricePerMonth / area : Number.MAX_SAFE_INTEGER
    default:
      return pricePerMonth
  }
}
const priceDisplay = ref<'day' | 'week' | 'month' | 'year' | 'sqm' | 'campaign' | null>(null)
const isStatusMenuOpen = ref(false)
const statusMultiselect = ref<HTMLElement | null>(null)
const currentPage = ref(1)
const itemsPerPage = 20

const route = useRoute()
const router = useRouter()

// Flaga zapobiegająca cyklicznemu wywoływaniu watch'ów
const isResettingFilters = ref(false)
const isInitialized = ref(false)

const LAST_SEARCH_KEY = 'reklamap_last_search'

const saveLastSearch = () => {
  try {
    const searchFilters = { 
      ...filters.value,
      keyword: searchQuery.value,
      _priceDisplayUnit: priceDisplay.value
    }
    localStorage.setItem(LAST_SEARCH_KEY, JSON.stringify(searchFilters))
  } catch (error) {
    console.error('Error saving search filters:', error)
  }
}

// Helper to map type to Polish label
const getTypeLabel = (type: string): string => {
  // Mapowanie zarówno wartości z bazy danych jak i z URL
  const typeLabels: Record<string, string> = {
    // Wartości z bazy danych
    'billboard': 'Billboardy',
    'citylight': 'Citylighty',
    'led_screen': 'Ekrany LED',
    'banner': 'Banery',
    'wall': 'Ściany reklamowe',
    'totem': 'Totemy reklamowe',
    'transport': 'Reklama w transporcie',
    'mobile': 'Reklama mobilna',
    'other': 'Inne',
    // Wartości z URL (dla breadcrumbs i SEO)
    'billboardy': 'Billboardy',
    'citylighty': 'Citylighty',
    'ekrany-led': 'Ekrany LED',
    'banery': 'Banery',
    'sciany-reklamowe': 'Ściany reklamowe',
    'totemy-reklamowe': 'Totemy reklamowe',
    'reklama-w-transporcie': 'Reklama w transporcie',
    'reklama-mobilna': 'Reklama mobilna',
    'inne': 'Inne'
  }
  
  // Jeśli typ jest w mapie, zwróć go
  if (typeLabels[type]) {
    return typeLabels[type]
  }
  
  // W przeciwnym razie kapitalizuj pierwszą literę
  return type.charAt(0).toUpperCase() + type.slice(1)
}

// Breadcrumbs for SEO
const breadcrumbs = computed(() => {
  const items = [
    {
      label: 'Strona główna',
      path: '/'
    },
    {
      label: 'Powierzchnie reklamowe'
    }
  ]
  
  // Add type if filtered
  if (route.params.type) {
    const type = route.params.type as string
    items[items.length - 1].path = '/powierzchnie-reklamowe'
    items.push({
      label: getTypeLabel(type)
    })
  }
  
  // Add city if filtered
  if (route.params.city) {
    const city = route.params.city as string
    if (route.params.type) {
      // Jeśli jest typ i miasto: Strona główna > Powierzchnie > Typ > Miasto
      items[items.length - 1].path = `/powierzchnie-reklamowe/${route.params.type}`
    } else {
      // Jeśli jest tylko miasto: Strona główna > Powierzchnie > Miasto
      items[items.length - 1].path = '/powierzchnie-reklamowe'
    }
    items.push({
      label: deslugify(city)
    })
  }
  
  return items
})

// Category/City Description for SEO
const currentDescription = computed(() => {
  const city = route.params.city as string | undefined
  const type = route.params.type as string | undefined
  
  // Priorytet: miasto > kategoria > domyślny
  if (city && cityDescriptions[city]) {
    return cityDescriptions[city]
  }
  
  if (type && categoryDescriptions[type]) {
    return categoryDescriptions[type]
  }
  
  // Domyślny opis dla wszystkich powierzchni
  return categoryDescriptions['']
})

// SEO Page Info (Title, Description, Keywords)
const seoInfo = computed(() => {
  const type = route.params.type as string | undefined
  const city = route.params.city as string | undefined
  
  let title = 'Powierzchnie Reklamowe w Polsce'
  let description = 'Przeglądaj oferty powierzchni reklamowych w całej Polsce. Billboardy, citylighty, banery i więcej.'
  let keywords = 'powierzchnie reklamowe, billboardy, citylighty, banery, reklama zewnętrzna'
  
  if (type && city) {
    const typeLabel = getTypeLabel(type)
    const cityName = deslugify(city)
    title = `${typeLabel} ${cityName} - Wynajem Powierzchni Reklamowych | ReklaMap`
    description = `Znajdź i wynajmij ${typeLabel.toLowerCase()} w ${cityName}. Porównuj oferty, ceny i lokalizacje na mapie. ${listings.value.length} ofert dostępnych.`
    keywords = `${typeLabel} ${cityName}, powierzchnie reklamowe ${cityName}, wynajem ${typeLabel.toLowerCase()} ${cityName}`
  } else if (type) {
    const typeLabel = getTypeLabel(type)
    title = `${typeLabel} - Wynajem w Całej Polsce | ReklaMap`
    description = `Przeglądaj oferty ${typeLabel.toLowerCase()} w całej Polsce. ${listings.value.length} ofert dostępnych. Porównuj ceny i lokalizacje.`
    keywords = `${typeLabel}, wynajem ${typeLabel.toLowerCase()}, powierzchnie reklamowe`
  } else if (city) {
    const cityName = deslugify(city)
    title = `Powierzchnie Reklamowe ${cityName} - Billboardy, Citylighty | ReklaMap`
    description = `Wszystkie powierzchnie reklamowe w ${cityName}. ${listings.value.length} ofert. Porównuj ceny billboardy, citylighty, banery.`
    keywords = `powierzchnie reklamowe ${cityName}, billboardy ${cityName}, reklama ${cityName}`
  }
  
  return { title, description, keywords }
})

// SEO Meta Tags
watch([seoInfo, listings], () => {
  const { title, description, keywords } = seoInfo.value
  const url = typeof window !== 'undefined' ? window.location.origin + route.path : 'https://reklamap.pl' + route.path
  
  useSeo({
    title,
    description,
    keywords,
    ogType: 'website',
    canonical: url,
    structuredData: {
      '@context': 'https://schema.org',
      '@type': 'ItemList',
      'name': title,
      'description': description,
      'itemListElement': listings.value.slice(0, 5).map((ad, index) => ({
        '@type': 'ListItem',
        'position': index + 1,
        'url': typeof window !== 'undefined' 
          ? `${window.location.origin}/powierzchnia-reklamowa/${mapTypeToUrlFormat(ad.type)}/${slugify(ad.city)}/${slugify(ad.title)}-${ad.id}`
          : `https://reklamap.pl/powierzchnia-reklamowa/${mapTypeToUrlFormat(ad.type)}/${slugify(ad.city)}/${slugify(ad.title)}-${ad.id}`
      }))
    }
  })
}, { immediate: true, deep: true })

// Filters
const filters = ref({
  type: '',
  priceFrom: null as number | null,
  priceTo: null as number | null,
  priceUnit: 'month',
  widthFrom: null as number | null,
  widthTo: null as number | null,
  heightFrom: null as number | null,
  heightTo: null as number | null,
  surfaceFrom: null as number | null,
  surfaceTo: null as number | null,
  city: '',
  region: '',
  rentalPeriod: '',
  orientation: '',
  trafficIntensity: '',
  trafficDirection: '',
  trafficType: '',
  status: [] as string[],
  onlyWithImage: false,
  priceIncludesPrint: false,
  priceIncludesMounting: false,
  graphicDesignHelp: false,
  offerType: '',
  hasVatInvoice: false,
  hasBacklight: false,
  selectedLocationCoords: null as { lat: number; lng: number } | null,
  // Type-specific filters
  variant: '',
  roadClass: '',
  environment: '',
  // LED screen filters
  resolution: '',
  pixelPitchFrom: null as number | null,
  pixelPitchTo: null as number | null,
  brightnessFrom: null as number | null,
  brightnessTo: null as number | null,
  transportScope: '',
  vehicleCountFrom: null as number | null,
  vehicleCountTo: null as number | null,
  mobileExposureMode: '',
  campaignDurationFrom: null as number | null,
  campaignDurationTo: null as number | null,
  // Nowe pola dla rozszerzonych opcji
  lightingType: '' as string,
  dailyPassengersFrom: null as number | null,
  dailyPassengersTo: null as number | null,
  operatingZone: '' as string,
  ambientLightControl: false as boolean,
  // Checkboxy dla podświetlenia
  hasLightingTypeBanner: false as boolean,
  hasLightingTypeBillboard: false as boolean,
  // OTS filters
  estimatedDailyViewsFrom: null as number | null,
  estimatedDailyViewsTo: null as number | null,
})

// Lokalizacja - podobnie jak na stronie głównej
const locationQuery = ref('')
const tempLocationQuery = ref('')
const isLocationMenuOpen = ref(false)
const apiLocationResults = ref<LocationResult[]>([])
const isLoadingLocations = ref(false)


interface LocationSuggestion {
  type: 'region' | 'city'
  value: string
  label: string
  subtitle?: string
  coords?: { lat: number; lng: number }
  addresstype?: string
  osmType?: string
  osmClass?: string
}

const popularLocations: LocationSuggestion[] = [
  { type: 'city', value: 'Warszawa', label: 'Warszawa' },
  { type: 'city', value: 'Kraków', label: 'Kraków' },
  { type: 'city', value: 'Wrocław', label: 'Wrocław' },
  { type: 'city', value: 'Poznań', label: 'Poznań' },
  { type: 'city', value: 'Gdańsk', label: 'Gdańsk' },
]

const locationSuggestions = computed(() => {
  const currentQuery = showFiltersModal.value ? tempLocationQuery.value : locationQuery.value
  
  if (!currentQuery) {
    return popularLocations
  }

  const query = currentQuery.toLowerCase()
  const suggestions: LocationSuggestion[] = []

  // Filter regions from JSON (instant)
  const matchingRegions = polishLocations.voivodeships
    .filter(r => r.name.toLowerCase().includes(query))
    .map(r => ({ type: 'region' as const, value: r.id, label: r.name }))

  // Add API results (cities, towns, villages)
  const apiSuggestions = apiLocationResults.value
    .map(loc => {
      // Use state from Nominatim address
      const voivodeship = loc.state || ''
      
      // Extract detailed location from displayName
      const parts = loc.displayName.split(', ')
      let detailedLocation = ''
      
      if (parts.length >= 2) {
        // If first part is different from city name, it's a district/suburb
        if (parts[0] !== loc.name && parts[1] === loc.name) {
          detailedLocation = `${parts[0]}, ${loc.name}`
        } else {
          detailedLocation = loc.name
        }
      } else {
        detailedLocation = loc.name
      }
      
      // Construct subtitle with city if available and different from name
      let subtitleParts: string[] = []
      
      // Add city to subtitle if it exists, is different from the main name, 
      // and isn't already part of the detailed location label
      if (loc.city && loc.city !== loc.name && !detailedLocation.includes(loc.city)) {
        subtitleParts.push(loc.city)
      }
      
      if (voivodeship) {
        subtitleParts.push(voivodeship)
      }
      
      subtitleParts.push('Polska')
      
      return {
        type: 'city' as const,
        value: loc.name,
        label: detailedLocation,
        subtitle: subtitleParts.join(', '),
        coords: { lat: loc.lat, lng: loc.lng },
        addresstype: loc.addresstype,
        osmType: loc.osmType,
        osmClass: loc.osmClass
      }
    })

  // Deduplicate by city + state, preferring place/city over boundary
  const uniqueCities = new Map<string, LocationSuggestion>()
  apiSuggestions.forEach(suggestion => {
    // Create key with city name and voivodeship to show cities from different voivodeships
    const cityKey = `${suggestion.value}|${suggestion.subtitle?.split(', ').slice(-2)[0] || ''}`
    const existing = uniqueCities.get(cityKey)
    if (!existing) {
      uniqueCities.set(cityKey, suggestion)
    } else {
      // Calculate priority for current and existing
      // Priority: place/city > place/town > addresstype=city > others
      const getPriority = (s: LocationSuggestion) => {
        if (s.osmClass === 'place' && s.osmType === 'city') return 4
        if (s.osmClass === 'place' && s.osmType === 'town') return 3
        if (s.addresstype === 'city') return 2
        if (s.type === 'city') return 1
        return 0
      }
      
      const currentPriority = getPriority(suggestion)
      const existingPriority = getPriority(existing)
      
      if (currentPriority > existingPriority) {
        uniqueCities.set(cityKey, suggestion)
      }
    }
  })
  const deduplicatedSuggestions = Array.from(uniqueCities.values())

  suggestions.push(...matchingRegions, ...deduplicatedSuggestions)
  return suggestions.slice(0, 10)
})

const selectLocation = (suggestion: LocationSuggestion) => {
  if (showFiltersModal.value) {
    tempLocationQuery.value = suggestion.label
  } else {
    locationQuery.value = suggestion.label
  }
  
  const targetFilters = showFiltersModal.value ? tempFilters.value : filters.value
  
  if (suggestion.type === 'region') {
    // Find the matching region ID from polishLocations
    const matchingRegion = polishLocations.voivodeships.find(
      v => v.name === suggestion.label
    )
    targetFilters.region = matchingRegion?.id || suggestion.value
    targetFilters.city = ''
    targetFilters.selectedLocationCoords = null
  } else {
    targetFilters.city = suggestion.value
    targetFilters.region = ''
    // Store coordinates if available from API
    targetFilters.selectedLocationCoords = suggestion.coords || null
  }
  
  isLocationMenuOpen.value = false
}


const handleLocationFocus = () => {
  isLocationMenuOpen.value = true
}

const handleLocationBlur = () => {
  window.setTimeout(() => {
    isLocationMenuOpen.value = false
  }, 200)
}

const handleLocationInput = () => {
  const currentQuery = showFiltersModal.value ? tempLocationQuery.value : locationQuery.value
  
  // Trigger API search when user types
  if (currentQuery.length >= 2) {
    isLoadingLocations.value = true
    debouncedSearchLocations(currentQuery, (results) => {
      apiLocationResults.value = results
      isLoadingLocations.value = false
    })
  } else {
    apiLocationResults.value = []
  }
  
  const targetFilters = showFiltersModal.value ? tempFilters.value : filters.value
  
  // If user types custom text without selecting, treat as city search
  targetFilters.city = currentQuery
  targetFilters.region = ''
  targetFilters.selectedLocationCoords = null
}


const clearLocation = () => {
  if (showFiltersModal.value) {
    tempLocationQuery.value = ''
    if (tempFilters.value) {
      tempFilters.value.city = ''
      tempFilters.value.region = ''
      tempFilters.value.selectedLocationCoords = null
    }
  } else {
    locationQuery.value = ''
    filters.value.city = ''
    filters.value.region = ''
    filters.value.selectedLocationCoords = null
  }
  apiLocationResults.value = []
}


const typeColors: Record<string, string> = {
  billboard: '#EF4444',
  citylight: '#F59E0B',
  led_screen: '#10B981',
  banner: '#8B5CF6',
  wall: '#EC4899',
  totem: '#3B82F6',
  transport: '#14B8A6',
  mobile: '#F97316',
  other: '#6B7280'
}

const typeLabels: Record<string, string> = {
  billboard: 'Billboardy',
  citylight: 'Citylighty',
  led_screen: 'Ekrany LED',
  banner: 'Banery',
  wall: 'Ściany reklamowe',
  totem: 'Totemy reklamowe',
  transport: 'Reklama w transporcie',
  mobile: 'Reklama mobilna',
  other: 'Inne'
}

const getStatusLabel = (ad: Advertisement) => {
  let currentStatus = ad.display_status || ad.status
  
  // Jeśli status to soon_available, sprawdź czy data dostępności już minęła
  if (currentStatus === 'soon_available' && ad.available_from) {
    const availableDate = new Date(ad.available_from)
    const today = new Date()
    // Ustaw czas na początek dnia dla porównania
    today.setHours(0, 0, 0, 0)
    availableDate.setHours(0, 0, 0, 0)
    
    // Jeśli data dostępności to dzisiaj lub wcześniej, zmień status na active
    if (availableDate <= today) {
      currentStatus = 'active'
    }
  }
  
  switch (currentStatus) {
    case 'active':
      return 'Wolne'
    case 'reserved':
      return 'Zarezerwowane'
    case 'soon_available':
      return 'Wkrótce dostępne'
    default:
      return 'Nieznany'
  }
}

const getStatusColor = (ad: Advertisement) => {
  let currentStatus = ad.display_status || ad.status
  
  // Jeśli status to soon_available, sprawdź czy data dostępności już minęła
  if (currentStatus === 'soon_available' && ad.available_from) {
    const availableDate = new Date(ad.available_from)
    const today = new Date()
    // Ustaw czas na początek dnia dla porównania
    today.setHours(0, 0, 0, 0)
    availableDate.setHours(0, 0, 0, 0)
    
    // Jeśli data dostępności to dzisiaj lub wcześniej, zmień status na active
    if (availableDate <= today) {
      currentStatus = 'active'
    }
  }
  
  switch (currentStatus) {
    case 'active':
      return '#10B981'
    case 'reserved':
      return '#F59E0B'
    case 'soon_available':
      return '#3B82F6'
    default:
      return '#6B7280'
  }
}

const activeFiltersCount = computed(() => {
  let count = 0
  if (searchQuery.value || filters.value.city || filters.value.region || filters.value.selectedLocationCoords || locationQuery.value) count++
  if (filters.value.type) count++
  if (filters.value.priceFrom !== null) count++
  if (filters.value.priceTo !== null) count++
  if (filters.value.widthFrom !== null) count++
  if (filters.value.widthTo !== null) count++
  if (filters.value.heightFrom !== null) count++
  if (filters.value.heightTo !== null) count++
  if (filters.value.surfaceFrom !== null) count++
  if (filters.value.surfaceTo !== null) count++
  if (filters.value.rentalPeriod) count++
  if (filters.value.orientation) count++
  if (filters.value.trafficIntensity) count++
  if (filters.value.trafficDirection) count++
  if (filters.value.trafficType) count++
  if (filters.value.status && filters.value.status.length > 0) count++
  if (filters.value.onlyWithImage) count++
  if (filters.value.priceIncludesPrint) count++
  if (filters.value.priceIncludesMounting) count++
  if (filters.value.graphicDesignHelp) count++
  if (filters.value.offerType) count++
  if (filters.value.hasVatInvoice) count++
  if (filters.value.hasBacklight) count++
  // Type-specific filters
  if (filters.value.variant) count++
  if (filters.value.roadClass) count++
  if (filters.value.environment) count++
  if (filters.value.resolution) count++
  if (filters.value.pixelPitchFrom !== null) count++
  if (filters.value.pixelPitchTo !== null) count++
  if (filters.value.brightnessFrom !== null) count++
  if (filters.value.brightnessTo !== null) count++
  if (filters.value.transportScope) count++
  if (filters.value.vehicleCountFrom !== null) count++
  if (filters.value.vehicleCountTo !== null) count++
  if (filters.value.mobileExposureMode) count++
  if (filters.value.campaignDurationFrom !== null) count++
  if (filters.value.campaignDurationTo !== null) count++
  return count
})

// Computed properties for filter visibility based on selected ad type
// Type-specific filter visibility
const getVariantOptions = (type: string) => {
  switch (type) {
    case 'billboard':
      return [
        { value: 'standard', label: 'Jednostronny' },
        { value: 'two_sided', label: 'Dwustronny (back-to-back)' },
        { value: 'three_sided', label: 'Trójstronny (prismatron)' },
        { value: 'scrolling', label: 'Scrolling / Rolowany' }
      ]
    case 'citylight':
      return [
        { value: 'single_sided', label: 'Jednostronny' },
        { value: 'double_sided', label: 'Dwustronny' },
        { value: 'scrolling', label: 'Scrolling (rotacyjny)' },
        { value: 'digital', label: 'Cyfrowy (DOOH)' }
      ]
    case 'led_screen':
      return [
        { value: 'standard', label: 'Standardowy' },
        { value: 'interactive', label: 'Interaktywny' }
      ]
    case 'totem':
      return [
        { value: 'single_sided', label: 'Jednostronny' },
        { value: 'double_sided', label: 'Dwustronny' },
        { value: 'multi_sided', label: 'Wielostronny / Kolumna' },
        { value: 'pylon', label: 'Pylon (przy drodze)' },
        { value: 'digital', label: 'Cyfrowy (LED)' }
      ]
    case 'transport':
      return [
        { value: 'bus', label: 'Autobus' },
        { value: 'tram', label: 'Tramwaj' },
        { value: 'metro', label: 'Metro' },
        { value: 'train', label: 'Pociąg / SKM / Kolej' },
        { value: 'stop', label: 'Przystanek' }
      ]
    case 'mobile':
      return [
        { value: 'trailer', label: 'Przyczepka' },
        { value: 'car', label: 'Samochód' },
        { value: 'bike', label: 'Rower' },
        { value: 'other', label: 'Inna' }
      ]
    default:
      return []
  }
}

const getEnvironmentOptions = (type: string) => {
  switch (type) {
    case 'citylight':
      return [
        { value: 'indoor', label: 'Wewnątrz' },
        { value: 'outdoor', label: 'Na zewnątrz' }
      ]
    case 'led_screen':
      return [
        { value: 'indoor', label: 'Wewnątrz' },
        { value: 'outdoor', label: 'Na zewnątrz' },
        { value: 'event', label: 'Event / Wydarzenie' }
      ]
    case 'totem':
      return [
        { value: 'indoor', label: 'Wewnątrz' },
        { value: 'outdoor', label: 'Na zewnątrz' },
        { value: 'event', label: 'Event / Wydarzenie' }
      ]
    case 'mobile':
      return [
        { value: 'indoor', label: 'Wewnątrz' },
        { value: 'outdoor', label: 'Na zewnątrz' },
        { value: 'event', label: 'Event / Wydarzenie' }
      ]
    case 'other':
      return [
        { value: 'indoor', label: 'Wewnątrz' },
        { value: 'outdoor', label: 'Na zewnątrz' },
        { value: 'event', label: 'Event / Wydarzenie' }
      ]
    default:
      return []
  }
}

const getAvailablePriceUnits = (type: string) => {
  // Citylight - tylko miesiąc i m²
  if (type === 'citylight') {
    return [
      { value: 'month', label: 'za miesiąc' },
      { value: 'sqm', label: 'za m²' }
    ]
  }
  // Billboard - dzień, tydzień, miesiąc, rok, m²
  if (type === 'billboard') {
    return [
      { value: 'day', label: 'za dzień' },
      { value: 'week', label: 'za tydzień' },
      { value: 'month', label: 'za miesiąc' },
      { value: 'year', label: 'za rok' },
      { value: 'sqm', label: 'za m²' }
    ]
  }
  // Wall - miesiąc, rok, m²
  if (type === 'wall') {
    return [
      { value: 'month', label: 'za miesiąc' },
      { value: 'year', label: 'za rok' },
      { value: 'sqm', label: 'za m²' }
    ]
  }
  // Banner - dzień, tydzień, miesiąc, m²
  if (type === 'banner') {
    return [
      { value: 'day', label: 'za dzień' },
      { value: 'week', label: 'za tydzień' },
      { value: 'month', label: 'za miesiąc' },
      { value: 'sqm', label: 'za m²' }
    ]
  }
  // Ekran LED - dzień, miesiąc, kampania
  if (type === 'led_screen') {
    return [
      { value: 'day', label: 'za dzień' },
      { value: 'month', label: 'za miesiąc' },
      { value: 'campaign', label: 'za kampanię' }
    ]
  }
  // Transport - dzień, miesiąc, kampania
  if (type === 'transport') {
    return [
      { value: 'day', label: 'za dzień' },
      { value: 'month', label: 'za miesiąc' },
      { value: 'campaign', label: 'za kampanię' }
    ]
  }
  // Mobile - dzień i kampania
  if (type === 'mobile') {
    return [
      { value: 'day', label: 'za dzień' },
      { value: 'campaign', label: 'za kampanię' }
    ]
  }
  // Dla pozostałych typów z m²
  return [
    { value: 'day', label: 'za dzień' },
    { value: 'week', label: 'za tydzień' },
    { value: 'month', label: 'za miesiąc' },
    { value: 'year', label: 'za rok' },
    { value: 'sqm', label: 'za m²' }
  ]
}

const showEquipmentSection = computed(() => {
  const type = filters.value.type
  const showPrint = ['billboard', 'banner'].includes(type)
  const showMounting = ['billboard', 'banner', 'wall'].includes(type)
  const showGraphicDesign = ['billboard', 'banner', 'wall'].includes(type)
  return showPrint || showMounting || showGraphicDesign
})

const showEquipmentSectionInModal = computed(() => {
  if (!tempFilters.value) return false
  const type = tempFilters.value.type
  const showPrint = ['billboard', 'banner'].includes(type)
  const showMounting = ['billboard', 'banner', 'wall'].includes(type)
  const showGraphicDesign = ['billboard', 'banner', 'wall'].includes(type)
  const showBacklight = ['citylight', 'totem', 'led_screen', 'banner', 'wall', 'billboard'].includes(type)
  return showPrint || showMounting || showGraphicDesign || showBacklight
})

const filteredListings = computed(() => {
  let filtered = listings.value
  
  // Search query
  if (searchQuery.value) {
    const query = normalizePolishChars(searchQuery.value.toLowerCase())
    filtered = filtered.filter(ad => 
      normalizePolishChars(ad.title.toLowerCase()).includes(query) ||
      normalizePolishChars(ad.city.toLowerCase()).includes(query) ||
      normalizePolishChars(ad.location.toLowerCase()).includes(query)
    )
  }

  // Type filter
  if (filters.value.type) {
    filtered = filtered.filter(ad => ad.type === filters.value.type)
  }

  // Price filters
  if (filters.value.priceFrom !== null) {
    filtered = filtered.filter(ad => ad.price >= filters.value.priceFrom!)
  }
  if (filters.value.priceTo !== null) {
    filtered = filtered.filter(ad => ad.price <= filters.value.priceTo!)
  }

  // Width filters
  if (filters.value.widthFrom !== null) {
    // Konwertuj wartość filtru z mm na metry dla LED screens
    const widthFrom = filters.value.type === 'led_screen' ? filters.value.widthFrom / 1000 : filters.value.widthFrom
    filtered = filtered.filter(ad => ad.width >= widthFrom)
  }
  if (filters.value.widthTo !== null) {
    // Konwertuj wartość filtru z mm na metry dla LED screens
    const widthTo = filters.value.type === 'led_screen' ? filters.value.widthTo / 1000 : filters.value.widthTo
    filtered = filtered.filter(ad => ad.width <= widthTo)
  }

  // Height filters
  if (filters.value.heightFrom !== null) {
    // Konwertuj wartość filtru z mm na metry dla LED screens
    const heightFrom = filters.value.type === 'led_screen' ? filters.value.heightFrom / 1000 : filters.value.heightFrom
    filtered = filtered.filter(ad => ad.height >= heightFrom)
  }
  if (filters.value.heightTo !== null) {
    // Konwertuj wartość filtru z mm na metry dla LED screens
    const heightTo = filters.value.type === 'led_screen' ? filters.value.heightTo / 1000 : filters.value.heightTo
    filtered = filtered.filter(ad => ad.height <= heightTo)
  }

  // Surface area filters
  if (filters.value.surfaceFrom !== null) {
    filtered = filtered.filter(ad => {
      const surface = ad.width * ad.height
      return surface >= filters.value.surfaceFrom!
    })
  }
  if (filters.value.surfaceTo !== null) {
    filtered = filtered.filter(ad => {
      const surface = ad.width * ad.height
      return surface <= filters.value.surfaceTo!
    })
  }

  // Location filters
  if (filters.value.city) {
    // Normalizuj obie strony porównania używając slugify (usuwa polskie znaki)
    const normalizedFilterCity = slugify(filters.value.city)
    filtered = filtered.filter(ad => {
      const normalizedAdCity = slugify(ad.city)
      return normalizedAdCity.includes(normalizedFilterCity)
    })
  }
  if (filters.value.region) {
    filtered = filtered.filter(ad => ad.region === filters.value.region)
  }

  // Rental period filter
  if (filters.value.rentalPeriod) {
    filtered = filtered.filter(ad => ad.rental_period === filters.value.rentalPeriod)
  }

  // Orientation filter
  if (filters.value.orientation) {
    filtered = filtered.filter(ad => ad.orientation === filters.value.orientation)
  }

  // Traffic intensity filter
  if (filters.value.trafficIntensity) {
    filtered = filtered.filter(ad => ad.traffic_intensity === filters.value.trafficIntensity)
  }

  // Traffic direction filter
  if (filters.value.trafficDirection) {
    filtered = filtered.filter(ad => {
      if (!ad.traffic_direction) return false
      if (filters.value.trafficDirection === 'both') {
        return Array.isArray(ad.traffic_direction) && ad.traffic_direction.length === 2
      }
      return Array.isArray(ad.traffic_direction) && ad.traffic_direction.includes(filters.value.trafficDirection)
    })
  }

  // Traffic type filter
  if (filters.value.trafficType) {
    filtered = filtered.filter(ad => {
      if (!ad.traffic_type) return false
      if (filters.value.trafficType === 'both') {
        return Array.isArray(ad.traffic_type) && ad.traffic_type.length === 2
      }
      return Array.isArray(ad.traffic_type) && ad.traffic_type.includes(filters.value.trafficType)
    })
  }

  // Status filter
  if (filters.value.status && filters.value.status.length > 0) {
    filtered = filtered.filter(ad => filters.value.status.includes(ad.display_status || ad.status))
  }

  // Feature filters
  if (filters.value.onlyWithImage) {
    filtered = filtered.filter(ad => ad.has_image)
  }
  if (filters.value.priceIncludesPrint) {
    filtered = filtered.filter(ad => ad.price_includes_print === true)
  }
  if (filters.value.priceIncludesMounting) {
    filtered = filtered.filter(ad => ad.price_includes_mounting === true)
  }
  if (filters.value.graphicDesignHelp) {
    filtered = filtered.filter(ad => ad.graphic_design_help === true)
  }

  // Offer type filter
  if (filters.value.offerType) {
    filtered = filtered.filter(ad => ad.offer_type === filters.value.offerType)
  }

  // VAT invoice filter
  if (filters.value.hasVatInvoice) {
    filtered = filtered.filter(ad => ad.has_vat_invoice === true)
  }

  // Backlight filter
  if (filters.value.hasBacklight) {
    filtered = filtered.filter(ad => ad.has_backlight === true)
  }

  // Type-specific filters
  if (filters.value.variant) {
    filtered = filtered.filter(ad => ad.variant === filters.value.variant)
  }

  if (filters.value.roadClass) {
    filtered = filtered.filter(ad => ad.road_class === filters.value.roadClass)
  }

  if (filters.value.environment) {
    filtered = filtered.filter(ad => ad.environment === filters.value.environment)
  }

  // LED-specific filters
  if (filters.value.resolution) {
    filtered = filtered.filter(ad => ad.resolution && ad.resolution.toLowerCase().includes(filters.value.resolution.toLowerCase()))
  }
  if (filters.value.pixelPitchFrom !== null) {
    filtered = filtered.filter(ad => (ad as any).pixel_pitch && (ad as any).pixel_pitch >= filters.value.pixelPitchFrom!)
  }
  if (filters.value.pixelPitchTo !== null) {
    filtered = filtered.filter(ad => (ad as any).pixel_pitch && (ad as any).pixel_pitch <= filters.value.pixelPitchTo!)
  }
  if (filters.value.brightnessFrom !== null) {
    filtered = filtered.filter(ad => (ad as any).brightness && (ad as any).brightness >= filters.value.brightnessFrom!)
  }
  if (filters.value.brightnessTo !== null) {
    filtered = filtered.filter(ad => (ad as any).brightness && (ad as any).brightness <= filters.value.brightnessTo!)
  }

  // Transport-specific filters
  if (filters.value.transportScope) {
    filtered = filtered.filter(ad => ad.transport_scope === filters.value.transportScope)
  }
  if (filters.value.vehicleCountFrom !== null) {
    filtered = filtered.filter(ad => ad.vehicle_count && ad.vehicle_count >= filters.value.vehicleCountFrom!)
  }
  if (filters.value.vehicleCountTo !== null) {
    filtered = filtered.filter(ad => ad.vehicle_count && ad.vehicle_count <= filters.value.vehicleCountTo!)
  }

  // Mobile-specific filters
  if (filters.value.mobileExposureMode) {
    filtered = filtered.filter(ad => ad.mobile_exposure_mode === filters.value.mobileExposureMode)
  }

  // Campaign duration filter
  if (filters.value.campaignDurationFrom !== null) {
    filtered = filtered.filter(ad => ad.campaign_duration && ad.campaign_duration >= filters.value.campaignDurationFrom!)
  }
  if (filters.value.campaignDurationTo !== null) {
    filtered = filtered.filter(ad => ad.campaign_duration && ad.campaign_duration <= filters.value.campaignDurationTo!)
  }

  // Nowe pola dla rozszerzonych opcji
  if ((filters.value as any).lightingType) {
    filtered = filtered.filter(ad => (ad as any).lighting_type === (filters.value as any).lightingType)
  }
  if ((filters.value as any).dailyPassengersFrom !== null) {
    filtered = filtered.filter(ad => (ad as any).daily_passengers && (ad as any).daily_passengers >= (filters.value as any).dailyPassengersFrom!)
  }
  if ((filters.value as any).dailyPassengersTo !== null) {
    filtered = filtered.filter(ad => (ad as any).daily_passengers && (ad as any).daily_passengers <= (filters.value as any).dailyPassengersTo!)
  }
  if ((filters.value as any).operatingZone) {
    filtered = filtered.filter(ad => (ad as any).operating_zone === (filters.value as any).operatingZone)
  }
  if ((filters.value as any).ambientLightControl) {
    filtered = filtered.filter(ad => (ad as any).ambient_light_control === true)
  }
  if ((filters.value as any).hasLightingTypeBanner === true) {
    filtered = filtered.filter(ad => {
      // Tylko dla banerów i ścian
      if (!['banner', 'wall'].includes(ad.type)) return false
      const lightingType = (ad as any).lighting_type_banner
      return lightingType && lightingType !== 'none'
    })
  }

  // OTS filters logic
  if (filters.value.estimatedDailyViewsFrom !== null) {
    filtered = filtered.filter(ad => (ad as any).estimated_daily_views && (ad as any).estimated_daily_views >= filters.value.estimatedDailyViewsFrom!)
  }
  if (filters.value.estimatedDailyViewsTo !== null) {
    filtered = filtered.filter(ad => (ad as any).estimated_daily_views && (ad as any).estimated_daily_views <= filters.value.estimatedDailyViewsTo!)
  }
  if ((filters.value as any).hasLightingTypeBillboard === true) {
    filtered = filtered.filter(ad => {
      // Tylko dla billboardów
      if (ad.type !== 'billboard') return false
      const lightingType = (ad as any).lighting_type
      return lightingType && lightingType !== 'none'
    })
  }

  // Sortowanie
  const sorted = [...filtered]

  switch (sortBy.value) {
    case 'newest':
      sorted.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
      break
    case 'oldest':
      sorted.sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime())
      break
    case 'name-asc':
      sorted.sort((a, b) => a.title.localeCompare(b.title, 'pl'))
      break
    case 'name-desc':
      sorted.sort((a, b) => b.title.localeCompare(a.title, 'pl'))
      break
    case 'price-day-asc':
      priceDisplay.value = 'day'
      sorted.sort((a, b) => getPrice(a, 'day') - getPrice(b, 'day'))
      break
    case 'price-day-desc':
      priceDisplay.value = 'day'
      sorted.sort((a, b) => {
        const priceA = getPrice(a, 'day')
        const priceB = getPrice(b, 'day')
        if (priceA === Number.MAX_SAFE_INTEGER && priceB === Number.MAX_SAFE_INTEGER) return 0
        if (priceA === Number.MAX_SAFE_INTEGER) return 1
        if (priceB === Number.MAX_SAFE_INTEGER) return -1
        return priceB - priceA
      })
      break
    case 'price-week-asc':
      priceDisplay.value = 'week'
      sorted.sort((a, b) => getPrice(a, 'week') - getPrice(b, 'week'))
      break
    case 'price-week-desc':
      priceDisplay.value = 'week'
      sorted.sort((a, b) => {
        const priceA = getPrice(a, 'week')
        const priceB = getPrice(b, 'week')
        if (priceA === Number.MAX_SAFE_INTEGER && priceB === Number.MAX_SAFE_INTEGER) return 0
        if (priceA === Number.MAX_SAFE_INTEGER) return 1
        if (priceB === Number.MAX_SAFE_INTEGER) return -1
        return priceB - priceA
      })
      break
    case 'price-month-asc':
      priceDisplay.value = 'month'
      sorted.sort((a, b) => getPrice(a, 'month') - getPrice(b, 'month'))
      break
    case 'price-month-desc':
      priceDisplay.value = 'month'
      sorted.sort((a, b) => {
        const priceA = getPrice(a, 'month')
        const priceB = getPrice(b, 'month')
        if (priceA === Number.MAX_SAFE_INTEGER && priceB === Number.MAX_SAFE_INTEGER) return 0
        if (priceA === Number.MAX_SAFE_INTEGER) return 1
        if (priceB === Number.MAX_SAFE_INTEGER) return -1
        return priceB - priceA
      })
      break
    case 'price-year-asc':
      priceDisplay.value = 'year'
      sorted.sort((a, b) => getPrice(a, 'year') - getPrice(b, 'year'))
      break
    case 'price-year-desc':
      priceDisplay.value = 'year'
      sorted.sort((a, b) => {
        const priceA = getPrice(a, 'year')
        const priceB = getPrice(b, 'year')
        if (priceA === Number.MAX_SAFE_INTEGER && priceB === Number.MAX_SAFE_INTEGER) return 0
        if (priceA === Number.MAX_SAFE_INTEGER) return 1
        if (priceB === Number.MAX_SAFE_INTEGER) return -1
        return priceB - priceA
      })
      break
    case 'price-sqm-asc':
      priceDisplay.value = 'sqm'
      sorted.sort((a, b) => getPrice(a, 'sqm') - getPrice(b, 'sqm'))
      break
    case 'price-sqm-desc':
      priceDisplay.value = 'sqm'
      sorted.sort((a, b) => {
        const priceA = getPrice(a, 'sqm')
        const priceB = getPrice(b, 'sqm')
        if (priceA === Number.MAX_SAFE_INTEGER && priceB === Number.MAX_SAFE_INTEGER) return 0
        if (priceA === Number.MAX_SAFE_INTEGER) return 1
        if (priceB === Number.MAX_SAFE_INTEGER) return -1
        return priceB - priceA
      })
      break
    case 'price-campaign-asc':
      priceDisplay.value = 'campaign'
      sorted.sort((a, b) => getPrice(a, 'campaign') - getPrice(b, 'campaign'))
      break
    case 'price-campaign-desc':
      priceDisplay.value = 'campaign'
      sorted.sort((a, b) => {
        const priceA = getPrice(a, 'campaign')
        const priceB = getPrice(b, 'campaign')
        if (priceA === Number.MAX_SAFE_INTEGER && priceB === Number.MAX_SAFE_INTEGER) return 0
        if (priceA === Number.MAX_SAFE_INTEGER) return 1
        if (priceB === Number.MAX_SAFE_INTEGER) return -1
        return priceB - priceA
      })
      break
    default:
      priceDisplay.value = null
  }

  return sorted
})

const totalPages = computed(() => {
  return Math.ceil(filteredListings.value.length / itemsPerPage)
})

// Pobierz ogłoszenia dla aktualnej strony
const getCurrentPageAds = () => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredListings.value.slice(start, end)
}

// Obsługa zmiany strony
const onPageChange = (page: number) => {
  currentPage.value = page
  router.push({ query: { ...route.query, page: page.toString() } })
  
  // Scroll to top of ads list
  const adsListContainer = document.querySelector('.ads-list-container')
  if (adsListContainer) {
    adsListContainer.scrollTop = 0
  }
}

const statusLabel = computed(() => {
  if (filters.value.status.length === 0) return 'Wszystkie'
  if (filters.value.status.length === 3) return 'Wszystkie'
  
  const labels: string[] = []
  const map: Record<string, string> = { 
    active: 'Wolne', 
    reserved: 'Zarezerwowane', 
    soon: 'Wkrótce dostępne' 
  }
  
  for (const s of filters.value.status) {
    if (map[s]) labels.push(map[s])
  }
  
  if (labels.length <= 1) return labels.join(', ')
  return `Wybrano (${labels.length})`
})

const transportScopeOptions = computed(() => {
  // Użyj tempFilters jeśli modal jest otwarty, inaczej użyj filters
  const variant = tempFilters.value?.variant || filters.value.variant
  
  // Dla przystanku (stop) - tylko opcje wewnętrzna i zewnętrzna
  if (variant === 'stop') {
    return [
      { value: 'internal', label: 'Wewnętrzna' },
      { value: 'external', label: 'Zewnętrzna' }
    ]
  }
  // Dla pozostałych wariantów (bus, tram, metro) - wszystkie opcje
  return [
    { value: 'internal', label: 'Wewnętrzna' },
    { value: 'external', label: 'Zewnętrzna' },
    { value: 'full_vehicle', label: 'Całopojazdowa' }
  ]
})

// Funkcja do formatowania ceny w zależności od wybranego sortowania
const getFormattedPrice = (ad: Advertisement) => {
  const displayUnit = priceDisplay.value || ad.price_unit || 'month'
  const price = getPrice(ad, displayUnit as any)
  let suffix = ''
  
  switch (displayUnit) {
    case 'day':
      suffix = ' zł/dzień'
      break
    case 'week':
      suffix = ' zł/tydzień'
      break
    case 'month':
      suffix = ' zł/mies.'
      break
    case 'year':
      suffix = ' zł/rok'
      break
    case 'sqm':
      suffix = ' zł/m²'
      break
  }
  
  return price.toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + suffix
}

// Helper function to check if price is estimated for a specific ad
const isEstimatedPrice = (ad: Advertisement) => {
  // If priceDisplay is null, use original ad.price_unit (not estimated)
  if (priceDisplay.value === null) {
    return false
  }
  const displayUnit = priceDisplay.value || ad.price_unit || 'month'
  const adPriceUnit = ad.price_unit || 'month'
  return displayUnit !== adPriceUnit
}

// Helper function to check if data is missing for a specific ad
const isMissingData = (ad: Advertisement) => {
  const displayUnit = priceDisplay.value || ad.price_unit || 'month'
  
  if (displayUnit === 'sqm') {
    const area = ad.width && ad.height ? ad.width * ad.height : 0
    return area === 0
  }
  
  if (displayUnit === 'campaign') {
    return !ad.campaign_duration
  }
  
  return false
}

// Funkcja do pobierania etykiety okresu cenowego
const getPriceLabel = (period: 'day' | 'week' | 'month' | 'year' | 'sqm' | 'campaign', ad?: Advertisement) => {
  switch (period) {
    case 'day':
      return '/dzień'
    case 'week':
      return '/tydzień'
    case 'month':
      return '/miesiąc'
    case 'year':
      return '/rok'
    case 'campaign':
      // For campaign, add duration in days if available
      if (ad && ad.campaign_duration) {
        return `/kampanię (${ad.campaign_duration} dni)`
      }
      return '/kampanię'
    case 'sqm':
      return '/m²'
    default:
      return '/miesiąc'
  }
}

const handleClickOutside = (event: MouseEvent) => {
  if (statusMultiselect.value && !statusMultiselect.value.contains(event.target as Node)) {
    isStatusMenuOpen.value = false
  }
}


// Funkcja otwierająca modal z kopiowaniem aktualnych filtrów
const openFiltersModal = () => {
  // Skopiuj aktualne filtry do tymczasowych
  tempFilters.value = JSON.parse(JSON.stringify(filters.value))
  tempLocationQuery.value = locationQuery.value
  showFiltersModal.value = true
}


// Funkcja zamykająca modal bez stosowania zmian
const closeFiltersModal = () => {
  showFiltersModal.value = false
  tempFilters.value = null
}

// Funkcja stosująca filtry z modalu
const applyFilters = () => {
  if (!tempFilters.value) return
  
  // Ustaw flagę resetowania
  isResettingFilters.value = true

  
  // Zastosuj tymczasowe filtry
  filters.value = JSON.parse(JSON.stringify(tempFilters.value))
  locationQuery.value = tempLocationQuery.value
  
  // Jeśli użytkownik wpisał cenę, ustaw priceDisplay na tę jednostkę
  // Aby wyniki były przełączone na tę jednostkę (jak przy sortowaniu)
  if ((tempFilters.value.priceFrom !== null || tempFilters.value.priceTo !== null) && tempFilters.value.priceUnit) {
    priceDisplay.value = tempFilters.value.priceUnit as 'day' | 'week' | 'month' | 'year' | 'sqm' | 'campaign'
  }
  
  // Zamknij modal
  showFiltersModal.value = false
  tempFilters.value = null
  tempLocationQuery.value = ''

  
  // Reset to page 1
  currentPage.value = 1
  
  // Konwertuj filtry na query params
  const queryParams = filtersToQueryParams({
    ...filters.value,
    keyword: searchQuery.value
  })
  
  // Dodaj parametr sortowania
  if (sortBy.value !== 'newest') {
    queryParams.sort = sortBy.value
  }
  
  // Dodaj parametr strony
  queryParams.page = '1'
  
  // Aktualizuj URL z nowymi parametrami
  router.push({ query: queryParams }).then(() => {
    // Zapisz filtry do localStorage
    saveLastSearch()
    
    setTimeout(() => {
      isResettingFilters.value = false
    }, 100)
  })
}

const clearFilters = () => {
  // Ustaw flagę resetowania
  isResettingFilters.value = true
  
  // Wyczyść wszystkie filtry
  filters.value = {
    type: '',
    priceFrom: null,
    priceTo: null,
    priceUnit: 'month',
    widthFrom: null,
    widthTo: null,
    heightFrom: null,
    heightTo: null,
    surfaceFrom: null,
    surfaceTo: null,
    city: '',
    region: '',
    rentalPeriod: '',
    orientation: '',
    trafficIntensity: '',
    trafficDirection: '',
    trafficType: '',
    status: [],
    onlyWithImage: false,
    priceIncludesPrint: false,
    priceIncludesMounting: false,
    graphicDesignHelp: false,
    offerType: '',
    hasVatInvoice: false,
    hasBacklight: false,
    selectedLocationCoords: null,
    // Type-specific filters
    variant: '',
    roadClass: '',
    environment: '',
    // LED screen filters
    resolution: '',
    pixelPitchFrom: null,
    pixelPitchTo: null,
    brightnessFrom: null,
    brightnessTo: null,
    transportScope: '',
    vehicleCountFrom: null,
    vehicleCountTo: null,
    mobileExposureMode: '',
    campaignDurationFrom: null,
    campaignDurationTo: null,
    // Nowe pola dla rozszerzonych opcji
    lightingType: '',
    dailyPassengersFrom: null,
    dailyPassengersTo: null,
    operatingZone: '',
    ambientLightControl: false,
    // Checkboxy dla podświetlenia
    hasLightingTypeBanner: false,
    hasLightingTypeBillboard: false,
    estimatedDailyViewsFrom: null,
    estimatedDailyViewsTo: null,
  }
  
  // Wyczyść wyszukiwane słowo kluczowe i lokalizację
  searchQuery.value = ''
  locationQuery.value = ''
  
  // Resetuj sortowanie
  sortBy.value = 'newest'
  
  // Zamknij modal
  showFiltersModal.value = false
  tempFilters.value = null
  
  // Usuń zapisane wyszukiwanie
  try {
    localStorage.removeItem(LAST_SEARCH_KEY)
  } catch (error) {
    console.error('Error clearing search filters:', error)
  }
  
  // Przekieruj na stronę główną powierzchni reklamowych (bez filtrów)
  router.push('/powierzchnie-reklamowe').then(() => {
    setTimeout(() => {
      isResettingFilters.value = false
    }, 100)
  })
}
const createCustomIcon = (type: string, isHovered: boolean = false, isSelected: boolean = false) => {
  const color = typeColors[type] || '#6B7280'
  const scale = isHovered || isSelected ? 1.3 : 1
  const zIndex = isHovered || isSelected ? 1000 : 500
  
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

const initMap = () => {
  if (!mapContainer.value) return

  // Współrzędne centrum Polski
  const polandCenter: [number, number] = [52.0, 19.0]
  
  // Granice Polski (przybliżone) - z marginesem
  const polandBounds = L.latLngBounds(
    [48.5, 13.5],  // południowo-zachodni róg (z marginesem)
    [55.5, 24.5]   // północno-wschodni róg (z marginesem)
  )
  
  // Tworzymy mapę z widokiem na całą Polskę i ograniczeniami
  map = L.map(mapContainer.value, {
    maxBounds: polandBounds,        // Nie można przesunąć mapy poza te granice
    maxBoundsViscosity: 1.0,        // Twarde ograniczenie (nie można przeciągnąć poza)
    minZoom: 5,                      // Minimalne przybliżenie (cała Polska + więcej)
    maxZoom: 18,                     // Maksymalne przybliżenie
    scrollWheelZoom: false,          // Disable scroll wheel zoom until activated
    dragging: !isMobile.value,       // Disable dragging on mobile until activated
    touchZoom: false,                // Disable touch zoom until activated
    doubleClickZoom: false           // Disable double click zoom until activated
  }).setView(polandCenter, 6)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map)
  
  // Function to enable all interactions
  const enableMapInteractions = () => {
    if (!map) return
    
    map.scrollWheelZoom.enable()
    map.dragging.enable()
    map.touchZoom.enable()
    map.doubleClickZoom.enable()
    
    isMapActive.value = true
  }

  // Enable interactions on click and hide hint
  map.on('click', () => {
    if (map && !isMapActive.value) {
      enableMapInteractions()
    }
  })
  
  // Ustaw widok na całą Polskę
  map.fitBounds(polandBounds)

  updateMarkers()
}

const updateMarkers = () => {
  if (!map) return

  // Clear existing markers
  markers.forEach(marker => marker.remove())
  markers.clear()

  filteredListings.value.forEach((ad) => {
    const marker = L.marker([ad.latitude, ad.longitude], {
      icon: createCustomIcon(ad.type, hoveredAdId.value === ad.id, selectedAdId.value === ad.id)
    })

    const citySlug = slugify(ad.city)
    const titleSlug = slugify(ad.title)
    const adUrl = `/ogloszenie/${citySlug}/${titleSlug}/${ad.id}`

    const imageUrl = ad.image_url ? getFullImageUrl(ad.image_url) : ''
    
    marker.bindPopup(`
      <div style="width: 250px;">
        <a href="${adUrl}" style="text-decoration: none; color: inherit; display: block;">
          ${imageUrl ? `
            <div style="margin: -20px -20px 12px -20px; overflow: hidden; border-radius: 12px 12px 0 0;">
              <img src="${imageUrl}" alt="${ad.title}" style="width: 100%; height: 140px; object-fit: cover; display: block;" />
            </div>
          ` : ''}
          <h3 style="margin: 0 0 8px 0; font-size: 1.1rem; font-weight: 700; color: #1F2937;">
            ${ad.title}
          </h3>
          <div style="color: #6B7280; font-size: 0.9rem; margin-bottom: 8px;">
            📍 ${formatLocation(ad.location, ad.city)}
          </div>
          <div style="font-weight: 700; color: #4F46E5; font-size: 1.1rem;">
            ${Math.round(ad.price).toLocaleString('pl-PL')} ${getPriceUnitLabel(ad)}
          </div>
        </a>
      </div>
    `, { 
      maxWidth: 250,
      autoPan: true,    // Automatyczne przesunięcie mapy, aby popup był widoczny
      autoPanPadding: [50, 50]  // Padding przy autopan
    })

    marker.on('click', () => {
      // On mobile, prevent popup from opening until map is activated
      if (isMobile.value && !isMapActive.value) {
        // Activate map interactions on first marker click
        if (map) {
          map.scrollWheelZoom.enable()
          map.dragging.enable()
          map.touchZoom.enable()
          map.doubleClickZoom.enable()
          isMapActive.value = true
        }
        // Don't open popup on first click, wait for second click
        return
      }
      
      selectedAdId.value = ad.id
      scrollToAd(ad.id)
    })

    marker.on('mouseover', () => {
      marker.setIcon(createCustomIcon(ad.type, true, selectedAdId.value === ad.id))
    })

    marker.on('mouseout', () => {
      if (hoveredAdId.value !== ad.id) {
        marker.setIcon(createCustomIcon(ad.type, false, selectedAdId.value === ad.id))
      }
    })

    marker.addTo(map!)
    markers.set(ad.id, marker)
  })

  // Fit bounds if there are markers and active filters
  if (markers.size > 0 && activeFiltersCount.value > 0) {
    const group = new L.FeatureGroup(Array.from(markers.values()))
    map.fitBounds(group.getBounds(), { padding: [50, 50], maxZoom: 12 })
  } else if (markers.size > 0 && !map.getBounds().equals(L.latLngBounds([[0,0],[0,0]]))) {
    // Jeśli nie ma filtrów i mapa już ma ustawione granice, nie zmieniaj widoku
    // Pozwala to zachować widok całej Polski przy pierwszym ładowaniu
  }
}

const handleAdHover = (adId: string | null) => {
  hoveredAdId.value = adId
  
  if (adId && markers.has(adId)) {
    const ad = listings.value.find(a => a.id === adId)
    if (ad) {
      const marker = markers.get(adId)!
      marker.setIcon(createCustomIcon(ad.type, true, selectedAdId.value === adId))
    }
  }
  
  // Reset other markers
  markers.forEach((marker, id) => {
    if (id !== adId) {
      const ad = listings.value.find(a => a.id === id)
      if (ad) {
        marker.setIcon(createCustomIcon(ad.type, false, selectedAdId.value === id))
      }
    }
  })
}

const handleAdLeave = () => {
  handleAdHover(null)
}

const favoritesRefresh = ref(0)
const comparisonRefresh = ref(0)

const isFavorite = (id: string) => {
  favoritesRefresh.value // Dependency to trigger reactivity
  const favorites = JSON.parse(localStorage.getItem('favorites') || '[]')
  return favorites.includes(id)
}

const isInComparison = (id: string) => {
  comparisonRefresh.value // Dependency to trigger reactivity
  const comparison = JSON.parse(localStorage.getItem('comparison') || '[]')
  return comparison.includes(id)
}

const handleStorageChange = () => {
  favoritesRefresh.value++
  comparisonRefresh.value++
}

const handleAdClick = (adId: string) => {
  selectedAdId.value = adId
  
  if (markers.has(adId) && map) {
    const marker = markers.get(adId)!
    const latLng = marker.getLatLng()
    map.setView(latLng, 13, { animate: true })
    marker.openPopup()
  }
}

const scrollToAd = (adId: string) => {
  const element = document.getElementById(`ad-${adId}`)
  const container = document.querySelector('.listings-list-container')
  if (element && container) {
    // Przewijamy tylko kontener z ogłoszeniami, a nie całą stronę
    // Obliczamy pozycję elementu względem kontenera
    const containerRect = container.getBoundingClientRect()
    const elementRect = element.getBoundingClientRect()
    const relativeTop = elementRect.top - containerRect.top
    
    // Przewijamy kontener, aby element był widoczny na środku
    container.scrollBy({
      top: relativeTop - (containerRect.height / 2) + (elementRect.height / 2),
      behavior: 'smooth'
    })
  }
}

const loadListings = async () => {
  try {
    isLoading.value = true
    const data = await api.getAdvertisements()
    // Backend returns only active advertisements
    listings.value = data
      .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
    
    setTimeout(() => updateMarkers(), 100)
  } catch (error) {
    console.error('Error loading advertisements:', error)
  } finally {
    isLoading.value = false
  }
}

// Watch for URL query parameter changes
watch(() => route.query, (newQuery, oldQuery) => {
  // Skip na początkowym załadowaniu - filtry będą ustawione w onMounted
  if (!isInitialized.value) {
    return
  }
  
  // Jeśli query nie zmienił się faktycznie, nie rób nic
  if (JSON.stringify(newQuery) === JSON.stringify(oldQuery)) {
    return
  }
  
  // Jeśli query params są puste (breadcrumb navigation), resetuj filtry do wartości z route.params
  if (Object.keys(newQuery).length === 0) {
    isResettingFilters.value = true
    
    // Resetuj wszystkie filtry do domyślnych wartości
    filters.value = {
      type: '',
      priceFrom: null,
      priceTo: null,
      priceUnit: 'month',
      widthFrom: null,
      widthTo: null,
      heightFrom: null,
      heightTo: null,
      surfaceFrom: null,
      surfaceTo: null,
      city: '',
      region: '',
      rentalPeriod: '',
      orientation: '',
      trafficIntensity: '',
      trafficDirection: '',
      trafficType: '',
      status: [],
      onlyWithImage: false,
      priceIncludesPrint: false,
      priceIncludesMounting: false,
      graphicDesignHelp: false,
      offerType: '',
      hasVatInvoice: false,
      hasBacklight: false,
      selectedLocationCoords: null,
      // Type-specific filters
      variant: '',
      roadClass: '',
      environment: '',
      // LED screen filters
      resolution: '',
      pixelPitchFrom: null,
      pixelPitchTo: null,
      brightnessFrom: null,
      brightnessTo: null,
      transportScope: '',
      vehicleCountFrom: null,
      vehicleCountTo: null,
      mobileExposureMode: '',
      campaignDurationFrom: null,
      campaignDurationTo: null,
      // Nowe pola dla rozszerzonych opcji
      lightingType: '',
      dailyPassengersFrom: null,
      dailyPassengersTo: null,
      operatingZone: '',
      ambientLightControl: false,
      hasLightingTypeBanner: false,
      hasLightingTypeBillboard: false,
      estimatedDailyViewsFrom: null,
      estimatedDailyViewsTo: null,
    }
    
    // Zastosuj filtry z route.params (type i city)
    if (route.params.type) {
      const typeMapping: Record<string, string> = {
        'billboardy': 'billboard',
        'citylighty': 'citylight',
        'ekrany-led': 'led_screen',
        'banery': 'banner',
        'sciany-reklamowe': 'wall',
        'totemy-reklamowe': 'totem',
        'reklama-w-transporcie': 'transport',
        'reklama-mobilna': 'mobile',
        'inne': 'other'
      }
      const type = typeMapping[route.params.type as string] || ''
      if (type) {
        filters.value.type = type
      }
    }
    
    if (route.params.city) {
      const citySlug = route.params.city as string
      const city = deslugify(citySlug)
      filters.value.city = city
      locationQuery.value = city
    } else {
      locationQuery.value = ''
    }
    
    // Resetuj search query i sortowanie
    searchQuery.value = ''
    sortBy.value = 'newest'
    currentPage.value = 1
    
    // Resetuj flagę po krótkiej chwili
    setTimeout(() => {
      isResettingFilters.value = false
    }, 100)
    
    return
  }
  
  // Aktualizuj numer strony
  const page = parseInt(newQuery.page as string) || 1
  if (page !== currentPage.value && page >= 1 && page <= totalPages.value) {
    currentPage.value = page
  }
  
  // Aktualizuj sortowanie
  if (newQuery.sort && newQuery.sort !== sortBy.value) {
    sortBy.value = newQuery.sort as string
  }
  
  // Aktualizuj wyszukiwane słowo kluczowe
  if (newQuery.q && newQuery.q !== searchQuery.value) {
    searchQuery.value = newQuery.q as string
  }
  
  // Aktualizuj filtry na podstawie query params
  const queryFilters = queryParamsToFilters(newQuery as Record<string, string>)
  
  // Aktualizuj tylko jeśli są różnice w filtrach
  if (JSON.stringify(queryFilters) !== JSON.stringify(filters.value)) {
    // Ustaw tylko niepuste wartości, aby nie nadpisywać domyślnych
    Object.entries(queryFilters).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '' && 
          (Array.isArray(value) ? value.length > 0 : true) &&
          key !== 'keyword') { // Pomijamy keyword, bo jest obsługiwane przez searchQuery
        // @ts-ignore - dynamiczny dostęp do właściwości
        filters.value[key] = value
      }
    })
  }
}, { immediate: true })

// Usunięto watch - filtry aktualizują się tylko po kliknięciu "Zastosuj"

watch(() => filteredListings.value, () => {
  updateMarkers()
})

// Block body scroll when modal or panel is open
watch(() => showFiltersModal.value, (isOpen) => {
  if (isOpen) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

watch(() => showSortPanel.value, (isOpen) => {
  if (isOpen) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

onMounted(async () => {
  // Załaduj dane NAJPIERW
  await loadListings()
  
  checkIfMobile()
  window.addEventListener('resize', checkIfMobile)
  window.addEventListener('scroll', handleScroll)
  handleScroll() // Initial check
  
  // Initialize map only on desktop or if mobile and map is shown
  if (!isMobile.value) {
    setTimeout(() => initMap(), 100)
  }
  
  document.addEventListener('click', handleClickOutside)
  
  // Listen to localStorage changes
  if (typeof window !== 'undefined') {
    window.addEventListener('localStorageChange', handleStorageChange)
    window.addEventListener('storage', handleStorageChange)
  }
  
  // 1. Najpierw załaduj filtry z localStorage (jako baza)
  try {
    const saved = localStorage.getItem(LAST_SEARCH_KEY)
    if (saved) {
      const lastSearch = JSON.parse(saved)
      
      // Ustaw searchQuery jeśli jest keyword w zapisanych filtrach
      if (lastSearch.keyword) {
        searchQuery.value = lastSearch.keyword
      }
      
      // Ustaw lokalizację do wyświetlenia w polu tekstowym
      if (lastSearch.city) {
        locationQuery.value = lastSearch.city
      } else if (lastSearch.region) {
        const region = polishLocations.voivodeships.find(v => v.id === lastSearch.region)
        if (region) {
          locationQuery.value = region.name
        }
      }
      
      // Scal filtry
      filters.value = { ...filters.value, ...lastSearch }
      
      // Jeśli był zapisany priceDisplayUnit, ustaw go
      if (lastSearch._priceDisplayUnit) {
        priceDisplay.value = lastSearch._priceDisplayUnit
      }
    }
  } catch (error) {
    console.error('Error loading search from localStorage:', error)
  }

  // 2. Nadpisz parametrami z URL path (type i city mają najwyższy priorytet ścieżki)
  if (route.params.type) {
    const typeMapping: Record<string, string> = {
      'billboardy': 'billboard',
      'citylighty': 'citylight',
      'ekrany-led': 'led_screen',
      'banery': 'banner',
      'sciany-reklamowe': 'wall',
      'totemy-reklamowe': 'totem',
      'reklama-w-transporcie': 'transport',
      'reklama-mobilna': 'mobile',
      'inne': 'other'
    }
    
    const type = typeMapping[route.params.type as string] || ''
    if (type) {
      filters.value.type = type
    }
  }
  
  if (route.params.city) {
    const citySlug = route.params.city as string
    const city = deslugify(citySlug)
    filters.value.city = city
    locationQuery.value = city
  }
  
  // 3. Nadpisz parametrami z URL query
  if (Object.keys(route.query).length > 0) {
    const queryFilters = queryParamsToFilters(route.query as Record<string, string>)
    
    if (queryFilters.keyword) {
      searchQuery.value = queryFilters.keyword
      delete queryFilters.keyword
    }
    
    if (route.query.sort) {
      sortBy.value = route.query.sort as string
    }
    
    // Nadpisz lokalizację z query tylko jeśli nie ma jej w path
    if (!route.params.city) {
      if (queryFilters.city) {
        locationQuery.value = queryFilters.city
      } else if (queryFilters.region) {
        const region = polishLocations.voivodeships.find(v => v.id === queryFilters.region)
        if (region) {
          locationQuery.value = region.name
        }
      }
    }
    
    // Połącz, ale zachowaj priorytet ścieżki dla typu i miasta
    const mergedFilters = { ...queryFilters }
    if (route.params.type && filters.value.type) {
      delete mergedFilters.type
    }
    if (route.params.city && filters.value.city) {
      delete mergedFilters.city
    }
    
    filters.value = { ...filters.value, ...mergedFilters }
  }
  
  // Oznacz że inicjalizacja jest ukończona
  isInitialized.value = true

  // Zapisz zainicjalizowane filtry (np. z URL) do localStorage
  saveLastSearch()

  // Logic for showing the search alert modal after 20 seconds
  if (!hasShownAlertModal.value && activeFiltersCount.value > 0) {
    setTimeout(() => {
      // Check again if we haven't shown it yet in this session
      if (!hasShownAlertModal.value) {
        showSearchAlertModal.value = true
        hasShownAlertModal.value = true
        localStorage.setItem('search_alert_shown', 'true')
      }
    }, 20000) // 20 seconds
  }
})

const handleSearchAlertSubmit = (email: string) => {
  console.log('Saving alert for:', email, filters.value)
  // Here we would call the API to save the alert
}


onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('scroll', handleScroll)
  
  // Reset body overflow in case modal was open
  document.body.style.overflow = ''
  
  // Remove localStorage listeners
  if (typeof window !== 'undefined') {
    window.removeEventListener('localStorageChange', handleStorageChange)
    window.removeEventListener('storage', handleStorageChange)
  }
})
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
            v-model="searchQuery" 
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
          <span v-if="activeFiltersCount > 0" class="filter-badge">{{ activeFiltersCount }}</span>
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
            v-model="searchQuery" 
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
          <span v-if="activeFiltersCount > 0" class="mobile-filter-badge">{{ activeFiltersCount }}</span>
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
      <div class="listings-list-container" :class="{ 'mobile-hidden': isMobile && showMapOnMobile }">
        <div v-if="isLoading" class="loading-state">
          <div class="spinner"></div>
          <p>Ładowanie ogłoszeń...</p>
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
            v-if="activeFiltersCount > 0"
            :location-label="locationQuery || (route.params.city ? deslugify(route.params.city as string) : '')"
            :ad-type-label="filters && filters.type ? getTypeLabel(filters.type) : 'ogłoszenie'"
            @click="showSearchAlertModal = true"
          />

        </div>


        <div v-else class="listings-list" :class="viewMode">
          <router-link
            v-for="ad in getCurrentPageAds()"
            :key="ad.id"
            :to="`/powierzchnia-reklamowa/${mapTypeToUrlFormat(ad.type)}/${slugify(ad.city)}/${slugify(ad.title)}-${ad.id}`"
            class="listing-card"
            :class="{ hovered: hoveredAdId === ad.id, selected: selectedAdId === ad.id }"
            @mouseenter="handleAdHover(ad.id)"
            @mouseleave="handleAdLeave()"
          >
            <div class="card-image">
              <img 
                v-if="ad.image_url" 
                :src="getFullImageUrl(ad.image_url)" 
                :alt="ad.title"
              />
              <div v-else class="no-image-placeholder">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                  <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                  <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                  <path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span>Brak zdjęcia</span>
              </div>
              <div class="card-badge" :style="{ background: typeColors[ad.type] || '#6B7280' }">
                {{ typeLabels[ad.type] || ad.type }}
              </div>
              <div class="status-badge" :style="{ background: getStatusColor(ad) }">
                {{ getStatusLabel(ad) }}
              </div>
              <div class="card-actions">
                <button 
                  @click.prevent.stop="$emit('toggleFavorite', ad.id)"
                  class="action-btn favorite-btn"
                  :class="{ active: isFavorite(ad.id) }"
                  title="Dodaj do ulubionych"
                >
                  <svg width="22" height="22" viewBox="0 0 24 24" :fill="isFavorite(ad.id) ? '#EF4444' : 'none'">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" :stroke="isFavorite(ad.id) ? '#EF4444' : 'white'" stroke-width="2"/>
                  </svg>
                </button>
                <button 
                  @click.prevent.stop="$emit('toggleComparison', ad.id)"
                  class="action-btn comparison-btn"
                  :class="{ active: isInComparison(ad.id) }"
                  title="Dodaj do porównania"
                >
                  <svg width="22" height="22" viewBox="0 0 24 24" :fill="isInComparison(ad.id) ? '#667eea' : 'none'">
                    <rect x="3" y="3" width="7" height="7" :stroke="isInComparison(ad.id) ? '#667eea' : 'white'" stroke-width="2" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" :stroke="isInComparison(ad.id) ? '#667eea' : 'white'" stroke-width="2" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" :stroke="isInComparison(ad.id) ? '#667eea' : 'white'" stroke-width="2" rx="1"/>
                    <rect x="14" y="14" width="7" height="7" :stroke="isInComparison(ad.id) ? '#667eea' : 'white'" stroke-width="2" rx="1"/>
                  </svg>
                </button>
              </div>
            </div>

            <div class="card-content">
              <h3 class="card-title">{{ ad.title }}</h3>

              <div class="card-location">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                  <path d="M8 8C9.1 8 10 7.1 10 6C10 4.9 9.1 4 8 4C6.9 4 6 4.9 6 6C6 7.1 6.9 8 8 8Z" stroke="#6B7280" stroke-width="1.3"/>
                  <path d="M8 14C8 14 12 10.5 12 6C12 3.79 10.21 2 8 2C5.79 2 4 3.79 4 6C4 10.5 8 14 8 14Z" stroke="#6B7280" stroke-width="1.3"/>
                </svg>
                <span>{{ formatLocation(ad.location, ad.city) }}</span>
              </div>

              <div class="card-footer">
                <div class="card-price">
                  <span v-if="isMissingData(ad)" class="missing-data-badge">Brak danych</span>
                  <template v-else>
                    <span class="price-amount">
                      <span v-if="isEstimatedPrice(ad)" class="estimated-label">~</span>{{ Math.round(getPrice(ad, (priceDisplay || ad.price_unit || 'month') as any)).toLocaleString('pl-PL') }} zł
                    </span>
                    <span class="price-period">
                      {{ getPriceLabel((priceDisplay || ad.price_unit || 'month') as any, ad) }}<span v-if="isEstimatedPrice(ad)" class="estimated-info"> (szacunkowo)</span>
                    </span>
                    <span v-if="ad.price_negotiable" class="negotiable-badge">do negocjacji</span>
                  </template>
                </div>
              </div>
            </div>
          </router-link>
        </div>
        
        <!-- Pagination (na samym dole) -->
        <Pagination
          v-if="filteredListings.length > 0"
          :current-page="currentPage"
          :total-pages="totalPages"
          :total-items="filteredListings.length"
          :items-per-page="itemsPerPage"
          :show-info="true"
          :scroll-to-top="true"
          @update:current-page="onPageChange"
          class="pagination-bottom"
        />

        <SearchAlertBox 
          v-if="activeFiltersCount > 0 && filteredListings.length > 0"
          :location-label="locationQuery || (route.params.city ? deslugify(route.params.city as string) : '')"
          :ad-type-label="filters && filters.type ? getTypeLabel(filters.type) : 'ogłoszenie'"
          @click="showSearchAlertModal = true"
        />

      </div>


      <!-- Mobile toggle button (shows either list or map) -->
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

      <div class="map-container-wrapper" :class="{ 'mobile-visible': showMapOnMobile, 'mobile-hidden': isMobile && !showMapOnMobile }">
        <div ref="mapContainer" class="map-container">
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
        
        <!-- Side panel legend -->
        <div class="legend-side-panel" :class="{ 'is-visible': isLegendVisible && isMobile }">
          <div class="legend-header">
            <h3>Legenda</h3>
            <button class="close-legend" @click="isLegendVisible = false" aria-label="Zamknij legendę">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>
          <div class="legend-content">
            <div class="legend-items">
              <div v-for="(color, type) in typeColors" :key="type" class="legend-item">
                <div class="legend-marker" :style="{ background: color }"></div>
                <span class="legend-label">
                  {{ type === 'billboard' ? 'Billboardy' :
                      type === 'citylight' ? 'Citylighty' :
                      type === 'led_screen' ? 'Ekrany LED' :
                      type === 'banner' ? 'Banery' :
                      type === 'wall' ? 'Ściany reklamowe' :
                      type === 'totem' ? 'Totemy reklamowe' :
                      type === 'transport' ? 'Reklama w transporcie' :
                      type === 'mobile' ? 'Reklama mobilna' : 'Inne' }}
                </span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Desktop Legend -->
        <div v-if="!isMobile" class="map-legend" :class="{ 'is-visible': isLegendVisible }">
          <div class="legend-header">
            <h3 class="legend-title">Legenda</h3>
            <button class="close-legend" @click="isLegendVisible = false" aria-label="Zamknij legendę">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>
          <div class="legend-items">
            <div v-for="(color, type) in typeColors" :key="type" class="legend-item">
              <div class="legend-marker" :style="{ background: color }"></div>
              <span class="legend-label">
                {{ type === 'billboard' ? 'Billboardy' :
                    type === 'citylight' ? 'Citylighty' :
                    type === 'led_screen' ? 'Ekrany LED' :
                    type === 'banner' ? 'Banery' :
                    type === 'wall' ? 'Ściany' :
                    type === 'totem' ? 'Totemy' :
                    type === 'transport' ? 'Transport' :
                    type === 'mobile' ? 'Mobilna' : 'Inne' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>

    <!-- Filters Modal -->
    <Teleport to="body">
      <div v-if="showFiltersModal" class="modal-overlay" @click="closeFiltersModal">
        <div class="modal-content" @click.stop>
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
                    v-model.number="tempFilters.priceFrom" 
                    type="number" 
                    placeholder="Od"
                    class="filter-input"
                    v-if="tempFilters"
                  />
                  <span>-</span>
                  <input 
                    v-model.number="tempFilters.priceTo" 
                    type="number" 
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
                  v-model.number="tempFilters.widthFrom" 
                  type="number" 
                  placeholder="Od"
                  :step="tempFilters && tempFilters.type === 'led_screen' ? '1' : '0.1'"
                  class="filter-input"
                  v-if="tempFilters"
                />
                <span>-</span>
                <input 
                  v-model.number="tempFilters.widthTo" 
                  type="number" 
                  placeholder="Do"
                  :step="tempFilters && tempFilters.type === 'led_screen' ? '1' : '0.1'"
                  class="filter-input"
                  v-if="tempFilters"
                />
              </div>
            </div>

            <div class="filter-group">
              <label class="filter-label">Wysokość ({{ tempFilters && tempFilters.type === 'led_screen' ? 'mm' : 'm' }})</label>
              <div class="range-inputs">
                <input 
                  v-model.number="tempFilters.heightFrom" 
                  type="number" 
                  placeholder="Od"
                  :step="tempFilters && tempFilters.type === 'led_screen' ? '1' : '0.1'"
                  class="filter-input"
                  v-if="tempFilters"
                />
                <span>-</span>
                <input 
                  v-model.number="tempFilters.heightTo" 
                  type="number" 
                  placeholder="Do"
                  :step="tempFilters && tempFilters.type === 'led_screen' ? '1' : '0.1'"
                  class="filter-input"
                  v-if="tempFilters"
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
                  v-model.number="tempFilters.surfaceFrom" 
                  type="number" 
                  placeholder="Od"
                  step="0.1"
                  class="filter-input"
                  v-if="tempFilters"
                />
                <span>-</span>
                <input 
                  v-model.number="tempFilters.surfaceTo" 
                  type="number" 
                  placeholder="Do"
                  step="0.1"
                  class="filter-input"
                  v-if="tempFilters"
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
          
          <!-- Variant Filter -->
          <div v-if="tempFilters && tempFilters.type && ['billboard', 'citylight', 'led_screen', 'totem', 'transport', 'mobile'].includes(tempFilters.type)" class="filter-group">
            <label class="filter-label">Wariant</label>
            <select v-model="tempFilters.variant" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option v-for="variant in getVariantOptions(tempFilters.type)" :key="variant.value" :value="variant.value">
                {{ variant.label }}
              </option>
            </select>
          </div>

          <!-- Road Class Filter (Billboard only) -->
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

          <!-- Traffic Intensity (all outdoor types) -->
          <div v-if="tempFilters && ['billboard', 'banner', 'wall', 'totem'].includes(tempFilters.type)" class="filter-group">
            <label class="filter-label">Natężenie ruchu</label>
            <select v-model="tempFilters.trafficIntensity" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option value="low">Niskie</option>
              <option value="medium">Średnie</option>
              <option value="high">Wysokie</option>
            </select>
          </div>

          <!-- OTS Range (estimatedDailyViews) -->
          <div v-if="tempFilters && ['billboard', 'citylight', 'led_screen', 'banner', 'wall', 'totem'].includes(tempFilters.type)" class="filter-group">
            <label class="filter-label">Zasięg dzienny (OTS)</label>
            <div class="range-inputs">
              <input 
                v-model.number="tempFilters.estimatedDailyViewsFrom" 
                type="number" 
                step="1000"
                placeholder="Od"
                class="filter-input"
                v-if="tempFilters"
              />
              <span>-</span>
              <input 
                v-model.number="tempFilters.estimatedDailyViewsTo" 
                type="number" 
                step="1000"
                placeholder="Do"
                class="filter-input"
                v-if="tempFilters"
              />
            </div>
          </div>

          <!-- Kierunek ruchu (all outdoor types) -->
          <div v-if="tempFilters && ['billboard', 'banner', 'wall', 'totem'].includes(tempFilters.type)" class="filter-group">
            <label class="filter-label">Kierunek ruchu</label>
            <select v-model="tempFilters.trafficDirection" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option value="entry">Wjazd</option>
              <option value="exit">Wyjazd</option>
              <option value="both">Oba kierunki</option>
            </select>
          </div>

          <!-- Rodzaj ruchu (all outdoor types) -->
          <div v-if="tempFilters && ['billboard', 'banner', 'wall', 'totem'].includes(tempFilters.type)" class="filter-group">
            <label class="filter-label">Rodzaj ruchu</label>
            <select v-model="tempFilters.trafficType" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option value="pedestrian">Pieszy</option>
              <option value="vehicular">Samochodowy</option>
              <option value="both">Oba rodzaje</option>
            </select>
          </div>

          <!-- Environment Filter -->
          <div v-if="tempFilters && ['citylight', 'led_screen', 'totem', 'other'].includes(tempFilters.type)" class="filter-group">
            <label class="filter-label">Środowisko</label>
            <select v-model="tempFilters.environment" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option v-for="env in getEnvironmentOptions(tempFilters.type)" :key="env.value" :value="env.value">
                {{ env.label }}
              </option>
            </select>
          </div>

          <!-- LED Screen Filters -->
          <div v-if="tempFilters && tempFilters.type === 'led_screen'" class="filter-group">
            <label class="filter-label">Rozdzielczość</label>
            <input 
              v-model="tempFilters.resolution" 
              type="text" 
              placeholder="np. 1920x1080"
              class="filter-input"
              v-if="tempFilters"
            />
          </div>

          <div v-if="tempFilters && tempFilters.type === 'led_screen'" class="filter-group">
            <label class="filter-label">Pixel Pitch (mm)</label>
            <div class="range-inputs">
              <input 
                v-model.number="tempFilters.pixelPitchFrom" 
                type="number" 
                step="0.1"
                placeholder="Od"
                class="filter-input"
                v-if="tempFilters"
              />
              <span>-</span>
              <input 
                v-model.number="tempFilters.pixelPitchTo" 
                type="number" 
                step="0.1"
                placeholder="Do"
                class="filter-input"
                v-if="tempFilters"
              />
            </div>
          </div>

          <div v-if="tempFilters && tempFilters.type === 'led_screen'" class="filter-group">
            <label class="filter-label">Jasność (nits)</label>
            <div class="range-inputs">
              <input 
                v-model.number="tempFilters.brightnessFrom" 
                type="number" 
                placeholder="Od"
                class="filter-input"
                v-if="tempFilters"
              />
              <span>-</span>
              <input 
                v-model.number="tempFilters.brightnessTo" 
                type="number" 
                placeholder="Do"
                class="filter-input"
                v-if="tempFilters"
              />
            </div>
          </div>

          <!-- Transport Filters -->
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
                v-model.number="tempFilters.vehicleCountFrom" 
                type="number" 
                placeholder="Od"
                class="filter-input"
                v-if="tempFilters"
              />
              <span>-</span>
              <input 
                v-model.number="tempFilters.vehicleCountTo" 
                type="number" 
                placeholder="Do"
                class="filter-input"
                v-if="tempFilters"
              />
            </div>
          </div>

          <!-- Mobile Filters -->
          <div v-if="tempFilters && tempFilters.type === 'mobile'" class="filter-group">
            <label class="filter-label">Tryb ekspozycji</label>
            <select v-model="tempFilters.mobileExposureMode" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option value="moving">Jeżdżąca</option>
              <option value="stationary">Stojąca</option>
              <option value="mixed">Mieszana</option>
            </select>
          </div>

          <!-- Billboard - Lighting Type -->
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

          <!-- Transport - Daily Passengers -->
          <div v-if="tempFilters && tempFilters.type === 'transport'" class="filter-group">
            <label class="filter-label">Liczba pasażerów dziennie</label>
            <div class="range-inputs">
              <input 
                v-model.number="(tempFilters as any).dailyPassengersFrom" 
                type="number" 
                step="100"
                placeholder="Od"
                class="filter-input"
                v-if="tempFilters"
              />
              <span class="separator">-</span>
              <input 
                v-model.number="(tempFilters as any).dailyPassengersTo" 
                type="number" 
                step="100"
                placeholder="Do"
                class="filter-input"
                v-if="tempFilters"
              />
            </div>
          </div>

          <!-- Mobile - Operating Zone -->
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
                  <input type="checkbox" value="active" v-model="tempFilters.status" v-if="tempFilters">
                  <span>Wolne</span>
                </label>
                <label class="checkbox-option">
                  <input type="checkbox" value="reserved" v-model="tempFilters.status" v-if="tempFilters">
                  <span>Zarezerwowane</span>
                </label>
                <label class="checkbox-option">
                  <input type="checkbox" value="soon" v-model="tempFilters.status" v-if="tempFilters">
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
    <div class="description-wrapper">
      <CategoryDescription 
        v-if="currentDescription" 
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
  background: white;
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
  height: 100vh;
  background: #f9fafb;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Description Wrapper - poza głównym kontenerem */
.description-wrapper {
  padding: 2rem;
  background: white;
  width: 100%;
  box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.05);
}

/* Search Bar */
.listings-title {
  font-size: 2.25rem;
  color: #111827;
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
  background: white;
  border-bottom: 2px solid #e5e7eb;
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
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.2s;
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
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-weight: 600;
  color: #374151;
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
  color: #6b7280;
  font-weight: 600;
  white-space: nowrap;
}

.view-toggle {
  display: flex;
  gap: 0.25rem;
  background: white;
  border: 2px solid #e5e7eb;
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
  background: #f3f4f6;
  color: #374151;
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
  background: white;
  border-right: 2px solid #e5e7eb;
  overflow-y: auto;
  height: 100%;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
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
  border: 4px solid #f3f4f6;
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
  color: #1f2937;
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
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  height: 100%;
  display: flex;
  flex-direction: column;
  text-decoration: none;
  color: inherit;
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
  background-color: #f3f4f6;
  color: #9ca3af;
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
  background: #f3f4f6;
  display: flex;
  flex-direction: column;
}

.map-container {
  flex: 1;
  min-height: 0; /* Allows the container to shrink below its content size */
  width: 100%;
}

/* Legend Toggle Button */
.legend-toggle-button {
  position: absolute;
  top: 1rem;
  right: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.75);
  border: 2px solid rgba(229, 231, 235, 0.75);
  border-radius: 8px;
  padding: 0.75rem 1.25rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #374151;
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
  right: -320px;
  width: 300px;
  height: 100%;
  background: white;
  box-shadow: -4px 0 15px rgba(0, 0, 0, 0.1);
  z-index: 1100;
  transition: transform 0.3s ease-in-out;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.legend-side-panel.is-visible {
  transform: translateX(-100%);
}

.legend-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
}

.legend-header h3 {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
}

.close-legend {
  background: none;
  border: none;
  padding: 0.5rem;
  color: #6b7280;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.close-legend:hover {
  background-color: #e5e7eb;
  color: #1f2937;
}

.legend-content {
  padding: 1.25rem;
  overflow-y: auto;
  flex: 1;
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
  padding: 0.5rem 0;
}

.legend-marker {
  width: 16px;
  height: 16px;
  border-radius: 4px;
  flex-shrink: 0;
}

.legend-label {
  font-size: 0.9375rem;
  color: #4b5563;
  line-height: 1.4;
}

/* Desktop Legend */
.map-legend {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: white;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  max-width: 240px;
  width: 100%;
  box-sizing: border-box;
  transition: all 0.3s ease;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-10px);
  pointer-events: none;
}

.map-legend.is-visible {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
  pointer-events: auto;
}

.legend-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.25rem;
  padding: 0.25rem 1rem 0.5rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  background: transparent;
}

.legend-header .legend-title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: #1F2937;
}

.close-legend {
  background: rgba(255, 255, 255, 0.75);
  border: none;
  color: #6B7280;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  backdrop-filter: blur(4px);
}

.close-legend:hover {
  background: rgba(255, 255, 255, 0.9);
  color: #111827;
}

.close-legend svg {
  width: 16px;
  height: 16px;
}

@media (min-width: 768px) {
  .legend-side-panel {
    display: none;
  }
  
  .legend-toggle-button {
    display: flex;
  }
  
  .map-legend {
    display: block;
  }
}

/* Legend items for desktop */
.legend-items {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.4rem;
  max-height: 300px;
  overflow-y: auto;
  padding-right: 0.25rem;
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
  color: #4B5563;
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
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
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
  border-bottom: 2px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
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
  border-bottom: 1px solid #e5e7eb;
}

.toggle-switch {
  display: none !important;
}

.toggle-switch-display {
  display: inline-block;
  width: 50px;
  height: 28px;
  background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%);
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
  background: #EFF6FF;
  border: 1px solid #BFDBFE;
  border-radius: 8px;
  color: #1E40AF;
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
  color: #374151;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.filter-input,
.filter-select {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.2s;
  background: white;
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

.checkbox-option input[type="checkbox"] {
  width: 20px;
  height: 20px;
  cursor: pointer;
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
  color: #6b7280;
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
  background-color: #f3f4f6;
}

.suggestion-text {
  display: flex;
  flex-direction: column;
}

.suggestion-name {
  font-size: 0.95rem;
  color: #1f2937;
  font-weight: 500;
}

.suggestion-type {
  font-size: 0.75rem;
  color: #9ca3af;
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
  border-top: 2px solid #e5e7eb;
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
  background: white;
  color: #374151;
  border: 2px solid #e5e7eb;
}

.btn-secondary:hover {
  background: #f9fafb;
  border-color: #d1d5db;
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
    max-height: 90vh;
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