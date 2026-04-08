<script setup lang="ts">
import { ref, watch, onBeforeUnmount } from 'vue'
import type * as LType from 'leaflet'
import { filterWaterFeatures } from '../services/locationService'

interface Props {
  modelValue: boolean
  initialLatitude: number
  initialLongitude: number
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'confirm', data: { latitude: number, longitude: number, location: string, city: string, region: string }): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

let L: typeof LType | null = null

const loadLeaflet = async () => {
  if (L) return L
  const LModule = await import('leaflet')
  L = LModule.default || LModule
  await import('leaflet/dist/leaflet.css')
  
  const [icon, iconShadow] = await Promise.all([
    import('leaflet/dist/images/marker-icon.png'),
    import('leaflet/dist/images/marker-shadow.png')
  ])
  
  const DefaultIcon = L!.icon({
    iconUrl: icon.default,
    shadowUrl: iconShadow.default,
    iconSize: [25, 41],
    iconAnchor: [12, 41]
  })
  L!.Marker.prototype.options.icon = DefaultIcon
  return L
}

let turfPoint: any = null
let turfBooleanPointInPolygon: any = null
let polandGeoJsonData: any = null

const loadGeoUtils = async () => {
  if (turfPoint && turfBooleanPointInPolygon && polandGeoJsonData) return
  const [pointModule, polygonModule, geojsonResponse] = await Promise.all([
    import('@turf/helpers'),
    import('@turf/boolean-point-in-polygon'),
    fetch('/data/poland_highres.json').then(res => res.json())
  ])
  turfPoint = pointModule.point
  turfBooleanPointInPolygon = polygonModule.default
  polandGeoJsonData = geojsonResponse
}

const isInPoland = async (lat: number, lng: number): Promise<boolean> => {
  await loadGeoUtils()
  const pt = turfPoint([lng, lat])
  // @ts-ignore
  for (const feature of polandGeoJsonData.features) {
    if (turfBooleanPointInPolygon(pt, feature.geometry as any)) {
      return true
    }
  }
  return false
}

const modalMapContainer = ref<HTMLElement | null>(null)
let modalMap: LType.Map | null = null
let modalMarker: LType.Marker | null = null

const modalSearchQuery = ref('')
const modalSearchSuggestions = ref<any[]>([])
const showModalSearchSuggestions = ref(false)
let modalSearchTimeout: ReturnType<typeof setTimeout> | null = null

const showToast = ref(false)
const toastMessage = ref('')

const displayToast = (message: string) => {
  toastMessage.value = message
  showToast.value = true
  setTimeout(() => {
    showToast.value = false
  }, 3000)
}

const closeModal = () => {
  emit('update:modelValue', false)
  modalSearchQuery.value = ''
  modalSearchSuggestions.value = []
  showModalSearchSuggestions.value = false
  if (modalMap) {
    modalMap.remove()
    modalMap = null
    modalMarker = null
  }
}

const initModalMap = async () => {
  if (!modalMapContainer.value || modalMap) return

  await loadLeaflet()
  if (!L) return

  const polandBounds = L.latLngBounds([48.5, 13.5], [55.5, 24.5])

  const isDefaultLocation = props.initialLatitude === 52.0 && props.initialLongitude === 19.0
  const isMobile = window.innerWidth <= 768
  const zoomLevel = isDefaultLocation ? (isMobile ? 4 : 5) : (isMobile ? 11 : 12)
  
  modalMap = L.map(modalMapContainer.value, {
    maxBounds: polandBounds,
    maxBoundsViscosity: 1.0,
    minZoom: 4.5,
    maxZoom: 18,
    zoomControl: true
  }).setView([props.initialLatitude || 52.0, props.initialLongitude || 19.0], zoomLevel)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(modalMap)

  modalMarker = L.marker([props.initialLatitude || 52.0, props.initialLongitude || 19.0], {
    draggable: true
  }).addTo(modalMap)

  modalMarker.on('dragend', async () => {
    const position = modalMarker!.getLatLng()
    const isInside = await isInPoland(position.lat, position.lng)
    if (!isInside) {
      displayToast('Lokalizacja musi być w Polsce')
      modalMarker!.setLatLng([props.initialLatitude, props.initialLongitude])
      return
    }
    
    // Reverse geocode to get address and update modal search input
    try {
      const response = await fetch(
        `https://nominatim.openstreetmap.org/reverse?format=json&lat=${position.lat}&lon=${position.lng}&zoom=18&addressdetails=1`
      )
      const data = await response.json()
      
      if (data.display_name) {
        modalSearchQuery.value = data.display_name
      }
    } catch (error) {
      // Silently fail
    }
  })

  modalMap!.on('click', async (e: LType.LeafletMouseEvent) => {
    const isInside = await isInPoland(e.latlng.lat, e.latlng.lng)
    if (!isInside) {
      displayToast('Lokalizacja musi być w Polsce')
      return
    }
    modalMarker!.setLatLng(e.latlng)
    
    // Reverse geocode to get address and update modal search input
    try {
      const response = await fetch(
        `https://nominatim.openstreetmap.org/reverse?format=json&lat=${e.latlng.lat}&lon=${e.latlng.lng}&zoom=18&addressdetails=1`
      )
      const data = await response.json()
      
      if (data.display_name) {
        modalSearchQuery.value = data.display_name
      }
    } catch (error) {
      // Silently fail
    }
  })
}

const searchModalLocation = () => {
  if (modalSearchTimeout) {
    clearTimeout(modalSearchTimeout)
  }

  if (modalSearchQuery.value.length < 3) {
    modalSearchSuggestions.value = []
    showModalSearchSuggestions.value = false
    return
  }

  modalSearchTimeout = setTimeout(async () => {
    try {
      const response = await fetch(
        `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(modalSearchQuery.value)}&countrycodes=pl&limit=10&addressdetails=1`
      )
      const data = await response.json()
      
      // Filter out water features (rivers, lakes, etc.)
      const filteredData = filterWaterFeatures(data)
      
      modalSearchSuggestions.value = filteredData
      showModalSearchSuggestions.value = filteredData.length > 0
    } catch (error) {
      // Silently fail
    }
  }, 500)
}

const selectModalLocation = async (suggestion: any) => {
  const lat = parseFloat(suggestion.lat)
  const lng = parseFloat(suggestion.lon)
  
  const isInside = await isInPoland(lat, lng)
  if (!isInside) {
    displayToast('Lokalizacja musi być w Polsce')
    return
  }

  if (modalMap && modalMarker) {
    modalMap.setView([lat, lng], 16)
    modalMarker.setLatLng([lat, lng])
  }
  
  modalSearchQuery.value = suggestion.display_name || ''
  modalSearchSuggestions.value = []
  showModalSearchSuggestions.value = false
}

const confirmModalLocation = async () => {
  if (!modalMarker) return

  const position = modalMarker.getLatLng()
  
  const isInside = await isInPoland(position.lat, position.lng)
  if (!isInside) {
    displayToast('Lokalizacja musi być w Polsce')
    return
  }

  // Reverse geocode to get full address details
  try {
    const response = await fetch(
      `https://nominatim.openstreetmap.org/reverse?format=json&lat=${position.lat}&lon=${position.lng}&zoom=18&addressdetails=1`
    )
    const data = await response.json()
    
    const address = data.address
    let city = address.city || address.town || address.village || address.municipality || address.county || address.administrative || ''
    if (!address.city && !address.town && !address.village && address.municipality) {
      city = city.replace(/^gmina\s+/i, '')
    }
    
    emit('confirm', {
      latitude: position.lat,
      longitude: position.lng,
      location: data.display_name || '',
      city: city,
      region: address.state || ''
    })
  } catch (error) {
    // Fallback - emit without full address details
    emit('confirm', {
      latitude: position.lat,
      longitude: position.lng,
      location: modalSearchQuery.value || '',
      city: '',
      region: ''
    })
  }
  
  closeModal()
}

// Watch for modal open/close
watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    document.body.style.overflow = 'hidden'
    setTimeout(() => initModalMap(), 100)
  } else {
    document.body.style.overflow = ''
    if (modalMap) {
      modalMap.remove()
      modalMap = null
      modalMarker = null
    }
  }
})

// Cleanup on unmount
onBeforeUnmount(() => {
  document.body.style.overflow = ''
  if (modalMap) {
    modalMap.remove()
    modalMap = null
    modalMarker = null
  }
})
</script>

<template>
  <div v-if="modelValue" class="modal-overlay" @click="closeModal">
    <div v-if="showToast" class="toast-notification-map">
      {{ toastMessage }}
    </div>
    
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3>Zaznacz lokalizację na mapie</h3>
        <button type="button" @click="closeModal" class="modal-close">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
            <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </button>
      </div>
      
      <div class="modal-search">
        <div class="modal-search-wrapper">
          <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
            <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
            <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <input
            v-model="modalSearchQuery"
            @input="searchModalLocation"
            type="text"
            placeholder="Wyszukaj miasto, ulicę..."
            class="modal-search-input"
          />
          <button
            v-if="modalSearchQuery"
            type="button"
            @click="modalSearchQuery = ''"
            class="modal-clear-button"
            title="Wyczyść wyszukiwanie"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
              <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
          <div v-if="showModalSearchSuggestions && modalSearchSuggestions.length > 0" class="modal-suggestions">
            <div
              v-for="(suggestion, index) in modalSearchSuggestions"
              :key="index"
              @click="selectModalLocation(suggestion)"
              class="modal-suggestion-item"
            >
              {{ suggestion.display_name }}
            </div>
          </div>
        </div>
      </div>
      
      <div class="modal-body">
        <div ref="modalMapContainer" class="modal-map"></div>
        <p class="modal-hint">Wyszukaj lokalizację powyżej lub kliknij na mapie / przeciągnij marker</p>
      </div>
      
      <div class="modal-footer">
        <button type="button" @click="closeModal" class="btn-cancel-modal">Anuluj</button>
        <button type="button" @click="confirmModalLocation" class="btn-primary-modal">Potwierdź lokalizację</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Toast notification */
.toast-notification-map {
  position: absolute;
  top: 4rem;
  left: 50%;
  transform: translateX(-50%);
  background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
  color: white;
  padding: 1rem 2rem;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(239, 68, 68, 0.3);
  z-index: 10000;
  font-weight: 600;
  font-size: 1rem;
  animation: scaleIn 0.3s ease;
}

@keyframes scaleIn {
  from {
    opacity: 0;
    transform: translateX(-50%) scale(0.8);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) scale(1);
  }
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 80px;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
  backdrop-filter: blur(8px);
  animation: fadeIn 0.2s ease-out;
  overflow-y: auto;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.modal-content {
  background: white;
  border-radius: 20px;
  width: 100%;
  max-width: 600px;
  max-height: calc(100vh - 115px);
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  animation: scaleInModal 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  margin: auto;
  position: relative;
  z-index: 10000;
}

@keyframes scaleInModal {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.modal-header {
  background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
  padding: 1rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e5e7eb;
  flex-shrink: 0;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  color: #111827;
  font-weight: 700;
}

.modal-close {
  background: white;
  border: 1px solid #e5e7eb;
  color: #6b7280;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 8px;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
}

.modal-close:hover {
  background: #EF4444;
  color: white;
  border-color: #EF4444;
}

.modal-search {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #f3f4f6;
  flex-shrink: 0;
}

.modal-search-wrapper {
  position: relative;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  pointer-events: none;
  z-index: 1;
}

.modal-search-input {
  width: 100%;
  padding: 0.75rem 0.75rem 0.75rem 2.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: all 0.2s;
}

.modal-search-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.modal-clear-button {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s;
  z-index: 2;
}

.modal-clear-button:hover {
  color: #6b7280;
}

/* FIXED: Zwiększony z-index dla suggestions */
.modal-suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e5e7eb;
  border-top: none;
  border-radius: 0 0 8px 8px;
  max-height: 200px;
  overflow-y: auto;
  z-index: 10001;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  margin-top: 1px;
}

.modal-suggestion-item {
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background-color 0.2s;
  border-bottom: 1px solid #f3f4f6;
  font-size: 0.9rem;
  color: #374151;
}

.modal-suggestion-item:last-child {
  border-bottom: none;
}

.modal-suggestion-item:hover {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.08) 100%);
}

.modal-body {
  flex: 1;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  padding: 1rem 1.5rem;
  min-height: 0;
}

.modal-map {
  width: 100%;
  height: 400px;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  flex-shrink: 0;
}

.modal-hint {
  margin: 1rem 0 0 0;
  font-size: 0.875rem;
  color: #6b7280;
  text-align: center;
  font-style: italic;
}

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #f3f4f6;
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  flex-shrink: 0;
}

.btn-cancel-modal {
  padding: 0.75rem 1.5rem;
  border: 1px solid #e5e7eb;
  background: white;
  color: #374151;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cancel-modal:hover {
  background: #f9fafb;
  border-color: #d1d5db;
}

.btn-primary-modal {
  padding: 0.75rem 1.5rem;
  border: none;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-primary-modal:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

/* Mobile responsive */
@media (max-width: 768px) {
  .modal-overlay {
    top: 60px;
    padding: 0.5rem;
  }

  .modal-content {
    max-height: calc(100vh - 75px);
    border-radius: 16px;
  }

  .modal-header {
    padding: 0.875rem 1rem;
  }

  .modal-header h3 {
    font-size: 1.1rem;
  }

  .modal-search {
    padding: 0.875rem 1rem;
  }

  .modal-body {
    padding: 0.875rem 1rem;
  }

  .modal-map {
    height: 300px;
  }

  .modal-footer {
    padding: 0.875rem 1rem;
    flex-direction: column;
  }

  .btn-cancel-modal,
  .btn-primary-modal {
    width: 100%;
  }
}
</style>
