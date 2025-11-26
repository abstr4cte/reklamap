<script setup lang="ts">
import { computed } from 'vue'

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

const handleManagementClick = () => {
  emit('openManagementModal')
}

const handleAddAdClick = () => {
  emit('navigateToAddAd')
}

const handleFavoritesClick = () => {
  emit('openFavorites')
}

const handleComparisonClick = () => {
  emit('openComparison')
}
</script>

<template>
  <header class="app-header">
    <div class="container">
      <div class="header-left">
        <router-link to="/" class="logo">
          <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="40" height="40" rx="8" fill="#4F46E5"/>
            <path d="M12 15L20 10L28 15V25L20 30L12 25V15Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="20" cy="20" r="3" fill="white"/>
          </svg>
          <span class="logo-text">AdSpace</span>
        </router-link>
      </div>

      <nav class="header-center">
        <router-link to="/" class="nav-link">Strona główna</router-link>
        <router-link to="/blog" class="nav-link">Blog</router-link>
        <router-link to="/faq" class="nav-link">FAQ</router-link>
        <router-link to="/regulamin" class="nav-link">Regulamin</router-link>
        <router-link to="/kontakt" class="nav-link">Kontakt</router-link>
      </nav>

      <div class="header-right">
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
    </div>
  </header>
</template>

<style scoped>
.app-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1rem 0;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 1000;
}

.container {
  max-width: 1400px;
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

@media (max-width: 968px) {
  .container {
    flex-wrap: wrap;
  }

  .header-left {
    order: 1;
  }

  .header-right {
    order: 2;
  }

  .header-center {
    order: 3;
    flex-basis: 100%;
    margin-top: 1rem;
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
