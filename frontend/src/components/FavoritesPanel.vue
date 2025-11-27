<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue'
import { supabase } from '../lib/supabase'
import type { Advertisement } from '../lib/supabase'

const props = defineProps<{
  isOpen: boolean
}>()

const emit = defineEmits<{
  close: []
  removeFavorite: [id: string]
}>()

const favoriteAds = ref<Advertisement[]>([])
const isLoading = ref(false)

const loadFavorites = async () => {
  const favoriteIds = JSON.parse(localStorage.getItem('favorites') || '[]')

  if (favoriteIds.length === 0) {
    favoriteAds.value = []
    return
  }

  try {
    isLoading.value = true
    const { data, error } = await supabase
      .from('advertisements')
      .select('*')
      .in('id', favoriteIds)

    if (error) throw error
    favoriteAds.value = data || []
  } catch (error) {
    console.error('Error loading favorites:', error)
  } finally {
    isLoading.value = false
  }
}

const removeFavorite = (id: string) => {
  emit('removeFavorite', id)
  favoriteAds.value = favoriteAds.value.filter(ad => ad.id !== id)
}



const handleStorageChange = () => {
  if (props.isOpen) {
    loadFavorites()
  }
}

watch(() => props.isOpen, (newValue) => {
  if (newValue) {
    loadFavorites()
  }
})

if (typeof window !== 'undefined') {
  window.addEventListener('storage', handleStorageChange)
}

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('storage', handleStorageChange)
  }
})
</script>

<template>
  <div>
    <div
      v-if="isOpen"
      class="overlay"
      @click="emit('close')"
    ></div>

    <div class="favorites-panel" :class="{ open: isOpen }">
      <div class="panel-header">
        <h2>
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="#EF4444"/>
          </svg>
          Ulubione ({{ favoriteAds.length }})
        </h2>
        <button @click="emit('close')" class="close-btn">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
            <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </button>
      </div>

      <div class="panel-content">
        <div v-if="isLoading" class="loading-state">
          <div class="spinner"></div>
          <p>Ładowanie...</p>
        </div>

        <div v-else-if="favoriteAds.length === 0" class="empty-state">
          <svg width="80" height="80" viewBox="0 0 24 24" fill="none">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" stroke="#d1d5db" stroke-width="2"/>
          </svg>
          <h3>Brak ulubionych</h3>
          <p>Dodaj ogłoszenia do ulubionych, aby móc łatwo do nich wrócić</p>
        </div>

        <div v-else class="favorites-list">
          <router-link
            v-for="ad in favoriteAds"
            :key="ad.id"
            :to="`/ogloszenie/${ad.id}`"
            class="favorite-item"
            @click="emit('close')"
          >
            <div class="favorite-image">
              <img
                v-if="ad.image_url"
                :src="ad.image_url"
                :alt="ad.title"
              />
              <div v-else class="no-image">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                  <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                  <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                  <path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2"/>
                </svg>
              </div>
            </div>

            <div class="favorite-content">
              <h3>{{ ad.title }}</h3>
              <div class="favorite-location">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                  <path d="M8 8C9.1 8 10 7.1 10 6C10 4.9 9.1 4 8 4C6.9 4 6 4.9 6 6C6 7.1 6.9 8 8 8Z" stroke="currentColor" stroke-width="1.3"/>
                  <path d="M8 14C8 14 12 10.5 12 6C12 3.79 10.21 2 8 2C5.79 2 4 3.79 4 6C4 10.5 8 14 8 14Z" stroke="currentColor" stroke-width="1.3"/>
                </svg>
                {{ ad.city }}
              </div>
              <div class="favorite-price">{{ ad.price.toLocaleString('pl-PL') }} PLN</div>
            </div>

            <button
              @click.prevent.stop="removeFavorite(ad.id)"
              class="remove-btn"
              aria-label="Usuń z ulubionych"
            >
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </router-link>
        </div>
      </div>


    </div>
  </div>
</template>

<style scoped>
.overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1999;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.favorites-panel {
  position: fixed;
  top: 0;
  right: 0;
  height: 100vh;
  width: 420px;
  background: white;
  box-shadow: -4px 0 24px rgba(0, 0, 0, 0.15);
  z-index: 2000;
  display: flex;
  flex-direction: column;
  transform: translateX(100%);
  transition: transform 0.3s ease;
}

.favorites-panel.open {
  transform: translateX(0);
}

.panel-header {
  padding: 1.5rem 2rem;
  border-bottom: 2px solid #f3f4f6;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.panel-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.close-btn {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  color: white;
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(90deg);
}

.panel-content {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
}

.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 2rem;
  text-align: center;
  min-height: 300px;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #f3f4f6;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.empty-state svg {
  margin-bottom: 1.5rem;
  opacity: 0.5;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  color: #1f2937;
  font-size: 1.25rem;
}

.empty-state p {
  margin: 0;
  color: #6b7280;
  line-height: 1.6;
}

.favorites-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.favorite-item {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  text-decoration: none;
  color: inherit;
  transition: all 0.2s;
  background: white;
}

.favorite-item:hover {
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
  transform: translateX(-4px);
}

.favorite-image {
  width: 80px;
  height: 80px;
  flex-shrink: 0;
  border-radius: 8px;
  overflow: hidden;
  background: #f3f4f6;
}

.favorite-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.no-image {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
}

.favorite-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  min-width: 0;
}

.favorite-content h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.favorite-location {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.favorite-price {
  font-weight: 700;
  color: #667eea;
  font-size: 1.05rem;
}

.remove-btn {
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  background: #fef2f2;
  border: 2px solid #fecaca;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  color: #dc2626;
}

.remove-btn:hover {
  background: #fee2e2;
  border-color: #fca5a5;
  transform: scale(1.05);
}



@media (max-width: 640px) {
  .favorites-panel {
    width: 100%;
  }

  .panel-header {
    padding: 1rem 1.5rem;
  }

  .panel-content {
    padding: 1rem;
  }

  .panel-footer {
    padding: 1rem 1.5rem;
  }
}
</style>
