<script setup lang="ts">
import { computed } from 'vue'
import type { Advertisement } from '../lib/supabase'

const props = defineProps<{
  ad: Advertisement
  isFavorite: boolean
  isInComparison: boolean
  viewMode?: 'grid' | 'list'
  priceDisplay?: 'day' | 'week' | 'month' | 'year' | 'sqm'
}>()

const emit = defineEmits<{
  toggleFavorite: [id: string]
  toggleComparison: [id: string]
}>()

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

const displayPrice = computed(() => {
  const basePrice = props.ad.price
  const display = props.priceDisplay || 'month'

  switch (display) {
    case 'day':
      return `${Math.round(basePrice / 30).toLocaleString('pl-PL')} zł/dzień`
    case 'week':
      return `${Math.round(basePrice / 4).toLocaleString('pl-PL')} zł/tydzień`
    case 'month':
      return `${basePrice.toLocaleString('pl-PL')} zł/mies.`
    case 'year':
      return `${(basePrice * 12).toLocaleString('pl-PL')} zł/rok`
    case 'sqm':
      const area = props.ad.width && props.ad.height ? props.ad.width * props.ad.height : 1
      return `${Math.round(basePrice / area).toLocaleString('pl-PL')} zł/m²`
    default:
      return `${basePrice.toLocaleString('pl-PL')} zł/mies.`
  }
})

// Clean description without image data
const cleanDescription = computed(() => {
  if (!props.ad.description) return ''
  return props.ad.description.replace(/\n\n\[IMAGES\].*?\[\/IMAGES\]/s, '')
})

const priceLabel = computed(() => {
  const display = props.priceDisplay || 'month'

  switch (display) {
    case 'day':
      return '/dzień'
    case 'week':
      return '/tydzień'
    case 'month':
      return '/miesiąc'
    case 'year':
      return '/rok'
    case 'sqm':
      return '/m²'
    default:
      return '/miesiąc'
  }
})
</script>

<template>
  <router-link :to="`/ogloszenie/${ad.id}`" class="ad-card" :class="{ 'list-view': viewMode === 'list' }">
    <div class="card-image">
      <img
        :src="ad.image_url || 'https://images.pexels.com/photos/417273/pexels-photo-417273.jpeg?auto=compress&cs=tinysrgb&w=800'"
        :alt="ad.title"
      />
      <div class="card-badge" :style="{ background: typeColors[ad.type] || '#6B7280' }">
        {{ typeLabels[ad.type] || ad.type }}
      </div>
      <div class="card-actions">
        <button
          @click="handleFavoriteClick"
          class="action-btn favorite-btn"
          :class="{ active: isFavorite }"
          :aria-label="isFavorite ? 'Usuń z ulubionych' : 'Dodaj do ulubionych'"
        >
          <svg width="22" height="22" viewBox="0 0 24 24" :fill="isFavorite ? '#EF4444' : 'none'">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" :stroke="isFavorite ? '#EF4444' : 'white'" stroke-width="2"/>
          </svg>
        </button>
        <button
          @click="handleComparisonClick"
          class="action-btn comparison-btn"
          :class="{ active: isInComparison }"
          :aria-label="isInComparison ? 'Usuń z porównania' : 'Dodaj do porównania'"
        >
          <svg width="22" height="22" viewBox="0 0 24 24" :fill="isInComparison ? '#667eea' : 'none'">
            <rect x="3" y="3" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'white'" stroke-width="2" rx="1"/>
            <rect x="14" y="3" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'white'" stroke-width="2" rx="1"/>
            <rect x="3" y="14" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'white'" stroke-width="2" rx="1"/>
            <rect x="14" y="14" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'white'" stroke-width="2" rx="1"/>
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
        <span>{{ ad.location }}, {{ ad.city }}</span>
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
          <span class="price-amount">{{ parseFloat(displayPrice).toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} zł</span>
          <span class="price-period">{{ priceLabel }}</span>
        </div>

        <div class="card-button">
          Zobacz szczegóły
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      </div>
    </div>
  </router-link>
</template>

<style scoped>
.ad-card {
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

.ad-card:hover {
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

.ad-card:hover .card-image img {
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
.ad-card.list-view {
  flex-direction: row;
  height: auto;
}

.ad-card.list-view .card-image {
  width: 280px;
  height: 200px;
  flex-shrink: 0;
}

.ad-card.list-view .card-content {
  flex: 1;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.ad-card.list-view .card-title {
  font-size: 1.5rem;
  margin-bottom: 0.75rem;
}

.ad-card.list-view .card-location,
.ad-card.list-view .card-dimensions {
  font-size: 1rem;
}

.ad-card.list-view .card-description {
  display: block;
  margin: 1rem 0;
}

.ad-card.list-view .card-footer {
  margin-top: auto;
}

@media (max-width: 1024px) {
  .ad-card.list-view {
    flex-direction: column;
  }

  .ad-card.list-view .card-image {
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
