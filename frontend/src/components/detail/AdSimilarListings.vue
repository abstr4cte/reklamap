<script setup lang="ts">
import WebPImage from '../WebPImage.vue'
import { slugify } from '../../utils/slugify'
import { mapTypeToUrlFormat } from '../../utils/typeMapping'
import { formatPrice } from '../../utils/formatPrice'
import type { Advertisement } from '../../types'
import { useSearchStore } from '../../stores/useSearchStore'

const props = defineProps<{
  similarAds: Advertisement[]
  getTypeLabel: (type: string) => string
}>()

const searchStore = useSearchStore()

const getPriceWithUnit = (ad: Advertisement) => {
  const priceLabel = searchStore.getPriceLabel(ad.price_unit || 'month', ad)
  return `${formatPrice(ad.price)} PLN${priceLabel}`
}

</script>

<template>
  <div v-if="similarAds.length > 0" class="similar-listings">
    <h3>Podobne oferty</h3>
    <div class="similar-listings-list">
      <router-link
        v-for="similarAd in similarAds"
        :key="similarAd.id"
        :to="`/powierzchnia-reklamowa/${mapTypeToUrlFormat(similarAd.type)}/${slugify(similarAd.city)}/${slugify(similarAd.title)}-${similarAd.id}`"
        class="similar-listing-card"
      >
        <div class="similar-listing-image">
          <WebPImage v-if="similarAd.image_url" :src="similarAd.image_url" :alt="`${getTypeLabel(similarAd.type)} ${similarAd.city} - ${similarAd.title}`" />
          <div v-else class="similar-listing-no-image">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
              <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
              <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
              <path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2"/>
            </svg>
          </div>
        </div>
        <div class="similar-listing-content">
          <h4>{{ similarAd.title }}</h4>
          <div class="similar-listing-price">{{ getPriceWithUnit(similarAd) }}</div>
          <div class="similar-listing-location">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M8 8C9.1 8 10 7.1 10 6C10 4.9 9.1 4 8 4C6.9 4 6 4.9 6 6C6 7.1 6.9 8 8 8Z" stroke="#6B7280" stroke-width="1.3"/>
              <path d="M8 14C8 14 12 10.5 12 6C12 3.79 10.21 2 8 2C5.79 2 4 3.79 4 6C4 10.5 8 14 8 14Z" stroke="#6B7280" stroke-width="1.3"/>
            </svg>
            <span>{{ similarAd.city }}</span>
          </div>
        </div>
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.similar-listings {
  margin-top: 3rem;
  background: var(--card-bg, white);
  padding: 2.5rem;
  border-radius: 20px;
  box-shadow: var(--card-shadow, 0 4px 6px -1px rgba(0, 0, 0, 0.1));
}

.similar-listings h3 {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 2rem;
  color: var(--text-main, #1f2937);
}

.similar-listings-list {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}

.similar-listing-card {
  display: flex;
  gap: 1rem;
  background: var(--card-bg, white);
  padding: 1rem;
  border-radius: 12px;
  text-decoration: none;
  transition: all 0.3s ease;
  border: 2px solid var(--border-color, #e5e7eb);
  color: var(--text-main);
}

.similar-listing-card:hover {
  transform: translateY(-4px);
  border-color: #667eea;
  box-shadow: 0 8px 16px -4px rgba(102, 126, 234, 0.2);
}

.similar-listing-image {
  width: 100px;
  height: 80px;
  border-radius: 8px;
  overflow: hidden;
  flex-shrink: 0;
}

.similar-listing-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.similar-listing-no-image {
  width: 100%;
  height: 100%;
  background: var(--bg-tertiary, #f3f4f6);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-muted, #9ca3af);
}

.similar-listing-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 0.5rem;
}

.similar-listing-content h4 {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-main, #1f2937);
  margin: 0;
  line-height: 1.3;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
}

.similar-listing-price {
  font-weight: 700;
  color: #667eea;
  font-size: 1rem;
}

.similar-listing-location {
  font-size: 0.875rem;
  color: var(--text-muted, #6b7280);
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.similar-listing-location svg {
  flex-shrink: 0;
}

@media (max-width: 768px) {
  .similar-listings {
    padding: 1.5rem;
    margin-top: 2rem;
  }

  .similar-listings h3 {
    font-size: 1.25rem;
    margin-bottom: 1.5rem;
  }

  .similar-listings-list {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .similar-listing-image {
    width: 80px;
    height: 60px;
  }

  .similar-listing-content h4 {
    font-size: 0.95rem;
  }

  .similar-listing-price {
    font-size: 0.9rem;
  }
}
</style>
