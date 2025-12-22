<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
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
  const unit = ad.price_unit || 'month'
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
// Get saved view mode from localStorage or default to grid
const savedViewMode = typeof window !== 'undefined' ? window.localStorage.getItem('adsViewMode') : null
const viewMode = ref<'grid' | 'list'>(savedViewMode === 'list' ? 'list' : 'grid')

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
const priceDisplay = ref<'day' | 'week' | 'month' | 'year' | 'sqm' | 'campaign' | undefined>(undefined)
const isStatusMenuOpen = ref(false)
const statusMultiselect = ref<HTMLElement | null>(null)
const currentPage = ref(1)
const itemsPerPage = 20

const route = useRoute()
const router = useRouter()

// Flaga zapobiegająca cyklicznemu wywoływaniu watch'ów
const isResettingFilters = ref(false)

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

// SEO Meta Tags
watch([() => route.params.type, () => route.params.city, listings], () => {
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
  
  useSeo({
    title,
    description,
    keywords,
    ogType: 'website',
    canonical: typeof window !== 'undefined' ? window.location.href.split('?')[0] : undefined
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
  spotDurationFrom: null as number | null,
  spotDurationTo: null as number | null,
  loopDurationFrom: null as number | null,
  loopDurationTo: null as number | null,
  transportScope: '',
  vehicleCountFrom: null as number | null,
  vehicleCountTo: null as number | null,
  mobileExposureMode: '',
  campaignDurationFrom: null as number | null,
  campaignDurationTo: null as number | null
})

// Lokalizacja - podobnie jak na stronie głównej
const locationQuery = ref('')
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
  if (!locationQuery.value) {
    return popularLocations
  }

  const query = locationQuery.value.toLowerCase()
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

  // Deduplicate by city name, preferring place/city over boundary
  const uniqueCities = new Map<string, LocationSuggestion>()
  apiSuggestions.forEach(suggestion => {
    const existing = uniqueCities.get(suggestion.value)
    if (!existing) {
      uniqueCities.set(suggestion.value, suggestion)
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
        uniqueCities.set(suggestion.value, suggestion)
      }
    }
  })
  const deduplicatedSuggestions = Array.from(uniqueCities.values())

  suggestions.push(...matchingRegions, ...deduplicatedSuggestions)
  return suggestions.slice(0, 10)
})

const selectLocation = (suggestion: LocationSuggestion) => {
  locationQuery.value = suggestion.label
  
  if (suggestion.type === 'region') {
    // Find the matching region ID from polishLocations
    const matchingRegion = polishLocations.voivodeships.find(
      v => v.name === suggestion.label
    )
    filters.value.region = matchingRegion?.id || suggestion.value
    filters.value.city = ''
    filters.value.selectedLocationCoords = null
  } else {
    filters.value.city = suggestion.value
    filters.value.region = ''
    // Store coordinates if available from API
    filters.value.selectedLocationCoords = suggestion.coords || null
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
  // Trigger API search when user types
  if (locationQuery.value.length >= 2) {
    isLoadingLocations.value = true
    debouncedSearchLocations(locationQuery.value, (results) => {
      apiLocationResults.value = results
      isLoadingLocations.value = false
    })
  } else {
    apiLocationResults.value = []
  }
  
  // If user types custom text without selecting, treat as city search
  filters.value.city = locationQuery.value
  filters.value.region = ''
  filters.value.selectedLocationCoords = null
}

const clearLocation = () => {
  locationQuery.value = ''
  filters.value.city = ''
  filters.value.region = ''
  filters.value.selectedLocationCoords = null
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
  const currentStatus = ad.display_status || ad.status
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
  const currentStatus = ad.display_status || ad.status
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
  if (filters.value.type) count++
  if (filters.value.priceFrom !== null) count++
  if (filters.value.priceTo !== null) count++
  if (filters.value.widthFrom !== null) count++
  if (filters.value.widthTo !== null) count++
  if (filters.value.heightFrom !== null) count++
  if (filters.value.heightTo !== null) count++
  if (filters.value.surfaceFrom !== null) count++
  if (filters.value.surfaceTo !== null) count++
  if (filters.value.city || filters.value.region || locationQuery.value) count++
  if (filters.value.rentalPeriod) count++
  if (filters.value.orientation) count++
  if (filters.value.trafficIntensity) count++
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
  if (filters.value.spotDurationFrom !== null) count++
  if (filters.value.spotDurationTo !== null) count++
  if (filters.value.loopDurationFrom !== null) count++
  if (filters.value.loopDurationTo !== null) count++
  if (filters.value.transportScope) count++
  if (filters.value.vehicleCountFrom !== null) count++
  if (filters.value.vehicleCountTo !== null) count++
  if (filters.value.mobileExposureMode) count++
  if (filters.value.campaignDurationFrom !== null) count++
  if (filters.value.campaignDurationTo !== null) count++
  return count
})

// Computed properties for filter visibility based on selected ad type
const showPrintFilter = computed(() => {
  const type = filters.value.type
  return ['billboard', 'banner'].includes(type)
})

const showMountingFilter = computed(() => {
  const type = filters.value.type
  return ['billboard', 'banner', 'wall'].includes(type)
})

const showGraphicDesignFilter = computed(() => {
  const type = filters.value.type
  return ['billboard', 'banner', 'wall'].includes(type)
})

const showTrafficIntensityFilter = computed(() => {
  const type = filters.value.type
  return ['billboard', 'banner'].includes(type)
})

const showDimensionsFilter = computed(() => {
  const type = filters.value.type
  return ['billboard', 'citylight', 'banner', 'wall'].includes(type)
})

// Type-specific filter visibility
const getVariantOptions = (type: string) => {
  switch (type) {
    case 'billboard':
      return [
        { value: 'standard', label: 'Standardowy' },
        { value: 'three_sided', label: 'Trójstronny' },
        { value: 'backlit', label: 'Backlit' }
      ]
    case 'citylight':
      return [
        { value: 'single', label: 'Pojedynczy' },
        { value: 'double', label: 'Podwójny' },
        { value: 'digital', label: 'Cyfrowy' }
      ]
    case 'led_screen':
      return [
        { value: 'outdoor', label: 'Zewnętrzny' },
        { value: 'indoor', label: 'Wewnętrzny' },
        { value: 'interactive', label: 'Interaktywny' }
      ]
    case 'banner':
      return [
        { value: 'pvc', label: 'PCV' },
        { value: 'mesh', label: 'Siatkowy/Mesh' },
        { value: 'textile', label: 'Tekstylny' }
      ]
    case 'wall':
      return [
        { value: 'mural', label: 'Mural' },
        { value: 'foil', label: 'Folia' },
        { value: 'construction', label: 'Konstrukcja' }
      ]
    case 'totem':
      return [
        { value: 'single_sided', label: 'Jednostronny' },
        { value: 'double_sided', label: 'Dwustronny' },
        { value: 'multi_sided', label: 'Wielostronny' },
        { value: 'digital', label: 'Digital' }
      ]
    case 'transport':
      return [
        { value: 'bus', label: 'Autobus' },
        { value: 'tram', label: 'Tramwaj' },
        { value: 'metro', label: 'Metro' },
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

const showRoadClassFilter = computed(() => {
  return filters.value.type === 'billboard'
})

const showEnvironmentFilter = computed(() => {
  const type = filters.value.type
  return ['citylight', 'led_screen', 'totem', 'mobile', 'other'].includes(type)
})

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

const showLEDFilters = computed(() => {
  return filters.value.type === 'led_screen'
})

const showTransportFilters = computed(() => {
  return filters.value.type === 'transport'
})

const showMobileFilters = computed(() => {
  return filters.value.type === 'mobile'
})

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
      { value: 'day', label: 'za dzień (emisje)' },
      { value: 'month', label: 'za miesiąc (emisje)' },
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

  // Size filters
  if (filters.value.widthFrom !== null) {
    filtered = filtered.filter(ad => ad.width >= filters.value.widthFrom!)
  }
  if (filters.value.widthTo !== null) {
    filtered = filtered.filter(ad => ad.width <= filters.value.widthTo!)
  }
  if (filters.value.heightFrom !== null) {
    filtered = filtered.filter(ad => ad.height >= filters.value.heightFrom!)
  }
  if (filters.value.heightTo !== null) {
    filtered = filtered.filter(ad => ad.height <= filters.value.heightTo!)
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

  // Status filter
  if (filters.value.status && filters.value.status.length > 0) {
    filtered = filtered.filter(ad => filters.value.status.includes(ad.display_status || ad.status))
  }

  // Feature filters
  if (filters.value.onlyWithImage) {
    filtered = filtered.filter(ad => ad.has_image === true)
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
  if (filters.value.spotDurationFrom !== null) {
    filtered = filtered.filter(ad => ad.spot_duration && ad.spot_duration >= filters.value.spotDurationFrom!)
  }
  if (filters.value.spotDurationTo !== null) {
    filtered = filtered.filter(ad => ad.spot_duration && ad.spot_duration <= filters.value.spotDurationTo!)
  }
  if (filters.value.loopDurationFrom !== null) {
    filtered = filtered.filter(ad => ad.loop_duration && ad.loop_duration >= filters.value.loopDurationFrom!)
  }
  if (filters.value.loopDurationTo !== null) {
    filtered = filtered.filter(ad => ad.loop_duration && ad.loop_duration <= filters.value.loopDurationTo!)
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
      priceDisplay.value = undefined
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

// Funkcja do formatowania ceny w zależności od wybranego sortowania
const getFormattedPrice = (ad: Advertisement) => {
  const price = getPrice(ad, priceDisplay.value)
  let suffix = ''
  
  switch (priceDisplay.value) {
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

// Check if price is estimated (converted from different unit)
const isEstimatedPrice = (ad: Advertisement) => {
  const displayUnit = priceDisplay.value || ad.price_unit || 'month'
  const adPriceUnit = ad.price_unit || 'month'
  return displayUnit !== adPriceUnit
}

// Check if data is missing for the requested display unit
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
      // For LED screens, add "(emisję)"
      if (ad && ad.type === 'led_screen') {
        return '/dzień (emisję)'
      }
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
  showFiltersModal.value = true
}

// Funkcja zamykająca modal bez stosowania zmian
const closeFiltersModal = () => {
  showFiltersModal.value = false
  tempFilters.value = null
}

// Funkcja stosująca filtry z modalu
const applyFilters = () => {
  // Ustaw flagę resetowania
  isResettingFilters.value = true
  
  // Zastosuj tymczasowe filtry
  filters.value = JSON.parse(JSON.stringify(tempFilters.value))
  
  // Zamknij modal
  showFiltersModal.value = false
  tempFilters.value = null
  
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
    spotDurationFrom: null,
    spotDurationTo: null,
    loopDurationFrom: null,
    loopDurationTo: null,
    transportScope: '',
    vehicleCountFrom: null,
    vehicleCountTo: null,
    mobileExposureMode: '',
    campaignDurationFrom: null,
    campaignDurationTo: null
  }
  
  // Wyczyść wyszukiwane słowo kluczowe i lokalizację
  searchQuery.value = ''
  locationQuery.value = ''
  
  // Resetuj sortowanie
  sortBy.value = 'newest'
  
  // Zamknij modal
  showFiltersModal.value = false
  tempFilters.value = null
  
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
    minZoom: 6,                      // Minimalne przybliżenie (cała Polska)
    maxZoom: 18                      // Maksymalne przybliżenie
  }).setView(polandCenter, 6)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map)
  
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

const isFavorite = (id: string) => {
  const favorites = JSON.parse(localStorage.getItem('favorites') || '[]')
  return favorites.includes(id)
}

const isInComparison = (id: string) => {
  const comparison = JSON.parse(localStorage.getItem('comparison') || '[]')
  return comparison.includes(id)
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
      spotDurationFrom: null,
      spotDurationTo: null,
      loopDurationFrom: null,
      loopDurationTo: null,
      transportScope: '',
      vehicleCountFrom: null,
      vehicleCountTo: null,
      mobileExposureMode: '',
      campaignDurationFrom: null,
      campaignDurationTo: null
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

onMounted(() => {
  loadListings()
  setTimeout(() => initMap(), 100)
  document.addEventListener('click', handleClickOutside)
  
  // Sprawdź parametry z URL path
  if (route.params.type) {
    // Mapowanie typów z URL na wartości w filtrach (wartości w bazie danych)
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
    // Dekoduj miasto z URL - użyj deslugify do konwersji
    const citySlug = route.params.city as string
    const city = deslugify(citySlug)
    filters.value.city = city
    locationQuery.value = city
  }
  
  // Jeśli są parametry w URL query, zastosuj je jako filtry
  if (Object.keys(route.query).length > 0) {
    const queryFilters = queryParamsToFilters(route.query as Record<string, string>)
    
    // Ustaw searchQuery jeśli jest keyword
    if (queryFilters.keyword) {
      searchQuery.value = queryFilters.keyword
      delete queryFilters.keyword // Usuń, żeby nie dodać do filters
    }
    
    // Ustaw sortBy jeśli jest sort
    if (route.query.sort) {
      sortBy.value = route.query.sort as string
    }
    
    // Ustaw lokalizację jeśli jest city lub region (tylko jeśli nie ma już z parametrów ścieżki)
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
    
    // Połącz z domyślnymi filtrami (ale nie nadpisuj typ i miasto jeśli są już ustawione z parametrów ścieżki)
    const mergedFilters = { ...queryFilters }
    if (route.params.type && filters.value.type) {
      delete mergedFilters.type
    }
    if (route.params.city && filters.value.city) {
      delete mergedFilters.city
    }
    
    filters.value = { ...filters.value, ...mergedFilters }
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div>
    <div class="listings-page">
    <!-- SEO Breadcrumbs -->
    <Breadcrumbs :items="breadcrumbs" />
    
    <!-- Search and Filters Bar -->
    <div class="search-bar">
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
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        Filtry
        <span v-if="activeFiltersCount > 0" class="filter-badge">{{ activeFiltersCount }}</span>
      </button>

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

      <div class="results-count">
        {{ filteredListings.length }} ogłoszeń
      </div>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper">
      <div class="listings-list-container">
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

              <div class="card-dimensions">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                  <rect x="2" y="2" width="12" height="12" rx="1" stroke="#6B7280" stroke-width="1.3"/>
                  <path d="M2 6H14M6 2V14" stroke="#6B7280" stroke-width="1.3"/>
                </svg>
                <span>{{ ad.width }}m × {{ ad.height }}m</span>
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
      </div>

      <div class="map-container-wrapper">
        <div ref="mapContainer" class="map-container"></div>
        
        <div class="map-legend">
          <h3 class="legend-title">Legenda</h3>
          <div class="legend-items">
            <div v-for="(color, type) in typeColors" :key="type" class="legend-item">
              <div class="legend-marker" :style="{ background: color }"></div>
              <span class="legend-label">
                {{ typeLabels[type] || type }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>

    <!-- Filters Modal -->
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
                    v-model="locationQuery"
                    type="text"
                    placeholder="Wpisz region, miasto lub ulicę"
                    class="filter-input"
                    @focus="handleLocationFocus"
                    @blur="handleLocationBlur"
                    @input="handleLocationInput"
                    autocomplete="off"
                  />
                  <button 
                    v-if="locationQuery" 
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
                  <div v-else-if="!locationQuery" class="suggestion-section">
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
        <div v-if="tempFilters && ['billboard', 'citylight', 'banner', 'wall'].includes(tempFilters.type)" class="filter-section">
          <h4 class="section-title">Wymiary i powierzchnia</h4>
          <div class="filter-row">
            <div class="filter-group">
              <label class="filter-label">Szerokość (m)</label>
              <div class="range-inputs">
                <input 
                  v-model.number="tempFilters.widthFrom" 
                  type="number" 
                  placeholder="Od"
                  class="filter-input"
                  v-if="tempFilters"
                />
                <span>-</span>
                <input 
                  v-model.number="tempFilters.widthTo" 
                  type="number" 
                  placeholder="Do"
                  class="filter-input"
                  v-if="tempFilters"
                />
              </div>
            </div>

            <div class="filter-group">
              <label class="filter-label">Wysokość (m)</label>
              <div class="range-inputs">
                <input 
                  v-model.number="tempFilters.heightFrom" 
                  type="number" 
                  placeholder="Od"
                  class="filter-input"
                  v-if="tempFilters"
                />
                <span>-</span>
                <input 
                  v-model.number="tempFilters.heightTo" 
                  type="number" 
                  placeholder="Do"
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
          <div v-if="tempFilters && tempFilters.type && ['billboard', 'citylight', 'led_screen', 'banner', 'wall', 'totem', 'transport', 'mobile'].includes(tempFilters.type)" class="filter-group">
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

          <!-- Traffic Intensity -->
          <div v-if="tempFilters && ['billboard', 'banner', 'wall'].includes(tempFilters.type)" class="filter-group">
            <label class="filter-label">Natężenie ruchu</label>
            <select v-model="tempFilters.trafficIntensity" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option value="low">Niskie</option>
              <option value="medium">Średnie</option>
              <option value="high">Wysokie</option>
            </select>
          </div>

          <!-- Kierunek ruchu (Billboard only) -->
          <div v-if="tempFilters && tempFilters.type === 'billboard'" class="filter-group">
            <label class="filter-label">Kierunek ruchu</label>
            <select v-model="tempFilters.trafficDirection" class="filter-select" v-if="tempFilters">
              <option value="">Wszystkie</option>
              <option value="entry">Wjazd</option>
              <option value="exit">Wyjazd</option>
              <option value="both">Oba kierunki</option>
            </select>
          </div>

          <!-- Rodzaj ruchu (Banner) -->
          <div v-if="tempFilters && tempFilters.type === 'banner'" class="filter-group">
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
            <label class="filter-label">Czas spotu (sekundy)</label>
            <div class="range-inputs">
              <input 
                v-model.number="tempFilters.spotDurationFrom" 
                type="number" 
                placeholder="Od"
                class="filter-input"
                v-if="tempFilters"
              />
              <span>-</span>
              <input 
                v-model.number="tempFilters.spotDurationTo" 
                type="number" 
                placeholder="Do"
                class="filter-input"
                v-if="tempFilters"
              />
            </div>
          </div>

          <div v-if="tempFilters && tempFilters.type === 'led_screen'" class="filter-group">
            <label class="filter-label">Pętla emisji (sekundy)</label>
            <div class="range-inputs">
              <input 
                v-model.number="tempFilters.loopDurationFrom" 
                type="number" 
                placeholder="Od"
                class="filter-input"
                v-if="tempFilters"
              />
              <span>-</span>
              <input 
                v-model.number="tempFilters.loopDurationTo" 
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
              <option value="internal">Wewnętrzna</option>
              <option value="external">Zewnętrzna</option>
              <option value="full_vehicle">Całopojazdowa</option>
            </select>
          </div>

          <div v-if="tempFilters && tempFilters.type === 'transport'" class="filter-group">
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
        </div>

        <!-- SEKCJA: Wyposażenie i dodatki -->
        <div class="filter-section">
          <h4 class="section-title">Wyposażenie i dodatki</h4>
          
          <!-- Feature Filters -->
          <div class="filter-group">
            <label class="checkbox-option">
              <input v-model="tempFilters.onlyWithImage" type="checkbox" v-if="tempFilters" />
              <span>Tylko ze zdjęciem</span>
            </label>
          </div>

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
            </select>
          </div>

          <div class="filter-group">
            <label class="checkbox-option">
              <input v-model="tempFilters.hasVatInvoice" type="checkbox" v-if="tempFilters" />
              <span>Faktura VAT</span>
            </label>
          </div>
        </div>
        </div>

        <div class="modal-footer">
          <button @click="clearFilters" class="btn-secondary">Wyczyść</button>
          <button @click="applyFilters" class="btn-primary">Zastosuj</button>
        </div>
      </div>
    </div>

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
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-weight: 600;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}

.filters-btn:hover {
  border-color: #667eea;
  color: #667eea;
  background: #f0f4ff;
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
  position: relative;
  background: #e5e7eb;
  height: 100%;
}

.map-container {
  width: 100%;
  height: 100%;
}

.map-legend {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: white;
  padding: 1rem 1.25rem;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1000;
}

.legend-title {
  margin: 0 0 0.75rem 0;
  font-size: 0.9rem;
  font-weight: 700;
  color: #1f2937;
}

.legend-items {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.legend-marker {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.legend-label {
  font-size: 0.85rem;
  color: #4b5563;
  font-weight: 500;
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
    grid-template-rows: auto 300px;
    height: auto;
  }
  
  .ads-list-container {
    max-height: none;
  }

  .map-container-wrapper {
    height: 300px;
  }

  .ad-image {
    width: 80px;
    height: 80px;
  }

  .ad-title {
    font-size: 1rem;
  }

  .modal-content {
    width: 95%;
    max-height: 95vh;
  }

  .modal-header,
  .modal-body,
  .modal-footer {
    padding: 1rem;
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