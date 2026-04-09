<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import AppHeader from './components/AppHeader.vue'
import AppFooter from './components/AppFooter.vue'
import FavoritesPanel from './components/FavoritesPanel.vue'
import ComparisonPanel from './components/ComparisonPanel.vue'
import EmailModal from './components/EmailModal.vue'
import FeedbackButton from './components/FeedbackButton.vue'
import CookieConsent from './components/CookieConsent.vue'
import ToastNotification from './components/ToastNotification.vue'
import { usePreferencesStore } from './stores/usePreferencesStore'
import ScrollToTop from './components/ScrollToTop.vue'
import { useToast } from './composables/useToast'

const router = useRouter()
const isModalOpen = ref(false)
const isFavoritesPanelOpen = ref(false)
const isComparisonPanelOpen = ref(false)
const prefStore = usePreferencesStore()
const toast = ref<InstanceType<typeof ToastNotification> | null>(null)
const { setToastInstance } = useToast()

// Compute favorites count based on active listings only
const favoritesCount = computed(() => prefStore.favorites.length)

// Compute comparison count based on active listings only
const comparisonCount = computed(() => prefStore.comparison.length)

const handleNavigateToAddAd = () => {
  router.push('/dodaj-powierzchnie-reklamowa')
}

const handleToggleFavorite = async (id: string) => {
  await prefStore.toggleFavorite(id)
}

const handleRemoveFavorite = (id: string) => {
  handleToggleFavorite(id)
}

const handleToggleComparison = async (id: string) => {
  await prefStore.toggleComparison(id)
}

const handleRemoveComparison = (id: string) => {
  handleToggleComparison(id)
}

onMounted(() => {
  if (typeof window !== 'undefined') {
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
  prefStore.syncStores()
  // Set global toast instance after DOM is ready
  nextTick(() => {
    setToastInstance(toast.value)
  })
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
    <router-view v-slot="{ Component, route }">
      <keep-alive :include="['HomePage']" :max="5">
        <component :is="Component" :key="route.path" @toggle-favorite="handleToggleFavorite" @toggle-comparison="handleToggleComparison" />
      </keep-alive>
    </router-view>
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
    <ScrollToTop />
  </div>
</template>

<style>
* {
  box-sizing: border-box;
}

.app {
  min-height: 100vh;
  padding-top: 72px;
  overflow-x: clip;
  width: 100%;
}
</style>
