<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import type { Advertisement } from '../types'
import { slugify } from '../utils/slugify'
import { usePreferencesStore } from '../stores/usePreferencesStore'
import { useSearchStore, typeColors } from '../stores/useSearchStore'
import { mapTypeToUrlFormat } from '../utils/typeMapping'

import WebPImage from './WebPImage.vue'

const props = defineProps<{
  ad: Advertisement
  isFavorite?: boolean
  isInComparison?: boolean
  viewMode?: 'grid' | 'list'
  priceDisplay?: 'day' | 'week' | 'month' | 'year' | 'sqm' | 'campaign' | null
}>()

const prefStore = usePreferencesStore()
const searchStore = useSearchStore()

const localIsFavorite = computed(() => prefStore.isFavorite(props.ad.id))
const localIsInComparison = computed(() => prefStore.isCompared(props.ad.id))

const emit = defineEmits<{
  toggleFavorite: [id: string]
  toggleComparison: [id: string]
  hoverStart: [id: string]
  hoverEnd: [id: null]
}>()

// Link helper
const adLink = computed(() => {
  const city = slugify(props.ad.city)
  const title = slugify(props.ad.title)
  const type = mapTypeToUrlFormat(props.ad.type)
  return `/powierzchnia-reklamowa/${type}/${city}/${title}-${props.ad.id}`
})

// Helpers from store
const getTypeLabel = (type: string) => searchStore.getTypeLabel(type)
const getStatusLabel = (ad: Advertisement) => searchStore.getStatusLabel(ad)
const getStatusColor = (ad: Advertisement) => searchStore.getStatusColor(ad)
const formatLocation = (loc: string, city: string) => searchStore.formatLocation(loc, city)

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
  const typeLabel = searchStore.getTypeLabel(props.ad.type)
  return `${typeLabel} ${props.ad.city} - ${props.ad.title}`
})

const displayPrice = computed(() => {
  const unit = props.priceDisplay || props.ad.price_unit || 'month'
  return Math.round(searchStore.getPrice(props.ad, unit as any))
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
  const displayUnit = props.priceDisplay || props.ad.price_unit || 'month'
  return searchStore.getPriceLabel(displayUnit as any, props.ad)
})


// Entrance animation via IntersectionObserver
const cardRef = ref<any>(null)
const isVisible = ref(false)

onMounted(() => {
  if (typeof IntersectionObserver === 'undefined') {
    isVisible.value = true
    return
  }
  
  const observer = new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting) {
        isVisible.value = true
        observer.disconnect()
      }
    },
    { threshold: 0.1 }
  )

  // cardRef might be the component proxy (router-link) or the DOM element itself
  // we check for $el (component) or the element itself
  const target = cardRef.value?.$el || cardRef.value
  
  if (target && target instanceof Element) {
    observer.observe(target)
  } else {
    // If we can't observe (e.g. no element found), fallback to visible
    isVisible.value = true
  }
})
</script>

<template>
  <router-link ref="cardRef" :to="adLink" class="listing-card" :class="{ 'list-view': viewMode === 'list', 'is-visible': isVisible }" @mouseenter="emit('hoverStart', ad.id)" @mouseleave="emit('hoverEnd', null)">
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
        {{ getTypeLabel(ad.type) }}
      </div>
      <div class="status-badge" :style="{ background: getStatusColor(ad) }">
        {{ getStatusLabel(ad) }}
      </div>
      <div v-if="(ad as any).estimated_daily_views" class="ots-badge">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/>
          <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
        </svg>
        <span>OTS: {{ (ad as any).estimated_daily_views.toLocaleString('pl-PL') }}</span>
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
  background: var(--card-bg, white);
  border-radius: 12px;
  overflow: hidden;
  box-shadow: var(--card-shadow, 0 2px 8px rgba(0, 0, 0, 0.08));
  transition: all 0.3s ease, opacity 0.5s ease, transform 0.5s ease;
  height: 100%;
  display: flex;
  flex-direction: column;
  text-decoration: none;
  color: var(--text-main, inherit);
  /* Entrance animation */
  opacity: 0;
  transform: translateY(20px);
}

.listing-card.is-visible {
  opacity: 1;
  transform: translateY(0);
}

.listing-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
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

.ots-badge {
  position: absolute;
  bottom: 1rem;
  right: 1rem;
  background: rgba(30, 41, 59, 0.7);
  color: white;
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-size: 0.7rem;
  font-weight: 700;
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  gap: 0.375rem;
  z-index: 5;
  border: 1px solid rgba(255, 255, 255, 0.1);
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
  color: var(--text-main, #1F2937);
  margin: 0;
  line-height: 1.3;
}

.card-location,
.card-dimensions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-muted, #6B7280);
  font-size: 0.9rem;
}

.card-description {
  color: var(--text-muted, #6b7280);
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
  border-top: 1px solid var(--border-color, #F3F4F6);
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
