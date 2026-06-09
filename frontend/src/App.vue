<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
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

const updateHeaderHeight = () => {
  const header = document.querySelector('.app-header') as HTMLElement | null
  if (header) {
    const height = header.offsetHeight
    document.documentElement.style.setProperty('--header-height', `${height}px`)
  }
}

onMounted(() => {
  if (typeof window !== 'undefined') {
    updateHeaderHeight()
    window.addEventListener('resize', updateHeaderHeight)
    // Organization/WebSite (JSON-LD) przeniesione do statycznego @graph w index.html
    // (jeden render-niezalezny sygnal encji z @id). Tu juz ich nie wstrzykujemy.
  }
  prefStore.syncStores()
  nextTick(() => {
    setToastInstance(toast.value)

    const params = new URLSearchParams(window.location.search)
    const wypisano = params.get('wypisano')
    const blad = params.get('blad')
    if (wypisano === 'alerty') {
      const miasto = params.get('miasto')
      const region = params.get('region')
      const typ    = params.get('typ')
      const czesci = [typ, miasto || region].filter(Boolean)
      const szczegoly = czesci.length ? ` (${czesci.join(', ')})` : ''
      toast.value?.add(`Wypisano z powiadomień${szczegoly}. Nie będziesz już otrzymywać alertów dla tych kryteriów.`, 'success')
      window.history.replaceState({}, '', window.location.pathname)
    } else if (wypisano === 'newsletter') {
      toast.value?.add('Wypisano z newslettera ReklaMap. Twój adres e-mail został usunięty z listy.', 'success')
      window.history.replaceState({}, '', window.location.pathname)
    } else if (blad === 'newsletter-token') {
      toast.value?.add('Link do wypisania jest nieprawidłowy lub już wygasł.', 'error')
      window.history.replaceState({}, '', window.location.pathname)
    } else if (blad === 'alert-token') {
      toast.value?.add('Link do wypisania z alertów jest nieprawidłowy lub już wygasł.', 'error')
      window.history.replaceState({}, '', window.location.pathname)
    }
  })
})

onUnmounted(() => {
  window.removeEventListener('resize', updateHeaderHeight)
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
      <keep-alive :include="['HomePage', 'listings']" :max="5">
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
  padding-top: var(--header-height, 100px);
  overflow-x: clip;
  width: 100%;
}
</style>
