<script setup lang="ts">
import { ref, onMounted, watch, onBeforeUnmount, onActivated, onDeactivated } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import type { MapPin } from '../types'
import { slugify } from '../utils/slugify'
import { getFullImageUrl } from '../services/api'
import WebPImage from './WebPImage.vue'
import { useSearchStore, typeColors } from '../stores/useSearchStore'
import { mapTypeToUrlFormat } from '../utils/typeMapping'
// import 'leaflet.markercluster'
// import 'leaflet.markercluster/dist/MarkerCluster.css'
// import 'leaflet.markercluster/dist/MarkerCluster.Default.css'

const searchStore = useSearchStore()
const isActive = ref(true)

// Funkcja formatująca adres i miasto, taka sama jak w AdCard
const formatLocation = (location: string, city: string) => searchStore.formatLocation(location, city)

// Funkcja zwracająca etykietę jednostki ceny
const getPriceUnitLabel = (ad: MapPin): string => searchStore.getPriceUnitLabel(ad)

const scrollToAdGrid = () => {
  const adGrid = document.querySelector('.listings-section')
  if (adGrid) {
    const elementPosition = adGrid.getBoundingClientRect().top + window.pageYOffset
    const offsetPosition = elementPosition - 80

    window.scrollTo({
      top: offsetPosition,
      behavior: 'smooth'
    })
  }
}

const scrollToMap = () => {
  const mapEl = document.querySelector('.map-container')
  const header = document.querySelector('.app-header')
  if (!mapEl) return

  const headerHeight = header
    ? header.getBoundingClientRect().height
    : 0

  const elementPosition = mapEl.getBoundingClientRect().top + window.pageYOffset
  const offsetPosition = elementPosition - headerHeight

  isScrollingToMap.value = true
  window.scrollTo({ top: offsetPosition, behavior: 'smooth' })
  setTimeout(() => { isScrollingToMap.value = false }, 700)
}

const props = defineProps<{
  listings: MapPin[]
  selectedRegion?: string
  selectedCity?: string
  selectedLocationCoords?: { lat: number; lng: number } | null
  hoveredAdId?: number | null
}>()

const emit = defineEmits<{
  'update:hoveredAdId': [id: number | null]
}>()

const mapContainer = ref<HTMLElement | null>(null)
let map: L.Map | null = null
let resizeObserver: ResizeObserver | null = null
// let markerClusterGroup: any = null
const markers: Map<number, L.Marker> = new Map()
const isMapActive = ref(false)
const isScrollingToMap = ref(false)
const isLegendVisible = ref(false)
const selectedAd = ref<MapPin | null>(null)
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

const createCustomIcon = (type: string) => {
  const color = typeColors[type] || '#6B7280'
  return L.divIcon({
    className: 'custom-marker',
    html: `
      <div style="
        background: ${color};
        width: 32px;
        height: 32px;
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        border: 3px solid white;
        box-shadow: 0 3px 10px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
      ">
        <div style="
          width: 12px;
          height: 12px;
          background: white;
          border-radius: 50%;
          transform: rotate(45deg);
        "></div>
      </div>
    `,
    iconSize: [32, 32],
    iconAnchor: [16, 32],
    popupAnchor: [0, -32]
  })
}

const enableMapInteractions = () => {
  if (!map) return
  
  map.scrollWheelZoom.enable()
  map.dragging.enable()
  map.touchZoom.enable()
  map.doubleClickZoom.enable()
  
  isMapActive.value = true
}

const disableMapInteractions = () => {
  if (!map) return
  
  map.scrollWheelZoom.disable()
  if (isMobile.value) {
    map.dragging.disable()
    map.touchZoom.disable()
    map.doubleClickZoom.disable()
  }
  
  isMapActive.value = false
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
    zoomControl: false,
    maxBounds: polandBounds,
    maxBoundsViscosity: 1.0,
    minZoom: 5,
    maxZoom: 18
  }).setView([52.0, 19.0], isMobile.value ? 5 : 6)

  // Dodaj kontrolkę zoomu w odpowiednim miejscu
  L.control.zoom({
    position: 'topleft'
  }).addTo(map)

  // Delegowany click handler dla markerów — jeden listener na kontenerze zamiast per-marker.
  // marker.getElement() zwraca aktualny element nawet po setIcon() (hover scale),
  // dlatego ta metoda jest odporna na wymianę DOM elementu przez Leaflet.
  mapContainer.value.addEventListener('click', (e: MouseEvent) => {
    const target = e.target as HTMLElement
    for (const [id, marker] of markers) {
      if (marker.getElement()?.contains(target)) {
        const ad = props.listings.find(a => a.id === id)
        if (ad) {
          marker.getElement()?.classList.remove('hovered')
          if (!isMapActive.value) {
            enableMapInteractions()
            scrollToMap()
          }
          selectedAd.value = ad
        }
        return
      }
    }
  })

  // Zamknij wybraną reklamę przy kliknięciu w tło mapy (nie pinezkę)
  map.on('click', (e: any) => {
    if (!isMapActive.value) {
      enableMapInteractions()
      scrollToMap()
    }
    const target = e.originalEvent?.target as HTMLElement | null
    if (!target?.closest('.custom-marker')) {
      selectedAd.value = null
    }
  })

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

  // (logika aktywacji mapy jest teraz w głównym handlerze click powyżej)
  
  // Disable interactions when mouse leaves the map (desktop only)
  if (mapContainer.value && !isMobile.value) {
    mapContainer.value.addEventListener('mouseleave', () => {
      if (isMapActive.value) {
        disableMapInteractions()
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

    // Skip if component is deactivated (keep-alive) — delayed Leaflet events can still fire
    // after navigation and would overwrite mapBounds already cleared by the new page.
    if (!isActive.value) return

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
    
    // Jeśli marker już istnieje, aktualizuj tylko kolor (typ)
    if (markers.has(ad.id)) {
      const marker = markers.get(ad.id)!
      marker.setIcon(createCustomIcon(ad.type))
      marker.getElement()?.classList.toggle('hovered', isHovered)
    } else {
      // Utwórz nowy marker
      const marker = L.marker([ad.latitude, ad.longitude], {
        icon: createCustomIcon(ad.type)
      })

    const citySlug = slugify(ad.city)
    const titleSlug = slugify(ad.title)
    const typeSlug = mapTypeToUrlFormat(ad.type)
    const adUrl = `/powierzchnia-reklamowa/${typeSlug}/${citySlug}/${titleSlug}-${ad.id}`

    const imageUrl = ad.image_url ? getFullImageUrl(ad.image_url) : ''
    
    const webpImageUrl = imageUrl ? imageUrl.replace(/\.(jpg|jpeg)$/i, '.webp') : null
    const popupContent = `
      <div style="width: 250px;">
        <a href="${adUrl}" style="text-decoration: none; color: inherit; display: block;">
          ${webpImageUrl ? `
            <div style="margin: -20px -20px 12px -20px; overflow: hidden; border-radius: 12px 12px 0 0;">
              <img src="${webpImageUrl}" alt="${ad.title}" style="width: 100%; height: ${isMobile.value ? '100px' : '140px'}; object-fit: cover; display: block;" />
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

      marker.on('mouseover', () => {
        if (!isMobile.value) marker.getElement()?.classList.add('hovered')
      })
      marker.on('mouseout', () => {
        if (!isMobile.value) marker.getElement()?.classList.remove('hovered')
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
  markers.forEach((marker, id) => {
    marker.getElement()?.classList.toggle('hovered', id === newId)
  })
})

// Watch for mapBounds being cleared (e.g., when user clicks "Search" again) — reset map view
watch(() => searchStore.filters.mapBounds, (newBounds, oldBounds) => {
  if (!newBounds && oldBounds) {
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
  selectedAd.value = null
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
  
  // Handle scroll to show/hide, position toggle button, and lock map on mobile
  const handleScroll = () => {
    // If user is scrolling the page, deactivate map interactions on mobile
    // (ale nie podczas programatycznego scrolla do mapy — scrollToMap ustawia flagę)
    if (isMobile.value && isMapActive.value && !isScrollingToMap.value) {
      disableMapInteractions()
    }

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
  onActivated(() => {
    isActive.value = true
    selectedAd.value = null
    // mapBounds to efemeryczny stan viewport — czyścimy przy powrocie na stronę,
    // żeby bbox ustawiony np. na ListingsPage nie filtrował ogłoszeń do 0.
    if (searchStore.filters.mapBounds) {
      searchStore.filters.mapBounds = null  // watcher (~linia 474) wywoła syncMapToFilters()
      searchStore.fetchListings()
    }
  })

  onDeactivated(() => {
    isActive.value = false
    // Anuluj timer debounce mapBounds — opóźnione moveend events mogą odpalić fetchListings
    // ze starym bbox po przejściu na inną stronę, co skutkuje 0 wyników na ListingsPage.
    searchStore.cancelMapBoundsTimer()
  })

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

    <div class="map-wrapper" :style="{ minHeight: `calc(100dvh - ${headerHeight}px)` }">
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
        :class="{ 'is-clamped': isMobileClamped || (selectedAd && isMobile) }"
        :style="{
          opacity: showMobileToggle ? 0.9 : 0,
          visibility: showMobileToggle ? 'visible' : 'hidden',
          pointerEvents: showMobileToggle ? 'auto' : 'none',
          bottom: '20px'
        }"
      >
        <span>Pokaż listę</span>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="5" width="18" height="4" rx="1"/>
          <rect x="3" y="11" width="18" height="4" rx="1"/>
          <rect x="3" y="17" width="18" height="4" rx="1"/>
        </svg>
      </button>

      <!-- Bottom Sheet Card for Mobile -->
      <transition name="slide-up">
        <div v-if="isMobile && selectedAd" class="mobile-bottom-card">
          <button class="close-card" @click="selectedAd = null">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12" />
            </svg>
          </button>
          
          <router-link 
            :to="`/powierzchnia-reklamowa/${mapTypeToUrlFormat(selectedAd.type)}/${slugify(selectedAd.city)}/${slugify(selectedAd.title)}-${selectedAd.id}`"
            class="card-content"
          >
            <div class="card-image" v-if="selectedAd.image_url">
              <WebPImage :src="getFullImageUrl(selectedAd.image_url)" :alt="selectedAd.title" />
            </div>
            <div class="card-info">
              <div class="card-badges">
                <div class="card-category" :style="{ background: typeColors[selectedAd.type] }">
                  {{ searchStore.getTypeLabel(selectedAd.type) }}
                </div>
                <div class="card-status" :style="{ background: searchStore.getStatusColor(selectedAd) }">
                  {{ searchStore.getStatusLabel(selectedAd) }}
                </div>
              </div>
              <h3 class="card-title">{{ selectedAd.title }}</h3>
              <div class="card-details-row">
                <div class="card-location">
                  <svg width="12" height="12" viewBox="0 0 14 14" fill="none">
                    <path d="M7 7C7.825 7 8.5 6.325 8.5 5.5C8.5 4.675 7.825 4 7 4C6.175 4 5.5 4.675 5.5 5.5C5.5 6.325 6.175 7 7 7Z" stroke="currentColor" stroke-width="1.2"/>
                    <path d="M7 12C7 12 10.5 9 10.5 5.5C10.5 3.567 8.933 2 7 2C5.067 2 3.5 3.567 3.5 5.5C3.5 9 7 12 7 12Z" stroke="currentColor" stroke-width="1.2"/>
                  </svg>
                  {{ formatLocation(selectedAd.location, selectedAd.city) }}
                </div>
                <div v-if="selectedAd.dimensions" class="card-dimensions">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                  </svg>
                  {{ selectedAd.dimensions }}
                </div>
              </div>
              <div class="card-price">
                {{ Math.round(selectedAd.price).toLocaleString('pl-PL') }} {{ getPriceUnitLabel(selectedAd) }}
              </div>
            </div>
          </router-link>
        </div>
      </transition>

      <!-- Desktop Side Panel -->
      <transition name="slide-left">
        <div v-if="!isMobile && selectedAd" class="desktop-side-panel">
          <div class="panel-header">
            <h3>Szczegóły ogłoszenia</h3>
            <button class="close-panel" @click="selectedAd = null">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="panel-content">
            <div class="panel-image" v-if="selectedAd.image_url">
              <WebPImage :src="getFullImageUrl(selectedAd.image_url)" :alt="selectedAd.title" />
            </div>
            
            <div class="panel-body">
              <div class="panel-badges">
                <div class="panel-type" :style="{ background: typeColors[selectedAd.type] }">
                  {{ searchStore.getTypeLabel(selectedAd.type) }}
                </div>
                <div class="panel-status" :style="{ background: searchStore.getStatusColor(selectedAd) }">
                  {{ searchStore.getStatusLabel(selectedAd) }}
                </div>
              </div>
              <h2 class="panel-title">{{ selectedAd.title }}</h2>
              
              <div class="panel-info-items">
                <div class="panel-info-item">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1118 0z" />
                    <circle cx="12" cy="10" r="3" />
                  </svg>
                  <span>{{ formatLocation(selectedAd.location, selectedAd.city) }}</span>
                </div>
                
                <div v-if="selectedAd.dimensions" class="panel-info-item">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" translateY="2" />
                    <line x1="2" y1="12" x2="22" y2="12" />
                  </svg>
                  <span>Wymiary: {{ selectedAd.dimensions }}</span>
                </div>
              </div>

              <div class="panel-price-box">
                <div class="price-label">Cena:</div>
                <div class="price-value">
                  {{ Math.round(selectedAd.price).toLocaleString('pl-PL') }} zł
                  <span class="price-unit-large"> / {{ getPriceUnitLabel(selectedAd).replace('za ', '') }}</span>
                </div>
              </div>

              <router-link 
                :to="`/powierzchnia-reklamowa/${mapTypeToUrlFormat(selectedAd.type)}/${slugify(selectedAd.city)}/${slugify(selectedAd.title)}-${selectedAd.id}`"
                class="view-details-btn"
              >
                Zobacz pełne ogłoszenie
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M5 12h14M12 5l7 7-7 7" />
                </svg>
              </router-link>
            </div>
          </div>
        </div>
      </transition>
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
  height: 100dvh;
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

:deep(.custom-marker.hovered) {
  scale: 1.3;
  transform-origin: 50% 100%;
  z-index: 1000 !important;
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

:deep(.leaflet-control-container) {
  z-index: 800;
}

:deep(.leaflet-popup-pane) {
  z-index: 900;
}

:deep(.leaflet-top), :deep(.leaflet-bottom) {
  z-index: 850;
}

/* Mobile Bottom Card Styles */
.mobile-bottom-card {
  position: absolute;
  bottom: 1rem;
  left: 1rem;
  right: 1rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  z-index: 2000;
  overflow: hidden;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.close-card {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.9);
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #374151;
  cursor: pointer;
  z-index: 10;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.card-content {
  display: flex;
  text-decoration: none;
  color: inherit;
  height: 120px;
}

.card-image {
  width: 120px;
  height: 120px;
  flex-shrink: 0;
}

.card-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.card-info {
  flex: 1;
  padding: 0.75rem 1rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 4px;
  min-width: 0;
}

.card-badges {
  display: flex;
  gap: 6px;
  align-items: center;
  flex-wrap: wrap;
}

.card-category {
  color: white;
  padding: 1px 8px;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
}

.card-status {
  color: white;
  padding: 1px 8px;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
}

.card-title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: #111827;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-details-row {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.card-location, .card-dimensions {
  font-size: 0.75rem;
  color: #6B7280;
  display: flex;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-dimensions svg {
  color: #9CA3AF;
}

.card-price {
  font-size: 1rem;
  font-weight: 800;
  color: #4F46E5;
}

/* Animations */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(120%);
  opacity: 0;
}

.mobile-list-toggle {
  transition: bottom 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

/* Desktop Side Panel Styles */
.desktop-side-panel {
  position: absolute;
  top: 1rem;
  right: 1rem;
  bottom: 1rem;
  width: 400px;
  background: white;
  border-radius: 20px;
  box-shadow: -10px 0 30px rgba(0, 0, 0, 0.1);
  z-index: 2000;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.panel-header {
  padding: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #F3F4F6;
}

.panel-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 800;
  color: #111827;
}

.close-panel {
  background: #F3F4F6;
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6B7280;
  cursor: pointer;
  transition: all 0.2s;
}

.close-panel:hover {
  background: #E5E7EB;
  color: #1F2937;
}

.panel-content {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.panel-image {
  width: 100%;
  height: 240px;
  overflow: hidden;
}

.panel-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.panel-body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.panel-badges {
  display: flex;
  gap: 8px;
  align-items: center;
}

.panel-type, .panel-status {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  color: white;
}

.panel-title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 800;
  color: #111827;
  line-height: 1.2;
}

.panel-info-items {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.panel-info-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: #4B5563;
  font-size: 1rem;
}

.panel-info-item svg {
  color: #9CA3AF;
  flex-shrink: 0;
}

.panel-price-box {
  background: #F9FAFB;
  padding: 1.25rem;
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.price-label {
  font-size: 0.875rem;
  color: #6B7280;
  font-weight: 600;
}

.price-value {
  font-size: 1.75rem;
  font-weight: 900;
  color: #4F46E5;
}

.price-unit-large {
  font-size: 0.9rem;
  color: #6B7280;
  margin-left: 2px;
}

.view-details-btn {
  margin-top: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.625rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  text-decoration: none;
  padding: 1rem;
  border-radius: 14px;
  font-weight: 700;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
}

.view-details-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.view-details-btn:active {
  transform: translateY(0);
}

.view-details-btn:active {
  transform: translateY(0);
}

/* Animations */
.slide-left-enter-active,
.slide-left-leave-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-left-enter-from,
.slide-left-leave-to {
  transform: translateX(110%);
  opacity: 0;
}
</style>
