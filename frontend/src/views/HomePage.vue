<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import EmailModal from '../components/EmailModal.vue'
import HeroBanner from '../components/HeroBanner.vue'
import PolandMap from '../components/PolandMap.vue'
import AdGrid from '../components/AdGrid.vue'
import { supabase } from '../lib/supabase'
import type { Advertisement } from '../lib/supabase'

const emit = defineEmits<{
  toggleFavorite: [id: string]
  toggleComparison: [id: string]
}>()

const isModalOpen = ref(false)
const advertisements = ref<Advertisement[]>([])
const isLoading = ref(true)
const viewMode = ref<'grid' | 'list'>('grid')
const sortBy = ref('newest')
const priceDisplay = ref<'day' | 'week' | 'month' | 'year' | 'sqm'>('month')

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
  status: string
  hasLighting: boolean
  onlyWithImage: boolean
  priceIncludesPrint: boolean
  graphicDesignHelp: boolean
  offerType: string
  hasVatInvoice: boolean
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
  status: '',
  hasLighting: false,
  onlyWithImage: false,
  priceIncludesPrint: false,
  graphicDesignHelp: false,
  offerType: '',
  hasVatInvoice: false,
})

const sortedAndFilteredAdvertisements = computed(() => {
  let filtered = advertisements.value

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

  if (filters.value.status) {
    filtered = filtered.filter(ad => ad.status === filters.value.status)
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

const handleSearch = (searchFilters: Filters) => {
  filters.value = searchFilters

  const mapSection = document.querySelector('.map-section')
  if (mapSection) {
    mapSection.scrollIntoView({ behavior: 'smooth' })
  }
}

const handleReset = (resetFilters: Filters) => {
  filters.value = resetFilters
}

const loadAdvertisements = async () => {
  try {
    isLoading.value = true
    const { data, error } = await supabase
      .from('advertisements')
      .select('*')
      .eq('status', 'active')
      .order('created_at', { ascending: false })

    if (error) throw error
    advertisements.value = data || []
  } catch (error) {
    console.error('Error loading advertisements:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadAdvertisements()
})
</script>

<template>
  <div>
    <EmailModal :is-open="isModalOpen" @close="isModalOpen = false" />
    <HeroBanner @search="handleSearch" @reset="handleReset" />
    <PolandMap 
      :advertisements="sortedAndFilteredAdvertisements" 
      :selected-region="filters.region"
      :selected-city="filters.city"
    />
    <AdGrid
      :advertisements="sortedAndFilteredAdvertisements"
      :is-loading="isLoading"
      :view-mode="viewMode"
      :sort-by="sortBy"
      :price-display="priceDisplay"
      @toggle-favorite="$emit('toggleFavorite', $event)"
      @toggle-comparison="$emit('toggleComparison', $event)"
      @update:view-mode="viewMode = $event"
      @update:sort-by="sortBy = $event"
    />
  </div>
</template>
