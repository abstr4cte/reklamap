<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import logoImage from '../assets/logo.webp'
import logoTextImage from '../assets/logo-text.webp'

const route = useRoute()


const props = defineProps<{
  favoritesCount: number
  comparisonCount: number
}>()

const emit = defineEmits<{
  openManagementModal: []
  navigateToAddAd: []
  openFavorites: []
  openComparison: []
}>()

const isMobileMenuOpen = ref(false)
const isCategoriesDropdownOpen = ref(false)
const isMobileCategoriesOpen = ref(false)


const categories = [
  { name: 'Wszystkie powierzchnie', slug: '', icon: '🗺️' },
  { name: 'Billboardy', slug: 'billboardy', icon: '🏢' },
  { name: 'Citylighty', slug: 'citylighty', icon: '💡' },
  { name: 'Ekrany LED', slug: 'ekrany-led', icon: '📺' },
  { name: 'Banery', slug: 'banery', icon: '🎯' },
  { name: 'Ściany reklamowe', slug: 'sciany-reklamowe', icon: '🧱' },
  { name: 'Totemy reklamowe', slug: 'totemy-reklamowe', icon: '📍' },
  { name: 'Reklama w transporcie', slug: 'reklama-w-transporcie', icon: '🚌' },
  { name: 'Reklama mobilna', slug: 'reklama-mobilna', icon: '🚚' },
  { name: 'Inne', slug: 'inne', icon: '✨' }
]

const popularCities = [
  { name: 'Warszawa', slug: 'warszawa' },
  { name: 'Kraków', slug: 'krakow' },
  { name: 'Wrocław', slug: 'wroclaw' },
  { name: 'Poznań', slug: 'poznan' },
  { name: 'Gdańsk', slug: 'gdansk' },
  { name: 'Łódź', slug: 'lodz' },
  { name: 'Katowice', slug: 'katowice' },
  { name: 'Szczecin', slug: 'szczecin' },
  { name: 'Bydgoszcz', slug: 'bydgoszcz' },
  { name: 'Lublin', slug: 'lublin' },
  { name: 'Białystok', slug: 'bialystok' },
  { name: 'Gdynia', slug: 'gdynia' }
]

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false
}

const handleHomeClick = () => {
  closeMobileMenu()
  // Przy kliknięciu w logo lub "Strona główna" zachowujemy filtry (jeśli są), 
  // czyścimy tylko zapamiętaną pozycję scrolla.
  try {
    sessionStorage.removeItem('homepage_scroll_position')
    sessionStorage.removeItem('listings_scroll_position')
  } catch (e) {
    console.error(e)
  }
}

const handleManagementClick = () => {
  closeMobileMenu()
  emit('openManagementModal')
}

const handleAddAdClick = () => {
  closeMobileMenu()
  emit('navigateToAddAd')
}

const handleFavoritesClick = () => {
  closeMobileMenu()
  emit('openFavorites')
}

const handleComparisonClick = () => {
  closeMobileMenu()
  emit('openComparison')
}

const closeCategoriesDropdown = () => {
  isCategoriesDropdownOpen.value = false
}

// Wyczyść flagę user_initiated_search gdy użytkownik wchodzi z linku kategorii/miasta
const clearSearchFlag = () => {
  try {
    localStorage.removeItem('user_initiated_search')
    localStorage.removeItem('reklamap_last_search')
  } catch (e) { /* ignore */ }
}

watch(isMobileMenuOpen, (isOpen) => {
  if (isOpen) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})
</script>

<template>
  <header class="app-header">
    <div class="container">
      <div class="header-left">
        <router-link to="/" class="logo" @click="handleHomeClick">
          <img :src="logoTextImage" alt="ReklaMap" class="logo-image logo-image--full" />
          <img :src="logoImage" alt="ReklaMap" class="logo-image logo-image--icon" />
        </router-link>
      </div>

      <!-- Desktop Navigation -->
      <nav class="header-center desktop-nav">
        <router-link to="/" class="nav-link" @click="handleHomeClick">Strona główna</router-link>
        
        <!-- Categories Dropdown -->
        <div 
          class="nav-dropdown"
          @mouseenter="isCategoriesDropdownOpen = true"
          @mouseleave="isCategoriesDropdownOpen = false"
        >
          <button class="nav-link dropdown-trigger">
            Kategorie
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" class="dropdown-icon">
              <path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
          
          <Transition name="dropdown">
            <div v-if="isCategoriesDropdownOpen" class="dropdown-menu dropdown-menu-wide">
              <div class="dropdown-columns">
                <div class="dropdown-column">
                  <div class="dropdown-column-title">Kategorie</div>
                  <router-link
                    v-for="category in categories"
                    :key="category.slug"
                    :to="category.slug ? `/powierzchnie-reklamowe/${category.slug}` : '/powierzchnie-reklamowe'"
                    class="dropdown-item"
                    @click="closeCategoriesDropdown(); clearSearchFlag()"
                  >
                    {{ category.name }}
                  </router-link>
                </div>
                <div class="dropdown-column">
                  <div class="dropdown-column-title">Popularne miasta</div>
                  <router-link
                    v-for="city in popularCities"
                    :key="city.slug"
                    :to="`/powierzchnie-reklamowe/${city.slug}`"
                    class="dropdown-item"
                    @click="closeCategoriesDropdown(); clearSearchFlag()"
                  >
                    {{ city.name }}
                  </router-link>
                </div>
              </div>
            </div>
          </Transition>
        </div>
        
        <router-link to="/blog" class="nav-link">Blog</router-link>
        <router-link to="/faq" class="nav-link">FAQ</router-link>
        <router-link to="/kontakt" class="nav-link">Kontakt</router-link>
      </nav>

      <div class="header-right">
        <!-- Desktop Buttons -->
        <div class="desktop-buttons">
          <button @click="handleFavoritesClick" class="favorites-btn" :aria-label="`Ulubione${favoritesCount > 0 ? ` (${favoritesCount})` : ''}`">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" stroke="currentColor" stroke-width="2"/>
            </svg>
            <span class="badge-count" v-if="favoritesCount > 0" aria-hidden="true">{{ favoritesCount }}</span>
          </button>
          <button @click="handleComparisonClick" class="comparison-btn" :aria-label="`Porównaj${comparisonCount > 0 ? ` (${comparisonCount})` : ''}`">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <rect x="3" y="3" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
              <rect x="14" y="3" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
              <rect x="3" y="14" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
              <rect x="14" y="14" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
            </svg>
            <span class="badge-count" v-if="comparisonCount > 0" aria-hidden="true">{{ comparisonCount }}</span>
          </button>
          <button @click="handleAddAdClick" class="add-listing-btn btn-interactive">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M10 5V15M5 10H15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Dodaj ogłoszenie
          </button>
          <button @click="handleManagementClick" class="manage-btn btn-interactive">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 4h14M3 10h14M3 16h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Zarządzaj
          </button>

        </div>

        <!-- Mobile Quick "Dodaj" Button (visible only on mobile/tablet) -->
        <button
          v-if="route.path !== '/dodaj'"
          @click="handleAddAdClick"
          class="mobile-add-btn"
          aria-label="Dodaj ogłoszenie"
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
          </svg>
          <span class="mobile-add-btn-label">Dodaj</span>
        </button>

        <!-- Hamburger Button -->
        <button @click="toggleMobileMenu" class="hamburger-btn" :class="{ active: isMobileMenuOpen }" :aria-label="isMobileMenuOpen ? 'Zamknij menu' : 'Otwórz menu'" :aria-expanded="isMobileMenuOpen">
          <span aria-hidden="true"></span>
          <span aria-hidden="true"></span>
          <span aria-hidden="true"></span>
        </button>
      </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <Transition name="overlay">
      <div v-if="isMobileMenuOpen" class="mobile-overlay" @click="closeMobileMenu"></div>
    </Transition>

    <!-- Mobile Menu Sidebar -->
    <Transition name="sidebar">
      <div v-if="isMobileMenuOpen" class="mobile-menu">
        <div class="mobile-menu-header">
          <span class="mobile-menu-title">Menu</span>
          <button @click="closeMobileMenu" class="close-menu-btn" aria-label="Zamknij menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>

        <nav class="mobile-nav">
          <router-link to="/" class="mobile-nav-link" @click="handleHomeClick">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2"/>
            </svg>
            Strona główna
          </router-link>
          
          <!-- Mobile Categories Section -->
          <button 
            @click="isMobileCategoriesOpen = !isMobileCategoriesOpen"
            class="mobile-nav-link categories-toggle"
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <rect x="3" y="3" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
              <rect x="14" y="3" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
              <rect x="3" y="14" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
              <rect x="14" y="14" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
            </svg>
            <span>Kategorie</span>
            <svg 
              width="16" 
              height="16" 
              viewBox="0 0 24 24" 
              fill="none"
              :class="{ 'rotate-180': isMobileCategoriesOpen }"
              class="categories-toggle-icon"
            >
              <path d="M19 9l-7 7-7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
          <Transition name="accordion">
            <div v-if="isMobileCategoriesOpen" class="mobile-categories-list">
              <router-link
                v-for="category in categories"
                :key="category.slug"
                :to="category.slug ? `/powierzchnie-reklamowe/${category.slug}` : '/powierzchnie-reklamowe'"
                class="mobile-nav-link category-link"
                @click="closeMobileMenu(); clearSearchFlag()"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                </svg>
                {{ category.name }}
              </router-link>
            </div>
          </Transition>
          
          <router-link to="/blog" class="mobile-nav-link" @click="closeMobileMenu()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2"/>
              <path d="M14 2v6h6" stroke="currentColor" stroke-width="2"/>
            </svg>
            Blog
          </router-link>
          <router-link to="/faq" class="mobile-nav-link" @click="closeMobileMenu()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
              <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" stroke="currentColor" stroke-width="2"/>
              <circle cx="12" cy="17" r="0.5" fill="currentColor"/>
            </svg>
            FAQ
          </router-link>
          <router-link to="/regulamin" class="mobile-nav-link" @click="closeMobileMenu()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2"/>
            </svg>
            Regulamin
          </router-link>
          <router-link to="/polityka-prywatnosci" class="mobile-nav-link" @click="closeMobileMenu()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Polityka prywatności
          </router-link>
          <router-link to="/kontakt" class="mobile-nav-link" @click="closeMobileMenu()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" stroke="currentColor" stroke-width="2"/>
            </svg>
            Kontakt
          </router-link>
        </nav>

        <div class="mobile-actions">
          <button @click="handleFavoritesClick" class="mobile-action-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" stroke="currentColor" stroke-width="2"/>
            </svg>
            Ulubione
            <span class="badge-count-mobile" v-if="favoritesCount > 0">{{ favoritesCount }}</span>
          </button>
          <button @click="handleComparisonClick" class="mobile-action-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <rect x="3" y="3" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
              <rect x="14" y="3" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
              <rect x="3" y="14" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
              <rect x="14" y="14" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
            </svg>
            Porównaj
            <span class="badge-count-mobile" v-if="comparisonCount > 0">{{ comparisonCount }}</span>
          </button>
          <button @click="handleAddAdClick" class="mobile-action-btn primary">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M10 5V15M5 10H15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Dodaj ogłoszenie
          </button>
          <button @click="handleManagementClick" class="mobile-action-btn">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M3 4h14M3 10h14M3 16h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Zarządzaj
          </button>
        </div>
      </div>
    </Transition>
  </header>
</template>

<style scoped>
.app-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1rem 0;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  width: 100%;
  z-index: 3000;
}

.container {
  width: 100%;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.header-left, .header-right {
  flex: 1;
}

.header-center {
  flex: 1;
  display: flex;
  justify-content: center;
  gap: 2rem;
}

.logo {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.logo-image {
  height: 68px;
  object-fit: contain;
}

.logo-image--full {
  width: auto;
  display: block;
}

.logo-image--icon {
  width: 56px;
  display: none;
}

.logo-text {
  font-size: 1.5rem;
  font-weight: 700;
  color: white;
  letter-spacing: -0.5px;
}

.nav-link {
  color: white;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.95rem;
  transition: all 0.2s ease;
  position: relative;
  padding: 0.5rem 0;
  white-space: nowrap;
}

.nav-link:hover {
  opacity: 0.8;
}

.nav-link::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 0;
  height: 2px;
  background: white;
  transition: width 0.3s ease;
}

.nav-link:hover::after {
  width: 100%;
}

.nav-link.router-link-active {
  font-weight: 700;
}

.nav-link.router-link-active::after {
  width: 100%;
}

/* Dropdown Styles */
.nav-dropdown {
  position: relative;
  display: inline-block;
}

.dropdown-trigger {
  background: none;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.dropdown-icon {
  transition: transform 0.2s ease;
}

.nav-dropdown:hover .dropdown-icon {
  transform: rotate(180deg);
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  margin-top: 0;
  padding-top: 0.5rem;
  background: transparent;
  min-width: 240px;
  z-index: 1000;
}

.dropdown-menu::before {
  content: '';
  display: block;
  height: 0.5rem;
}

.dropdown-menu > * {
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  padding: 0.5rem;
}

.dropdown-menu-wide {
  min-width: 520px;
  left: 50%;
  transform: translateX(-50%);
}

.dropdown-columns {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.dropdown-column {
  display: flex;
  flex-direction: column;
}

.dropdown-column-title {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #9ca3af;
  padding: 0.5rem 1rem;
  margin-bottom: 0.25rem;
}

.dropdown-item {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  color: var(--text-main, #1f2937);
  text-decoration: none;
  border-radius: 8px;
  transition: all 0.2s ease;
  font-size: 0.95rem;
  font-weight: 500;
}

.dropdown-item:hover {
  background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
  color: #667eea;
  transform: translateX(4px);
}

/* Dropdown Transitions */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.header-right {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  align-items: center;
}

.desktop-buttons {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.favorites-btn,
.comparison-btn,
.add-listing-btn,
.manage-btn {
  background: var(--card-bg, white);
  color: var(--primary-color, #4F46E5);
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  box-shadow: var(--card-shadow, 0 2px 8px rgba(0, 0, 0, 0.1));
  position: relative;
}

.favorites-btn,
.comparison-btn {
  padding: 0.75rem;
}

.badge-count {
  position: absolute;
  top: -4px;
  right: -4px;
  background: #EF4444;
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid var(--card-bg, white);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.add-listing-btn {
  background: linear-gradient(135deg, #10B981 0%, #059669 100%);
  color: #ffffff;
  white-space: nowrap;
  border: none;
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.25),
    0 2px 8px rgba(16, 185, 129, 0.4);
  animation: cta-pulse 8s ease-out infinite;
}

.add-listing-btn::before {
  content: "";
  position: absolute;
  top: 0;
  left: -120%;
  width: 60%;
  height: 100%;
  background: linear-gradient(
    100deg,
    transparent 0%,
    rgba(255, 255, 255, 0.45) 50%,
    transparent 100%
  );
  transform: skewX(-20deg);
  pointer-events: none;
  transition: left 0.6s cubic-bezier(0.22, 1, 0.36, 1);
  z-index: 1;
}

.add-listing-btn:hover::before {
  left: 140%;
}

.add-listing-btn:hover {
  animation: none;
}

/* 3 pulses (~1s each) + 5s idle pause = 8s total cycle */
@keyframes cta-pulse {
  /* Pulse 1 */
  0% {
    box-shadow:
      inset 0 1px 0 rgba(255, 255, 255, 0.35),
      0 1px 3px rgba(0, 0, 0, 0.08),
      0 0 0 0 rgba(255, 255, 255, 0.6);
  }
  12.5% {
    box-shadow:
      inset 0 1px 0 rgba(255, 255, 255, 0.35),
      0 1px 3px rgba(0, 0, 0, 0.08),
      0 0 0 9px rgba(255, 255, 255, 0);
  }
  /* Pulse 2 */
  12.51% {
    box-shadow:
      inset 0 1px 0 rgba(255, 255, 255, 0.35),
      0 1px 3px rgba(0, 0, 0, 0.08),
      0 0 0 0 rgba(255, 255, 255, 0.6);
  }
  25% {
    box-shadow:
      inset 0 1px 0 rgba(255, 255, 255, 0.35),
      0 1px 3px rgba(0, 0, 0, 0.08),
      0 0 0 9px rgba(255, 255, 255, 0);
  }
  /* Pulse 3 */
  25.01% {
    box-shadow:
      inset 0 1px 0 rgba(255, 255, 255, 0.35),
      0 1px 3px rgba(0, 0, 0, 0.08),
      0 0 0 0 rgba(255, 255, 255, 0.6);
  }
  37.5% {
    box-shadow:
      inset 0 1px 0 rgba(255, 255, 255, 0.35),
      0 1px 3px rgba(0, 0, 0, 0.08),
      0 0 0 9px rgba(255, 255, 255, 0);
  }
  /* Idle pause */
  37.51%, 100% {
    box-shadow:
      inset 0 1px 0 rgba(255, 255, 255, 0.35),
      0 1px 3px rgba(0, 0, 0, 0.08),
      0 0 0 0 rgba(255, 255, 255, 0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .add-listing-btn,
  .mobile-add-btn {
    animation: none !important;
  }
}

.theme-toggle-btn {
  display: none; /* Temporarily disabled - dark mode not ready */
  background: white;
  color: #4B5563;
  border: none;
  padding: 0.75rem;
  border-radius: 8px;
  cursor: pointer;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.theme-toggle-btn:hover {
  background: #f3f4f6;
  color: #111827;
  transform: scale(1.1);
}

.favorites-btn:hover,
.comparison-btn:hover,
.add-listing-btn:hover,
.manage-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.favorites-btn:hover {
  background: #fef2f2;
  color: #EF4444;
}

.comparison-btn:hover {
  background: #f5f3ff;
  color: #667eea;
}

.add-listing-btn:hover {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.3),
    0 4px 14px rgba(16, 185, 129, 0.5);
}

.manage-btn:hover {
  background: #f8f8ff;
}

.add-listing-btn:active,
.manage-btn:active {
  transform: translateY(0);
}

/* Mobile "Dodaj" quick button — visible only when hamburger is shown */
.mobile-add-btn {
  display: none;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  height: 40px;
  padding: 0 1rem;
  border-radius: 999px;
  border: none;
  cursor: pointer;
  background: linear-gradient(135deg, #10B981 0%, #059669 100%);
  color: #ffffff;
  font-size: 0.9rem;
  font-weight: 700;
  letter-spacing: 0.01em;
  border: none;
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.25),
    0 2px 8px rgba(16, 185, 129, 0.4);
  transition: transform 0.18s ease, box-shadow 0.25s ease, background 0.18s ease, border-color 0.18s ease;
  z-index: 1101;
  white-space: nowrap;
  position: relative;
  overflow: hidden;
  animation: cta-pulse 8s ease-out infinite;
}

.mobile-add-btn::before {
  content: "";
  position: absolute;
  top: 0;
  left: -120%;
  width: 60%;
  height: 100%;
  background: linear-gradient(
    100deg,
    transparent 0%,
    rgba(255, 255, 255, 0.45) 50%,
    transparent 100%
  );
  transform: skewX(-20deg);
  pointer-events: none;
  transition: left 0.6s cubic-bezier(0.22, 1, 0.36, 1);
  z-index: 1;
}

.mobile-add-btn:hover::before {
  left: 140%;
}

.mobile-add-btn > * {
  position: relative;
  z-index: 2;
}

.mobile-add-btn:hover {
  animation: none;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  transform: translateY(-1px) scale(1.03);
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.3),
    0 4px 14px rgba(16, 185, 129, 0.5);
}

.mobile-add-btn:active {
  transform: translateY(0) scale(0.97);
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.2),
    0 1px 3px rgba(16, 185, 129, 0.5);
}

.mobile-add-btn-label {
  line-height: 1;
}

/* Hamburger & Mobile Menu */
.hamburger-btn {
  display: none;
  background: transparent;
  border: none;
  cursor: pointer;
  flex-direction: column;
  gap: 4px;
  padding: 0.5rem;
  z-index: 1101;
}

.hamburger-btn span {
  width: 24px;
  height: 2px;
  background: white;
  transition: all 0.3s ease;
  border-radius: 2px;
}

.hamburger-btn.active span:nth-child(1) {
  transform: rotate(45deg) translate(5px, 5px);
}

.hamburger-btn.active span:nth-child(2) {
  opacity: 0;
}

.hamburger-btn.active span:nth-child(3) {
  transform: rotate(-45deg) translate(6px, -6px);
}

.mobile-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  z-index: 1099;
}

.mobile-menu {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  width: 320px;
  max-width: 85vw;
  background: white;
  z-index: 3000;
  display: flex;
  flex-direction: column;
  box-shadow: -4px 0 20px rgba(0, 0, 0, 0.1);
}

.mobile-menu-header {
  padding: 1.5rem;
  border-bottom: 1px solid #f3f4f6;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.mobile-menu-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: white;
}

.close-menu-btn {
  background: transparent;
  border: none;
  color: white;
  cursor: pointer;
  padding: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: background 0.2s;
}

.close-menu-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}

.mobile-nav {
  flex: 1;
  padding: 1rem 0;
  overflow-y: auto;
}


.mobile-nav-link {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.5rem;
  color: #1f2937;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.2s;
  border-left: 3px solid transparent;
  background: none;
  border: none;
  cursor: pointer;
  font-size: inherit;
  font-family: inherit;
}

.mobile-nav-link:hover {
  background: #f9fafb;
  border-left-color: #667eea;
  color: #667eea;
}

.mobile-nav-link.router-link-active {
  background: #f5f3ff;
  border-left-color: #667eea;
  color: #667eea;
  font-weight: 600;
}

.mobile-nav-link svg {
  flex-shrink: 0;
}

.mobile-nav-link.category-link {
  padding-left: 3.5rem;
  font-size: 0.9rem;
  color: #4b5563;
}

.mobile-nav-link.category-link:hover {
  background: #f0f4ff;
  color: #667eea;
}

.mobile-nav-link.category-link.router-link-active {
  background: #f5f3ff;
  color: #667eea;
  font-weight: 600;
}

.mobile-actions {
  padding: 1rem;
  border-top: 1px solid #f3f4f6;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  background: #f9fafb;
}

.mobile-action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 0.875rem;
  background: white;
  color: #4F46E5;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}

.mobile-action-btn:hover {
  border-color: #667eea;
  background: #f5f3ff;
}

.mobile-action-btn.primary {
  background: linear-gradient(135deg, #10B981 0%, #059669 100%);
  color: white;
  border-color: transparent;
}

.mobile-action-btn.primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.badge-count-mobile {
  position: absolute;
  top: -4px;
  right: -4px;
  background: #EF4444;
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
  min-width: 20px;
  height: 20px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 0.375rem;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* Mobile Categories Toggle */
.mobile-nav-link.categories-toggle {
  width: 100%;
}

.mobile-nav-link.categories-toggle span {
  flex: 1;
  text-align: left;
}

.categories-toggle-icon {
  transition: transform 0.3s ease;
  flex-shrink: 0;
}

.mobile-categories-list {
  display: flex;
  flex-direction: column;
}

/* Transitions */
.overlay-enter-active,
.overlay-leave-active {
  transition: opacity 0.3s ease;
}

.overlay-enter-from,
.overlay-leave-to {
  opacity: 0;
}

.sidebar-enter-active,
.sidebar-leave-active {
  transition: transform 0.3s ease;
}

.sidebar-enter-from,
.sidebar-leave-to {
  transform: translateX(100%);
}

.accordion-enter-active,
.accordion-leave-active {
  transition: all 0.3s ease;
}

.accordion-enter-from,
.accordion-leave-to {
  opacity: 0;
  max-height: 0;
}

/* Responsive */
@media (max-width: 968px) {
  .nav-link {
    font-size: 0.875rem;
  }
  
  .header-center {
    gap: 1.5rem;
  }
}

@media (max-width: 1180px) {
  .header-center.desktop-nav {
    display: none !important;
  }

  .desktop-buttons {
    display: none !important;
  }

  .hamburger-btn {
    display: flex;
  }

  .hamburger-btn.active {
    opacity: 0;
    pointer-events: none;
  }

  .mobile-add-btn {
    display: inline-flex;
  }

  .header-right {
    flex: 0;
    gap: 0.5rem;
    display: flex;
    align-items: center;
  }

  .container {
    padding: 0 1rem;
    gap: 0.75rem;
    max-width: 100vw;
    overflow: hidden;
  }

  .logo-text {
    font-size: 1.25rem;
  }
}

@media (max-width: 640px) {
  .nav-link {
    font-size: 0.85rem;
  }

  .manage-btn {
    font-size: 0.85rem;
    padding: 0.6rem 1rem;
  }

  .logo-text {
    font-size: 1.25rem;
  }
}

@media (max-width: 480px) {
  .logo-image--full {
    display: none;
  }

  .logo-image--icon {
    display: block;
  }
}
</style>
