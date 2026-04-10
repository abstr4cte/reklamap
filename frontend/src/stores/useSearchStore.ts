import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '../services/api'
import type { Advertisement, MapPin } from '../types'
import { FilterParams, DEFAULT_FILTERS } from '../types/filters'
import { normalizePolishChars, queryParamsToFilters } from '../utils/filterUtils'
import { deslugify } from '../utils/slugify'
import { type LocationResult } from '../services/locationService'
import polishLocations from '../data/polishLocations.json'

// Helper function to calculate distance between two coordinates in kilometers
function getDistanceFromLatLonInKm(lat1: number, lon1: number, lat2: number, lon2: number): number {
  const R = 6371 // Radius of the earth in km
  const dLat = deg2rad(lat2 - lat1)
  const dLon = deg2rad(lon2 - lon1)
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
    Math.sin(dLon / 2) * Math.sin(dLon / 2)
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
  const d = R * c // Distance in km
  return d
}

function deg2rad(deg: number): number {
  return deg * (Math.PI / 180)
}

export const typeColors: Record<string, string> = {
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

export const typeLabels: Record<string, string> = {
  'billboard': 'Billboardy', 'citylight': 'Citylighty', 'led_screen': 'Ekrany LED', 'banner': 'Banery',
  'wall': 'Ściany reklamowe', 'totem': 'Totemy reklamowe', 'transport': 'Reklama w transporcie',
  'mobile': 'Reklama mobilna', 'other': 'Inne'
}

export const variantLabels: Record<string, Record<string, string>> = {
  billboard: { standard: 'Jednostronny', two_sided: 'Dwustronny (back-to-back)', three_sided: 'Trójstronny (prismatron)', scrolling: 'Scrolling / Rolowany' },
  citylight: { single_sided: 'Jednostronny', double_sided: 'Dwustronny', scrolling: 'Scrolling (rotacyjny)', digital: 'Cyfrowy (DOOH)' },
  led_screen: { standard: 'Standardowy', interactive: 'Interaktywny' },
  totem: { single_sided: 'Jednostronny', double_sided: 'Dwustronny', multi_sided: 'Wielostronny / Kolumna', pylon: 'Pylon (przy drodze)', digital: 'Cyfrowy (LED)' },
  transport: { bus: 'Autobus', tram: 'Tramwaj', metro: 'Metro', train: 'Pociąg / SKM / Kolej', stop: 'Przystenek' },
  mobile: { trailer: 'Przyczepka', car: 'Samochód', bike: 'Rower', other: 'Inna' }
}

export const defaultPriceUnitsByType: Record<string, string> = {
  'billboard': 'month',
  'wall': 'month',
  'banner': 'day',
  'citylight': 'month',
  'led_screen': 'month',
  'totem': 'month',
  'transport': 'month',
  'mobile': 'day',
  'other': 'day'
}

export interface LocationSuggestion {
    type: 'region' | 'city' | 'street'
    value: string
    label: string
    subtitle?: string
    coords?: { lat: number; lng: number }
    addresstype?: string
    city?: string
    road?: string
    osmType?: string
    osmClass?: string
}

// Popular locations with precise coordinates
export const popularLocations: LocationSuggestion[] = [
  { type: 'city' as const, value: 'Warszawa', label: 'Warszawa', subtitle: 'Mazowieckie, Polska', coords: { lat: 52.2297, lng: 21.0122 }, osmClass: 'place', osmType: 'city' },
  { type: 'city' as const, value: 'Kraków', label: 'Kraków', subtitle: 'Małopolskie, Polska', coords: { lat: 50.0647, lng: 19.9450 }, osmClass: 'place', osmType: 'city' },
  { type: 'city' as const, value: 'Wrocław', label: 'Wrocław', subtitle: 'Dolnośląskie, Polska', coords: { lat: 51.1079, lng: 17.0385 }, osmClass: 'place', osmType: 'city' },
  { type: 'city' as const, value: 'Poznań', label: 'Poznań', subtitle: 'Wielkopolskie, Polska', coords: { lat: 52.4064, lng: 16.9252 }, osmClass: 'place', osmType: 'city' },
  { type: 'city' as const, value: 'Gdańsk', label: 'Gdańsk', subtitle: 'Pomorskie, Polska', coords: { lat: 54.3520, lng: 18.6466 }, osmClass: 'place', osmType: 'city' },
]

export const useSearchStore = defineStore('search', () => {
  // State
  const listings = ref<Advertisement[]>([])
  const mapPins = ref<MapPin[]>([])
  const isLoading = ref(false)
  const filters = ref<FilterParams>({ ...DEFAULT_FILTERS })
  const sortBy = ref('newest')
  const priceDisplay = ref<'day' | 'week' | 'month' | 'year' | 'sqm' | 'campaign'>('day')
  const viewMode = ref<'grid' | 'list'>('grid')
  const currentPage = ref(1)
  const itemsPerPage = ref(24)
  // Server-side pagination state
  const serverTotal = ref(0)
  const serverLastPage = ref(1)
  // Track filters from path params (category/city from menu)
  const pathParamsFilters = ref<{ type?: string; city?: string }>({})

  // Actions
  const setListings = (data: Advertisement[]) => {
    listings.value = data
  }

  // Build query params from current filters + page for the backend API
  const buildApiParams = (): Record<string, any> => {
    const f = filters.value
    const params: Record<string, any> = {
      page: currentPage.value,
      per_page: itemsPerPage.value,
      sort: sortBy.value,
    }

    if (f.type) params.type = f.type
    if (f.city && !f.mapBounds) {
      params.city = f.city
      if (f.cityStrict) params.city_strict = 1
    }
    if (f.region && !f.mapBounds) params.region = f.region
    if (f.keyword) params.search = f.keyword
    if (f.status.length > 0) params.status = f.status.join(',')

    // Distance coords (when city is strictly matched with known coords)
    if (f.selectedLocationCoords && f.cityStrict && !f.mapBounds) {
      params.lat = f.selectedLocationCoords.lat
      params.lng = f.selectedLocationCoords.lng
      params.radius = 30
    }

    // Map bounds (overrides city/region when user pans the map)
    if (f.mapBounds) {
      params.map_north = f.mapBounds.northEast.lat
      params.map_south = f.mapBounds.southWest.lat
      params.map_east  = f.mapBounds.northEast.lng
      params.map_west  = f.mapBounds.southWest.lng
    }

    // Price range
    if (f.priceFrom !== null) { params.price_from = f.priceFrom; params.price_unit = f.priceUnit }
    if (f.priceTo !== null)   { params.price_to   = f.priceTo;   params.price_unit = f.priceUnit }

    // Dimension ranges (LED dimensions in DB are meters, but user inputs mm for LED)
    const ledFactor = f.type === 'led_screen' ? 1000 : 1
    if (f.widthFrom  !== null) params.width_from  = f.widthFrom  / ledFactor
    if (f.widthTo    !== null) params.width_to    = f.widthTo    / ledFactor
    if (f.heightFrom !== null) params.height_from = f.heightFrom / ledFactor
    if (f.heightTo   !== null) params.height_to   = f.heightTo   / ledFactor

    // Surface
    if (f.surfaceFrom !== null) params.surface_from = f.surfaceFrom
    if (f.surfaceTo   !== null) params.surface_to   = f.surfaceTo

    // Exact match filters
    if (f.orientation)       params.orientation        = f.orientation
    if (f.variant)           params.variant            = f.variant
    if (f.roadClass)         params.road_class         = f.roadClass
    if (f.offerType)         params.offer_type         = f.offerType
    if (f.trafficIntensity)  params.traffic_intensity  = f.trafficIntensity
    if (f.environment)       params.environment        = f.environment
    if (f.transportScope)    params.transport_scope    = f.transportScope
    if (f.mobileExposureMode)params.mobile_exposure_mode = f.mobileExposureMode
    if (f.operatingZone)     params.operating_zone     = f.operatingZone
    if (f.rentalPeriod)      params.rental_period      = f.rentalPeriod
    if (f.lightingType)      params.lighting_type      = f.lightingType
    if (f.lightingTypeBanner)params.lighting_type_banner = f.lightingTypeBanner
    if (f.locationTier)      params.location_tier      = f.locationTier

    // Traffic direction / type
    const trafficDir = Array.isArray(f.trafficDirection) ? f.trafficDirection[0] : f.trafficDirection
    if (trafficDir) params.traffic_direction = trafficDir

    const trafficType = Array.isArray(f.trafficType) ? f.trafficType[0] : f.trafficType
    if (trafficType) params.traffic_type = trafficType

    // Boolean filters
    if (f.hasBacklight)          params.has_backlight          = 1
    if (f.onlyWithImage)         params.has_image              = 1
    if (f.priceIncludesPrint)    params.price_includes_print   = 1
    if (f.priceIncludesMounting) params.price_includes_mounting = 1
    if (f.graphicDesignHelp)     params.graphic_design_help    = 1
    if (f.hasVatInvoice)         params.has_vat_invoice        = 1
    if (f.ambientLightControl)   params.ambient_light_control  = 1

    // LED-specific ranges
    if (f.pixelPitchFrom      !== null) params.pixel_pitch_from       = f.pixelPitchFrom
    if (f.pixelPitchTo        !== null) params.pixel_pitch_to         = f.pixelPitchTo
    if (f.brightnessFrom      !== null) params.brightness_from        = f.brightnessFrom
    if (f.brightnessTo        !== null) params.brightness_to          = f.brightnessTo
    if (f.vehicleCountFrom    !== null) params.vehicle_count_from     = f.vehicleCountFrom
    if (f.vehicleCountTo      !== null) params.vehicle_count_to       = f.vehicleCountTo
    if (f.campaignDurationFrom !== null) params.campaign_duration_from = f.campaignDurationFrom
    if (f.campaignDurationTo  !== null) params.campaign_duration_to   = f.campaignDurationTo
    if (f.dailyPassengersFrom !== null) params.daily_passengers_from  = f.dailyPassengersFrom
    if (f.dailyPassengersTo   !== null) params.daily_passengers_to    = f.dailyPassengersTo

    return params
  }

  // Generation counter to discard stale responses when fetches overlap
  let _fetchGeneration = 0
  let _mapPinsGeneration = 0
  let _mapBoundsTimer: ReturnType<typeof setTimeout> | null = null

  const fetchMapPins = async () => {
    const generation = ++_mapPinsGeneration
    try {
      // Build params without mapBounds — pins always show all results for current filters
      const params = buildApiParams()
      delete params.map_north
      delete params.map_south
      delete params.map_east
      delete params.map_west

      const pins = await api.getMapPins(params)
      if (generation !== _mapPinsGeneration) return
      mapPins.value = pins
    } catch {
      if (generation !== _mapPinsGeneration) return
    }
  }

  const fetchListings = async () => {
    const generation = ++_fetchGeneration

    try {
      isLoading.value = true
      const params = buildApiParams()
      const response = await api.getAdvertisements(params)

      // Discard if a newer fetch was started while this was in flight
      if (generation !== _fetchGeneration) return

      if (response && 'data' in response && 'current_page' in response) {
        // Paginated response from Laravel paginate()
        listings.value = Array.isArray(response.data) ? response.data : []
        serverTotal.value = response.total ?? listings.value.length
        serverLastPage.value = response.last_page ?? 1
      } else if (Array.isArray(response)) {
        // Plain array (e.g. ids= query, legacy)
        listings.value = response
        serverTotal.value = response.length
        serverLastPage.value = 1
      }
    } catch (error) {
      if (generation !== _fetchGeneration) return
      console.error('Failed to fetch listings:', error)
    } finally {
      if (generation === _fetchGeneration) {
        isLoading.value = false
      }
    }
  }

  const applyFilters = (newFilters: Partial<FilterParams>) => {
    filters.value = { ...filters.value, ...newFilters }

    const isMapBoundsOnly = Object.keys(newFilters).length === 1 && 'mapBounds' in newFilters
    if (isMapBoundsOnly) {
      // Debounce grid re-fetch on map pan/zoom — 600ms after user stops moving
      if (_mapBoundsTimer) clearTimeout(_mapBoundsTimer)
      _mapBoundsTimer = setTimeout(() => {
        currentPage.value = 1
        fetchListings()
      }, 600)
      return
    }

    currentPage.value = 1
    fetchListings()
    fetchMapPins()

    // Persist to localStorage
    try {
      const filtersToSave = { ...filters.value }
      localStorage.setItem('reklamap_last_search', JSON.stringify(filtersToSave))
    } catch (e) {
      // Silently fail
    }
  }

  const resetFilters = () => {
    filters.value = { ...DEFAULT_FILTERS }
    sortBy.value = 'newest'
    priceDisplay.value = 'day'
    currentPage.value = 1
    pathParamsFilters.value = {}

    fetchListings()
    fetchMapPins()

    // Clear localStorage
    try {
      localStorage.removeItem('reklamap_last_search')
      localStorage.removeItem('user_initiated_search')
    } catch (e) {
      // Silently fail
    }
  }

  const setViewMode = (mode: 'grid' | 'list') => {
    viewMode.value = mode
    localStorage.setItem('adsViewMode', mode)
  }

  const setCurrentPage = (page: number) => {
    currentPage.value = page
    fetchListings()
  }

  const syncFromUrl = (query: Record<string, string>, params: Record<string, string>) => {
    // We only reset filters, but DO NOT CLEAR listings.value anymore.
    // Clearing listings causes empty states on every navigation and race conditions.
    // The existing data will be instantly re-filtered on the frontend by sortedAndFilteredListings.
    filters.value = { ...DEFAULT_FILTERS }
    
    // Reset other state to avoid stale pagination or sorting
    currentPage.value = 1
    sortBy.value = 'newest'
    
    // Reset path params tracking
    pathParamsFilters.value = {}
    
    const urlFilters = queryParamsToFilters(query)
    
    // Merge URL path params
    if (params.type) {
        // Find DB type for slug
        const typeMapping: Record<string, string> = {
            'billboardy': 'billboard',
            'citylighty': 'citylight',
            'ekrany-led': 'led_screen',
            'banery': 'banner',
            'sciany-reklamowe': 'wall',
            'totemy-reklamowe': 'totem',
            'reklama-w-transporcie': 'transport',
            'reklama-mobilna': 'mobile',
            'inne': 'other'
        }
        filters.value.type = typeMapping[params.type as string] || params.type as string
        pathParamsFilters.value.type = filters.value.type // Track as path param
    }
    
    if (params.city) {
        // City from path has priority
        filters.value.city = deslugify(params.city as string)
        filters.value.cityStrict = true // If in URL path, it's usually intentional
        pathParamsFilters.value.city = filters.value.city // Track as path param
    }

    // Apply other filters from query
    // cityStrict set by path params (params.city) takes priority over query param default (false)
    const hasCityFromPath = !!params.city
    Object.entries(urlFilters).forEach(([key, value]) => {
      if (hasCityFromPath && key === 'cityStrict') return // path param already set cityStrict=true
      if (value !== undefined && value !== null && value !== '' &&
          (Array.isArray(value) ? value.length > 0 : true)) {
        // @ts-ignore
        filters.value[key] = value
      }
    })

    if (query.sort) {
        sortBy.value = query.sort
    }

    if (query.page) {
        currentPage.value = parseInt(query.page) || 1
    }
  }

  const processLocationSuggestions = (apiResults: LocationResult[] | null | undefined): LocationSuggestion[] => {
    if (!apiResults) return []
    const apiSuggestions = apiResults.map(loc => {
      const voivodeship = loc.state || ''
      const parts = loc.displayName.split(', ')
      let detailedLocation = ''
      
      if (parts.length >= 2) {
        if (parts[0] !== loc.name && parts[1] === loc.name) {
          detailedLocation = `${parts[0]}, ${loc.name}`
        } else {
          detailedLocation = loc.name
        }
      } else {
        detailedLocation = loc.name
      }
      
      let subtitleParts: string[] = []
      if (loc.city && loc.city !== loc.name && !detailedLocation.includes(loc.city)) {
        subtitleParts.push(loc.city)
      }
      if (voivodeship) subtitleParts.push(voivodeship)
      subtitleParts.push('Polska')
      
      return {
        type: (loc.addresstype === 'road' || loc.osmClass === 'highway' || loc.addresstype === 'street') ? ('street' as const) : ('city' as const),
        value: loc.name,
        label: detailedLocation,
        subtitle: subtitleParts.join(', '),
        coords: { lat: loc.lat, lng: loc.lng },
        addresstype: loc.addresstype,
        city: loc.city,
        road: loc.road,
        osmType: loc.osmType,
        osmClass: loc.osmClass
      }
    })

    const uniqueCities = new Map<string, LocationSuggestion>()
    apiSuggestions.forEach(suggestion => {
      const cityKey = `${suggestion.value}|${suggestion.subtitle?.split(', ').slice(-2)[0] || ''}`
      const existing = uniqueCities.get(cityKey)
      if (!existing) {
        uniqueCities.set(cityKey, suggestion)
      } else {
        const getPriority = (s: LocationSuggestion) => {
          if (s.osmClass === 'place' && s.osmType === 'city') return 4
          if (s.osmClass === 'place' && s.osmType === 'town') return 3
          if (s.addresstype === 'city') return 2
          if (s.type === 'city') return 1
          return 0
        }
        if (getPriority(suggestion) > getPriority(existing)) {
          uniqueCities.set(cityKey, suggestion)
        }
      }
    })
    
    return Array.from(uniqueCities.values())
  }

  const selectLocationSuggestion = (suggestion: LocationSuggestion, targetFilters: FilterParams) => {
    const displayLabel = suggestion.type === 'street' && suggestion.city 
      ? `${suggestion.label}, ${suggestion.city}` 
      : suggestion.label
    
    targetFilters.locationLabel = displayLabel
    
    if (suggestion.type === 'region') {
      const matchingRegion = (polishLocations as any).voivodeships.find((v: any) => v.name === suggestion.label)
      targetFilters.region = matchingRegion?.id || suggestion.value
      targetFilters.city = ''
      targetFilters.cityStrict = false
      targetFilters.keyword = ''
      targetFilters.selectedLocationCoords = null
    } else if (suggestion.type === 'street') {
      targetFilters.city = suggestion.city || ''
      targetFilters.region = ''
      targetFilters.street = suggestion.road || suggestion.value
      targetFilters.selectedLocationCoords = suggestion.coords || null
      targetFilters.cityStrict = !!targetFilters.city
    } else {
      targetFilters.city = suggestion.value
      targetFilters.region = ''
      targetFilters.street = ''
      targetFilters.keyword = ''
      targetFilters.selectedLocationCoords = suggestion.coords || null
      targetFilters.cityStrict = true
    }
    
    return displayLabel
  }

  // Helper for price calculation
  const getPrice = (ad: Advertisement, period: 'day' | 'week' | 'month' | 'year' | 'sqm' | 'campaign') => {
    const basePrice = ad.price
    const adPriceUnit = ad.price_unit || 'month'

    if (adPriceUnit === period) return basePrice

    let pricePerMonth = basePrice
    switch (adPriceUnit) {
      case 'day': pricePerMonth = basePrice * 30; break
      case 'week': pricePerMonth = basePrice * 4; break
      case 'month': pricePerMonth = basePrice; break
      case 'year': pricePerMonth = basePrice / 12; break
      case 'campaign': pricePerMonth = basePrice; break
    }

    switch (period) {
      case 'day': return pricePerMonth / 30
      case 'week': return pricePerMonth / 4
      case 'month': return pricePerMonth
      case 'year': return pricePerMonth * 12
      case 'campaign':
        if (ad.campaign_duration) return pricePerMonth * (ad.campaign_duration / 30)
        return Number.MAX_SAFE_INTEGER
      case 'sqm':
        const area = (ad.width || 0) * (ad.height || 0)
        return area > 0 ? pricePerMonth / area : Number.MAX_SAFE_INTEGER
      default: return pricePerMonth
    }
  }

  const getTypeLabel = (type: string): string => {
    return typeLabels[type] || (type ? type.charAt(0).toUpperCase() + type.slice(1) : '')
  }

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

  const priceUnitOptions = [
    { value: 'day', label: 'za dzień' },
    { value: 'week', label: 'za tydzień' },
    { value: 'month', label: 'za miesiąc' },
    { value: 'year', label: 'za rok' },
    { value: 'sqm', label: 'za m²' },
    { value: 'campaign', label: 'za kampanię' }
  ]

  const getAvailablePriceUnits = (type: string) => {
    if (!type) return priceUnitOptions.filter(o => o.value !== 'sqm')
    
    const disabled: Record<string, string[]> = {
      citylight: ['day', 'week', 'campaign', 'sqm'],
      billboard: ['campaign', 'sqm'],
      wall: ['campaign', 'sqm'],
      banner: ['year', 'campaign', 'sqm'],
      led_screen: ['week', 'sqm'],
      transport: ['week', 'sqm'],
      mobile: ['week', 'month', 'year', 'sqm'],
      totem: ['sqm'],
      other: ['sqm']
    }
    const skip = disabled[type] || ['sqm']
    return priceUnitOptions.filter(o => !skip.includes(o.value))
  }

  const sortOptions = [
    { value: 'newest', label: 'Najnowsze', description: 'Od najnowszych' },
    { value: 'oldest', label: 'Najstarsze', description: 'Od najstarszych' },
    { value: 'name-asc', label: 'Nazwa A-Z', description: 'Alfabetycznie rosnąco' },
    { value: 'name-desc', label: 'Nazwa Z-A', description: 'Alfabetycznie malejąco' },
    { value: 'price-day-asc', label: 'Cena za dzień', description: 'Od najtańszych' },
    { value: 'price-day-desc', label: 'Cena za dzień', description: 'Od najdroższych' },
    { value: 'price-month-asc', label: 'Cena za miesiąc', description: 'Od najtańszych' },
    { value: 'price-month-desc', label: 'Cena za miesiąc', description: 'Od najdroższych' },
    { value: 'price-sqm-asc', label: 'Cena za m²', description: 'Od najtańszych' },
    { value: 'price-sqm-desc', label: 'Cena za m²', description: 'Od najdroższych' },
    { value: 'price-campaign-asc', label: 'Cena za kampanię', description: 'Od najtańszych' },
    { value: 'price-campaign-desc', label: 'Cena za kampanię', description: 'Od najdroższych' }
  ]

  const getStatusLabel = (ad: Advertisement) => {
    let status = ad.display_status || ad.status
    if (status === 'soon_available' && ad.available_from && new Date(ad.available_from) <= new Date()) status = 'active'
    const labels: Record<string, string> = { active: 'Wolne', reserved: 'Zarezerwowane', soon_available: 'Wkrótce dostępne' }
    return labels[status] || 'Nieznany'
  }

  const getStatusColor = (ad: Advertisement) => {
    let status = ad.display_status || ad.status
    if (status === 'soon_available' && ad.available_from && new Date(ad.available_from) <= new Date()) status = 'active'
    const colors: Record<string, string> = { active: '#10B981', reserved: '#F59E0B', soon_available: '#3B82F6' }
    return colors[status] || '#6B7280'
  }

  const formatLocation = (location: string, city: string) => {
    if (!location) return city
    const parts = location.split(',').map(p => p.trim())
    const streetWithNumber = parts.length >= 2 ? ( /^\d+/.test(parts[0]) ? `${parts[1]} ${parts[0]}` : parts[0] ) : (parts[0] || location)
    return `${streetWithNumber}, ${city}`
  }

  const formatTrafficDirection = (directions: string[] | undefined) => {
    if (!directions || !Array.isArray(directions) || directions.length === 0) return '—'
    if (directions.includes('entry') && directions.includes('exit')) return 'Oba kierunki'
    return directions.map(dir => dir === 'entry' ? 'Wjazd do miasta' : 'Wyjazd z miasta').join(', ')
  }

  const getPriceUnitLabel = (ad: any): string => {
    const unit = typeof ad === 'string' ? ad : (ad.price_unit || 'month')
    const labels: Record<string, string> = { day: 'za dzień', week: 'za tydzień', month: 'za miesiąc', year: 'za rok', campaign: 'za kampanię', sqm: 'za m²' }
    return labels[unit] || unit
  }

  const getPriceLabel = (period: string, ad?: Advertisement) => {
    const labels: Record<string, string> = { 
      day: '/dzień', 
      week: '/tydzień', 
      month: '/miesiąc', 
      year: '/rok', 
      campaign: '/kampanię', 
      sqm: '/m²' 
    }
    if (period === 'campaign' && ad?.campaign_duration) return `/kampanię (${ad.campaign_duration} dni)`
    return labels[period] || '/miesiąc'
  }

  const getVariantLabel = (variant: string, type: string): string => {
    if (!variant) return '—'
    const labels: Record<string, Record<string, string>> = {
      billboard: { standard: 'Jednostronny', two_sided: 'Dwustronny (back-to-back)', three_sided: 'Trójstronny (prismatron)', scrolling: 'Scrolling / Rolowany' },
      citylight: { single_sided: 'Jednostronny', double_sided: 'Dwustronny', scrolling: 'Scrolling (rotacyjny)', digital: 'Cyfrowy (DOOH)' },
      led_screen: { standard: 'Standardowy', interactive: 'Interaktywny' },
      totem: { single_sided: 'Jednostronny', double_sided: 'Dwustronny', multi_sided: 'Wielostronny / Kolumna', pylon: 'Pylon (przy drodze)', digital: 'Cyfrowy (LED)' },
      transport: { bus: 'Autobus', tram: 'Tramwaj', metro: 'Metro', train: 'Pociąg / SKM / Kolej', stop: 'Przystanek' },
      mobile: { trailer: 'Przyczepka', car: 'Samochód', bike: 'Rower', other: 'Inna' }
    }
    return labels[type]?.[variant] || variant
  }

  const getRoadClassLabel = (roadClass: string): string => {
    const labels: Record<string, string> = { highway: 'Autostrada (A)', expressway: 'Droga ekspresowa (S)', national: 'Droga krajowa (DK)', regional: 'Droga wojewódzka', local: 'Droga lokalna', urban: 'Droga miejska' }
    return labels[roadClass] || roadClass
  }

  const getTrafficIntensityLabel = (intensity: string): string => {
    const labels: Record<string, string> = { low: 'Niskie', medium: 'Średnie', high: 'Wysokie' }
    return labels[intensity] || intensity
  }

  const formatTrafficType = (types: string[] | undefined) => {
    if (!types || !Array.isArray(types) || types.length === 0) return '—'
    return types.map(t => t === 'pedestrian' ? 'Pieszy' : 'Samochodowy').join(', ')
  }

  const formatEnvironment = (environment: string | undefined) => {
    if (!environment) return '—'
    const labels: Record<string, string> = { indoor: 'Wewnątrz', outdoor: 'Na zewnątrz', event: 'Event / Wydarzenie' }
    return labels[environment] || environment
  }

  const formatTransportScope = (scope: string | undefined) => {
    if (!scope) return '—'
    const labels: Record<string, string> = { internal: 'Wewnętrzna', external: 'Zewnętrzna', full_vehicle: 'Całopojazdowa' }
    return labels[scope] || scope
  }

  const formatMobileExposureMode = (mode: string | undefined) => {
    if (!mode) return '—'
    const labels: Record<string, string> = { moving: 'Jeżdżąca', stationary: 'Stojąca', mixed: 'Mieszana' }
    return labels[mode] || mode
  }

  const formatLightingType = (type: string | undefined) => {
    if (!type) return '—'
    const labels: Record<string, string> = { led: 'LED', fluorescent: 'Fluorescencyjne', natural: 'Naturalne', none: 'Brak' }
    return labels[type] || type
  }

  const formatLightingTypeBanner = (type: string | undefined) => {
    if (!type) return '—'
    const labels: Record<string, string> = { none: 'Brak podświetlenia', backlight: 'Podświetlenie z tyłu', frontlight: 'Podświetlenie z przodu' }
    return labels[type] || type
  }

  const formatOperatingZone = (zone: string | undefined) => {
    if (!zone) return '—'
    const labels: Record<string, string> = { center: 'Centrum', periphery: 'Peryferia', agglomeration: 'Cała aglomeracja' }
    return labels[zone] || zone
  }

  // Getters
  const sortedAndFilteredListings = computed(() => {
    let filtered = listings.value
    const f = filters.value

    // 1. Text Search (Keyword)
    if (f.keyword) {
      const keyword = normalizePolishChars(f.keyword.toLowerCase())
      filtered = filtered.filter(ad =>
        normalizePolishChars(ad.title.toLowerCase()).includes(keyword) ||
        normalizePolishChars(ad.description.toLowerCase()).includes(keyword) ||
        normalizePolishChars(ad.location.toLowerCase()).includes(keyword)
      )
    }

    // 2. Location Search
    // Skip location text filters if mapBounds is active (user is interactively panning/zooming the map)
    // This allows users to zoom out of a city and discover ads in neighboring areas
    if (!f.mapBounds) {
      if (f.region) filtered = filtered.filter(ad => ad.region === f.region)
      if (f.city) {
        const cityQuery = normalizePolishChars(f.city.toLowerCase().trim())
        if (f.cityStrict) {
          // Ścisłe dopasowanie miasta (np. po wyborze z sugestii)
          filtered = filtered.filter(ad => {
            const cityMatch = normalizePolishChars((ad.city || '').toLowerCase()) === cityQuery
            
            // Jeśli mamy współrzędne wybranej lokalizacji, dodatkowo filtruj po odległości
            // aby wykluczyć inne miasta o tej samej nazwie (np. różne "Warszawy" w Polsce)
            if (cityMatch && f.selectedLocationCoords) {
              const distance = getDistanceFromLatLonInKm(
                f.selectedLocationCoords.lat,
                f.selectedLocationCoords.lng,
                ad.latitude,
                ad.longitude
              )
              // Ogłoszenie musi być w promieniu 30km od wybranego miasta
              return distance <= 30
            }
            
            return cityMatch
          })
        } else {
          // Inteligentne szukanie luźne (obsługuje typowanie ręczne)
          filtered = filtered.filter(ad => {
            const adCity = normalizePolishChars((ad.city || '').toLowerCase())
            const adLocation = normalizePolishChars((ad.location || '').toLowerCase())
            
            // 1. Jeśli wpisana fraza jest częścią nazwy miasta obiektu -> OK
            if (adCity.includes(cityQuery)) return true
            
            // 2. Jeśli wpisana fraza jest osobnym słowem w adresie -> OK
            const locationWords = adLocation.split(/[^a-z0-9]+/)
            return locationWords.some(word => word === cityQuery)
          })
        }
      }
      
      // 3. Street Search (from suggestion selection)
      if (f.street) {
        const streetQuery = normalizePolishChars(f.street.toLowerCase().trim())
        filtered = filtered.filter(ad => 
          normalizePolishChars((ad.location || '').toLowerCase()).includes(streetQuery)
        )
      }
    }

    // 3. Exact Match Filters
    const exactFilters: Record<string, keyof Advertisement> = {
      type: 'type',
      rentalPeriod: 'rental_period' as any,
      orientation: 'orientation' as any,
      trafficIntensity: 'traffic_intensity' as any,
      offerType: 'offer_type' as any,
      variant: 'variant' as any,
      roadClass: 'road_class' as any,
      environment: 'environment' as any,
      transportScope: 'transport_scope' as any,
      mobileExposureMode: 'mobile_exposure_mode' as any,
      operatingZone: 'operating_zone' as any,
      locationTier: 'location_tier' as any // Virtual field check below
    }

    Object.entries(exactFilters).forEach(([filterKey, adKey]) => {
      const val = (f as any)[filterKey]
      if (val) {
        if (filterKey === 'locationTier') {
          if (f.type === 'billboard') {
            filtered = filtered.filter(ad => {
              const ti = ad.traffic_intensity
              const rc = (ad as any).road_class
              const tier = (ti === 'high' && ['highway', 'expressway', 'national'].includes(rc || '')) ? 'PREMIUM' : 'STANDARD'
              return tier === val
            })
          }
        } else {
          filtered = filtered.filter(ad => (ad as any)[adKey] === val)
        }
      }
    })

    // 4. Range Filters (Min/Max)
    const rangeFilters: Record<string, { key: keyof Advertisement, min: string, max: string }> = {
      price: { key: 'price', min: 'priceFrom', max: 'priceTo' },
      width: { key: 'width', min: 'widthFrom', max: 'widthTo' },
      height: { key: 'height', min: 'heightFrom', max: 'heightTo' },
      pixelPitch: { key: 'pixel_pitch' as any, min: 'pixelPitchFrom', max: 'pixelPitchTo' },
      brightness: { key: 'brightness' as any, min: 'brightnessFrom', max: 'brightnessTo' },
      vehicleCount: { key: 'vehicle_count' as any, min: 'vehicleCountFrom', max: 'vehicleCountTo' },
      campaignDuration: { key: 'campaign_duration' as any, min: 'campaignDurationFrom', max: 'campaignDurationTo' }
    }

    Object.values(rangeFilters).forEach(config => {
      let minVal = (f as any)[config.min]
      let maxVal = (f as any)[config.max]
      
      // Special handling for LED screen dimensions
      // Filters store dimensions in mm for LED screens, but database has meters
      // Convert mm to meters for filtering when type is led_screen
      if ((config.key === 'width' || config.key === 'height') && f.type === 'led_screen') {
        if (minVal !== null) minVal = minVal / 1000
        if (maxVal !== null) maxVal = maxVal / 1000
      }
      
      if (config.key === 'price') {
        const unit = f.priceUnit || 'month'
        if (minVal !== null) filtered = filtered.filter(ad => getPrice(ad, unit as any) >= minVal)
        if (maxVal !== null) filtered = filtered.filter(ad => getPrice(ad, unit as any) <= maxVal)
      } else {
        if (minVal !== null) filtered = filtered.filter(ad => (ad as any)[config.key] >= minVal)
        if (maxVal !== null) filtered = filtered.filter(ad => (ad as any)[config.key] <= maxVal)
      }
    })

    // 5. Boolean Flags
    const flagFilters: Record<string, keyof Advertisement> = {
      hasBacklight: 'has_backlight' as any,
      onlyWithImage: 'has_image' as any,
      priceIncludesPrint: 'price_includes_print' as any,
      priceIncludesMounting: 'price_includes_mounting' as any,
      graphicDesignHelp: 'graphic_design_help' as any,
      hasVatInvoice: 'has_vat_invoice' as any
    }

    Object.entries(flagFilters).forEach(([filterKey, adKey]) => {
      // hasBacklight has special handling below
      if (filterKey === 'hasBacklight') return
      if ((f as any)[filterKey]) filtered = filtered.filter(ad => (ad as any)[adKey] === true)
    })

    if (f.hasBacklight) {
      filtered = filtered.filter(ad => 
        ad.has_backlight === true || 
        (ad.lighting_type && ad.lighting_type !== '' && ad.lighting_type !== 'none') ||
        (ad.lighting_type_banner && ad.lighting_type_banner !== '' && ad.lighting_type_banner !== 'none')
      )
    }

    // 6. Special Case: Surface (Calculated)
    if (f.surfaceFrom !== null || f.surfaceTo !== null) {
      filtered = filtered.filter(ad => {
        const surface = (ad.width || 0) * (ad.height || 0)
        const fromOk = f.surfaceFrom === null || surface >= f.surfaceFrom
        const toOk = f.surfaceTo === null || surface <= f.surfaceTo
        return fromOk && toOk
      })
    }

    // 7. Special Case: Multi-select status
    if (f.status && f.status.length > 0) {
      filtered = filtered.filter(ad => {
        // Normalizujmy statusy do wspólnego mianownika ('active')
        let adStatus = ad.status === 'available' ? 'active' : ad.status
        let d_status = ad.display_status || adStatus
        if (d_status === 'available') d_status = 'active'
        
        // Oblicz 'effective status' na frontendzie - jeśli status to 'soon_available', ale data minęła, to traktuj jako 'active' (Wolne)
        let effectiveStatus = d_status
        if (d_status === 'soon_available' && ad.available_from) {
          if (new Date(ad.available_from) <= new Date()) {
            effectiveStatus = 'active'
          }
        }
        
        const filterStatus = f.status.map(s => s === 'available' ? 'active' : s)
        return filterStatus.includes(effectiveStatus)
      })
    }

    // 8. Special Case: Traffic Direction (Array in ad, string/array in filter)
    if (f.trafficDirection) {
      // trafficDirection can be string[] or string depending on usage
      const dirValue = f.trafficDirection
      const dir = Array.isArray(dirValue) ? (dirValue.length > 0 ? dirValue[0] : '') : dirValue
      if (dir && dir !== '') {
        filtered = filtered.filter(ad => {
          if (!ad.traffic_direction) return false
          const dirs = Array.isArray(ad.traffic_direction) ? ad.traffic_direction : [ad.traffic_direction]
          if (dir === 'both') return dirs.length >= 2 || dirs.includes('both')
          return dirs.includes(dir) || dirs.includes('both')
        })
      }
    }

    // 9. Special Case: Traffic Type (Array in ad, string/array in filter)
    if (f.trafficType) {
      // trafficType can be string[] or string depending on usage
      const typeValue = f.trafficType
      const type = Array.isArray(typeValue) ? (typeValue.length > 0 ? typeValue[0] : '') : typeValue
      if (type && type !== '') {
        filtered = filtered.filter(ad => {
          if (!ad.traffic_type) return false
          const types = Array.isArray(ad.traffic_type) ? ad.traffic_type : [ad.traffic_type]
          if (type === 'both') return types.length >= 2 || types.includes('both')
          return types.includes(type) || types.includes('both')
        })
      }
    }

    // 10. Special Case: Lighting Type (Multiple fields)
    if (f.lightingType && f.lightingType !== '') {
      filtered = filtered.filter(ad => ad.lighting_type === f.lightingType)
    }

    if (f.lightingTypeBanner && f.lightingTypeBanner !== '') {
      filtered = filtered.filter(ad => ad.lighting_type_banner === f.lightingTypeBanner)
    }


    // 10. Map Bounds Filter
    if (f.mapBounds) {
      const { northEast, southWest } = f.mapBounds
      filtered = filtered.filter(ad => {
        if (!ad.latitude || !ad.longitude) return false
        return (
          ad.latitude <= northEast.lat &&
          ad.latitude >= southWest.lat &&
          ad.longitude <= northEast.lng &&
          ad.longitude >= southWest.lng
        )
      })
    }

    // 11. Sorting logic
    const sorted = [...filtered]
    const sortVal = sortBy.value

    if (sortVal.startsWith('price-')) {
        const unit = sortVal.split('-')[1] as any
        priceDisplay.value = unit
        if (sortVal.endsWith('-asc')) {
            sorted.sort((a, b) => getPrice(a, unit) - getPrice(b, unit))
        } else {
            sorted.sort((a, b) => {
                const pA = getPrice(a, unit); const pB = getPrice(b, unit)
                if (pA === Number.MAX_SAFE_INTEGER && pB === Number.MAX_SAFE_INTEGER) return 0
                if (pA === Number.MAX_SAFE_INTEGER) return 1
                if (pB === Number.MAX_SAFE_INTEGER) return -1
                return pB - pA
            })
        }
    } else {
        switch (sortVal) {
            case 'newest': sorted.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()); break
            case 'oldest': sorted.sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()); break
            case 'name-asc': sorted.sort((a, b) => a.title.localeCompare(b.title, 'pl')); break
            case 'name-desc': sorted.sort((a, b) => b.title.localeCompare(a.title, 'pl')); break
        }
    }

    return sorted
  })

  // Server already returns paginated data — no client-side slicing needed.
  // sortedAndFilteredListings may still apply client-side refinements (e.g. mapBounds).
  const paginatedListings = computed(() => sortedAndFilteredListings.value)

  // Use server-provided page count; fall back to client-side calculation when store
  // is pre-populated via setListings() (e.g. in tests or SSR scenarios).
  const totalPages = computed(() => {
    if (serverLastPage.value > 1) return serverLastPage.value
    return Math.ceil(sortedAndFilteredListings.value.length / itemsPerPage.value) || 1
  })

  const activeFiltersCount = computed(() => {
    const f = filters.value
    const d = DEFAULT_FILTERS
    const pathParams = pathParamsFilters.value
    let count = 0

    // Group 1: Search & Location (counts as one filter if any change)
    // Exclude city if it comes from path params
    const cityChanged = f.city !== d.city && f.city !== pathParams.city
    if (f.keyword !== d.keyword || cityChanged || f.region !== d.region || f.street !== d.street || f.selectedLocationCoords !== d.selectedLocationCoords) {
      count++
    }

    // Group 2: All other filters (dynamic check)
    const skipKeys = ['keyword', 'city', 'region', 'street', 'selectedLocationCoords', 'priceUnit', 'locationLabel', 'cityStrict', '_priceDisplayUnit', 'mapBounds', 'type']
    Object.keys(f).forEach(key => {
      if (skipKeys.includes(key)) return
      if (!(key in d)) return // Skip properties not in DEFAULT_FILTERS
      
      const k = key as keyof FilterParams
      const current = f[k]
      const defaultValue = d[k]

      if (Array.isArray(current)) {
        if (current.length > 0) count++
      } else if (current !== defaultValue) {
        count++
      }
    })

    // Add type filter only if it's NOT from path params
    if (f.type !== d.type && f.type !== pathParams.type) {
      count++
    }

    return count
  })


  const computedPriceDisplayUnit = computed(() => {
    if (sortBy.value && sortBy.value.startsWith('price-')) {
      const sortUnit = sortBy.value.split('-')[1]
      if (sortUnit) return sortUnit as 'day' | 'week' | 'month' | 'year' | 'sqm' | 'campaign'
    }
    
    if ((filters.value.priceFrom !== null || filters.value.priceTo !== null) && filters.value.priceUnit) {
      return filters.value.priceUnit as 'day' | 'week' | 'month' | 'year' | 'sqm' | 'campaign'
    }
    
    return null
  })

  return {
    listings, mapPins, isLoading, filters, sortBy, priceDisplay, viewMode, currentPage, itemsPerPage,
    serverTotal, serverLastPage,
    fetchListings, fetchMapPins, setListings, applyFilters, resetFilters, setViewMode, setCurrentPage, syncFromUrl,
    sortedAndFilteredListings, paginatedListings, totalPages, activeFiltersCount, getPrice,
    computedPriceDisplayUnit,
    getTypeLabel, getStatusLabel, getStatusColor, formatLocation, formatTrafficDirection, getPriceUnitLabel, getVariantLabel, getRoadClassLabel, getTrafficIntensityLabel,
    formatTrafficType, formatEnvironment, formatTransportScope, formatMobileExposureMode, formatLightingType, formatLightingTypeBanner, formatOperatingZone,
    adTypes, priceUnitOptions, sortOptions, getPriceLabel, getAvailablePriceUnits,
    processLocationSuggestions, selectLocationSuggestion, pathParamsFilters
  }
})
