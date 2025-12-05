<script setup lang="ts">
import { computed, ref } from 'vue'

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

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false
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
</script>

<template>
  <header class="app-header">
    <div class="container">
      <div class="header-left">
        <router-link to="/" class="logo" @click="closeMobileMenu">
          <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="40" height="40" rx="8" fill="#4F46E5"/>
            <path d="M12 15L20 10L28 15V25L20 30L12 25V15Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="20" cy="20" r="3" fill="white"/>
          </svg>
          <span class="logo-text">AdSpace</span>
        </router-link>
      </div>

      <!-- Desktop Navigation -->
      <nav class="header-center desktop-nav">
        <router-link to="/" class="nav-link">Strona główna</router-link>
        <router-link to="/blog" class="nav-link">Blog</router-link>
        <router-link to="/faq" class="nav-link">FAQ</router-link>
        <router-link to="/regulamin" class="nav-link">Regulamin</router-link>
        <router-link to="/polityka-prywatnosci" class="nav-link">Polityka prywatności</router-link>
        <router-link to="/kontakt" class="nav-link">Kontakt</router-link>
      </nav>

      <div class="header-right">
        <!-- Desktop Buttons -->
        <div class="desktop-buttons">
          <button @click="handleFavoritesClick" class="favorites-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" stroke="currentColor" stroke-width="2"/>
            </svg>
            <span class="badge-count" v-if="favoritesCount > 0">{{ favoritesCount }}</span>
          </button>
          <button @click="handleComparisonClick" class="comparison-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <rect x="3" y="3" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
              <rect x="14" y="3" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
              <rect x="3" y="14" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
              <rect x="14" y="14" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
            </svg>
            <span class="badge-count" v-if="comparisonCount > 0">{{ comparisonCount }}</span>
          </button>
          <button @click="handleAddAdClick" class="add-ad-btn">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M10 5V15M5 10H15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Dodaj ogłoszenie
          </button>
          <button @click="handleManagementClick" class="manage-btn">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 4h14M3 10h14M3 16h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Zarządzaj
          </button>
        </div>

        <!-- Hamburger Button -->
        <button @click="toggleMobileMenu" class="hamburger-btn" :class="{ active: isMobileMenuOpen }">
          <span></span>
          <span></span>
          <span></span>
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
          <button @click="closeMobileMenu" class="close-menu-btn">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
              <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>

        <nav class="mobile-nav">
          <router-link to="/" class="mobile-nav-link" @click="closeMobileMenu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2"/>
            </svg>
            Strona główna
          </router-link>
          <router-link to="/blog" class="mobile-nav-link" @click="closeMobileMenu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2"/>
              <path d="M14 2v6h6" stroke="currentColor" stroke-width="2"/>
            </svg>
            Blog
          </router-link>
          <router-link to="/faq" class="mobile-nav-link" @click="closeMobileMenu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
              <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" stroke="currentColor" stroke-width="2"/>
              <circle cx="12" cy="17" r="0.5" fill="currentColor"/>
            </svg>
            FAQ
          </router-link>
          <router-link to="/regulamin" class="mobile-nav-link" @click="closeMobileMenu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2"/>
            </svg>
            Regulamin
          </router-link>
          <router-link to="/polityka-prywatnosci" class="mobile-nav-link" @click="closeMobileMenu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Polityka prywatności
          </router-link>
          <router-link to="/kontakt" class="mobile-nav-link" @click="closeMobileMenu">
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
  position: sticky;
  top: 0;
  z-index: 1100;
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
  gap: 0.75rem;
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
.add-ad-btn,
.manage-btn {
  background: white;
  color: #4F46E5;
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
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
  border: 2px solid white;
}

.add-ad-btn {
  background: #10B981;
  color: white;
}

.favorites-btn:hover,
.comparison-btn:hover,
.add-ad-btn:hover,
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

.add-ad-btn:hover {
  background: #059669;
}

.manage-btn:hover {
  background: #f8f8ff;
}

.add-ad-btn:active,
.manage-btn:active {
  transform: translateY(0);
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
  z-index: 1100;
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

/* Responsive */
@media (max-width: 968px) {
  .nav-link {
    font-size: 0.875rem;
  }
  
  .header-center {
    gap: 1.5rem;
  }
}

@media (max-width: 768px) {
  .header-center.desktop-nav {
    display: none;
  }

  .desktop-buttons {
    display: none;
  }

  .hamburger-btn {
    display: flex;
  }
  
  .hamburger-btn.active {
    opacity: 0;
    pointer-events: none;
  }

  .header-right {
    flex: 0;
  }

  .container {
    padding: 0 1rem;
    gap: 1rem;
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
</style>
