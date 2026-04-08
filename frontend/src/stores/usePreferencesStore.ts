import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import { api } from '../services/api'
import { useToast } from '../composables/useToast'

export const usePreferencesStore = defineStore('preferences', () => {
  const favorites = ref<string[]>(JSON.parse(localStorage.getItem('favorites') || '[]'))
  const comparison = ref<string[]>(JSON.parse(localStorage.getItem('comparison') || '[]'))
  const isDarkMode = ref<boolean>(false)
  
  // Helper to get toast instance (lazy)
  const getToast = () => {
    const { showToast } = useToast()
    return showToast
  }

  watch(favorites, (val) => {
    localStorage.setItem('favorites', JSON.stringify(val))
  }, { deep: true })

  watch(comparison, (val) => {
    localStorage.setItem('comparison', JSON.stringify(val))
  }, { deep: true })

  // Force light theme on mount
  if (typeof document !== 'undefined') {
    document.documentElement.setAttribute('data-theme', 'light')
    localStorage.removeItem('darkMode')
  }

  const toggleDarkMode = () => {
    // Disabled
  }

  const toggleFavorite = async (id: string) => {
    const idx = favorites.value.indexOf(id)
    if (idx > -1) {
      favorites.value.splice(idx, 1)
      getToast()('Usunięto z ulubionych', 'success')
    } else {
      try {
        const ad = await api.getAdvertisement(id)
        if (ad && ad.is_active) {
          favorites.value.push(id)
          getToast()('Dodano do ulubionych', 'success')
        }
      } catch (error) {
        getToast()('Błąd podczas dodawania do ulubionych', 'error')
      }
    }
  }

  const toggleComparison = async (id: string): Promise<{ success: boolean; error?: string }> => {
    const idx = comparison.value.indexOf(id)
    if (idx > -1) {
      comparison.value.splice(idx, 1)
      getToast()('Usunięto z porównania', 'success')
      return { success: true }
    } else {
      if (comparison.value.length >= 5) {
        const errorMsg = 'Możesz porównać maksymalnie 5 ogłoszeń'
        getToast()(errorMsg, 'error')
        return { success: false, error: errorMsg }
      }
      try {
        const ad = await api.getAdvertisement(id)
        if (ad && ad.is_active) {
          if (comparison.value.length > 0) {
            const existingAds = await api.getAdvertisementsByIds(comparison.value)
            const existingType = existingAds[0]?.type
            if (existingType && existingType !== ad.type) {
              const errorMsg = 'Możesz porównywać tylko ogłoszenia tego samego typu'
              getToast()(errorMsg, 'error')
              return { success: false, error: errorMsg }
            }
          }
          comparison.value.push(id)
          getToast()('Dodano do porównania', 'success')
          return { success: true }
        } else {
          const errorMsg = 'Ogłoszenie jest zarchiwizowane lub nieaktywne'
          getToast()(errorMsg, 'error')
          return { success: false, error: errorMsg }
        }
      } catch (error) {
         const errorMsg = 'Błąd podczas weryfikacji ogłoszenia'
         getToast()(errorMsg, 'error')
         return { success: false, error: errorMsg }
      }
    }
  }

  const isFavorite = (id: string) => favorites.value.includes(id)
  const isCompared = (id: string) => comparison.value.includes(id)

  const clearComparison = () => {
    comparison.value = []
  }

  const syncStores = async () => {
    try {
      if (favorites.value.length > 0) {
        const data = await api.getAdvertisementsByIds(favorites.value)
        const activeAds = data.filter(ad => ad.is_active)
        const activeIds = activeAds.map(ad => ad.id)
        if (activeIds.length !== favorites.value.length) {
          favorites.value = activeIds
        }
      }
      if (comparison.value.length > 0) {
        const data = await api.getAdvertisementsByIds(comparison.value)
        const activeAds = data.filter(ad => ad.is_active)
        const activeIds = activeAds.map(ad => ad.id)
        if (activeIds.length !== comparison.value.length) {
          comparison.value = activeIds
        }
      }
    } catch (error) {
      // Silently fail
    }
  }

  return { favorites, comparison, isDarkMode, toggleFavorite, toggleComparison, toggleDarkMode, isFavorite, isCompared, clearComparison, syncStores }
})
