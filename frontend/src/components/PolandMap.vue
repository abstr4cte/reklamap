<script setup lang="ts">
import { ref, onMounted, watch, onBeforeUnmount } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import type { Advertisement } from '../types'
import { slugify } from '../utils/slugify'
import { getFullImageUrl } from '../services/api'
import { useSearchStore, typeColors } from '../stores/useSearchStore'
import { mapTypeToUrlFormat } from '../utils/typeMapping'
// import 'leaflet.markercluster'
// import 'leaflet.markercluster/dist/MarkerCluster.css'
// import 'leaflet.markercluster/dist/MarkerCluster.Default.css'

const searchStore = useSearchStore()

// Funkcja formatująca adres i miasto, taka sama jak w AdCard
const formatLocation = (location: string, city: string) => searchStore.formatLocation(location, city)

// Funkcja zwracająca etykietę jednostki ceny
const getPriceUnitLabel = (ad: Advertisement): string => searchStore.getPriceUnitLabel(ad)

const scrollToAdGrid = () => {
  const adGrid = document.querySelector('.listings-section')
  if (adGrid) {
    const elementPosition = adGrid.getBoundingClientRect().top + window.pageYOffset
    const offsetPosition = elementPosition - 32
    
    window.scrollTo({
      top: offsetPosition,
      behavior: 'smooth'
    })
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
let resizeObserver: ResizeObserver | null = null
// let markerClusterGroup: any = null
const markers: Map<string, L.Marker> = new Map()
const isMapActive = ref(false)
const isLegendVisible = ref(false)
const isMobile = ref(window.innerWidth < 768)
const headerHeight = ref(80)
const mapSection = ref<HTMLElement | null>(null)
const showMobileToggle = ref(false)
const isMobileClamped = ref(false)
const showDesktopToggle = ref(false)
const isDesktopClamped = ref(false)
const isProgrammaticMove = ref(false)



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

  if (map) {
    map.invalidateSize()
    return
  }

  // Granice Polski (bardziej rozszerzone) - aby markery/popupy nie były ucinane
  const polandBounds = L.latLngBounds(
    [47.5, 12.0],  // południowo-zachodni róg
    [57.5, 26.0]   // północno-wschodni róg
  )

  isProgrammaticMove.value = true
  map = L.map(mapContainer.value, {
    scrollWheelZoom: false,
    dragging: !isMobile.value, // Disable dragging on mobile until activated
    touchZoom: false, // Disable touch zoom until activated
    doubleClickZoom: false, // Disable double click zoom until activated
    zoomControl: true, // Disable zoom controls until activated
    maxBounds: polandBounds,
    maxBoundsViscosity: 1.0,
    minZoom: 3.5,
    maxZoom: 18
  }).setView([52.0, 19.0], isMobile.value ? 5 : 6)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
  }).addTo(map)

  // Initialize marker cluster group - DISABLED
  // markerClusterGroup = (L as any).markerClusterGroup({
  //   showCoverageOnHover: false,
  //   spiderfyOnMaxZoom: true,
  //   zoomToBoundsOnClick: true,
  //   maxClusterRadius: 50
  // })
  // map.addLayer(markerClusterGroup)

  // Function to enable all interactions
  const enableMapInteractions = () => {
    if (!map) return
    
    map.scrollWheelZoom.enable()
    map.dragging.enable()
    map.touchZoom.enable()
    map.doubleClickZoom.enable()
    
    isMapActive.value = true
  }

  // Enable interactions on click and hide hint
  map.on('click', () => {
    if (map && !isMapActive.value) {
      enableMapInteractions()
    }
  })
  
  // Disable scroll wheel zoom when mouse leaves the map (desktop only)
  if (mapContainer.value && !isMobile.value) {
    mapContainer.value.addEventListener('mouseleave', () => {
      if (map && map.scrollWheelZoom.enabled()) {
        map.scrollWheelZoom.disable()
        isMapActive.value = false
      }
    })
  }
  
  if (window.ResizeObserver && mapContainer.value) {
    resizeObserver = new ResizeObserver(() => {
      if (map) {
        isProgrammaticMove.value = true
        map.invalidateSize()
      }
    })
    resizeObserver.observe(mapContainer.value)
  }

  // Guard against phantom Leaflet 'moveend' bugs!
  let mapUserInteractedTime = 0
  const flagUserInteraction = () => { mapUserInteractedTime = Date.now() }
  
  mapContainer.value.addEventListener('mousedown', flagUserInteraction)
  mapContainer.value.addEventListener('touchstart', flagUserInteraction, { passive: true })
  mapContainer.value.addEventListener('wheel', flagUserInteraction, { passive: true })
  mapContainer.value.addEventListener('keydown', flagUserInteraction)

  map.on('moveend', () => {
    if (!map) return
    
    // Skip if it was a programmatic move (initial zoom, city zoom etc)
    if (isProgrammaticMove.value) {
      setTimeout(() => { isProgrammaticMove.value = false }, 200)
      return
    }

    // Absolute guard: If the container wasn't physically interacted with in the last 2.5 seconds,
    // this moveend is heavily delayed from an animation or Leaflet internal bug. Cancel it.
    if (Date.now() - mapUserInteractedTime > 2500) {
      return
    }

    const bounds = map.getBounds()
    
    // Update map bounds to filter results, but keep text filter values intact
    const updates: any = {
      mapBounds: {
        northEast: { lat: bounds.getNorthEast().lat, lng: bounds.getNorthEast().lng },
        southWest: { lat: bounds.getSouthWest().lat, lng: bounds.getSouthWest().lng }
      }
    }
    
    searchStore.applyFilters(updates)
  })
}

const syncMapToFilters = () => {
  if (!map) return
  
  if (props.selectedLocationCoords) {
    // Priority 1: If exact coordinates are provided, zoom to them
    const zoomLevel = isMobile.value ? 11 : 13
    isProgrammaticMove.value = true
    map.setView([props.selectedLocationCoords.lat, props.selectedLocationCoords.lng], zoomLevel)
  } else if (props.selectedCity && markers.size > 0) {
    // Priority 2: If city is selected, fit bounds to markers (likely clustered in that city)
    const group = new L.FeatureGroup(Array.from(markers.values()))
    const maxZoom = isMobile.value ? 10 : 12
    isProgrammaticMove.value = true
    map.fitBounds(group.getBounds(), { padding: [50, 50], maxZoom })
  } else if (props.selectedRegion && regionCoordinates[props.selectedRegion]) {
    // Priority 3: If region is selected (and no city), zoom to region center
    const region = regionCoordinates[props.selectedRegion]
    const zoomLevel = isMobile.value ? region.zoom - 2 : region.zoom
    isProgrammaticMove.value = true
    map.setView([region.lat, region.lng], zoomLevel)
  } else if (!searchStore.filters.mapBounds) {
    // Default: Always show full Poland ONLY when no mapBounds are set yet
    const defaultZoom = isMobile.value ? 4 : 6
    isProgrammaticMove.value = true
    map.setView([52.0, 19.0], defaultZoom)
  }
}

const updateMarkers = () => {
  if (!map) return

  // Usuń markery, których nie ma już w danych
  markers.forEach((marker, id) => {
    if (!props.listings.find(ad => ad.id === id)) {
      map?.removeLayer(marker)
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
                ${searchStore.getTypeLabel(ad.type)}
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
        maxHeight: 250,
        autoPan: true,    // Automatyczne przesunięcie mapy, aby popup był widoczny
        autoPanPadding: [10, 10]  // Zmniejszony padding przy autopan, żeby omijać bugi na małych ekranach
      })
      
      // On mobile, activate map interactions if inactive
      marker.on('click', () => {
        if (isMobile.value && !isMapActive.value) {
          // Activate map interactions on first marker click
          if (map) {
            map.scrollWheelZoom.enable()
            map.dragging.enable()
            map.touchZoom.enable()
            map.doubleClickZoom.enable()
            isMapActive.value = true
          }
        }
      })
      
      marker.on('mouseover', () => {
        if (!isMobile.value) {
          marker.setIcon(createCustomIcon(ad.type, true))
        }
      })

      marker.on('mouseout', () => {
        if (!isMobile.value && props.hoveredAdId !== ad.id) {
          marker.setIcon(createCustomIcon(ad.type, false))
        }
      })
      
      marker.addTo(map!)
      markers.set(ad.id, marker)
    }
  })
}

watch(() => props.listings, () => {
  updateMarkers()
}, { deep: true })

watch(() => props.selectedRegion, () => {
  // Only sync map if mapBounds is not active (user hasn't scrolled/zoomed manually)
  if (!searchStore.filters.mapBounds) {
    syncMapToFilters()
  }
  updateMarkers()
})

watch(() => props.selectedCity, () => {
  // Only sync map if mapBounds is not active (user hasn't scrolled/zoomed manually)
  if (!searchStore.filters.mapBounds) {
    syncMapToFilters()
  }
  updateMarkers()
})

watch(() => props.selectedLocationCoords, () => {
  // Only sync map if mapBounds is not active (user hasn't scrolled/zoomed manually)
  if (!searchStore.filters.mapBounds) {
    syncMapToFilters()
  }
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

// Watch for mapBounds being cleared (e.g., when user clicks "Search" again)
watch(() => searchStore.filters.mapBounds, (newBounds, oldBounds) => {
  // If mapBounds was cleared (null) and we have location filters, zoom to them
  if (!newBounds && oldBounds && (props.selectedCity || props.selectedRegion || props.selectedLocationCoords)) {
    syncMapToFilters()
  }
})

// Block body scroll when legend is visible on mobile
watch(isLegendVisible, (newVal) => {
  if (typeof window !== 'undefined') {
    if (newVal && isMobile.value) {
      document.body.style.overflow = 'hidden'
    } else {
      document.body.style.overflow = ''
    }
  }
})

onMounted(() => {
  initMap()
  
  // Get map section reference
  mapSection.value = document.querySelector('#map-section') as HTMLElement
  
  // Calculate header height including padding and margins
  const calculateHeaderHeight = () => {
    const header = document.querySelector('.app-header')
    if (header) {
      const rect = header.getBoundingClientRect()
      const styles = window.getComputedStyle(header)
      const marginTop = parseFloat(styles.marginTop)
      const marginBottom = parseFloat(styles.marginBottom)
      headerHeight.value = rect.height + marginTop + marginBottom
    }
  }
  
  // Handle scroll to show/hide and position toggle button
  const handleScroll = () => {
    if (!mapSection.value) return
    
    const mapRect = mapSection.value.getBoundingClientRect()
    const mapTop = mapRect.top
    const mapBottom = mapRect.bottom
    const mapHeight = mapRect.height
    const mapQuarterPoint = mapTop + (mapHeight / 4)
    
    // Show button when map is visible and we're past 1/4 of the map
    const isMapVisible = mapBottom > 0 && mapTop < window.innerHeight
    const isPastQuarter = mapQuarterPoint < window.innerHeight / 2
    
    if (isMobile.value) {
      showMobileToggle.value = isMapVisible && isPastQuarter
      
      // Position button at bottom of viewport, but clamp it to stay within map bounds
      if (showMobileToggle.value) {
        // Clamp when map bottom is closer than viewport bottom - offset
        isMobileClamped.value = mapBottom < window.innerHeight - 20
      }
    } else {
      // Desktop version
      showDesktopToggle.value = isMapVisible && isPastQuarter
      
      // Position button at bottom of viewport, but clamp it to stay within map bounds
      if (showDesktopToggle.value) {
        isDesktopClamped.value = mapBottom < window.innerHeight - 20
      }
    }
  }
  
  // Update isMobile on window resize
  const handleResize = () => {
    isMobile.value = window.innerWidth < 768
    calculateHeaderHeight()
  }
  
  calculateHeaderHeight()
  window.addEventListener('resize', handleResize)
  window.addEventListener('scroll', handleScroll)
  
  // Cleanup
  onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize)
    window.removeEventListener('scroll', handleScroll)
    if (resizeObserver) resizeObserver.disconnect()
    if (map) {
      map.off()
      map.remove()
      map = null
    }
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

    <div class="map-wrapper" :style="{ minHeight: `calc(100vh - ${headerHeight}px)` }">
      <div ref="mapContainer" class="map-container">
        <div v-if="!isMapActive" class="map-hint-overlay">
          <div class="map-hint-message">
            {{'Kliknij, aby móc przybliżyć mapę' }}
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
                {{ searchStore.getTypeLabel(type) }}
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
              {{ searchStore.getTypeLabel(type) }}
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

      <button 
        v-if="!isMobile" 
        @click="scrollToAdGrid" 
        class="desktop-list-toggle"
        :class="{ 'is-clamped': isDesktopClamped }"
        :style="{
          opacity: showDesktopToggle ? 0.9 : 0,
          visibility: showDesktopToggle ? 'visible' : 'hidden',
          pointerEvents: showDesktopToggle ? 'auto' : 'none'
        }"
      >
        <span>Pokaż listę</span>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="5" width="18" height="4" rx="1"/>
          <rect x="3" y="11" width="18" height="4" rx="1"/>
          <rect x="3" y="17" width="18" height="4" rx="1"/>
        </svg>
      </button>

      <button 
        v-if="isMobile" 
        @click="scrollToAdGrid" 
        class="mobile-list-toggle"
        :class="{ 'is-clamped': isMobileClamped }"
        :style="{
          opacity: showMobileToggle ? 0.9 : 0,
          visibility: showMobileToggle ? 'visible' : 'hidden',
          pointerEvents: showMobileToggle ? 'auto' : 'none'
        }"
      >
        <span>Pokaż listę</span>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="5" width="18" height="4" rx="1"/>
          <rect x="3" y="11" width="18" height="4" rx="1"/>
          <rect x="3" y="17" width="18" height="4" rx="1"/>
        </svg>
      </button>
    </div>
  </section>
</template>

<style scoped>
.map-section {
  padding: 4rem 0 0 0;
  background: var(--bg-secondary, linear-gradient(to bottom, #F9FAFB 0%, white 100%));
  scroll-margin-top: 120px; /* Increased to ensure header visibility */
  scroll-behavior: smooth;
  display: flex;
  flex-direction: column;
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
  color: var(--text-main, #1F2937);
  margin: 0 0 1rem 0;
}

.section-subtitle {
  font-size: 1.1rem;
  color: var(--text-muted, #6B7280);
  margin: 0;
}

.map-wrapper {
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.map-container {
  flex: 1;
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
  z-index: 900;
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
  right: 0;
  width: 280px;
  height: 100vh;
  background: white;
  box-shadow: -4px 0 20px rgba(0, 0, 0, 0.1);
  z-index: 4000;
  transition: transform 0.3s ease-in-out;
  display: flex;
  flex-direction: column;
  transform: translateX(100%);
}

.legend-side-panel.is-visible {
  transform: translateX(0);
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
  z-index: 1;
  position: relative;
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
  color: var(--text-main, #374151);
  line-height: 1.4;
}

.map-legend {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: var(--card-bg, rgba(255, 255, 255, 0.8));
  border: 1px solid var(--border-color, rgba(229, 231, 235, 0.8));
  border-radius: 12px;
  box-shadow: var(--card-shadow, 0 4px 12px rgba(0, 0, 0, 0.1));
  padding: 0.75rem 1rem;
  z-index: 1001;
  backdrop-filter: blur(4px);
  opacity: 0;
  visibility: hidden;
  transform: translateY(-8px);
  transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
  pointer-events: none;
  display: flex;
  flex-direction: column;
  max-height: calc(100% - 2rem);
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
  overflow-y: auto;
  padding-right: 0.25rem;
  flex: 1;
  min-height: 0;
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
  flex-shrink: 0;
}

/* Overlay when legend is open */
.legend-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.3);
  z-index: 3999;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s ease, visibility 0.3s ease;
}

.legend-overlay.is-visible {
  opacity: 1;
  visibility: visible;
}

/* Desktop List Toggle Button */
.desktop-list-toggle {
  position: fixed;
  bottom: 38px;
  left: 50%;
  transform: translateX(-50%);
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 24px;
  padding: 10px 20px;
  font-size: 15px;
  font-weight: 500;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: opacity 0.2s ease, visibility 0.2s ease, transform 0.2s ease;
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  opacity: 0.9;
  visibility: hidden;
  white-space: nowrap;
  z-index: 100;
  pointer-events: none;
}

.desktop-list-toggle.is-clamped {
  position: absolute;
  bottom: 18px;
}

.desktop-list-toggle:hover {
  background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
  transform: translateX(-50%) translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
  opacity: 1;
}

.desktop-list-toggle:active {
  transform: translateX(-50%) translateY(0);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* Responsive adjustments */
@media (max-width: 480px) {
  .legend-side-panel {
    width: 90%;
    max-width: 320px;
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
  
  .legend-header {
    padding: 1rem 0.75rem;
  }
  
  .close-legend {
    padding: 0.5rem;
    flex-shrink: 0;
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
    padding: 4rem 0 0 0;
  }

  .section-title {
    font-size: 2rem;
  }

  .section-subtitle {
    font-size: 1rem;
  }

  .map-container {
    flex: 1;
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

  .mobile-list-toggle {
    position: fixed;
    bottom: 38px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 24px;
    padding: 10px 20px;
    font-size: 15px;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: opacity 0.2s ease, visibility 0.2s ease, transform 0.2s ease;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    opacity: 0.9;
    visibility: hidden;
    white-space: nowrap;
    z-index: 100;
    pointer-events: none;
  }

  .mobile-list-toggle.is-clamped {
    position: absolute;
    bottom: 18px;
  }

  .mobile-list-toggle:hover {
    background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
    transform: translateX(-50%) translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    opacity: 1;
  }

  .mobile-list-toggle:active {
    transform: translateX(-50%) translateY(0);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  }
}
</style>
