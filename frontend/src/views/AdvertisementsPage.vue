<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../services/api'
import { slugify } from '../utils/slugify'
import type { Advertisement } from '../types'
import Pagination from '../components/Pagination.vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { filtersToQueryParams, queryParamsToFilters } from '../utils/filterUtils'

const advertisements = ref<Advertisement[]>([])
const isLoading = ref(true)
const hoveredAdId = ref<string | null>(null)
const selectedAdId = ref<string | null>(null)
const searchQuery = ref('')
const showFiltersModal = ref(false)
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
const priceDisplay = ref<'day' | 'week' | 'month' | 'year' | 'sqm'>('month')
const isStatusMenuOpen = ref(false)
const statusMultiselect = ref<HTMLElement | null>(null)
const currentPage = ref(1)
const itemsPerPage = 20

const route = useRoute()
const router = useRouter()

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
  city: '',
  region: '',
  rentalPeriod: '',
  orientation: '',
  trafficIntensity: '',
  status: [] as string[],
  hasLighting: false,
  onlyWithImage: false,
  priceIncludesPrint: false,
  graphicDesignHelp: false,
  offerType: '',
  hasVatInvoice: false
})

const typeColors: Record<string, string> = {
  billboard: '#EF4444',
  citylight: '#F59E0B',
  led_screen: '#10B981',
  digital: '#3B82F6',
  banner: '#8B5CF6',
  poster: '#EC4899'
}

const typeLabels: Record<string, string> = {
  billboard: 'Billboard',
  citylight: 'Citylight',
  led_screen: 'Ekran LED',
  digital: 'Digital',
  banner: 'Banner',
  poster: 'Plakat'
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
  if (filters.value.city) count++
  if (filters.value.region) count++
  if (filters.value.rentalPeriod) count++
  if (filters.value.orientation) count++
  if (filters.value.trafficIntensity) count++
  if (filters.value.status && filters.value.status.length > 0) count++
  if (filters.value.hasLighting) count++
  if (filters.value.onlyWithImage) count++
  if (filters.value.priceIncludesPrint) count++
  if (filters.value.graphicDesignHelp) count++
  if (filters.value.offerType) count++
  if (filters.value.hasVatInvoice) count++
  return count
})

const filteredAdvertisements = computed(() => {
  let filtered = advertisements.value
  
  // Search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(ad => 
      ad.title.toLowerCase().includes(query) ||
      ad.city.toLowerCase().includes(query) ||
      ad.location.toLowerCase().includes(query)
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

  // Location filters
  if (filters.value.city) {
    const city = filters.value.city.toLowerCase()
    filtered = filtered.filter(ad => ad.city.toLowerCase().includes(city))
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
    filtered = filtered.filter(ad => filters.value.status.includes(ad.status))
  }

  // Feature filters
  if (filters.value.hasLighting) {
    filtered = filtered.filter(ad => ad.has_lighting === true)
  }
  if (filters.value.onlyWithImage) {
    filtered = filtered.filter(ad => ad.has_image === true)
  }
  if (filters.value.priceIncludesPrint) {
    filtered = filtered.filter(ad => ad.price_includes_print === true)
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

  // Sortowanie
  const sorted = [...filtered]

  const getPrice = (ad: Advertisement, period: 'day' | 'week' | 'month' | 'year' | 'sqm') => {
    const basePrice = ad.price

    switch (period) {
      case 'day':
        return basePrice / 30
      case 'week':
        return basePrice / 4
      case 'month':
        return basePrice
      case 'year':
        return basePrice * 12
      case 'sqm':
        const area = ad.width * ad.height
        return area > 0 ? basePrice / area : 0
      default:
        return basePrice
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
      sorted.sort((a, b) => getPrice(b, 'day') - getPrice(a, 'day'))
      break
    case 'price-week-asc':
      priceDisplay.value = 'week'
      sorted.sort((a, b) => getPrice(a, 'week') - getPrice(b, 'week'))
      break
    case 'price-week-desc':
      priceDisplay.value = 'week'
      sorted.sort((a, b) => getPrice(b, 'week') - getPrice(a, 'week'))
      break
    case 'price-month-asc':
      priceDisplay.value = 'month'
      sorted.sort((a, b) => getPrice(a, 'month') - getPrice(b, 'month'))
      break
    case 'price-month-desc':
      priceDisplay.value = 'month'
      sorted.sort((a, b) => getPrice(b, 'month') - getPrice(a, 'month'))
      break
    case 'price-year-asc':
      priceDisplay.value = 'year'
      sorted.sort((a, b) => getPrice(a, 'year') - getPrice(b, 'year'))
      break
    case 'price-year-desc':
      priceDisplay.value = 'year'
      sorted.sort((a, b) => getPrice(b, 'year') - getPrice(a, 'year'))
      break
    case 'price-sqm-asc':
      priceDisplay.value = 'sqm'
      sorted.sort((a, b) => getPrice(a, 'sqm') - getPrice(b, 'sqm'))
      break
    case 'price-sqm-desc':
      priceDisplay.value = 'sqm'
      sorted.sort((a, b) => getPrice(b, 'sqm') - getPrice(a, 'sqm'))
      break
    default:
      priceDisplay.value = 'month'
  }

  return sorted
})

const totalPages = computed(() => {
  return Math.ceil(filteredAdvertisements.value.length / itemsPerPage)
})

const paginatedAdvertisements = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredAdvertisements.value.slice(start, end)
})

const handlePageChange = (page: number) => {
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

const handleClickOutside = (event: MouseEvent) => {
  if (statusMultiselect.value && !statusMultiselect.value.contains(event.target as Node)) {
    isStatusMenuOpen.value = false
  }
}

const clearFilters = () => {
  filters.value = {
    type: '',
    priceFrom: null,
    priceTo: null,
    priceUnit: 'month',
    widthFrom: null,
    widthTo: null,
    heightFrom: null,
    heightTo: null,
    city: '',
    region: '',
    rentalPeriod: '',
    orientation: '',
    trafficIntensity: '',
    status: [],
    hasLighting: false,
    onlyWithImage: false,
    priceIncludesPrint: false,
    graphicDesignHelp: false,
    offerType: '',
    hasVatInvoice: false
  }
  
  // Wyczyść wyszukiwane słowo kluczowe
  searchQuery.value = ''
  
  // Resetuj sortowanie
  sortBy.value = 'newest'
  
  // Wyczyść URL
  router.push({ query: {} })
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

  map = L.map(mapContainer.value).setView([52.0, 19.0], 6)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map)

  updateMarkers()
}

const updateMarkers = () => {
  if (!map) return

  // Clear existing markers
  markers.forEach(marker => marker.remove())
  markers.clear()

  filteredAdvertisements.value.forEach((ad) => {
    const marker = L.marker([ad.latitude, ad.longitude], {
      icon: createCustomIcon(ad.type, hoveredAdId.value === ad.id, selectedAdId.value === ad.id)
    })

    const citySlug = slugify(ad.city)
    const titleSlug = slugify(ad.title)
    const adUrl = `/ogloszenie/${citySlug}/${titleSlug}/${ad.id}`

    marker.bindPopup(`
      <div style="min-width: 200px;">
        <a href="${adUrl}" style="text-decoration: none; color: inherit; display: block;">
          <h3 style="margin: 0 0 8px 0; font-size: 1.1rem; font-weight: 700; color: #1F2937;">
            ${ad.title}
          </h3>
          <div style="color: #6B7280; font-size: 0.9rem; margin-bottom: 8px;">
            📍 ${ad.city}
          </div>
          <div style="font-weight: 700; color: #4F46E5; font-size: 1.1rem;">
            ${ad.price.toLocaleString('pl-PL')} zł/mies.
          </div>
        </a>
      </div>
    `)

    marker.on('click', () => {
      selectedAdId.value = ad.id
      scrollToAd(ad.id)
    })

    marker.addTo(map!)
    markers.set(ad.id, marker)
  })

  // Fit bounds if there are markers
  if (markers.size > 0) {
    const group = new L.FeatureGroup(Array.from(markers.values()))
    map.fitBounds(group.getBounds(), { padding: [50, 50], maxZoom: 12 })
  }
}

const handleAdHover = (adId: string | null) => {
  hoveredAdId.value = adId
  
  if (adId && markers.has(adId)) {
    const ad = advertisements.value.find(a => a.id === adId)
    if (ad) {
      const marker = markers.get(adId)!
      marker.setIcon(createCustomIcon(ad.type, true, selectedAdId.value === adId))
    }
  }
  
  // Reset other markers
  markers.forEach((marker, id) => {
    if (id !== adId) {
      const ad = advertisements.value.find(a => a.id === id)
      if (ad) {
        marker.setIcon(createCustomIcon(ad.type, false, selectedAdId.value === id))
      }
    }
  })
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
  if (element) {
    element.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }
}

const loadAdvertisements = async () => {
  try {
    isLoading.value = true
    const data = await api.getAdvertisements()
    advertisements.value = data.filter(ad => ad.status === 'active')
      .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
    
    setTimeout(() => updateMarkers(), 100)
  } catch (error) {
    console.error('Error loading advertisements:', error)
  } finally {
    isLoading.value = false
  }
}

// Watch for URL query parameter changes
watch(() => route.query, (newQuery) => {
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
}, { immediate: true, deep: true })

// Watch for filter and sort changes - reset to page 1 and update URL
watch([() => filters.value, () => sortBy.value, () => searchQuery.value], () => {
  // Reset to page 1
  currentPage.value = 1
  
  // Konwertuj filtry na query params
  const queryParams = filtersToQueryParams({
    ...filters.value,
    keyword: searchQuery.value // Dodaj wyszukiwane słowo kluczowe
  })
  
  // Dodaj parametr sortowania
  if (sortBy.value !== 'newest') {
    queryParams.sort = sortBy.value
  }
  
  // Dodaj parametr strony
  queryParams.page = '1'
  
  // Aktualizuj URL z nowymi parametrami
  router.push({ query: queryParams })
}, { deep: true })

watch(() => filteredAdvertisements.value, () => {
  updateMarkers()
})

onMounted(() => {
  loadAdvertisements()
  setTimeout(() => initMap(), 100)
  document.addEventListener('click', handleClickOutside)
  
  // Jeśli są parametry w URL, zastosuj je jako filtry
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
    
    // Połącz z domyślnymi filtrami
    filters.value = { ...filters.value, ...queryFilters }
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="advertisements-page">
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
          placeholder="Szukaj po tytule, mieście..."
          class="search-input"
        />
      </div>
      
      <button @click="showFiltersModal = true" class="filters-btn">
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
      </select>

      <div class="results-count">
        {{ filteredAdvertisements.length }} ogłoszeń
      </div>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper">
      <div class="ads-list-container">
        <div v-if="isLoading" class="loading-state">
          <div class="spinner"></div>
          <p>Ładowanie ogłoszeń...</p>
        </div>

        <div v-else-if="filteredAdvertisements.length === 0" class="empty-state">
          <svg width="80" height="80" viewBox="0 0 24 24" fill="none">
            <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
            <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
            <path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2"/>
          </svg>
          <h3>Brak ogłoszeń</h3>
          <p>Nie znaleziono ogłoszeń pasujących do wyszukiwania</p>
        </div>

        <div v-else class="ads-list" :class="viewMode">
          <div
            v-for="ad in filteredAdvertisements"
            :key="ad.id"
            :id="`ad-${ad.id}`"
            class="ad-list-item"
            :class="{ 
              'hovered': hoveredAdId === ad.id,
              'selected': selectedAdId === ad.id
            }"
            @mouseenter="handleAdHover(ad.id)"
            @mouseleave="handleAdHover(null)"
            @click="handleAdClick(ad.id)"
          >
            <router-link 
              :to="`/ogloszenie/${slugify(ad.city)}/${slugify(ad.title)}/${ad.id}`"
              class="ad-link"
            >
              <div class="ad-image">
                <img 
                  v-if="ad.image_url" 
                  :src="ad.image_url" 
                  :alt="ad.title"
                />
                <div v-else class="no-image">
                  <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
                    <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                    <path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2"/>
                  </svg>
                </div>
                <div class="ad-type-badge" :style="{ background: typeColors[ad.type] }">
                  {{ typeLabels[ad.type] || ad.type }}
                </div>
              </div>

              <div class="ad-content">
                <h3 class="ad-title">{{ ad.title }}</h3>
                <div class="ad-location">
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M8 8C9.1 8 10 7.1 10 6C10 4.9 9.1 4 8 4C6.9 4 6 4.9 6 6C6 7.1 6.9 8 8 8Z" stroke="currentColor" stroke-width="1.3"/>
                    <path d="M8 14C8 14 12 10.5 12 6C12 3.79 10.21 2 8 2C5.79 2 4 3.79 4 6C4 10.5 8 14 8 14Z" stroke="currentColor" stroke-width="1.3"/>
                  </svg>
                  {{ ad.city }}
                </div>
                <div class="ad-details">
                  <span class="ad-size">{{ ad.width }}m × {{ ad.height }}m</span>
                  <span class="ad-price">{{ ad.price.toLocaleString('pl-PL') }} zł/mies.</span>
                </div>
              </div>
            </router-link>
          </div>
        </div>
      </div>

      <div class="map-container-wrapper">
        <div ref="mapContainer" class="map-container"></div>
      </div>
    </div>

    <!-- Filters Modal -->
    <div v-if="showFiltersModal" class="modal-overlay" @click="showFiltersModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>Filtry</h2>
          <button @click="showFiltersModal = false" class="close-btn">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
              <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <!-- Type Filter -->
          <div class="filter-group">
            <label class="filter-label">Typ powierzchni</label>
            <select v-model="filters.type" class="filter-select">
              <option value="">Wszystkie</option>
              <option value="billboard">Billboard</option>
              <option value="citylight">Citylight</option>
              <option value="led_screen">Ekran LED</option>
              <option value="digital">Digital</option>
              <option value="banner">Banner</option>
              <option value="poster">Plakat</option>
            </select>
          </div>

          <!-- Price Range with Unit -->
          <div class="filter-group">
            <label class="filter-label">Cena</label>
            <div class="price-filter-group">
              <div class="range-inputs">
                <input 
                  v-model.number="filters.priceFrom" 
                  type="number" 
                  placeholder="Od"
                  class="filter-input"
                />
                <span>-</span>
                <input 
                  v-model.number="filters.priceTo" 
                  type="number" 
                  placeholder="Do"
                  class="filter-input"
                />
              </div>
              <select v-model="filters.priceUnit" class="filter-select price-unit-select">
                <option value="day">dzień</option>
                <option value="week">tydzień</option>
                <option value="month">miesiąc</option>
                <option value="year">rok</option>
                <option value="sqm">m²</option>
              </select>
            </div>
          </div>

          <!-- Rental Period -->
          <div class="filter-group">
            <label class="filter-label">Czas wynajmu</label>
            <select v-model="filters.rentalPeriod" class="filter-select">
              <option value="">Wszystkie</option>
              <option value="short_term">Krótkoterminowy (&lt;1 miesiąc)</option>
              <option value="long_term">Długoterminowy</option>
            </select>
          </div>

          <!-- Size Range -->
          <div class="filter-group">
            <label class="filter-label">Szerokość (m)</label>
            <div class="range-inputs">
              <input 
                v-model.number="filters.widthFrom" 
                type="number" 
                placeholder="Od"
                class="filter-input"
              />
              <span>-</span>
              <input 
                v-model.number="filters.widthTo" 
                type="number" 
                placeholder="Do"
                class="filter-input"
              />
            </div>
          </div>

          <div class="filter-group">
            <label class="filter-label">Wysokość (m)</label>
            <div class="range-inputs">
              <input 
                v-model.number="filters.heightFrom" 
                type="number" 
                placeholder="Od"
                class="filter-input"
              />
              <span>-</span>
              <input 
                v-model.number="filters.heightTo" 
                type="number" 
                placeholder="Do"
                class="filter-input"
              />
            </div>
          </div>

          <!-- Orientation -->
          <div class="filter-group">
            <label class="filter-label">Orientacja</label>
            <select v-model="filters.orientation" class="filter-select">
              <option value="">Wszystkie</option>
              <option value="vertical">Pion</option>
              <option value="horizontal">Poziom</option>
            </select>
          </div>

          <!-- Location Filters -->
          <div class="filter-group">
            <label class="filter-label">Miasto</label>
            <input 
              v-model="filters.city" 
              type="text" 
              placeholder="Wpisz miasto..."
              class="filter-input"
            />
          </div>

          <div class="filter-group">
            <label class="filter-label">Województwo</label>
            <select v-model="filters.region" class="filter-select">
              <option value="">Wszystkie</option>
              <option value="dolnoslaskie">Dolnośląskie</option>
              <option value="kujawsko-pomorskie">Kujawsko-pomorskie</option>
              <option value="lubelskie">Lubelskie</option>
              <option value="lubuskie">Lubuskie</option>
              <option value="lodzkie">Łódzkie</option>
              <option value="malopolskie">Małopolskie</option>
              <option value="mazowieckie">Mazowieckie</option>
              <option value="opolskie">Opolskie</option>
              <option value="podkarpackie">Podkarpackie</option>
              <option value="podlaskie">Podlaskie</option>
              <option value="pomorskie">Pomorskie</option>
              <option value="slaskie">Śląskie</option>
              <option value="swietokrzyskie">Świętokrzyskie</option>
              <option value="warminsko-mazurskie">Warmińsko-mazurskie</option>
              <option value="wielkopolskie">Wielkopolskie</option>
              <option value="zachodniopomorskie">Zachodniopomorskie</option>
            </select>
          </div>

          <!-- Traffic Intensity -->
          <div class="filter-group">
            <label class="filter-label">Natężenie ruchu</label>
            <select v-model="filters.trafficIntensity" class="filter-select">
              <option value="">Wszystkie</option>
              <option value="low">Niskie</option>
              <option value="medium">Średnie</option>
              <option value="high">Wysokie</option>
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
                  <input type="checkbox" value="active" v-model="filters.status">
                  <span>Wolne</span>
                </label>
                <label class="checkbox-option">
                  <input type="checkbox" value="reserved" v-model="filters.status">
                  <span>Zarezerwowane</span>
                </label>
                <label class="checkbox-option">
                  <input type="checkbox" value="soon" v-model="filters.status">
                  <span>Wkrótce dostępne</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Offer Type -->
          <div class="filter-group">
            <label class="filter-label">Rodzaj oferty</label>
            <select v-model="filters.offerType" class="filter-select">
              <option value="">Wszystkie</option>
              <option value="owner">Właściciel</option>
              <option value="agency">Agencja</option>
            </select>
          </div>

          <!-- Feature Filters -->
          <div class="filter-group">
            <label class="checkbox-label">
              <input v-model="filters.onlyWithImage" type="checkbox" class="filter-checkbox" />
              <span>Tylko ze zdjęciem</span>
            </label>
          </div>

          <div class="filter-group">
            <label class="checkbox-label">
              <input v-model="filters.hasLighting" type="checkbox" class="filter-checkbox" />
              <span>Z podświetleniem</span>
            </label>
          </div>

          <div class="filter-group">
            <label class="checkbox-label">
              <input v-model="filters.priceIncludesPrint" type="checkbox" class="filter-checkbox" />
              <span>Druk i montaż w cenie</span>
            </label>
          </div>

          <div class="filter-group">
            <label class="checkbox-label">
              <input v-model="filters.graphicDesignHelp" type="checkbox" class="filter-checkbox" />
              <span>Pomoc przy projekcie graficznym</span>
            </label>
          </div>

          <div class="filter-group">
            <label class="checkbox-label">
              <input v-model="filters.hasVatInvoice" type="checkbox" class="filter-checkbox" />
              <span>Faktura VAT</span>
            </label>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="clearFilters" class="btn-secondary">Wyczyść</button>
          <button @click="showFiltersModal = false" class="btn-primary">Zastosuj</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.advertisements-page {
  min-height: 100vh;
  background: #f9fafb;
  display: flex;
  flex-direction: column;
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
}

.ads-list-container {
  background: white;
  border-right: 2px solid #e5e7eb;
  overflow-y: auto;
  height: calc(100vh - 80px);
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

.ads-list {
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.ads-list.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
}

.ads-list.grid .ad-list-item {
  flex-direction: column;
}

.ads-list.grid .ad-link {
  flex-direction: column;
  padding: 0;
}

.ads-list.grid .ad-image {
  width: 100%;
  height: 180px;
  border-radius: 8px 8px 0 0;
}

.ads-list.grid .ad-content {
  padding: 1rem;
}

.ads-list.grid .ad-details {
  flex-direction: column;
  align-items: flex-start;
  gap: 0.5rem;
}

.ad-list-item {
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.2s;
  cursor: pointer;
  background: white;
}

.ad-list-item:hover,
.ad-list-item.hovered {
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
  transform: translateX(-4px);
}

.ad-list-item.selected {
  border-color: #667eea;
  box-shadow: 0 6px 16px rgba(102, 126, 234, 0.25);
  background: #f0f4ff;
}

.ad-link {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  text-decoration: none;
  color: inherit;
}

.ad-image {
  width: 120px;
  height: 120px;
  flex-shrink: 0;
  border-radius: 8px;
  overflow: hidden;
  background: #f3f4f6;
  position: relative;
}

.ad-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.no-image {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
}

.ad-type-badge {
  position: absolute;
  top: 0.5rem;
  left: 0.5rem;
  color: white;
  padding: 0.25rem 0.625rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.ad-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-width: 0;
}

.ad-title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 700;
  color: #1f2937;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
}

.ad-location {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  color: #6b7280;
  font-size: 0.9rem;
}

.ad-details {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
}

.ad-size {
  color: #6b7280;
  font-size: 0.875rem;
}

.ad-price {
  font-weight: 700;
  color: #667eea;
  font-size: 1.125rem;
}

.map-container-wrapper {
  position: relative;
  background: #e5e7eb;
}

.map-container {
  width: 100%;
  height: 100%;
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

.filter-group {
  margin-bottom: 1.5rem;
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
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background 0.2s;
}

.checkbox-option:hover {
  background: #f9fafb;
}

.checkbox-option input[type="checkbox"] {
  width: 18px;
  height: 18px;
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
  }

  .ads-list-container {
    border-right: none;
    border-bottom: 2px solid #e5e7eb;
    height: auto;
  }
}

@media (max-width: 640px) {
  .search-bar {
    flex-direction: column;
    align-items: stretch;
    padding: 1rem;
  }

  .search-container {
    max-width: none;
  }

  .results-count {
    text-align: center;
  }

  .content-wrapper {
    grid-template-rows: 1fr 300px;
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
</style>
