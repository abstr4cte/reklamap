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
import ToastNotification from './components/ToastNotification.vue'
import { api } from './services/api'

const router = useRouter()
const isModalOpen = ref(false)
const isFavoritesPanelOpen = ref(false)
const isComparisonPanelOpen = ref(false)
const favoritesKey = ref(0)
const comparisonKey = ref(0)
const activeFavoriteIds = ref<string[]>([])
const activeComparisonIds = ref<string[]>([])
const toast = ref<InstanceType<typeof ToastNotification> | null>(null)

// Synchronize favorites with active listings
const syncFavorites = async () => {
  const favoriteIds = JSON.parse(localStorage.getItem('favorites') || '[]')
  
  console.log('🔄 syncFavorites - IDs z localStorage:', favoriteIds)
  
  if (favoriteIds.length === 0) {
    activeFavoriteIds.value = []
    return
  }
  
  try {
    const data = await api.getAdvertisementsByIds(favoriteIds)
    console.log('📥 Pobrane ogłoszenia:', data.length)
    
    // Filter to only include active listings
    const activeAds = data.filter(ad => ad.is_active)
    console.log('✅ Aktywne ogłoszenia:', activeAds.length, activeAds.map(ad => ad.id))
    
    activeFavoriteIds.value = activeAds.map(ad => ad.id)
    
    // Update localStorage if there are inactive/deleted ads
    if (activeAds.length < favoriteIds.length) {
      console.log('🗑️ Usuwam nieaktywne z localStorage')
      localStorage.setItem('favorites', JSON.stringify(activeFavoriteIds.value))
      favoritesKey.value++
    }
  } catch (error) {
    console.error('Error syncing favorites:', error)
  }
}

// Synchronize comparison with active listings
const syncComparison = async () => {
  const comparisonIds = JSON.parse(localStorage.getItem('comparison') || '[]')
  
  if (comparisonIds.length === 0) {
    activeComparisonIds.value = []
    return
  }
  
  try {
    const data = await api.getAdvertisementsByIds(comparisonIds)
    // Filter to only include active listings
    const activeAds = data.filter(ad => ad.is_active)
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

// Compute favorites count based on active listings only
const favoritesCount = computed(() => {
  favoritesKey.value // Dependency to trigger recomputation
  return activeFavoriteIds.value.length
})

// Compute comparison count based on active listings only
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
      if (ad && ad.is_active) {
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
      toast.value?.add('Możesz porównać maksymalnie 5 ogłoszeń', 'error')
      return
    }
    // Verify the ad exists and is active before adding
    try {
      const ad = await api.getAdvertisement(id)
      if (ad && ad.is_active) {
        // Check if there are already ads in comparison with different type
        if (comparison.length > 0) {
          const existingAds = await api.getAdvertisementsByIds(comparison)
          const existingType = existingAds[0]?.type
          
          if (existingType && existingType !== ad.type) {
            toast.value?.add('Możesz porównywać tylko ogłoszenia tego samego typu', 'error')
            return
          }
        }
        
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
  // Synchronizuj z localStorage i filtruj tylko aktywne ogłoszenia
  syncFavorites()
  syncComparison()
}

onMounted(() => {
  if (typeof window !== 'undefined') {
    // Nasłuchuj niestandardowego zdarzenia zamiast 'storage'
    window.addEventListener('localStorageChange', handleStorageChange)
    // Zachowaj również nasłuchiwanie standardowego zdarzenia 'storage' dla kompatybilności
    window.addEventListener('storage', handleStorageChange)
    
    // Add Organization and WebSite structured data for SEO
    const organizationSchema = {
      '@context': 'https://schema.org',
      '@type': 'Organization',
      'name': 'ReklaMap',
      'url': 'https://reklamap.pl',
      'logo': 'https://reklamap.pl/logo.png',
      'description': 'Platforma do wynajmu powierzchni reklamowych w całej Polsce',
      'contactPoint': {
        '@type': 'ContactPoint',
        'email': 'kontakt@reklamap.pl',
        'contactType': 'customer service',
        'availableLanguage': 'Polish'
      },
      'sameAs': []
    }
    
    const websiteSchema = {
      '@context': 'https://schema.org',
      '@type': 'WebSite',
      'name': 'ReklaMap',
      'url': 'https://reklamap.pl',
      'potentialAction': {
        '@type': 'SearchAction',
        'target': {
          '@type': 'EntryPoint',
          'urlTemplate': 'https://reklamap.pl/powierzchnie-reklamowe?keyword={search_term_string}'
        },
        'query-input': 'required name=search_term_string'
      }
    }
    
    // Add schemas to head
    const orgScript = document.createElement('script')
    orgScript.type = 'application/ld+json'
    orgScript.textContent = JSON.stringify(organizationSchema)
    document.head.appendChild(orgScript)
    
    const websiteScript = document.createElement('script')
    websiteScript.type = 'application/ld+json'
    websiteScript.textContent = JSON.stringify(websiteSchema)
    document.head.appendChild(websiteScript)
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
    <ToastNotification ref="toast" />
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
