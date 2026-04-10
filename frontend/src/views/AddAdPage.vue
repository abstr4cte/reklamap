<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../services/api'
import { useSeo } from '../composables/useSeo'
import ToastNotification from '../components/ToastNotification.vue'
import LocationMapModal from '../components/LocationMapModal.vue'
import type * as LType from 'leaflet'
import { slugify } from '../utils/slugify'
import { analytics } from '../utils/analytics'
import { useSearchStore, defaultPriceUnitsByType, variantLabels } from '../stores/useSearchStore'
import { mapTypeToUrlFormat } from '../utils/typeMapping'
import { filterWaterFeatures } from '../services/locationService'

const polishMonths = ['Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień']
const polishDays = ['Pn', 'Wt', 'Śr', 'Cz', 'Pt', 'So', 'N']
const dpYears = Array.from({ length: 8 }, (_, i) => new Date().getFullYear() + i)

import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

useSeo({
  title: 'Dodaj powierzchnię reklamową | ReklaMap',
  description: 'Wystaw swoją powierzchnię reklamową na ReklaMap. Dodaj billboard, baner, ekran LED, citylight lub inne miejsce reklamowe i dotrzyj do tysięcy reklamodawców.',
  ogType: 'website',
  canonical: `${window.location.origin}/dodaj-powierzchnie-reklamowa`,
  keywords: 'dodaj ogłoszenie, wystaw powierzchnię reklamową, billboard do wynajęcia, reklama zewnętrzna'
})

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

let profanityFilterInstance: any = null;
const getProfanityFilter = async () => {
  if (profanityFilterInstance) return profanityFilterInstance;
  const { Filter } = await import('bad-words');
  profanityFilterInstance = new Filter();
  const polishBadWords = ['kurwa', 'chuj', 'pizda', 'jebac', 'pierdolic', 'spierdalaj', 'cipa', 'dupa', 'skurwysyn', 'cholera', 'gowno'];
  profanityFilterInstance.addWords(...polishBadWords);
  return profanityFilterInstance;
}

const router = useRouter()
const searchStore = useSearchStore()

const currentStep = ref(1)
const totalSteps = 6

const formData = ref({
  email: '',
  title: '',
  description: '',
  type: '',
  price: null as number | null,
  priceUnit: 'month' as 'day' | 'week' | 'month' | 'year' | 'campaign',
  priceNegotiable: false,
  width: null as number | null,
  height: null as number | null,
  location: '',
  city: '',
  region: '',
  latitude: 52.0,
  longitude: 19.0,
  phone: '',
  countryCode: '+48',
  contactPreference: '' as '' | 'email' | 'phone' | 'both',
  hasBacklight: false,
  graphicDesignHelp: false,
  priceIncludesPrint: false,
  priceIncludesMounting: false,
  hasVatInvoice: false,
  offerType: '' as '' | 'owner' | 'agency',
  status: '' as '' | 'available' | 'reserved' | 'soon_available',
  availableFrom: null as Date | null,
  trafficIntensity: '' as '' | 'low' | 'medium' | 'high',
  imageFiles: [] as { file: File, preview: string, id: string, loading?: boolean }[],
  estimatedDailyViews: null as number | null,
  acceptTerms: false,
  // Nowe pola specyficzne dla typów
  variant: '' as string,
  roadClass: '' as '' | 'highway' | 'expressway' | 'national' | 'regional' | 'local' | 'urban',
  trafficDirection: [] as string[],
  trafficType: [] as string[],
  environment: '' as '' | 'indoor' | 'outdoor' | 'event',
  transportScope: '' as '' | 'internal' | 'external' | 'full_vehicle',
  vehicleCount: null as number | null,
  mobileExposureMode: '' as '' | 'moving' | 'stationary' | 'mixed',
  operatingHours: '',
  routeArea: '',
  campaignDuration: null as number | null,
  // Pola techniczne dla LED screens
  resolution: '' as string,
  pixelPitch: null as number | null,
  brightness: null as number | null,
  // Nowe pola dla rozszerzonych opcji
  lightingType: '' as '' | 'led' | 'fluorescent' | 'natural' | 'none',
  dailyPassengers: null as number | null,
  operatingZone: '' as '' | 'center' | 'periphery' | 'agglomeration',
  ambientLightControl: false,
  // Lighting type dla banerów i ścian
  lightingTypeBanner: '' as '' | 'none' | 'backlight' | 'frontlight',
  subscribeNewsletter: false
})


const minDate = new Date()
minDate.setHours(0, 0, 0, 0)

const formatDate = (date: Date | null): string => {
  if (!date) return ''
  const d = new Date(date)
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const year = d.getFullYear()
  return `${day}.${month}.${year}`
}

const errors = ref<Record<string, string>>({})

// Wyczyść błąd po zmianie wartości pola
watch(() => formData.value, (newVal) => {
  if (newVal.email) delete errors.value.email
  if (newVal.title) delete errors.value.title
  if (newVal.description) delete errors.value.description
  if (newVal.type) delete errors.value.type
  if (newVal.price) delete errors.value.price
  if (newVal.location) delete errors.value.location
  if (newVal.status) delete errors.value.status
  if (newVal.offerType) delete errors.value.offerType
  if (newVal.contactPreference) delete errors.value.contactPreference
  if (newVal.phone) delete errors.value.phone
  if (newVal.roadClass) delete errors.value.roadClass
  if (newVal.trafficIntensity) delete errors.value.trafficIntensity
  if (newVal.variant) delete errors.value.variant
  if (newVal.transportScope) delete errors.value.transportScope
  if (newVal.mobileExposureMode) delete errors.value.mobileExposureMode
  if (newVal.acceptTerms) delete errors.value.acceptTerms
}, { deep: true })
const isSubmitting = ref(false)
const addressSuggestions = ref<any[]>([])
const showAddressSuggestions = ref(false)
const mapContainer = ref<HTMLElement | null>(null)
const showToast = ref(false)
const toastMessage = ref('')
const isResolvingAddress = ref(false)
const showMapModal = ref(false)

const toast = ref<InstanceType<typeof ToastNotification> | null>(null)
const isDragging = ref(false)
const draggedImageIndex = ref<number | null>(null)
const dragOverTarget = ref<number | null>(null)
const isLoadingImages = ref(false)
let map: LType.Map | null = null
let marker: LType.Marker | null = null
let searchTimeout: ReturnType<typeof setTimeout> | null = null

const displayToast = (message: string) => {
  toastMessage.value = message
  showToast.value = true
  window.setTimeout(() => {
    showToast.value = false
  }, 3000)
}

// Funkcja do walidacji i konwersji liczb
const handleNumberInput = (value: string, allowDecimals: boolean = false): string => {
  if (value === '') return ''
  
  // Pozwól tylko na cyfry i opcjonalnie na przecinek/kropkę
  let filtered = value.replace(/[^\d.,]/g, '')
  
  // Zamień przecinek na kropkę dla spójności
  filtered = filtered.replace(',', '.')
  
  // Jeśli nie pozwalamy na decimals, usuń je
  if (!allowDecimals) {
    filtered = filtered.replace(/\./g, '')
  } else {
    // Pozwól tylko na jedną kropkę
    const parts = filtered.split('.')
    if (parts.length > 2) {
      filtered = parts[0] + '.' + parts.slice(1).join('')
    }
  }
  
  return filtered
}

const clearLocation = () => {
  formData.value.location = ''
  formData.value.city = ''
  formData.value.region = ''
  formData.value.latitude = 52.0
  formData.value.longitude = 19.0
  
  if (map && marker) {
    map.setView([52.0, 19.0], 6)
    marker.setLatLng([52.0, 19.0])
  }
}

const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as HTMLElement
  if (!target.closest('.address-input-wrapper')) {
    showAddressSuggestions.value = false
  }
}

const handleBlur = async () => {
  // Delay hiding suggestions to allow click event on suggestion item
  setTimeout(() => {
    showAddressSuggestions.value = false
  }, 300)

  // If location is filled but city/region missing, try to resolve
  if (formData.value.location && (!formData.value.city || !formData.value.region)) {
    await resolveAddressFromInput(formData.value.location)
  }
}

const resolveAddressFromInput = async (query: string) => {
  if (query.length < 3) return

  isResolvingAddress.value = true
  try {
    const response = await fetch(
      `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=pl&limit=1&addressdetails=1`
    )
    const data = await response.json()
    
    if (data && data.length > 0) {
      const suggestion = data[0]
      selectAddress(suggestion)
      // Update location field with the formatted name from API to be sure
      // formData.value.location = suggestion.display_name 
      // (Optional: might annoy user if it changes what they typed too much, but ensures consistency)
    } else {
      // No result found
      displayToast('Nie znaleziono adresu. Sprawdź pisownię lub zaznacz na mapie.')
    }
  } catch (error) {
    // Silently fail
  } finally {
    isResolvingAddress.value = false
  }
}

onMounted(() => {
  // Track start of adding an advertisement
  analytics.startAddAd()

  if (currentStep.value === 3) {
    initMap()
  }
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Automatyczne ustawienie jednostki ceny przy zmianie typu
watch(() => formData.value.type, (newType: string) => {
  // Wyczyść environment jeśli pole nie jest widoczne dla tego typu
  if (!showEnvironmentField.value) {
    formData.value.environment = ''
  }

  if (defaultPriceUnitsByType[newType]) {
    formData.value.priceUnit = defaultPriceUnitsByType[newType] as 'day' | 'week' | 'month' | 'year' | 'campaign'
  }
})

// Computed properties dla dynamicznych opcji
const availablePriceUnits = computed(() => searchStore.getAvailablePriceUnits(formData.value.type))

const variantOptions = computed(() => {
  const type = formData.value.type
  if (!type) return []
  const typeLabels = variantLabels[type] || {}
  return Object.entries(typeLabels).map(([value, label]) => ({ value, label: label as string }))
})

const requiresDimensions = computed(() => {
  const type = formData.value.type
  return ['billboard', 'banner', 'wall', 'led_screen'].includes(type)
})

const hideDimensions = computed(() => {
  const type = formData.value.type
  return ['transport', 'mobile', 'other'].includes(type)
})

// Computed properties for option visibility based on ad type
const showLightingOption = computed(() => {
  const type = formData.value.type
  return ['citylight', 'totem'].includes(type)
})

const showLightingTypeBannerField = computed(() => {
  const type = formData.value.type
  return ['banner', 'wall'].includes(type)
})

const showPrintOption = computed(() => {
  const type = formData.value.type
  return ['billboard', 'banner', 'wall'].includes(type)
})

const showMountingOption = computed(() => {
  const type = formData.value.type
  return ['billboard', 'banner', 'wall', 'totem'].includes(type)
})

const showGraphicDesignOption = computed(() => {
  const type = formData.value.type
  return ['billboard', 'banner', 'wall', 'totem'].includes(type)
})

const showTrafficIntensity = computed(() => {
  const type = formData.value.type
  return ['billboard', 'banner', 'wall', 'totem', 'led_screen'].includes(type)
})

const showTrafficDirection = computed(() => {
  const type = formData.value.type
  return ['billboard', 'banner', 'wall', 'totem'].includes(type)
})

const showTrafficType = computed(() => {
  const type = formData.value.type
  return ['billboard', 'banner', 'wall', 'totem'].includes(type)
})

const showPricePerSqm = computed(() => {
  const type = formData.value.type
  return ['billboard', 'banner', 'wall', 'citylight', 'led_screen', 'totem'].includes(type)
})


const showEnvironmentField = computed(() => {
  const type = formData.value.type
  // Billboard, Ściana, Transport - zawsze outdoor (nie pokazujemy pola)
  if (['billboard', 'wall', 'transport'].includes(type)) return false
  // Citylight, LED, Totem, Banner, Mobile, Other - pokazujemy pole
  return ['citylight', 'led_screen', 'totem', 'banner', 'mobile', 'other'].includes(type)
})

const environmentOptions = computed(() => {
  const type = formData.value.type
  switch (type) {
    case 'citylight':
      return [
        { value: 'indoor', label: 'Wewnątrz (galeria handlowa)' },
        { value: 'outdoor', label: 'Na zewnątrz (ulica)' }
      ]
    case 'led_screen':
      return [
        { value: 'indoor', label: 'Wewnątrz (wewnętrzny)' },
        { value: 'outdoor', label: 'Na zewnątrz (zewnętrzny)' },
        { value: 'event', label: 'Event / Wydarzenie' }
      ]
    case 'totem':
      return [
        { value: 'indoor', label: 'Wewnątrz (galeria)' },
        { value: 'outdoor', label: 'Na zewnątrz (plac, ulica)' },
        { value: 'event', label: 'Event / Wydarzenie' }
      ]
    case 'banner':
      return [
        { value: 'outdoor', label: 'Na zewnątrz (budynek, płot)' },
        { value: 'event', label: 'Event / Wydarzenie' }
      ]
    case 'mobile':
      return [
        { value: 'outdoor', label: 'Na zewnątrz (ulica)' },
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

const transportScopeOptions = computed(() => {
  // Dla przystanku (stop) - tylko opcje wewnętrzna i zewnętrzna
  if (formData.value.variant === 'stop') {
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

// Visibility flags dla nowych pól
const showLightingTypeField = computed(() => {
  return formData.value.type === 'billboard'
})

const showDailyPassengersField = computed(() => {
  return formData.value.type === 'transport'
})

const showOperatingZoneField = computed(() => {
  return formData.value.type === 'mobile'
})

// Opcje dla nowych pól
const lightingTypeOptions = [
  { value: 'led', label: 'LED' },
  { value: 'fluorescent', label: 'Fluorescencyjne' },
  { value: 'natural', label: 'Naturalne' },
  { value: 'none', label: 'Brak' }
]

const operatingZoneOptions = [
  { value: 'center', label: 'Centrum' },
  { value: 'periphery', label: 'Peryferia' },
  { value: 'agglomeration', label: 'Cała aglomeracja' }
]

const surface = computed(() => {
  if (formData.value.width && formData.value.height) {
    return (formData.value.width * formData.value.height).toFixed(2)
  }
  return '0'
})

const pricePerMonth = computed(() => {
  if (!formData.value.price) return 0

  switch (formData.value.priceUnit) {
    case 'day':
      return formData.value.price * 30
    case 'week':
      return formData.value.price * 4
    case 'month':
      return formData.value.price
    case 'year':
      return formData.value.price / 12
    case 'campaign':
      // Przelicz cenę kampanii na miesiąc (zakładając że mamy liczbę dni)
      if (formData.value.campaignDuration && formData.value.campaignDuration > 0) {
        const pricePerDay = formData.value.price / formData.value.campaignDuration
        return pricePerDay * 30
      }
      return 0
    default:
      return 0
  }
})

const pricePerSqm = computed(() => {
  const area = parseFloat(surface.value)
  if (area > 0 && pricePerMonth.value) {
    return (pricePerMonth.value / area).toFixed(2)
  }
  return '0'
})

const calculatePrice = (unit: 'day' | 'week' | 'month' | 'year') => {
  const basePrice = pricePerMonth.value

  switch (unit) {
    case 'day':
      return (basePrice / 30).toFixed(2)
    case 'week':
      return (basePrice / 4).toFixed(2)
    case 'month':
      return basePrice.toFixed(2)
    case 'year':
      return (basePrice * 12).toFixed(2)
    default:
      return '0'
  }
}

const calculateCampaignPrice = (unit: 'day' | 'total') => {
  if (!formData.value.price || !formData.value.campaignDuration) return '0'
  
  switch (unit) {
    case 'day':
      return (formData.value.price / formData.value.campaignDuration).toFixed(2)
    case 'total':
      return formData.value.price.toFixed(2)
    default:
      return '0'
  }
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

const initMap = async () => {
  if (!mapContainer.value || map) return

  await loadLeaflet()
  if (!L) return

  // Granice Polski (bardziej rozszerzone) - aby markery/popupy nie były ucinane
  const polandBounds = L.latLngBounds(
    [47.5, 12.0],  // południowo-zachodni róg
    [57.5, 26.0]   // północno-wschodni róg
  )

  // Center on Poland (approximate geographic center)
  map = L.map(mapContainer.value, {
    maxBounds: polandBounds,        // Nie można przesunąć mapy poza te granice
    maxBoundsViscosity: 1.0,        // Twarde ograniczenie (nie można przeciągnąć poza)
    minZoom: 4.5,                      // Minimalne przybliżenie (cała Polska)
    maxZoom: 18                      // Maksymalne przybliżenie
  }).setView([52.0, 19.0], 6)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map)

  marker = L.marker([formData.value.latitude, formData.value.longitude], {
    draggable: true
  }).addTo(map)

  marker.on('dragend', async () => {
    const position = marker!.getLatLng()
    // First check client-side GeoJSON
    const isInside = await isInPoland(position.lat, position.lng)
    if (!isInside) {
      displayToast('Lokalizacja musi być w Polsce')
      marker!.setLatLng([formData.value.latitude, formData.value.longitude])
      return
    }
    
    // Then verify with Nominatim
    const isValid = await reverseGeocode(position.lat, position.lng)
    if (!isValid) {
      displayToast('Lokalizacja musi być w Polsce')
      marker!.setLatLng([formData.value.latitude, formData.value.longitude])
      return
    }

    formData.value.latitude = position.lat
    formData.value.longitude = position.lng
  })

  map!.on('click', async (e: LType.LeafletMouseEvent) => {
    // First check client-side GeoJSON
    const isInside = await isInPoland(e.latlng.lat, e.latlng.lng)
    if (!isInside) {
      displayToast('Lokalizacja musi być w Polsce')
      return
    }

    // Then verify with Nominatim
    const isValid = await reverseGeocode(e.latlng.lat, e.latlng.lng)
    if (!isValid) {
      displayToast('Lokalizacja musi być w Polsce')
      return
    }

    formData.value.latitude = e.latlng.lat
    formData.value.longitude = e.latlng.lng
    marker!.setLatLng(e.latlng)
  })
}

const reverseGeocode = async (lat: number, lng: number): Promise<boolean> => {
  try {
    const response = await fetch(
      `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`
    )
    const data = await response.json()

    if (data.address) {
      if (data.address.country_code !== 'pl') {
        return false
      }

      const address = data.address
      let city = address.city || address.town || address.village || address.municipality || address.county || address.administrative || ''
      // Usuń prefix "gmina" jeśli pochodzi z municipality
      if (!address.city && !address.town && !address.village && address.municipality) {
        city = city.replace(/^gmina\s+/i, '')
      }
      formData.value.city = city
      formData.value.region = address.state || ''
      // Use full display_name for location field
      formData.value.location = data.display_name || ''
      return true
    }
    return false
  } catch (error) {
    return false
  }
}

const searchAddress = (query: string) => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }

  if (query.length < 3) {
    addressSuggestions.value = []
    showAddressSuggestions.value = false
    isResolvingAddress.value = false
    return
  }

  isResolvingAddress.value = true

  searchTimeout = setTimeout(async () => {
    try {
      const response = await fetch(
        `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=pl&limit=5&addressdetails=1`
      )
      const data = await response.json()
      
      // Filter out water features (rivers, lakes, etc.)
      const filteredData = filterWaterFeatures(data)
      
      addressSuggestions.value = filteredData
      showAddressSuggestions.value = filteredData.length > 0
    } catch (error) {
      // Silently fail
    } finally {
      isResolvingAddress.value = false
    }
  }, 500)
}

const selectAddress = async (suggestion: any) => {
  const address = suggestion.address
  const lat = parseFloat(suggestion.lat)
  const lng = parseFloat(suggestion.lon)

  // Check if location is in Poland
  const isInside = await isInPoland(lat, lng)
  if (!isInside) {
    displayToast('Lokalizacja musi być w Polsce')
    showAddressSuggestions.value = false
    return
  }

  formData.value.location = suggestion.display_name
  let city = address.city || address.town || address.village || address.municipality || address.county  || address.administrative || ''
  // Usuń prefix "gmina" jeśli pochodzi z municipality
  if (!address.city && !address.town && !address.village && address.municipality) {
    city = city.replace(/^gmina\s+/i, '')
  }
  formData.value.city = city
  formData.value.region = address.state || ''
  formData.value.latitude = lat
  formData.value.longitude = lng

  if (map && marker) {
    map.setView([formData.value.latitude, formData.value.longitude], 16)
    marker.setLatLng([formData.value.latitude, formData.value.longitude])
  }

  showAddressSuggestions.value = false
}

const openMapModal = () => {
  showMapModal.value = true
}

const handleMapConfirm = (data: { latitude: number, longitude: number, location: string, city: string, region: string }) => {
  formData.value.latitude = data.latitude
  formData.value.longitude = data.longitude
  formData.value.location = data.location
  formData.value.city = data.city
  formData.value.region = data.region
}

const handleImageUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = target.files
  processFiles(files)
  target.value = ''
}

const handleDrop = (event: DragEvent) => {
  event.preventDefault()
  isDragging.value = false
  const files = event.dataTransfer?.files
  processFiles(files)
}

const processFiles = async (files: FileList | null | undefined) => {
  if (!files) return

  const remainingSlots = 5 - formData.value.imageFiles.length

  if (remainingSlots <= 0) {
    errors.value.image = 'Osiągnięto limit 5 zdjęć'
    return
  }

  if (files.length > remainingSlots) {
    errors.value.image = `Możesz dodać jeszcze tylko ${remainingSlots} zdjęć`
  }

  const filesToProcess = Array.from(files).slice(0, remainingSlots)
  isLoadingImages.value = true

  for (const file of filesToProcess) {
    if (file.size > 5 * 1024 * 1024) {
      errors.value.image = `Plik ${file.name} jest za duży (max 5MB)`
      continue
    }

    // Sprawdź MIME type lub rozszerzenie pliku (telefony czasami nie wysyłają prawidłowego MIME)
    const fileExt = file.name.split('.').pop()?.toLowerCase() || ''
    const allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif', 'avif']
    const isValidImage = file.type.startsWith('image/') || allowedExts.includes(fileExt)
    
    if (!isValidImage) {
      errors.value.image = `Plik ${file.name} nie jest obrazem (obsługiwane: JPG, PNG, HEIC)`
      continue
    }
    
    // Dodaj placeholder z loaderem
    const tempId = `img-${Date.now()}-${Math.random()}`
    formData.value.imageFiles.push({ 
      file, 
      preview: '',
      id: tempId,
      loading: true
    })

    // NSFW Check
    try {
      const { nsfwService } = await import('../services/nsfwService')
      const nsfwResult = await nsfwService.checkImage(file)
      if (!nsfwResult.isSafe) {
        // Usuń placeholder jeśli zdjęcie nie przeszło weryfikacji
        const index = formData.value.imageFiles.findIndex(img => img.id === tempId)
        if (index !== -1) {
          formData.value.imageFiles.splice(index, 1)
        }
        errors.value.image = `Zdjęcie ${file.name} zawiera niedozwolone treści`
        toast.value?.add(`Zdjęcie ${file.name} zostało odrzucone: wykryto treści niedozwolone`, 'error')
        continue
      }
    } catch (error) {
      // Silently fail - continue with upload
    }

    // Wczytaj podgląd zdjęcia
    const reader = new FileReader()
    reader.onload = (e) => {
      const index = formData.value.imageFiles.findIndex(img => img.id === tempId)
      if (index !== -1) {
        // Aktualizuj istniejący placeholder
        formData.value.imageFiles[index].preview = e.target?.result as string
        formData.value.imageFiles[index].loading = false
      }
      
      // Sprawdź czy wszystkie zdjęcia zostały wczytane
      const stillLoading = formData.value.imageFiles.some(img => img.loading === true)
      if (!stillLoading) {
        isLoadingImages.value = false
      }
    }
    reader.readAsDataURL(file)
    delete errors.value.image
  }
  
  // Jeśli nie ma żadnych zdjęć do wczytania, wyłącz loader
  if (filesToProcess.length === 0) {
    isLoadingImages.value = false
  }
}

const handleImageDragStart = (event: DragEvent, index: number) => {
  draggedImageIndex.value = index
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', index.toString())
  }
}

const handleImageDragOver = (event: DragEvent, index: number) => {
  event.preventDefault()
  event.stopPropagation()
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'move'
  }
  
  if (draggedImageIndex.value !== null) {
    if (draggedImageIndex.value === index) return
    
    if (dragOverTarget.value !== index) {
      dragOverTarget.value = index
    }
  }
}

const handleDragEnd = () => {
  draggedImageIndex.value = null
  dragOverTarget.value = null
}

const handleImageDrop = (event: DragEvent, targetIndex: number) => {
  event.preventDefault()
  event.stopPropagation()
  
  dragOverTarget.value = null
  
  if (draggedImageIndex.value === null) return

  const sourceIndex = draggedImageIndex.value

  if (sourceIndex === targetIndex) {
    draggedImageIndex.value = null
    return
  }

  const items = [...formData.value.imageFiles]
  const [movedItem] = items.splice(sourceIndex, 1)
  items.splice(targetIndex, 0, movedItem)
  
  formData.value.imageFiles = items
  draggedImageIndex.value = null
}

const removeImage = (index: number) => {
  formData.value.imageFiles.splice(index, 1)
}

const validateStep = (step: number): boolean => {
  errors.value = {}

  switch (step) {
    case 1:
      if (!formData.value.email) {
        errors.value.email = 'E-mail jest wymagany'
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.value.email)) {
        errors.value.email = 'Nieprawidłowy format e-mail'
      }
      if (!formData.value.title) {
        errors.value.title = 'Tytuł jest wymagany'
      } else if (formData.value.title.length > 200) {
        errors.value.title = 'Tytuł nie może być dłuższy niż 200 znaków'
      }
      if (!formData.value.description) {
        errors.value.description = 'Opis jest wymagany'
      } else if (formData.value.description.length > 5000) {
        errors.value.description = 'Opis nie może być dłuższy niż 5000 znaków'
      }
      if (!formData.value.type) {
        errors.value.type = 'Rodzaj powierzchni jest wymagany'
      }
      break

    case 2:
      if (!formData.value.price || formData.value.price <= 0) {
        errors.value.price = 'Cena jest wymagana'
      } else if (formData.value.price > 999999) {
        errors.value.price = 'Cena nie może przekraczać 999 999 zł'
      }
      // Walidacja czasu trwania kampanii
      if (formData.value.priceUnit === 'campaign') {
        if (!formData.value.campaignDuration || formData.value.campaignDuration <= 0) {
          errors.value.campaignDuration = 'Czas trwania kampanii jest wymagany'
        }
      }
      break

    case 3:
      // Wymiary wymagane dla billboard, banner, wall, citylight
      if (requiresDimensions.value) {
        const isLed = formData.value.type === 'led_screen'
        const maxW = isLed ? 500000 : 500   // LED: mm (500000mm = 500m), inne: m
        const maxH = isLed ? 100000 : 100
        const unit = isLed ? 'mm' : 'm'
        if (!formData.value.width || formData.value.width <= 0) {
          errors.value.width = 'Szerokość jest wymagana'
        } else if (formData.value.width > maxW) {
          errors.value.width = `Szerokość nie może przekraczać ${maxW} ${unit}`
        }
        if (!formData.value.height || formData.value.height <= 0) {
          errors.value.height = 'Wysokość jest wymagana'
        } else if (formData.value.height > maxH) {
          errors.value.height = `Wysokość nie może przekraczać ${maxH} ${unit}`
        }
      }
      if (!formData.value.location) {
        errors.value.location = 'Lokalizacja jest wymagana'
      }
      if (!formData.value.contactPreference) {
        errors.value.contactPreference = 'Opcja kontaktu jest wymagana'
      }
      if (formData.value.contactPreference === 'phone' || formData.value.contactPreference === 'both') {
        if (!formData.value.phone) {
          errors.value.phone = 'Numer telefonu jest wymagany dla wybranej opcji kontaktu'
        } else if (formData.value.phone.length !== 9) {
          errors.value.phone = 'Numer telefonu musi mieć dokładnie 9 cyfr'
        } else if (!/^[0-9]{9}$/.test(formData.value.phone)) {
          errors.value.phone = 'Numer telefonu może zawierać tylko cyfry'
        }
      }
      break

    case 4:
      // Klasa drogi wymagana dla billboardów
      if (formData.value.type === 'billboard' && !formData.value.roadClass) {
        errors.value.roadClass = 'Klasa drogi jest wymagana dla billboardów'
      }
      // Natężenie ruchu wymagane dla wszystkich typów outdoor
      if (['billboard', 'banner', 'wall', 'totem', 'led_screen'].includes(formData.value.type) && !formData.value.trafficIntensity) {
        errors.value.trafficIntensity = 'Natężenie ruchu jest wymagane'
      }
      if (!formData.value.status) {
        errors.value.status = 'Status dostępności jest wymagany'
      }
      if (formData.value.status === 'soon_available' && !formData.value.availableFrom) {
        errors.value.availableFrom = 'Data dostępności jest wymagana'
      }
      if (!formData.value.offerType) {
        errors.value.offerType = 'Rodzaj oferty jest wymagany'
      }
      // Walidacja dla transportu
      if (formData.value.type === 'transport') {
        if (!formData.value.transportScope) {
          errors.value.transportScope = 'Zakres reklamy jest wymagany'
        }
      }
      // Walidacja wariantu dla typów które go mają
      const typesWithVariant = ['billboard', 'citylight', 'led_screen', 'totem', 'transport', 'mobile']
      if (typesWithVariant.includes(formData.value.type)) {
        if (!formData.value.variant) {
          errors.value.variant = 'Wariant jest wymagany'
        }
      }
      // Walidacja dla mobilnej
      if (formData.value.type === 'mobile') {
        if (!formData.value.mobileExposureMode) {
          errors.value.mobileExposureMode = 'Tryb ekspozycji jest wymagany'
        }
      }
      // Walidacja resolution dla LED screens (format: WxH)
      if (formData.value.type === 'led_screen' && formData.value.resolution) {
        if (!/^\d+x\d+$/i.test(formData.value.resolution)) {
          errors.value.resolution = 'Rozdzielczość musi być w formacie WxH (np. 1920x1080)'
        }
      }
      // Walidacja pixel pitch
      if (formData.value.type === 'led_screen' && formData.value.pixelPitch !== null) {
        if (formData.value.pixelPitch < 0.1 || formData.value.pixelPitch > 100) {
          errors.value.pixelPitch = 'Pixel Pitch musi być między 0.1 a 100 mm'
        }
      }
      // Walidacja jasności
      if (formData.value.type === 'led_screen' && formData.value.brightness !== null) {
        if (formData.value.brightness < 1000 || formData.value.brightness > 15000) {
          errors.value.brightness = 'Jasność musi być między 1000 a 15000 nits'
        }
      }
      break

    case 5:
      // Zdjęcia opcjonalne, ale jeśli są, muszą być przynajmniej 1
      // Można dodać walidację jeśli chcemy wymagać zdjęć
      break

    case 6:
      if (!formData.value.acceptTerms) {
        errors.value.acceptTerms = 'Musisz zaakceptować regulamin i politykę prywatności'
      }
      break
  }

  return Object.keys(errors.value).length === 0
}

const nextStep = async () => {
  if (validateStep(currentStep.value)) {
    if (currentStep.value < totalSteps) {
      currentStep.value++
      if (currentStep.value === 3) {
        setTimeout(() => initMap(), 100)
      }
      // Czekaj na aktualizację DOM zanim przewiniesz — zmiana v-show może skrócić
      // stronę w trakcie smooth scroll, co powoduje lądowanie na footerze
      await nextTick()
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }
  } else {
    toast.value?.add('Proszę uzupełnić wymagane pola', 'error')
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const validateField = async (field: 'title' | 'description') => {
  const value = formData.value[field];
  if (!value) return; // Don't validate empty fields on blur

  // Check for links
  if (/(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i.test(value)) {
    errors.value[field] = `Pole ${field === 'title' ? 'Tytuł' : 'Opis'} nie może zawierać linków.`;
    return;
  }

  // Check for profanity
  const profanityFilter = await getProfanityFilter();
  if (profanityFilter.isProfane(value)) {
    errors.value[field] = `Pole ${field === 'title' ? 'Tytuł' : 'Opis'} zawiera niedozwolone słowa.`;
    return;
  }

  // Clear error if validation passes
  delete errors.value[field];
}

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const uploadImages = async (): Promise<string[]> => {
  if (formData.value.imageFiles.length === 0) {
    return []
  }

  const failedErrors: string[] = []
  
  const uploadPromises = formData.value.imageFiles.map(async (item) => {
    try {
      const url = await api.storage.upload(item.file)
      return url
    } catch (error: any) {
      const msg = error?.message || 'Nieznany błąd'
      failedErrors.push(`"${item.file.name}": ${msg}`)
      return ''
    }
  })

  const results = await Promise.all(uploadPromises)
  const validUrls = results.filter(url => url !== '')

  if (failedErrors.length > 0) {
    // Pokaż szczegółowy błąd dla pojedynczego zdjęcia
    if (failedErrors.length === 1) {
      toast.value?.add(`Nie udało się przesłać zdjęcia: ${failedErrors[0]}`, 'error')
    } else {
      // Dla wielu zdjęć pokaż ogólny komunikat
      toast.value?.add(`Nie udało się przesłać ${failedErrors.length} zdjęć. Sprawdź format pliku (JPG, PNG, HEIC).`, 'error')
    }
  }

  return validUrls
}

const handleSubmit = async () => {
  if (!validateStep(currentStep.value)) {
    window.scrollTo({ top: 0, behavior: 'smooth' })
    toast.value?.add('Proszę uzupełnić wymagane pola', 'error')
    return
  }

  try {
    isSubmitting.value = true

    const imageUrls = await uploadImages()
    const mainImageUrl = imageUrls.length > 0 ? imageUrls[0] : ''
    
    // Konwertuj wymiary LCD z mm na metry
    let width = formData.value.width || 0
    let height = formData.value.height || 0
    if (formData.value.type === 'led_screen') {
      width = width / 1000  // mm -> m
      height = height / 1000  // mm -> m
    }
    
    const newAd = await api.createAdvertisement({
        owner_email: formData.value.email,
        title: formData.value.title,
        description: formData.value.description,
        type: formData.value.type,
        price: formData.value.price!,
        price_unit: formData.value.priceUnit,
        price_negotiable: formData.value.priceNegotiable,
        width: width,
        height: height,
        orientation: (formData.value.width && formData.value.height) 
          ? (formData.value.width >= formData.value.height ? 'horizontal' : 'vertical')
          : 'horizontal',
        location: formData.value.location,
        city: formData.value.city,
        region: formData.value.region,
        latitude: formData.value.latitude,
        longitude: formData.value.longitude,
        phone: formData.value.phone ? `+48${formData.value.phone}` : '',
        contact_preference: formData.value.contactPreference,
        has_backlight: formData.value.hasBacklight,
        graphic_design_help: formData.value.graphicDesignHelp,
        price_includes_print: formData.value.priceIncludesPrint,
        has_vat_invoice: formData.value.hasVatInvoice,
        status: formData.value.status === 'available' ? 'active' : formData.value.status,
        available_from: formData.value.availableFrom 
          ? (() => {
              const d = new Date(formData.value.availableFrom)
              const year = d.getFullYear()
              const month = String(d.getMonth() + 1).padStart(2, '0')
              const day = String(d.getDate()).padStart(2, '0')
              return `${year}-${month}-${day}`
            })()
          : new Date().toISOString().split('T')[0],
        traffic_intensity: formData.value.trafficIntensity,
        rental_period: (['day', 'week'].includes(formData.value.priceUnit)) ? 'short_term' : 'long_term',
        image_url: mainImageUrl,
        has_image: imageUrls.length > 0,
        offer_type: formData.value.offerType,
        is_active: true,
        images: imageUrls,
        // Nowe pola specyficzne dla typów
        variant: formData.value.variant || null,
        road_class: formData.value.roadClass || null,
        traffic_direction: formData.value.trafficDirection.length > 0 
          ? formData.value.trafficDirection 
          : null,
        traffic_type: formData.value.trafficType.length > 0 
          ? formData.value.trafficType 
          : null,
        environment: formData.value.environment || null,
        resolution: formData.value.resolution || null,
        pixel_pitch: formData.value.pixelPitch || null,
        brightness: formData.value.brightness || null,
        transport_scope: formData.value.transportScope || null,
        vehicle_count: formData.value.vehicleCount || null,
        mobile_exposure_mode: formData.value.mobileExposureMode || null,
        operating_hours: formData.value.operatingHours || null,
        route_area: formData.value.routeArea || null,
        price_includes_mounting: formData.value.priceIncludesMounting,
        campaign_duration: formData.value.campaignDuration || null,
        // Nowe pola dla rozszerzonych opcji
        lighting_type: formData.value.lightingType || null,
        daily_passengers: formData.value.dailyPassengers || null,
        operating_zone: formData.value.operatingZone || null,
        ambient_light_control: formData.value.ambientLightControl,
        lighting_type_banner: formData.value.lightingTypeBanner || null,
        estimated_daily_views: formData.value.estimatedDailyViews || null,
        subscribe_newsletter: formData.value.subscribeNewsletter

    } as any) // Casting to any to avoid strict type checks for now if interface mismatches

    if (newAd && newAd.id) {
      // Track conversion in GA4
      analytics.finishAddAd(formData.value.type, formData.value.city)
      
      // Update global listings cache so the new ad is visible elsewhere immediately
      searchStore.fetchListings()
      
      toast.value?.add('Ogłoszenie zostało dodane pomyślnie!', 'success')
      setTimeout(() => {
        // Generowanie linku w nowym formacie SEO-friendly
        const type = mapTypeToUrlFormat(formData.value.type)
        const city = slugify(formData.value.city)
        const title = slugify(formData.value.title)
        
        // Redirection with a slight delay and ensuring window scroll
        setTimeout(() => {
          window.scrollTo(0, 0)
          router.push(`/powierzchnia-reklamowa/${type || 'inne'}/${city || 'polska'}/${title || 'ogloszenie'}-${newAd.id}`)
        }, 100)
      }, 1000)
    } else {
      router.push('/')
    }
  } catch (error: any) {
    isSubmitting.value = false;

    if (error.response && error.response.data && error.response.data.errors) {
      const fieldTranslations: Record<string, string> = {
        title: 'Tytuł',
        description: 'Opis',
        email: 'E-mail',
        type: 'Rodzaj powierzchni',
        price: 'Cena',
        location: 'Lokalizacja',
        city: 'Miasto',
        region: 'Region',
        owner_email: 'E-mail właściciela',
        variant: 'Wariant',
        road_class: 'Klasa drogi',
        traffic_intensity: 'Natężenie ruchu',
        status: 'Status',
        available_from: 'Data dostępności',
        offer_type: 'Typ oferty',
        contact_preference: 'Preferowany kontakt',
        phone: 'Telefon',
        width: 'Szerokość',
        height: 'Wysokość',
        campaign_duration: 'Czas trwania kampanii',
        pixel_pitch: 'Pixel Pitch',
        brightness: 'Jasność',
        resolution: 'Rozdzielczość',
      };

      const fieldRanges: Record<string, string> = {
        pixel_pitch: '0.1–100 mm',
        brightness: '1000–15000 nits',
      };

      const serverErrors = error.response.data.errors;
      const newErrors: Record<string, string> = {};
      let toastMessage = 'Formularz zawiera błędy:';

      for (const key in serverErrors) {
        const translatedField = fieldTranslations[key] || key;
        let message = serverErrors[key][0];

        // Tłumaczenie kluczy walidacji Laravel
        if (message === 'validation.required') {
          message = `Pole ${translatedField} jest wymagane`;
        } else if (message === 'validation.email') {
          message = `Pole ${translatedField} musi być prawidłowym adresem e-mail`;
        } else if (message === 'validation.numeric') {
          message = `Pole ${translatedField} musi być liczbą`;
        } else if (message === 'validation.integer') {
          message = `Pole ${translatedField} musi być liczbą całkowitą`;
        } else if (message === 'validation.string') {
          message = `Pole ${translatedField} musi być tekstem`;
        } else if (message.startsWith('validation.min.')) {
          message = `Pole ${translatedField} jest za małe`;
        } else if (message.startsWith('validation.max.')) {
          message = `Pole ${translatedField} przekracza dozwoloną wartość`;
        } else if (message.startsWith('validation.between.')) {
          const range = fieldRanges[key];
          message = range
            ? `${translatedField}: dozwolony zakres to ${range}`
            : `Pole ${translatedField} ma niedozwoloną wartość`;
        } else if (message.includes('validation.')) {
          message = `Pole ${translatedField} ma nieprawidłową wartość`;
        } else {
          message = message.replace(key, translatedField);
        }
        
        newErrors[key] = message;
        toastMessage += `\n- ${message}`;
      }

      errors.value = newErrors;
      toast.value?.add(toastMessage, 'error', 8000);
    } else {
      toast.value?.add(error.message || 'Wystąpił nieoczekiwany błąd. Spróbuj ponownie.', 'error');
    }
  }
}


// Mapowanie typów powierzchni reklamowych pobieramy z narzędzia typeMapping

// Resetuj transportScope gdy wariant się zmieni
watch(() => formData.value.variant, () => {
  if (formData.value.type === 'transport') {
    // Jeśli zmieniliśmy wariant na 'stop' i mamy 'full_vehicle', resetuj
    if (formData.value.variant === 'stop' && formData.value.transportScope === 'full_vehicle') {
      formData.value.transportScope = ''
    }
  }
})

onMounted(() => {
  if (currentStep.value === 3) {
    initMap()
  }
})
</script>

<template>
  <div class="add-listing-page-root">
    <div class="add-listing-page">
    <!-- Toast Notification -->
    <ToastNotification ref="toast" />
    
    <!-- Legacy Toast (for map errors) -->
    <div v-if="showToast" class="toast-notification">
      {{ toastMessage }}
    </div>

    <div class="page-container">
      <div class="page-header">
        <button @click="currentStep > 1 ? prevStep() : router.back()" class="back-button">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M15 10H5M5 10L10 15M5 10L10 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          {{ currentStep > 1 ? 'Poprzedni krok' : 'Powrót' }}
        </button>
        <div class="header-content">
          <h1>Dodaj ogłoszenie</h1>
          <p class="subtitle">Wypełnij formularz, aby dodać swoją powierzchnię reklamową</p>
        </div>
      </div>

      <div class="progress-bar">
        <div
          v-for="step in totalSteps"
          :key="step"
          class="progress-step"
          :class="{ active: step <= currentStep, current: step === currentStep }"
        >
          <div class="step-number">{{ step }}</div>
          <div class="step-label" v-if="step === 1">Podstawy</div>
          <div class="step-label" v-if="step === 2">Cena</div>
          <div class="step-label" v-if="step === 3">Lokalizacja</div>
          <div class="step-label" v-if="step === 4">Opcje</div>
          <div class="step-label" v-if="step === 5">Zdjęcie</div>
          <div class="step-label" v-if="step === 6">Zgody</div>
        </div>
      </div>

      <form @submit.prevent="currentStep === totalSteps ? handleSubmit() : nextStep()" class="form-content">
        <div v-show="currentStep === 1" class="step-section">
          <h2>Dane podstawowe</h2>

          <div class="form-group">
            <label class="form-label">Adres e-mail <span class="required">*</span></label>
            <input
              v-model="formData.email"
              type="text"
              class="form-input"
              :class="{ 'error': errors.email }"
              placeholder="twoj@email.pl"
            />
            <span v-if="errors.email" class="error-text">{{ errors.email }}</span>
          </div>

          <div class="form-group">
            <label class="form-label">Rodzaj powierzchni <span class="required">*</span></label>
            <select v-model="formData.type" class="form-select" :class="{ 'error': errors.type }">
              <option value="">Wybierz rodzaj</option>
              <option v-for="type in searchStore.adTypes" :key="type.value" :value="type.value">
                {{ type.label }}
              </option>
            </select>
            <span v-if="errors.type" class="error-text">{{ errors.type }}</span>
          </div>

          <div class="form-group">
            <label class="form-label">Tytuł ogłoszenia <span class="required">*</span></label>
            <input
              v-model="formData.title"
              type="text"
              class="form-input"
              :class="{ 'error': errors.title }"
              placeholder="np. Billboard przy autostradzie A1"
              maxlength="100"
            />
            <div class="char-counter" :class="{ 'near-limit': formData.title.length > 80 }">
              {{ formData.title.length }}/100 znaków
            </div>
            <span v-if="errors.title" class="error-text">{{ errors.title }}</span>
          </div>

          <div class="form-group">
            <label class="form-label">Opis <span class="required">*</span></label>
            <textarea
              id="description"
              v-model="formData.description"
              @blur="validateField('description')"
              rows="5"
              class="form-textarea"
              :class="{ 'error': errors.description }"
              placeholder="Szczegółowy opis powierzchni reklamowej..."
              maxlength="2000"
            ></textarea>
            <div class="char-counter" :class="{ 'near-limit': formData.description.length > 1800 }">
              {{ formData.description.length }}/2000 znaków
            </div>
            <span v-if="errors.description" class="error-text">{{ errors.description }}</span>
          </div>
        </div>

        <div v-show="currentStep === 2" class="step-section">
          <h2>Cena i jednostka</h2>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Cena <span class="required">*</span></label>
              <input
                :value="formData.price"
                @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, true); formData.price = val ? parseFloat(val) : null }"
                type="text"
                class="form-input"
                :class="{ 'error': errors.price }"
                placeholder="1000"
              />
              <span v-if="errors.price" class="error-text">{{ errors.price }}</span>
            </div>

            <div class="form-group">
              <label class="form-label">Jednostka <span class="required">*</span></label>
              <select v-model="formData.priceUnit" class="form-select">
                <option v-for="unit in availablePriceUnits" :key="unit.value" :value="unit.value">
                  {{ unit.label }}
                </option>
              </select>
            </div>
          </div>

          <!-- Czas trwania kampanii - tylko dla jednostki 'kampania' -->
          <div v-if="formData.priceUnit === 'campaign'" class="form-group">
            <label class="form-label">Czas trwania kampanii (dni) <span class="required">*</span></label>
            <input
              :value="formData.campaignDuration"
              @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, false); formData.campaignDuration = val ? parseInt(val) : null }"
              type="text"
              class="form-input"
              :class="{ 'error': errors.campaignDuration }"
              placeholder="30"
            />
            <span v-if="errors.campaignDuration" class="error-text">{{ errors.campaignDuration }}</span>
          </div>

          <!-- Checkbox: Cena do negocjacji -->
          <div class="form-group">
            <label class="checkbox-option">
              <input type="checkbox" v-model="formData.priceNegotiable" />
              <span>Cena do negocjacji</span>
            </label>
          </div>

          <!-- Właściwości faktury i ceny -->
          <div class="checkbox-group">
            <label class="checkbox-option">
              <input type="checkbox" v-model="formData.hasVatInvoice" />
              <span>Faktura VAT</span>
            </label>
          </div>

          <!-- Przeliczenia cenowe dla standardowych jednostek -->
          <div v-if="formData.price && formData.priceUnit !== 'campaign'" class="price-info-box" style="margin-top: 1.5rem;">
            <h3>Przeliczenia cenowe</h3>
            <div class="price-grid">
              <div class="price-item">
                <span class="price-label">Dzień:</span>
                <span class="price-value">{{ calculatePrice('day') }} PLN</span>
              </div>
              <div class="price-item">
                <span class="price-label">Tydzień:</span>
                <span class="price-value">{{ calculatePrice('week') }} PLN</span>
              </div>
              <div class="price-item">
                <span class="price-label">Miesiąc:</span>
                <span class="price-value">{{ calculatePrice('month') }} PLN</span>
              </div>
              <div class="price-item">
                <span class="price-label">Rok:</span>
                <span class="price-value">{{ calculatePrice('year') }} PLN</span>
              </div>
            </div>
            <div v-if="showPricePerSqm && formData.width && formData.height" class="price-per-sqm">
              <strong>Cena za m²:</strong> {{ pricePerSqm }} PLN/m² (miesięcznie)
            </div>
          </div>

          <!-- Przeliczenia cenowe dla kampanii -->
          <div v-if="formData.price && formData.priceUnit === 'campaign' && formData.campaignDuration" class="price-info-box" style="margin-top: 1.5rem;">
            <h3>Przeliczenia cenowe kampanii</h3>
            <div class="price-grid">
              <div class="price-item">
                <span class="price-label">Cena całkowita ({{ formData.campaignDuration }} dni):</span>
                <span class="price-value">{{ calculateCampaignPrice('total') }} PLN</span>
              </div>
              <div class="price-item">
                <span class="price-label">Cena za dzień:</span>
                <span class="price-value">{{ calculateCampaignPrice('day') }} PLN</span>
              </div>
              <div class="price-item">
                <span class="price-label">Przeliczenie na miesiąc:</span>
                <span class="price-value">{{ calculatePrice('month') }} PLN</span>
              </div>
              <div class="price-item">
                <span class="price-label">Przeliczenie na rok:</span>
                <span class="price-value">{{ calculatePrice('year') }} PLN</span>
              </div>
            </div>
            <div v-if="showPricePerSqm && formData.width && formData.height" class="price-per-sqm">
              <strong>Cena za m²:</strong> {{ pricePerSqm }} PLN/m² (miesięcznie)
            </div>
          </div>
        </div>

        <div v-show="currentStep === 3" class="step-section">
          <h2>{{ hideDimensions ? 'Lokalizacja' : 'Wymiary i lokalizacja' }}</h2>

          <!-- Wymiary dla LED screens (mm) -->
          <div v-if="formData.type === 'led_screen'" class="form-row">
            <div class="form-group">
              <label class="form-label">Szerokość (mm) <span class="required">*</span></label>
              <input
                :value="formData.width"
                @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, false); formData.width = val ? parseInt(val) : null }"
                type="text"
                class="form-input"
                :class="{ 'error': errors.width }"
                placeholder="2000"
              />
              <span v-if="errors.width" class="error-text">{{ errors.width }}</span>
              <p class="help-text" style="margin-top: 0.25rem; margin-bottom: 0; font-size: 0.8rem; color: #9ca3af;">Szerokość ekranu w milimetrach</p>
            </div>

            <div class="form-group">
              <label class="form-label">Wysokość (mm) <span class="required">*</span></label>
              <input
                :value="formData.height"
                @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, false); formData.height = val ? parseInt(val) : null }"
                type="text"
                class="form-input"
                :class="{ 'error': errors.height }"
                placeholder="1000"
              />
              <span v-if="errors.height" class="error-text">{{ errors.height }}</span>
              <p class="help-text" style="margin-top: 0.25rem; margin-bottom: 0; font-size: 0.8rem; color: #9ca3af;">Wysokość ekranu w milimetrach</p>
            </div>
          </div>

          <!-- Wymiary dla pozostałych typów (m) -->
          <div v-if="!hideDimensions && formData.type !== 'led_screen'" class="form-row">
            <div class="form-group">
              <label class="form-label">
                Szerokość (m) 
                <span v-if="requiresDimensions" class="required">*</span>
              </label>
              <input
                :value="formData.width"
                @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, true); formData.width = val ? parseFloat(val) : null }"
                type="text"
                class="form-input"
                :class="{ 'error': errors.width }"
                placeholder="2"
              />
              <span v-if="errors.width" class="error-text">{{ errors.width }}</span>
            </div>

            <div class="form-group">
              <label class="form-label">Wysokość (m) <span class="required">*</span></label>
              <input
                :value="formData.height"
                @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, true); formData.height = val ? parseFloat(val) : null }"
                type="text"
                class="form-input"
                :class="{ 'error': errors.height }"
                placeholder="1"
              />
              <span v-if="errors.height" class="error-text">{{ errors.height }}</span>
            </div>
          </div>

          <!-- Powierzchnia i cena za m² (dla LED i billboardów) -->
          <div v-if="!hideDimensions && formData.width && formData.height" class="surface-display">
            <strong>Powierzchnia:</strong> 
            <span v-if="formData.type === 'led_screen'">{{ (formData.width * formData.height / 1000000).toFixed(2) }} m²</span>
            <span v-else>{{ surface }} m²</span>
            <span v-if="formData.price" class="surface-price">
              ({{ pricePerSqm }} PLN/m²)
            </span>
          </div>

          <div class="form-group">
            <label class="form-label">Lokalizacja <span class="required">*</span></label>
            <div class="location-input-group">
              <div class="address-input-wrapper">
                <input
                  v-model="formData.location"
                  type="text"
                  class="form-input"
                  :class="{ 'error': errors.location }"
                  placeholder="Wpisz adres (np. ul. Marszałkowska 1, Warszawa)"
                  @input="searchAddress(formData.location)"
                  @blur="handleBlur"
                />
                <div v-if="isResolvingAddress" class="input-spinner">
                  <svg class="spinner-icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                </div>
                <button 
                  v-if="formData.location && !isResolvingAddress" 
                  type="button" 
                  @click="clearLocation" 
                  class="clear-input-btn"
                  title="Wyczyść lokalizację"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </button>
                <div v-if="showAddressSuggestions && addressSuggestions.length > 0" class="address-suggestions">
                  <div
                    v-for="suggestion in addressSuggestions"
                    :key="suggestion.place_id"
                    class="suggestion-item"
                    @click="selectAddress(suggestion)"
                  >
                    {{ suggestion.display_name }}
                  </div>
                </div>
              </div>
              <button type="button" @click="openMapModal" class="map-button-modern">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                  <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2"/>
                </svg>
                <span>Znajdź na mapie</span>
              </button>
            </div>
            <span v-if="errors.location" class="error-text">{{ errors.location }}</span>
          </div>

          <div class="map-container-desktop" ref="mapContainer"></div>
          <p class="map-hint-desktop">Kliknij na mapie lub przeciągnij marker, aby ustawić lokalizację</p>

          <div class="form-group">
            <label class="form-label">Opcje kontaktu <span class="required">*</span></label>
            <select v-model="formData.contactPreference" class="form-select" :class="{ 'error': errors.contactPreference }">
              <option value="" disabled>Wybierz opcję kontaktu</option>
              <option value="email">Tylko formularz kontaktowy</option>
              <option value="phone">Tylko telefon</option>
              <option value="both">Formularz i telefon</option>
            </select>
            <span v-if="errors.contactPreference" class="error-text">{{ errors.contactPreference }}</span>
          </div>

          <div v-if="formData.contactPreference === 'phone' || formData.contactPreference === 'both'" class="form-group">
            <label class="form-label">Numer telefonu <span class="required">*</span></label>
            <div class="phone-input-with-prefix">
              <div class="phone-prefix">
                <svg class="flag-icon" viewBox="0 0 640 480" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                  <rect width="640" height="240" fill="#fff"/>
                  <rect y="240" width="640" height="240" fill="#dc143c"/>
                </svg>
                <span>+48</span>
              </div>
              <input
                v-model="formData.phone"
                type="tel"
                class="form-input phone-input-field"
                :class="{ 'error': errors.phone }"
                placeholder="123 456 789"
                maxlength="9"
                @input="formData.phone = formData.phone.replace(/[^0-9]/g, '')"
              />
            </div>
            <span v-if="errors.phone" class="error-text">{{ errors.phone }}</span>
          </div>
        </div>

        <div v-show="currentStep === 4" class="step-section">
          <h2>Opcje i cechy specjalne</h2>

          <!-- Klasa drogi - REQUIRED dla billboardów -->
          <div v-if="formData.type === 'billboard'" class="form-group">
            <label class="form-label">Klasa drogi <span class="required">*</span></label>
            <select v-model="formData.roadClass" class="form-select" :class="{ 'error': errors.roadClass }">
              <option value="" disabled>Wybierz klasę drogi</option>
              <option value="highway">Autostrada</option>
              <option value="expressway">Droga ekspresowa</option>
              <option value="national">Droga krajowa</option>
              <option value="regional">Droga wojewódzka</option>
              <option value="local">Droga lokalna</option>
              <option value="urban">Droga miejska</option>
            </select>
            <span v-if="errors.roadClass" class="error-text">{{ errors.roadClass }}</span>
          </div>

          <!-- Natężenie ruchu - REQUIRED dla wszystkich outdoor -->
          <div v-if="showTrafficIntensity" class="form-group">
            <label class="form-label">
              Natężenie ruchu 
              <span class="required">*</span>
            </label>
            <select v-model="formData.trafficIntensity" class="form-select" :class="{ 'error': errors.trafficIntensity }">
              <option value="" disabled>Wybierz natężenie ruchu</option>
              <option value="low">Niskie</option>
              <option value="medium">Średnie</option>
              <option value="high">Wysokie</option>
            </select>
            <span v-if="errors.trafficIntensity" class="error-text">{{ errors.trafficIntensity }}</span>
          </div>

          <!-- Kierunek ruchu - dla wszystkich outdoor -->
          <div v-if="showTrafficDirection" class="form-group">
            <label class="form-label">Kierunek ruchu (opcjonalnie)</label>
            <div class="checkbox-group">
              <label class="checkbox-option">
                <input type="checkbox" value="entry" v-model="formData.trafficDirection" />
                <span>Wjazd do miasta</span>
              </label>
              <label class="checkbox-option">
                <input type="checkbox" value="exit" v-model="formData.trafficDirection" />
                <span>Wyjazd z miasta</span>
              </label>
            </div>
            <p class="help-text" style="margin-top: 0.5rem; font-size: 0.875rem; color: #6b7280;">
              Zaznacz oba, jeśli powierzchnia widoczna z obu stron
            </p>
          </div>

          <!-- OTS / Daily Views -->
          <div v-if="['billboard', 'citylight', 'led_screen', 'banner', 'wall', 'totem'].includes(formData.type)" class="form-group">
            <label class="form-label">Szacowana dzienna liczba odbiorców (OTS)</label>
            <input
              :value="formData.estimatedDailyViews"
              @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, false); formData.estimatedDailyViews = val ? parseInt(val) : null }"
              type="text"
              class="form-input"
              placeholder="np. 25000"
            />
            <p class="help-text" style="margin-top: 0.25rem; margin-bottom: 0; font-size: 0.8rem; color: #9ca3af;">Przybliżona liczba osób, które dziennie widzą reklamę (Opportunity To See)</p>
          </div>

          <!-- Rodzaj ruchu - dla wszystkich outdoor -->
          <div v-if="showTrafficType" class="form-group">
            <label class="form-label">Rodzaj ruchu (opcjonalnie)</label>
            <div class="checkbox-group">
              <label class="checkbox-option">
                <input type="checkbox" value="pedestrian" v-model="formData.trafficType" />
                <span>Pieszy</span>
              </label>
              <label class="checkbox-option">
                <input type="checkbox" value="vehicular" v-model="formData.trafficType" />
                <span>Samochodowy</span>
              </label>
            </div>
            <p class="help-text" style="margin-top: 0.5rem; font-size: 0.875rem; color: #6b7280;">
              Możesz zaznaczyć oba rodzaje ruchu
            </p>
          </div>

          <!-- Warianty - zależnie od typu -->
          <div v-if="variantOptions.length > 0" class="form-group">
            <label class="form-label">
              Wariant
              <span class="required">*</span>
            </label>
            <select v-model="formData.variant" class="form-select" :class="{ 'error': errors.variant }">
              <option value="">Wybierz wariant</option>
              <option v-for="variant in variantOptions" :key="variant.value" :value="variant.value">
                {{ variant.label }}
              </option>
            </select>
            <span v-if="errors.variant" class="error-text">{{ errors.variant }}</span>
          </div>

          <!-- Wyposażenie i dodatki -->
          <div v-if="showPrintOption || showMountingOption || showGraphicDesignOption || showLightingOption" class="form-group" style="padding: 1rem; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; margin-top: 1.5rem; margin-bottom: 1.5rem;">
            <label class="form-label" style="margin-bottom: 1rem; color: #374151; font-weight: 600;">Wyposażenie i dodatki</label>
            <div class="checkbox-group" style="display: flex; flex-direction: column; gap: 0.75rem;">
              <label v-if="showPrintOption" class="checkbox-option">
                <input type="checkbox" v-model="formData.priceIncludesPrint" />
                <span>Cena zawiera druk</span>
              </label>

              <label v-if="showMountingOption" class="checkbox-option">
                <input type="checkbox" v-model="formData.priceIncludesMounting" />
                <span>Cena zawiera montaż</span>
              </label>

              <label v-if="showGraphicDesignOption" class="checkbox-option">
                <input type="checkbox" v-model="formData.graphicDesignHelp" />
                <span>Pomoc przy projekcie graficznym</span>
              </label>

              <label v-if="showLightingOption" class="checkbox-option">
                <input type="checkbox" v-model="formData.hasBacklight" />
                <span>Podświetlenie</span>
              </label>
            </div>
          </div>

          <!-- Typ oświetlenia dla Banerów i Ścian -->
          <div v-if="showLightingTypeBannerField" class="form-group">
            <label class="form-label">Typ oświetlenia</label>
            <select v-model="formData.lightingTypeBanner" class="form-select">
              <option value="">Brak podświetlenia</option>
              <option value="backlight">Podświetlenie z tyłu (backlight)</option>
              <option value="frontlight">Podświetlenie z przodu (frontlight)</option>
            </select>
          </div>

          <!-- Środowisko - dla Citylight, LED, Totem, Banner, Mobile, Other -->
          <div v-if="showEnvironmentField" class="form-group">
            <label class="form-label">Środowisko</label>
            <select v-model="formData.environment" class="form-select">
              <option value="">Wybierz środowisko (opcjonalnie)</option>
              <option v-for="env in environmentOptions" :key="env.value" :value="env.value">
                {{ env.label }}
              </option>
            </select>
          </div>

          <!-- Typ oświetlenia dla Billboardu -->
          <div v-if="showLightingTypeField">
            <div class="form-group">
              <label class="form-label">Typ oświetlenia</label>
              <select v-model="formData.lightingType" class="form-select">
                <option value="">Brak</option>
                <option v-for="option in lightingTypeOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>
          </div>

          <!-- Liczba pasażerów dla Transportu -->
          <div v-if="showDailyPassengersField">
            <div class="form-group">
              <label class="form-label">Liczba pasażerów dziennie</label>
              <input
                :value="formData.dailyPassengers"
                @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, false); formData.dailyPassengers = val ? parseInt(val) : null }"
                type="text"
                class="form-input"
                placeholder="np. 5000"
              />
              <p class="help-text" style="margin-top: 0.25rem; margin-bottom: 0; font-size: 0.8rem; color: #9ca3af;">Przybliżona liczba pasażerów dziennie dla tego wariantu transportu</p>
            </div>
          </div>

          <!-- Strefa operacyjna dla Mobilnej -->
          <div v-if="showOperatingZoneField">
            <div class="form-group">
              <label class="form-label">Strefa operacyjna</label>
              <select v-model="formData.operatingZone" class="form-select">
                <option value="">Wybierz strefę</option>
                <option v-for="option in operatingZoneOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>
          </div>

          <!-- Parametry techniczne dla LED screens -->
          <div v-if="formData.type === 'led_screen'">
            <h3 style="margin-top: 1.5rem; margin-bottom: 1rem; font-size: 0.95rem; color: #374151;">Parametry techniczne (opcjonalnie)</h3>
            
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Rozdzielczość (piksele)</label>
                <input
                  v-model="formData.resolution"
                  type="text"
                  class="form-input"
                  :class="{ 'error': errors.resolution }"
                  placeholder="np. 1920x1080"
                  @input="errors.resolution = ''"
                />
                <span v-if="errors.resolution" class="error-text">{{ errors.resolution }}</span>
                <p v-else class="help-text" style="margin-top: 0.25rem; margin-bottom: 0; font-size: 0.8rem; color: #9ca3af;">Przykład: 1920x1080, 3840x2160, 1280x720</p>
              </div>

              <div class="form-group">
                <label class="form-label">Pixel Pitch (mm)</label>
                <input
                  :value="formData.pixelPitch"
                  @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, true); formData.pixelPitch = val ? parseFloat(val) : null; errors.pixelPitch = '' }"
                  type="text"
                  class="form-input"
                  :class="{ 'error': errors.pixelPitch }"
                  placeholder="np. 3.9"
                />
                <span v-if="errors.pixelPitch" class="error-text">{{ errors.pixelPitch }}</span>
                <p v-else class="help-text" style="margin-top: 0.25rem; margin-bottom: 0; font-size: 0.8rem; color: #9ca3af;">Zakres: 0.1–100 mm (np. 3.9, 6.67, 10)</p>
              </div>

              <div class="form-group">
                <label class="form-label">Jasność (nits)</label>
                <input
                  :value="formData.brightness"
                  @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, false); formData.brightness = val ? parseInt(val) : null; errors.brightness = '' }"
                  type="text"
                  class="form-input"
                  :class="{ 'error': errors.brightness }"
                  placeholder="np. 5000"
                />
                <span v-if="errors.brightness" class="error-text">{{ errors.brightness }}</span>
                <p v-else class="help-text" style="margin-top: 0.25rem; margin-bottom: 0; font-size: 0.8rem; color: #9ca3af;">Zakres: 1000–15000 nits (np. 5000, 6500, 8000)</p>
              </div>
            </div>

            <div class="form-group" style="margin-top: 1.5rem;">
              <label class="checkbox-option">
                <input
                  v-model="formData.ambientLightControl"
                  type="checkbox"
                />
                <span>Ekran dostosowuje jasność do otoczenia</span>
              </label>
              <p class="help-text" style="margin-top: 0.5rem; margin-bottom: 0; font-size: 0.85rem; color: #6b7280; text-align: left;">Czy ekran posiada czujnik światła otoczenia i automatycznie dostosowuje jasność?</p>
            </div>
          </div>

          <!-- Pola specyficzne dla Transportu -->
          <div v-if="formData.type === 'transport'">
            <div class="form-group">
              <label class="form-label">Zakres reklamy <span class="required">*</span></label>
              <select v-model="formData.transportScope" class="form-select" :class="{ 'error': errors.transportScope }">
                <option value="" disabled>Wybierz zakres</option>
                <option v-for="option in transportScopeOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
              <span v-if="errors.transportScope" class="error-text">{{ errors.transportScope }}</span>
            </div>

            <div v-if="formData.variant !== 'stop'" class="form-group">
              <label class="form-label">Liczba pojazdów</label>
              <input
                :value="formData.vehicleCount"
                @input="(e) => { const val = handleNumberInput((e.target as HTMLInputElement).value, false); formData.vehicleCount = val ? parseInt(val) : null }"
                type="text"
                class="form-input"
                placeholder="1"
              />
            </div>
          </div>

          <!-- Pola specyficzne dla Mobilnej -->
          <div v-if="formData.type === 'mobile'">
            <div class="form-group">
              <label class="form-label">Tryb ekspozycji <span class="required">*</span></label>
              <select v-model="formData.mobileExposureMode" class="form-select" :class="{ 'error': errors.mobileExposureMode }">
                <option value="" disabled>Wybierz tryb</option>
                <option value="moving">Jeżdżąca</option>
                <option value="stationary">Stojąca</option>
                <option value="mixed">Mieszana</option>
              </select>
              <span v-if="errors.mobileExposureMode" class="error-text">{{ errors.mobileExposureMode }}</span>
            </div>

            <div class="form-group">
              <label class="form-label">Godziny działania</label>
              <input
                v-model="formData.operatingHours"
                type="text"
                class="form-input"
                placeholder="np. 8:00-20:00"
              />
            </div>

            <div class="form-group">
              <label class="form-label">Trasa / Obszar</label>
              <textarea
                v-model="formData.routeArea"
                rows="3"
                class="form-textarea"
                placeholder="Opis trasy lub obszaru działania..."
              ></textarea>
            </div>
          </div>

          <!-- Status dostępności -->          
          <div class="form-group">
            <label class="form-label">Status dostępności <span class="required">*</span></label>
            <select v-model="formData.status" class="form-select" :class="{ 'error': errors.status }" size="1">
              <option value="" disabled>Wybierz status</option>
              <option value="available">Wolne</option>
              <option value="reserved">Zarezerwowane</option>
              <option value="soon_available">Wkrótce dostępne</option>
            </select>
            <span v-if="errors.status" class="error-text">{{ errors.status }}</span>
          </div>

          <div v-if="formData.status === 'soon_available'" class="form-group">
            <label class="form-label">Data dostępności <span class="required">*</span></label>
            <VueDatePicker
              v-model="formData.availableFrom"
              :enable-time-picker="false"
              auto-apply
              :min-date="minDate"
              :clearable="false"
              :day-names="polishDays"
              class="w-full"
            >
              <template #month-year="{ month, year, updateMonthYear, handleMonthYearChange }">
                <div class="dp-month-year-nav">
                  <button class="dp-nav-btn" @click="handleMonthYearChange(false)" type="button" aria-label="Poprzedni miesiąc">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                  </button>
                  <div class="dp-month-year-selects">
                    <select class="dp-select" :value="month" @change="(e) => updateMonthYear(parseInt((e.target as HTMLSelectElement).value), year)">
                      <option v-for="(name, idx) in polishMonths" :key="idx" :value="idx">{{ name }}</option>
                    </select>
                    <select class="dp-select" :value="year" @change="(e) => updateMonthYear(month, parseInt((e.target as HTMLSelectElement).value))">
                      <option v-for="y in dpYears" :key="y" :value="y">{{ y }}</option>
                    </select>
                  </div>
                  <button class="dp-nav-btn" @click="handleMonthYearChange(true)" type="button" aria-label="Następny miesiąc">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                  </button>
                </div>
              </template>
              <template #trigger>
                <div class="date-picker-wrapper">
                  <input
                    type="text"
                    readonly
                    :value="formatDate(formData.availableFrom)"
                    placeholder="dd.mm.rrrr"
                    class="dp__input date-input"
                  />
                  <div class="date-picker-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                      <line x1="16" y1="2" x2="16" y2="6"></line>
                      <line x1="8" y1="2" x2="8" y2="6"></line>
                      <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                  </div>
                </div>
              </template>
            </VueDatePicker>
            <span v-if="errors.availableFrom" class="error-text">{{ errors.availableFrom }}</span>
          </div>

          <div class="form-group">
            <label class="form-label">Rodzaj oferty <span class="required">*</span></label>
            <select v-model="formData.offerType" class="form-select" :class="{ 'error': errors.offerType }">
              <option value="" disabled>Wybierz rodzaj oferty</option>
              <option value="owner">Właściciel (bezpośrednio)</option>
              <option value="agency">Agencja reklamowa</option>
              <option value="sublease">Podnajmujący</option>
            </select>
            <span v-if="errors.offerType" class="error-text">{{ errors.offerType }}</span>
          </div>
        </div>

        <div v-show="currentStep === 5" class="step-section">
          <h2>Zdjęcia powierzchni</h2>

          <div class="form-group">
            <label class="form-label">Dodaj zdjęcia (opcjonalne, max 5MB każde)</label>
            
            <div v-if="formData.imageFiles.length < 5"
              class="file-dropzone"
              :class="{ 'dragging': isDragging }"
              @drop="handleDrop"
              @dragover.prevent="isDragging = true"
              @dragleave.prevent="isDragging = false"
              @dragenter.prevent="isDragging = true"
            >
              <input
                type="file"
                accept="image/*"
                multiple
                @change="handleImageUpload"
                class="file-input"
                id="image-upload"
              />
              <label for="image-upload" class="file-label">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="file-text">Kliknij aby wybrać lub przeciągnij pliki tutaj</span>
                <span class="file-hint">PNG, JPG, GIF do 5MB • Możesz dodać do 5 zdjęć ({{ formData.imageFiles.length }}/5)</span>
              </label>
            </div>
            <span v-if="errors.image" class="error-text">{{ errors.image }}</span>
          </div>

          <div v-if="formData.imageFiles.length > 0" class="images-preview">
            <p class="help-text">Pierwsze zdjęcie będzie zdjęciem głównym. Przeciągnij, aby zmienić kolejność.</p>
            <div class="images-grid">
              <div 
                v-for="(img, index) in formData.imageFiles" 
                :key="img.id" 
                class="image-item"
                :class="{ 
                  'drag-over': dragOverTarget === index,
                  'dragging': draggedImageIndex === index,
                  'loading': img.loading
                }"
                draggable="true"
                @dragstart="handleImageDragStart($event, index)"
                @dragover.prevent="handleImageDragOver($event, index)"
                @dragend="handleDragEnd"
                @drop.prevent="handleImageDrop($event, index)"
              >
                <!-- Loader podczas ładowania zdjęcia -->
                <div v-if="img.loading" class="image-loader">
                  <svg class="spinner-icon" width="40" height="40" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span class="loading-text">Wczytywanie...</span>
                </div>
                
                <!-- Podgląd zdjęcia -->
                <img v-else :src="img.preview" alt="Podgląd" />
                
                <span v-if="index === 0" class="main-badge">Główne</span>
                <button type="button" @click="removeImage(index)" class="remove-btn" title="Usuń">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-show="currentStep === 6" class="step-section">
          <h2>Regulamin i zgody</h2>

          <div class="terms-content">
            <h3>Regulamin serwisu</h3>
            <p>Korzystając z platformy akceptujesz następujące warunki:</p>
            <ul>
              <li>Podane dane są prawdziwe i aktualne</li>
              <li>Posiadasz prawo do oferowania powierzchni reklamowej</li>
              <li>Zgadzasz się na przetwarzanie danych osobowych zgodnie z RODO</li>
              <li>Akceptujesz warunki korzystania z serwisu</li>
            </ul>
          </div>

          <div class="form-group">
            <label class="checkbox-option large">
              <input type="checkbox" v-model="formData.acceptTerms" :class="{ 'error': errors.acceptTerms }" />
              <span>Akceptuję <a href="/regulamin" target="_blank" class="link">regulamin</a> i <a href="/polityka-prywatnosci" target="_blank" class="link">politykę prywatności</a> <span class="required">*</span></span>
            </label>
            <span v-if="errors.acceptTerms" class="error-text">{{ errors.acceptTerms }}</span>
          </div>

          <div class="form-group" style="margin-top: 1rem;">
            <label class="checkbox-option large">
              <input type="checkbox" v-model="formData.subscribeNewsletter" />
              <span>Chcę otrzymywać newsletter z nowościami i informacjami o rynku reklamy (opcjonalnie)</span>
            </label>
          </div>
        </div>


        <div v-if="errors.submit" class="submit-error">{{ errors.submit }}</div>

        <div class="form-actions">
          <button
            v-if="currentStep > 1"
            type="button"
            @click="prevStep"
            class="btn btn-secondary"
          >
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M12 15L7 10L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Wstecz
          </button>

          <button
            v-if="currentStep < totalSteps"
            type="submit"
            class="btn btn-primary"
          >
            Dalej
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M8 5L13 10L8 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>

          <button
            v-if="currentStep === totalSteps"
            type="submit"
            class="btn btn-success"
            :disabled="isSubmitting"
          >
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M16 5L7.5 13.5L4 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            {{ isSubmitting ? 'Dodawanie...' : 'Opublikuj ogłoszenie' }}
          </button>
        </div>
      </form>
    </div>
 
    <!-- Map Modal -->
    <LocationMapModal
      v-model="showMapModal"
      :initial-latitude="formData.latitude"
      :initial-longitude="formData.longitude"
      @confirm="handleMapConfirm"
    />
    </div>
  </div>
</template>

<style scoped>
.add-listing-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem 0 3rem;
}

.page-container {
  max-width: 900px;
  margin: 0 auto;
  padding: 0 1.5rem;
}

.page-header {
  color: white;
  margin-bottom: 2rem;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 1rem;
}

.header-content {
  width: 100%;
  text-align: center;
}

.back-button {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.back-button:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateX(-4px);
}

.page-header h1 {
  font-size: 2.5rem;
  margin: 0 0 0.5rem 0;
}

.subtitle {
  font-size: 1.1rem;
  opacity: 0.9;
}

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
  z-index: 1000;
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

.progress-bar {
  display: flex;
  justify-content: space-between;
  background: white;
  padding: 2rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.progress-step {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  position: relative;
}

.progress-step::before {
  content: '';
  position: absolute;
  top: 18px;
  left: 50%;
  width: 100%;
  height: 2px;
  background: #e5e7eb;
  z-index: 0;
}

.progress-step:last-child::before {
  display: none;
}

.progress-step.active::before {
  background: #10B981;
}

.step-number {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #e5e7eb;
  color: #6b7280;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.1rem;
  transition: all 0.3s ease;
  position: relative;
  z-index: 1;
}

.progress-step.active .step-number {
  background: #10B981;
  color: white;
}

.progress-step.current .step-number {
  background: #059669;
  transform: scale(1.15);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.step-label {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 600;
  text-align: center;
}

.progress-step.active .step-label {
  color: #1f2937;
}

.form-content {
  background: white;
  padding: 3rem;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.step-section {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  position: relative;
}

.step-section h2 {
  margin: 0 0 1.5rem 0;
  font-size: 1.75rem;
  font-weight: 700;
  color: #1f2937;
}

.clear-input-btn {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: white;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  padding: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  border-radius: 4px;
  z-index: 10;
}

.clear-input-btn:hover {
  color: #EF4444;
  background: #fee2e2;
}

.toast-notification {
  position: fixed;
  top: 6rem;
  left: 50%;
  transform: translateX(-50%);
  background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
  color: white;
  padding: 1rem 2rem;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(239, 68, 68, 0.3);
  z-index: 9999;
  font-weight: 600;
  font-size: 1rem;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
}

.form-group {
  margin-bottom: 1.75rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  color: #374151;
  font-weight: 600;
  font-size: 0.95rem;
}

.required {
  color: #EF4444;
}

.form-input,
.form-select,
.form-textarea {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.2s;
  font-family: inherit;
  background: white;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.phone-input-with-prefix {
  display: flex;
  align-items: stretch;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  transition: all 0.2s;
}

.phone-input-with-prefix:hover {
  border-color: #d1d5db;
}

.phone-input-with-prefix:focus-within {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.phone-prefix {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0 0.75rem;
  background: #f9fafb;
  border-right: 1px solid #e5e7eb;
  font-weight: 600;
  color: #374151;
  font-size: 0.95rem;
}

.flag-icon {
  width: 24px;
  height: 16px;
  border-radius: 4px;
  border: 1px solid #d1d5db;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

input.form-input.phone-input-field {
  flex: 1;
  min-width: 0;
  width: 100%;
  padding: 0.75rem;
  border: none;
  border-radius: 0;
  font-size: 0.95rem;
  color: #374151;
  background: white;
  box-shadow: none;
}

input.form-input.phone-input-field:focus {
  outline: none;
  border: none;
  box-shadow: none;
}

.form-input.error,
.form-select.error,
.form-textarea.error {
  border-color: #EF4444;
}

.error-text {
  display: block;
  color: #EF4444;
  font-size: 0.875rem;
  margin-top: 0.5rem;
  font-weight: 500;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.radio-group,
.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.radio-option,
.checkbox-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  padding: 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  transition: all 0.2s;
}

.radio-option:hover,
.checkbox-option:hover {
  border-color: #10B981;
  background: #f0fdf4;
}

.radio-option input[type="radio"] {
  width: 20px;
  height: 20px;
  cursor: pointer;
}

.radio-option span,
.checkbox-option span {
  font-weight: 500;
  color: #374151;
}

.checkbox-option.large {
  font-size: 1.05rem;
}

.checkbox-option input[type="checkbox"].error {
  outline: 2px solid #EF4444;
}

.link {
  color: #667eea;
  text-decoration: underline;
  font-weight: 600;
  transition: color 0.2s;
}

.link:hover {
  color: #764ba2;
}

.price-info-box {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  border: 2px solid #86efac;
}

.price-info-box h3 {
  margin: 0 0 1rem 0;
  color: #166534;
  font-size: 1.1rem;
}

.price-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.price-item {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
}

.price-label {
  color: #6b7280;
  font-weight: 500;
}

.price-value {
  color: #166534;
  font-weight: 700;
}

.campaign-days {
  color: #6b7280;
  font-weight: 500;
  font-size: 0.9rem;
}

.price-per-sqm {
  margin-top: 1rem;
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
  text-align: center;
  color: #166534;
  font-size: 1.05rem;
}

.surface-display {
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  padding: 1.25rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  color: #1e40af;
  font-size: 1.15rem;
  font-weight: 600;
  border: 2px solid #93c5fd;
}

.surface-price {
  color: #1e3a8a;
  margin-left: 0.5rem;
}

.address-input-wrapper {
  position: relative;
}

.address-suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 2px solid #e5e7eb;
  border-top: none;
  border-radius: 0 0 8px 8px;
  max-height: 300px;
  overflow-y: auto;
  z-index: 1000;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.suggestion-item {
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background 0.2s;
  border-bottom: 1px solid #f3f4f6;
}

.suggestion-item:hover {
  background: #f9fafb;
}

.suggestion-item:last-child {
  border-bottom: none;
}

.map-container {
  height: 400px;
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 0.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Hide Leaflet attribution */
.map-container :deep(.leaflet-control-attribution) {
  display: none;
}

.map-hint {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0 0 1.5rem 0;
  font-style: italic;
}

.file-dropzone {
  position: relative;
  transition: all 0.2s;
}

.file-dropzone.dragging {
  transform: scale(1.02);
}

.file-dropzone.dragging .file-label {
  border-color: #10B981;
  background: #f0fdf4;
}

.file-input {
  display: none;
}

.file-label {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  border: 3px dashed #d1d5db;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s;
  background: #f9fafb;
}

.file-label:hover {
  border-color: #10B981;
  background: #f0fdf4;
}

.file-label svg {
  color: #6b7280;
  margin-bottom: 1rem;
}

.file-text {
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.file-hint {
  font-size: 0.875rem;
  color: #6b7280;
}

.images-preview {
  margin-top: 1.5rem;
}

.images-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 1rem;
}

.image-item {
  position: relative;
  aspect-ratio: 1;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid #e5e7eb;
  background: white;
}

.image-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.remove-btn {
  position: absolute;
  top: 4px;
  right: 4px;
  background: rgba(255, 255, 255, 0.9);
  border: none;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #ef4444;
  transition: all 0.2s;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.remove-btn:hover {
  background: #fee2e2;
  transform: scale(1.1);
}

.terms-content {
  background: #f9fafb;
  padding: 2rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  border: 2px solid #e5e7eb;
}

.terms-content h3 {
  margin: 0 0 1rem 0;
  color: #1f2937;
  font-size: 1.2rem;
}

.terms-content p {
  margin: 0 0 1rem 0;
  color: #6b7280;
  line-height: 1.6;
}

.terms-content ul {
  margin: 0;
  padding-left: 1.5rem;
  color: #6b7280;
  line-height: 1.8;
}

.terms-content li {
  margin-bottom: 0.5rem;
}

.submit-error {
  padding: 1rem 1.5rem;
  background: #FEE2E2;
  border: 2px solid #FCA5A5;
  border-radius: 8px;
  color: #991B1B;
  font-weight: 600;
  margin-bottom: 1.5rem;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  padding-top: 2rem;
  border-top: 2px solid #f3f4f6;
}

.btn {
  padding: 1rem 2rem;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-secondary {
  background: white;
  color: #667eea;
  border: 2px solid #667eea;
}

.btn-secondary:hover {
  background: #f5f3ff;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  margin-left: auto;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.btn-success {
  background: linear-gradient(135deg, #10B981 0%, #059669 100%);
  color: white;
  margin-left: auto;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-success:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

@media (max-width: 768px) {
  .add-listing-page {
    padding: 1rem 0 2rem;
  }

  .page-header h1 {
    font-size: 1.75rem;
  }

  .subtitle {
    font-size: 1rem;
  }

  .progress-bar {
    padding: 1rem;
    gap: 0.25rem;
  }

  .step-number {
    width: 32px;
    height: 32px;
    font-size: 0.9rem;
  }

  .step-label {
    font-size: 0.7rem;
  }

  .form-content {
    padding: 1.5rem;
  }

  .step-section h2 {
    font-size: 1.5rem;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .price-grid {
    grid-template-columns: 1fr;
  }

  .map-container {
    height: 300px;
  }

  .form-actions {
    flex-direction: column;
  }

  .btn-primary,
  .btn-success {
    margin-left: 0;
  }
}

.image-item {
  position: relative;
  transition: all 0.2s ease;
}

.image-item.dragging {
  opacity: 0.5;
  transform: scale(0.95);
  border: 2px dashed #667eea;
  filter: grayscale(0.5);
}

.image-item.drag-over {
  transform: scale(1.05);
  box-shadow: 0 0 0 2px #667eea, 0 8px 16px rgba(102, 126, 234, 0.2);
  border-color: #667eea;
  z-index: 1;
}

.main-badge {
  position: absolute;
  top: 8px;
  left: 8px;
  background: rgba(16, 185, 129, 0.9);
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
  backdrop-filter: blur(4px);
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  z-index: 2;
}

.image-loader {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background-color: rgba(255, 255, 255, 0.8);
  border-radius: 8px;
  z-index: 1;
}

.loading-text {
  margin-top: 8px;
  font-size: 0.9rem;
  color: #4B5563;
  font-weight: 500;
}

.help-text {
  color: #6b7280;
  font-size: 0.9rem;
  margin-bottom: 1rem;
  text-align: center;
}

.input-spinner {
  position: absolute;
  right: 2.5rem;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
}

.spinner-icon {
  animation: spin 1s linear infinite;
  color: #667eea;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.char-counter {
  text-align: right;
  color: #6b7280;
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

.char-counter.near-limit {
  color: #f59e0b;
  font-weight: 600;
}

.date-picker-wrapper {
  position: relative;
  width: 100%;
  display: block;
}

.date-input {
  width: 100%;
  cursor: pointer;
  display: block;
}

.date-picker-icon {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  display: flex;
  align-items: center;
  padding-left: 0.75rem;
  pointer-events: none;
  z-index: 10;
  color: #9ca3af;
}

/* Map styles */
.map-container-desktop {
  display: block;
  width: 100%;
  height: 400px;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  overflow: hidden;
  border: 2px solid #e5e7eb;
}

.map-container-desktop :deep(.leaflet-control-attribution) {
  display: none !important;
}

.map-hint-desktop {
  text-align: center;
  color: #6b7280;
  font-size: 0.9rem;
  margin-bottom: 2rem;
}

.location-input-group {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
}

.address-input-wrapper {
  width: 100%;
  position: relative;
}

.map-button-modern {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0 1.25rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 8px;
  color: white;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.25);
  height: 44px;
  margin-top: 0;
}

.map-button-modern:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
}

.map-button-modern svg {
  flex-shrink: 0;
}

@media (min-width: 769px) {
  .map-button-modern {
    display: none;
  }
}

@media (max-width: 768px) {
  .map-container-desktop {
    display: none;
  }

  .map-hint-desktop {
    display: none;
  }

  .location-input-group {
    flex-direction: column;
  }

  .map-button-modern {
    width: 100%;
    justify-content: center;
  }

  .leaflet-container :deep(.leaflet-bottom),
  .leaflet-container :deep(.leaflet-right) {
    display: none !important;
  }

  :deep(.leaflet-control-attribution) {
    display: none !important;
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes scaleIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>
