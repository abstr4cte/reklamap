<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import polishLocations from '../data/polishLocations.json'
import { debouncedSearchLocations, type LocationResult } from '../services/locationService'

interface Filters {
  keyword: string
  type: string
  region: string
  city: string
  priceFrom: number | null
  priceTo: number | null
  priceUnit: string
  rentalPeriod: string
  orientation: string
  widthFrom: number | null
  widthTo: number | null
  heightFrom: number | null
  heightTo: number | null
  surfaceFrom: number | null
  surfaceTo: number | null
  trafficIntensity: string
  trafficDirection: string
  trafficType: string
  status: string[]
  environment: string
  onlyWithImage: boolean
  priceIncludesPrint: boolean
  priceIncludesMounting: boolean
  graphicDesignHelp: boolean
  offerType: string
  hasVatInvoice: boolean
  hasBacklight: boolean
  selectedLocationCoords?: { lat: number; lng: number } | null
  // Type-specific filters
  variant: string
  roadClass: string
  spotDurationFrom: number | null
  spotDurationTo: number | null
  loopDurationFrom: number | null
  loopDurationTo: number | null
  transportScope: string
  vehicleCountFrom: number | null
  vehicleCountTo: number | null
  mobileExposureMode: string
  campaignDurationFrom: number | null
  campaignDurationTo: number | null
}

const emit = defineEmits<{
  search: [filters: Filters]
  reset: [filters: Filters]
}>()

const showAdvanced = ref(false)

const filters = ref<Filters>({
  keyword: '',
  type: '',
  region: '',
  city: '',
  priceFrom: null,
  priceTo: null,
  priceUnit: 'month',
  rentalPeriod: '',
  orientation: '',
  widthFrom: null,
  widthTo: null,
  heightFrom: null,
  heightTo: null,
  surfaceFrom: null,
  surfaceTo: null,
  trafficIntensity: '',
  trafficDirection: '',
  trafficType: '',
  status: [],
  environment: '',
  onlyWithImage: false,
  priceIncludesPrint: false,
  priceIncludesMounting: false,
  graphicDesignHelp: false,
  offerType: '',
  hasVatInvoice: false,
  hasBacklight: false,
  // Type-specific filters
  variant: '',
  roadClass: '',
  spotDurationFrom: null,
  spotDurationTo: null,
  loopDurationFrom: null,
  loopDurationTo: null,
  transportScope: '',
  vehicleCountFrom: null,
  vehicleCountTo: null,
  mobileExposureMode: '',
  campaignDurationFrom: null,
  campaignDurationTo: null,
})

const adTypes = [
  { value: '', label: 'Wszystkie typy' },
  { value: 'billboard', label: 'Billboardy' },
  { value: 'citylight', label: 'Citylighty' },
  { value: 'led_screen', label: 'Ekrany LED' },
  { value: 'banner', label: 'Banery' },
  { value: 'wall', label: 'Ściany reklamowe' },
  { value: 'totem', label: 'Totemy reklamowe' },
  { value: 'transport', label: 'Reklama w transporcie' },
  { value: 'mobile', label: 'Reklama mobilna' },
  { value: 'other', label: 'Inne' }
]

const regions = [
  { value: '', label: 'Wszystkie' },
  ...polishLocations.voivodeships.map(v => ({
    value: v.id,
    label: v.name
  }))
]

const locationQuery = ref('')
const isLocationMenuOpen = ref(false)
const apiLocationResults = ref<LocationResult[]>([])
const isLoadingLocations = ref(false)

const popularLocations: LocationSuggestion[] = [
  { type: 'city', value: 'Warszawa', label: 'Warszawa' },
  { type: 'city', value: 'Kraków', label: 'Kraków' },
  { type: 'city', value: 'Wrocław', label: 'Wrocław' },
  { type: 'city', value: 'Poznań', label: 'Poznań' },
  { type: 'city', value: 'Gdańsk', label: 'Gdańsk' },
]

interface LocationSuggestion {
  type: 'region' | 'city'
  value: string
  label: string
  subtitle?: string
  coords?: { lat: number; lng: number }
  addresstype?: string
  osmType?: string
  osmClass?: string
}

const locationSuggestions = computed(() => {
  if (!locationQuery.value) {
    return popularLocations
  }

  const query = locationQuery.value.toLowerCase()
  const suggestions: LocationSuggestion[] = []

  // Filter regions from JSON (instant)
  const matchingRegions = regions
    .filter(r => r.value && r.label.toLowerCase().includes(query))
    .map(r => ({ type: 'region' as const, value: r.value, label: r.label }))

  // Add API results (cities, towns, villages)
  const apiSuggestions = apiLocationResults.value
    .map(loc => {
    // Use state from Nominatim address
    const voivodeship = loc.state || ''
    
    // Extract detailed location from displayName
    // displayName format: "Jelitkowo, Gdańsk, Pomorskie, Polska"
    const parts = loc.displayName.split(', ')
    let detailedLocation = ''
    
    if (parts.length >= 2) {
      // If first part is different from city name, it's a district/suburb
      if (parts[0] !== loc.name && parts[1] === loc.name) {
        detailedLocation = `${parts[0]}, ${loc.name}`
      } else {
        detailedLocation = loc.name
      }
    } else {
      detailedLocation = loc.name
    }
    
    // Construct subtitle with city if available and different from name
    let subtitleParts: string[] = []
    
    // Add city to subtitle if it exists, is different from the main name, 
    // and isn't already part of the detailed location label
    if (loc.city && loc.city !== loc.name && !detailedLocation.includes(loc.city)) {
      subtitleParts.push(loc.city)
    }
    
    if (voivodeship) {
      subtitleParts.push(voivodeship)
    }
    
    subtitleParts.push('Polska')
    
    return {
      type: 'city' as const,
      value: loc.name,
      label: detailedLocation,
      subtitle: subtitleParts.join(', '),
      coords: { lat: loc.lat, lng: loc.lng },
      addresstype: loc.addresstype,
      osmType: loc.osmType,
      osmClass: loc.osmClass
    }
  })

  // Deduplicate by city + state, preferring place/city over boundary
  const uniqueCities = new Map<string, LocationSuggestion>()
  apiSuggestions.forEach(suggestion => {
    // Create key with city name and voivodeship to show cities from different voivodeships
    const cityKey = `${suggestion.value}|${suggestion.subtitle.split(', ').slice(-2)[0] || ''}`
    const existing = uniqueCities.get(cityKey)
    if (!existing) {
      uniqueCities.set(cityKey, suggestion)
    } else {
      // Calculate priority for current and existing
      // Priority: place/city > place/town > addresstype=city > others
      const getPriority = (s: LocationSuggestion) => {
        if (s.osmClass === 'place' && s.osmType === 'city') return 4
        if (s.osmClass === 'place' && s.osmType === 'town') return 3
        if (s.addresstype === 'city') return 2
        if (s.type === 'city') return 1
        return 0
      }
      
      const currentPriority = getPriority(suggestion)
      const existingPriority = getPriority(existing)
      
      if (currentPriority > existingPriority) {
        uniqueCities.set(cityKey, suggestion)
      }
    }
  })
  const deduplicatedSuggestions = Array.from(uniqueCities.values())

  suggestions.push(...matchingRegions, ...deduplicatedSuggestions)
  return suggestions.slice(0, 10)
})

const selectLocation = (suggestion: LocationSuggestion) => {
  locationQuery.value = suggestion.label
  
  if (suggestion.type === 'region') {
    // Find the matching region ID from polishLocations
    const matchingRegion = polishLocations.voivodeships.find(
      v => v.name === suggestion.label
    )
    filters.value.region = matchingRegion?.id || suggestion.value
    filters.value.city = ''
    filters.value.selectedLocationCoords = null
  } else {
    filters.value.city = suggestion.value
    filters.value.region = ''
    // Store coordinates if available from API
    filters.value.selectedLocationCoords = suggestion.coords || null
  }
  
  isLocationMenuOpen.value = false
}

const handleLocationFocus = () => {
  isLocationMenuOpen.value = true
}

const handleLocationBlur = () => {
  window.setTimeout(() => {
    isLocationMenuOpen.value = false
  }, 200)
}

const handleLocationInput = () => {
  // Trigger API search when user types
  if (locationQuery.value.length >= 2) {
    isLoadingLocations.value = true
    debouncedSearchLocations(locationQuery.value, (results) => {
      apiLocationResults.value = results
      isLoadingLocations.value = false
    })
  } else {
    apiLocationResults.value = []
  }
  
  // If user types custom text without selecting, treat as city search
  filters.value.city = locationQuery.value
  filters.value.region = ''
  filters.value.selectedLocationCoords = null
}

const clearLocation = () => {
  locationQuery.value = ''
  filters.value.city = ''
  filters.value.region = ''
  filters.value.selectedLocationCoords = null
  apiLocationResults.value = []
}

const goToPolandMap = () => {
  const mapContainer = document.querySelector('[data-poland-map] .map-container')
  const header = document.querySelector('.app-header')
  
  if (mapContainer && header) {
    const headerRect = header.getBoundingClientRect()
    const headerStyles = window.getComputedStyle(header)
    const headerHeight = headerRect.height + parseFloat(headerStyles.marginTop) + parseFloat(headerStyles.marginBottom)
    
    const elementPosition = mapContainer.getBoundingClientRect().top + window.pageYOffset
    const offsetPosition = elementPosition - headerHeight
    
    window.scrollTo({
      top: offsetPosition,
      behavior: 'smooth'
    })
  }
}

const handleSearch = () => {
  // First emit the search event
  emit('search', { ...filters.value })
  
  // Then scroll to map using goToPolandMap function
  goToPolandMap()
}

const resetFilters = () => {
  filters.value = {
    keyword: '',
    type: '',
    region: '',
    city: '',
    priceFrom: null,
    priceTo: null,
    priceUnit: 'month',
    rentalPeriod: '',
    orientation: '',
    widthFrom: null,
    widthTo: null,
    heightFrom: null,
    heightTo: null,
    surfaceFrom: null,
    surfaceTo: null,
    trafficIntensity: '',
    trafficDirection: '',
    trafficType: '',
    status: [],
    environment: '',
    onlyWithImage: false,
    priceIncludesPrint: false,
    priceIncludesMounting: false,
    graphicDesignHelp: false,
    offerType: '',
    hasVatInvoice: false,
    hasBacklight: false,
    selectedLocationCoords: null,
    // Type-specific filters
    variant: '',
    roadClass: '',
    spotDurationFrom: null,
    spotDurationTo: null,
    loopDurationFrom: null,
    loopDurationTo: null,
    transportScope: '',
    vehicleCountFrom: null,
    vehicleCountTo: null,
    mobileExposureMode: '',
    campaignDurationFrom: null,
    campaignDurationTo: null,
  }
  locationQuery.value = ''
  apiLocationResults.value = []
  emit('reset', { ...filters.value })
}

const isStatusMenuOpen = ref(false)
const statusMultiselect = ref<HTMLElement | null>(null)

// Computed properties for filter visibility based on selected ad type
const showPrintFilter = computed(() => {
  const type = filters.value.type
  return ['billboard', 'banner'].includes(type)
})

const showMountingFilter = computed(() => {
  const type = filters.value.type
  return ['billboard', 'banner', 'wall'].includes(type)
})

const showGraphicDesignFilter = computed(() => {
  const type = filters.value.type
  return ['billboard', 'banner', 'wall'].includes(type)
})

const showTrafficIntensityFilter = computed(() => {
  const type = filters.value.type
  return ['billboard', 'banner', 'wall'].includes(type)
})

const showDimensionsFilter = computed(() => {
  const type = filters.value.type
  return ['billboard', 'citylight', 'banner', 'wall'].includes(type)
})

// Type-specific filter visibility
const variantOptions = computed(() => {
  const type = filters.value.type
  switch (type) {
    case 'billboard':
      return [
        { value: 'standard', label: 'Standardowy' },
        { value: 'three_sided', label: 'Trójstronny' },
        { value: 'backlit', label: 'Backlit' }
      ]
    case 'citylight':
      return [
        { value: 'single', label: 'Pojedynczy' },
        { value: 'double', label: 'Podwójny' }
      ]
    case 'led_screen':
      return [
        { value: 'standard', label: 'Standardowy' },
        { value: 'interactive', label: 'Interaktywny' }
      ]
    case 'banner':
      return [
        { value: 'pvc', label: 'PCV' },
        { value: 'mesh', label: 'Mesh' },
        { value: 'textile', label: 'Tekstylny' }
      ]
    case 'totem':
      return [
        { value: 'single', label: 'Jednostronny' },
        { value: 'double', label: 'Dwustronny' },
        { value: 'multi', label: 'Wielostronny' }
      ]
    case 'transport':
      return [
        { value: 'bus', label: 'Autobus' },
        { value: 'tram', label: 'Tramwaj' },
        { value: 'metro', label: 'Metro' },
        { value: 'stop', label: 'Przystanek' }
      ]
    case 'mobile':
      return [
        { value: 'trailer', label: 'Przyczepka' },
        { value: 'car', label: 'Samochód' },
        { value: 'other', label: 'Inna' }
      ]
    default:
      return []
  }
})

const showRoadClassFilter = computed(() => {
  return filters.value.type === 'billboard'
})

const showEnvironmentFilter = computed(() => {
  const type = filters.value.type
  return ['citylight', 'led_screen', 'totem', 'mobile', 'other'].includes(type)
})

const environmentOptions = computed(() => {
  const type = filters.value.type
  switch (type) {
    case 'citylight':
      return [
        { value: 'indoor', label: 'Wewnątrz' },
        { value: 'outdoor', label: 'Na zewnątrz' }
      ]
    case 'led_screen':
      return [
        { value: 'indoor', label: 'Wewnątrz' },
        { value: 'outdoor', label: 'Na zewnątrz' },
        { value: 'event', label: 'Event / Wydarzenie' }
      ]
    case 'totem':
      return [
        { value: 'indoor', label: 'Wewnątrz' },
        { value: 'outdoor', label: 'Na zewnątrz' },
        { value: 'event', label: 'Event / Wydarzenie' }
      ]
    case 'mobile':
      return [
        { value: 'indoor', label: 'Wewnątrz' },
        { value: 'outdoor', label: 'Na zewnątrz' },
        { value: 'event', label: 'Event / Wydarzenie' }
      ]
    case 'other':
      return [
        { value: 'indoor', label: 'Wewnątrz' },
        { value: 'outdoor', label: 'Na zewnątrz' },
        { value: 'event', label: 'Event / Wydarzenie' }
      ]
    default:
      return []
  }
})

const showLEDFilters = computed(() => {
  return filters.value.type === 'led_screen'
})

const showTransportFilters = computed(() => {
  return filters.value.type === 'transport'
})

const showMobileFilters = computed(() => {
  return filters.value.type === 'mobile'
})


const availablePriceUnits = computed(() => {
  const type = filters.value.type
  if (!type) {
    return [
      { value: 'day', label: 'za dzień' },
      { value: 'week', label: 'za tydzień' },
      { value: 'month', label: 'za miesiąc' },
      { value: 'year', label: 'za rok' },
      { value: 'sqm', label: 'za m²' }
    ]
  }
  
  if (type === 'citylight') {
    return [
      { value: 'month', label: 'za miesiąc' },
      { value: 'sqm', label: 'za m²' }
    ]
  } else if (type === 'billboard') {
    return [
      { value: 'day', label: 'za dzień' },
      { value: 'week', label: 'za tydzień' },
      { value: 'month', label: 'za miesiąc' },
      { value: 'year', label: 'za rok' },
      { value: 'sqm', label: 'za m²' }
    ]
  } else if (type === 'wall') {
    return [
      { value: 'month', label: 'za miesiąc' },
      { value: 'year', label: 'za rok' },
      { value: 'sqm', label: 'za m²' }
    ]
  } else if (type === 'banner') {
    return [
      { value: 'day', label: 'za dzień' },
      { value: 'week', label: 'za tydzień' },
      { value: 'month', label: 'za miesiąc' },
      { value: 'sqm', label: 'za m²' }
    ]
  } else if (type === 'led_screen') {
    return [
      { value: 'day', label: 'za dzień (emisje)' },
      { value: 'month', label: 'za miesiąc (emisje)' },
      { value: 'campaign', label: 'za kampanię' }
    ]
  } else if (type === 'transport') {
    return [
      { value: 'day', label: 'za dzień' },
      { value: 'month', label: 'za miesiąc' },
      { value: 'campaign', label: 'za kampanię' }
    ]
  } else if (type === 'mobile') {
    return [
      { value: 'day', label: 'za dzień' },
      { value: 'campaign', label: 'za kampanię' }
    ]
  }
  
  return [
    { value: 'day', label: 'za dzień' },
    { value: 'month', label: 'za miesiąc' }
  ]
})

const statusLabel = computed(() => {
  if (filters.value.status.length === 0) return 'Wszystkie'
  if (filters.value.status.length === 3) return 'Wszystkie'
  
  const labels: string[] = []
  const map: Record<string, string> = { 
    active: 'Wolne', 
    reserved: 'Zarezerwowane', 
    soon: 'Wkrótce dostępne' 
  }
  
  for (const s of filters.value.status) {
    if (map[s]) labels.push(map[s])
  }
  
  if (labels.length <= 1) return labels.join(', ')
  return `Wybrano (${labels.length})`
})

const handleClickOutside = (event: MouseEvent) => {
  if (statusMultiselect.value && !statusMultiselect.value.contains(event.target as Node)) {
    isStatusMenuOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <section class="hero-section">
    <div class="hero-background">
      <div class="gradient-overlay"></div>
      <img
        src="https://images.pexels.com/photos/220365/pexels-photo-220365.jpeg?auto=compress&cs=tinysrgb&w=1920"
        alt="Advertising surfaces"
        class="hero-image"
      />
    </div>

    <div class="hero-content">
      <div class="hero-text">
        <h1 class="hero-title animate-title">Znajdź idealną powierzchnię reklamową</h1>
        <p class="hero-subtitle animate-subtitle">Tysiące ofert w całej Polsce. Sprawdź dostępność w Twojej okolicy!</p>
      </div>

      <div class="search-card animate-card">
        <form @submit.prevent="handleSearch" class="search-form">
          <div class="basic-filters">
            <div class="search-row">
              <div class="input-group">
                <label for="search-keyword" class="input-label">
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="8" cy="8" r="6" stroke="#4F46E5" stroke-width="1.5"/>
                    <path d="M12.5 12.5L16 16" stroke="#4F46E5" stroke-width="1.5" stroke-linecap="round"/>
                  </svg>
                  Nazwa / słowo kluczowe
                </label>
                <input
                  id="search-keyword"
                  v-model="filters.keyword"
                  type="text"
                  placeholder="np. billboard centrum"
                  class="search-input"
                />
              </div>

              <div class="input-group">
                <label for="search-type" class="input-label">
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="2" width="14" height="14" rx="2" stroke="#4F46E5" stroke-width="1.5"/>
                    <path d="M2 7H16M7 2V16" stroke="#4F46E5" stroke-width="1.5"/>
                  </svg>
                  Typ powierzchni
                </label>
                <select id="search-type" v-model="filters.type" class="search-select">
                  <option v-for="type in adTypes" :key="type.value" :value="type.value">
                    {{ type.label }}
                  </option>
                </select>
              </div>

              <div class="input-group location-autocomplete">
                <label for="search-location" class="input-label">
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 9C10.1046 9 11 8.10457 11 7C11 5.89543 10.1046 5 9 5C7.89543 5 7 5.89543 7 7C7 8.10457 7.89543 9 9 9Z" stroke="#4F46E5" stroke-width="1.5"/>
                    <path d="M9 16C9 16 14 11.5 14 7C14 4.23858 11.7614 2 9 2C6.23858 2 4 4.23858 4 7C4 11.5 9 16 9 16Z" stroke="#4F46E5" stroke-width="1.5"/>
                  </svg>
                  Lokalizacja
                </label>
                <div class="input-with-clear">
                  <input
                    id="search-location"
                    v-model="locationQuery"
                    type="text"
                    placeholder="Wpisz region, miasto lub ulicę"
                    class="search-input"
                    @focus="handleLocationFocus"
                    @blur="handleLocationBlur"
                    @input="handleLocationInput"
                    autocomplete="off"
                  />
                  <button 
                    v-if="locationQuery" 
                    type="button" 
                    class="clear-button" 
                    @click.stop="clearLocation"
                    @mousedown.prevent
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path d="M18 6L6 18M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                </div>
                <div v-if="isLocationMenuOpen" class="location-suggestions">
                  <div v-if="isLoadingLocations" class="loading-state">
                    <div class="loading-spinner"></div>
                    <span>Szukam...</span>
                  </div>
                  <div v-else-if="!locationQuery" class="suggestion-section">
                    <div class="suggestion-header">Popularne lokalizacje</div>
                    <div
                      v-for="suggestion in locationSuggestions"
                      :key="suggestion.value"
                      class="location-suggestion"
                      @click="selectLocation(suggestion)"
                    >
                      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 8C8.82843 8 9.5 7.32843 9.5 6.5C9.5 5.67157 8.82843 5 8 5C7.17157 5 6.5 5.67157 6.5 6.5C6.5 7.32843 7.17157 8 8 8Z" stroke="#6B7280" stroke-width="1.2"/>
                        <path d="M8 14C8 14 12 10.5 12 6.5C12 4.01472 10.2091 2 8 2C5.79086 2 4 4.01472 4 6.5C4 10.5 8 14 8 14Z" stroke="#6B7280" stroke-width="1.2"/>
                      </svg>
                      {{ suggestion.label }}
                    </div>
                  </div>
                  <div v-else>
                    <div
                      v-for="suggestion in locationSuggestions"
                      :key="suggestion.value + suggestion.type"
                      class="location-suggestion"
                      @click="selectLocation(suggestion)"
                    >
                      <svg v-if="suggestion.type === 'region'" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="2" y="2" width="12" height="12" rx="1.5" stroke="#6B7280" stroke-width="1.2"/>
                        <path d="M2 6H14M6 2V14" stroke="#6B7280" stroke-width="1.2"/>
                      </svg>
                      <svg v-else width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 8C8.82843 8 9.5 7.32843 9.5 6.5C9.5 5.67157 8.82843 5 8 5C7.17157 5 6.5 5.67157 6.5 6.5C6.5 7.32843 7.17157 8 8 8Z" stroke="#6B7280" stroke-width="1.2"/>
                        <path d="M8 14C8 14 12 10.5 12 6.5C12 4.01472 10.2091 2 8 2C5.79086 2 4 4.01472 4 6.5C4 10.5 8 14 8 14Z" stroke="#6B7280" stroke-width="1.2"/>
                      </svg>
                      <span class="suggestion-text">
                        <span class="suggestion-name">{{ suggestion.label }}</span>
                        <span v-if="suggestion.type === 'region'" class="suggestion-type">Województwo</span>
                        <span v-else-if="suggestion.subtitle" class="suggestion-type">{{ suggestion.subtitle }}</span>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="search-row">
              <div class="input-group price-group">
                <label class="input-label">Cena</label>
                <div class="price-filter">
                  <input
                    v-model.number="filters.priceFrom"
                    type="number"
                    placeholder="Od"
                    class="search-input price-input"
                  />
                  <span class="separator">-</span>
                  <input
                    v-model.number="filters.priceTo"
                    type="number"
                    placeholder="Do"
                    class="search-input price-input"
                  />
                  <select v-model="filters.priceUnit" class="search-select price-unit">
                    <option v-for="unit in availablePriceUnits" :key="unit.value" :value="unit.value">
                      {{ unit.label }}
                    </option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <button type="button" class="toggle-advanced" @click="showAdvanced = !showAdvanced">
            {{ showAdvanced ? 'Ukryj' : 'Pokaż' }} filtrowanie zaawansowane
            <span class="arrow" :class="{ expanded: showAdvanced }">▼</span>
          </button>

          <transition name="slide">
            <div v-if="showAdvanced" class="advanced-filters">
              <!-- Info message when no type selected -->
              <div v-if="!filters.type" class="info-message">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="10" stroke="#3B82F6" stroke-width="2"/>
                  <path d="M12 16v-4M12 8h.01" stroke="#3B82F6" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span>Wybierz typ powierzchni, aby zobaczyć więcej filtrów specyficznych dla danego typu</span>
              </div>

              <div v-if="showDimensionsFilter" class="filter-section">
                <h4 class="section-title">Wymiary i powierzchnia</h4>
                <div class="search-row">
                  <div class="input-group">
                    <label class="input-label">Szerokość (m)</label>
                    <div class="range-input">
                      <input
                        v-model.number="filters.widthFrom"
                        type="number"
                        placeholder="Od"
                        step="0.1"
                        class="search-input"
                      />
                      <span class="separator">-</span>
                      <input
                        v-model.number="filters.widthTo"
                        type="number"
                        placeholder="Do"
                        step="0.1"
                        class="search-input"
                      />
                    </div>
                  </div>
                  <div class="input-group">
                    <label class="input-label">Wysokość (m)</label>
                    <div class="range-input">
                      <input
                        v-model.number="filters.heightFrom"
                        type="number"
                        placeholder="Od"
                        step="0.1"
                        class="search-input"
                      />
                      <span class="separator">-</span>
                      <input
                        v-model.number="filters.heightTo"
                        type="number"
                        placeholder="Do"
                        step="0.1"
                        class="search-input"
                      />
                    </div>
                  </div>
                </div>
                <div class="search-row">
                  <div class="input-group">
                    <label for="orientation" class="input-label">Orientacja</label>
                    <select id="orientation" v-model="filters.orientation" class="search-select">
                      <option value="">Wszystkie</option>
                      <option value="vertical">Pion</option>
                      <option value="horizontal">Poziom</option>
                    </select>
                  </div>
                  <div class="input-group">
                    <label class="input-label">Powierzchnia (m²)</label>
                    <div class="range-input">
                      <input
                        v-model.number="filters.surfaceFrom"
                        type="number"
                        placeholder="Od"
                        step="0.1"
                        class="search-input"
                      />
                      <span class="separator">-</span>
                      <input
                        v-model.number="filters.surfaceTo"
                        type="number"
                        placeholder="Do"
                        step="0.1"
                        class="search-input"
                      />
                    </div>
                  </div>
                </div>
              </div>

              <!-- TYPE-SPECIFIC FILTERS SECTION -->
              <div v-if="filters.type" class="filter-section">
                <h4 class="section-title">Opcje specyficzne dla typu</h4>
                
                <!-- Variant Filter -->
                <div v-if="variantOptions.length > 0" class="search-row">
                  <div class="input-group">
                    <label class="input-label">Wariant</label>
                    <select v-model="filters.variant" class="search-select">
                      <option value="">Wszystkie</option>
                      <option v-for="variant in variantOptions" :key="variant.value" :value="variant.value">
                        {{ variant.label }}
                      </option>
                    </select>
                  </div>
                </div>

                <!-- Road Class Filter (Billboard only) -->
                <div v-if="showRoadClassFilter" class="search-row">
                  <div class="input-group">
                    <label class="input-label">Klasa drogi</label>
                    <select v-model="filters.roadClass" class="search-select">
                      <option value="">Wszystkie</option>
                      <option value="highway">Autostrada</option>
                      <option value="expressway">Droga ekspresowa</option>
                      <option value="national">Droga krajowa</option>
                      <option value="regional">Droga wojewódzka</option>
                      <option value="local">Droga lokalna</option>
                      <option value="urban">Droga miejska</option>
                    </select>
                  </div>
                </div>

                <!-- Traffic Intensity (Billboard and Banner) -->
                <div v-if="showTrafficIntensityFilter" class="search-row">
                  <div class="input-group">
                    <label class="input-label">Natężenie ruchu</label>
                    <select v-model="filters.trafficIntensity" class="search-select">
                      <option value="">Wszystkie</option>
                      <option value="low">Niskie</option>
                      <option value="medium">Średnie</option>
                      <option value="high">Wysokie</option>
                    </select>
                  </div>
                </div>

                <!-- Traffic Direction (Billboard only) -->
                <!-- Kierunek ruchu dla billboard -->
                <div v-if="filters.type === 'billboard'" class="search-row">
                  <div class="input-group">
                    <label class="input-label">Kierunek ruchu</label>
                    <select v-model="filters.trafficDirection" class="search-select">
                      <option value="">Wszystkie</option>
                      <option value="entry">Wjazd</option>
                      <option value="exit">Wyjazd</option>
                      <option value="both">Oba kierunki</option>
                    </select>
                  </div>
                </div>

                <!-- Rodzaj ruchu dla banner -->
                <div v-if="filters.type === 'banner'" class="search-row">
                  <div class="input-group">
                    <label class="input-label">Rodzaj ruchu</label>
                    <select v-model="filters.trafficType" class="search-select">
                      <option value="">Wszystkie</option>
                      <option value="pedestrian">Pieszy</option>
                      <option value="vehicular">Samochodowy</option>
                      <option value="both">Oba rodzaje</option>
                    </select>
                  </div>
                </div>

                <!-- Environment Filter -->
                <div v-if="showEnvironmentFilter" class="search-row">
                  <div class="input-group">
                    <label class="input-label">Środowisko</label>
                    <select v-model="filters.environment" class="search-select">
                      <option value="">Wszystkie</option>
                      <option v-for="env in environmentOptions" :key="env.value" :value="env.value">
                        {{ env.label }}
                      </option>
                    </select>
                  </div>
                </div>

                <!-- LED Screen Filters -->
                <div v-if="showLEDFilters" class="search-row">
                  <div class="input-group">
                    <label class="input-label">Czas spotu (sekundy)</label>
                    <div class="range-input">
                      <input
                        v-model.number="filters.spotDurationFrom"
                        type="number"
                        placeholder="Od"
                        class="search-input"
                      />
                      <span class="separator">-</span>
                      <input
                        v-model.number="filters.spotDurationTo"
                        type="number"
                        placeholder="Do"
                        class="search-input"
                      />
                    </div>
                  </div>
                  <div class="input-group">
                    <label class="input-label">Pętla emisji (sekundy)</label>
                    <div class="range-input">
                      <input
                        v-model.number="filters.loopDurationFrom"
                        type="number"
                        placeholder="Od"
                        class="search-input"
                      />
                      <span class="separator">-</span>
                      <input
                        v-model.number="filters.loopDurationTo"
                        type="number"
                        placeholder="Do"
                        class="search-input"
                      />
                    </div>
                  </div>
                </div>

                <!-- Transport Filters -->
                <div v-if="showTransportFilters" class="search-row">
                  <div class="input-group">
                    <label class="input-label">Zakres reklamy</label>
                    <select v-model="filters.transportScope" class="search-select">
                      <option value="">Wszystkie</option>
                      <option value="internal">Wewnętrzna</option>
                      <option value="external">Zewnętrzna</option>
                      <option value="full_vehicle">Całopojazdowa</option>
                    </select>
                  </div>
                  <div class="input-group">
                    <label class="input-label">Liczba pojazdów</label>
                    <div class="range-input">
                      <input
                        v-model.number="filters.vehicleCountFrom"
                        type="number"
                        placeholder="Od"
                        class="search-input"
                      />
                      <span class="separator">-</span>
                      <input
                        v-model.number="filters.vehicleCountTo"
                        type="number"
                        placeholder="Do"
                        class="search-input"
                      />
                    </div>
                  </div>
                </div>

                <!-- Mobile Filters -->
                <div v-if="showMobileFilters" class="search-row">
                  <div class="input-group">
                    <label class="input-label">Tryb ekspozycji</label>
                    <select v-model="filters.mobileExposureMode" class="search-select">
                      <option value="">Wszystkie</option>
                      <option value="moving">Jeżdżąca</option>
                      <option value="stationary">Stojąca</option>
                      <option value="mixed">Mieszana</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="filter-section">
                <h4 class="section-title">Wyposażenie i dodatki</h4>
                <div class="checkbox-grid">
                  <label class="checkbox-label search-select" style="justify-content: flex-start;">
                    <input type="checkbox" v-model="filters.onlyWithImage" />
                    <span>Tylko ze zdjęciem</span>
                  </label>
                  <label v-if="showPrintFilter" class="checkbox-label search-select" style="justify-content: flex-start;">
                    <input type="checkbox" v-model="filters.priceIncludesPrint" />
                    <span>Cena zawiera druk</span>
                  </label>
                  <label v-if="showMountingFilter" class="checkbox-label search-select" style="justify-content: flex-start;">
                    <input type="checkbox" v-model="filters.priceIncludesMounting" />
                    <span>Cena zawiera montaż</span>
                  </label>
                  <label v-if="showGraphicDesignFilter" class="checkbox-label search-select" style="justify-content: flex-start;">
                    <input type="checkbox" v-model="filters.graphicDesignHelp" />
                    <span>Pomoc przy projekcie graficznym</span>
                  </label>
                  <label v-if="['citylight', 'totem'].includes(filters.type)" class="checkbox-label search-select" style="justify-content: flex-start;">
                    <input type="checkbox" v-model="filters.hasBacklight" />
                    <span>Podświetlenie</span>
                  </label>
                </div>
              </div>

              <div class="filter-section">
                <h4 class="section-title">Dostępność</h4>
                <div class="search-row">
                  <div class="input-group">
                    <label for="rental-period" class="input-label">Czas wynajmu</label>
                    <select id="rental-period" v-model="filters.rentalPeriod" class="search-select">
                      <option value="">Wszystkie</option>
                      <option value="short_term">Krótkoterminowy (&lt;1 miesiąc)</option>
                      <option value="long_term">Długoterminowy</option>
                    </select>
                  </div>
                  <div class="input-group">
                    <label for="status" class="input-label">Status</label>
                    <div class="multiselect-wrapper" ref="statusMultiselect">
                      <div class="search-select multiselect-trigger" @click="isStatusMenuOpen = !isStatusMenuOpen">
                        <span class="selected-text">{{ statusLabel }}</span>
                        <svg class="arrow" :class="{ open: isStatusMenuOpen }" width="10" height="6" viewBox="0 0 10 6" fill="none">
                          <path d="M1 1L5 5L9 1" stroke="#6B7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </div>
                      <div v-if="isStatusMenuOpen" class="multiselect-dropdown">
                        <label class="checkbox-option">
                          <input type="checkbox" value="active" v-model="filters.status">
                          <span>Wolne</span>
                        </label>
                        <label class="checkbox-option">
                          <input type="checkbox" value="reserved" v-model="filters.status">
                          <span>Zarezerwowane</span>
                        </label>
                        <label class="checkbox-option">
                          <input type="checkbox" value="soon" v-model="filters.status">
                          <span>Wkrótce dostępne</span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="filter-section">
                <h4 class="section-title">Typ oferty i formalności</h4>
                <div class="search-row">
                  <div class="input-group">
                    <label for="offer-type" class="input-label">Rodzaj oferty</label>
                    <select id="offer-type" v-model="filters.offerType" class="search-select">
                      <option value="">Wszystkie</option>
                      <option value="owner">Właściciel</option>
                      <option value="agency">Agencja</option>
                    </select>
                  </div>
                  <div class="input-group">
                    <span class="input-label" style="visibility: hidden">Opcje</span>
                    <label class="checkbox-label search-select" style="justify-content: flex-start;">
                      <input type="checkbox" v-model="filters.hasVatInvoice" />
                      <span>Faktura VAT</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </transition>

          <div class="button-row">
            <button type="button" class="reset-button" @click="resetFilters">
              Wyczyść filtry
            </button>
            <button type="submit" class="search-button">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="9" cy="9" r="6" stroke="white" stroke-width="2"/>
                <path d="M13.5 13.5L17 17" stroke="white" stroke-width="2" stroke-linecap="round"/>
              </svg>
              Szukaj
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<style scoped>
.hero-section {
  position: relative;
  min-height: 580px;
  overflow: visible;
}

.hero-background {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 100%;
  overflow: hidden;
}

.hero-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.gradient-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.85) 0%, rgba(118, 75, 162, 0.85) 100%);
  z-index: 1;
}

.hero-content {
  position: relative;
  z-index: 2;
  max-width: 1400px;
  margin: 0 auto;
  padding: 4rem 2rem 4rem;
  min-height: 580px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.hero-text {
  text-align: center;
  margin-bottom: 3rem;
}

.hero-title {
  font-size: 3.5rem;
  font-weight: 800;
  color: white;
  margin: 0 0 1rem 0;
  line-height: 1.2;
  text-shadow: 0 2px 20px rgba(0, 0, 0, 0.2);
}

.animate-title {
  animation: fadeInDown 0.8s ease-out;
}

.hero-subtitle {
  font-size: 1.35rem;
  color: rgba(255, 255, 255, 0.95);
  margin: 0;
  font-weight: 400;
  text-shadow: 0 1px 10px rgba(0, 0, 0, 0.15);
}

.animate-subtitle {
  animation: fadeInUp 0.8s ease-out 0.2s both;
}

.search-card {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  width: 100%;
  max-width: 1100px;
  transform: translateY(60px);
  margin-bottom: 60px;
}

.animate-card {
  animation: fadeInScale 0.8s ease-out 0.4s both;
}

.search-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.basic-filters {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.search-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.input-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.input-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #4B5563;
}

.search-input,
.search-select {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid #E5E7EB;
  border-radius: 10px;
  font-size: 0.95rem;
  transition: all 0.2s ease;
  background: white;
}

.search-input:focus,
.search-select:focus {
  outline: none;
  border-color: #4F46E5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.search-select {
  cursor: pointer;
}

.price-filter,
.range-input {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.price-input {
  flex: 1;
  min-width: 0;
}

.price-unit {
  min-width: 250px;
  max-width: 250px;
  flex-shrink: 0;
}

.separator {
  color: #9CA3AF;
  font-weight: 500;
  flex-shrink: 0;
}

.price-group {
  min-width: 320px;
}

.price-input {
  min-width: 100px;
  flex: 1;
}

.city-autocomplete {
  position: relative;
}

.city-suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 2px solid #E5E7EB;
  border-radius: 10px;
  margin-top: 0.25rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 10;
  max-height: 200px;
  overflow-y: auto;
}

.city-suggestion {
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background-color 0.2s ease;
  font-size: 0.95rem;
  color: #1F2937;
}

.city-suggestion:hover {
  background-color: #F3F4F6;
}

.city-suggestion:first-child {
  border-radius: 8px 8px 0 0;
}

.city-suggestion:last-child {
  border-radius: 0 0 8px 8px;
}

.location-autocomplete {
  position: relative;
}

.input-with-clear {
  position: relative;
  width: 100%;
}

.clear-button {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: #9ca3af;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  width: 20px;
  height: 20px;
  border-radius: 50%;
}

.clear-button:hover {
  color: #4b5563;
  background-color: #f3f4f6;
}

.location-suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 2px solid #E5E7EB;
  border-radius: 10px;
  margin-top: 0.25rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 10;
  max-height: 320px;
  overflow-y: auto;
}

.suggestion-section {
  padding: 0.5rem 0;
}

.suggestion-header {
  padding: 0.5rem 1rem;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #6B7280;
  letter-spacing: 0.05em;
}

.location-suggestion {
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background-color 0.2s ease;
  font-size: 0.95rem;
  color: #1F2937;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.location-suggestion:hover {
  background-color: #F3F4F6;
}

.location-suggestion svg {
  flex-shrink: 0;
}

.suggestion-text {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
  flex: 1;
}

.suggestion-name {
  font-size: 0.95rem;
  color: #1F2937;
  font-weight: 500;
}

.suggestion-type {
  font-size: 0.75rem;
  color: #9CA3AF;
  font-weight: 500;
}

.toggle-advanced {
  padding: 0.875rem 1.5rem;
  background: #F3F4F6;
  border: none;
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 600;
  color: #4F46E5;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.toggle-advanced:hover {
  background: #E5E7EB;
}

.arrow {
  transition: transform 0.3s ease;
  font-size: 0.75rem;
}

.arrow.expanded {
  transform: rotate(180deg);
}

.advanced-filters {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  padding-top: 1rem;
}

.info-message {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  background: #EFF6FF;
  border: 1px solid #BFDBFE;
  border-radius: 8px;
  color: #1E40AF;
  font-size: 0.95rem;
  line-height: 1.5;
}

.info-message svg {
  flex-shrink: 0;
}

.info-message span {
  flex: 1;
}

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center !important;
  gap: 0.75rem;
  padding: 1rem;
  color: #6B7280;
  font-size: 0.95rem;
  flex-direction: row !important;
  width: 100%;
}

.loading-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #E5E7EB;
  border-top-color: #4F46E5;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: translateY(90px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(60px) scale(1);
  }
}

.filter-section {
  padding: 1.25rem;
  background: #F9FAFB;
  border-radius: 10px;
}

.section-title {
  font-size: 1rem;
  font-weight: 700;
  color: #1F2937;
  margin: 0 0 1rem 0;
}

.checkbox-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-size: 0.875rem;
  color: #4B5563;
  font-weight: 500;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #4F46E5;
}

.vat-checkbox {
  padding-top: 1.5rem;
}

.button-row {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.reset-button {
  padding: 0.875rem 2rem;
  background: white;
  border: 2px solid #E5E7EB;
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 600;
  color: #6B7280;
  cursor: pointer;
  transition: all 0.2s ease;
}

.reset-button:hover {
  background: #F9FAFB;
  border-color: #9CA3AF;
}

.search-button {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.875rem 2.5rem;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  white-space: nowrap;
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.search-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
}

.search-button:active {
  transform: translateY(0);
}

.slide-enter-active,
.slide-leave-active {
  transition: all 0.3s ease;
  max-height: 1000px;
  overflow: hidden;
}

.slide-enter-from,
.slide-leave-to {
  max-height: 0;
  opacity: 0;
}

@media (max-width: 1024px) {
  .hero-title {
    font-size: 2.75rem;
  }

  .hero-subtitle {
    font-size: 1.15rem;
  }

  .search-row {
    grid-template-columns: 1fr;
  }

  .button-row {
    flex-direction: column;
  }

  .reset-button,
  .search-button {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 640px) {
  .hero-section {
    min-height: auto;
  }

  .hero-content {
    min-height: auto;
    padding: 3rem 1rem 0;
  }

  .hero-title {
    font-size: 2rem;
  }

  .hero-subtitle {
    font-size: 1rem;
  }

  .search-card {
    padding: 1.5rem;
    transform: translateY(40px);
    margin-bottom: 80px;
  }

  .checkbox-grid {
    grid-template-columns: 1fr;
  }

  .price-filter {
    flex-wrap: wrap;
  }

  .price-unit {
    min-width: 100%;
    max-width: 100%;
    flex-basis: 100%;
    margin-top: 0.5rem;
  }
}

@media (max-width: 400px) {
  .price-group {
    min-width: auto;
  }

  .price-unit {
    min-width: 100%;
    max-width: 100%;
  }

  .search-card {
    padding: 1rem;
  }
}

.multiselect-wrapper {
  position: relative;
  width: 100%;
}

.multiselect-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.875rem 1rem;
  border: 2px solid #E5E7EB;
  border-radius: 10px;
  background: white;
  cursor: pointer;
  font-size: 0.95rem;
  color: #1F2937;
  transition: all 0.2s ease;
}

.multiselect-trigger:hover {
  border-color: #9CA3AF;
}

.multiselect-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 2px solid #E5E7EB;
  border-radius: 10px;
  margin-top: 0.25rem;
  padding: 0.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 20;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.checkbox-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem;
  cursor: pointer;
  border-radius: 6px;
  transition: background-color 0.2s;
}

.checkbox-option:hover {
  background-color: #F3F4F6;
}

.checkbox-option input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #4F46E5;
}
</style>
