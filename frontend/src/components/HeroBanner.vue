<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import polishLocations from '../data/polishLocations.json'
import { debouncedSearchLocations, type LocationResult } from '../services/locationService'
import bannerImage from '../assets/banner.jpg'
import { useSearchStore, type LocationSuggestion, popularLocations } from '../stores/useSearchStore'
import { type FilterParams, DEFAULT_FILTERS } from '../types/filters'

const scrollY = ref(0)
const handleScroll = () => {
  scrollY.value = typeof window !== 'undefined' ? window.scrollY : 0
}



const emit = defineEmits<{
  search: [filters: FilterParams & { _priceDisplayUnit?: string }]
  reset: [filters: FilterParams]
}>()

const showAdvanced = ref(false)
const searchStore = useSearchStore()
const isUserEditing = ref(false)
let editingTimeout: ReturnType<typeof setTimeout> | null = null

const filters = ref<FilterParams & { _priceDisplayUnit?: string }>({
  ...searchStore.filters,
  _priceDisplayUnit: undefined
})

const adTypes = searchStore.adTypes

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

// Funkcja do walidacji i konwersji liczb
const handleNumberInput = (value: string, allowDecimals: boolean = false): string => {
  if (value === '') return ''
  let filtered = value.replace(/[^\d.,]/g, '')
  filtered = filtered.replace(',', '.')
  if (!allowDecimals) {
    filtered = filtered.replace(/\./g, '')
  } else {
    const parts = filtered.split('.')
    if (parts.length > 2) {
      filtered = parts[0] + '.' + parts.slice(1).join('')
    }
  }
  return filtered
}

// popularLocations imported from store

const locationSuggestions = computed(() => {
  if (!locationQuery.value) {
    return popularLocations
  }

  const query = locationQuery.value.toLowerCase()
  const matchingRegions = regions
    .filter(r => r.value && r.label.toLowerCase().includes(query))
    .map(r => ({ type: 'region' as const, value: r.value, label: r.label }))

  const apiSuggestions = searchStore.processLocationSuggestions(apiLocationResults.value)
  
  return [...matchingRegions, ...apiSuggestions].slice(0, 10)
})

const selectLocation = (suggestion: LocationSuggestion) => {
  const displayLabel = searchStore.selectLocationSuggestion(suggestion, filters.value)
  locationQuery.value = displayLabel
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
    isLoadingLocations.value = false
  }
  
  // If user types custom text without selecting, treat as city search
  filters.value.city = locationQuery.value
  filters.value.locationLabel = locationQuery.value
  filters.value.region = ''
  filters.value.street = ''
  filters.value.selectedLocationCoords = null
  filters.value.cityStrict = false
}

const clearLocation = () => {
  locationQuery.value = ''
  filters.value.city = ''
  filters.value.region = ''
  filters.value.street = ''
  filters.value.locationLabel = ''
  filters.value.selectedLocationCoords = null
  filters.value.cityStrict = false
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

const LAST_SEARCH_KEY = 'reklamap_last_search'

const saveLastSearch = (searchFilters: FilterParams) => {
  try {
    localStorage.setItem(LAST_SEARCH_KEY, JSON.stringify(searchFilters))
  } catch (error) {
    console.error('Error saving search filters:', error)
  }
}

const loadLastSearch = () => {
  try {
    const saved = localStorage.getItem(LAST_SEARCH_KEY)
    if (saved) {
      const lastSearch = JSON.parse(saved)
      // Scalaj zapisane filtry z domyślnymi (na wypadek dodania nowych pól)
      filters.value = {
        ...filters.value,
        ...lastSearch
      }

      // Ustaw lokalizację do wyświetlenia w polu tekstowym
      if (lastSearch.locationLabel) {
        locationQuery.value = lastSearch.locationLabel
      } else if (lastSearch.city) {
        locationQuery.value = lastSearch.city
      } else if (lastSearch.region) {
        const region = polishLocations.voivodeships.find(v => v.id === lastSearch.region)
        if (region) {
          locationQuery.value = region.name
        }
      }
    }
  } catch (error) {
    console.error('Error loading search filters:', error)
  }
}

const handleSearch = () => {
  // Reset editing flag and clear timeout
  isUserEditing.value = false
  if (editingTimeout) {
    clearTimeout(editingTimeout)
  }
  
  // Przygotuj filtry do wysłania (bez konwersji wymiarów LED - zostaną w mm)
  const searchFilters = { ...filters.value }
  
  // DEBUG: Log dimension values for LED screens
  if (searchFilters.type === 'led_screen') {
    console.log('🔍 HeroBanner.handleSearch() - LED wymiary przed wysłaniem:', {
      widthFrom: searchFilters.widthFrom,
      widthTo: searchFilters.widthTo,
      heightFrom: searchFilters.heightFrom,
      heightTo: searchFilters.heightTo
    })
  }
  
  // Jeśli użytkownik wpisał cenę, dodaj priceUnit do filtrów
  // Aby wyniki były przełączone na tę jednostkę (jak przy sortowaniu)
  if ((searchFilters.priceFrom !== null || searchFilters.priceTo !== null) && searchFilters.priceUnit) {
    // Dodaj specjalny parametr do emitowanego eventu
    searchFilters._priceDisplayUnit = searchFilters.priceUnit
  } else {
    // Jeśli nie ma ceny, wyczyść priceUnit (nie ma sensu go wysyłać w query params)
    searchFilters.priceUnit = ''
  }
  
  // Zapisz filtry do localStorage (z wartościami w mm dla LED)
  saveLastSearch(searchFilters)
  
  // Emit search event with original dimension values (mm for LED screens)
  emit('search', searchFilters)
  
  // Then scroll to map using goToPolandMap function
  goToPolandMap()
}

const resetFilters = () => {
  filters.value = {
    ...DEFAULT_FILTERS,
    _priceDisplayUnit: undefined
  }
  locationQuery.value = ''
  apiLocationResults.value = []
  // Usuń zapisane wyszukiwanie
  try {
    localStorage.removeItem(LAST_SEARCH_KEY || 'reklamap_last_search')
  } catch (error) {
    console.error('Error clearing search filters:', error)
  }
  emit('reset', DEFAULT_FILTERS)
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

const showEquipmentSection = computed(() => {
  return showPrintFilter.value || showMountingFilter.value || showGraphicDesignFilter.value
})

const showTrafficIntensityFilter = computed(() => {
  const type = filters.value.type
  return ['billboard', 'banner', 'wall', 'totem'].includes(type)
})

const showDimensionsFilter = computed(() => {
  const type = filters.value.type
  return ['billboard', 'citylight', 'banner', 'wall', 'led_screen'].includes(type)
})

const variantOptions = computed(() => {
  const type = filters.value.type
  if (!type) return []
  const labels: any = {
    billboard: { standard: 'Jednostronny', two_sided: 'Dwustronny (back-to-back)', three_sided: 'Trójstronny (prismatron)', scrolling: 'Scrolling / Rolowany' },
    citylight: { single_sided: 'Jednostronny', double_sided: 'Dwustronny', scrolling: 'Scrolling (rotacyjny)', digital: 'Cyfrowy (DOOH)' },
    led_screen: { standard: 'Standardowy', interactive: 'Interaktywny' },
    totem: { single_sided: 'Jednostronny', double_sided: 'Dwustronny', multi_sided: 'Wielostronny / Kolumna', pylon: 'Pylon (przy drodze)', digital: 'Cyfrowy (LED)' },
    transport: { bus: 'Autobus', tram: 'Tramwaj', metro: 'Metro', train: 'Pociąg / SKM / Kolej', stop: 'Przystanek' },
    mobile: { trailer: 'Przyczepka', car: 'Samochód', other: 'Inna' }
  }
  const typeLabels = labels[type] || {}
  return Object.entries(typeLabels).map(([value, label]) => ({ value, label: label as string }))
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

const transportScopeOptions = computed(() => {
  // Dla przystanku (stop) - tylko opcje wewnętrzna i zewnętrzna
  if (filters.value.variant === 'stop') {
    return [
      { value: 'internal', label: 'Wewnętrzna' },
      { value: 'external', label: 'Zewnętrzna' }
    ]
  }
  // Dla pozostałych wariantów (bus, tram, metro) - wszystkie opcje
  return [
    { value: 'internal', label: 'Wewnętrzna' },
    { value: 'external', label: 'Zewnętrzna' },
    { value: 'full_vehicle', label: 'Całopojazdowa' }
  ]
})

const availablePriceUnits = computed(() => searchStore.getAvailablePriceUnits(filters.value.type))

const statusLabel = computed(() => {
  if (filters.value.status.length === 0 || filters.value.status.length === 3) return 'Wszystkie'
  const map: any = { active: 'Wolne', reserved: 'Zarezerwowane', soon: 'Wkrótce dostępne', soon_available: 'Wkrótce dostępne' }
  const labels = filters.value.status.map(s => map[s]).filter(Boolean)
  return labels.length === 1 ? labels[0] : `Wybrano (${labels.length})`
})

const handleClickOutside = (event: MouseEvent) => {
  if (statusMultiselect.value && !statusMultiselect.value.contains(event.target as Node)) {
    isStatusMenuOpen.value = false
  }
}

// Wyczyść filtry specyficzne dla typu gdy typ się zmieni
watch(() => filters.value.type, (newType, oldType) => {
  // Nie rób nic przy pierwszym załadowaniu lub gdy typ się nie zmienił
  if (!oldType || newType === oldType) return
  
  // Filtry ogólne które zachowujemy (nie są specyficzne dla typu)
  const generalFilters = {
    type: filters.value.type,
    keyword: filters.value.keyword,
    city: filters.value.city,
    region: filters.value.region,
    street: filters.value.street,
    locationLabel: filters.value.locationLabel,
    selectedLocationCoords: filters.value.selectedLocationCoords,
    cityStrict: filters.value.cityStrict,
    priceFrom: filters.value.priceFrom,
    priceTo: filters.value.priceTo,
    priceUnit: filters.value.priceUnit,
    status: filters.value.status,
    onlyWithImage: filters.value.onlyWithImage,
    mapBounds: filters.value.mapBounds
  }
  
  // Resetuj wszystkie filtry do wartości domyślnych
  Object.assign(filters.value, {
    ...generalFilters,
    // Wyczyść wszystkie specyficzne filtry
    widthFrom: null,
    widthTo: null,
    heightFrom: null,
    heightTo: null,
    surfaceFrom: null,
    surfaceTo: null,
    orientation: '',
    variant: '',
    trafficIntensity: '',
    trafficDirection: [],
    trafficType: [],
    roadClass: '',
    environment: '',
    transportScope: '',
    vehicleCountFrom: null,
    vehicleCountTo: null,
    mobileExposureMode: '',
    operatingHours: '',
    routeArea: '',
    estimatedDailyViewsFrom: null,
    estimatedDailyViewsTo: null,
    pixelPitchFrom: null,
    pixelPitchTo: null,
    brightnessFrom: null,
    brightnessTo: null,
    resolution: '',
    priceIncludesPrint: false,
    priceIncludesMounting: false,
    graphicDesignHelp: false,
    hasBacklight: false,
    hasLightingTypeBanner: false,
    hasLightingTypeBillboard: false,
    ambientLightControl: false,
    operatingZone: '',
    offerType: '',
    hasVatInvoice: false,
    campaignDuration: null,
    rentalPeriod: ''
  })
})

// Resetuj transportScope gdy wariant się zmieni
watch(() => filters.value.variant, () => {
  if (filters.value.type === 'transport') {
    // Jeśli zmieniliśmy wariant na 'stop' i mamy 'full_vehicle', resetuj
    if (filters.value.variant === 'stop' && filters.value.transportScope === 'full_vehicle') {
      filters.value.transportScope = ''
    }
  }
})

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  loadLastSearch()
  if (typeof window !== 'undefined') {
    window.addEventListener('scroll', handleScroll, { passive: true })
  }
})

// Mark user as editing when interacting with inputs
const markUserEditing = () => {
  isUserEditing.value = true
  if (editingTimeout) {
    clearTimeout(editingTimeout)
  }
  // Reset after 2 seconds of inactivity
  editingTimeout = setTimeout(() => {
    isUserEditing.value = false
  }, 2000)
}

// Synchronize with searchStore filters (only when user is not actively editing)
watch(() => searchStore.filters, (newStoreFilters) => {
  // Don't sync if user is actively editing the form
  if (isUserEditing.value) {
    return
  }

  // Filtry wspólne dla wszystkich typów - NIE synchronizuj ich (użytkownik je ustawił ręcznie)
  const commonFilters = [
    'type',           // Typ - kontrolowany lokalnie
    'keyword',        // Słowo kluczowe
    'city',           // Miasto
    'region',         // Region
    'street',         // Ulica
    'locationLabel',  // Etykieta lokalizacji
    'selectedLocationCoords', // Współrzędne
    'cityStrict',     // Ścisłe miasto
    'priceFrom',      // Cena od
    'priceTo',        // Cena do
    'priceUnit',      // Jednostka ceny
    'widthFrom',      // Szerokość od (mm dla LED screen)
    'widthTo',        // Szerokość do (mm dla LED screen)
    'heightFrom',     // Wysokość od (mm dla LED screen)
    'heightTo',       // Wysokość do (mm dla LED screen)
    'surfaceFrom',    // Powierzchnia od
    'surfaceTo',      // Powierzchnia do
    'status',         // Status (wolne/zarezerwowane/wkrótce)
    'onlyWithImage'   // Tylko ze zdjęciem
  ]

  // Update local filters (excluding common filters that user may have set)
  Object.keys(newStoreFilters).forEach(key => {
    const k = key as keyof FilterParams
    // Skip common filters - they should be preserved when user changes type
    if (commonFilters.includes(k)) {
      return
    }
    if (filters.value[k] !== newStoreFilters[k]) {
      // @ts-ignore
      filters.value[k] = newStoreFilters[k]
    }
  })

  // Update location query display
  if (newStoreFilters.locationLabel) {
    locationQuery.value = newStoreFilters.locationLabel
  } else if (newStoreFilters.city) {
    locationQuery.value = newStoreFilters.city
  } else if (newStoreFilters.region) {
    const region = polishLocations.voivodeships.find(v => v.id === newStoreFilters.region)
    locationQuery.value = region ? region.name : ''
  } else {
    locationQuery.value = ''
  }
}, { deep: true })

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  if (typeof window !== 'undefined') {
    window.removeEventListener('scroll', handleScroll)
  }
})
</script>

<template>
  <section class="hero-section">
    <div class="hero-background">
      <div class="gradient-overlay"></div>
      <div
        class="hero-image"
        :style="{ 
          backgroundImage: `url(${bannerImage})`,
          transform: `translateY(${scrollY * 0.4}px)`
        }"
      ></div>
    </div>

    <!-- ... rest of the code remains the same ... -->
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
                  @focus="markUserEditing"
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
                <select id="search-type" v-model="filters.type" class="search-select" @focus="markUserEditing">
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
                    :value="filters.priceFrom"
                    @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, true); filters.priceFrom = val ? parseFloat(val) : null }"
                    @focus="markUserEditing"
                    type="text"
                    placeholder="Od"
                    class="search-input price-input"
                  />
                  <span class="separator">-</span>
                  <input
                    :value="filters.priceTo"
                    @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, true); filters.priceTo = val ? parseFloat(val) : null }"
                    @focus="markUserEditing"
                    type="text"
                    placeholder="Do"
                    class="search-input price-input"
                  />
                  <select v-model="filters.priceUnit" class="search-select price-unit" @focus="markUserEditing">
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
                    <label class="input-label">Szerokość ({{ filters.type === 'led_screen' ? 'mm' : 'm' }})</label>
                    <div class="range-input">
                      <input
                        :value="filters.widthFrom"
                        @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, filters.type === 'led_screen' ? false : true); filters.widthFrom = val ? parseFloat(val) : null }"
                        type="text"
                        placeholder="Od"
                        class="search-input"
                      />
                      <span class="separator">-</span>
                      <input
                        :value="filters.widthTo"
                        @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, filters.type === 'led_screen' ? false : true); filters.widthTo = val ? parseFloat(val) : null }"
                        type="text"
                        placeholder="Do"
                        class="search-input"
                      />
                    </div>
                  </div>
                  <div class="input-group">
                    <label class="input-label">Wysokość ({{ filters.type === 'led_screen' ? 'mm' : 'm' }})</label>
                    <div class="range-input">
                      <input
                        :value="filters.heightFrom"
                        @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, filters.type === 'led_screen' ? false : true); filters.heightFrom = val ? parseFloat(val) : null }"
                        type="text"
                        placeholder="Od"
                        class="search-input"
                      />
                      <span class="separator">-</span>
                      <input
                        :value="filters.heightTo"
                        @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, filters.type === 'led_screen' ? false : true); filters.heightTo = val ? parseFloat(val) : null }"
                        type="text"
                        placeholder="Do"
                        class="search-input"
                      />
                    </div>
                  </div>
                </div>
                <div class="search-row">
                  <div class="input-group">
                    <label for="orientation" class="input-label">Orientacja</label>
                    <select id="orientation" v-model="filters.orientation" class="search-select" @focus="markUserEditing">
                      <option value="">Wszystkie</option>
                      <option value="vertical">Pion</option>
                      <option value="horizontal">Poziom</option>
                    </select>
                  </div>
                  <div class="input-group">
                    <label class="input-label">Powierzchnia (m²)</label>
                    <div class="range-input">
                      <input
                        :value="filters.surfaceFrom"
                        @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, true); filters.surfaceFrom = val ? parseFloat(val) : null }"
                        type="text"
                        placeholder="Od"
                        class="search-input"
                      />
                      <span class="separator">-</span>
                      <input
                        :value="filters.surfaceTo"
                        @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, true); filters.surfaceTo = val ? parseFloat(val) : null }"
                        type="text"
                        placeholder="Do"
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
                    <select v-model="filters.variant" class="search-select" @focus="markUserEditing">
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
                    <select v-model="filters.roadClass" class="search-select" @focus="markUserEditing">
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
                    <select v-model="filters.trafficIntensity" class="search-select" @focus="markUserEditing">
                      <option value="">Wszystkie</option>
                      <option value="low">Niskie</option>
                      <option value="medium">Średnie</option>
                      <option value="high">Wysokie</option>
                    </select>
                  </div>
                </div>

                <!-- OTS Range (estimatedDailyViews) -->
                <div v-if="['billboard', 'citylight', 'led_screen', 'banner', 'wall', 'totem'].includes(filters.type)" class="search-row">
                  <div class="input-group">
                    <label class="input-label">Zasięg dzienny (OTS)</label>
                    <div class="range-input">
                      <input
                        :value="filters.estimatedDailyViewsFrom"
                        @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, false); filters.estimatedDailyViewsFrom = val ? parseInt(val) : null }"
                        type="text"
                        placeholder="Od"
                        class="search-input"
                      />
                      <span class="separator">-</span>
                      <input
                        :value="filters.estimatedDailyViewsTo"
                        @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, false); filters.estimatedDailyViewsTo = val ? parseInt(val) : null }"
                        type="text"
                        placeholder="Do"
                        class="search-input"
                      />
                    </div>
                  </div>
                </div>

                <!-- Traffic Direction (all outdoor types) -->
                <div v-if="['billboard', 'banner', 'wall', 'totem'].includes(filters.type)" class="search-row">
                  <div class="input-group">
                    <label class="input-label">Kierunek ruchu</label>
                    <select v-model="filters.trafficDirection" class="search-select" @focus="markUserEditing">
                      <option value="">Wszystkie</option>
                      <option value="entry">Wjazd</option>
                      <option value="exit">Wyjazd</option>
                      <option value="both">Oba kierunki</option>
                    </select>
                  </div>
                </div>

                <!-- Rodzaj ruchu (all outdoor types) -->
                <div v-if="['billboard', 'banner', 'wall', 'totem'].includes(filters.type)" class="search-row">
                  <div class="input-group">
                    <label class="input-label">Rodzaj ruchu</label>
                    <select v-model="filters.trafficType" class="search-select" @focus="markUserEditing">
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
                    <select v-model="filters.environment" class="search-select" @focus="markUserEditing">
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
                    <label class="input-label">Rozdzielczość</label>
                    <input
                      v-model="filters.resolution"
                      type="text"
                      placeholder="np. 1920x1080"
                      class="search-input"
                      @focus="markUserEditing"
                    />
                  </div>
                  <div class="input-group">
                    <label class="input-label">Pixel Pitch (mm)</label>
                    <div class="range-input">
                      <input
                        :value="filters.pixelPitchFrom"
                        @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, true); filters.pixelPitchFrom = val ? parseFloat(val) : null }"
                        type="text"
                        placeholder="Od"
                        class="search-input"
                      />
                      <span class="separator">-</span>
                      <input
                        :value="filters.pixelPitchTo"
                        @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, true); filters.pixelPitchTo = val ? parseFloat(val) : null }"
                        type="text"
                        placeholder="Do"
                        class="search-input"
                      />
                    </div>
                  </div>
                  <div class="input-group">
                    <label class="input-label">Jasność (nits)</label>
                    <div class="range-input">
                      <input
                        :value="filters.brightnessFrom"
                        @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, false); filters.brightnessFrom = val ? parseInt(val) : null }"
                        type="text"
                        placeholder="Od"
                        class="search-input"
                      />
                      <span class="separator">-</span>
                      <input
                        :value="filters.brightnessTo"
                        @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, false); filters.brightnessTo = val ? parseInt(val) : null }"
                        type="text"
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
                    <select v-model="filters.transportScope" class="search-select" @focus="markUserEditing">
                      <option value="">Wszystkie</option>
                      <option v-for="option in transportScopeOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                      </option>
                    </select>
                  </div>
                  <div v-if="filters.variant !== 'stop'" class="input-group">
                    <label class="input-label">Liczba pojazdów</label>
                    <div class="range-input">
                      <input
                        :value="filters.vehicleCountFrom"
                        @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, false); filters.vehicleCountFrom = val ? parseInt(val) : null }"
                        type="text"
                        placeholder="Od"
                        class="search-input"
                      />
                      <span class="separator">-</span>
                      <input
                        :value="filters.vehicleCountTo"
                        @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, false); filters.vehicleCountTo = val ? parseInt(val) : null }"
                        type="text"
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
                    <select v-model="filters.mobileExposureMode" class="search-select" @focus="markUserEditing">
                      <option value="">Wszystkie</option>
                      <option value="moving">Jeżdżąca</option>
                      <option value="stationary">Stojąca</option>
                      <option value="mixed">Mieszana</option>
                    </select>
                  </div>
                </div>

                <!-- Billboard - Lighting Type -->
                <div v-if="filters.type === 'billboard'" class="search-row">
                  <div class="input-group">
                    <label class="input-label">Typ oświetlenia</label>
                    <select v-model="(filters as any).lightingType" class="search-select">
                      <option value="">Wszystkie</option>
                      <option value="led">LED</option>
                      <option value="fluorescent">Fluorescencyjne</option>
                      <option value="natural">Naturalne</option>
                      <option value="none">Brak</option>
                    </select>
                  </div>
                </div>

                <!-- Banner - Lighting Type -->
                <div v-if="filters.type === 'banner'" class="search-row">
                  <div class="input-group">
                    <label class="input-label">Typ oświetlenia</label>
                    <select v-model="(filters as any).lightingType" class="search-select">
                      <option value="">Wszystkie</option>
                      <option value="led">LED</option>
                      <option value="fluorescent">Fluorescencyjne</option>
                      <option value="natural">Naturalne</option>
                      <option value="none">Brak</option>
                    </select>
                  </div>
                </div>

                <!-- Wall - Lighting Type -->
                <div v-if="filters.type === 'wall'" class="search-row">
                  <div class="input-group">
                    <label class="input-label">Typ oświetlenia</label>
                    <select v-model="(filters as any).lightingType" class="search-select">
                      <option value="">Wszystkie</option>
                      <option value="led">LED</option>
                      <option value="fluorescent">Fluorescencyjne</option>
                      <option value="natural">Naturalne</option>
                      <option value="none">Brak</option>
                    </select>
                  </div>
                </div>

                <!-- Transport - Daily Passengers -->
                <div v-if="filters.type === 'transport'" class="search-row">
                  <div class="input-group">
                    <label class="input-label">Liczba pasażerów dziennie</label>
                    <div class="range-input">
                      <input
                        :value="(filters as any).dailyPassengersFrom"
                        @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, false); (filters as any).dailyPassengersFrom = val ? parseInt(val) : null }"
                        type="text"
                        placeholder="Od"
                        class="search-input"
                      />
                      <span class="separator">-</span>
                      <input
                        :value="(filters as any).dailyPassengersTo"
                        @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, false); (filters as any).dailyPassengersTo = val ? parseInt(val) : null }"
                        type="text"
                        placeholder="Do"
                        class="search-input"
                      />
                    </div>
                  </div>
                </div>

                <!-- Mobile - Operating Zone -->
                <div v-if="filters.type === 'mobile'" class="search-row">
                  <div class="input-group">
                    <label class="input-label">Strefa operacyjna</label>
                    <select v-model="(filters as any).operatingZone" class="search-select">
                      <option value="">Wszystkie</option>
                      <option value="center">Centrum</option>
                      <option value="periphery">Peryferia</option>
                      <option value="agglomeration">Cała aglomeracja</option>
                    </select>
                  </div>
                </div>

                <!-- LED Screen - Ambient Light Control -->
                <div v-if="filters.type === 'led_screen'" class="search-row">
                  <div class="input-group">
                    <label class="checkbox-label search-select" style="justify-content: flex-start;">
                      <input type="checkbox" v-model="(filters as any).ambientLightControl" />
                      <span>Dostosowanie do otoczenia</span>
                    </label>
                  </div>
                </div>
              </div>

              <div v-if="showEquipmentSection" class="filter-section">
                <h4 class="section-title">Wyposażenie i dodatki</h4>
                <div class="checkbox-grid">
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
                  <label v-if="['banner', 'wall'].includes(filters.type)" class="checkbox-label search-select" style="justify-content: flex-start;">
                    <input type="checkbox" v-model="(filters as any).hasLightingTypeBanner" />
                    <span>Podświetlenie</span>
                  </label>
                  <label v-if="filters.type === 'billboard'" class="checkbox-label search-select" style="justify-content: flex-start;">
                    <input type="checkbox" v-model="(filters as any).hasLightingTypeBillboard" />
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

          <!-- Only with Image Toggle -->
          <div v-if="showAdvanced" class="filter-section">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
              <input type="checkbox" v-model="filters.onlyWithImage" class="toggle-switch" style="display: none;" />
              <span class="toggle-switch-display" :class="{ active: filters.onlyWithImage }" @click="filters.onlyWithImage = !filters.onlyWithImage"></span>
              <label style="margin: 0; cursor: pointer; font-size: 0.875rem; color: #4B5563; font-weight: 500;" @click="filters.onlyWithImage = !filters.onlyWithImage">Tylko ze zdjęciem</label>
            </div>
          </div>

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
  height: 600px;
  overflow: hidden;
}

.hero-image {
  position: absolute;
  top: -200px;
  left: 0;
  right: 0;
  width: 100%;
  height: calc(100% + 200px);
  background-size: cover;
  background-position: center;
  will-change: transform;
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
  background: var(--card-bg, white);
  border-radius: 16px;
  padding: 2rem;
  box-shadow: var(--card-shadow, 0 20px 60px rgba(0, 0, 0, 0.3));
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
  color: var(--text-muted, #4B5563);
}

.search-input,
.search-select {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid var(--border-color, #E5E7EB);
  border-radius: 10px;
  font-size: 0.95rem;
  transition: all 0.2s ease;
  background: var(--card-bg, white);
  color: var(--text-main, #111827);
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

.city-suggestions,
.suggestion-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: var(--card-bg, white);
  border: 2px solid var(--border-color, #E5E7EB);
  border-radius: 10px;
  margin-top: 0.25rem;
  box-shadow: var(--card-shadow, 0 4px 12px rgba(0, 0, 0, 0.1));
  z-index: 10;
  max-height: 200px;
  overflow-y: auto;
}

.city-suggestion {
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background-color 0.2s ease;
  font-size: 0.95rem;
  color: var(--text-main, #1F2937);
}

.city-suggestion:hover {
  background-color: var(--bg-secondary, #F3F4F6);
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
  color: var(--text-main, #1F2937);
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.location-suggestion:hover {
  background-color: var(--bg-secondary, #F3F4F6);
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
  background: var(--bg-secondary, #F9FAFB);
  border-radius: 10px;
}

.toggle-switch {
  display: none !important;
}

.toggle-switch-display {
  display: inline-block;
  width: 50px;
  height: 28px;
  background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%);
  border-radius: 14px;
  cursor: pointer;
  position: relative;
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.05);
  flex-shrink: 0;
}

.toggle-switch-display::before {
  content: '';
  position: absolute;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: var(--card-bg, white);
  top: 3px;
  left: 3px;
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15), 0 1px 3px rgba(0, 0, 0, 0.1);
}

.toggle-switch-display:hover {
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.05);
}

.toggle-switch-display.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1), 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.toggle-switch-display.active::before {
  left: 25px;
  box-shadow: 0 3px 8px rgba(102, 126, 234, 0.3), 0 1px 3px rgba(0, 0, 0, 0.1);
}

.toggle-switch-display.active:hover {
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1), 0 0 0 3px rgba(102, 126, 234, 0.15);
}

.section-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-main, #1F2937);
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
  color: var(--text-muted, #4B5563);
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
  background: var(--card-bg, white);
  border: 2px solid var(--border-color, #E5E7EB);
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--text-muted, #6B7280);
  cursor: pointer;
  transition: all 0.2s ease;
}

.reset-button:hover {
  background: var(--bg-secondary, #F9FAFB);
  border-color: var(--text-muted, #9CA3AF);
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
  border: 2px solid var(--border-color, #E5E7EB);
  border-radius: 10px;
  background: var(--card-bg, white);
  cursor: pointer;
  font-size: 0.95rem;
  color: var(--text-main, #1F2937);
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
  background: var(--card-bg, white);
  border: 2px solid var(--border-color, #E5E7EB);
  border-radius: 10px;
  margin-top: 0.25rem;
  padding: 0.5rem;
  box-shadow: var(--card-shadow, 0 4px 12px rgba(0, 0, 0, 0.1));
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
  background-color: var(--bg-secondary, #F3F4F6);
}

.checkbox-option input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #4F46E5;
}
</style>
