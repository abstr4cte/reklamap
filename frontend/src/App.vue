<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import AppHeader from './components/AppHeader.vue'
import AppFooter from './components/AppFooter.vue'
import FavoritesPanel from './components/FavoritesPanel.vue'
import ComparisonPanel from './components/ComparisonPanel.vue'
import EmailModal from './components/EmailModal.vue'
import FeedbackButton from './components/FeedbackButton.vue'
import CookieConsent from './components/CookieConsent.vue'
import { api } from './services/api'

const router = useRouter()
const isModalOpen = ref(false)
const isFavoritesPanelOpen = ref(false)
const isComparisonPanelOpen = ref(false)
const favoritesKey = ref(0)
const comparisonKey = ref(0)
const activeFavoriteIds = ref<string[]>([])
const activeComparisonIds = ref<string[]>([])

// Synchronize favorites with active advertisements
const syncFavorites = async () => {
  const favoriteIds = JSON.parse(localStorage.getItem('favorites') || '[]')
  
  if (favoriteIds.length === 0) {
    activeFavoriteIds.value = []
    return
  }
  
  try {
    const data = await api.getAdvertisementsByIds(favoriteIds)
    // Filter to only include active advertisements
    const activeAds = data.filter(ad => ad.is_active && ad.status === 'active')
    activeFavoriteIds.value = activeAds.map(ad => ad.id)
    
    // Update localStorage if there are inactive/deleted ads
    if (activeAds.length < favoriteIds.length) {
      localStorage.setItem('favorites', JSON.stringify(activeFavoriteIds.value))
      favoritesKey.value++
    }
  } catch (error) {
    console.error('Error syncing favorites:', error)
  }
}

// Synchronize comparison with active advertisements
const syncComparison = async () => {
  const comparisonIds = JSON.parse(localStorage.getItem('comparison') || '[]')
  
  if (comparisonIds.length === 0) {
    activeComparisonIds.value = []
    return
  }
  
  try {
    const data = await api.getAdvertisementsByIds(comparisonIds)
    // Filter to only include active advertisements
    const activeAds = data.filter(ad => ad.is_active && ad.status === 'active')
    activeComparisonIds.value = activeAds.map(ad => ad.id)
    
    // Update localStorage if there are inactive/deleted ads
    if (activeAds.length < comparisonIds.length) {
      localStorage.setItem('comparison', JSON.stringify(activeComparisonIds.value))
      comparisonKey.value++
    }
  } catch (error) {
    console.error('Error syncing comparison:', error)
  }
}

// Compute favorites count based on active advertisements only
const favoritesCount = computed(() => {
  favoritesKey.value // Dependency to trigger recomputation
  return activeFavoriteIds.value.length
})

// Compute comparison count based on active advertisements only
const comparisonCount = computed(() => {
  comparisonKey.value // Dependency to trigger recomputation
  return activeComparisonIds.value.length
})

const handleNavigateToAddAd = () => {
  router.push('/dodaj-powierzchnie-reklamowa')
}

const handleToggleFavorite = async (id: string) => {
  const favorites = JSON.parse(localStorage.getItem('favorites') || '[]')
  const index = favorites.indexOf(id)

  if (index > -1) {
    favorites.splice(index, 1)
    activeFavoriteIds.value = activeFavoriteIds.value.filter(fId => fId !== id)
  } else {
    // Verify the ad exists and is active before adding
    try {
      const ad = await api.getAdvertisement(id)
      if (ad && ad.is_active && ad.status === 'active') {
        favorites.push(id)
        activeFavoriteIds.value.push(id)
      }
    } catch (error) {
      console.error('Error checking advertisement:', error)
    }
  }

  localStorage.setItem('favorites', JSON.stringify(favorites))
  // Increment the key to force recomputation of the favorites count
  favoritesKey.value++
}

const handleRemoveFavorite = (id: string) => {
  handleToggleFavorite(id)
}

const handleToggleComparison = async (id: string) => {
  const comparison = JSON.parse(localStorage.getItem('comparison') || '[]')
  const index = comparison.indexOf(id)

  if (index > -1) {
    comparison.splice(index, 1)
    activeComparisonIds.value = activeComparisonIds.value.filter(cId => cId !== id)
  } else {
    if (comparison.length >= 5) {
      alert('Możesz porównać maksymalnie 5 ogłoszeń')
      return
    }
    // Verify the ad exists and is active before adding
    try {
      const ad = await api.getAdvertisement(id)
      if (ad && ad.is_active && ad.status === 'active') {
        comparison.push(id)
        activeComparisonIds.value.push(id)
      }
    } catch (error) {
      console.error('Error checking advertisement:', error)
    }
  }

  localStorage.setItem('comparison', JSON.stringify(comparison))
  // Increment the key to force recomputation of the comparison count
  comparisonKey.value++
}

const handleRemoveComparison = (id: string) => {
  handleToggleComparison(id)
}

const handleStorageChange = () => {
  // Synchronizuj activeFavoriteIds z localStorage
  const storedFavorites = JSON.parse(localStorage.getItem('favorites') || '[]')
  activeFavoriteIds.value = storedFavorites
  
  // Synchronizuj activeComparisonIds z localStorage
  const storedComparison = JSON.parse(localStorage.getItem('comparison') || '[]')
  activeComparisonIds.value = storedComparison
  
  // Aktualizuj liczniki
  favoritesKey.value++
  comparisonKey.value++
}

onMounted(() => {
  if (typeof window !== 'undefined') {
    // Nasłuchuj niestandardowego zdarzenia zamiast 'storage'
    window.addEventListener('localStorageChange', handleStorageChange)
    // Zachowaj również nasłuchiwanie standardowego zdarzenia 'storage' dla kompatybilności
    window.addEventListener('storage', handleStorageChange)
  }
  // Sync favorites and comparison on mount
  syncFavorites()
  syncComparison()
})

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('localStorageChange', handleStorageChange)
    window.removeEventListener('storage', handleStorageChange)
  }
})
</script>

<template>
  <div class="app">
    <AppHeader
      :favorites-count="favoritesCount"
      :comparison-count="comparisonCount"
      @open-management-modal="isModalOpen = true"
      @navigate-to-add-ad="handleNavigateToAddAd"
      @open-favorites="isFavoritesPanelOpen = true"
      @open-comparison="isComparisonPanelOpen = true"
    />
    <router-view :key="favoritesKey + comparisonKey" @toggle-favorite="handleToggleFavorite" @toggle-comparison="handleToggleComparison" />
    <AppFooter />
    <FavoritesPanel
      :is-open="isFavoritesPanelOpen"
      @close="isFavoritesPanelOpen = false"
      @remove-favorite="handleRemoveFavorite"
    />
    <ComparisonPanel
      :is-open="isComparisonPanelOpen"
      @close="isComparisonPanelOpen = false"
      @remove-comparison="handleRemoveComparison"
    />
    <EmailModal
      :is-open="isModalOpen"
      @close="isModalOpen = false"
    />
    <FeedbackButton />
    <CookieConsent />
  </div>
</template>

<style>
* {
  box-sizing: border-box;
}

.app {
  min-height: 100vh;
}
</style>
