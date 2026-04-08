import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useStreetViewStore = defineStore('streetView', () => {
  // Map of adId to availability status (true/false)
  const availabilityCache = ref<Record<string, boolean>>({})
  
  // Initialize from sessionStorage to persist during the session
  const initCache = () => {
    try {
      const cached = sessionStorage.getItem('street_view_availability')
      if (cached) {
        availabilityCache.value = JSON.parse(cached)
      }
    } catch (e) {
      // Silently fail
    }
  }

  const saveCache = () => {
    try {
      sessionStorage.setItem('street_view_availability', JSON.stringify(availabilityCache.value))
    } catch (e) {
      // Silently fail
    }
  }

  const getCachedAvailability = (adId: string): boolean | undefined => {
    return availabilityCache.value[adId]
  }

  const setAvailability = (adId: string, available: boolean) => {
    availabilityCache.value[adId] = available
    saveCache()
  }

  // Load cache on creation
  initCache()

  return {
    availabilityCache,
    getCachedAvailability,
    setAvailability
  }
})
