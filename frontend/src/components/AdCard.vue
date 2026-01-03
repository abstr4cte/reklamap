<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, watch } from 'vue'
import type { Advertisement } from '../types'
import { slugify } from '../utils/slugify'

import WebPImage from './WebPImage.vue'

const props = defineProps<{
  ad: Advertisement
  isFavorite?: boolean
  isInComparison?: boolean
  viewMode?: 'grid' | 'list'
  priceDisplay?: 'day' | 'week' | 'month' | 'year' | 'sqm' | 'campaign'
}>()

const localIsFavorite = ref(props.isFavorite)
const localIsInComparison = ref(props.isInComparison)

// Watch props changes
watch(() => props.isFavorite, (newVal) => {
  localIsFavorite.value = newVal
})

watch(() => props.isInComparison, (newVal) => {
  localIsInComparison.value = newVal
})

// Listen to localStorage changes
const handleStorageChange = () => {
  const favorites = JSON.parse(localStorage.getItem('favorites') || '[]')
  const comparison = JSON.parse(localStorage.getItem('comparison') || '[]')
  localIsFavorite.value = favorites.includes(props.ad.id)
  localIsInComparison.value = comparison.includes(props.ad.id)
}

onMounted(() => {
  if (typeof window !== 'undefined') {
    window.addEventListener('localStorageChange', handleStorageChange)
    window.addEventListener('storage', handleStorageChange)
  }
})

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('localStorageChange', handleStorageChange)
    window.removeEventListener('storage', handleStorageChange)
  }
})

const emit = defineEmits<{
  toggleFavorite: [id: string]
  toggleComparison: [id: string]
  hoverStart: [id: string]
  hoverEnd: [id: null]
}>()

const mapTypeToUrlFormat = (type: string): string => {
  const typeMapping: Record<string, string> = {
    'billboard': 'billboardy',
    'citylight': 'citylighty',
    'led_screen': 'ekrany-led',
    'banner': 'banery',
    'wall': 'sciany-reklamowe',
    'totem': 'totemy-reklamowe',
    'transport': 'reklama-w-transporcie',
    'mobile': 'reklama-mobilna',
    'other': 'inne'
  }
  return typeMapping[type] || 'inne'
}

const adLink = computed(() => {
  const city = slugify(props.ad.city)
  const title = slugify(props.ad.title)
  const type = mapTypeToUrlFormat(props.ad.type)
  return `/powierzchnia-reklamowa/${type}/${city}/${title}-${props.ad.id}`
})

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

const handleFavoriteClick = (e: Event) => {
  e.preventDefault()
  e.stopPropagation()
  emit('toggleFavorite', props.ad.id)
}

const handleComparisonClick = (e: Event) => {
  e.preventDefault()
  e.stopPropagation()
  emit('toggleComparison', props.ad.id)
}

const imageAlt = computed(() => {
  const typeLabel = typeLabels[props.ad.type] || 'Powierzchnia reklamowa'
  return `${typeLabel} ${props.ad.city} - ${props.ad.title}`
})

// Funkcja do pobierania ceny - taka sama jak w AdvertisementsPage
const getPrice = (period: 'day' | 'week' | 'month' | 'year' | 'sqm' | 'campaign') => {
  const basePrice = props.ad.price
  const adPriceUnit = props.ad.price_unit || 'month'

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
      if (props.ad.campaign_duration) {
        return pricePerMonth * (props.ad.campaign_duration / 30)
      }
      // If no duration, return a very high number to sort at the end
      return Number.MAX_SAFE_INTEGER
    case 'sqm':
      const area = props.ad.width && props.ad.height ? props.ad.width * props.ad.height : 1
      return area > 0 ? pricePerMonth / area : Number.MAX_SAFE_INTEGER
    default:
      return pricePerMonth
  }
}

const displayPrice = computed(() => {
  // Use priceDisplay if provided (from sorting), otherwise use ad's price_unit
  const displayUnit = props.priceDisplay || props.ad.price_unit || 'month'
  return Math.round(getPrice(displayUnit as any))
})

// Clean description without image data
const cleanDescription = computed(() => {
  if (!props.ad.description) return ''
  return props.ad.description.replace(/\n\n\[IMAGES\].*?\[\/IMAGES\]/s, '')
})

// Check if price is estimated (converted from different unit)
const isEstimatedPrice = computed(() => {
  const displayUnit = props.priceDisplay || props.ad.price_unit || 'month'
  const adPriceUnit = props.ad.price_unit || 'month'
  return displayUnit !== adPriceUnit
})

// Check if data is missing for the requested display unit
const isMissingData = computed(() => {
  const displayUnit = props.priceDisplay || props.ad.price_unit || 'month'
  
  if (displayUnit === 'sqm') {
    const area = props.ad.width && props.ad.height ? props.ad.width * props.ad.height : 0
    return area === 0
  }
  
  if (displayUnit === 'campaign') {
    return !props.ad.campaign_duration
  }
  
  return false
})

const priceLabel = computed(() => {
  // Use priceDisplay if provided (from sorting), otherwise use ad's price_unit
  const displayUnit = props.priceDisplay || props.ad.price_unit || 'month'

  switch (displayUnit) {
    case 'day':
      // For LED screens, add "(emisję)"
      if (props.ad.type === 'led_screen') {
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
      if (props.ad.campaign_duration) {
        return `/kampanię (${props.ad.campaign_duration} dni)`
      }
      return '/kampanię'
    case 'sqm':
      return '/m²'
    default:
      return '/miesiąc'
  }
})

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

const statusLabel = computed(() => {
  const currentStatus = props.ad.display_status || props.ad.status
  
  // Debug log
  console.log('📊 Ogłoszenie ID:', props.ad.id, {
    'Status z bazy': props.ad.status,
    'Display status': props.ad.display_status,
    'Data dostępności': props.ad.available_from || 'brak',
    'Używany status': currentStatus
  })
  
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
})

const statusColor = computed(() => {
  const currentStatus = props.ad.display_status || props.ad.status
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
})



</script>

<template>
  <router-link :to="adLink" class="listing-card" :class="{ 'list-view': viewMode === 'list' }" @mouseenter="emit('hoverStart', ad.id)" @mouseleave="emit('hoverEnd', null)">
    <div class="card-image">
      <WebPImage
        v-if="ad.image_url"
        :src="ad.image_url"
        :alt="imageAlt"
        class="card-img"
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
      <div class="status-badge" :style="{ background: statusColor }">
        {{ statusLabel }}
      </div>
      <div class="card-actions">
        <button
          @click="handleFavoriteClick"
          class="action-btn favorite-btn"
          :class="{ active: localIsFavorite }"
          :aria-label="localIsFavorite ? 'Usuń z ulubionych' : 'Dodaj do ulubionych'"
        >
          <svg width="22" height="22" viewBox="0 0 24 24" :fill="localIsFavorite ? '#EF4444' : 'none'">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" :stroke="localIsFavorite ? '#EF4444' : 'white'" stroke-width="2"/>
          </svg>
        </button>
        <button
          @click="handleComparisonClick"
          class="action-btn comparison-btn"
          :class="{ active: localIsInComparison }"
          :aria-label="localIsInComparison ? 'Usuń z porównania' : 'Dodaj do porównania'"
        >
          <svg width="22" height="22" viewBox="0 0 24 24" :fill="localIsInComparison ? '#667eea' : 'none'">
            <rect x="3" y="3" width="7" height="7" :stroke="localIsInComparison ? '#667eea' : 'white'" stroke-width="2" rx="1"/>
            <rect x="14" y="3" width="7" height="7" :stroke="localIsInComparison ? '#667eea' : 'white'" stroke-width="2" rx="1"/>
            <rect x="3" y="14" width="7" height="7" :stroke="localIsInComparison ? '#667eea' : 'white'" stroke-width="2" rx="1"/>
            <rect x="14" y="14" width="7" height="7" :stroke="localIsInComparison ? '#667eea' : 'white'" stroke-width="2" rx="1"/>
          </svg>
        </button>
      </div>
    </div>

    <div class="card-content">
      <h3 class="card-title">{{ ad.title }}</h3>

      <div class="card-location">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M8 8C9.1 8 10 7.1 10 6C10 4.9 9.1 4 8 4C6.9 4 6 4.9 6 6C6 7.1 6.9 8 8 8Z" stroke="#6B7280" stroke-width="1.3"/>
          <path d="M8 14C8 14 12 10.5 12 6C12 3.79 10.21 2 8 2C5.79 2 4 3.79 4 6C4 10.5 8 14 8 14Z" stroke="#6B7280" stroke-width="1.3"/>
        </svg>
        <span>{{ formatLocation(ad.location, ad.city) }}</span>
      </div>

      <div v-if="ad.dimensions" class="card-dimensions">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="2" y="2" width="12" height="12" rx="1" stroke="#6B7280" stroke-width="1.3"/>
          <path d="M2 6H14M6 2V14" stroke="#6B7280" stroke-width="1.3"/>
        </svg>
        <span>{{ ad.dimensions }}</span>
      </div>

      <div v-if="cleanDescription" class="card-description">
        {{ cleanDescription }}
      </div>

      <div class="card-footer">
        <div class="card-price">
          <span v-if="isMissingData" class="missing-data-badge">Brak danych</span>
          <template v-else>
            <span class="price-amount">
              <span v-if="isEstimatedPrice" class="estimated-label">~</span>{{ displayPrice.toLocaleString('pl-PL') }} zł
            </span>
            <span class="price-period">
              {{ priceLabel }}<span v-if="isEstimatedPrice" class="estimated-info"> (szacunkowo)</span>
            </span>
            <span v-if="ad.price_negotiable" class="negotiable-badge">do negocjacji</span>
          </template>
        </div>
      </div>
    </div>
  </router-link>

</template>

<style scoped>
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
}

.listing-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
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

.listing-card:hover .card-image img {
  transform: scale(1.05);
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

.card-description {
  color: #6B7280;
  font-size: 0.9rem;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
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

.card-button {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.625rem 1.25rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.375rem;
  transition: all 0.3s ease;
}

.card-button:hover {
  transform: translateX(2px);
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

/* List View Styles */
.listing-card.list-view {
  flex-direction: row;
  height: auto;
}

.listing-card.list-view .card-image {
  width: 280px;
  height: 200px;
  flex-shrink: 0;
}

.listing-card.list-view .card-content {
  flex: 1;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.listing-card.list-view .card-title {
  font-size: 1.5rem;
  margin-bottom: 0.75rem;
}

.listing-card.list-view .card-location,
.listing-card.list-view .card-dimensions {
  font-size: 1rem;
}

.listing-card.list-view .card-description {
  display: block;
  margin: 1rem 0;
}

.listing-card.list-view .card-footer {
  margin-top: auto;
}

@media (max-width: 1024px) {
  .listing-card.list-view {
    flex-direction: column;
  }

  .listing-card.list-view .card-image {
    width: 100%;
    height: 220px;
  }
}

@media (max-width: 640px) {
  .card-image {
    height: 180px;
  }

  .card-title {
    font-size: 1.1rem;
  }

  .card-footer {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .card-button {
    width: 100%;
    justify-content: center;
  }
}
</style>
