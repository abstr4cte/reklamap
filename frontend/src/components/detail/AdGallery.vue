<script setup lang="ts">
import { ref } from 'vue'
import WebPImage from '../WebPImage.vue'
import { getFullImageUrl } from '../../services/api'

const props = defineProps<{
  images: string[]
  imageAlt: string
  thumbnailAlt: (index: number) => string
}>()

const currentImageIndex = ref(0)
const showImagePreview = ref(false)
const isZoomed = ref(false)
const touchStartX = ref(0)
const touchEndX = ref(0)
const touchStartTime = ref(0)

const nextImage = () => {
  if (props.images.length === 0) return
  currentImageIndex.value = (currentImageIndex.value + 1) % props.images.length
}

const prevImage = () => {
  if (props.images.length === 0) return
  currentImageIndex.value = (currentImageIndex.value - 1 + props.images.length) % props.images.length
}

const openImagePreview = () => {
  if (props.images.length > 0) {
    showImagePreview.value = true
    isZoomed.value = false
    document.body.style.overflow = 'hidden'
  }
}

const closeImagePreview = () => {
  showImagePreview.value = false
  isZoomed.value = false
  document.body.style.overflow = 'auto'
}

const toggleZoom = () => {
  isZoomed.value = !isZoomed.value
}

const handleTouchStart = (e: TouchEvent) => {
  touchStartX.value = e.touches[0].clientX
  touchStartTime.value = Date.now()
}

const handleTouchEnd = (e: TouchEvent) => {
  touchEndX.value = e.changedTouches[0].clientX
  const touchEndTime = Date.now()
  const duration = touchEndTime - touchStartTime.value
  const distance = touchEndX.value - touchStartX.value

  if (Math.abs(distance) > 50 && duration < 300) {
    if (distance > 0) {
      prevImage()
    } else {
      nextImage()
    }
    isZoomed.value = false
  }
}

const handleImageClick = () => {
  toggleZoom()
}
</script>

<template>
  <div class="image-gallery">
    <div class="main-image-wrapper">
      <div v-if="images.length > 0" class="image-container" @click="openImagePreview">
        <WebPImage :src="images[currentImageIndex]" :alt="imageAlt" class="main-image" />
        <div class="zoom-hint">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
            <path d="M15 15l6 6m-6-6a9 9 0 113.5-3.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      </div>
      <div v-else class="no-image">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none">
          <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
          <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
          <path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <p>Brak zdjęcia</p>
      </div>

      <button v-if="images.length > 1" @click.stop="prevImage" class="nav-btn prev">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
          <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
      <button v-if="images.length > 1" @click.stop="nextImage" class="nav-btn next">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
          <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </div>

    <div v-if="images.length > 1" class="thumbnails">
      <div 
        v-for="(img, index) in images" 
        :key="index" 
        class="thumbnail" 
        :class="{ active: index === currentImageIndex }"
        @click="currentImageIndex = index"
      >
        <WebPImage :src="img" :alt="thumbnailAlt(index)" />
      </div>
    </div>

    <!-- Image Preview Modal -->
    <Transition name="fade">
      <div 
        v-if="showImagePreview" 
        class="image-preview-overlay" 
        @click.self="closeImagePreview"
      >
        <button @click="closeImagePreview" class="preview-close-btn" aria-label="Zamknij podgląd">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
            <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>

        <button v-if="images.length > 1 && !isZoomed" @click="prevImage" class="preview-nav-btn prev" aria-label="Poprzednie zdjęcie">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
            <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>

        <button v-if="images.length > 1 && !isZoomed" @click="nextImage" class="preview-nav-btn next" aria-label="Następne zdjęcie">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
            <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>

        <div 
          class="preview-container" 
          :class="{ 'is-zoomed': isZoomed }"
          @touchstart="handleTouchStart"
          @touchend="handleTouchEnd"
          @click.self="closeImagePreview"
        >
          <div class="preview-image-wrapper" @click="handleImageClick">
            <img
              :src="getFullImageUrl(images[currentImageIndex])"
              :alt="imageAlt"
              :class="`preview-image ${isZoomed ? 'zoomed' : ''}`"
            />
          </div>
        </div>

        <div class="preview-footer">
          <div v-if="images.length > 1" class="preview-counter">
            {{ currentImageIndex + 1 }} / {{ images.length }}
          </div>
          <div class="preview-hint">
            {{ isZoomed ? 'Kliknij, aby pomniejszyć' : (images.length > 1 ? 'Przesuń, aby zmienić • ' : '') + 'Kliknij, aby powiększyć' }}
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.image-gallery {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  width: 100%;
  max-width: 100%;
  overflow: hidden;
}

.main-image-wrapper {
  position: relative;
  width: 100%;
  height: 500px;
  background: var(--bg-tertiary, #f3f4f6);
  border-radius: 12px;
  overflow: hidden;
  box-shadow: var(--card-shadow, 0 2px 8px rgba(0, 0, 0, 0.1));
  min-width: 0;
}

.image-container {
  width: 100%;
  height: 100%;
  cursor: zoom-in;
}

.main-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.zoom-hint {
  position: absolute;
  bottom: 1rem;
  right: 1rem;
  background: rgba(0, 0, 0, 0.4);
  padding: 0.5rem;
  border-radius: 50%;
  color: white;
  opacity: 0;
  transition: opacity 0.3s;
}

.image-container:hover .zoom-hint {
  opacity: 1;
}

.nav-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255, 255, 255, 0.8);
  border: none;
  border-radius: 50%;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #374151;
  transition: all 0.2s;
  backdrop-filter: blur(4px);
  z-index: 10;
}

.nav-btn:hover {
  background: white;
  color: #10B981;
}

.nav-btn.prev { left: 1rem; }
.nav-btn.next { right: 1rem; }

.thumbnails {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  padding: 0.75rem 1rem 0.5rem 1rem;
  max-width: 100%;
  -webkit-overflow-scrolling: touch;
}

.thumbnails::-webkit-scrollbar {
  height: 6px;
}

.thumbnails::-webkit-scrollbar-thumb {
  background: #e5e7eb;
  border-radius: 3px;
}

.thumbnail {
  width: 100px;
  height: 75px;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  border: 2px solid transparent;
  transition: all 0.2s;
  flex-shrink: 0;
}

.thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.thumbnail.active {
  border-color: #10B981;
  transform: scale(1.05);
}

.no-image {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: var(--bg-tertiary, #f3f4f6);
  color: var(--text-muted, #9ca3af);
}

/* Preview Modal Styles */
.image-preview-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.95);
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.preview-close-btn {
  position: absolute;
  top: 1.5rem;
  right: 1.5rem;
  background: transparent;
  border: none;
  color: white;
  cursor: pointer;
  padding: 0.5rem;
  z-index: 10001;
}

.preview-nav-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 50%;
  width: 64px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: white;
  z-index: 10001;
  transition: background 0.2s;
}

.preview-nav-btn:hover { background: rgba(255, 255, 255, 0.2); }
.preview-nav-btn.prev { left: 1.5rem; }
.preview-nav-btn.next { right: 1.5rem; }

.preview-container {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.preview-image-wrapper {
  max-width: 90%;
  max-height: 80vh;
  transition: transform 0.3s ease;
}

.preview-image {
  max-width: 100%;
  max-height: 80vh;
  object-fit: contain;
  cursor: zoom-in;
  transition: transform 0.3s ease;
}

.preview-image.zoomed {
  transform: scale(2);
  cursor: zoom-out;
}

.preview-footer {
  position: absolute;
  bottom: 2rem;
  left: 0;
  right: 0;
  text-align: center;
  color: white;
}

.preview-counter {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.preview-hint {
  font-size: 0.9rem;
  opacity: 0.7;
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

@media (max-width: 768px) {
  .main-image-wrapper { 
    height: auto;
    aspect-ratio: 4/3;
    min-height: 250px;
  }
  .preview-nav-btn { display: none; }
  .thumbnails { padding: 0.75rem 1rem; }
}
</style>
