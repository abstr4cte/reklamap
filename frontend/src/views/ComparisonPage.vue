<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../services/api'
import type { Advertisement } from '../types'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import WebPImage from '../components/WebPImage.vue'
import { slugify } from '../utils/slugify'
import { mapTypeToUrlFormat } from '../utils/typeMapping'
import { getFieldsForType, shouldShowField, type ComparisonField } from '../utils/comparisonFields'

import axios from '../api/axios'

const router = useRouter()
const comparisonAds = ref<Advertisement[]>([])
const isLoading = ref(true)
const isGeneratingPdf = ref(false)
const priceUnit = ref<'original' | 'day' | 'week' | 'month' | 'year'>('original')
const confirmDialog = ref<InstanceType<typeof ConfirmDialog> | null>(null)
const headerScrollRef = ref<HTMLElement | null>(null)
const isMobile = ref(window.innerWidth <= 1180)
const isTableZoomed = ref(false)
const tableWrapperRef = ref<HTMLElement | null>(null)

const updateIsMobile = () => {
  isMobile.value = window.innerWidth <= 1180
}

const toggleTableZoom = () => {
  if (window.innerWidth <= 640) {
    isTableZoomed.value = !isTableZoomed.value
    if (isTableZoomed.value) {
      document.body.style.overflow = 'hidden'
    } else {
      document.body.style.overflow = 'auto'
    }
  }
}

const syncScroll = (e: Event) => {
  const target = e.target as HTMLElement
  if (headerScrollRef.value) {
    headerScrollRef.value.scrollLeft = target.scrollLeft
  }
}

const downloadPdf = async () => {
  if (comparisonAds.value.length === 0) return
  
  isGeneratingPdf.value = true
  try {
    const ids = comparisonAds.value.map(ad => ad.id).join(',')
    // Filtruj pola - wyślij tylko te, które są obsługiwane w PDF
    const supportedPdfFields = ['price', 'price_per_sqm', 'type', 'variant', 'dimensions', 'surface_area', 
                                'orientation', 'location', 'traffic_intensity', 'has_backlight', 
                                'price_includes_print', 'graphic_design_help', 'status', 'offer_type', 'has_vat_invoice']
    const fields = comparisonFields.value
      .filter((f: ComparisonField) => supportedPdfFields.includes(f.key))
      .map((f: ComparisonField) => f.key)
      .join(',')
    const response = await axios.get(`/api/listings/pdf/comparison?ids=${ids}&unit=${priceUnit.value}&fields=${fields}`, {
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'porownanie-ogloszen.pdf')
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error('Error downloading PDF:', error)
  } finally {
    isGeneratingPdf.value = false
  }
}

const loadComparison = async () => {
  const comparisonIds = JSON.parse(localStorage.getItem('comparison') || '[]')

  if (comparisonIds.length === 0) {
    isLoading.value = false
    return
  }

  try {
    isLoading.value = true
    const data = await api.getAdvertisementsByIds(comparisonIds)
    comparisonAds.value = data || []
    
    // Ustaw domyślną jednostkę na 'original'
    priceUnit.value = 'original'
  } catch (error) {
    console.error('Error loading comparison:', error)
  } finally {
    isLoading.value = false
  }
}

const removeFromComparison = (id: string) => {
  const comparison = JSON.parse(localStorage.getItem('comparison') || '[]')
  const filtered = comparison.filter((adId: string) => adId !== id)
  localStorage.setItem('comparison', JSON.stringify(filtered))
  comparisonAds.value = comparisonAds.value.filter(ad => ad.id !== id)
  // Trigger custom event to update header counter
  window.dispatchEvent(new CustomEvent('localStorageChange'))
}

const clearAll = () => {
  confirmDialog.value?.open()
}

const handleConfirmClear = () => {
  localStorage.setItem('comparison', JSON.stringify([]))
  comparisonAds.value = []
  // Trigger custom event to update header counter
  window.dispatchEvent(new CustomEvent('localStorageChange'))
}

const getSurfaceArea = (ad: Advertisement) => {
  if (ad.width && ad.height) {
    // Wymiary są zawsze przechowywane w metrach w bazie
    return (ad.width * ad.height).toFixed(2)
  }
  return '0'
}

const getPricePerSqm = (ad: Advertisement) => {
  const area = parseFloat(getSurfaceArea(ad))
  if (area > 0 && ad.price) {
    return (ad.price / area).toFixed(2)
  }
  return '0'
}

const getPrice = (ad: Advertisement) => {
  const basePrice = ad.price
  const originalUnit = ad.price_unit || 'month'
  
  // Jeśli wybrana jednostka to 'original' lub oryginalna jednostka ogłoszenia, zwróć cenę bez przeliczania
  if (priceUnit.value === 'original' || priceUnit.value === originalUnit) {
    return Math.round(basePrice).toLocaleString('pl-PL')
  }
  
  // Przelicz cenę z oryginalnej jednostki na wybraną
  let priceInDay = basePrice
  
  // Najpierw przelicz na dzień (jako bazę)
  switch (originalUnit) {
    case 'day':
      priceInDay = basePrice
      break
    case 'week':
      priceInDay = basePrice / 7
      break
    case 'month':
      priceInDay = basePrice / 30
      break
    case 'year':
      priceInDay = basePrice / 365
      break
    case 'campaign':
      // Dla kampanii używamy campaign_duration (ilość dni)
      const campaignDays = (ad as any).campaign_duration || 30 // domyślnie 30 dni jeśli nie podano
      priceInDay = basePrice / campaignDays
      break
  }
  
  // Następnie przelicz z dnia na wybraną jednostkę
  let convertedPrice = priceInDay
  switch (priceUnit.value) {
    case 'day':
      convertedPrice = priceInDay
      break
    case 'week':
      convertedPrice = priceInDay * 7
      break
    case 'month':
      convertedPrice = priceInDay * 30
      break
    case 'year':
      convertedPrice = priceInDay * 365
      break
  }
  
  return Math.round(convertedPrice).toLocaleString('pl-PL')
}

const priceUnitLabel = computed(() => {
  switch (priceUnit.value) {
    case 'original': return ''
    case 'day': return '/ dzień'
    case 'week': return '/ tydzień'
    case 'month': return '/ miesiąc'
    case 'year': return '/ rok'
  }
})

// Funkcja zwracająca etykietę jednostki dla konkretnego ogłoszenia
const getPriceUnitLabelForAd = (ad: Advertisement): string => {
  const unit = ad.price_unit || 'month'
  switch (unit) {
    case 'day': return '/ dzień'
    case 'week': return '/ tydzień'
    case 'month': return '/ miesiąc'
    case 'year': return '/ rok'
    case 'campaign': return '/ kampania'
    default: return '/ miesiąc'
  }
}

// Funkcja sprawdzająca czy cena została przeliczona
const isPriceConverted = (ad: Advertisement): boolean => {
  const originalUnit = ad.price_unit || 'month'
  // Nie jest przeliczona jeśli wybrano 'original' lub jeśli wybrana jednostka = oryginalna
  if (priceUnit.value === 'original' || priceUnit.value === originalUnit) {
    return false
  }
  return true
}

const getStatusLabel = (status: string) => {
  switch (status) {
    case 'active':
      return 'Wolne'
    case 'reserved':
      return 'Zarezerwowane'
    case 'soon_available':
      return 'Wkrótce dostępne'
    default:
      return 'Nieznany'
  }
}

const getTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
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
  return labels[type] || type
}

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

// Dynamiczne pola do wyświetlenia w porównywarce
const comparisonFields = computed(() => {
  if (comparisonAds.value.length === 0) return []
  
  // Pobierz typ pierwszego ogłoszenia (wszystkie powinny być tego samego typu)
  const adType = comparisonAds.value[0]?.type
  if (!adType) return []
  
  // Pobierz pola dla tego typu
  const fields = getFieldsForType(adType)
  
  // Filtruj pola - pokaż tylko te, które są wymagane lub mają wartość w którymś ogłoszeniu
  return fields.filter(field => shouldShowField(field, comparisonAds.value))
})

// Funkcja do pobierania wartości pola dla ogłoszenia
const getFieldValue = (field: ComparisonField, ad: Advertisement): any => {
  switch (field.key) {
    case 'price':
      const price = getPrice(ad)
      const isConverted = isPriceConverted(ad)
      return isConverted ? price : price
    case 'price_per_sqm':
      return `${getPricePerSqm(ad)} PLN/m² (szacunkowo)`
    case 'type':
      return getTypeLabel(ad.type)
    case 'dimensions':
      if (!ad.width || !ad.height) return '—'
      // Dla LED screens konwertuj z metrów na mm
      if (ad.type === 'led_screen') {
        return `${(ad.width * 1000).toFixed(0)}mm × ${(ad.height * 1000).toFixed(0)}mm`
      }
      return `${ad.width}m × ${ad.height}m`
    case 'surface_area':
      return `${getSurfaceArea(ad)} m²`
    case 'orientation':
      return ad.orientation === 'horizontal' ? 'Poziom' : 'Pion'
    case 'location':
      return formatLocation(ad.location, ad.city)
    case 'traffic_intensity':
      return ad.traffic_intensity === 'low' ? 'Niskie' : ad.traffic_intensity === 'medium' ? 'Średnie' : ad.traffic_intensity === 'high' ? 'Wysokie' : '—'
    case 'traffic_direction':
      return formatTrafficDirectionValue(ad.traffic_direction)
    case 'traffic_type':
      return formatTrafficType(ad.traffic_type)
    case 'location_tier':
      return getLocationTier(ad)
    case 'road_class':
      return formatRoadClass((ad as any).road_class)
    case 'variant':
      return formatVariant(ad.variant, ad.type)
    case 'environment':
      return formatEnvironment((ad as any).environment)
    case 'has_backlight':
      return ad.has_backlight ? 'Tak' : 'Nie'
    case 'price_includes_print':
      return ad.price_includes_print ? 'Tak' : 'Nie'
    case 'price_includes_mounting':
      return (ad as any).price_includes_mounting ? 'Tak' : 'Nie'
    case 'graphic_design_help':
      return ad.graphic_design_help ? 'Tak' : 'Nie'
    case 'status':
      return getStatusLabel(ad.display_status || ad.status)
    case 'offer_type':
      return ad.offer_type === 'owner' ? 'Właściciel' : 'Agencja'
    case 'has_vat_invoice':
      return ad.has_vat_invoice ? 'Tak' : 'Nie'
    case 'transport_scope':
      return formatTransportScope((ad as any).transport_scope)
    case 'vehicle_count':
      return (ad as any).vehicle_count || '—'
    case 'mobile_exposure_mode':
      return formatMobileExposureMode((ad as any).mobile_exposure_mode)
    case 'route_area':
      return (ad as any).route_area || '—'
    case 'operating_hours':
      return (ad as any).operating_hours || '—'
    case 'resolution':
      return (ad as any).resolution || '—'
    case 'pixel_pitch':
      return (ad as any).pixel_pitch ? `${(ad as any).pixel_pitch} mm` : '—'
    case 'brightness':
      return (ad as any).brightness ? `${(ad as any).brightness} nits` : '—'
    default:
      return '—'
  }
}

// Funkcja obliczająca klasę lokalizacji dla billboardu
const getLocationTier = (ad: Advertisement): string => {
  if (ad.type !== 'billboard') return '—'
  
  const trafficIntensity = ad.traffic_intensity
  const roadClass = (ad as any).road_class
  
  // PREMIUM: wysokie natężenie ruchu + autostrada/droga ekspresowa/droga krajowa
  if (trafficIntensity === 'high' && ['highway', 'expressway', 'national'].includes(roadClass || '')) {
    return 'PREMIUM'
  }
  
  // STANDARD: wszystkie inne kombinacje
  return 'STANDARD'
}

// Funkcje formatujące
const formatTrafficDirectionValue = (directions: string[] | undefined) => {
  if (!directions || !Array.isArray(directions) || directions.length === 0) return '—'
  if (directions.includes('entry') && directions.includes('exit')) return 'Oba kierunki'
  const formatted = directions.map(dir => {
    if (dir === 'entry') return 'Wjazd do miasta'
    if (dir === 'exit') return 'Wyjazd z miasta'
    return dir
  })
  return formatted.join(', ')
}

const formatTrafficType = (types: string[] | undefined) => {
  if (!types || !Array.isArray(types) || types.length === 0) return '—'
  const formatted = types.map(type => {
    if (type === 'pedestrian') return 'Pieszy'
    if (type === 'vehicular') return 'Samochodowy'
    return type
  })
  return formatted.join(', ')
}

const formatRoadClass = (roadClass: string | undefined) => {
  if (!roadClass) return '—'
  const labels: Record<string, string> = {
    highway: 'Autostrada',
    expressway: 'Droga ekspresowa',
    national: 'Droga krajowa',
    regional: 'Droga wojewódzka',
    local: 'Droga lokalna',
    urban: 'Droga miejska'
  }
  return labels[roadClass] || roadClass
}

const formatVariant = (variant: string | undefined, type: string) => {
  if (!variant) return '—'
  const labels: Record<string, Record<string, string>> = {
    billboard: {
      standard: 'Standardowy',
      three_sided: 'Trójstronny',
      backlit: 'Backlit (podświetlany)'
    },
    citylight: {
      single: 'Pojedynczy',
      double: 'Podwójny',
      digital: 'Cyfrowy'
    },
    led_screen: {
      standard: 'Standardowy',
      interactive: 'Interaktywny'
    },
    totem: {
      single_sided: 'Jednostronny',
      double_sided: 'Dwustronny',
      multi_sided: 'Wielostronny'
    },
    transport: {
      bus: 'Autobus',
      tram: 'Tramwaj',
      metro: 'Metro',
      stop: 'Przystanek'
    },
    mobile: {
      trailer: 'Przyczepka',
      car: 'Samochód',
      bike: 'Rower',
      other: 'Inna'
    }
  }
  return labels[type]?.[variant] || variant
}

const formatEnvironment = (environment: string | undefined) => {
  if (!environment) return '—'
  const labels: Record<string, string> = {
    indoor: 'Wewnątrz',
    outdoor: 'Na zewnątrz',
    event: 'Event / Wydarzenie'
  }
  return labels[environment] || environment
}

const formatTransportScope = (scope: string | undefined) => {
  if (!scope) return '—'
  const labels: Record<string, string> = {
    internal: 'Wewnętrzna',
    external: 'Zewnętrzna',
    full_vehicle: 'Całopojazdowa'
  }
  return labels[scope] || scope
}

const formatMobileExposureMode = (mode: string | undefined) => {
  if (!mode) return '—'
  const labels: Record<string, string> = {
    moving: 'Jeżdżąca',
    stationary: 'Stojąca',
    mixed: 'Mieszana'
  }
  return labels[mode] || mode
}

// Funkcja sprawdzająca czy wartość jest pozytywna (dla kolorowania)
const isPositiveValue = (field: ComparisonField, value: any): boolean => {
  if (field.key === 'has_backlight' || field.key === 'price_includes_print' || 
      field.key === 'price_includes_mounting' || field.key === 'graphic_design_help' || 
      field.key === 'has_vat_invoice') {
    return value === 'Tak'
  }
  return false
}

onMounted(() => {
  loadComparison()
  window.addEventListener('resize', updateIsMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', updateIsMobile)
})
</script>

<template>
  <div class="comparison-page">
    <div class="page-header">
      <div class="container">
        <div class="header-nav">
          <button @click="router.back()" class="back-button">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M15 10H5M5 10L10 15M5 10L10 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Powrót</span>
          </button>
          <div class="header-actions">
            <button @click="downloadPdf" class="pdf-button" :disabled="isGeneratingPdf || comparisonAds.length === 0">
              <svg v-if="!isGeneratingPdf" width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M12 15L12 3M12 15L8 11M12 15L16 11M2 17L2 21L22 21L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <div v-else class="spinner-small"></div>
              <span>{{ isGeneratingPdf ? 'Generowanie...' : 'Pobierz PDF' }}</span>
            </button>
            <button @click="clearAll" class="clear-button" :disabled="comparisonAds.length === 0">
              Wyczyść
            </button>
          </div>
        </div>
        <h1>Porównanie ogłoszeń</h1>
      </div>
    </div>

    <div class="page-content">
      <div class="container">
        <div v-if="isLoading" class="loading-state">
          <div class="spinner"></div>
          <p>Ładowanie porównania...</p>
        </div>

        <div v-else-if="comparisonAds.length === 0" class="empty-state">
          <svg width="120" height="120" viewBox="0 0 24 24" fill="none">
            <rect x="3" y="3" width="7" height="7" stroke="#d1d5db" stroke-width="2" rx="1"/>
            <rect x="14" y="3" width="7" height="7" stroke="#d1d5db" stroke-width="2" rx="1"/>
            <rect x="3" y="14" width="7" height="7" stroke="#d1d5db" stroke-width="2" rx="1"/>
            <rect x="14" y="14" width="7" height="7" stroke="#d1d5db" stroke-width="2" rx="1"/>
          </svg>
          <h2>Brak ogłoszeń do porównania</h2>
          <p>Dodaj ogłoszenia do porównania, aby zobaczyć różnice między nimi</p>
          <button @click="router.push('/')" class="btn-primary">
            Przejdź do listy ogłoszeń
          </button>
        </div>

        <div v-else class="comparison-container">
          <div class="controls-bar">
            <div class="price-toggle">
              <span class="toggle-label">Jednostka ceny:</span>
              <div class="toggle-buttons">
                <button 
                  @click="priceUnit = 'original'"
                  class="toggle-btn"
                  :class="{ active: priceUnit === 'original' }"
                >
                  Oryginalna
                </button>
                <button 
                  v-for="unit in ['day', 'week', 'month', 'year'] as const" 
                  :key="unit"
                  @click="priceUnit = unit"
                  class="toggle-btn"
                  :class="{ active: priceUnit === unit }"
                >
                  {{ unit === 'day' ? 'Dzień' : unit === 'week' ? 'Tydzień' : unit === 'month' ? 'Miesiąc' : 'Rok' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Sticky Header Table -->
          <div class="comparison-header-sticky">
            <div class="comparison-table-wrapper header-only" ref="headerScrollRef">
              <table class="comparison-table">
                <colgroup>
                  <col class="col-feature">
                  <col v-for="ad in comparisonAds" :key="ad.id" class="col-listing">
                </colgroup>
                <thead>
                  <tr>
                    <th class="feature-column"></th>
                    <th v-for="ad in comparisonAds" :key="ad.id" class="listing-column">
                      <div class="listing-header">
                        <router-link :to="`/powierzchnia-reklamowa/${mapTypeToUrlFormat(ad.type)}/${slugify(ad.city)}/${slugify(ad.title)}-${ad.id}`" class="listing-image-link">
                          <WebPImage
                            v-if="ad.image_url"
                            :src="ad.image_url"
                            :alt="ad.title"
                            class="listing-image"
                          />
                          <div v-else class="no-image">
                            <svg width="60" height="60" viewBox="0 0 24 24" fill="none">
                              <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                              <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                              <path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2"/>
                            </svg>
                          </div>
                        </router-link>
                        <router-link :to="`/powierzchnia-reklamowa/${mapTypeToUrlFormat(ad.type)}/${slugify(ad.city)}/${slugify(ad.title)}-${ad.id}`" class="listing-title">
                          {{ ad.title }}
                        </router-link>
                        <button @click="removeFromComparison(ad.id)" class="remove-btn" title="Usuń z porównania">
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                          </svg>
                        </button>
                      </div>
                    </th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>

          <!-- Scrollable Body Table -->
          <div 
            class="comparison-table-wrapper" 
            @scroll="syncScroll"
            @click="toggleTableZoom"
            :class="{ zoomed: isTableZoomed }"
            ref="tableWrapperRef"
          >
            <table class="comparison-table">
              <colgroup>
                <col class="col-feature">
                <col v-for="ad in comparisonAds" :key="ad.id" class="col-listing">
              </colgroup>
              <tbody>
                <template v-for="field in comparisonFields" :key="field.key">
                  <tr class="mobile-label-row">
                    <td :colspan="comparisonAds.length + (isMobile ? 0 : 1)">
                      <div class="mobile-label-text">{{ field.label }}</div>
                    </td>
                  </tr>
                  <tr class="data-row">
                    <td class="feature-name desktop-only">{{ field.label }}</td>
                    <td 
                      v-for="ad in comparisonAds" 
                      :key="ad.id" 
                      class="feature-value"
                      :class="{ 'highlight': field.key === 'price' || field.key === 'surface_area' }"
                    >
                      <div v-if="field.key === 'price'" class="price-cell">
                        <strong>
                          {{ getFieldValue(field, ad) }} PLN {{ isPriceConverted(ad) ? priceUnitLabel : getPriceUnitLabelForAd(ad) }}
                        </strong>
                        <span v-if="isPriceConverted(ad)" class="estimated-label">(szacunkowo)</span>
                      </div>
                      <strong v-else-if="field.key === 'surface_area'">
                        {{ getFieldValue(field, ad) }}
                      </strong>
                      <span 
                        v-else
                        :class="{ 
                          'value-yes': isPositiveValue(field, getFieldValue(field, ad)),
                          'value-no': !isPositiveValue(field, getFieldValue(field, ad)) && (field.key === 'has_backlight' || field.key === 'price_includes_print' || field.key === 'price_includes_mounting' || field.key === 'graphic_design_help' || field.key === 'has_vat_invoice')
                        }"
                      >
                        {{ getFieldValue(field, ad) }}
                      </span>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <ConfirmDialog
    ref="confirmDialog"
    title="Wyczyść porównanie"
    message="Czy na pewno chcesz wyczyścić wszystkie ogłoszenia z porównania?"
    type="warning"
    confirm-text="Wyczyść"
    cancel-text="Anuluj"
    @confirm="handleConfirmClear"
  />
</template>

<style scoped>
.comparison-page {
  min-height: calc(100vh - 200px);
  background: #f9fafb;
}

.page-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2.5rem 0;
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
  position: relative;
  overflow: hidden;
}

.page-header::before {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
  border-radius: 50%;
  pointer-events: none;
}

.page-header::after {
  content: '';
  position: absolute;
  bottom: -100px;
  left: -100px;
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.05) 0%, transparent 70%);
  border-radius: 50%;
  pointer-events: none;
}

.page-header .container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
  position: relative;
  z-index: 1;
}

.header-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  gap: 1rem;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
}

.page-header h1 {
  margin: 0;
  font-size: 2.25rem;
  font-weight: 800;
  color: white;
  text-align: left;
  letter-spacing: -0.5px;
}

.back-button,
.pdf-button,
.clear-button {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-size: 0.9rem;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.15);
}

.back-button:hover {
  background: rgba(255, 255, 255, 0.25);
  border-color: rgba(255, 255, 255, 0.5);
  transform: translateX(-4px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.clear-button {
  background: rgba(220, 38, 38, 0.2);
  border-color: rgba(220, 38, 38, 0.4);
  color: white;
}

.clear-button:hover {
  background: rgba(220, 38, 38, 0.3);
  border-color: rgba(220, 38, 38, 0.6);
  box-shadow: 0 8px 16px rgba(220, 38, 38, 0.2);
}

.pdf-button {
  background: rgba(91, 33, 182, 0.2);
  border-color: rgba(91, 33, 182, 0.4);
  color: white;
}

.pdf-button:hover:not(:disabled) {
  background: rgba(91, 33, 182, 0.3);
  border-color: rgba(91, 33, 182, 0.6);
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(91, 33, 182, 0.2);
}

.pdf-button:disabled,
.clear-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.spinner-small {
  width: 18px;
  height: 18px;
  border: 2px solid #ddd6fe;
  border-top-color: #5b21b6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.page-content {
  padding: 2rem 0;
}

.container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 0 2rem;
}

.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
  min-height: 400px;
}

.spinner {
  width: 64px;
  height: 64px;
  border: 5px solid #f3f4f6;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1.5rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.empty-state svg {
  margin-bottom: 2rem;
  opacity: 0.5;
}

.empty-state h2 {
  margin: 0 0 1rem 0;
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
}

.empty-state p {
  margin: 0 0 2rem 0;
  font-size: 1.1rem;
  color: #6b7280;
  max-width: 500px;
  line-height: 1.6;
}

.btn-primary {
  padding: 1rem 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.comparison-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  width: 100%;
  overflow: visible; /* Changed to visible for native sticky to work */
}

.comparison-table-wrapper {
  overflow-x: auto;
  overflow-y: visible;
  position: relative;
  -webkit-overflow-scrolling: touch;
}

.comparison-table-wrapper.header-only {
  overflow: hidden; /* Hide horizontal scrollbar for the header */
}

.comparison-header-sticky {
  position: sticky;
  top: 72px;
  z-index: 1000;
  background: white;
  border-bottom: 2px solid #f1f5f9;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
  transition: box-shadow 0.3s ease;
}

.comparison-header-sticky:hover {
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
}

.comparison-table {
  width: max-content;
  min-width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  table-layout: fixed;
  border-radius: 0 0 16px 16px;
}

.comparison-table thead {
  background: #f8fafc;
}

.comparison-table thead th {
  padding: 1.5rem 1rem;
  text-align: left;
  background: #fcfdfe;
  border-bottom: 2px solid #f1f5f9;
  vertical-align: top;
  transition: background-color 0.2s ease;
}

.comparison-table thead th:hover {
  background: #f1f5f9;
}

.feature-column {
  background: #fcfdfe !important;
  position: sticky;
  left: 0;
  z-index: 101 !important;
  border-right: 2px solid #e2e8f0;
}

.col-feature {
  width: 220px;
}

.col-listing {
  width: 280px;
}



.mobile-label-row {
  display: none;
}

/* .listing-column width is handled by .col-listing */

.listing-header {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  position: relative;
}

.listing-image-link {
  width: 100%;
  height: 160px;
  border-radius: 12px;
  overflow: hidden;
  display: block;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.listing-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s;
}

.listing-image-link:hover .listing-image {
  transform: scale(1.05);
}

.no-image {
  width: 100%;
  height: 100%;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
}

.listing-title {
  font-size: 1rem;
  font-weight: 700;
  color: #1e293b;
  text-decoration: none;
  line-height: 1.4;
  height: 2.8rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.listing-title:hover {
  color: #4f46e5;
}

.remove-btn {
  position: absolute;
  top: -0.5rem;
  right: -0.5rem;
  width: 28px;
  height: 28px;
  background: white;
  border: 1px solid #fee2e2;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  color: #ef4444;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.remove-btn:hover {
  background: #ef4444;
  color: white;
  transform: scale(1.1);
}

.comparison-table tbody tr.data-row {
  border-bottom: 1px solid #f1f5f9;
  transition: all 0.2s ease;
}

/* Zebra striping using a very subtle indigo tint */
.comparison-table tbody tr.data-row:nth-of-type(odd) {
  background: rgba(102, 126, 234, 0.02);
}

.comparison-table tbody tr.data-row {
  position: relative;
}

.comparison-table tbody tr.data-row:hover {
  background: rgba(102, 126, 234, 0.08) !important;
}

/* Add the indicator to the sticky column on hover */
.comparison-table tbody tr.data-row:hover .feature-name {
  box-shadow: inset 4px 0 0 0 #667eea;
}

/* Ensure sticky feature name also changes background on row hover */
.comparison-table tbody tr.data-row:hover .feature-name {
  background: #f5f7ff;
  color: #667eea;
}

/* Ensure highlighted cells respond to row hover */
.comparison-table tbody tr.data-row:hover .feature-value.highlight {
  background: rgba(102, 126, 234, 0.12);
}

.feature-name {
  padding: 1.25rem 1.5rem;
  font-weight: 700;
  color: #64748b;
  background: #fcfdfe; /* Very subtle blue-ish white */
  position: sticky;
  left: 0;
  z-index: 10;
  border-right: 2px solid #e2e8f0;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  transition: all 0.2s ease;
}

.feature-value {
  padding: 1.25rem 1.5rem;
  color: #334155;
  font-size: 0.95rem;
  border-bottom: 1px solid #f1f5f9;
  border-right: 1px solid #f1f5f9;
  transition: all 0.2s ease;
  background: transparent; /* Allow row background to show through */
}

.feature-value:last-child {
  border-right: none;
}

.feature-value.highlight {
  background: #f8faff;
  color: #4f46e5;
  font-weight: 700;
}

.feature-value.highlight:hover {
  background: #f0f4ff;
}

.value-yes {
  color: #059669;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: #ecfdf5;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.85rem;
  transition: transform 0.2s ease, background-color 0.2s ease;
}

.value-yes:hover {
  background: #d1fae5;
  transform: translateY(-1px);
}

.value-yes::before {
  content: '✓';
  font-weight: 800;
}

.value-no {
  color: #94a3b8;
  font-size: 0.85rem;
  padding-left: 0.75rem;
  transition: color 0.2s ease;
}

.value-no:hover {
  color: #64748b;
}

.estimated-label {
  display: block;
  font-size: 0.75rem;
  color: #94a3b8;
  font-weight: 400;
  margin-top: 0.25rem;
  font-style: italic;
}

.price-cell {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.controls-bar {
  padding: 1.25rem 2rem;
  background: white;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: flex-end;
  border-radius: 16px 16px 0 0;
}

.price-toggle {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.toggle-label {
  font-weight: 600;
  color: #64748b;
  font-size: 0.85rem;
  letter-spacing: 0.025em;
}

.toggle-buttons {
  display: flex;
  background: #f1f5f9;
  padding: 0.25rem;
  border-radius: 10px;
  gap: 0.25rem;
}

.toggle-btn {
  padding: 0.4rem 0.8rem;
  border: none;
  background: transparent;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}

.toggle-btn:hover {
  color: #1e293b;
}

.toggle-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

@media (max-width: 1180px) {
  .page-header {
    padding: 1.5rem 0;
    z-index: 1;
  }

  .page-header .container {
    padding: 0 1rem;
  }

  .header-nav {
    margin-bottom: 1rem;
  }

  .page-header h1 {
    font-size: 1.5rem;
  }

  .back-button, .pdf-button, .clear-button {
    padding: 0.6rem 1rem;
    font-size: 0.85rem;
  }

  .back-button span, .pdf-button span {
    display: none;
  }

  .back-button::after {
    content: 'Wróć';
  }

  .pdf-button::after {
    content: 'PDF';
  }

  .page-content {
    padding: 1rem 0;
  }

  .container {
    padding: 0 1rem;
  }

  .comparison-container {
    border-radius: 12px;
    width: 100%;
    overflow: visible;
  }

  .controls-bar {
    padding: 1rem;
    justify-content: flex-start;
    overflow: visible;
    border-radius: 12px 12px 0 0;
  }

  .price-toggle {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
    width: 100%;
  }

  .toggle-buttons {
    width: 100%;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    background: transparent;
    padding: 0;
    overflow: visible;
  }

  .toggle-btn {
    flex: 1 1 calc(33.333% - 0.5rem);
    min-width: 90px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.6rem 0.4rem;
    font-size: 0.8rem;
    text-align: center;
    transition: all 0.2s;
  }

  .toggle-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: transparent;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
  }

  .comparison-table-wrapper {
    overflow-x: auto;
    overflow-y: visible;
    -webkit-overflow-scrolling: touch;
  }

  .comparison-table {
    display: table;
    width: max-content;
    min-width: 100%;
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0;
  }

  .col-feature {
    display: none;
  }

  .col-listing {
    width: 280px;
  }


  .comparison-table thead {
    display: table-header-group;
  }

  .comparison-table thead th {
    background: white;
    padding: 0.75rem 0.5rem;
    border-bottom: 2px solid #e2e8f0;
  }

  .feature-column {
    display: none;
  }

  .desktop-only {
    display: none;
  }

  .mobile-label-row {
    display: table-row;
    background: transparent;
  }

  .mobile-label-row td {
    padding: 0;
    border: none;
  }

  .mobile-label-text {
    position: sticky;
    left: 0;
    width: calc(100vw - 2rem);
    padding: 0.75rem 1rem;
    font-weight: 800;
    font-size: 0.75rem;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.075em;
    background: #fcfdfe;
    text-align: center;
    box-sizing: border-box;
    z-index: 50;
    border-bottom: 1px solid #f1f5f9;
    border-top: 1px solid #f1f5f9;
  }

  .data-row {
    display: table-row;
    transition: background-color 0.2s ease;
  }

  .data-row:nth-of-type(odd) {
    background: rgba(102, 126, 234, 0.02);
  }

  .data-row:hover {
    background: rgba(102, 126, 234, 0.1) !important;
  }

  .listing-column, .feature-value {
    width: 280px;
    padding: 0.75rem 0.5rem;
    box-sizing: border-box;
    border-right: 1px solid #f1f5f9;
    vertical-align: middle;
    transition: background-color 0.2s ease;
    background: transparent;
  }

  .feature-value:active {
    background: #f1f5f9;
  }

  .listing-column:last-child, .feature-value:last-child {
    border-right: none;
  }

  .listing-image-link {
    height: 80px;
    margin-bottom: 0.5rem;
  }

  .listing-title {
    font-size: 0.8rem;
    height: 2.2rem;
    margin-top: 0;
    text-align: center;
    color: #1e293b;
  }

  .feature-value {
    padding: 1rem 0.5rem;
    font-size: 0.85rem;
    text-align: center;
    min-height: 60px;
    background: transparent;
  }

  .price-cell {
    align-items: center;
    text-align: center;
  }
}

@media (max-width: 640px) {
  .col-listing {
    width: 140px !important;
  }

  .listing-column, .feature-value {
    width: 140px !important;
    padding: 0.5rem 0.25rem !important;
    font-size: 0.7rem !important;
  }

  .listing-image-link {
    height: 50px !important;
    margin-bottom: 0.25rem !important;
  }

  .listing-image-link img,
  .listing-image-link .no-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .listing-title {
    font-size: 0.65rem !important;
    height: 1.8rem !important;
    margin-top: 0;
  }

  .feature-value {
    padding: 0.5rem 0.25rem !important;
    font-size: 0.65rem !important;
    min-height: 40px !important;
  }

  .mobile-label-text {
    font-size: 0.65rem !important;
    padding: 0.5rem 0.75rem !important;
  }

  .comparison-table-wrapper {
    cursor: default;
  }

  .comparison-table-wrapper:not(.zoomed) {
    cursor: zoom-in;
  }

  .comparison-table-wrapper.zoomed {
    cursor: zoom-out;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    background: white;
    overflow: auto;
    border-radius: 0;
  }

  .comparison-table-wrapper.zoomed .comparison-table {
    width: auto !important;
    min-width: auto !important;
  }

  .comparison-table-wrapper.zoomed .col-listing {
    width: 280px !important;
  }

  .comparison-table-wrapper.zoomed .listing-column,
  .comparison-table-wrapper.zoomed .feature-value {
    width: 280px !important;
    padding: 0.75rem 0.5rem !important;
    font-size: 0.85rem !important;
  }

  .comparison-table-wrapper.zoomed .listing-image-link {
    height: 80px !important;
    margin-bottom: 0.5rem !important;
  }

  .comparison-table-wrapper.zoomed .listing-title {
    font-size: 0.8rem !important;
    height: 2.2rem !important;
  }

  .comparison-table-wrapper.zoomed .feature-value {
    min-height: 60px !important;
  }

  .comparison-table-wrapper.zoomed .mobile-label-text {
    font-size: 0.75rem !important;
    padding: 0.75rem 1rem !important;
  }
}
</style>
