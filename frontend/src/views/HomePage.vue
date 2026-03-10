<script setup lang="ts">
import { ref, onMounted, computed, watch, nextTick, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import EmailModal from '../components/EmailModal.vue'
import HeroBanner from '../components/HeroBanner.vue'
import PolandMap from '../components/PolandMap.vue'
import AdGrid from '../components/AdGrid.vue'
import Pagination from '../components/Pagination.vue'
import { api } from '../services/api'
import type { Advertisement } from '../types'
import { filtersToQueryParams, queryParamsToFilters, normalizePolishChars } from '../utils/filterUtils'
import { useSeo } from '../composables/useSeo'
import SearchAlertModal from '../components/SearchAlertModal.vue'
import SearchAlertBox from '../components/SearchAlertBox.vue'
import polishLocations from '../data/polishLocations.json'



const emit = defineEmits<{
  toggleFavorite: [id: string]
  toggleComparison: [id: string]
}>()

// Helper to map type to Polish label
const getTypeLabel = (type: string): string => {
  const typeLabels: Record<string, string> = {
    'billboard': 'Billboardy',
    'citylight': 'Citylighty',
    'led_screen': 'Ekrany LED',
    'banner': 'Banery',
    'wall': 'Ściany reklamowe',
    'totem': 'Totemy reklamowe',
    'transport': 'Reklama w transporcie',
    'mobile': 'Reklama mobilna',
    'other': 'Inne'
  }
  return typeLabels[type] || 'ogłoszenie'
}

const route = useRoute()
const router = useRouter()

const isModalOpen = ref(false)
const listings = ref<Advertisement[]>([])
const isLoading = ref(true)
const viewMode = ref<'grid' | 'list'>('grid')
const sortBy = ref('newest')
const priceDisplay = ref<'day' | 'week' | 'month' | 'year' | 'sqm' | 'campaign' | undefined>(undefined)
const currentPage = ref(1)
const itemsPerPage = 18
const hoveredAdId = ref<string | null>(null)

const showSearchAlertModal = ref(false)
const hasShownAlertModal = ref(localStorage.getItem('search_alert_shown') === 'true')


interface Filters {
  keyword: string
  type: string
  region: string
  city: string
  priceFrom: number | null
  priceTo: number | null
  priceUnit: string
  rentalPeriod: string
  orientation: string
  widthFrom: number | null
  widthTo: number | null
  heightFrom: number | null
  heightTo: number | null
  surfaceFrom: number | null
  surfaceTo: number | null
  trafficIntensity: string
  status: string[]
  environment: string
  hasBacklight: boolean
  onlyWithImage: boolean
  priceIncludesPrint: boolean
  priceIncludesMounting: boolean
  graphicDesignHelp: boolean
  offerType: string
  hasVatInvoice: boolean
  selectedLocationCoords?: { lat: number; lng: number } | null
  // Type-specific filters
  variant: string
  roadClass: string
  // LED screen filters
  resolution: string
  pixelPitchFrom: number | null
  pixelPitchTo: number | null
  brightnessFrom: number | null
  brightnessTo: number | null
  transportScope: string
  vehicleCountFrom: number | null
  vehicleCountTo: number | null
  mobileExposureMode: string
  campaignDurationFrom: number | null
  campaignDurationTo: number | null
  // Nowe pola dla rozszerzonych opcji
  lightingType: string
  dailyPassengersFrom: number | null
  dailyPassengersTo: number | null
  operatingZone: string
  ambientLightControl: boolean
  // Checkboxy dla podświetlenia
  hasLightingTypeBanner: boolean
  hasLightingTypeBillboard: boolean
}

const filters = ref<Filters>({
  keyword: '',
  type: '',
  region: '',
  city: '',
  priceFrom: null,
  priceTo: null,
  priceUnit: 'month',
  rentalPeriod: '',
  orientation: '',
  widthFrom: null,
  widthTo: null,
  heightFrom: null,
  heightTo: null,
  surfaceFrom: null,
  surfaceTo: null,
  trafficIntensity: '',
  status: [],
  environment: '',
  hasBacklight: false,
  onlyWithImage: false,
  priceIncludesPrint: false,
  priceIncludesMounting: false,
  graphicDesignHelp: false,
  offerType: '',
  hasVatInvoice: false,
  selectedLocationCoords: null,
  // Type-specific filters
  variant: '',
  roadClass: '',
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
})

const sortedAndFilteredListings = computed(() => {
  let filtered = listings.value
  
  // Dodanie zależności od sortBy i priceDisplay, aby computed się przeliczał
  
  if (filters.value.keyword) {
    const keyword = normalizePolishChars(filters.value.keyword.toLowerCase())
    filtered = filtered.filter(ad =>
      normalizePolishChars(ad.title.toLowerCase()).includes(keyword) ||
      normalizePolishChars(ad.description.toLowerCase()).includes(keyword) ||
      normalizePolishChars(ad.location.toLowerCase()).includes(keyword)
    )
  }

  if (filters.value.type) {
    filtered = filtered.filter(ad => ad.type === filters.value.type)
  }

  if (filters.value.region) {
    filtered = filtered.filter(ad => ad.region === filters.value.region)
  }

  if (filters.value.city) {
    const city = filters.value.city.toLowerCase()
    filtered = filtered.filter(ad =>
      ad.city.toLowerCase().includes(city) ||
      ad.location.toLowerCase().includes(city)
    )
  }

  if (filters.value.priceFrom !== null) {
    filtered = filtered.filter(ad => ad.price >= filters.value.priceFrom!)
  }

  if (filters.value.priceTo !== null) {
    filtered = filtered.filter(ad => ad.price <= filters.value.priceTo!)
  }

  if (filters.value.rentalPeriod) {
    filtered = filtered.filter(ad => ad.rental_period === filters.value.rentalPeriod)
  }

  if (filters.value.orientation) {
    filtered = filtered.filter(ad => ad.orientation === filters.value.orientation)
  }

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
      let surface = ad.width * ad.height
      // Convert mm² to m² for LED screens
      if (ad.type === 'led_screen') {
        surface = surface / 1000000 // mm² to m²
      }
      return surface >= filters.value.surfaceFrom!
    })
  }
  if (filters.value.surfaceTo !== null) {
    filtered = filtered.filter(ad => {
      let surface = ad.width * ad.height
      // Convert mm² to m² for LED screens
      if (ad.type === 'led_screen') {
        surface = surface / 1000000 // mm² to m²
      }
      return surface <= filters.value.surfaceTo!
    })
  }

  if (filters.value.trafficIntensity) {
    filtered = filtered.filter(ad => ad.traffic_intensity === filters.value.trafficIntensity)
  }

  if (filters.value.status && filters.value.status.length > 0) {
    filtered = filtered.filter(ad => filters.value.status.includes(ad.display_status || ad.status))
  }

  if (filters.value.hasBacklight) {
    filtered = filtered.filter(ad => ad.has_backlight === true)
  }

  if (filters.value.lightingType) {
    filtered = filtered.filter(ad => ad.lighting_type === filters.value.lightingType)
  }

  if (filters.value.hasLightingTypeBillboard) {
    filtered = filtered.filter(ad => {
      // Tylko dla billboardów
      if (ad.type !== 'billboard') return false
      const lightingType = (ad as any).lighting_type
      return lightingType && lightingType !== 'none'
    })
  }

  if (filters.value.hasLightingTypeBanner) {
    filtered = filtered.filter(ad => {
      // Tylko dla banerów
      if (ad.type !== 'banner') return false
      const lightingType = (ad as any).lighting_type
      return lightingType && lightingType !== 'none'
    })
  }

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

  if (filters.value.offerType) {
    filtered = filtered.filter(ad => ad.offer_type === filters.value.offerType)
  }

  if (filters.value.hasVatInvoice) {
    filtered = filtered.filter(ad => ad.has_vat_invoice === true)
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

  // Sortowanie
  const sorted = [...filtered]

  const getPrice = (ad: Advertisement, period: 'day' | 'week' | 'month' | 'year' | 'sqm' | 'campaign') => {
    const basePrice = ad.price
    // Use the ad's price_unit as the base, not always 'month'
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
        // Keep items with MAX_SAFE_INTEGER at the end
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

const activeFiltersCount = computed(() => {
  let count = 0
  const f = filters.value
  
  if (f.keyword || f.city || f.region || f.selectedLocationCoords) count++
  if (f.type) count++
  if (f.priceFrom !== null) count++
  if (f.priceTo !== null) count++
  
  if (f.rentalPeriod) count++
  if (f.orientation) count++
  if (f.widthFrom !== null) count++
  if (f.widthTo !== null) count++
  if (f.heightFrom !== null) count++
  if (f.heightTo !== null) count++
  if (f.surfaceFrom !== null) count++
  if (f.surfaceTo !== null) count++
  if (f.trafficIntensity) count++
  if (f.status && f.status.length > 0) count++
  if (f.environment) count++
  if (f.hasBacklight) count++
  if (f.onlyWithImage) count++
  if (f.priceIncludesPrint) count++
  if (f.priceIncludesMounting) count++
  if (f.graphicDesignHelp) count++
  if (f.offerType) count++
  if (f.hasVatInvoice) count++
  
  // Type-specific
  if (f.variant) count++
  if (f.roadClass) count++
  if (f.resolution) count++
  if (f.pixelPitchFrom !== null) count++
  if (f.pixelPitchTo !== null) count++
  if (f.brightnessFrom !== null) count++
  if (f.brightnessTo !== null) count++
  if (f.transportScope) count++
  if (f.vehicleCountFrom !== null) count++
  if (f.vehicleCountTo !== null) count++
  if (f.mobileExposureMode) count++
  if (f.campaignDurationFrom !== null) count++
  if (f.campaignDurationTo !== null) count++
  
  return count
})

const totalPages = computed(() => {
  return Math.ceil(sortedAndFilteredListings.value.length / itemsPerPage)
})

const paginatedListings = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return sortedAndFilteredListings.value.slice(start, end)
})

const handlePageChange = async (page: number) => {
  currentPage.value = page
  await router.push({ query: { ...route.query, page: page.toString() } })
  
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

const handleSearch = (searchFilters: Filters & { _priceDisplayUnit?: string }) => {
  filters.value = searchFilters
  currentPage.value = 1 // Reset to first page on search
  
  // Jeśli użytkownik wpisał cenę, ustaw priceDisplay na tę jednostkę
  // Aby wyniki były przełączone na tę jednostkę (jak przy sortowaniu)
  if (searchFilters._priceDisplayUnit) {
    priceDisplay.value = searchFilters._priceDisplayUnit as 'day' | 'week' | 'month' | 'year' | 'sqm' | 'campaign'
  }
  
  // Konwertuj filtry na query params
  const queryParams = filtersToQueryParams(searchFilters)
  
  // Dodaj parametr strony
  queryParams.page = '1'
  
  // Use history.replaceState to update URL without triggering navigation
  const newUrl = window.location.pathname + '?' + new URLSearchParams(queryParams).toString()
  window.history.replaceState({}, document.title, newUrl)
  
  // No need to scroll here as HeroBanner component will handle scrolling
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

// Obsługa zmiany rozmiaru okna
onMounted(() => {
  checkIfMobile()
  window.addEventListener('resize', checkIfMobile)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', checkIfMobile)
  document.body.style.overflow = ''
})

const handleReset = (resetFilters: Filters) => {
  // Ustaw flagę, że resetujemy filtry
  isResettingFilters.value = true
  
  // Zresetuj filtry
  filters.value = { ...resetFilters }
  
  // Zresetuj sortowanie do domyślnego
  sortBy.value = 'newest'
  priceDisplay.value = undefined
  
  // Wyczyść parametry URL
  router.replace({ query: {} })
  
  // Zapobiegaj przewijaniu do góry
  window.scrollTo(0, window.scrollY)
  
  // Zresetuj flagę po zakończeniu
  setTimeout(() => {
    isResettingFilters.value = false
  }, 0)
}

const loadAdvertisements = async () => {
  try {
    isLoading.value = true
    const data = await api.getAdvertisements()
    // Backend returns only active listings
    listings.value = data
      .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
  } catch (error) {
    console.error('Error loading listings:', error)
  } finally {
    isLoading.value = false
  }
}

// Watch for URL query parameter changes
watch(() => route.query, (newQuery) => {
  // Skip if we're in the middle of resetting filters
  if (isResettingFilters.value) {
    return
  }
  
  // Aktualizuj numer strony
  const page = parseInt(newQuery.page as string) || 1
  if (page !== currentPage.value && page >= 1 && page <= totalPages.value) {
    currentPage.value = page
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
}, { immediate: true, deep: true })

// SEO Meta Tags
useSeo({
  title: 'ReklaMap - Wynajem Powierzchni Reklamowych w Polsce | Billboardy, Citylighty, Banery',
  description: 'Znajdź i wynajmij powierzchnie reklamowe w całej Polsce. Billboardy, citylighty, banery, ściany reklamowe. Porównuj oferty, sprawdzaj ceny i lokalizacje na mapie.',
  keywords: 'powierzchnie reklamowe, billboardy, citylighty, banery reklamowe, wynajem billboardu, reklama zewnętrzna, outdoor, powierzchnie OOH',
  ogType: 'website',
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
  
  // Jeśli są parametry w URL, zastosuj je jako filtry
  if (Object.keys(route.query).length > 0) {
    const queryFilters = queryParamsToFilters(route.query as Record<string, string>)
    // Połącz z domyślnymi filtrami
    filters.value = { ...filters.value, ...queryFilters }
  } else {
    // Jeśli nie ma parametrów w URL, spróbuj załadować z localStorage
    try {
      const saved = localStorage.getItem('reklamap_last_search')
      if (saved) {
        const lastSearch = JSON.parse(saved)
        filters.value = { ...filters.value, ...lastSearch }
        
        if (lastSearch._priceDisplayUnit) {
          priceDisplay.value = lastSearch._priceDisplayUnit as any
        }
      }
    } catch (error) {
      console.error('Error loading search from localStorage:', error)
    }
  }

  // Logic for showing the search alert modal after 20 seconds
  if (!hasShownAlertModal.value) {
    setTimeout(() => {
      // Show only if user has active filters and hasn't seen it yet
      if (!hasShownAlertModal.value && activeFiltersCount.value > 0) {
        showSearchAlertModal.value = true
        hasShownAlertModal.value = true
        localStorage.setItem('search_alert_shown', 'true')
      }
    }, 20000) // 20 seconds
  }
})

const handleSearchAlertSubmit = (email: string) => {
  console.log('Saving alert on Home for:', email, filters.value)
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
            >
              <div class="category-icon">
                <img :src="`/icons/${category.icon}`" :alt="category.name" />
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
      @toggle-favorite="$emit('toggleFavorite', $event)"
      @toggle-comparison="$emit('toggleComparison', $event)"
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
        :location-label="filters.city || filters.region || ''"
        @close="showSearchAlertModal = false"
        @submit="handleSearchAlertSubmit"
      />
    </Teleport>
  </div>
</template>


<style scoped>
.categories-section {
  background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
  padding: 5rem 0;
  margin: 0;
  position: relative;
  overflow: hidden;
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
  color: #1f2937;
  margin-bottom: 3.5rem;
  animation: fadeInDown 0.6s ease-out;
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 2.5rem;
}

.category-card {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.08) 100%);
  backdrop-filter: blur(10px);
  border-radius: 24px;
  padding: 2.5rem;
  text-decoration: none;
  color: inherit;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  box-shadow: 0 8px 30px rgba(102, 126, 234, 0.15), 0 1px 3px rgba(0, 0, 0, 0.05);
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  animation: fadeInUp 0.6s ease-out backwards;
  border: 1px solid rgba(102, 126, 234, 0.15);
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
  right: 0;
  height: 5px;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.category-card::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
  transform: translate(-50%, -50%);
  transition: width 0.6s ease, height 0.6s ease;
}

.category-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
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

.category-icon img {
  width: 100%;
  height: 100%;
  /* Apply purple color directly */
  filter: invert(48%) sepia(79%) saturate(2476%) hue-rotate(224deg) brightness(94%) contrast(91%);
  -webkit-filter: invert(48%) sepia(79%) saturate(2476%) hue-rotate(224deg) brightness(94%) contrast(91%);
  transition: filter 0.3s ease;
}

.category-card:hover .category-icon img {
  filter: invert(48%) sepia(79%) saturate(2476%) hue-rotate(224deg) brightness(94%) contrast(91%);
  -webkit-filter: invert(48%) sepia(79%) saturate(2476%) hue-rotate(224deg) brightness(94%) contrast(91%);
}

.category-card:hover .category-icon {
  transform: scale(1.15) rotate(5deg);
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.25);
}


.category-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
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
  color: #6b7280;
  margin: 0;
  flex: 1;
  position: relative;
  z-index: 1;
  line-height: 1.7;
  transition: color 0.3s ease;
}

.category-card:hover .category-description {
  color: #4b5563;
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
  
  .category-icon img {
    width: 100%;
    height: 100%;
    filter: invert(32%) sepia(79%) saturate(1100%) hue-rotate(260deg) brightness(95%) contrast(102%);
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
    background: rgba(102, 126, 234, 0.1);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 12px;
    color: #4F46E5;
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
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(249, 250, 255, 0.9) 100%);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 2.25rem 2rem;
  text-decoration: none;
  color: inherit;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  position: relative;
  overflow: hidden;
  box-shadow: 0 8px 30px rgba(102, 126, 234, 0.15), 0 2px 8px rgba(0, 0, 0, 0.08);
  animation: fadeInUp 0.6s ease-out backwards;
  border: 1px solid rgba(255, 255, 255, 0.4);
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
  right: 0;
  height: 5px;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  border-radius: 20px 20px 0 0;
}

.city-card::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.08) 100%);
  opacity: 0;
  transition: opacity 0.4s ease;
  border-radius: 20px;
}

.city-card:hover {
  transform: translateY(-10px) scale(1.03);
  box-shadow: 0 20px 50px rgba(102, 126, 234, 0.25), 0 10px 25px rgba(118, 75, 162, 0.2);
  border-color: rgba(255, 255, 255, 0.6);
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(252, 253, 255, 0.95) 100%);
}

.city-card:hover::before {
  transform: scaleX(1);
}

.city-card:hover::after {
  opacity: 1;
}

.city-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  position: relative;
  z-index: 1;
  transition: all 0.3s ease;
  letter-spacing: -0.02em;
  line-height: 1.3;
}

.city-card:hover .city-name {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  transform: translateX(4px);
}

.city-region {
  font-size: 0.95rem;
  color: #6b7280;
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  opacity: 0;
  transform: translateX(-15px) rotate(-45deg);
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  z-index: 1;
  filter: drop-shadow(0 0 8px rgba(102, 126, 234, 0.4));
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



