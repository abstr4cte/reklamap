<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import type { Advertisement } from '../types'
import { slugify } from '../utils/slugify'

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

const props = defineProps<{
  advertisements: Advertisement[]
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

const typeColors: Record<string, string> = {
  billboard: '#EF4444',
  citylight: '#F59E0B',
  led_screen: '#10B981',
  digital: '#3B82F6',
  banner: '#8B5CF6',
  poster: '#EC4899'
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

  map = L.map(mapContainer.value).setView([52.0, 19.0], 6)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
  }).addTo(map)

  updateMarkers()
}

const updateMarkers = () => {
  if (!map) return

  // Usuń markery, których nie ma już w danych
  markers.forEach((marker, id) => {
    if (!props.advertisements.find(ad => ad.id === id)) {
      marker.remove()
      markers.delete(id)
    }
  })

  // Dodaj lub aktualizuj markery
  props.advertisements.forEach((ad) => {
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
      billboard: 'Billboard',
      citylight: 'Citylight',
      led_screen: 'Ekran LED',
      digital: 'Digital',
      banner: 'Banner',
      poster: 'Plakat'
    }

    const mapTypeToUrlFormat = (type: string): string => {
      const typeMapping: Record<string, string> = {
        'billboard': 'billboard',
        'citylight': 'citylight',
        'led_screen': 'ekran-led',
        'digital': 'digital',
        'banner': 'baner',
        'poster': 'plakat',
        'wall': 'sciana',
        'other': 'inne'
      }
      return typeMapping[type] || 'inne'
    }
    
    const citySlug = slugify(ad.city)
    const titleSlug = slugify(ad.title)
    const typeSlug = mapTypeToUrlFormat(ad.type)
    const adUrl = `/powierzchnia-reklamowa/${typeSlug}/${citySlug}/${titleSlug}-${ad.id}`

    const popupContent = `
      <div style="min-width: 200px;">
        <a href="${adUrl}" style="text-decoration: none; color: inherit; display: block;">
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
              ${ad.price.toLocaleString('pl-PL')} zł/mies.
            </div>
          </div>
        </a>
      </div>
    `

      marker.bindPopup(popupContent)
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

watch(() => props.advertisements, () => {
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
  props.advertisements.forEach((ad) => {
    if (markers.has(ad.id)) {
      const marker = markers.get(ad.id)!
      const isHovered = newId === ad.id
      marker.setIcon(createCustomIcon(ad.type, isHovered))
    }
  })
})

onMounted(() => {
  initMap()
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
      <div ref="mapContainer" class="map-container"></div>

      <div class="map-legend">
        <h3 class="legend-title">Legenda</h3>
        <div class="legend-items">
          <div v-for="(color, type) in typeColors" :key="type" class="legend-item">
            <div class="legend-marker" :style="{ background: color }"></div>
            <span class="legend-label">
              {{ type === 'billboard' ? 'Billboard' :
                  type === 'citylight' ? 'Citylight' :
                  type === 'led_screen' ? 'Ekran LED' :
                  type === 'digital' ? 'Digital' :
                  type === 'banner' ? 'Banner' : 'Plakat' }}
            </span>
          </div>
        </div>
      </div>
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
}

.map-legend {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: white;
  padding: 1rem 1.25rem;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1000;
}

.legend-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1F2937;
  margin: 0 0 0.75rem 0;
}

.legend-items {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.legend-marker {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  border: 2px solid white;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.legend-label {
  font-size: 0.85rem;
  color: #4B5563;
  font-weight: 500;
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
