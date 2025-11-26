<script setup lang="ts">
import { ref } from 'vue'
import AdCard from './AdCard.vue'
import type { Advertisement } from '../lib/supabase'

const props = defineProps<{
  advertisements: Advertisement[]
  isLoading?: boolean
  viewMode?: 'grid' | 'list'
  sortBy?: string
  priceDisplay?: 'day' | 'week' | 'month' | 'year' | 'sqm'
}>()

const emit = defineEmits<{
  toggleFavorite: [id: string]
  toggleComparison: [id: string]
  'update:viewMode': [mode: 'grid' | 'list']
  'update:sortBy': [sort: string]
}>()

const sortBy = ref(props.sortBy || 'newest')

const isFavorite = (id: string) => {
  const favorites = JSON.parse(localStorage.getItem('favorites') || '[]')
  return favorites.includes(id)
}

const isInComparison = (id: string) => {
  const comparison = JSON.parse(localStorage.getItem('comparison') || '[]')
  return comparison.includes(id)
}
</script>

<template>
  <section class="ads-section">
    <div class="container">
      <div class="section-header">
        <div class="header-left">
          <h2 class="section-title">Dostępne ogłoszenia</h2>
          <p class="section-subtitle">
            Znaleziono {{ advertisements.length }}
            {{ advertisements.length === 1 ? 'ogłoszenie' : advertisements.length < 5 ? 'ogłoszenia' : 'ogłoszeń' }}
          </p>
        </div>
        <div class="header-right">
          <div class="view-switcher">
            <button
              @click="emit('update:viewMode', 'grid')"
              class="view-btn"
              :class="{ active: viewMode === 'grid' }"
              title="Widok kafelków"
            >
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="3" width="7" height="7" :stroke="viewMode === 'grid' ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
                <rect x="14" y="3" width="7" height="7" :stroke="viewMode === 'grid' ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
                <rect x="3" y="14" width="7" height="7" :stroke="viewMode === 'grid' ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
                <rect x="14" y="14" width="7" height="7" :stroke="viewMode === 'grid' ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
              </svg>
            </button>
            <button
              @click="emit('update:viewMode', 'list')"
              class="view-btn"
              :class="{ active: viewMode === 'list' }"
              title="Widok listy"
            >
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" :stroke="viewMode === 'list' ? '#667eea' : 'currentColor'" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </button>
          </div>
          <select v-model="sortBy" @change="emit('update:sortBy', sortBy)" class="sort-select">
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
        </div>
      </div>

      <div v-if="isLoading" class="loading-state">
        <div class="spinner"></div>
        <p>Ładowanie ogłoszeń...</p>
      </div>

      <div v-else-if="advertisements.length === 0" class="empty-state">
        <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="40" cy="40" r="40" fill="#F3F4F6"/>
          <path d="M40 50C45.5228 50 50 45.5228 50 40C50 34.4772 45.5228 30 40 30C34.4772 30 30 34.4772 30 40C30 45.5228 34.4772 50 40 50Z" stroke="#9CA3AF" stroke-width="2"/>
          <path d="M48 48L58 58" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <h3>Nie znaleziono ogłoszeń</h3>
        <p>Spróbuj zmienić kryteria wyszukiwania</p>
      </div>

      <div v-else :class="viewMode === 'grid' ? 'ads-grid' : 'ads-list'">
        <AdCard
          v-for="ad in advertisements"
          :key="ad.id"
          :ad="ad"
          :is-favorite="isFavorite(ad.id)"
          :is-in-comparison="isInComparison(ad.id)"
          :view-mode="viewMode"
          :price-display="priceDisplay || 'month'"
          @toggle-favorite="emit('toggleFavorite', $event)"
          @toggle-comparison="emit('toggleComparison', $event)"
        />
      </div>
    </div>
  </section>
</template>

<style scoped>
.ads-section {
  padding: 4rem 0;
  background: white;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 3rem;
  gap: 2rem;
}

.header-left {
  flex: 1;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.section-title {
  font-size: 2.5rem;
  font-weight: 800;
  color: #1F2937;
  margin: 0 0 0.5rem 0;
}

.section-subtitle {
  font-size: 1.1rem;
  color: #6B7280;
  margin: 0;
}

.view-switcher {
  display: flex;
  gap: 0.5rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  padding: 0.25rem;
  background: white;
}

.view-btn {
  padding: 0.5rem;
  background: transparent;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.view-btn:hover {
  background: #f3f4f6;
}

.view-btn.active {
  background: #f0f3ff;
  color: #667eea;
}

.sort-select {
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  background: white;
  color: #374151;
  font-size: 0.95rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  min-width: 200px;
}

.sort-select:hover {
  border-color: #667eea;
}

.sort-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.ads-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 2rem;
}

.ads-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
}

.loading-state p,
.empty-state p {
  color: #6B7280;
  font-size: 1.1rem;
  margin: 1rem 0 0 0;
}

.empty-state h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1F2937;
  margin: 1.5rem 0 0.5rem 0;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #F3F4F6;
  border-top-color: #4F46E5;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 1024px) {
  .ads-grid {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
  }
}

@media (max-width: 768px) {
  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1.5rem;
  }

  .header-right {
    width: 100%;
    flex-direction: column;
  }

  .sort-select {
    width: 100%;
  }

  .view-switcher {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 640px) {
  .ads-section {
    padding: 3rem 0;
  }

  .section-header {
    margin-bottom: 2rem;
  }

  .section-title {
    font-size: 2rem;
  }

  .section-subtitle {
    font-size: 1rem;
  }

  .ads-grid {
    grid-template-columns: 1fr;
    gap: 1.25rem;
  }
}
</style>
