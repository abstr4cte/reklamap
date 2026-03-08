<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api, getFullImageUrl } from '../services/api'
import axios from '../api/axios'
import type { Advertisement } from '../types'
import { getRecaptchaToken, isRecaptchaAvailable } from '../services/recaptchaService'
import ToastNotification from '../components/ToastNotification.vue'
import { slugify } from '../utils/slugify'
import { mapTypeToUrlFormat } from '../utils/typeMapping'
import WebPImage from '../components/WebPImage.vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import icon from 'leaflet/dist/images/marker-icon.png'
import iconShadow from 'leaflet/dist/images/marker-shadow.png'
import Breadcrumbs from '../components/Breadcrumbs.vue'
import { useSeo } from '../composables/useSeo'
import { analytics } from '../utils/analytics'

// Fix Leaflet icon paths
const DefaultIcon = L.icon({
  iconUrl: icon,
  shadowUrl: iconShadow,
  iconSize: [25, 41],
  iconAnchor: [12, 41]
})
L.Marker.prototype.options.icon = DefaultIcon

const route = useRoute()
const router = useRouter()

const ad = ref<Advertisement | null>(null)
const similarAds = ref<Advertisement[]>([])
const isLoading = ref(true)
const notFound = ref(false)
const showPhone = ref(false)
const isFavorite = ref(false)
const isInComparison = ref(false)
const contactForm = ref({
  email: '',
  message: ''
})
const contactErrors = ref<Record<string, string>>({})
const isSubmittingContact = ref(false)
const contactSuccess = ref(false)
const toast = ref<InstanceType<typeof ToastNotification> | null>(null)
const dailyStatsViews = ref(0)

const mapContainer = ref<HTMLElement | null>(null)
let map: L.Map | null = null

const showStreetView = ref(false)
const streetViewError = ref(false)
const streetViewLoading = ref(false)
const streetViewCached = ref(false) // Track if we've already loaded/tried to load
const streetViewUrl = ref('') // Store URL to prevent re-renders

const currentImageIndex = ref(0)
const showImagePreview = ref(false)
const isZoomed = ref(false)
const touchStartX = ref(0)
const touchEndX = ref(0)
const touchStartTime = ref(0)

const displayViews = computed(() => {
  return dailyStatsViews.value || 0
})

const images = computed(() => {
  if (!ad.value) return []
  
  // Use the dedicated images field if available
  if (ad.value.images && Array.isArray(ad.value.images) && ad.value.images.length > 0) {
    return ad.value.images
  }
  
  // Backward compatibility: try to extract from description for old ads
  const allImages: string[] = []
  
  // Add main image
  if (ad.value.image_url) {
    allImages.push(ad.value.image_url)
  }
  
  // Extract additional images from description (for old ads)
  if (ad.value.description) {
    const match = ad.value.description.match(/\[IMAGES\](.*?)\[\/IMAGES\]/s)
    if (match && match[1]) {
      try {
        const additionalImages = JSON.parse(match[1].trim())
        if (Array.isArray(additionalImages)) {
          allImages.push(...additionalImages)
        }
      } catch (e) {
        console.error('Error parsing images from description:', e)
      }
    }
  }
  
  return allImages
})

// Clean description without image data
const cleanDescription = computed(() => {
  if (!ad.value?.description) return ''
  return ad.value.description.replace(/\n\n\[IMAGES\].*?\[\/IMAGES\]/s, '')
})

// SEO-friendly alt text for images
const imageAlt = computed(() => {
  if (!ad.value) return ''
  const typeLabel = getTypeLabel(ad.value.type)
  return `${typeLabel} ${ad.value.city} - ${ad.value.title}`
})

const thumbnailAlt = (index: number) => {
  if (!ad.value) return `Miniatura ${index + 1}`
  return `${ad.value.title} - zdjęcie ${index + 1}`
}

const nextImage = () => {
  if (images.value.length === 0) return
  currentImageIndex.value = (currentImageIndex.value + 1) % images.value.length
}

const prevImage = () => {
  if (images.value.length === 0) return
  currentImageIndex.value = (currentImageIndex.value - 1 + images.value.length) % images.value.length
}

const openImagePreview = () => {
  if (images.value.length > 0) {
    showImagePreview.value = true
    isZoomed.value = false
    // Prevent body scroll when modal is open
    document.body.style.overflow = 'hidden'
  }
}

const closeImagePreview = () => {
  showImagePreview.value = false
  isZoomed.value = false
  // Restore body scroll
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

  // Swipe detection
  if (Math.abs(distance) > 50 && duration < 300) {
    if (distance > 0) {
      prevImage()
    } else {
      nextImage()
    }
    isZoomed.value = false
  } 
  // Double tap detection (very basic)
  else if (duration < 200) {
    // We could implement a proper double tap here, but for now let's use the click event
  }
}

const handleImageClick = () => {
  toggleZoom()
}

const handlePreviewKeydown = (event: KeyboardEvent) => {
  if (!showImagePreview.value) return
  
  if (event.key === 'ArrowLeft') {
    prevImage()
  } else if (event.key === 'ArrowRight') {
    nextImage()
  } else if (event.key === 'Escape') {
    closeImagePreview()
  }
}

const checkFavoriteStatus = () => {
  if (!ad.value) return
  const favorites = JSON.parse(localStorage.getItem('favorites') || '[]')
  isFavorite.value = favorites.includes(ad.value.id)
}

const checkComparisonStatus = () => {
  if (!ad.value) return
  const comparison = JSON.parse(localStorage.getItem('comparison') || '[]')
  isInComparison.value = comparison.includes(ad.value.id)
}

const statusLabel = computed(() => {
  let currentStatus = ad.value?.display_status || ad.value?.status
  
  // Debug log dla szczegółów ogłoszenia
  if (ad.value) {
    console.log('🔍 SZCZEGÓŁY OGŁOSZENIA ID:', ad.value.id)
    console.log('├─ Status z bazy:', ad.value.status)
    console.log('├─ Display status:', ad.value.display_status)
    console.log('├─ Data dostępności:', ad.value.available_from || 'brak')
    console.log('└─ Używany status:', currentStatus)
  }
  
  // Jeśli status to soon_available, sprawdź czy data dostępności już minęła
  if (currentStatus === 'soon_available' && ad.value?.available_from) {
    const availableDate = new Date(ad.value.available_from)
    const today = new Date()
    // Ustaw czas na początek dnia dla porównania
    today.setHours(0, 0, 0, 0)
    availableDate.setHours(0, 0, 0, 0)
    
    // Jeśli data dostępności to dzisiaj lub wcześniej, zmień status na active
    if (availableDate <= today) {
      currentStatus = 'active'
    }
  }
  
  switch (currentStatus) {
    case 'active':
      return 'Wolne'
    case 'reserved':
      return 'Zarezerwowane'
    case 'soon_available':
      if (ad.value?.available_from) {
        return `Dostępne od: ${formatDate(ad.value.available_from)}`
      }
      return 'Wkrótce dostępne'
    default:
      return 'Nieznany'
  }
})

const statusClass = computed(() => {
  let currentStatus = ad.value?.display_status || ad.value?.status
  
  // Jeśli status to soon_available, sprawdź czy data dostępności już minęła
  if (currentStatus === 'soon_available' && ad.value?.available_from) {
    const availableDate = new Date(ad.value.available_from)
    const today = new Date()
    // Ustaw czas na początek dnia dla porównania
    today.setHours(0, 0, 0, 0)
    availableDate.setHours(0, 0, 0, 0)
    
    // Jeśli data dostępności to dzisiaj lub wcześniej, zmień status na active
    if (availableDate <= today) {
      currentStatus = 'active'
    }
  }
  
  switch (currentStatus) {
    case 'active':
      return 'status-available'
    case 'reserved':
      return 'status-reserved'
    case 'soon_available':
      return 'status-soon'
    default:
      return ''
  }
})

// Computed properties for field visibility based on ad type
const showDimensions = computed(() => {
  if (!ad.value) return false
  // Show dimensions only if they exist and are greater than 0
  const hasDimensions = ad.value.width && ad.value.height && ad.value.width > 0 && ad.value.height > 0
  return ['billboard', 'citylight', 'banner', 'wall', 'totem', 'led_screen'].includes(ad.value.type) && hasDimensions
})

const showTrafficIntensity = computed(() => {
  if (!ad.value) return false
  return ['billboard', 'banner', 'wall', 'totem'].includes(ad.value.type) && ad.value.traffic_intensity
})

const showTrafficDirection = computed(() => {
  if (!ad.value) return false
  return ['billboard', 'banner', 'wall', 'totem'].includes(ad.value.type) && ad.value.traffic_direction && ad.value.traffic_direction.length > 0
})

const formatTrafficDirection = computed(() => {
  if (!ad.value?.traffic_direction || !Array.isArray(ad.value.traffic_direction)) return ''
  
  // Jeśli są oba kierunki, wyświetl "Oba kierunki"
  if (ad.value.traffic_direction.includes('entry') && ad.value.traffic_direction.includes('exit')) {
    return 'Oba kierunki'
  }
  
  const directions = ad.value.traffic_direction.map(dir => {
    if (dir === 'entry') return 'Wjazd do miasta'
    if (dir === 'exit') return 'Wyjazd z miasta'
    return dir
  })
  
  return directions.join(', ')
})

const showLighting = computed(() => {
  if (!ad.value) return false
  return ['citylight', 'led_screen', 'totem'].includes(ad.value.type) && ad.value.has_backlight
})

const showEnvironment = computed(() => {
  if (!ad.value) return false
  return ['citylight', 'led_screen', 'totem', 'banner', 'mobile', 'other'].includes(ad.value.type) && ad.value.environment
})

const showTrafficType = computed(() => {
  if (!ad.value) return false
  return ['billboard', 'banner', 'wall', 'totem'].includes(ad.value.type) && ad.value.traffic_type && (ad.value.traffic_type as string[]).length > 0
})

const showPrint = computed(() => {
  if (!ad.value) return false
  return ['billboard', 'banner', 'citylight'].includes(ad.value.type) && ad.value.price_includes_print
})

const showMounting = computed(() => {
  if (!ad.value) return false
  return ['billboard', 'banner', 'wall', 'citylight'].includes(ad.value.type) && (ad.value as any).price_includes_mounting
})

const showGraphicDesign = computed(() => {
  if (!ad.value) return false
  return ['billboard', 'banner', 'wall', 'citylight'].includes(ad.value.type) && ad.value.graphic_design_help
})

const showVariant = computed(() => {
  if (!ad.value) return false
  return ['billboard', 'citylight', 'led_screen', 'totem', 'transport', 'mobile'].includes(ad.value.type) && ad.value.variant && ad.value.variant.trim() !== ''
})

const surfaceArea = computed(() => {
  if (ad.value?.width && ad.value?.height) {
    // Wymiary są zawsze przechowywane w metrach w bazie
    return (ad.value.width * ad.value.height).toFixed(2)
  }
  return '0'
})

const pricePerSqm = computed(() => {
  const area = parseFloat(surfaceArea.value)
  if (area > 0 && ad.value?.price) {
    return (ad.value.price / area).toFixed(2)
  }
  return '0'
})

const showPricePerSqm = computed(() => {
  if (!ad.value) return false
  // Don't show price per sqm for citylight and types without dimensions
  return ad.value.type !== 'citylight' && ad.value.width && ad.value.height && parseFloat(surfaceArea.value) > 0
})

// Computed property for location tier (billboard only)
const locationTier = computed(() => {
  if (!ad.value || ad.value.type !== 'billboard') return null
  
  const trafficIntensity = ad.value.traffic_intensity
  const roadClass = ad.value.road_class
  
  // PREMIUM: wysokie natężenie ruchu + autostrada/droga ekspresowa/droga krajowa
  if (trafficIntensity === 'high' && ['highway', 'expressway', 'national'].includes(roadClass || '')) {
    return 'PREMIUM'
  }
  
  // STANDARD: wszystkie inne kombinacje
  return 'STANDARD'
})

// Computed property to show road class for billboards
const showRoadClass = computed(() => {
  if (!ad.value) return false
  return ad.value.type === 'billboard' && ad.value.road_class
})

// Helper to format road class label
const getRoadClassLabel = (roadClass: string): string => {
  const roadClassLabels: Record<string, string> = {
    'highway': 'Autostrada (A)',
    'expressway': 'Droga ekspresowa (S)',
    'national': 'Droga krajowa (DK)',
    'regional': 'Droga wojewódzka',
    'local': 'Droga lokalna',
    'urban': 'Droga miejska'
  }
  return roadClassLabels[roadClass] || roadClass
}

// Helper to format environment label
const getEnvironmentLabel = (environment: string): string => {
  const environmentLabels: Record<string, string> = {
    'indoor': 'Wewnątrz',
    'outdoor': 'Na zewnątrz',
    'event': 'Event / Wydarzenie'
  }
  return environmentLabels[environment] || environment
}

// Helper to format transport scope label
const getTransportScopeLabel = (scope: string): string => {
  const scopeLabels: Record<string, string> = {
    'internal': 'Wewnętrzna',
    'external': 'Zewnętrzna',
    'full_vehicle': 'Całopojazdowa'
  }
  return scopeLabels[scope] || scope
}

// Helper to format mobile exposure mode label
const getMobileExposureModeLabel = (mode: string): string => {
  const modeLabels: Record<string, string> = {
    'moving': 'Jeżdżąca',
    'stationary': 'Stojąca',
    'mixed': 'Mieszana'
  }
  return modeLabels[mode] || mode
}

// Helper to format lighting type label
const getLightingTypeLabel = (lightingType: string): string => {
  const lightingLabels: Record<string, string> = {
    'led': 'LED',
    'fluorescent': 'Fluorescencyjne',
    'natural': 'Naturalne',
    'none': 'Brak',
    'backlight': 'Podświetlenie z tyłu',
    'frontlight': 'Podświetlenie z przodu'
  }
  return lightingLabels[lightingType] || lightingType
}

// Helper to format operating zone label
const getOperatingZoneLabel = (zone: string): string => {
  const zoneLabels: Record<string, string> = {
    'center': 'Centrum',
    'periphery': 'Peryferia',
    'agglomeration': 'Cała aglomeracja'
  }
  return zoneLabels[zone] || zone
}

// Helper to format traffic type
const formatTrafficType = computed(() => {
  if (!ad.value || !ad.value.traffic_type) return ''
  const trafficType = ad.value.traffic_type as string[]
  const labels: Record<string, string> = {
    'pedestrian': 'Pieszy',
    'vehicular': 'Samochodowy'
  }
  return trafficType.map(type => labels[type] || type).join(', ')
})

// Helper to format type label
const getTypeLabel = (type: string): string => {
  const typeLabels: Record<string, string> = {
    'billboard': 'Billboard',
    'citylight': 'Citylight',
    'led_screen': 'Ekran LED',
    'banner': 'Banner',
    'wall': 'Ściana reklamowa',
    'totem': 'Totem reklamowy',
    'transport': 'Reklama w transporcie',
    'mobile': 'Reklama mobilna',
    'other': 'Inne'
  }
  return typeLabels[type] || type
}

// Helper to format price unit label
const getPriceUnitLabel = (priceUnit: string): string => {
  const priceUnitLabels: Record<string, string> = {
    'day': 'za dzień',
    'week': 'za tydzień',
    'month': 'za miesiąc',
    'year': 'za rok',
    'campaign': 'za kampanię',
    'sqm': 'za m²'
  }
  return priceUnitLabels[priceUnit] || priceUnit
}

// Computed properties for LED screen specific fields
const showResolution = computed(() => {
  if (!ad.value) return false
  return ad.value.type === 'led_screen' && (ad.value as any).resolution && (ad.value as any).resolution.trim() !== ''
})

const showPixelPitch = computed(() => {
  if (!ad.value) return false
  return ad.value.type === 'led_screen' && (ad.value as any).pixel_pitch && (ad.value as any).pixel_pitch > 0
})

const showBrightness = computed(() => {
  if (!ad.value) return false
  return ad.value.type === 'led_screen' && (ad.value as any).brightness && (ad.value as any).brightness > 0
})

// Computed property for campaign duration
const showCampaignDuration = computed(() => {
  if (!ad.value) return false
  return ad.value.price_unit === 'campaign' && ad.value.campaign_duration && ad.value.campaign_duration > 0
})

// Computed properties for transport-specific fields
const showTransportScope = computed(() => {
  if (!ad.value) return false
  return ad.value.type === 'transport' && ad.value.transport_scope && ad.value.transport_scope.trim() !== ''
})

const showVehicleCount = computed(() => {
  if (!ad.value) return false
  return ad.value.type === 'transport' && ad.value.vehicle_count && ad.value.vehicle_count > 0
})

// Computed properties for mobile-specific fields
const showMobileExposureMode = computed(() => {
  if (!ad.value) return false
  return ad.value.type === 'mobile' && ad.value.mobile_exposure_mode && ad.value.mobile_exposure_mode.trim() !== ''
})

const showOperatingHours = computed(() => {
  if (!ad.value) return false
  return ad.value.type === 'mobile' && ad.value.operating_hours && ad.value.operating_hours.trim() !== ''
})

const showRouteArea = computed(() => {
  if (!ad.value) return false
  return ad.value.type === 'mobile' && ad.value.route_area && ad.value.route_area.trim() !== ''
})

// Computed properties for new extended fields
const showLightingType = computed(() => {
  if (!ad.value) return false
  return ad.value.type === 'billboard' && (ad.value as any).lighting_type && (ad.value as any).lighting_type.trim() !== ''
})

const showLightingTypeBanner = computed(() => {
  if (!ad.value) return false
  return ['banner', 'wall'].includes(ad.value.type) && (ad.value as any).lighting_type_banner && (ad.value as any).lighting_type_banner.trim() !== ''
})

const showDailyPassengers = computed(() => {
  if (!ad.value) return false
  return ad.value.type === 'transport' && (ad.value as any).daily_passengers && (ad.value as any).daily_passengers > 0
})

const showOperatingZone = computed(() => {
  if (!ad.value) return false
  return ad.value.type === 'mobile' && (ad.value as any).operating_zone && (ad.value as any).operating_zone.trim() !== ''
})

const showAmbientLightControl = computed(() => {
  if (!ad.value) return false
  return ad.value.type === 'led_screen' && (ad.value as any).ambient_light_control
})

const showEstimatedDailyViews = computed(() => {
  if (!ad.value) return false
  return (ad.value as any).estimated_daily_views && (ad.value as any).estimated_daily_views > 0
})

// Helper to format variant label
const getVariantLabel = (variant: string): string => {
  const variantLabels: Record<string, string> = {
    // Billboard
    'standard': 'Jednostronny',
    'two_sided': 'Dwustronny (back-to-back)',
    'three_sided': 'Trójstronny (prismatron)',
    'scrolling': 'Scrolling / Rolowany',
    // Citylight
    'single_sided': 'Jednostronny',
    'double_sided': 'Dwustronny',
    'digital': 'Cyfrowy (DOOH)',
    // LED Screen
    'interactive': 'Interaktywny',
    // Totem
    'multi_sided': 'Wielostronny / Kolumna',
    'pylon': 'Pylon (przy drodze)',
    // Transport
    'bus': 'Autobus',
    'tram': 'Tramwaj',
    'metro': 'Metro',
    'train': 'Pociąg / SKM / Kolej',
    'stop': 'Przystanek',
    // Mobile
    'trailer': 'Przyczepka',
    'car': 'Samochód',
    'bike': 'Rower',
    'other': 'Inna'
  }
  
  return variantLabels[variant] || variant
}

// Helper to map type to URL format
const getTypeUrlFormat = (type: string): string => {
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

// SEO Refactor: Call useSeo once at top level
const seoOptions = ref<any>({
  title: 'ReklaMap - Platforma Powierzchni Reklamowych',
  description: 'Trwa ładowanie ogłoszenia...'
})
const { updateMetaTags } = useSeo(seoOptions)

// Update seoOptions when ad changes
watch(ad, (newAd) => {
  if (newAd) {
    const imageUrl = newAd.image_url ? getFullImageUrl(newAd.image_url) : undefined
    const url = typeof window !== 'undefined' ? window.location.href : ''
    
    // Budujemy dynamiczny tytuł SEO
    const typeLabel = getTypeLabel(newAd.type)
    const dims = (newAd.width && newAd.height) ? ` ${newAd.width}×${newAd.height}m` : ''
    const tier = (newAd.type === 'billboard' && locationTier.value === 'PREMIUM') ? ' [PREMIUM]' : ''
    const shortAdTitle = newAd.title.length > 28 ? newAd.title.substring(0, 25) + '…' : newAd.title
    const title = `${shortAdTitle} – ${typeLabel}${dims}, ${newAd.city}${tier} | ReklaMap`

    // Budujemy bogaty opis
    let extraDetails = ''
    if (newAd.variant) extraDetails += `${getVariantLabel(newAd.variant)}. `
    if (newAd.has_backlight) extraDetails += 'Oświetlenie LED. '
    if (newAd.traffic_intensity === 'high') extraDetails += 'Wysokie natężenie ruchu. '
    if (newAd.offer_type === 'rent') extraDetails += 'Do wynajęcia. '
    
    const description = `${newAd.title} – ${typeLabel} w ${newAd.city}. ${extraDetails}Wymiary: ${newAd.width}×${newAd.height}m. Cena od ${newAd.price} zł/${newAd.price_unit === 'day' ? 'dzień' : newAd.price_unit === 'week' ? 'tydzień' : 'mies.'}. ${newAd.description.substring(0, 60)}...`

    const keywords = `${typeLabel} ${newAd.city}, ${typeLabel}${dims} ${newAd.city}, wynajem ${typeLabel.toLowerCase()}, powierzchnia reklamowa ${newAd.city}, reklama zewnętrzna ${newAd.city}`

    // Update the reactive seoOptions and trigger manual update
    seoOptions.value = {
      title,
      description,
      keywords,
      ogType: 'product',
      ogImage: imageUrl,
      ogUrl: url,
      canonical: url,
      structuredData: [
        {
          '@context': 'https://schema.org',
          '@type': 'Product',
          'name': newAd.title,
          'description': newAd.description,
          'image': imageUrl,
          'brand': { '@type': 'Brand', 'name': 'ReklaMap' },
          'offers': {
            '@type': 'Offer',
            'price': newAd.price,
            'priceCurrency': 'PLN',
            'availability': newAd.status === 'active' ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url': url,
            'priceValidUntil': new Date(Date.now() + 60 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            'itemCondition': 'https://schema.org/NewCondition'
          },
          'category': getTypeLabel(newAd.type),
          'additionalProperty': [
            { '@type': 'PropertyValue', 'name': 'Szerokość', 'value': `${newAd.width}m` },
            { '@type': 'PropertyValue', 'name': 'Wysokość', 'value': `${newAd.height}m` },
            { '@type': 'PropertyValue', 'name': 'Powierzchnia', 'value': `${surfaceArea.value}m²` },
            { '@type': 'PropertyValue', 'name': 'Lokalizacja', 'value': newAd.city },
            ...(newAd.variant ? [{ '@type': 'PropertyValue', 'name': 'Wariant', 'value': getVariantLabel(newAd.variant) }] : []),
            ...(locationTier.value ? [{ '@type': 'PropertyValue', 'name': 'Klasa lokalizacji', 'value': locationTier.value }] : []),
            { '@type': 'PropertyValue', 'name': 'Oświetlenie', 'value': newAd.has_backlight ? 'Tak' : 'Nie' }
          ]
        },
        {
          '@context': 'https://schema.org',
          '@type': 'Place',
          'name': newAd.location,
          'address': {
            '@type': 'PostalAddress',
            'addressLocality': newAd.city,
            'addressRegion': newAd.region,
            'addressCountry': 'PL'
          },
          'geo': {
            '@type': 'GeoCoordinates',
            'latitude': newAd.latitude,
            'longitude': newAd.longitude
          }
        }
      ]
    }
  }
}, { immediate: true })

// Breadcrumbs for SEO
const breadcrumbs = computed(() => {
  if (!ad.value) return []
  
  return [
    {
      label: 'Strona główna',
      path: '/'
    },
    {
      label: 'Powierzchnie reklamowe',
      path: '/powierzchnie-reklamowe'
    },
    {
      label: getTypeLabel(ad.value.type),
      path: `/powierzchnie-reklamowe/${getTypeUrlFormat(ad.value.type)}`
    },
    {
      label: ad.value.city,
      path: `/powierzchnie-reklamowe/${getTypeUrlFormat(ad.value.type)}/${slugify(ad.value.city)}`
    },
    {
      label: ad.value.title
    }
  ]
})

const toggleFavorite = () => {
  if (!ad.value) return
  const favorites = JSON.parse(localStorage.getItem('favorites') || '[]')
  const index = favorites.indexOf(ad.value.id)

  if (index > -1) {
    favorites.splice(index, 1)
  } else {
    favorites.push(ad.value.id)
  }

  localStorage.setItem('favorites', JSON.stringify(favorites))
  checkFavoriteStatus()
  
  // Track favorite event
  if (favorites.includes(ad.value.id)) {
    analytics.trackEvent('add_to_favorites', { ad_id: ad.value.id, ad_title: ad.value.title })
  }

  // Użyj niestandardowego zdarzenia, które działa w ramach tej samej karty
  window.dispatchEvent(new CustomEvent('localStorageChange'))
}

const toggleComparison = async () => {
  if (!ad.value) return
  const comparison = JSON.parse(localStorage.getItem('comparison') || '[]')
  const index = comparison.indexOf(ad.value.id)

  if (index > -1) {
    comparison.splice(index, 1)
  } else {
    if (comparison.length >= 5) {
      toast.value?.add('Możesz porównać maksymalnie 5 ogłoszeń', 'error')
      return
    }
    
    // Check if there are already ads in comparison with different type
    if (comparison.length > 0) {
      try {
        const existingAds = await api.getAdvertisementsByIds(comparison)
        const existingType = existingAds[0]?.type
        
        if (existingType && existingType !== ad.value.type) {
          toast.value?.add('Możesz porównywać tylko ogłoszenia tego samego typu', 'error')
          return
        }
      } catch (error) {
        console.error('Error checking existing ads:', error)
      }
    }
    
    comparison.push(ad.value.id)
  }

  localStorage.setItem('comparison', JSON.stringify(comparison))
  checkComparisonStatus()
  
  // Track comparison event
  if (comparison.includes(ad.value.id)) {
    analytics.addToComparison(ad.value.id)
  }

  // Użyj niestandardowego zdarzenia, które działa w ramach tej samej karty
  window.dispatchEvent(new CustomEvent('localStorageChange'))
}

const getStreetViewEmbedUrl = (): string => {
  if (!ad.value) return ''
  
  const lat = ad.value.latitude
  const lng = ad.value.longitude
  const apiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY
  
  // Google Maps Embed API - Street View mode
  // Format: https://www.google.com/maps/embed/v1/streetview?key=API_KEY&location=lat,lng
  if (!apiKey) {
    console.warn('Google Maps API key not configured')
    streetViewError.value = true
    return ''
  }
  
  const params = new URLSearchParams({
    key: apiKey,
    location: `${lat},${lng}`,
    heading: '0',
    pitch: '0',
    fov: '80'
  })
  
  return `https://www.google.com/maps/embed/v1/streetview?${params.toString()}`
}

const handleStreetViewError = () => {
  streetViewError.value = true
  streetViewLoading.value = false
  streetViewCached.value = true
  console.error('Failed to load Street View')
}

const handleStreetViewLoad = (event: Event) => {
  const iframe = event.target as HTMLIFrameElement
  
  // Give iframe time to fully load and execute scripts
  setTimeout(() => {
    try {
      // Try to access iframe's window object to detect if it loaded error response
      const iframeWindow = iframe.contentWindow
      
      // Check if iframe has any content
      if (!iframeWindow || !iframeWindow.document || !iframeWindow.document.body) {
        console.warn('❌ Iframe body is empty')
        handleStreetViewError()
        return
      }
      
      // Try to find error indicators in the page
      const bodyHTML = iframeWindow.document.body.innerHTML || ''
      const bodyText = iframeWindow.document.body.innerText || ''
      
      console.log('🔍 Iframe loaded, checking content...')
      
      // Check for error messages
      if (bodyHTML.includes('no images') || 
          bodyHTML.includes('Search returned no images') ||
          bodyText.includes('no images') ||
          bodyText.includes('error')) {
        console.warn('❌ Street View error detected in iframe')
        handleStreetViewError()
        return
      }
      
      // Check if body is completely empty (black screen)
      if (bodyHTML.trim() === '' && bodyText.trim() === '') {
        console.warn('❌ Iframe is empty (black screen)')
        handleStreetViewError()
        return
      }
      
      // Success - iframe loaded with content
      console.log('✅ Street View iframe loaded successfully')
      streetViewLoading.value = false
      streetViewError.value = false
      streetViewCached.value = true
    } catch (e) {
      // CORS error - this is expected for Google Maps iframe
      // If iframe loaded without @error event, assume it's working
      console.log('⚠️ CORS - cannot access iframe content (expected for Google Maps)')
      streetViewLoading.value = false
      streetViewError.value = false
      streetViewCached.value = true
    }
  }, 2000)
}

const checkStreetViewAvailability = (): Promise<boolean> => {
  return new Promise((resolve) => {
    if (!ad.value) {
      resolve(false)
      return
    }

    try {
      // Load Google Maps API if not already loaded
      if (typeof window.google === 'undefined' || !window.google.maps) {
        console.log('Loading Google Maps API...')
        const script = document.createElement('script')
        script.src = `https://maps.googleapis.com/maps/api/js?key=${import.meta.env.VITE_GOOGLE_MAPS_API_KEY}`
        script.async = true
        script.defer = true
        script.onload = () => {
          checkStreetViewWithAPI(resolve)
        }
        script.onerror = () => {
          console.error('Failed to load Google Maps API')
          resolve(false)
        }
        document.head.appendChild(script)
      } else {
        checkStreetViewWithAPI(resolve)
      }
    } catch (error) {
      console.error('Error checking Street View:', error)
      resolve(false)
    }
  })
}

const checkStreetViewWithAPI = (resolve: (available: boolean) => void) => {
  if (!ad.value || typeof window.google === 'undefined') {
    resolve(false)
    return
  }

  try {
    const streetViewService = new window.google.maps.StreetViewService()
    const location = new window.google.maps.LatLng(ad.value.latitude, ad.value.longitude)

    streetViewService.getPanorama(
      { location: location, radius: 50 },
      (_data: any, status: any) => {
        if (status === window.google.maps.StreetViewStatus.OK) {
          console.log('✅ Street View available')
          resolve(true)
        } else {
          console.warn('❌ Street View not available:', status)
          resolve(false)
        }
      }
    )
  } catch (error) {
    console.error('Error with StreetViewService:', error)
    resolve(false)
  }
}

const toggleStreetView = async () => {
  if (showStreetView.value) {
    // Just hide it - keep cached state
    showStreetView.value = false
  } else {
    // Show it
    showStreetView.value = true
    
    // If not cached yet, check availability first
    if (!streetViewCached.value) {
      streetViewLoading.value = true
      streetViewError.value = false
      
      // Check if Street View is available using Google Maps API
      const available = await checkStreetViewAvailability()
      
      if (!available) {
        // Street View not available
        streetViewError.value = true
        streetViewLoading.value = false
        streetViewCached.value = true
        console.warn('Street View not available for this location')
        return
      }
      
      // Street View is available, generate URL and load iframe
      if (!streetViewUrl.value) {
        streetViewUrl.value = getStreetViewEmbedUrl()
      }
      
      // Set timeout - if not loaded in 3 seconds, show error
      setTimeout(() => {
        if (streetViewLoading.value) {
          streetViewError.value = true
          streetViewLoading.value = false
          streetViewCached.value = true
        }
      }, 3000)
    }
  }
}

const initMap = () => {
  if (!mapContainer.value || !ad.value || map) return

  try {
    // Granice Polski (bardziej rozszerzone) - aby markery/popupy nie były ucinane
    const polandBounds = L.latLngBounds(
      [47.5, 12.0],  // południowo-zachodni róg
      [57.5, 26.0]   // północno-wschodni róg
    )

    map = L.map(mapContainer.value, {
      attributionControl: false,
      maxBounds: polandBounds,        // Nie można przesunąć mapy poza te granice
      maxBoundsViscosity: 1.0,        // Twarde ograniczenie (nie można przeciągnąć poza)
      minZoom: 4.5,                      // Minimalne przybliżenie (cała Polska)
      maxZoom: 18                      // Maksymalne przybliżenie
    }).setView([ad.value.latitude, ad.value.longitude], 15)

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map)

    L.marker([ad.value.latitude, ad.value.longitude]).addTo(map)
    
    // Invalidate size after a short delay to ensure proper rendering
    setTimeout(() => {
      if (map) map.invalidateSize()
    }, 100)
  } catch (error) {
    console.error('Error initializing map:', error)
  }
}

const loadAd = async () => {
  try {
    isLoading.value = true
    notFound.value = false

    // Ustaw tymczasowy tytuł z URL od razu (zanim załadują się dane)
    // dzięki temu karta przeglądarki nie pokazuje domyślnego "ReklaMap - Platforma..."
    const typeFromUrl = route.params.type as string
    const cityFromUrl = route.params.city as string
    const typeUrlMap: Record<string, string> = {
      'billboardy': 'Billboard', 'citylighty': 'Citylight',
      'ekrany-led': 'Ekran LED', 'banery': 'Baner',
      'sciany-reklamowe': 'Ściana reklamowa', 'totemy-reklamowe': 'Totem reklamowy',
      'reklama-w-transporcie': 'Reklama w transporcie', 'reklama-mobilna': 'Reklama mobilna'
    }
    const typeLabelPrelim = typeUrlMap[typeFromUrl] || 'Powierzchnia reklamowa'
    const cityPrelim = cityFromUrl
      ? cityFromUrl.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
      : ''
    if (cityPrelim) {
      const prelimTitle = `${typeLabelPrelim} – ${cityPrelim} | ReklaMap`
      document.title = prelimTitle
      // Also update seoOptions to prevent overwrite
      seoOptions.value.title = prelimTitle
      updateMetaTags()
    }

    // Pobierz ID z parametru URL - może być w formacie slug-id
    const idParam = route.params.id as string
    const adId = idParam.includes('-') ? idParam.split('-').pop() || idParam : idParam

    const data = await api.getAdvertisement(adId)
    
    // Debug - sprawdź co przyszło z API
    console.log('📥 Dane z API:', {
      id: data?.id,
      status: data?.status,
      display_status: data?.display_status,
      available_from: data?.available_from
    })

    if (!data) {
      notFound.value = true
      isLoading.value = false
      return
    }

    // Block access to inactive advertisements - show 404 without changing URL
    if (!data.is_active) {
      notFound.value = true
      isLoading.value = false
      return
    }

    ad.value = data
    
    // Track GA4 view event
    analytics.viewAd(data.id, data.title, data.city)
    
    // Load daily stats for views
    try {
      const response = await api.getDailyStats(adId)
      if (response && response.summary && response.summary.total_views) {
        dailyStatsViews.value = response.summary.total_views
      }
    } catch (error) {
      console.error('Error loading daily stats:', error)
      // Fallback to ad.views if daily stats fail
    }
    
    // Sprawdź czy URL jest poprawny, jeśli nie - przekieruj na poprawny
    const correctPath = `/powierzchnia-reklamowa/${mapTypeToUrlFormat(data.type)}/${slugify(data.city)}/${slugify(data.title)}-${data.id}`
    const currentPath = router.currentRoute.value.path
    
    if (currentPath !== correctPath && !currentPath.includes('/ogloszenie/')) {
      await router.replace(correctPath)
      window.scrollTo({ top: 0, behavior: 'instant' })
      return
    }
    
    checkFavoriteStatus()
    checkComparisonStatus()
    
    // Initialize map with longer delay to ensure DOM is ready
    setTimeout(() => initMap(), 500)
    
    loadSimilarAds()
    
    // Increment views
    api.incrementViews(adId)
  } catch (error) {
    console.error('Error loading ad:', error)
    notFound.value = true
  } finally {
    isLoading.value = false
    // Scroll to top after content loads, counteracting any layout shifts
    window.scrollTo({ top: 0, behavior: 'instant' })
  }
}

const loadSimilarAds = async () => {
  if (!ad.value) return

  try {
    const data = await api.getSimilarAdvertisements(ad.value)
    similarAds.value = data || []
  } catch (error) {
    console.error('Error loading similar ads:', error)
  }
}

const validateContactForm = (): boolean => {
  contactErrors.value = {}

  if (!contactForm.value.email) {
    contactErrors.value.email = 'E-mail jest wymagany'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(contactForm.value.email)) {
    contactErrors.value.email = 'Nieprawidłowy format e-mail'
  }

  if (!contactForm.value.message) {
    contactErrors.value.message = 'Wiadomość jest wymagana'
  }

  return Object.keys(contactErrors.value).length === 0
}

const submitContactForm = async () => {
  if (!validateContactForm() || !ad.value) return

  try {
    isSubmittingContact.value = true

    // Get reCAPTCHA token
    let recaptchaToken = ''
    if (isRecaptchaAvailable()) {
      recaptchaToken = await getRecaptchaToken('contact_owner')
    }

    await api.contactAdvertisementOwner(ad.value.id, {
      email: contactForm.value.email,
      message: contactForm.value.message,
      recaptcha_token: recaptchaToken
    })

    // Track email click statistics
    api.incrementEmailClicks(ad.value.id)
    analytics.sendAdMessage(ad.value.id)

    contactSuccess.value = true
    contactForm.value.email = ''
    contactForm.value.message = ''

    setTimeout(() => {
      contactSuccess.value = false
    }, 5000)
  } catch (error) {
    console.error('Error sending message:', error)
    contactErrors.value.submit = 'Wystąpił błąd podczas wysyłania wiadomości'
  } finally {
    isSubmittingContact.value = false
  }
}

const currentUrl = computed(() => {
  if (typeof window !== 'undefined') {
    return window.location.href
  }
  return ''
})

const formatLocation = (location: string, city: string) => {
  // Extract street and number from full address
  // Format: "35, Łąkowa, Klucze, gmina Głogów, powiat głogowski..."
  // We want: "Łąkowa 35, Klucze"
  
  const parts = location.split(',').map(p => p.trim())
  
  // Try to find street name (usually second part) and number (first part if it's a number)
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

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('pl-PL', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const handleShowPhone = () => {
  if (!ad.value) return
  showPhone.value = true
  // Track phone click statistics
  api.incrementPhoneClicks(ad.value.id)
  analytics.clickPhone(ad.value.id, ad.value.title)
}

const getMaskedPhone = (phone: string | undefined) => {
  if (!phone) return '+48 XXX XXX XXX'
  // Remove all non-digits
  let cleaned = phone.replace(/\D/g, '')
  // Remove +48 prefix if present (will be 48 followed by 9 digits)
  if (cleaned.startsWith('48') && cleaned.length === 11) {
    cleaned = cleaned.slice(2)
  }
  if (cleaned.length < 9) return phone

  const start = cleaned.slice(0, 3)
  const end = cleaned.slice(-3)
  const middle = 'X'.repeat(cleaned.length - 6)

  return `+48 ${start} ${middle} ${end}`
}

const getFullPhone = (phone: string | undefined) => {
  if (!phone) return '+48 XXX XXX XXX'
  // Remove all non-digits
  let cleaned = phone.replace(/\D/g, '')
  // Remove +48 prefix if present (will be 48 followed by 9 digits)
  if (cleaned.startsWith('48') && cleaned.length === 11) {
    cleaned = cleaned.slice(2)
  }
  if (cleaned.length < 9) return phone

  return `+48 ${cleaned.slice(0, 3)} ${cleaned.slice(3, 6)} ${cleaned.slice(6)}`
}

// Move axios import to top (removed from here)

const showReportModal = ref(false)
const reportForm = ref({
  reason: '',
  details: ''
})
const isSubmittingReport = ref(false)
const showActionsMenu = ref(false)
const showSpecifications = ref(false)

// Używamy komponentu ToastNotification zamiast własnego obiektu toast

const showToast = (message: string, type: 'success' | 'error' = 'success') => {
  toast.value?.add(message, type)
}

const handlePrint = async () => {
  if (!ad.value) return
  
  isGeneratingPDF.value = true

  try {
    const response = await axios.get(`/api/listings/${ad.value.id}/pdf`, {
        responseType: 'blob'
    })
    
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    
    // Remove existing print iframe if any
    const existingIframe = document.getElementById('print-iframe')
    if (existingIframe) {
      document.body.removeChild(existingIframe)
    }

    // Create invisible iframe
    const iframe = document.createElement('iframe')
    iframe.id = 'print-iframe'
    // Use visibility hidden instead of display none for better browser support
    iframe.style.position = 'fixed'
    iframe.style.right = '0'
    iframe.style.bottom = '0'
    iframe.style.width = '0'
    iframe.style.height = '0'
    iframe.style.border = '0'
    iframe.style.visibility = 'hidden'
    iframe.src = url
    document.body.appendChild(iframe)
    
    iframe.onload = () => {
      if (iframe.contentWindow) {
        iframe.contentWindow.focus()
        iframe.contentWindow.print()
      }
    }
  } catch (error) {
    console.error('Error printing PDF:', error)
    showToast('Nie udało się przygotować pliku do druku', 'error')
  } finally {
    isGeneratingPDF.value = false
  }
}

const isGeneratingPDF = ref(false)

const handleDownloadPDF = async () => {
  if (!ad.value) return
  
  isGeneratingPDF.value = true

  try {
    const response = await axios.get(`/api/listings/${ad.value.id}/pdf`, {
        responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `ogloszenie-${ad.value.id}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    showToast('PDF został pobrany', 'success')
  } catch (error) {
    console.error('Error generating PDF:', error)
    showToast('Nie udało się wygenerować PDF', 'error')
  } finally {
    isGeneratingPDF.value = false
  }
}

// Share Logic
const showShareModal = ref(false)

// Prevent body scroll when modals are open
watch(showShareModal, (isOpen) => {
  if (typeof document !== 'undefined') {
    if (isOpen) {
      document.body.style.overflow = 'hidden'
      document.body.style.position = 'fixed'
      document.body.style.width = '100%'
    } else {
      document.body.style.overflow = 'auto'
      document.body.style.position = 'static'
      document.body.style.width = 'auto'
    }
  }
})

watch(showReportModal, (isOpen) => {
  if (typeof document !== 'undefined') {
    if (isOpen) {
      document.body.style.overflow = 'hidden'
      document.body.style.position = 'fixed'
      document.body.style.width = '100%'
    } else {
      document.body.style.overflow = 'auto'
      document.body.style.position = 'static'
      document.body.style.width = 'auto'
    }
  }
})

const handleShare = async () => {
  if (!ad.value) return

  const shareData = {
    title: ad.value.title,
    text: `Zobacz to ogłoszenie: ${ad.value.title}`,
    url: window.location.href
  }

  if (navigator.share) {
    try {
      await navigator.share(shareData)
    } catch (err) {
      console.error('Error sharing:', err)
    }
  } else {
    showShareModal.value = true
  }
}

const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(window.location.href)
    showToast('Link został skopiowany do schowka', 'success')
  } catch (err) {
    console.error('Failed to copy:', err)
    showToast('Nie udało się skopiować linku', 'error')
  }
}

const shareToSocial = (platform: 'facebook' | 'twitter' | 'whatsapp' | 'linkedin') => {
  const url = encodeURIComponent(window.location.href)
  const text = encodeURIComponent(`Zobacz to ogłoszenie: ${ad.value?.title}`)
  
  let shareUrl = ''
  switch (platform) {
    case 'facebook':
      shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`
      break
    case 'twitter':
      shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${text}`
      break
    case 'whatsapp':
      shareUrl = `https://wa.me/?text=${text}%20${url}`
      break
    case 'linkedin':
      shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${url}`
      break
  }
  
  window.open(shareUrl, '_blank', 'width=600,height=400')
}

const reportReasons = [
  { value: 'spam', label: 'Spam lub oszustwo' },
  { value: 'inappropriate', label: 'Treści nieodpowiednie' },
  { value: 'incorrect_info', label: 'Nieprawdziwe informacje' },
  { value: 'duplicate', label: 'Duplikat ogłoszenia' },
  { value: 'other', label: 'Inne' }
]

const openReportModal = () => {
  showReportModal.value = true
}

const closeReportModal = () => {
  showReportModal.value = false
  reportForm.value = { reason: '', details: '' }
}

const submitReport = async () => {
  if (!reportForm.value.reason || !ad.value) return

  try {
    isSubmittingReport.value = true
    
    // Get reCAPTCHA token
    let recaptchaToken = ''
    if (isRecaptchaAvailable()) {
      recaptchaToken = await getRecaptchaToken('report_advertisement')
    }

    await api.submitReport({
      advertisement_id: ad.value.id,
      reason: reportForm.value.reason,
      details: reportForm.value.details,
      recaptcha_token: recaptchaToken
    })

    closeReportModal()
    showToast('Dziękujemy za zgłoszenie. Przyjrzymy się tej sprawie.', 'success')
  } catch (error) {
    console.error('Error submitting report:', error)
    showToast('Wystąpił błąd podczas wysyłania zgłoszenia. Spróbuj ponownie później.', 'error')
  } finally {
    isSubmittingReport.value = false
  }
}

// Funkcja do aktualizacji stanu przycisków na podstawie localStorage
const handleStorageChange = () => {
  if (ad.value) {
    checkFavoriteStatus()
    checkComparisonStatus()
  }
}

// Nasłuchuj zmian parametru id w URL
watch(() => route.params.id, (newId) => {
  if (newId) {
    loadAd()
  }
}, { immediate: true })

// Prevent body scroll when mobile actions menu is open
watch(showActionsMenu, (isOpen) => {
  if (typeof window !== 'undefined') {
    document.body.style.overflow = isOpen ? 'hidden' : 'auto'
  }
})

// Block body scroll when report modal is open
watch(showReportModal, (isOpen) => {
  if (typeof window !== 'undefined') {
    document.body.style.overflow = isOpen ? 'hidden' : 'auto'
  }
})

onMounted(() => {
  // Dodatkowe sprawdzenie stanu po zamontowaniu komponentu
  setTimeout(() => {
    if (ad.value) {
      checkFavoriteStatus()
      checkComparisonStatus()
    }
  }, 100)
  
  // Nasłuchuj zmian w localStorage
  if (typeof window !== 'undefined') {
    window.addEventListener('localStorageChange', handleStorageChange)
    window.addEventListener('storage', handleStorageChange)
    window.addEventListener('keydown', handlePreviewKeydown)
  }
})

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('localStorageChange', handleStorageChange)
    window.removeEventListener('storage', handleStorageChange)
    window.removeEventListener('keydown', handlePreviewKeydown)
    // Restore body scroll on unmount
    document.body.style.overflow = 'auto'
    document.body.style.position = 'static'
    document.body.style.width = 'auto'
  }
  
  // Clean up map instance to prevent memory leaks and conflicts
  if (map) {
    try {
      map.remove()
      map = null
    } catch (error) {
      console.error('Error cleaning up map:', error)
    }
  }
})
</script>

<template>
  <div class="listing-detail-page">
    <div v-if="isLoading" class="loading-container">
      <div class="spinner"></div>
      <p>Ładowanie ogłoszenia...</p>
    </div>

    <div v-else-if="notFound" class="not-found-container">
      <div class="not-found-content">
        <div class="error-code">404</div>
        <h1>Ogłoszenie nie zostało znalezione</h1>
        <p>Wygląda na to, że ogłoszenie, którego szukasz, nie istnieje, zostało usunięte lub jest nieaktywne.</p>
        
        <div class="actions">
          <button @click="router.push('/')" class="btn btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Wróć na stronę główną
          </button>
        </div>
      </div>
    </div>

    <div v-else-if="ad" class="page-container">
      <button @click="router.back()" class="back-button">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
          <path d="M15 10H5M5 10L10 15M5 10L10 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Powrót do listy
      </button>

      <!-- SEO Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <div class="content-layout">
        <div class="main-content">
          <div class="image-gallery">
            <div class="main-image-wrapper" @click="openImagePreview" :class="{ 'cursor-pointer': images.length > 0 }">
              <WebPImage
                v-if="images.length > 0"
                :src="images[currentImageIndex]"
                :alt="imageAlt"
                class="main-image"
              />
              <div v-else class="no-image">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none">
                  <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                  <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                  <path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <p>Brak zdjęcia</p>
              </div>

              <button v-if="images.length > 1" @click="prevImage" class="nav-btn prev">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                  <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
              <button v-if="images.length > 1" @click="nextImage" class="nav-btn next">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                  <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
            </div>

            <div v-if="images.length > 0" class="thumbnails">
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
          </div>

          <!-- Mobile Sidebar Card - wyświetlany tylko na mobile -->
          <div class="sidebar-card mobile-sidebar-card">
            <div class="status-badge" :class="statusClass">
              {{ statusLabel }}
            </div>

            <div class="sidebar-info">
              <div class="info-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                  <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span>{{ formatDate(ad.created_at) }}</span>
              </div>
              <div class="info-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/>
                  <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                </svg>
                <span>{{ displayViews }} wyświetleń</span>
              </div>
            </div>

            <div v-if="ad.phone && ad.phone.trim()" class="phone-section">
              <button v-if="!showPhone" @click="handleShowPhone" class="btn btn-phone">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="2"/>
                </svg>
                {{ getMaskedPhone(ad.phone) }}
              </button>
              <div v-else class="phone-display">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="2"/>
                </svg>
                <a :href="`tel:${ad.phone}`" class="phone-number">{{ getFullPhone(ad.phone) }}</a>
              </div>
            </div>

            <!-- Mobile Actions Bar (Fixed Bottom) -->
            <div class="actions-mobile">
              <button @click="showActionsMenu = !showActionsMenu" class="mobile-actions-bar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Zobacz opcje</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" :class="{ 'rotate-180': showActionsMenu }">
                  <path d="M19 14l-7 7m0 0l-7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>

              <div v-if="showActionsMenu" class="mobile-menu-overlay" @click="showActionsMenu = false">
                <div class="mobile-menu-content" @click.stop>
                  <div class="mobile-menu-header">
                    <h3>Opcje</h3>
                    <button @click="showActionsMenu = false" class="mobile-menu-close">
                      <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                  </div>

                  <div class="mobile-menu-items">
                    <button type="button" @click="toggleFavorite(); showActionsMenu = false" class="mobile-menu-item" :class="{ active: isFavorite }">
                      <svg width="20" height="20" viewBox="0 0 24 24" :fill="isFavorite ? '#EF4444' : 'none'">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" :stroke="isFavorite ? '#EF4444' : 'currentColor'" stroke-width="2"/>
                      </svg>
                      <span>{{ isFavorite ? 'Usuń z ulubionych' : 'Dodaj do ulubionych' }}</span>
                    </button>

                    <button type="button" @click="toggleComparison(); showActionsMenu = false" class="mobile-menu-item" :class="{ active: isInComparison }">
                      <svg width="20" height="20" viewBox="0 0 24 24" :fill="isInComparison ? '#667eea' : 'none'">
                        <rect x="3" y="3" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
                      </svg>
                      <span>{{ isInComparison ? 'Usuń z porównania' : 'Dodaj do porównania' }}</span>
                    </button>

                    <div class="mobile-menu-divider"></div>

                    <button type="button" @click="handlePrint(); showActionsMenu = false" class="mobile-menu-item">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6 14h12v8H6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      <span>Drukuj</span>
                    </button>

                    <button type="button" @click="handleDownloadPDF(); showActionsMenu = false" class="mobile-menu-item" :disabled="isGeneratingPDF">
                      <svg v-if="isGeneratingPDF" class="animate-spin" width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      <span>{{ isGeneratingPDF ? 'Generowanie...' : 'Pobierz PDF' }}</span>
                    </button>

                    <button type="button" @click="handleShare(); showActionsMenu = false" class="mobile-menu-item">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <circle cx="18" cy="5" r="3" stroke="currentColor" stroke-width="2"/>
                        <circle cx="6" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                        <circle cx="18" cy="19" r="3" stroke="currentColor" stroke-width="2"/>
                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" stroke="currentColor" stroke-width="2"/>
                        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" stroke="currentColor" stroke-width="2"/>
                      </svg>
                      <span>Udostępnij</span>
                    </button>

                    <button type="button" @click="openReportModal(); showActionsMenu = false" class="mobile-menu-item report-btn">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      <span>Zgłoś naruszenie</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="specs-section">
            <h1 class="listing-title">{{ ad.title }}</h1>
            <div class="price-section">
              <div class="price-main">
                {{ ad.price }} PLN
                <span v-if="ad.price_negotiable" class="negotiable-badge">Do negocjacji</span>
              </div>
              <div class="price-details">
                <span v-if="showPricePerSqm">{{ pricePerSqm }} PLN/m²</span>
                <span v-if="showPricePerSqm">•</span>
                <span>{{ getPriceUnitLabel(ad.price_unit) }}</span>
              </div>
            </div>

            <button type="button" @click="showSpecifications = !showSpecifications" class="specs-toggle-btn">
              {{ showSpecifications ? 'Ukryj specyfikację' : 'Pokaż specyfikację' }}
              <span class="arrow" :class="{ expanded: showSpecifications }">▼</span>
            </button>

            <div class="specs-grid" v-show="showSpecifications">
              <div class="spec-item">
                <div class="spec-label">Typ powierzchni</div>
                <div class="spec-value">{{ getTypeLabel(ad.type) }}</div>
              </div>

              <div v-if="showDimensions" class="spec-item">
                <div class="spec-label">Wymiary</div>
                <div class="spec-value">
                  <span v-if="ad.type === 'led_screen'">{{ (ad.width * 1000).toFixed(0) }}mm × {{ (ad.height * 1000).toFixed(0) }}mm ({{ surfaceArea }} m²)</span>
                  <span v-else>{{ ad.width }}m × {{ ad.height }}m ({{ surfaceArea }} m²)</span>
                </div>
              </div>

              <div v-if="showVariant" class="spec-item">
                <div class="spec-label">Wariant</div>
                <div class="spec-value">{{ getVariantLabel(ad.variant!) }}</div>
              </div>

              <div v-if="showDimensions" class="spec-item">
                <div class="spec-label">Orientacja</div>
                <div class="spec-value">{{ ad.orientation === 'horizontal' ? 'Poziom' : 'Pion' }}</div>
              </div>

              <div class="spec-item">
                <div class="spec-label">Lokalizacja</div>
                <div class="spec-value">{{ formatLocation(ad.location, ad.city) }}</div>
              </div>
              <div v-if="showTrafficIntensity" class="spec-item">
                <div class="spec-label">Natężenie ruchu</div>
                <div class="spec-value">
                  {{ ad.traffic_intensity === 'low' ? 'Niskie' : ad.traffic_intensity === 'medium' ? 'Średnie' : 'Wysokie' }}
                </div>
              </div>

              <div v-if="showEstimatedDailyViews" class="spec-item">
                <div class="spec-label">Zasięg dzienny (OTS)</div>
                <div class="spec-value spec-premium">{{ (ad as any).estimated_daily_views.toLocaleString('pl-PL') }} osób</div>
              </div>

              <div v-if="showRoadClass" class="spec-item">
                <div class="spec-label">Klasa drogi</div>
                <div class="spec-value">{{ getRoadClassLabel(ad.road_class!) }}</div>
              </div>

              <div v-if="locationTier" class="spec-item">
                <div class="spec-label">Klasa lokalizacji</div>
                <div class="spec-value" :class="{ 'spec-premium': locationTier === 'PREMIUM', 'spec-standard': locationTier === 'STANDARD' }">
                  {{ locationTier }}
                </div>
              </div>

              <div v-if="showTrafficDirection" class="spec-item">
                <div class="spec-label">Kierunek ruchu</div>
                <div class="spec-value">{{ formatTrafficDirection }}</div>
              </div>

              <div v-if="showTrafficType" class="spec-item">
                <div class="spec-label">Rodzaj ruchu</div>
                <div class="spec-value">{{ formatTrafficType }}</div>
              </div>

              <div v-if="showEnvironment" class="spec-item">
                <div class="spec-label">Środowisko</div>
                <div class="spec-value">{{ getEnvironmentLabel(ad.environment!) }}</div>
              </div>

              <div v-if="showLightingType" class="spec-item">
                <div class="spec-label">Typ oświetlenia</div>
                <div class="spec-value">{{ getLightingTypeLabel((ad as any).lighting_type!) }}</div>
              </div>

              <div v-if="showLightingTypeBanner" class="spec-item">
                <div class="spec-label">Typ oświetlenia</div>
                <div class="spec-value">{{ getLightingTypeLabel((ad as any).lighting_type_banner!) }}</div>
              </div>

              <div v-if="showDailyPassengers" class="spec-item">
                <div class="spec-label">Liczba pasażerów dziennie</div>
                <div class="spec-value">{{ (ad as any).daily_passengers }}</div>
              </div>

              <div v-if="showOperatingZone" class="spec-item">
                <div class="spec-label">Strefa operacyjna</div>
                <div class="spec-value">{{ getOperatingZoneLabel((ad as any).operating_zone!) }}</div>
              </div>

              <div v-if="showResolution" class="spec-item">
                <div class="spec-label">Rozdzielczość</div>
                <div class="spec-value">{{ (ad as any).resolution }}</div>
              </div>

              <div v-if="showPixelPitch" class="spec-item">
                <div class="spec-label">Pixel Pitch</div>
                <div class="spec-value">{{ (ad as any).pixel_pitch }} mm</div>
              </div>

              <div v-if="showBrightness" class="spec-item">
                <div class="spec-label">Jasność</div>
                <div class="spec-value">{{ (ad as any).brightness }} nits</div>
              </div>

              <div v-if="showAmbientLightControl" class="spec-item">
                <div class="spec-label">Dostosowanie do otoczenia</div>
                <div class="spec-value">✓ Tak</div>
              </div>

              <div v-if="showCampaignDuration" class="spec-item">
                <div class="spec-label">Czas trwania kampanii</div>
                <div class="spec-value">{{ ad.campaign_duration }} dni</div>
              </div>

              <div v-if="showTransportScope" class="spec-item">
                <div class="spec-label">Zakres</div>
                <div class="spec-value">{{ getTransportScopeLabel(ad.transport_scope!) }}</div>
              </div>

              <div v-if="showVehicleCount" class="spec-item">
                <div class="spec-label">Liczba pojazdów</div>
                <div class="spec-value">{{ ad.vehicle_count }}</div>
              </div>

              <div v-if="showMobileExposureMode" class="spec-item">
                <div class="spec-label">Tryb ekspozycji</div>
                <div class="spec-value">{{ getMobileExposureModeLabel(ad.mobile_exposure_mode!) }}</div>
              </div>

              <div v-if="showOperatingHours" class="spec-item">
                <div class="spec-label">Godziny działania</div>
                <div class="spec-value">{{ ad.operating_hours }}</div>
              </div>

              <div v-if="showRouteArea" class="spec-item">
                <div class="spec-label">Trasa / Obszar</div>
                <div class="spec-value">{{ ad.route_area }}</div>
              </div>

              <div v-if="showLighting" class="spec-item">
                <div class="spec-label">Podświetlenie</div>
                <div class="spec-value spec-yes">Tak</div>
              </div>

              <div v-if="showPrint" class="spec-item">
                <div class="spec-label">Druk w cenie</div>
                <div class="spec-value spec-yes">Tak</div>
              </div>

              <div v-if="showMounting" class="spec-item">
                <div class="spec-label">Montaż w cenie</div>
                <div class="spec-value spec-yes">Tak</div>
              </div>

              <div v-if="showGraphicDesign" class="spec-item">
                <div class="spec-label">Pomoc graficzna</div>
                <div class="spec-value spec-yes">Dostępna</div>
              </div>

              <div class="spec-item">
                <div class="spec-label">Rodzaj oferty</div>
                <div class="spec-value">{{ ad.offer_type === 'owner' ? 'Właściciel (bezpośrednio)' : ad.offer_type === 'agency' ? 'Agencja reklamowa' : ad.offer_type === 'sublease' ? 'Podnajmujący' : ad.offer_type }}</div>
              </div>

              <div class="spec-item" v-if="ad.has_vat_invoice">
                <div class="spec-label">Faktura VAT</div>
                <div class="spec-value spec-yes">Tak</div>
              </div>
            </div>
          </div>

          <div class="map-section">
            <h2>Lokalizacja na mapie</h2>
            <div class="map-container" ref="mapContainer"></div>
          </div>

          <div class="street-view-section">
            <div class="street-view-header">
              <h2>Wirtualny spacer</h2>
              <button 
                v-if="!showStreetView" 
                @click="toggleStreetView"
                class="btn btn-secondary street-view-toggle"
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z" fill="currentColor"/>
                </svg>
                Pokaż Street View
              </button>
              <button 
                v-else 
                @click="toggleStreetView"
                class="btn btn-secondary street-view-toggle"
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z" fill="currentColor"/>
                </svg>
                Ukryj Street View
              </button>
            </div>

            <!-- Iframe always in DOM - never removed to prevent reloading -->
            <div
              class="street-view-cached-iframe"
              :style="{ display: streetViewUrl && !showStreetView ? 'none' : 'block' }"
            >
              <iframe
                v-if="streetViewUrl"
                :src="streetViewUrl"
                width="100%"
                height="400"
                style="border: none; border-radius: 8px;"
                allowfullscreen
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                @error="handleStreetViewError"
                @load="handleStreetViewLoad"
              ></iframe>
            </div>

            <div v-if="showStreetView" class="street-view-container">
              <div v-if="streetViewError" class="street-view-error">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                  <path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <div>
                  <h3>Street View niedostępny</h3>
                  <p>Niestety, Google Street View nie jest dostępny dla tej lokalizacji. Przyczyny mogą być następujące:</p>
                  <ul class="street-view-error-list">
                    <li>Brak pokrycia Street View w tym obszarze</li>
                    <li>Lokalizacja znajduje się w terenie niedostępnym dla Google Street View</li>
                    <li>Czasowy problem z ładowaniem danych</li>
                  </ul>
                  <p>Możesz zobaczyć lokalizację na mapie powyżej lub skontaktować się z właścicielem nośnika, aby uzyskać więcej informacji.</p>
                  <button @click="toggleStreetView" class="street-view-error-close">
                    Zamknij
                  </button>
                </div>
              </div>
              <div v-else class="street-view-iframe-wrapper">
                <p class="street-view-info">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                    <path d="M12 16v-4M12 8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  </svg>
                  Wirtualny spacer (Google Street View) pozwala zobaczyć lokalizację powierzchni reklamowej z perspektywy ulicy. Możesz obracać widok, zmieniać zoom i perspektywę.
                </p>
              </div>
            </div>
          </div>

          <div class="description-section">
            <h2>Opis</h2>
            <p class="description-text">{{ cleanDescription }}</p>
          </div>

          <div v-if="ad.contact_preference !== 'phone'" class="contact-form-section">
            <h2>Formularz kontaktowy</h2>

            <div v-if="contactSuccess" class="success-message">
              Wiadomość została wysłana pomyślnie!
            </div>
            
            <div v-if="contactErrors.submit" class="submit-error-message">
              {{ contactErrors.submit }}
            </div>

            <form @submit.prevent="submitContactForm" class="contact-form">
              <div class="form-group">
                <label class="form-label">Twój e-mail</label>
                <input
                  v-model="contactForm.email"
                  type="text"
                  class="form-input"
                  :class="{ 'error': contactErrors.email }"
                  placeholder="twoj@email.pl"
                />
                <span v-if="contactErrors.email" class="error-text">{{ contactErrors.email }}</span>
              </div>

              <div class="form-group">
                <label class="form-label">Wiadomość</label>
                <textarea
                  v-model="contactForm.message"
                  rows="5"
                  class="form-textarea"
                  :class="{ 'error': contactErrors.message }"
                  placeholder="Dzień dobry, interesuje mnie wynajem tej powierzchni reklamowej..."
                ></textarea>
                <span v-if="contactErrors.message" class="error-text">{{ contactErrors.message }}</span>
              </div>

              <button
                type="submit"
                class="btn btn-primary"
                :disabled="isSubmittingContact"
              >
                {{ isSubmittingContact ? 'Wysyłanie...' : 'Wyślij wiadomość' }}
              </button>
            </form>
          </div>
        </div>

        <!-- Desktop Sidebar -->
        <div class="sidebar desktop-sidebar">
          <div class="sidebar-card">
            <div class="status-badge" :class="statusClass">
              {{ statusLabel }}
            </div>

            <div class="sidebar-info">
              <div class="info-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                  <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span>{{ formatDate(ad.created_at) }}</span>
              </div>
              <div class="info-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/>
                  <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                </svg>
                <span>{{ displayViews }} wyświetleń</span>
              </div>
            </div>

            <div v-if="ad.phone && ad.phone.trim()" class="phone-section">
              <button v-if="!showPhone" @click="handleShowPhone" class="btn btn-phone">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="2"/>
                </svg>
                {{ getMaskedPhone(ad.phone) }}
              </button>
              <div v-else class="phone-display">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="2"/>
                </svg>
                <a :href="`tel:${ad.phone}`" class="phone-number">{{ getFullPhone(ad.phone) }}</a>
              </div>
            </div>

            <!-- Desktop Actions -->
            <div class="actions-desktop">
              <button @click="toggleFavorite" class="action-btn" :class="{ active: isFavorite }">
                <svg width="20" height="20" viewBox="0 0 24 24" :fill="isFavorite ? '#EF4444' : 'none'">
                  <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" :stroke="isFavorite ? '#EF4444' : 'currentColor'" stroke-width="2"/>
                </svg>
                {{ isFavorite ? 'Usuń z ulubionych' : 'Dodaj do ulubionych' }}
              </button>

              <button @click="toggleComparison" class="action-btn" :class="{ active: isInComparison }">
                <svg width="20" height="20" viewBox="0 0 24 24" :fill="isInComparison ? '#667eea' : 'none'">
                  <rect x="3" y="3" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
                  <rect x="14" y="3" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
                  <rect x="3" y="14" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
                  <rect x="14" y="14" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
                </svg>
                {{ isInComparison ? 'Usuń z porównania' : 'Dodaj do porównania' }}
              </button>

              <div class="actions-divider"></div>

              <button @click="handlePrint" class="action-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M6 14h12v8H6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Drukuj
              </button>

              <button @click="handleDownloadPDF" class="action-btn" :disabled="isGeneratingPDF">
                <svg v-if="isGeneratingPDF" class="animate-spin" width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{ isGeneratingPDF ? 'Generowanie...' : 'Pobierz PDF' }}
              </button>

              <button @click="handleShare" class="action-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <circle cx="18" cy="5" r="3" stroke="currentColor" stroke-width="2"/>
                  <circle cx="6" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                  <circle cx="18" cy="19" r="3" stroke="currentColor" stroke-width="2"/>
                  <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" stroke="currentColor" stroke-width="2"/>
                  <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" stroke="currentColor" stroke-width="2"/>
                </svg>
                Udostępnij
              </button>

              <button @click="openReportModal" class="action-btn report-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Zgłoś naruszenie
              </button>
            </div>

            <!-- Mobile Actions Bar (Fixed Bottom) -->
            <div class="actions-mobile">
              <button @click="showActionsMenu = !showActionsMenu" class="mobile-actions-bar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Zobacz opcje</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" :class="{ 'rotate-180': showActionsMenu }">
                  <path d="M19 14l-7 7m0 0l-7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>

              <div v-if="showActionsMenu" class="mobile-menu-overlay" @click="showActionsMenu = false">
                <div class="mobile-menu-content" @click.stop>
                  <div class="mobile-menu-header">
                    <h3>Opcje</h3>
                    <button @click="showActionsMenu = false" class="mobile-menu-close">
                      <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                  </div>

                  <div class="mobile-menu-items">
                    <button type="button" @click="toggleFavorite(); showActionsMenu = false" class="mobile-menu-item" :class="{ active: isFavorite }">
                      <svg width="20" height="20" viewBox="0 0 24 24" :fill="isFavorite ? '#EF4444' : 'none'">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" :stroke="isFavorite ? '#EF4444' : 'currentColor'" stroke-width="2"/>
                      </svg>
                      <span>{{ isFavorite ? 'Usuń z ulubionych' : 'Dodaj do ulubionych' }}</span>
                    </button>

                    <button type="button" @click="toggleComparison(); showActionsMenu = false" class="mobile-menu-item" :class="{ active: isInComparison }">
                      <svg width="20" height="20" viewBox="0 0 24 24" :fill="isInComparison ? '#667eea' : 'none'">
                        <rect x="3" y="3" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
                      </svg>
                      <span>{{ isInComparison ? 'Usuń z porównania' : 'Dodaj do porównania' }}</span>
                    </button>

                    <div class="mobile-menu-divider"></div>

                    <button type="button" @click="handlePrint(); showActionsMenu = false" class="mobile-menu-item">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6 14h12v8H6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      <span>Drukuj</span>
                    </button>

                    <button type="button" @click="handleDownloadPDF(); showActionsMenu = false" class="mobile-menu-item" :disabled="isGeneratingPDF">
                      <svg v-if="isGeneratingPDF" class="animate-spin" width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      <span>{{ isGeneratingPDF ? 'Generowanie...' : 'Pobierz PDF' }}</span>
                    </button>

                    <button type="button" @click="handleShare(); showActionsMenu = false" class="mobile-menu-item">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <circle cx="18" cy="5" r="3" stroke="currentColor" stroke-width="2"/>
                        <circle cx="6" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                        <circle cx="18" cy="19" r="3" stroke="currentColor" stroke-width="2"/>
                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" stroke="currentColor" stroke-width="2"/>
                        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" stroke="currentColor" stroke-width="2"/>
                      </svg>
                      <span>Udostępnij</span>
                    </button>

                    <button type="button" @click="openReportModal(); showActionsMenu = false" class="mobile-menu-item report-btn">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      <span>Zgłoś naruszenie</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="similarAds.length > 0" class="similar-listings">
            <h3>Podobne oferty</h3>
            <div class="similar-listings-list">
              <router-link
                v-for="similarAd in similarAds"
                :key="similarAd.id"
                :to="`/powierzchnia-reklamowa/${mapTypeToUrlFormat(similarAd.type)}/${slugify(similarAd.city)}/${slugify(similarAd.title)}-${similarAd.id}`"
                class="similar-listing-card"
              >
                <div class="similar-listing-image">
                  <WebPImage v-if="similarAd.image_url" :src="similarAd.image_url" :alt="`${getTypeLabel(similarAd.type)} ${similarAd.city} - ${similarAd.title}`" />
                  <div v-else class="similar-listing-no-image">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
                      <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                      <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                      <path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2"/>
                    </svg>
                  </div>
                </div>
                <div class="similar-listing-content">
                  <h4>{{ similarAd.title }}</h4>
                  <div class="similar-listing-price">{{ similarAd.price }} PLN</div>
                  <div class="similar-listing-location">{{ similarAd.city }}</div>
                </div>
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>



    <!-- Share Modal -->
    <div v-if="showShareModal" class="modal-overlay" @click.self="showShareModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Udostępnij ogłoszenie</h3>
          <button @click="showShareModal = false" class="close-btn">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
              <path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>

        <div class="share-content">
          <div class="share-link-group">
            <input type="text" :value="currentUrl" readonly class="share-input" />
            <button @click="copyLink" class="btn-copy">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <rect x="9" y="9" width="13" height="13" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="2"/>
              </svg>
              Kopiuj
            </button>
          </div>

          <div class="social-share-grid">
            <button @click="shareToSocial('facebook')" class="social-btn facebook">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
              </svg>
              Facebook
            </button>
            <button @click="shareToSocial('twitter')" class="social-btn twitter">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
              </svg>
              X
            </button>
            <button @click="shareToSocial('whatsapp')" class="social-btn whatsapp">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.008-.57-.008-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
              </svg>
              WhatsApp
            </button>
            <button @click="shareToSocial('linkedin')" class="social-btn linkedin">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/>
                <rect x="2" y="9" width="4" height="12"/>
                <circle cx="4" cy="4" r="2"/>
              </svg>
              LinkedIn
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Report Modal -->
    <div v-if="showReportModal" class="modal-overlay" @click.self="closeReportModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Zgłoś naruszenie</h3>
          <button @click="closeReportModal" class="close-btn">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
              <path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitReport" class="report-form">
          <div class="form-group">
            <label class="form-label">Powód zgłoszenia</label>
            <div class="radio-group">
              <label v-for="reason in reportReasons" :key="reason.value" class="radio-option">
                <input 
                  type="radio" 
                  v-model="reportForm.reason" 
                  :value="reason.value"
                  name="reportReason"
                />
                <span>{{ reason.label }}</span>
              </label>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Szczegóły (opcjonalnie)</label>
            <textarea
              v-model="reportForm.details"
              rows="4"
              class="form-textarea"
              placeholder="Opisz problem..."
            ></textarea>
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeReportModal" class="btn btn-secondary">
              Anuluj
            </button>
            <button 
              type="submit" 
              class="btn btn-danger"
              :disabled="!reportForm.reason || isSubmittingReport"
            >
              {{ isSubmittingReport ? 'Wysyłanie...' : 'Wyślij zgłoszenie' }}
            </button>
          </div>
        </form>
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
  <ToastNotification ref="toast" />
</template>

<style scoped>
/* Not Found Styles */
.not-found-container {
  min-height: 80vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f9fafb;
  padding: 2rem;
}

.not-found-content {
  max-width: 600px;
  width: 100%;
  text-align: center;
  background: white;
  padding: 4rem 2rem;
  border-radius: 24px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.not-found-content .error-code {
  font-size: 8rem;
  font-weight: 900;
  line-height: 1;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 1.5rem;
}

.not-found-content h1 {
  font-size: 2rem;
  color: #1f2937;
  margin-bottom: 1rem;
  font-weight: 800;
}

.not-found-content p {
  color: #6b7280;
  font-size: 1.1rem;
  margin-bottom: 2.5rem;
  line-height: 1.6;
}

.not-found-content .actions {
  display: flex;
  justify-content: center;
}

@media (max-width: 640px) {
  .not-found-content .error-code {
    font-size: 6rem;
  }

  .not-found-content h1 {
    font-size: 1.5rem;
  }

  .not-found-content {
    padding: 3rem 1.5rem;
  }
}

/* Share Modal Styles */
.share-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  padding: 2rem;
}

.share-link-group {
  display: flex;
  gap: 0.75rem;
  background: #f9fafb;
  padding: 0.5rem;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.share-input {
  flex: 1;
  padding: 0.75rem;
  border: none;
  background: transparent;
  color: #374151;
  font-size: 0.9rem;
  font-family: inherit;
  width: 100%;
}

.share-input:focus {
  outline: none;
}

.btn-copy {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  color: #374151;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.btn-copy:hover {
  background: #f3f4f6;
  border-color: #d1d5db;
  transform: translateY(-1px);
}

.btn-copy:active {
  transform: translateY(0);
}

.social-share-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.social-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 1rem;
  border: none;
  border-radius: 12px;
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.social-btn::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(rgba(255,255,255,0.1), rgba(255,255,255,0));
  opacity: 0;
  transition: opacity 0.3s;
}

.social-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.2);
}

.social-btn:hover::after {
  opacity: 1;
}

.social-btn:active {
  transform: translateY(-1px);
}

.social-btn.facebook { 
  background: linear-gradient(135deg, #1877F2 0%, #0C63D4 100%);
  box-shadow: 0 4px 12px rgba(24, 119, 242, 0.3);
}

.social-btn.twitter { 
  background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.social-btn.whatsapp { 
  background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
  box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
}

.social-btn.linkedin { 
  background: linear-gradient(135deg, #0A66C2 0%, #004182 100%);
  box-shadow: 0 4px 12px rgba(10, 102, 194, 0.3);
}

/* ... existing styles ... */
.listing-detail-page {
  min-height: calc(100vh - 200px);
  background: #f9fafb;
  padding: 2rem 0;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  gap: 1rem;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #e5e7eb;
  border-top-color: #10B981;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.page-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

.back-button {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: white;
  border: 2px solid #e5e7eb;
  color: #374151;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  margin-bottom: 2rem;
}

.back-button:hover {
  border-color: #10B981;
  color: #10B981;
  transform: translateX(-4px);
}

.content-layout {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 2rem;
}

.main-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.image-gallery {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.main-image-wrapper {
  position: relative;
  width: 100%;
  height: 500px;
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.main-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
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
}

.nav-btn:hover {
  background: white;
  color: #10B981;
}

.nav-btn.prev {
  left: 1rem;
}

.nav-btn.next {
  right: 1rem;
}

.thumbnails {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  padding: 0.75rem 0 0.5rem 0.5rem;
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

.thumbnail:hover {
  opacity: 0.9;
}

.no-image {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
  background: #f3f4f6;
}

.no-image p {
  margin-top: 1rem;
  font-weight: 500;
}

.specs-section,
.map-section,
.description-section,
.contact-form-section {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.listing-title {
  font-size: 2rem;
  color: #1f2937;
  margin: 0 0 1.5rem 0;
  font-weight: 700;
}

.price-section {
  padding: 1.5rem;
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  border-radius: 12px;
  margin-bottom: 2rem;
  border: 2px solid #86efac;
}

.price-main {
  font-size: 2.5rem;
  font-weight: 800;
  color: #166534;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.negotiable-badge {
  display: inline-block;
  font-size: 0.875rem;
  font-weight: 600;
  padding: 0.375rem 0.75rem;
  background: #fef3c7;
  color: #92400e;
  border-radius: 6px;
  border: 1px solid #fbbf24;
}

.price-details {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: #15803d;
  font-weight: 600;
}

.specs-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
}

.spec-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.spec-label {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}

.spec-value {
  font-size: 1.05rem;
  color: #1f2937;
  font-weight: 600;
}

.spec-yes {
  color: #10B981;
}

.spec-premium {
  color: #F59E0B;
  font-weight: 700;
  text-transform: uppercase;
}

.spec-standard {
  color: #6B7280;
  font-weight: 600;
  text-transform: uppercase;
}

.map-section h2,
.description-section h2,
.contact-form-section h2 {
  font-size: 1.5rem;
  color: #1f2937;
  margin: 0 0 1.5rem 0;
  font-weight: 700;
}

.map-container {
  height: 400px;
  border-radius: 12px;
  overflow: hidden;
  border: 2px solid #e5e7eb;
}

.description-text {
  color: #4b5563;
  line-height: 1.8;
  font-size: 1.05rem;
  margin: 0;
  white-space: pre-wrap;
}

.contact-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-weight: 600;
  color: #374151;
}

.form-input,
.form-textarea {
  padding: 0.875rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.2s;
  font-family: inherit;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #10B981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.form-input.error,
.form-textarea.error {
  border-color: #EF4444;
}

.error-text {
  color: #EF4444;
  font-size: 0.875rem;
  font-weight: 500;
}

.success-message {
  padding: 1rem 1.5rem;
  background: #d1fae5;
  border: 2px solid #6ee7b7;
  border-radius: 8px;
  color: #065f46;
  font-weight: 600;
  margin-bottom: 1.5rem;
}

.submit-error-message {
  padding: 1rem 1.5rem;
  background: #fee2e2;
  border: 2px solid #fca5a5;
  border-radius: 8px;
  color: #991b1b;
  font-weight: 600;
  margin-bottom: 1.5rem;
}

.btn {
  padding: 1rem 2rem;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.sidebar {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.sidebar-card {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.status-badge {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 700;
  text-align: center;
  font-size: 1.05rem;
}

.status-available {
  background: #d1fae5;
  color: #065f46;
  border: 2px solid #6ee7b7;
}

.status-reserved {
  background: #fee2e2;
  color: #991b1b;
  border: 2px solid #fca5a5;
}

.status-soon {
  background: #dbeafe;
  color: #1e40af;
  border: 2px solid #93c5fd;
}

.sidebar-info {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: #6b7280;
  font-size: 0.95rem;
  font-weight: 500;
}

.info-item svg {
  color: #9ca3af;
  flex-shrink: 0;
}

.phone-section {
  display: flex;
  flex-direction: column;
}

.btn-phone {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  background: #10B981;
  color: white;
  border: none;
  padding: 1rem;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-phone:hover {
  background: #059669;
  transform: translateY(-2px);
}

.phone-display {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: #f0fdf4;
  border: 2px solid #86efac;
  border-radius: 8px;
  color: #166534;
}

.phone-number {
  font-weight: 700;
  font-size: 1.1rem;
  color: #166534;
  text-decoration: none;
}

.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  background: white;
  color: #374151;
  border: 2px solid #e5e7eb;
  padding: 0.875rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.action-btn:nth-child(4):hover {
  border-color: #EF4444;
  color: #EF4444;
}

.action-btn:nth-child(4).active {
  background: #fef2f2;
  border-color: #EF4444;
  color: #EF4444;
}

.action-btn:nth-child(5):hover {
  border-color: #667eea;
  color: #667eea;
}

.action-btn:nth-child(5).active {
  background: #f5f3ff;
  border-color: #667eea;
  color: #667eea;
}

.similar-listings {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.similar-listings h3 {
  font-size: 1.25rem;
  color: #1f2937;
  margin: 0 0 1.5rem 0;
  font-weight: 700;
}

.similar-listings-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.similar-listing-card {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  text-decoration: none;
  transition: all 0.2s;
}

.similar-listing-card:hover {
  border-color: #10B981;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
  transform: translateY(-2px);
}

.similar-listing-image {
  width: 80px;
  height: 80px;
  flex-shrink: 0;
  border-radius: 8px;
  overflow: hidden;
  background: #f3f4f6;
}

.similar-listing-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.similar-listing-no-image {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
}

.similar-listing-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 0;
}

.similar-listing-content h4 {
  margin: 0;
  font-size: 0.95rem;
  color: #1f2937;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.similar-listing-price {
  font-weight: 700;
  color: #10B981;
  font-size: 1rem;
}

.similar-listing-location {
  font-size: 0.875rem;
  color: #6b7280;
}

/* Mobile Actions Menu */
.actions-desktop {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.actions-mobile {
  display: none;
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 500;
}

.mobile-actions-bar {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #8b9eff 0%, #9b7bb5 100%);
  color: white;
  border: none;
  padding: 1rem;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s;
  width: 100%;
  box-shadow: 0 -2px 8px rgba(102, 126, 234, 0.2);
}

.mobile-actions-bar:active {
  background: linear-gradient(135deg, #7a8ee8 0%, #8a6ba4 100%);
}

.mobile-actions-bar svg {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  color: white;
  flex-shrink: 0;
}

.mobile-actions-bar svg.rotate-180 {
  transform: rotate(180deg);
}

.mobile-menu-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: flex-end;
  z-index: 2000;
  animation: fadeIn 0.2s ease-out;
}

.mobile-menu-content {
  background: white;
  width: 100%;
  border-radius: 20px 20px 0 0;
  box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.15);
  animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  max-height: 80vh;
  overflow-y: auto;
  padding-bottom: 80px;
  z-index: 2001;
}

.mobile-menu-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  position: sticky;
  top: 0;
  background: white;
  z-index: 2002;
  width: 100%;
}

.mobile-menu-header h3 {
  margin: 0;
  font-size: 1.25rem;
  color: #1f2937;
  font-weight: 700;
}

.mobile-menu-close {
  background: none;
  border: none;
  color: #6b7280;
  cursor: pointer;
  padding: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.mobile-menu-close:hover {
  color: #EF4444;
  transform: scale(1.1);
}

.mobile-menu-items {
  padding: 1rem 0;
}

.mobile-menu-item {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  background: white;
  color: #374151;
  border: none;
  padding: 1.25rem 1.5rem;
  text-align: left;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  width: 100%;
  border-bottom: 1px solid #f3f4f6;
  font-size: 1rem;
  pointer-events: auto;
}

.mobile-menu-item:last-of-type {
  border-bottom: none;
}

.mobile-menu-item:active {
  background: #f9fafb;
  transform: scale(0.98);
}

.mobile-menu-item svg {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  transition: all 0.2s;
}

.mobile-menu-item span {
  flex: 1;
}

.mobile-menu-item.active {
  background: white;
  color: #10B981;
  border-left: 4px solid #10B981;
  padding-left: calc(1.5rem - 4px);
}

.mobile-menu-item.active svg {
  color: #10B981;
}

.mobile-menu-item.report-btn {
  color: #EF4444;
}

.mobile-menu-item.report-btn:active {
  background: #fef2f2;
}

.mobile-menu-item.report-btn svg {
  color: #EF4444;
}

.mobile-menu-divider {
  height: 1px;
  background: #e5e7eb;
}

@keyframes slideUp {
  from {
    transform: translateY(100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

@media (max-width: 1024px) {
  .content-layout {
    grid-template-columns: 1fr;
  }

  .sidebar {
    order: -1;
  }
}

@media (max-width: 768px) {
  .page-container {
    display: flex;
    flex-direction: column;
  }

  .content-layout {
    display: flex;
    flex-direction: column;
    order: 1;
  }

  .main-content {
    order: 1;
  }

  /* Ukryj desktop sidebar na mobile */
  .desktop-sidebar {
    display: none;
  }

  .image-gallery {
    order: 1;
  }

  /* Pokaż mobile sidebar card w main-content */
  .mobile-sidebar-card {
    display: flex;
    order: 2;
  }

  .specs-section {
    order: 3;
  }

  .description-section {
    order: 4;
  }

  .map-section {
    order: 5;
  }

  .street-view-section {
    order: 6;
  }

  .contact-form-section {
    order: 7;
  }

  .comparison-table-wrapper {
    cursor: default;
  }

  .comparison-table-wrapper:not(.zoomed) {
    cursor: zoom-in;
  }

  .comparison-table-wrapper.zoomed {
    cursor: zoom-out;
  }
  .actions-desktop {
    display: none;
  }

  .actions-mobile {
    display: block;
  }

  .specs-toggle-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem;
    background: rgba(102, 126, 234, 0.1);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 12px;
    color: #4F46E5;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
    margin: 1rem 0;
    width: 100%;
    pointer-events: auto;
  }

  .specs-toggle-btn:hover {
    background: rgba(102, 126, 234, 0.15);
    border-color: rgba(102, 126, 234, 0.3);
  }

  .specs-toggle-btn .arrow {
    transition: transform 0.3s ease;
    font-size: 0.75rem;
  }

  .specs-toggle-btn .arrow.expanded {
    transform: rotate(180deg);
  }

  .specs-grid {
    animation: slideDown 0.3s ease-out;
  }
}

@media (min-width: 769px) {
  .specs-toggle-btn {
    display: none;
  }

  .specs-section {
    display: block !important;
    animation: none;
  }

  .specs-grid {
    display: grid !important;
  }

  /* Ukryj mobile sidebar card na desktop */
  .mobile-sidebar-card {
    display: none !important;
  }
}

@media (max-width: 640px) {
  .page-container {
    padding: 0 1rem;
  }

  .image-container {
    height: 300px;
  }

  .listing-title {
    font-size: 1.5rem;
  }

  .price-main {
    font-size: 2rem;
  }

  .specs-grid {
    grid-template-columns: 1fr;
  }

  .map-container {
    height: 300px;
  }

  .preview-nav-btn {
    width: 48px;
    height: 48px;
  }

  .preview-nav-btn.prev {
    left: 0.5rem;
  }

  .preview-nav-btn.next {
    right: 0.5rem;
  }

  .preview-counter {
    bottom: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
  }

  .preview-close-btn {
    top: 0.5rem;
    right: 0.5rem;
  }

  .modal-overlay {
    padding: 0.5rem;
    top: 80px;
  }

  .modal-content {
    width: calc(100vw - 1rem);
    max-width: calc(100vw - 1rem);
    max-height: calc(100vh - 115px);
    border-radius: 16px;
    margin: auto;
  }

  .modal-header {
    padding: 1rem;
  }

  .modal-header h3 {
    font-size: 1.1rem;
  }

  .report-form {
    padding: 1rem;
  }

  .modal-actions {
    flex-direction: column;
    gap: 0.75rem;
  }

  .modal-actions button {
    width: 100%;
  }
}

/* Toast Notifications */
.toast-container {
  position: fixed;
  top: 90px;
  right: 2rem;
  left: auto;
  transform: none;
  z-index: 2000;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  pointer-events: none;
  align-items: flex-end;
}

.toast {
  background: white;
  padding: 1rem 1.5rem;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  gap: 1rem;
  min-width: 300px;
  animation: slideDown 0.3s ease-out;
  pointer-events: auto;
  border: 1px solid rgba(0,0,0,0.05);
}

.toast-success {
  border-left: 4px solid #10B981;
}

.toast-error {
  border-left: 4px solid #EF4444;
}

.toast-icon {
  flex-shrink: 0;
}

.toast-content {
  flex: 1;
}

.toast-title {
  font-weight: 700;
  font-size: 0.95rem;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.toast-message {
  font-size: 0.875rem;
  color: #6b7280;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Report Button */
.report-btn {
  color: #EF4444;
  border-color: #EF4444;
}

.report-btn:hover {
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
  transform: translateY(-2px);
}

/* Modal Styles */
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
  z-index: 1000;
  padding: 1rem;
  backdrop-filter: blur(8px);
  animation: fadeIn 0.2s ease-out;
  overflow-y: auto;
}

.modal-content {
  background: white;
  border-radius: 20px;
  width: 100%;
  max-width: 500px;
  max-height: calc(100vh - 115px);
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  animation: scaleIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  margin: auto;
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

.close-btn {
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

.close-btn:hover {
  background: #EF4444;
  color: white;
  border-color: #EF4444;
}

.report-form {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  overflow-y: auto;
}

.radio-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.radio-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  padding: 0.75rem 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  transition: all 0.2s;
  background: white;
}

.radio-option:hover {
  border-color: #EF4444;
  background: #fef2f2;
}

.radio-option:has(input:checked) {
  border-color: #EF4444;
  background: #fef2f2;
}

.radio-option input[type="radio"] {
  accent-color: #EF4444;
  width: 1.125rem;
  height: 1.125rem;
  margin: 0;
}

.radio-option span {
  font-weight: 500;
  color: #374151;
  font-size: 0.9rem;
}

.form-textarea {
  width: 100%;
  min-height: 80px;
  resize: vertical;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1rem;
  padding-top: 1.5rem;
  border-top: 1px solid #f3f4f6;
}

.btn-secondary {
  background: white;
  color: #374151;
  border: 2px solid #e5e7eb;
}

.btn-secondary:hover {
  background: #f9fafb;
  border-color: #d1d5db;
  color: #111827;
}

.action-btn.active {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border-color: #667eea;
  color: #667eea;
}

.actions-divider {
  height: 1px;
  background: linear-gradient(90deg, transparent 0%, #e5e7eb 20%, #e5e7eb 80%, transparent 100%);
  margin: 0.75rem 0;
  position: relative;
}

.actions-divider::before {
  content: '';
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  width: 40px;
  height: 1px;
  background: #d1d5db;
}

.btn-danger {
  background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
  color: white;
  border: none;
  box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);
}

.btn-danger:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.4);
}

.btn-danger:disabled {
  background: #fca5a5;
  transform: none;
  box-shadow: none;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
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

/* Image Preview Modal Styles */
.image-preview-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 1rem;
}

.preview-close-btn {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: white;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 8px;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(4px);
  z-index: 2001;
}

.preview-close-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: scale(1.1);
}

.preview-container {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  overflow: auto;
  padding: 1rem;
  scroll-behavior: smooth;
  -webkit-overflow-scrolling: touch;
}

.preview-image-wrapper {
  position: relative;
  margin: auto; /* Centers the image when smaller than container */
  width: 100%;
  height: 100%;
  max-width: 1400px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 8px;
  overflow: hidden;
}

.preview-container.is-zoomed .preview-image-wrapper {
  width: max-content;
  height: max-content;
  max-width: none;
  border-radius: 0;
  padding: 2rem;
}

.preview-image {
  max-width: 100%;
  max-height: 100%;
  width: auto;
  height: auto;
  object-fit: contain !important;
  transition: transform 0.3s ease;
  cursor: zoom-in;
  display: block;
}

.preview-image.zoomed {
  max-width: none;
  max-height: none;
  width: auto;
  height: auto;
  transform: scale(1); /* Reset any scale if used */
  cursor: zoom-out;
}

.preview-container.is-zoomed {
  overflow: auto;
  display: block;
}

.preview-container.is-zoomed .preview-image-wrapper {
  width: max-content;
  height: max-content;
  min-width: 100%;
  min-height: 100%;
  padding: 2rem;
  display: block;
}

.preview-container.is-zoomed .preview-image {
  /* On zoom, we want to see the image at a much larger size */
  min-width: 150vw; 
}

.preview-nav-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 50%;
  width: 56px;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: white;
  transition: all 0.2s ease;
  backdrop-filter: blur(4px);
  z-index: 2001;
}

.preview-nav-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-50%) scale(1.1);
}

.preview-nav-btn.prev {
  left: 1rem;
}

.preview-nav-btn.next {
  right: 1rem;
}

.preview-footer {
  position: absolute;
  bottom: 2rem;
  left: 0;
  right: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  pointer-events: none;
  z-index: 2001;
}

.preview-counter {
  background: rgba(0, 0, 0, 0.6);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.85rem;
  backdrop-filter: blur(4px);
}

.preview-hint {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.8rem;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}

.cursor-pointer {
  cursor: pointer;
}

.cursor-pointer:hover {
  opacity: 0.9;
}

@media (max-width: 768px) {
  .preview-nav-btn {
    display: none;
  }
  
  .preview-container {
    padding: 0;
  }

  .preview-image-wrapper {
    width: 100%;
    height: 100%;
    border-radius: 0;
  }

  .preview-container.is-zoomed .preview-image-wrapper {
    width: max-content;
    height: max-content;
    padding: 0;
  }

  .preview-container.is-zoomed .preview-image {
    width: 300vw;
    height: auto;
    min-width: auto;
  }

  .preview-close-btn {
    top: 1.5rem;
    right: 1.5rem;
    width: 44px;
    height: 44px;
    background: rgba(0, 0, 0, 0.5);
  }
}

/* Fade transition for preview modal */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.map-screenshot-wrapper {
  margin-bottom: 2rem;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.map-screenshot {
  width: 100%;
  height: auto;
  display: block;
  background: #f5f5f5;
}

@media print {
  .app-header,
  .app-footer,
  .sidebar,
  .back-nav,
  .toast-container,
  .image-gallery-thumbnails,
  .gallery-nav-btn,
  .contact-form-section,
  .map-container,
  .favorites-panel,
  .comparison-panel,
  .email-modal,
  .feedback-button {
    display: none !important;
  }
  
  .map-section {
    page-break-inside: avoid;
  }
  
  .map-screenshot-wrapper {
    margin-bottom: 2rem;
  }

  .listing-detail-page {
    padding: 0;
    background: white;
  }

  .ad-content {
    grid-template-columns: 1fr;
    gap: 2rem;
  }

  .main-content {
    box-shadow: none;
    padding: 0;
  }

  .image-gallery-main {
    height: 400px;
    page-break-inside: avoid;
  }

  .image-gallery-main img {
    object-fit: contain;
  }

  .specs-grid {
    grid-template-columns: 1fr 1fr;
    page-break-inside: avoid;
  }

  .description-section {
    page-break-inside: avoid;
  }

  body {
    background: white;
    color: black;
  }
}

/* Override Leaflet controls z-index to be below mobile menu */
:deep(.leaflet-control) {
  z-index: 400 !important;
}

:deep(.leaflet-control-zoom) {
  z-index: 400 !important;
}

:deep(.leaflet-top),
:deep(.leaflet-bottom) {
  z-index: 400 !important;
}

/* Street View Section */
.street-view-section {
  margin: 2rem 0;
  padding: 0;
}

.street-view-cached-iframe {
  display: none;
  margin-bottom: 1rem;
}

.street-view-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.street-view-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: #1a1a1a;
}

.street-view-toggle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(79, 70, 229, 0.05));
  border: 1px solid rgba(102, 126, 234, 0.2);
  border-radius: 8px;
  color: #667eea;
  font-weight: 500;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.street-view-toggle:hover {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(79, 70, 229, 0.1));
  border-color: rgba(102, 126, 234, 0.3);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.street-view-toggle:active {
  transform: translateY(0);
}

.street-view-container {
  animation: slideDown 0.3s ease;
  margin-top: 1rem;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.street-view-container iframe {
  display: block;
  margin-bottom: 1rem;
}

.street-view-info {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem;
  background: rgba(102, 126, 234, 0.05);
  border-left: 3px solid #667eea;
  border-radius: 6px;
  font-size: 0.9rem;
  color: #666;
  margin: 0;
}

.street-view-info svg {
  flex-shrink: 0;
  margin-top: 2px;
  color: #667eea;
}

.street-view-error {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.05), rgba(220, 38, 38, 0.02));
  border: 1px solid rgba(239, 68, 68, 0.2);
  border-radius: 8px;
  margin-bottom: 1rem;
}

.street-view-error svg {
  flex-shrink: 0;
  color: #ef4444;
  margin-top: 2px;
}

.street-view-error h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1rem;
  font-weight: 600;
  color: #dc2626;
}

.street-view-error p {
  margin: 0 0 0.75rem 0;
  font-size: 0.9rem;
  color: #666;
  line-height: 1.5;
}

.street-view-error p:last-child {
  margin-bottom: 0;
}

.street-view-error-list {
  margin: 0.75rem 0;
  padding-left: 1.5rem;
  font-size: 0.9rem;
  color: #666;
}

.street-view-error-list li {
  margin-bottom: 0.5rem;
  line-height: 1.5;
}

.street-view-error-list li:last-child {
  margin-bottom: 0;
}

.street-view-error-close {
  margin-top: 1rem;
  padding: 0.75rem 1.5rem;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.street-view-error-close:hover {
  background: #b91c1c;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
}

.street-view-error-close:active {
  transform: translateY(0);
}

@media (max-width: 768px) {
  .street-view-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .street-view-toggle {
    width: 100%;
    justify-content: center;
  }

  .street-view-container iframe {
    height: 300px !important;
  }
}

@media print {
  .street-view-section {
    display: none !important;
  }
}
</style>
