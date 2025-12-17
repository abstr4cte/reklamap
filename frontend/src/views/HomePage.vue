<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import EmailModal from '../components/EmailModal.vue'
import HeroBanner from '../components/HeroBanner.vue'
import PolandMap from '../components/PolandMap.vue'
import AdGrid from '../components/AdGrid.vue'
import Pagination from '../components/Pagination.vue'
import { api } from '../services/api'
import type { Advertisement } from '../types'
import { filtersToQueryParams, queryParamsToFilters } from '../utils/filterUtils'
import { useSeo } from '../composables/useSeo'

const emit = defineEmits<{
  toggleFavorite: [id: string]
  toggleComparison: [id: string]
}>()

const route = useRoute()
const router = useRouter()

const isModalOpen = ref(false)
const advertisements = ref<Advertisement[]>([])
const isLoading = ref(true)
const viewMode = ref<'grid' | 'list'>('grid')
const sortBy = ref('newest')
const priceDisplay = ref<'day' | 'week' | 'month' | 'year' | 'sqm'>('month')
const currentPage = ref(1)
const itemsPerPage = 20
const hoveredAdId = ref<string | null>(null)

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
  trafficIntensity: string
  status: string[]
  hasLighting: boolean
  onlyWithImage: boolean
  priceIncludesPrint: boolean
  graphicDesignHelp: boolean
  offerType: string
  hasVatInvoice: boolean
  selectedLocationCoords?: { lat: number; lng: number } | null
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
  trafficIntensity: '',
  status: [],
  hasLighting: false,
  onlyWithImage: false,
  priceIncludesPrint: false,
  graphicDesignHelp: false,
  offerType: '',
  hasVatInvoice: false,
  selectedLocationCoords: null,
})

const sortedAndFilteredAdvertisements = computed(() => {
  let filtered = advertisements.value
  
  // Dodanie zależności od sortBy i priceDisplay, aby computed się przeliczał
  const currentSort = sortBy.value
  const currentPriceDisplay = priceDisplay.value

  if (filters.value.keyword) {
    const keyword = filters.value.keyword.toLowerCase()
    filtered = filtered.filter(ad =>
      ad.title.toLowerCase().includes(keyword) ||
      ad.description.toLowerCase().includes(keyword) ||
      ad.location.toLowerCase().includes(keyword)
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

  if (filters.value.trafficIntensity) {
    filtered = filtered.filter(ad => ad.traffic_intensity === filters.value.trafficIntensity)
  }

  if (filters.value.status && filters.value.status.length > 0) {
    filtered = filtered.filter(ad => filters.value.status.includes(ad.display_status || ad.status))
  }

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

  if (filters.value.offerType) {
    filtered = filtered.filter(ad => ad.offer_type === filters.value.offerType)
  }

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
  return Math.ceil(sortedAndFilteredAdvertisements.value.length / itemsPerPage)
})

const paginatedAdvertisements = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return sortedAndFilteredAdvertisements.value.slice(start, end)
})

const handlePageChange = (page: number) => {
  currentPage.value = page
  router.push({ query: { ...route.query, page: page.toString() } })
  
  // Scroll to top of ads section
  const adsSection = document.querySelector('.ads-section')
  if (adsSection) {
    adsSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

const handleSearch = (searchFilters: Filters) => {
  filters.value = searchFilters
  currentPage.value = 1 // Reset to first page on search
  
  // Konwertuj filtry na query params
  const queryParams = filtersToQueryParams(searchFilters)
  
  // Dodaj parametr strony
  queryParams.page = '1'
  
  // Use history.replaceState to update URL without triggering navigation
  const newUrl = window.location.pathname + '?' + new URLSearchParams(queryParams).toString()
  window.history.replaceState({}, document.title, newUrl)
  
  // No need to scroll here as HeroBanner component will handle scrolling
}

const handleReset = (resetFilters: Filters) => {
  filters.value = resetFilters
  currentPage.value = 1 // Reset to first page
  
  // Prevent scrolling by using replaceState instead of router.push
  const currentPosition = window.scrollY
  window.history.replaceState({}, document.title, window.location.pathname)
  
  // Ensure we stay at the current scroll position
  setTimeout(() => {
    window.scrollTo(0, currentPosition)
  }, 0)
}

const loadAdvertisements = async () => {
  try {
    isLoading.value = true
    const data = await api.getAdvertisements()
    // Backend returns only active advertisements
    advertisements.value = data
      .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
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
    icon: '🪧',
    description: 'Duże formaty przy drogach krajowych i autostradach, zapewniające wysoką widoczność dla kampanii wizerunkowych.'
  },
  {
    name: 'Citylighty',
    slug: 'citylighty',
    icon: '💡',
    description: 'Podświetlane witryny w centrach miast, przy przystankach i galeriach, gwarantujące stałą ekspozycję.'
  },
  {
    name: 'Ekrany LED',
    slug: 'ekrany-led',
    icon: '📺',
    description: 'Cyfrowe wyświetlacze dynamiczne umożliwiające animacje i spoty wideo, idealne do nowoczesnych kampanii.'
  },
  {
    name: 'Banery',
    slug: 'banery',
    icon: '🎯',
    description: 'Elastyczne powierzchnie montowane na budynkach i płotach, łatwe do dopasowania do dostępnej przestrzeni.'
  },
  {
    name: 'Ściany reklamowe',
    slug: 'sciany-reklamowe',
    icon: '🧱',
    description: 'Murale i reklamy wielkoformatowe na elewacjach budynków, przyciągające uwagę w przestrzeni miejskiej.'
  },
  {
    name: 'Totemy reklamowe',
    slug: 'totemy-reklamowe',
    icon: '📍',
    description: 'Wysokie, wolnostojące słupy w centrach handlowych i placach, skuteczne w zwiększaniu rozpoznawalności marki.'
  },
  {
    name: 'Reklama w transporcie',
    slug: 'reklama-w-transporcie',
    icon: '🚌',
    description: 'Nośniki umieszczone na autobusach, tramwajach, metrze i przystankach, docierające do szerokiego grona odbiorców.'
  },
  {
    name: 'Reklama mobilna',
    slug: 'reklama-mobilna',
    icon: '🚚',
    description: 'Ruchome formaty, takie jak przyczepki i samochody firmowe, pozwalające dotrzeć z przekazem tam, gdzie jest grupa docelowa.'
  },
  {
    name: 'Inne',
    slug: 'inne',
    icon: '✨',
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
  }
})
</script>

<template>
  <div>
    <EmailModal :is-open="isModalOpen" @close="isModalOpen = false" />
    <HeroBanner @search="handleSearch" @reset="handleReset" />
    
    <!-- Categories Section -->
    <section class="categories-section">
      <div class="categories-container">
        <h2 class="categories-title">Przeglądaj kategorie powierzchni reklamowych</h2>
        <div class="categories-grid">
          <router-link
            v-for="category in categories"
            :key="category.slug"
            :to="`/powierzchnie-reklamowe/${category.slug}`"
            class="category-card"
          >
            <div class="category-icon">{{ category.icon }}</div>
            <h3 class="category-name">{{ category.name }}</h3>
            <p class="category-description">{{ category.description }}</p>
            <div class="category-arrow">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M5 12h14m-7-7l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>
        </div>
      </div>
    </section>

    <!-- Popular Cities Section -->
    <section class="cities-section">
      <div class="cities-container">
        <h2 class="cities-title">Popularne miasta</h2>
        <p class="cities-subtitle">Znajdź powierzchnie reklamowe w największych miastach Polski</p>
        <div class="cities-grid">
          <router-link
            v-for="city in popularCities"
            :key="city.slug"
            :to="`/powierzchnie-reklamowe/${city.slug}`"
            class="city-card"
          >
            <div class="city-name">{{ city.name }}</div>
            <div class="city-region">{{ city.region }}</div>
            <div class="city-arrow">→</div>
          </router-link>
        </div>
      </div>
    </section>
    
    <PolandMap 
      :advertisements="sortedAndFilteredAdvertisements" 
      :selected-region="filters.region"
      :selected-city="filters.city"
      :selected-location-coords="filters.selectedLocationCoords"
      :hovered-ad-id="hoveredAdId"
    />
    <AdGrid
      :advertisements="paginatedAdvertisements"
      :is-loading="isLoading"
      :view-mode="viewMode"
      :sort-by="sortBy"
      :price-display="priceDisplay"
      @toggle-favorite="$emit('toggleFavorite', $event)"
      @toggle-comparison="$emit('toggleComparison', $event)"
      @update:view-mode="viewMode = $event"
      @update:sort-by="sortBy = $event"
      @update:hovered-ad-id="hoveredAdId = $event"
    />
    <Pagination
      v-if="!isLoading && paginatedAdvertisements.length > 0"
      :current-page="currentPage"
      :total-pages="totalPages"
      :total-items="sortedAndFilteredAdvertisements.length"
      :items-per-page="itemsPerPage"
      :show-info="true"
      @update:current-page="handlePageChange"
    />
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
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.category-card {
  background: white;
  border-radius: 20px;
  padding: 2.5rem;
  text-decoration: none;
  color: inherit;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  animation: fadeInUp 0.6s ease-out backwards;
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
  transform: translateY(-12px) scale(1.03);
  box-shadow: 0 20px 40px rgba(102, 126, 234, 0.25);
}

.category-card:hover::before {
  transform: scaleX(1);
}

.category-card:hover::after {
  width: 300px;
  height: 300px;
}

.category-icon {
  font-size: 3.5rem;
  margin-bottom: 0.5rem;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  position: relative;
  z-index: 1;
}

.category-card:hover .category-icon {
  transform: scale(1.2) rotate(5deg);
}

.category-name {
  font-size: 1.6rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
  position: relative;
  z-index: 1;
  transition: color 0.3s ease;
}

.category-card:hover .category-name {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.category-description {
  font-size: 1rem;
  color: #6b7280;
  margin: 0;
  flex: 1;
  position: relative;
  z-index: 1;
  line-height: 1.6;
}

.category-arrow {
  color: #667eea;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  position: relative;
  z-index: 1;
}

.category-card:hover .category-arrow {
  transform: translateX(8px) scale(1.2);
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
    gap: 1.5rem;
  }

  .category-card {
    padding: 2rem;
  }
  
  .category-icon {
    font-size: 3rem;
  }
  
  .category-name {
    font-size: 1.4rem;
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
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.75rem;
}

.city-card {
  background: white;
  border-radius: 16px;
  padding: 2rem 1.75rem;
  text-decoration: none;
  color: inherit;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  position: relative;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  animation: fadeInUp 0.6s ease-out backwards;
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
  height: 4px;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.city-card::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
  opacity: 0;
  transition: opacity 0.4s ease;
}

.city-card:hover {
  transform: translateY(-8px) scale(1.02);
  box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
}

.city-card:hover::before {
  transform: scaleX(1);
}

.city-card:hover::after {
  opacity: 1;
}

.city-name {
  font-size: 1.35rem;
  font-weight: 700;
  color: #1f2937;
  position: relative;
  z-index: 1;
  transition: color 0.3s ease;
}

.city-card:hover .city-name {
  color: #667eea;
}

.city-region {
  font-size: 0.9rem;
  color: #6b7280;
  text-transform: capitalize;
  position: relative;
  z-index: 1;
  font-weight: 500;
}

.city-arrow {
  position: absolute;
  top: 2rem;
  right: 1.75rem;
  font-size: 1.75rem;
  color: #667eea;
  opacity: 0;
  transform: translateX(-15px) rotate(-45deg);
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  z-index: 1;
}

.city-card:hover .city-arrow {
  opacity: 1;
  transform: translateX(0) rotate(0deg);
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
    gap: 1.25rem;
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
</style>

