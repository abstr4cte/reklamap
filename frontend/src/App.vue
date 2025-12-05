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

const router = useRouter()
const isModalOpen = ref(false)
const isFavoritesPanelOpen = ref(false)
const isComparisonPanelOpen = ref(false)
const favoritesKey = ref(0)
const comparisonKey = ref(0)

const favoritesCount = computed(() => {
  favoritesKey.value
  const favorites = JSON.parse(localStorage.getItem('favorites') || '[]')
  return favorites.length
})

const comparisonCount = computed(() => {
  comparisonKey.value
  const comparison = JSON.parse(localStorage.getItem('comparison') || '[]')
  return comparison.length
})

const handleNavigateToAddAd = () => {
  router.push('/dodaj-ogloszenie')
}

const handleToggleFavorite = (id: string) => {
  const favorites = JSON.parse(localStorage.getItem('favorites') || '[]')
  const index = favorites.indexOf(id)

  if (index > -1) {
    favorites.splice(index, 1)
  } else {
    favorites.push(id)
  }

  localStorage.setItem('favorites', JSON.stringify(favorites))
  favoritesKey.value++
}

const handleRemoveFavorite = (id: string) => {
  handleToggleFavorite(id)
}

const handleToggleComparison = (id: string) => {
  const comparison = JSON.parse(localStorage.getItem('comparison') || '[]')
  const index = comparison.indexOf(id)

  if (index > -1) {
    comparison.splice(index, 1)
  } else {
    if (comparison.length >= 5) {
      alert('Możesz porównać maksymalnie 5 ogłoszeń')
      return
    }
    comparison.push(id)
  }

  localStorage.setItem('comparison', JSON.stringify(comparison))
  comparisonKey.value++
}

const handleRemoveComparison = (id: string) => {
  handleToggleComparison(id)
}

const handleStorageChange = () => {
  favoritesKey.value++
  comparisonKey.value++
}

onMounted(() => {
  if (typeof window !== 'undefined') {
    window.addEventListener('storage', handleStorageChange)
  }
})

onUnmounted(() => {
  if (typeof window !== 'undefined') {
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
