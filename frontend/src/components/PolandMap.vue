<script setup lang="ts">
import { ref, onMounted, watch, onBeforeUnmount } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import type { Advertisement } from '../types'
import { slugify } from '../utils/slugify'
import { getFullImageUrl } from '../services/api'

// Funkcja formatująca adres i miasto, taka sama jak w AdCard
const formatLocation = (location: string, city: string) => {
  // Extract street and number from full address
  const parts = location.split(',').map(p => p.trim())
  
  let streetWithNumber = ''
  
  if (parts.length >= 2) {
    const firstPart = parts[0]
    const secondPart = parts[1]
    
    // Check if first part is a number
    if (/^\d+/.test(firstPart)) {
      streetWithNumber = `${secondPart} ${firstPart}`
    } else {
      streetWithNumber = firstPart
    }
  } else {
    streetWithNumber = parts[0] || location
  }
  
  return `${streetWithNumber}, ${city}`
}

// Funkcja zwracająca etykietę jednostki ceny
const getPriceUnitLabel = (ad: Advertisement): string => {
  const unit = ad.price_unit || 'month'
  switch (unit) {
    case 'day': return 'zł/dzień'
    case 'week': return 'zł/tydzień'
    case 'month': return 'zł/mies.'
    case 'year': return 'zł/rok'
    case 'campaign': return 'zł/kampania'
    default: return 'zł/mies.'
  }
}

const props = defineProps<{
  listings: Advertisement[]
  selectedRegion?: string
  selectedCity?: string
  selectedLocationCoords?: { lat: number; lng: number } | null
  hoveredAdId?: string | null
}>()

const emit = defineEmits<{
  'update:hoveredAdId': [id: string | null]
}>()

const mapContainer = ref<HTMLElement | null>(null)
let map: L.Map | null = null
const markers: Map<string, L.Marker> = new Map()
const isMapActive = ref(false)
const isLegendVisible = ref(false)
const isMobile = ref(window.innerWidth < 768)

const typeColors: Record<string, string> = {
  billboard: '#EF4444',
  citylight: '#F59E0B',
  led_screen: '#10B981',
  banner: '#8B5CF6',
  wall: '#EC4899',
  totem: '#3B82F6',
  transport: '#14B8A6',
  mobile: '#F97316',
  other: '#6B7280'
}

const regionCoordinates: Record<string, { lat: number; lng: number; zoom: number }> = {
  'dolnoslaskie': { lat: 51.1079, lng: 17.0385, zoom: 8 },
  'kujawsko-pomorskie': { lat: 53.1235, lng: 18.0084, zoom: 8 },
  'lubelskie': { lat: 51.2465, lng: 22.5684, zoom: 8 },
  'lubuskie': { lat: 52.2297, lng: 15.2365, zoom: 8 },
  'lodzkie': { lat: 51.7592, lng: 19.4560, zoom: 8 },
  'malopolskie': { lat: 49.85, lng: 20.2, zoom: 8 },
  'mazowieckie': { lat: 52.2297, lng: 21.0122, zoom: 8 },
  'opolskie': { lat: 50.6751, lng: 17.9213, zoom: 9 },
  'podkarpackie': { lat: 50.0412, lng: 21.9991, zoom: 8 },
  'podlaskie': { lat: 53.1325, lng: 23.1688, zoom: 8 },
  'pomorskie': { lat: 54.3520, lng: 18.6466, zoom: 8 },
  'slaskie': { lat: 50.2649, lng: 19.0238, zoom: 9 },
  'swietokrzyskie': { lat: 50.8661, lng: 20.6286, zoom: 9 },
  'warminsko-mazurskie': { lat: 53.7784, lng: 20.4801, zoom: 8 },
  'wielkopolskie': { lat: 52.4064, lng: 16.9252, zoom: 8 },
  'zachodniopomorskie': { lat: 53.4285, lng: 14.5528, zoom: 8 }
}

const createCustomIcon = (type: string, isHovered: boolean = false) => {
  const color = typeColors[type] || '#6B7280'
  const scale = isHovered ? 1.3 : 1
  const zIndex = isHovered ? 1000 : 500
  
  return L.divIcon({
    className: 'custom-marker',
    html: `
      <div style="
        background: ${color};
        width: ${32 * scale}px;
        height: ${32 * scale}px;
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        border: 3px solid white;
        box-shadow: 0 ${3 * scale}px ${10 * scale}px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        z-index: ${zIndex};
      ">
        <div style="
          width: ${12 * scale}px;
          height: ${12 * scale}px;
          background: white;
          border-radius: 50%;
          transform: rotate(45deg);
        "></div>
      </div>
    `,
    iconSize: [32 * scale, 32 * scale],
    iconAnchor: [16 * scale, 32 * scale],
    popupAnchor: [0, -32 * scale]
  })
}

const initMap = () => {
  if (!mapContainer.value) return

  // Granice Polski (przybliżone) - z marginesem
  const polandBounds = L.latLngBounds(
    [48.5, 13.5],  // południowo-zachodni róg (z marginesem)
    [55.5, 24.5]   // północno-wschodni róg (z marginesem)
  )

  map = L.map(mapContainer.value, {
    scrollWheelZoom: false,
    maxBounds: polandBounds,        // Nie można przesunąć mapy poza te granice
    maxBoundsViscosity: 1.0,        // Twarde ograniczenie (nie można przeciągnąć poza)
    minZoom: 6,                      // Minimalne przybliżenie (cała Polska)
    maxZoom: 18                      // Maksymalne przybliżenie
  }).setView([52.0, 19.0], 6)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
  }).addTo(map)

  // Enable scroll wheel zoom on click and hide hint
  map.on('click', () => {
    if (map && !map.scrollWheelZoom.enabled()) {
      map.scrollWheelZoom.enable()
      isMapActive.value = true
    }
  })
  
  // Disable scroll wheel zoom when mouse leaves the map
  if (mapContainer.value) {
    mapContainer.value.addEventListener('mouseleave', () => {
      if (map && map.scrollWheelZoom.enabled()) {
        map.scrollWheelZoom.disable()
        isMapActive.value = false
      }
    })
  }

  updateMarkers()
}

const updateMarkers = () => {
  if (!map) return

  // Usuń markery, których nie ma już w danych
  markers.forEach((marker, id) => {
    if (!props.listings.find(ad => ad.id === id)) {
      marker.remove()
      markers.delete(id)
    }
  })

  // Dodaj lub aktualizuj markery
  props.listings.forEach((ad) => {
    const isHovered = props.hoveredAdId === ad.id
    
    // Jeśli marker już istnieje, aktualizuj jego ikonę
    if (markers.has(ad.id)) {
      const marker = markers.get(ad.id)!
      marker.setIcon(createCustomIcon(ad.type, isHovered))
    } else {
      // Utwórz nowy marker
      const marker = L.marker([ad.latitude, ad.longitude], {
        icon: createCustomIcon(ad.type, isHovered)
      })

    const typeLabels: Record<string, string> = {
      billboard: 'Billboardy',
      citylight: 'Citylighty',
      led_screen: 'Ekrany LED',
      banner: 'Banery',
      wall: 'Ściany reklamowe',
      totem: 'Totemy reklamowe',
      transport: 'Reklama w transporcie',
      mobile: 'Reklama mobilna',
      other: 'Inne'
    }

    const mapTypeToUrlFormat = (type: string): string => {
      const typeMapping: Record<string, string> = {
        'billboard': 'billboardy',
        'citylight': 'citylighty',
        'led_screen': 'ekrany-led',
        'banner': 'banery',
        'wall': 'sciany-reklamowe',
        'totem': 'totemy-reklamowe',
        'transport': 'reklama-w-transporcie',
        'mobile': 'reklama-mobilna',
        'other': 'inne'
      }
      return typeMapping[type] || 'inne'
    }
    
    const citySlug = slugify(ad.city)
    const titleSlug = slugify(ad.title)
    const typeSlug = mapTypeToUrlFormat(ad.type)
    const adUrl = `/powierzchnia-reklamowa/${typeSlug}/${citySlug}/${titleSlug}-${ad.id}`

    const imageUrl = ad.image_url ? getFullImageUrl(ad.image_url) : ''
    
    const popupContent = `
      <div style="width: 250px;">
        <a href="${adUrl}" style="text-decoration: none; color: inherit; display: block;">
          ${imageUrl ? `
            <div style="margin: -20px -20px 12px -20px; overflow: hidden; border-radius: 12px 12px 0 0;">
              <img src="${imageUrl}" alt="${ad.title}" style="width: 100%; height: 140px; object-fit: cover; display: block;" />
            </div>
          ` : ''}
          <h3 style="margin: 0 0 8px 0; font-size: 1.1rem; font-weight: 700; color: #1F2937;">
            ${ad.title}
          </h3>
          <div style="display: flex; flex-direction: column; gap: 6px; font-size: 0.9rem;">
            <div style="display: flex; align-items: center; gap: 6px;">
              <span style="
                background: ${typeColors[ad.type] || '#6B7280'};
                color: white;
                padding: 2px 8px;
                border-radius: 4px;
                font-size: 0.75rem;
                font-weight: 600;
              ">
                ${typeLabels[ad.type] || ad.type}
              </span>
            </div>
            <div style="color: #6B7280;">
              <svg width="14" height="14" viewBox="0 0 14 14" fill="none" style="display: inline; margin-right: 4px; vertical-align: middle;">
                <path d="M7 7C7.825 7 8.5 6.325 8.5 5.5C8.5 4.675 7.825 4 7 4C6.175 4 5.5 4.675 5.5 5.5C5.5 6.325 6.175 7 7 7Z" stroke="#6B7280" stroke-width="1.2"/>
                <path d="M7 12C7 12 10.5 9 10.5 5.5C10.5 3.567 8.933 2 7 2C5.067 2 3.5 3.567 3.5 5.5C3.5 9 7 12 7 12Z" stroke="#6B7280" stroke-width="1.2"/>
              </svg>
              ${formatLocation(ad.location, ad.city)}
            </div>
            ${ad.dimensions ? `
              <div style="color: #6B7280;">
                📐 ${ad.dimensions}
              </div>
            ` : ''}
            <div style="font-weight: 700; color: #4F46E5; font-size: 1.1rem; margin-top: 4px;">
              ${Math.round(ad.price).toLocaleString('pl-PL')} ${getPriceUnitLabel(ad)}
            </div>
          </div>
        </a>
      </div>
    `

      marker.bindPopup(popupContent, { 
        maxWidth: 250,
        autoPan: true,    // Automatyczne przesunięcie mapy, aby popup był widoczny
        autoPanPadding: [50, 50]  // Padding przy autopan
      })
      
      marker.on('mouseover', () => {
        marker.setIcon(createCustomIcon(ad.type, true))
      })

      marker.on('mouseout', () => {
        if (props.hoveredAdId !== ad.id) {
          marker.setIcon(createCustomIcon(ad.type, false))
        }
      })
      
      marker.addTo(map!)
      markers.set(ad.id, marker)
    }
  })

  if (props.selectedLocationCoords) {
    // Priority 1: If exact coordinates are provided, zoom to them
    map.setView([props.selectedLocationCoords.lat, props.selectedLocationCoords.lng], 13)
  } else if (props.selectedCity && markers.size > 0) {
    // Priority 2: If city is selected, fit bounds to markers (likely clustered in that city)
    const group = new L.FeatureGroup(Array.from(markers.values()))
    map.fitBounds(group.getBounds(), { padding: [50, 50], maxZoom: 12 })
  } else if (props.selectedRegion && regionCoordinates[props.selectedRegion]) {
    // Priority 3: If region is selected (and no city), zoom to region center
    const region = regionCoordinates[props.selectedRegion]
    map.setView([region.lat, region.lng], region.zoom)
  } else {
    // Default: Always show full Poland when no specific filters are active
    map.setView([52.0, 19.0], 6)
  }
}

watch(() => props.listings, () => {
  updateMarkers()
}, { deep: true })

watch(() => props.selectedRegion, () => {
  updateMarkers()
})

watch(() => props.selectedCity, () => {
  updateMarkers()
})

watch(() => props.selectedLocationCoords, () => {
  updateMarkers()
}, { deep: true })

watch(() => props.hoveredAdId, (newId) => {
  if (!map) return
  
  // Aktualizuj tylko ikony markerów, bez zmiany pozycji mapy
  props.listings.forEach((ad) => {
    if (markers.has(ad.id)) {
      const marker = markers.get(ad.id)!
      const isHovered = newId === ad.id
      marker.setIcon(createCustomIcon(ad.type, isHovered))
    }
  })
})

onMounted(() => {
  initMap()
  
  // Update isMobile on window resize
  const handleResize = () => {
    isMobile.value = window.innerWidth < 768
  }
  
  window.addEventListener('resize', handleResize)
  
  // Cleanup
  onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize)
  })
})
</script>

<template>
  <section id="map-section" class="map-section">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">Mapa dostępnych powierzchni</h2>
        <p class="section-subtitle">Kliknij na pinezki, aby zobaczyć szczegóły ogłoszeń</p>
      </div>
    </div>

    <div class="map-wrapper">
      <div ref="mapContainer" class="map-container">
        <div v-if="!isMapActive && !isMobile" class="map-hint-overlay">
          <div class="map-hint-message">
            Kliknij, aby przybliżyć mapę
          </div>
        </div>
      </div>

      <!-- Floating toggle button -->
      <button 
        class="legend-toggle-button"
        @click="isLegendVisible = !isLegendVisible"
        :aria-expanded="isLegendVisible"
        :class="{ 'is-active': isLegendVisible }"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M3 12h18M3 6h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Legenda</span>
      </button>

      <!-- Side panel legend -->
      <div class="legend-side-panel" :class="{ 'is-visible': isLegendVisible && isMobile }">
        <div class="legend-header">
          <h3>Legenda</h3>
          <button class="close-legend" @click="isLegendVisible = false" aria-label="Zamknij legendę">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
              <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
        <div class="legend-content">
          <div class="legend-items">
            <div v-for="(color, type) in typeColors" :key="type" class="legend-item">
              <div class="legend-marker" :style="{ background: color }"></div>
              <span class="legend-label">
                {{ type === 'billboard' ? 'Billboardy' :
                    type === 'citylight' ? 'Citylighty' :
                    type === 'led_screen' ? 'Ekrany LED' :
                    type === 'banner' ? 'Banery' :
                    type === 'wall' ? 'Ściany reklamowe' :
                    type === 'totem' ? 'Totemy reklamowe' :
                    type === 'transport' ? 'Reklama w transporcie' :
                    type === 'mobile' ? 'Reklama mobilna' : 'Inne' }}
              </span>
            </div>
          </div>
        </div>
      </div>
      
      <div v-if="!isMobile" class="map-legend" :class="{ 'is-visible': isLegendVisible }">
        <div class="legend-header">
          <h3 class="legend-title">Legenda</h3>
          <button class="close-legend" @click="isLegendVisible = false" aria-label="Zamknij legendę">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
              <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
        <div class="legend-items">
          <div v-for="(color, type) in typeColors" :key="type" class="legend-item">
            <div class="legend-marker" :style="{ background: color }"></div>
            <span class="legend-label">
              {{ type === 'billboard' ? 'Billboardy' :
                  type === 'citylight' ? 'Citylighty' :
                  type === 'led_screen' ? 'Ekrany LED' :
                  type === 'banner' ? 'Banery' :
                  type === 'wall' ? 'Ściany' :
                  type === 'totem' ? 'Totemy' :
                  type === 'transport' ? 'Transport' :
                  type === 'mobile' ? 'Mobilna' : 'Inne' }}
            </span>
          </div>
        </div>
      </div>
      
      <!-- Overlay when legend is open -->
      <div 
        class="legend-overlay" 
        :class="{ 'is-visible': isLegendVisible && isMobile }"
        @click="isLegendVisible = false"
      ></div>
    </div>
  </section>
</template>

<style scoped>
.map-section {
  padding: 4rem 0 0 0;
  background: linear-gradient(to bottom, #F9FAFB 0%, white 100%);
  scroll-margin-top: 120px; /* Increased to ensure header visibility */
  scroll-behavior: smooth;
  display: block; /* Ensure the section is treated as a block element */
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

.section-header {
  text-align: center;
  margin-bottom: 3rem;
}

.section-title {
  font-size: 2.5rem;
  font-weight: 800;
  color: #1F2937;
  margin: 0 0 1rem 0;
}

.section-subtitle {
  font-size: 1.1rem;
  color: #6B7280;
  margin: 0;
}

.map-wrapper {
  position: relative;
  overflow: hidden;
}

.map-container {
  height: 600px;
  width: 100%;
  z-index: 1;
  position: relative;
  cursor: pointer;
}

.map-hint-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
  z-index: 999;
  opacity: 0;
  transition: opacity 0.3s;
}

.map-container:hover .map-hint-overlay {
  opacity: 1;
}

.map-hint-message {
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  white-space: nowrap;
}

/* Floating toggle button */
.legend-toggle-button {
  position: absolute;
  top: 1rem;
  right: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.75);
  border: 2px solid rgba(229, 231, 235, 0.75);
  border-radius: 8px;
  padding: 0.75rem 1.25rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #374151;
  cursor: pointer;
  z-index: 1001;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
  backdrop-filter: blur(4px);
}

.legend-toggle-button:hover {
  background: rgba(255, 255, 255, 0.9);
  border-color: rgba(209, 213, 219, 0.9);
  transform: translateY(-1px);
}

.legend-toggle-button:active {
  transform: translateY(0);
}

.legend-toggle-button.is-active {
  background: rgba(255, 255, 255, 0.9);
  border-color: rgba(156, 163, 175, 0.9);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.legend-toggle-button svg {
  flex-shrink: 0;
  transition: transform 0.2s ease;
}

.legend-toggle-button.is-active svg {
  transform: rotate(90deg);
}

/* Side panel legend */
.legend-side-panel {
  position: fixed;
  top: 0;
  right: -300px;
  width: 280px;
  height: 100vh;
  background: white;
  box-shadow: -4px 0 20px rgba(0, 0, 0, 0.1);
  z-index: 1100;
  transition: transform 0.3s ease-in-out;
  display: flex;
  flex-direction: column;
}

.legend-side-panel.is-visible {
  transform: translateX(-300px);
}

.legend-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem;
  border-bottom: 1px solid #E5E7EB;
  background: #f9fafb;
}

.legend-header h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #111827;
}

.close-legend {
  background: none;
  border: none;
  color: #6B7280;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.close-legend:hover {
  background: #F3F4F6;
  color: #111827;
}

.legend-content {
  flex: 1;
  overflow-y: auto;
  padding: 1.25rem 1.5rem;
}

.legend-items {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0.5rem;
  border-radius: 6px;
  transition: background-color 0.2s ease;
}

.legend-item:hover {
  background-color: #F9FAFB;
}

.legend-marker {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
  flex-shrink: 0;
}

.legend-label {
  font-size: 0.9rem;
  color: #374151;
  line-height: 1.4;
}

.map-legend {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: rgba(255, 255, 255, 0.8);
  border: 1px solid rgba(229, 231, 235, 0.8);
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  padding: 0.75rem 1rem;
  z-index: 1001;
  backdrop-filter: blur(4px);
  opacity: 0;
  visibility: hidden;
  transform: translateY(-8px);
  transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
  pointer-events: none;
}

.map-legend.is-visible {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
  pointer-events: auto;
}

.map-legend .legend-items {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.4rem;
  max-width: 320px;
}

.map-legend .legend-item {
  padding: 0.25rem 0.25rem;
}

.map-legend .legend-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.25rem;
  padding: 0.25rem 1rem 0.5rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  background: transparent;
}

/* Overlay when legend is open */
.legend-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.3);
  z-index: 1099;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s ease, visibility 0.3s ease;
}

.legend-overlay.is-visible {
  opacity: 1;
  visibility: visible;
}

/* Responsive adjustments */
@media (max-width: 480px) {
  .legend-side-panel {
    right: -100%;
    width: 90%;
    max-width: 320px;
  }
  
  .legend-side-panel.is-visible {
    transform: translateX(-100%);
  }
  
  .legend-toggle-button {
    right: 0.75rem;
    top: 0.75rem;
    padding: 0.4rem 0.8rem;
    font-size: 0.8rem;
  }
  
  .legend-toggle-button span {
    display: none;
  }
}

:deep(.custom-marker) {
  background: transparent;
  border: none;
}

:deep(.leaflet-popup-content-wrapper) {
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

:deep(.leaflet-popup-content) {
  margin: 1rem;
}

:deep(.leaflet-control-attribution) {
  display: none !important;
}

@media (max-width: 768px) {
  .map-section {
    padding: 6rem 0 3rem;
  }

  .section-title {
    font-size: 2rem;
  }

  .section-subtitle {
    font-size: 1rem;
  }

  .map-container {
    height: 450px;
  }

  .map-legend {
    top: auto;
    bottom: 1rem;
    right: 1rem;
    padding: 0.75rem 1rem;
  }

  .legend-title {
    font-size: 0.85rem;
  }

  .legend-label {
    font-size: 0.8rem;
  }
}
</style>
