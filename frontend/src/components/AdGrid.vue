<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, onDeactivated } from 'vue'
import { storeToRefs } from 'pinia'
import AdCard from './AdCard.vue'
import type { Advertisement } from '../types'
import { useSearchStore } from '../stores/useSearchStore'
import { filtersToQueryParams } from '../utils/filterUtils'
import { slugify } from '../utils/slugify'
import { mapTypeToUrlFormat } from '../utils/typeMapping'

const searchStore = useSearchStore()
const { filters } = storeToRefs(searchStore)

const props = defineProps<{
  listings: Advertisement[]
  isLoading?: boolean
  viewMode?: 'grid' | 'list'
  sortBy?: string
  priceDisplay?: 'day' | 'week' | 'month' | 'year' | 'sqm' | 'campaign' | null
  activeFiltersCount?: number
}>()

const showSortPanel = ref(false)
const localSortBy = ref(props.sortBy || 'newest')

const sortOptions = searchStore.sortOptions

const handleSortButtonClick = () => {
  showSortPanel.value = true
}

const handleSortOptionClick = (value: string) => {
  localSortBy.value = value
  emit('update:sortBy', value)
  showSortPanel.value = false
}

const handleFilterButtonClick = () => {
  const headerHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--header-height')) || 100
  const heroBanner = document.querySelector('[data-hero-banner]')
  if (heroBanner) {
    const elementPosition = heroBanner.getBoundingClientRect().top + window.pageYOffset
    const offsetPosition = elementPosition - headerHeight
    window.scrollTo({ top: offsetPosition, behavior: 'smooth' })
  }
}

const isMobile = ref(false)
const showMapButton = ref(false)
const showMapButtonDesktop = ref(false)

const checkIfMobile = () => {
  isMobile.value = window.innerWidth <= 768
}

const handleScroll = () => {
  const listingsSection = document.querySelector('.listings-section')
  const sectionHeader = document.querySelector('.section-header')
  const footer = document.querySelector('footer')
  const polandMap = document.querySelector('[data-poland-map]')
  
  if (sectionHeader && listingsSection) {
    const headerRect = sectionHeader.getBoundingClientRect()
    const listingsRect = listingsSection.getBoundingClientRect()
    const footerRect = footer?.getBoundingClientRect()
    const mapRect = polandMap?.getBoundingClientRect()
    
    // Show button when:
    // 1. Header is sticky (at top)
    // 2. We're in listings section (not at footer)
    // 3. Footer is not visible
    // 4. Map is below us (we've scrolled past it)
    const isHeaderSticky = headerRect.top <= 0
    const inListingsSection = listingsRect.top < window.innerHeight && listingsRect.bottom > 0
    const footerIsVisible = footerRect && footerRect.top < window.innerHeight
    const mapIsBelowUs = !mapRect || mapRect.bottom < 0 // Map is above viewport (we've passed it) or doesn't exist
    
    const shouldShowButton = isHeaderSticky && inListingsSection && !footerIsVisible && mapIsBelowUs
    
    if (isMobile.value) {
      showMapButton.value = shouldShowButton
    } else {
      showMapButtonDesktop.value = shouldShowButton
    }
  }
}

const goToPolandMap = () => {
  const mapContainer = document.querySelector('[data-poland-map] .map-container')
  const header = document.querySelector('.app-header')
  
  if (mapContainer && header) {
    const headerRect = header.getBoundingClientRect()
    const headerStyles = window.getComputedStyle(header)
    const headerHeight = headerRect.height + parseFloat(headerStyles.marginTop) + parseFloat(headerStyles.marginBottom)
    
    const elementPosition = mapContainer.getBoundingClientRect().top + window.pageYOffset
    const offsetPosition = elementPosition - headerHeight  // Subtract header height + small padding
    
    window.scrollTo({
      top: offsetPosition,
      behavior: 'smooth'
    })
  }
}

// Computed property for "Zobacz wszystkie" link - converts city/type to path params
const seeAllLink = computed(() => {
  let path = '/powierzchnie-reklamowe'
  
  // Dodaj type do path (jeśli jest)
  if (filters.value.type) {
    const typeSlug = mapTypeToUrlFormat(filters.value.type)
    path += '/' + typeSlug
  }
  
  // Dodaj city do path (jeśli jest)
  if (filters.value.city) {
    const citySlug = slugify(filters.value.city)
    path += '/' + citySlug
  }
  
  // Przygotuj pozostałe filtry jako query params (bez type/city)
  const { type, city, cityStrict, mapBounds, ...otherFilters } = filters.value
  
  const queryParams = filtersToQueryParams(otherFilters as any)
  
  const queryString = new URLSearchParams(queryParams).toString()
  
  return queryString ? `${path}?${queryString}` : path
})

const handleSeeAllClick = () => {
  try {
    localStorage.setItem('user_initiated_search', 'true')
    
    // Zapisz też aktualne filtry do localStorage, aby po odświeżeniu zachować stan
    const filtersToSave = { ...filters.value }
    localStorage.setItem('reklamap_last_search', JSON.stringify(filtersToSave))
  } catch (error) {
    // Silently fail
  }
}


const emit = defineEmits<{
  toggleFavorite: [id: string]
  toggleComparison: [id: string]
  'update:viewMode': [mode: 'grid' | 'list']
  'update:sortBy': [sort: string]
  'update:hoveredAdId': [id: number | null]
}>()

const sortBy = ref(props.sortBy || 'newest')

onMounted(() => {
  if (typeof window !== 'undefined') {
    checkIfMobile()
    window.addEventListener('resize', checkIfMobile)
    window.addEventListener('scroll', handleScroll)
    handleScroll() // Initial check
  }
})

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('resize', checkIfMobile)
    window.removeEventListener('scroll', handleScroll)
  }
})

onDeactivated(() => {
  showMapButton.value = false
  showMapButtonDesktop.value = false
})
</script>

<template>
  <section class="listings-section">
    <!-- Title and count - always visible, not sticky -->
    <div class="section-title-wrapper">
      <h2 class="section-title">Dostępne ogłoszenia</h2>
      <p class="section-subtitle">
        Znaleziono {{ listings.length }}
        {{ listings.length === 1 ? 'ogłoszenie' : listings.length < 5 ? 'ogłoszenia' : 'ogłoszeń' }}
      </p>
    </div>

    <!-- Sticky buttons header -->
    <div class="section-header-wrapper">
      <div class="section-header">
        <div class="header-right">
          <router-link :to="seeAllLink" class="see-all-btn" @click="handleSeeAllClick">
            <span class="full-text">Zobacz wszystkie ogłoszenia</span>
            <span class="short-text">Zobacz wszystkie</span>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </router-link>
          <!-- Desktop Filter Button -->
          <button @click="handleFilterButtonClick" class="desktop-filter-btn" title="Filtruj">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
            </svg>
            <span>Filtruj</span>
            <span v-if="activeFiltersCount && activeFiltersCount > 0" class="filter-badge">{{ activeFiltersCount }}</span>
          </button>

          <!-- Mobile Sort and Filter Buttons -->
          <div class="mobile-header-actions">
            <button @click="handleSortButtonClick" class="mobile-header-btn" title="Sortuj">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 6h18"/>
                <path d="M6 12h12"/>
                <path d="M10 18h4"/>
              </svg>
              <span class="btn-text">Sortuj</span>
            </button>
            <button @click="handleFilterButtonClick" class="mobile-header-btn" title="Filtruj">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
              </svg>
              <span class="btn-text">Filtruj</span>
              <span v-if="activeFiltersCount && activeFiltersCount > 0" class="filter-badge">{{ activeFiltersCount }}</span>
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
            <optgroup label="Cena za kampanię">
              <option value="price-campaign-asc">Cena za kampanię rosnąco</option>
              <option value="price-campaign-desc">Cena za kampanię malejąco</option>
            </optgroup>
          </select>
          <div class="view-switcher">
            <button
              @click="emit('update:viewMode', 'grid')"
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
              @click="emit('update:viewMode', 'list')"
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
        </div>
      </div>
    </div>

    <div class="container">

      <div v-if="isLoading" class="loading-state">
        <div class="spinner"></div>
        <p>Ładowanie ogłoszeń...</p>
      </div>

      <div v-else-if="listings.length === 0" class="empty-state">
        <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="40" cy="40" r="40" fill="#F3F4F6"/>
          <path d="M40 50C45.5228 50 50 45.5228 50 40C50 34.4772 45.5228 30 40 30C34.4772 30 30 34.4772 30 40C30 45.5228 34.4772 50 40 50Z" stroke="#9CA3AF" stroke-width="2"/>
          <path d="M48 48L58 58" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <h3>Nie znaleziono ogłoszeń</h3>
        <p>Spróbuj zmienić kryteria wyszukiwania</p>
        <slot name="empty-content"></slot>
      </div>

      <div v-else :class="viewMode === 'grid' ? 'listings-grid' : 'listings-list'">
        <AdCard
          v-for="ad in listings"
          :key="ad.id"
          :ad="ad"
          :view-mode="viewMode"
          :price-display="searchStore.computedPriceDisplayUnit"
          @toggle-favorite="emit('toggleFavorite', $event)"
          @toggle-comparison="emit('toggleComparison', $event)"
          @hover-start="emit('update:hoveredAdId', $event)"
          @hover-end="emit('update:hoveredAdId', $event)"
        />
      </div>
    </div>

    <!-- Sort Panel (Mobile) -->
    <Teleport to="body">
      <div class="overlay" v-show="showSortPanel" @click="showSortPanel = false"></div>
      <div class="sort-panel" :class="{ 'is-open': showSortPanel }">
        <div class="sort-panel-header">
          <h3 class="sort-panel-title">Sortuj według</h3>
          <button @click="showSortPanel = false" class="sort-panel-close" aria-label="Zamknij">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        
        <div class="sort-options">
          <button 
            v-for="option in sortOptions" 
            :key="option.value"
            @click.stop="handleSortOptionClick(option.value)"
            class="sort-option"
            :class="{ active: localSortBy === option.value }"
          >
            <span class="option-label">{{ option.label }}</span>
            <span class="option-desc">{{ option.description }}</span>
          </button>
        </div>
      </div>
    </Teleport>

    <!-- Desktop toggle button (shows map) -->
    <button v-if="showMapButtonDesktop" @click="goToPolandMap" class="desktop-map-toggle">
      <span>Pokaż mapę</span>
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
        <circle cx="12" cy="10" r="3"></circle>
      </svg>
    </button>

    <!-- Mobile toggle button (shows map) -->
    <button v-if="showMapButton" @click="goToPolandMap" class="mobile-map-toggle">
      <span>Pokaż mapę</span>
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
        <circle cx="12" cy="10" r="3"></circle>
      </svg>
    </button>
  </section>
</template>

<style scoped>
.listings-section {
  padding: 4rem 0;
  background: white;
}

.section-title-wrapper {
  text-align: center;
  padding: 1rem 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.section-header-wrapper {
  position: sticky;
  top: var(--header-height, 100px);
  background: white;
  z-index: 50;
  box-shadow: 0 8px 12px -6px rgba(0, 0, 0, 0.15);
}

@media (max-width: 768px) {
  .section-header-wrapper {
    top: var(--header-height, 100px);
  }
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
  padding: 1rem 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 3rem 2rem 0 2rem;
}

.header-left {
  flex: 1;
}

.header-right {
  margin: auto;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.see-all-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  text-decoration: none;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.95rem;
  transition: all 0.2s;
  white-space: nowrap;
  height: fit-content;
}

.see-all-btn .full-text {
  display: inline;
}

.see-all-btn .short-text {
  display: none;
}

@media (max-width: 415px) {
  .see-all-btn .full-text {
    display: none;
  }

  .see-all-btn .short-text {
    display: inline;
  }
}

.see-all-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.see-all-btn svg {
  flex-shrink: 0;
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

/* Mobile Header Actions */
.desktop-filter-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-size: 0.95rem;
  font-weight: 600;
  transition: all 0.2s ease;
  position: relative;
}

.desktop-filter-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.desktop-filter-btn:active {
  transform: translateY(0);
}

@media (max-width: 825px) {
  .desktop-filter-btn {
    display: none;
  }
}

.mobile-header-actions {
  display: none;
  gap: 0.5rem;
}

.mobile-header-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-size: 0.95rem;
  font-weight: 600;
  transition: all 0.2s ease;
  height: fit-content;
  position: relative;
}

.mobile-header-btn .btn-text {
  display: none;
}

.mobile-header-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.mobile-header-btn:active {
  transform: translateY(0);
}

.view-switcher {
  display: flex;
  gap: 0.25rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  padding: 0.25rem;
  background: white;
}

@media (max-width: 688px) {
  .view-switcher {
    display: none;
  }
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
  color: #374151;
}

.view-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.view-btn.active:hover {
  background: linear-gradient(135deg, #5568d3 0%, #65408b 100%);
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
  max-width: 250px;
}

.sort-select:hover {
  border-color: #667eea;
}

.sort-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

@media (max-width: 825px) {
  .sort-select {
    display: none;
  }

  .mobile-header-actions {
    display: flex !important;
    gap: 0.5rem;
  }

  .mobile-header-btn .btn-text {
    display: inline;
  }

  .mobile-header-btn svg {
    width: 20px;
    height: 20px;
  }
}

@media (max-width: 564px) {
  .mobile-header-btn {
    padding: 0.75rem 0.75rem;
  }

  .mobile-header-btn .btn-text {
    display: none;
  }

  .mobile-header-btn svg {
    width: 20px;
    height: 20px;
  }
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
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  border: 2px solid white;
}

@media (max-width: 768px) {
  .filter-badge {
    width: 18px;
    height: 18px;
    top: -6px;
    right: -6px;
    font-size: 10px;
  }
}

.listings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 2rem;
}

.listings-list {
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

/* Sort Panel Overlay */
.overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  opacity: 1;
  pointer-events: auto;
  transition: opacity 0.2s ease;
}

/* Sort Panel */
.sort-panel {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: white;
  border-radius: 16px 16px 0 0;
  padding: 20px;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
  z-index: 1100;
  transform: translateY(100%);
  transition: transform 0.3s ease;
  max-height: 80vh;
  overflow-y: auto;
  touch-action: manipulation;
}

.sort-panel.is-open {
  transform: translateY(0);
}

.sort-panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid #e5e7eb;
}

.sort-panel-title {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.sort-panel-close {
  background: none;
  border: none;
  color: #6b7280;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
}

.sort-panel-close:hover {
  background: #f3f4f6;
}

.sort-options {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.sort-option {
  padding: 12px 16px;
  border-radius: 8px;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  text-align: left;
  cursor: pointer;
  transition: all 0.2s ease;
}

.sort-option:hover {
  background: #f3f4f6;
  border-color: #d1d5db;
}

.sort-option.active {
  background: #e0e7ff;
  border-color: #a5b4fc;
  color: #4f46e5;
  font-weight: 500;
}

.sort-option .option-label {
  display: block;
  font-size: 15px;
  margin-bottom: 2px;
}

.sort-option .option-desc {
  display: block;
  font-size: 13px;
  color: #6b7280;
}

.sort-option.active .option-desc {
  color: #6366f1;
}

@media (max-width: 1024px) {
  .listings-grid {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
  }
}

@media (max-width: 768px) {
  .listings-section {
    padding: 2rem 0;
  }

  .section-title-wrapper {
    padding: 1rem 2rem;
  }

  .section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.75rem 2rem;
    max-width: 1400px;
    margin: 0 auto;
    width: 100%;
  }

  .header-right {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    justify-content: flex-start;
    flex-wrap: wrap;
  }

  .see-all-btn {
    font-size: 0.85rem;
    padding: 0.75rem 0.75rem;
    white-space: nowrap;
  }

  .sort-select {
    display: none;
  }


  .mobile-header-actions {
    display: flex;
    gap: 0.5rem;
  }
}

@media (max-width: 768px) {
 
  .section-header {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .header-right {
    width: 100%;
    justify-content: center;
    flex-wrap: wrap;
  }
}

@media (max-width: 640px) {
  .listings-section {
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

  .listings-grid {
    grid-template-columns: 1fr;
    gap: 1.25rem;
  }
}

@media (max-width: 415px) {
  .see-all-btn {
    font-size: 0.75rem;
  }
}

@media (max-width: 400px) {
  .section-title {
    font-size: 1.8rem;
  }
}

/* Desktop Map Toggle Button */
.desktop-map-toggle {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 1000;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 24px;
  padding: 10px 20px;
  font-size: 15px;
  font-weight: 500;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  opacity: 0.9;
  white-space: nowrap;
}

.desktop-map-toggle:hover {
  background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
  transform: translateX(-50%) translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
  opacity: 1;
}

.desktop-map-toggle:active {
  transform: translateX(-50%) translateY(0);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* Mobile Map Toggle Button */
.mobile-map-toggle {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 1000;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 24px;
  padding: 10px 20px;
  font-size: 15px;
  font-weight: 500;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  opacity: 0.9;
  white-space: nowrap;
}

.mobile-map-toggle:hover {
  background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
  transform: translateX(-50%) translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
  opacity: 1;
}

.mobile-map-toggle:active {
  transform: translateX(-50%) translateY(0);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}
</style>
