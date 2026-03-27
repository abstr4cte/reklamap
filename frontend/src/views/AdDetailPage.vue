<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getFullImageUrl } from '../services/api'
import axios from '../api/axios'
import type { Advertisement } from '../types'
import ToastNotification from '../components/ToastNotification.vue'
import { formatPrice } from '../utils/formatPrice'
import Breadcrumbs from '../components/Breadcrumbs.vue'
import { useSeo } from '../composables/useSeo'
import { usePreferencesStore } from '../stores/usePreferencesStore'
import { useSearchStore } from '../stores/useSearchStore'
import { useStreetViewStore } from '../stores/useStreetViewStore'

// Detailed Components
import AdGallery from '../components/detail/AdGallery.vue'
import AdDetailsGrid from '../components/detail/AdDetailsGrid.vue'
import AdContactForm from '../components/detail/AdContactForm.vue'
import AdSidebar from '../components/detail/AdSidebar.vue'
import AdSimilarListings from '../components/detail/AdSimilarListings.vue'
import StreetViewSection from '../components/detail/StreetViewSection.vue'

let L: any = null

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

const route = useRoute()
const router = useRouter()
const searchStore = useSearchStore()
const streetViewStore = useStreetViewStore()
const prefStore = usePreferencesStore()

const ad = ref<Advertisement | null>(null)
const similarAds = ref<Advertisement[]>([])
const isLoading = ref(true)
const notFound = ref(false)
const showPhone = ref(false)

const handlePhoneCall = () => {
  if (!ad.value?.phone) return
  
  if (showPhone.value) {
    // If already showing, trigger the call
    const cleanPhone = ad.value.phone.replace(/[^0-9+]/g, '')
    window.location.href = `tel:${cleanPhone}`
  } else {
    // First click just shows the number
    showPhone.value = true
  }
}
const showActionsMenu = ref(false)
const dailyStatsViews = ref(0)
const toast = ref<InstanceType<typeof ToastNotification> | null>(null)

// Map Variables
const mapContainer = ref<HTMLElement | null>(null)
let map: any = null

// Street View State
const showStreetView = ref(false)
const streetViewError = ref(false)
const streetViewLoading = ref(false)
const streetViewCached = ref(false)
const streetViewUrl = ref('')

// Image logic
const images = computed(() => {
  if (!ad.value) return []
  if (ad.value.images && Array.isArray(ad.value.images) && ad.value.images.length > 0) return ad.value.images
  const allImages: string[] = []
  if (ad.value.image_url) allImages.push(ad.value.image_url)
  return allImages
})

const imageAlt = computed(() => {
  if (!ad.value) return ''
  return `${searchStore.getTypeLabel(ad.value.type)} ${ad.value.city} - ${ad.value.title}`
})

const thumbnailAlt = (index: number) => {
  if (!ad.value) return `Miniatura ${index + 1}`
  return `${ad.value.title} - zdjęcie ${index + 1}`
}

// Clean description
const cleanDescription = computed(() => {
  if (!ad.value?.description) return ''
  return ad.value.description.replace(/\n\n\[IMAGES\].*?\[\/IMAGES\]/s, '')
})

// Status logic
const statusLabel = computed(() => {
  if (!ad.value) return ''
  const status = ad.value.display_status || ad.value.status
  const labels: Record<string, string> = { active: 'Wolne', reserved: 'Zarezerwowane', unavailable: 'Niedostępne', soon_available: 'Wkrótce wolne' }
  if (status === 'soon_available' && ad.value.available_from && new Date(ad.value.available_from) <= new Date()) return 'Wolne'
  return labels[status] || 'Status nieznany'
})

const statusClass = computed(() => {
  if (!ad.value) return ''
  const status = ad.value.display_status || ad.value.status
  if (status === 'soon_available' && ad.value.available_from && new Date(ad.value.available_from) <= new Date()) return 'status-active'
  return `status-${status}`
})

// SEO Logic
const seoOptions = ref<any>({ title: 'ReklaMap', description: 'Trwa ładowanie...' })
const { updateMetaTags: _updateMetaTags } = useSeo(seoOptions)

watch(ad, (newAd) => {
  if (newAd) {
    const imageUrl = newAd.image_url ? getFullImageUrl(newAd.image_url) : undefined
    const title = `${newAd.title} – ${searchStore.getTypeLabel(newAd.type)}, ${newAd.city} | ReklaMap`
    const description = `${newAd.title} – ${searchStore.getTypeLabel(newAd.type)} w ${newAd.city}. Wymiary: ${newAd.width}×${newAd.height}m. Cena: ${formatPrice(newAd.price)} PLN. ${(newAd.description || '').substring(0, 100)}...`
    const keywords = `${newAd.title}, ${searchStore.getTypeLabel(newAd.type)} ${newAd.city}, reklama zewnętrzna ${newAd.city}`
    
    // Structured Data
    const structuredData = [
      {
        '@context': 'https://schema.org',
        '@type': 'Product',
        'name': newAd.title,
        'description': description,
        'image': imageUrl,
        'brand': { '@type': 'Brand', 'name': 'ReklaMap' },
        'offers': {
          '@type': 'Offer',
          'price': newAd.price,
          'priceCurrency': 'PLN',
          'availability': newAd.status === 'active' ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
          'url': typeof window !== 'undefined' ? window.location.href : ''
        }
      },
      {
        '@context': 'https://schema.org',
        '@type': 'Place',
        'name': newAd.location,
        'geo': {
          '@type': 'GeoCoordinates',
          'latitude': newAd.latitude,
          'longitude': newAd.longitude
        },
        'address': {
          '@type': 'PostalAddress',
          'addressLocality': newAd.city,
          'addressCountry': 'PL'
        }
      }
    ]
    
    seoOptions.value = { 
      title, 
      description, 
      keywords,
      ogImage: imageUrl, 
      ogType: 'product',
      structuredData
    }
  }
})

// Actions
const isFavorite = computed(() => ad.value ? prefStore.isFavorite(ad.value.id) : false)
const isInComparison = computed(() => ad.value ? prefStore.isCompared(ad.value.id) : false)

const toggleFavorite = async () => {
  if (ad.value) {
    await prefStore.toggleFavorite(ad.value.id)
    toast.value?.add(isFavorite.value ? 'Dodano do ulubionych' : 'Usunięto z ulubionych', 'success')
  }
}

const toggleComparison = async () => {
  if (ad.value) {
    const result = await prefStore.toggleComparison(ad.value.id)
    if (result.success) {
      toast.value?.add(isInComparison.value ? 'Dodano do porównania' : 'Usunięto z porównania', 'success')
    } else {
      toast.value?.add(result.error || 'Błąd', 'error')
    }
  }
}

// Map Logic
const initMap = async () => {
  if (!mapContainer.value || !ad.value) return
  L = await loadLeaflet()
  const lat = Number(ad.value.latitude)
  const lng = Number(ad.value.longitude)
  const position: [number, number] = [lat, lng]
  
  map = L.map(mapContainer.value, { scrollWheelZoom: false, zoomControl: false }).setView(position, 13)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map)
  L.marker(position).addTo(map)
  L.control.zoom({ position: 'bottomright' }).addTo(map)
}

// Street View
const loadGoogleMaps = (): Promise<void> => {
  return new Promise((resolve) => {
    if (window.google?.maps) return resolve()
    
    const existingScript = document.getElementById('google-maps-script')
    if (existingScript) {
      existingScript.addEventListener('load', () => resolve())
      return
    }

    const script = document.createElement('script')
    script.id = 'google-maps-script'
    script.src = `https://maps.googleapis.com/maps/api/js?key=${import.meta.env.VITE_GOOGLE_MAPS_API_KEY}&libraries=places,geometry`
    script.async = true
    script.defer = true
    script.onload = () => resolve()
    document.head.appendChild(script)
  })
}

const checkStreetViewAvailability = (): Promise<boolean> => {
  return new Promise(async (resolve) => {
    if (!ad.value) return resolve(false)
    const cached = streetViewStore.getCachedAvailability(ad.value.id)
    if (cached !== undefined) return resolve(cached)

    try {
      await loadGoogleMaps()
      const streetViewService = new window.google.maps.StreetViewService()
      const location = new window.google.maps.LatLng(ad.value.latitude, ad.value.longitude)
      streetViewService.getPanorama({ location, radius: 50 }, (_data: any, status: any) => {
        const available = status === window.google.maps.StreetViewStatus.OK
        streetViewStore.setAvailability(ad.value!.id, available)
        resolve(available)
      })
    } catch (err) {
      console.error('Failed to load Google Maps for Street View:', err)
      resolve(false)
    }
  })
}

const toggleStreetView = async () => {
  if (showStreetView.value) {
    showStreetView.value = false
  } else {
    showStreetView.value = true
    if (!streetViewCached.value) {
      streetViewLoading.value = true
      const available = await checkStreetViewAvailability()
      if (available) {
        streetViewUrl.value = `https://www.google.com/maps/embed/v1/streetview?key=${import.meta.env.VITE_GOOGLE_MAPS_API_KEY}&location=${ad.value?.latitude},${ad.value?.longitude}&heading=0&pitch=0&fov=80`
      } else {
        streetViewError.value = true
      }
      streetViewLoading.value = false
      streetViewCached.value = true
    }
  }
}

// Report Modal
const showReportModal = ref(false)

// Prevent background scroll when any modal is open
watch([showReportModal, showActionsMenu], ([reportVal, actionsVal]) => {
  if (reportVal || actionsVal) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

onUnmounted(() => {
  document.body.style.overflow = ''
})
const reportForm = ref({ reason: '', details: '' })
const isSubmittingReport = ref(false)
const reportReasons = [
  { value: 'incorrect_info', label: 'Niepoprawne informacje' },
  { value: 'unavailable', label: 'Powierzchnia już niedostępna' },
  { value: 'spam', label: 'Spam / Oszustwo' },
  { value: 'other', label: 'Inne' }
]

const submitReport = async () => {
  if (!ad.value || !reportForm.value.reason) return
  isSubmittingReport.value = true
  try {
    await axios.post('/api/reports', {
      ...reportForm.value,
      advertisement_id: ad.value.id
    })
    toast.value?.add('Zgłoszenie zostało wysłane', 'success')
    showReportModal.value = false
    reportForm.value = { reason: '', details: '' }
  } catch (e) {
    toast.value?.add('Błąd przy wysyłaniu zgłoszenia', 'error')
  } finally {
    isSubmittingReport.value = false
  }
}

// PDF/Print
const isGeneratingPDF = ref(false)
const handleDownloadPDF = async () => {
  if (!ad.value) return
  isGeneratingPDF.value = true
  try {
    const response = await axios.get(`/api/listings/${ad.value.id}/pdf`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `ogloszenie-${ad.value.id}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (e) {
    toast.value?.add('Błąd generowania PDF', 'error')
  } finally {
    isGeneratingPDF.value = false
  }
}

// Load advertisement data
const loadAd = async () => {
  const id = route.params.id as string
  isLoading.value = true
  notFound.value = false
  
  // Reset state
  showPhone.value = false
  showStreetView.value = false
  streetViewError.value = false
  streetViewLoading.value = false
  
  try {
    const lastPart = id.split('-').pop()
    const adId = lastPart || id
    const response = await axios.get(`/api/listings/${adId}`)
    ad.value = response.data
    
    // Increment view count after 2s
    setTimeout(() => {
      axios.post(`/api/listings/${adId}/increment-views`)
    }, 2000)

    // Fetch daily stats (optional, don't break page if fails)
    try {
      const statsResponse = await axios.get(`/api/listings/${adId}/daily-stats`)
      dailyStatsViews.value = statsResponse.data.summary?.total_views || 0
    } catch (err) {
      console.warn('Failed to fetch daily stats:', err)
    }

    // Fetch similar (optional, don't break page if fails)
    try {
      const similarResponse = await axios.get(`/api/listings/${adId}/similar`)
      similarAds.value = similarResponse.data
    } catch (err) {
      console.warn('Failed to fetch similar ads:', err)
    }

    isLoading.value = false
    await nextTick()
    
    // Scroll to top when loading new ad
    window.scrollTo({ top: 0, behavior: 'smooth' })
    
    // Check if container exists after DOM update
    if (mapContainer.value) {
      // Remove old map if exists
      if (map) {
        map.remove()
        map = null
      }
      initMap()
    }
  } catch (e: any) {
    if (e.response?.status === 404) notFound.value = true
    isLoading.value = false
  }
}

// Watch for route changes (when clicking similar ads)
watch(() => route.params.id, (newId, oldId) => {
  if (newId && newId !== oldId) {
    loadAd()
  }
})

// Lifecycle
onMounted(() => {
  loadAd()
})

onUnmounted(() => {
  if (map) map.remove()
})

defineExpose({
  toast
})
</script>

<template>
  <div class="listing-detail-page">
    <div v-if="isLoading" class="page-container skeleton-detail">
      <div class="skeleton-breadcrumbs skeleton"></div>
      <div class="content-layout">
        <div class="main-content">
          <div class="skeleton-gallery skeleton"></div>
          <div class="skeleton-tabs">
            <div class="skeleton-tab skeleton"></div>
            <div class="skeleton-tab skeleton"></div>
          </div>
          <div class="skeleton-title-box skeleton"></div>
          <div class="skeleton-desc skeleton"></div>
          <div class="skeleton-desc skeleton"></div>
          <div class="skeleton-desc skeleton last"></div>
        </div>
        <div class="sidebar">
          <div class="skeleton-sidebar-card skeleton"></div>
          <div class="skeleton-sidebar-card skeleton"></div>
        </div>
      </div>
    </div>

    <div v-else-if="notFound" class="not-found-container">
      <div class="not-found-content">
        <div class="error-code">404</div>
        <h1>Ogłoszenie nie zostało znalezione</h1>
        <div class="actions">
          <button @click="router.push('/')" class="btn btn-primary">Wróć na główną</button>
        </div>
      </div>
    </div>

    <div v-else-if="ad" class="page-container">
      <Breadcrumbs :items="[{ label: 'Główna', path: '/' }, { label: ad.city, path: '/lista' }, { label: ad.title }]" />

      <div class="content-layout">
        <div class="main-content">
          <!-- Gallery -->
          <AdGallery 
            :images="images" 
            :imageAlt="imageAlt" 
            :thumbnailAlt="thumbnailAlt" 
          />

          <!-- NO INLINE SIDEBAR ON MOBILE - Replaced by Sticky Bottom Bar -->

          <!-- Description -->
          <div class="description-section">
          <!-- Mobile Status Info -->
          <div class="mobile-status-badge mobile-only">
            <div class="status-badge" :class="statusClass">
              {{ statusLabel }}
            </div>
          </div>

          <h1 class="page-title">{{ ad.title }}</h1>
            <p class="location-text">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M12 21s-8-7.14-8-12.728A8 8 0 1 1 20 8.272C20 13.86 12 21 12 21z" stroke="currentColor" stroke-width="2"/>
                <circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="2"/>
              </svg>
              {{ ad.location }}, {{ ad.city }}
            </p>
            <div class="price-box">
              <span class="price-value">{{ formatPrice(ad.price) }} PLN</span>
              <span class="price-unit">/ {{ searchStore.getPriceUnitLabel(ad.price_unit) }}</span>
            </div>


            <!-- Mobile Stats Row -->
            <div class="mobile-stats-row mobile-only">
              <div class="stat-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M12 6v6l4 2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Dodano: {{ ad.created_at ? new Date(ad.created_at).toLocaleDateString('pl-PL') : '' }}</span>
              </div>
              <div class="divider"></div>
              <div class="stat-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>{{ dailyStatsViews }} wyświetleń</span>
              </div>
            </div>
            
            <div class="description-body">
              <h2>Opis ogłoszenia</h2>
              <p>{{ cleanDescription }}</p>
            </div>
          </div>

          <!-- Grid Parameters -->
          <div class="description-body" style="margin-top: 2rem;">
            <h2>Parametry techniczne</h2>
            <AdDetailsGrid :ad="ad" />
          </div>

          <!-- Map & Street View -->
          <div class="map-section">
            <h2>Lokalizacja na mapie</h2>
            <div ref="mapContainer" class="map-container"></div>
            
            <StreetViewSection 
              :showStreetView="showStreetView"
              :streetViewUrl="streetViewUrl"
              :streetViewLoading="streetViewLoading"
              :streetViewError="streetViewError"
              @toggle-street-view="toggleStreetView"
              @handle-street-view-error="streetViewError = true"
            />
          </div>

          <!-- Contact Form -->
          <AdContactForm 
            v-if="ad.contact_preference !== 'phone'" 
            :adId="ad.id"
            @success="toast?.add($event, 'success')"
            @error="toast?.add($event, 'error')"
          />

          <!-- Similar Listings -->
          <AdSimilarListings 
            :similarAds="similarAds" 
            :getTypeLabel="searchStore.getTypeLabel" 
          />
        </div>

        <!-- Desktop Sidebar -->
        <div class="sidebar desktop-only">
          <AdSidebar 
            :ad="ad"
            :displayViews="dailyStatsViews"
            :statusLabel="statusLabel"
            :statusClass="statusClass"
            :isFavorite="isFavorite"
            :isInComparison="isInComparison"
            :isGeneratingPDF="isGeneratingPDF"
            :showPhone="showPhone"
            :showActionsMenu="showActionsMenu"
            @toggle-favorite="toggleFavorite"
            @toggle-comparison="toggleComparison"
            @handle-download-pdf="handleDownloadPDF"
            @handle-show-phone="showPhone = true"
            @handle-share="showActionsMenu = true"
            @open-report-modal="showReportModal = true"
          />
        </div>
      </div>
    </div>



    <!-- Actions Modal (for Mobile "Options") -->
    <transition name="fade">
      <div v-if="showActionsMenu" class="modal-overlay" @click.self="showActionsMenu = false">
        <div class="modal-content actions-modal-content">
          <div class="modal-header">
            <h3>Opcje ogłoszenia</h3>
            <button @click="showActionsMenu = false" class="btn-close">&times;</button>
          </div>
          <div class="modal-body">
            <div class="actions-grid">
              <button @click="toggleFavorite(); showActionsMenu = false" class="action-item" :class="{ active: isFavorite }">
                <svg width="24" height="24" viewBox="0 0 24 24" :fill="isFavorite ? '#EF4444' : 'none'">
                  <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" :stroke="isFavorite ? '#EF4444' : 'currentColor'" stroke-width="2"/>
                </svg>
                <span>Ulubione</span>
              </button>
              
              <button @click="toggleComparison(); showActionsMenu = false" class="action-item" :class="{ active: isInComparison }">
                <svg width="24" height="24" viewBox="0 0 24 24" :fill="isInComparison ? '#667eea' : 'none'">
                  <rect x="3" y="3" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
                  <rect x="14" y="3" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
                  <rect x="3" y="14" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
                  <rect x="14" y="14" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
                </svg>
                <span>Porównaj</span>
              </button>

              <button @click="handleDownloadPDF(); showActionsMenu = false" class="action-item">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                  <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>PDF</span>
              </button>

              <button @click="showReportModal = true; showActionsMenu = false" class="action-item report">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                  <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Zgłoś</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Report Modal -->
    <transition name="fade">
      <div v-if="showReportModal" class="modal-overlay" @click.self="showReportModal = false">
        <div class="modal-content report-modal">
          <div class="modal-header">
            <h3>Zgłoś naruszenie</h3>
            <button @click="showReportModal = false" class="btn-close">&times;</button>
          </div>
          
          <div class="report-intro">
            Jeśli uważasz, że to ogłoszenie narusza nasze zasady, wybierz powód zgłoszenia poniżej.
          </div>

          <form @submit.prevent="submitReport" class="report-form">
            <div class="report-reasons-grid">
              <label v-for="reason in reportReasons" :key="reason.value" class="radio-option">
                <input type="radio" v-model="reportForm.reason" :value="reason.value" />
                <span class="radio-custom"></span>
                <span class="reason-label text-bold">{{ reason.label }}</span>
              </label>
            </div>

            <div class="details-section">
              <span class="section-label">Dodatkowe szczegóły (opcjonalnie)</span>
              <textarea 
                v-model="reportForm.details" 
                placeholder="Podaj więcej informacji, które pomogą nam zweryfikować to zgłoszenie..."
              ></textarea>
            </div>

            <div class="modal-actions">
              <button type="button" @click="showReportModal = false" class="btn btn-secondary-outline">Anuluj</button>
              <button type="submit" class="btn btn-danger" :disabled="isSubmittingReport || !reportForm.reason">
                {{ isSubmittingReport ? 'Wysyłanie...' : 'Zgłoś ogłoszenie' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>

    <!-- Mobile Sticky Actions -->
    <div v-if="ad" class="mobile-sticky-actions">
      <div class="sticky-info">
        <div class="sticky-price">{{ formatPrice(ad.price) }} PLN</div>
        <div class="sticky-unit">/ {{ searchStore.getPriceUnitLabel(ad.price_unit) }}</div>
      </div>
      <div class="sticky-btns">
        <button 
          v-if="ad.phone" 
          @click="handlePhoneCall" 
          class="btn btn-primary btn-small btn-phone"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l2.18-1.14a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
          </svg>
          <span class="btn-text">{{ showPhone ? ad.phone : 'Zadzwoń' }}</span>
        </button>

        <button @click="showActionsMenu = true" class="btn btn-secondary btn-small">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
          </svg>
          <span class="btn-text">Opcje</span>
        </button>
      </div>
    </div>

    <ToastNotification ref="toast" />
  </div>
</template>

<style scoped>
.listing-detail-page {
  background: #f9fafb;
  min-height: 100vh;
  padding: 2rem 0;
}

.page-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

.content-layout {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 2.5rem;
  margin-top: 2rem;
}

.main-content {
  display: flex;
  flex-direction: column;
  gap: 3rem;
}

.description-section {
  background: var(--card-bg, white);
  padding: 2.5rem;
  border-radius: 20px;
  box-shadow: var(--card-shadow, 0 4px 6px -1px rgba(0, 0, 0, 0.1));
}

.status-badge {
  display: block;
  width: 100%;
  text-align: center;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  font-weight: 700;
  font-size: 1rem;
  margin-bottom: 1.5rem;
}

.status-active { background: #ecfdf5; color: #059669; }
.status-reserved { background: #fffbeb; color: #d97706; }
.status-unavailable { background: #fef2f2; color: #dc2626; }
.status-soon { background: #eff6ff; color: #2563eb; }

.page-title {
  font-size: 2.5rem;
  font-weight: 800;
  color: var(--text-main, #111827);
  margin-bottom: 0.5rem;
}

.location-text {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-muted, #6b7280);
  margin-bottom: 2rem;
}

.price-box {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  margin-bottom: 2.5rem;
}

.price-value {
  font-size: 2rem;
  font-weight: 800;
  color: #667eea;
}

.price-unit {
  color: var(--text-muted, #6b7280);
  font-weight: 600;
}

.description-body h2 {
  font-size: 1.5rem;
  margin-bottom: 1rem;
}

.description-body p {
  color: var(--text-main, #4b5563);
  line-height: 1.7;
  font-size: 1.1rem;
}

.map-section {
  background: var(--card-bg, white);
  padding: 2.5rem;
  border-radius: 20px;
  box-shadow: var(--card-shadow, 0 4px 6px -1px rgba(0, 0, 0, 0.1));
}

.map-container {
  height: 400px;
  border-radius: 12px;
  margin: 1.5rem 0;
  overflow: hidden;
  z-index: 1;
}

.map-container :deep(.leaflet-control-attribution) {
  display: none !important;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 60vh;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 5px solid #e5e7eb;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin { to { transform: rotate(360deg); } }

.mobile-only { display: none; }

.mobile-status-badge {
  margin-bottom: 1rem;
}

.mobile-stats-row {
  display: none; /* Hidden on desktop */
  align-items: center;
  gap: 1.25rem;
  margin-top: 1rem;
  padding: 1rem 0;
  border-top: 1px solid #f1f5f9;
  border-bottom: 1px solid #f1f5f9;
  margin-bottom: 1.5rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
  font-size: 0.95rem;
  font-weight: 500;
}

.mobile-stats-row .divider {
  width: 1px;
  height: 16px;
  background: #e2e8f0;
}

.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 1.5rem;
}

.modal-content {
  background: var(--card-bg, white);
  color: var(--text-main, #111827);
  padding: 0;
  border-radius: 24px;
  width: 100%;
  max-width: 550px;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-header {
  padding: 1.5rem 2.5rem;
  border-bottom: 1px solid #f1f5f9;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.modal-body, .report-form {
  padding: 0 2.5rem 2.5rem 2.5rem;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  flex-grow: 1;
}

.report-intro {
  color: var(--text-muted, #64748b);
  font-size: 0.95rem;
  margin-top: 1.5rem;
  margin-bottom: 2rem;
  line-height: 1.5;
  padding-left: 2.5rem;
  padding-right: 2.5rem;
}


@keyframes modalIn {
  from { opacity: 0; transform: scale(0.95) translateY(10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.report-modal h3 {
  font-size: 1.5rem;
  font-weight: 800;
  margin: 0;
  letter-spacing: -0.025em;
}

.report-intro {
  color: var(--text-muted, #64748b);
  font-size: 0.95rem;
  margin-top: 0.5rem;
  margin-bottom: 2rem;
  line-height: 1.5;
}

.report-reasons-grid {
  display: grid;
  gap: 0.75rem;
  margin-bottom: 2rem;
}

.radio-option {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.25rem;
  background: #f8fafc;
  border: 2px solid transparent;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.radio-option:hover {
  background: #f1f5f9;
}

.radio-option input[type="radio"] {
  width: 1.25rem;
  height: 1.25rem;
  accent-color: #6366f1;
}

.radio-option:has(input:checked) {
  background: #eff6ff;
  border-color: #3b82f6;
}

.section-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 700;
  color: #475569;
  margin-bottom: 0.75rem;
}

.report-form textarea {
  width: 100%;
  padding: 1rem 1.25rem;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  border-radius: 12px;
  min-height: 120px;
  font-size: 0.95rem;
  transition: all 0.2s ease;
  resize: vertical;
}

.report-form textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
}

.btn-danger {
  background: #ef4444 !important;
  color: white !important;
  padding: 0.75rem 1.75rem !important;
  border-radius: 12px !important;
  font-weight: 700 !important;
  border: none !important;
  transition: all 0.2s ease !important;
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626 !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
}

.btn-secondary-outline {
  padding: 0.75rem 1.75rem !important;
  border-radius: 12px !important;
  font-weight: 700 !important;
  background: transparent !important;
  border: 2px solid #e2e8f0 !important;
  color: #64748b !important;
  transition: all 0.2s ease !important;
}

.btn-secondary-outline:hover {
  background: #f8fafc !important;
  border-color: #cbd5e1 !important;
}

@media (max-width: 1024px) {
  .content-layout { 
    display: flex;
    flex-direction: column;
    gap: 1.5rem; 
    overflow: hidden;
  }
  .desktop-only { display: none !important; }
  .page-title { font-size: 1.8rem; }
  .listing-detail-page { padding-bottom: 100px; }
  .page-container { padding: 0 1rem; }
}

.mobile-sticky-actions {
  display: none;
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--card-bg, white);
  padding: 1rem 1.5rem;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
  z-index: 1500;
  align-items: center;
  justify-content: space-between;
  border-top: 1px solid var(--border-color, #e5e7eb);
}

@media (max-width: 1024px) {
  .mobile-only { display: revert; }
  .mobile-sticky-actions { display: flex; }
  .price-box { display: none; }
  .mobile-status-badge {
    margin-bottom: 1rem;
  }
  .mobile-stats-row {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    margin-top: 1rem;
    padding: 1rem 0;
    border-top: 1px solid var(--border-color, #e5e7eb);
    border-bottom: 1px solid var(--border-color, #e5e7eb);
    margin-bottom: 1.5rem;
  }
  .stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-muted, #64748b);
    font-size: 0.95rem;
    font-weight: 500;
  }
  .mobile-stats-row .divider {
    width: 1px;
    height: 16px;
    background: var(--border-color, #e2e8f0);
  }
}

.sticky-info {
  display: flex;
  flex-direction: column;
}

.sticky-price {
  font-size: 1.25rem;
  font-weight: 800;
  color: #667eea;
}

.sticky-unit {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.sticky-btns {
  display: flex;
  gap: 0.75rem;
}

.btn-small {
  padding: 0.75rem 1.25rem;
  font-size: 0.95rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 700;
  border: none;
  transition: all 0.2s ease;
}

.btn-phone {
  background: var(--accent-color, #10B981);
  color: white;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
}

.btn-phone:active {
  transform: scale(0.95);
}

.btn-secondary {
  background: var(--primary-gradient);
  color: white;
  border: none !important;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
}

.btn-secondary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(102, 126, 234, 0.35);
}

.btn-secondary:active {
  transform: scale(0.95);
}

.actions-modal-content {
  max-width: 400px;
}

.actions-modal-content .modal-header {
  padding: 1.5rem 1.5rem 1rem 1.5rem;
  border-bottom: none;
}

.actions-modal-content .modal-body {
  padding: 0 1.5rem 1.5rem 1.5rem;
}

.actions-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.action-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 1.25rem 0.5rem;
  background: var(--card-bg, white);
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 16px;
  color: var(--text-main);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);

  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

.action-item:active {
  transform: scale(0.95);
  background: var(--bg-secondary);
}

.action-item svg {
  color: var(--primary-color, #667eea);
}

.action-item.active {
  border-color: var(--primary-color);
  background: rgba(102, 126, 234, 0.05);
}

.action-item.report svg {
  color: var(--error-color, #ef4444);
}

.action-item span {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--text-main);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: var(--text-muted);
  cursor: pointer;
}

/* Skeleton Specifics */
.skeleton-detail {
  padding-top: 2rem;
}

.skeleton-breadcrumbs {
  height: 2.5rem;
  width: 300px;
  margin-bottom: 1.5rem;
}

.skeleton-gallery {
  aspect-ratio: 16/9;
  width: 100%;
  border-radius: 20px;
  margin-bottom: 2rem;
}

.skeleton-tabs {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.skeleton-tab {
  height: 2.5rem;
  width: 120px;
  border-radius: 30px;
}

.skeleton-title-box {
  height: 3rem;
  width: 60%;
  margin-bottom: 2rem;
}

.skeleton-desc {
  height: 1.25rem;
  width: 100%;
  margin-bottom: 1rem;
}

.skeleton-desc.last {
  width: 70%;
}

.skeleton-sidebar-card {
  height: 300px;
  width: 100%;
  border-radius: 20px;
  margin-bottom: 1.5rem;
}

@media (max-width: 768px) {
  .skeleton-gallery { aspect-ratio: 4/3; }
}

/* Optimization for very narrow screens */
@media (max-width: 395px) {
  .mobile-sticky-actions {
    padding: 1rem 1rem !important;
  }
  
  .sticky-info {
    margin-right: 0.5rem;
    flex-shrink: 0;
  }
  
  .sticky-price {
    font-size: 1.1rem !important;
    white-space: nowrap;
    line-height: 1.1;
  }
  
  .sticky-unit {
    display: block !important;
    font-size: 0.65rem !important;
    opacity: 0.8;
    margin-top: 2px;
  }
  
  .sticky-btns {
    gap: 0.5rem !important;
    flex: 1;
    justify-content: flex-end;
  }
  
  .btn-small {
    padding: 0.65rem 0.61rem !important;
    font-size: 0.8rem !important;
    gap: 0.2rem !important;
    border-radius: 12px !important;
    flex: 0 1 auto;
    white-space: nowrap;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  @media (max-width:355px) {
    .btn-small .btn-text {
      display: none !important;
    }
    .btn-small {
      padding: 0.65rem 0.65rem !important;
      min-width: 42px;
      max-width: 42px;
      border-radius: 50% !important;
    }
  }

  .btn-small svg {
    width: 16px !important;
    height: 16px !important;
    flex-shrink: 0;
  }

  .mobile-stats-row {
    gap: 0.5rem !important;
  }
}
</style>

<style>
@media (max-width: 1024px) {
  .feedback-container {
    bottom: calc(1rem + 85px) !important;
    transition: bottom 0.3s ease;
  }
  
  .scroll-to-top {
    bottom: calc(5rem + 85px) !important;
    transition: bottom 0.3s ease;
  }
}
</style>
