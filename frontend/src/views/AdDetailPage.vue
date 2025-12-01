<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { supabase } from '../lib/supabase'
import type { Advertisement } from '../lib/supabase'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const route = useRoute()
const router = useRouter()

const ad = ref<Advertisement | null>(null)
const similarAds = ref<Advertisement[]>([])
const isLoading = ref(true)
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

const mapContainer = ref<HTMLElement | null>(null)
let map: L.Map | null = null

const currentImageIndex = ref(0)
const images = computed(() => {
  if (!ad.value) return []
  
  const allImages: string[] = []
  
  // Add main image
  if (ad.value.image_url) {
    allImages.push(ad.value.image_url)
  }
  
  // Extract additional images from description
  if (ad.value.description) {
    const match = ad.value.description.match(/\[IMAGES\](.*?)\[\/IMAGES\]/s)
    if (match && match[1]) {
      try {
        const additionalImages = JSON.parse(match[1])
        if (Array.isArray(additionalImages)) {
          allImages.push(...additionalImages)
        }
      } catch (e) {
        console.error('Error parsing images:', e)
      }
    }
  }
  
  // Fallback to old images array if exists
  if (allImages.length === 0 && ad.value.images && ad.value.images.length > 0) {
    return ad.value.images
  }
  
  console.log('Images array:', allImages)
  console.log('Description:', ad.value.description)
  return allImages
})

// Clean description without image data
const cleanDescription = computed(() => {
  if (!ad.value?.description) return ''
  return ad.value.description.replace(/\n\n\[IMAGES\].*?\[\/IMAGES\]/s, '')
})

const nextImage = () => {
  if (images.value.length === 0) return
  currentImageIndex.value = (currentImageIndex.value + 1) % images.value.length
}

const prevImage = () => {
  if (images.value.length === 0) return
  currentImageIndex.value = (currentImageIndex.value - 1 + images.value.length) % images.value.length
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
  switch (ad.value?.status) {
    case 'active':
      return 'Wolne'
    case 'reserved':
      return 'Zarezerwowane'
    case 'soon_available':
      return 'Wkrótce dostępne'
    default:
      return 'Nieznany'
  }
})

const statusClass = computed(() => {
  switch (ad.value?.status) {
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

const surfaceArea = computed(() => {
  if (ad.value?.width && ad.value?.height) {
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
  window.dispatchEvent(new Event('storage'))
}

const toggleComparison = () => {
  if (!ad.value) return
  const comparison = JSON.parse(localStorage.getItem('comparison') || '[]')
  const index = comparison.indexOf(ad.value.id)

  if (index > -1) {
    comparison.splice(index, 1)
  } else {
    if (comparison.length >= 5) {
      alert('Możesz porównać maksymalnie 5 ogłoszeń')
      return
    }
    comparison.push(ad.value.id)
  }

  localStorage.setItem('comparison', JSON.stringify(comparison))
  checkComparisonStatus()
  window.dispatchEvent(new Event('storage'))
}

const initMap = () => {
  if (!mapContainer.value || !ad.value || map) return

  map = L.map(mapContainer.value, {
    attributionControl: false
  }).setView([ad.value.latitude, ad.value.longitude], 15)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map)

  L.marker([ad.value.latitude, ad.value.longitude]).addTo(map)
}

const loadAd = async () => {
  try {
    isLoading.value = true
    const adId = route.params.id as string

    const { data, error } = await supabase
      .from('advertisements')
      .select('*')
      .eq('id', adId)
      .maybeSingle()

    if (error) throw error
    if (!data) {
      router.push('/')
      return
    }

    ad.value = data
    checkFavoriteStatus()
    checkComparisonStatus()
    setTimeout(() => initMap(), 100)
    loadSimilarAds()
  } catch (error) {
    console.error('Error loading ad:', error)
    router.push('/')
  } finally {
    isLoading.value = false
  }
}

const loadSimilarAds = async () => {
  if (!ad.value) return

  try {
    const { data, error } = await supabase
      .from('advertisements')
      .select('*')
      .eq('status', 'active')
      .neq('id', ad.value.id)
      .or(`type.eq.${ad.value.type},region.eq.${ad.value.region}`)
      .limit(4)

    if (error) throw error
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

    console.log('Wysyłanie wiadomości do:', ad.value.owner_email)
    console.log('Od:', contactForm.value.email)
    console.log('Treść:', contactForm.value.message)

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

const getMaskedPhone = (phone: string | undefined) => {
  if (!phone) return '+48 XXX XXX XXX'
  const cleaned = phone.replace(/\D/g, '')
  if (cleaned.length < 9) return phone

  const start = cleaned.slice(0, 3)
  const end = cleaned.slice(-3)
  const middle = 'X'.repeat(cleaned.length - 6)

  return `+48 ${start} ${middle} ${end}`
}

const getFullPhone = (phone: string | undefined) => {
  if (!phone) return '+48 XXX XXX XXX'
  const cleaned = phone.replace(/\D/g, '')
  if (cleaned.length < 9) return phone

  return `+48 ${cleaned.slice(0, 3)} ${cleaned.slice(3, 6)} ${cleaned.slice(6)}`
}

import html2pdf from 'html2pdf.js'

const showReportModal = ref(false)
const reportForm = ref({
  reason: '',
  details: ''
})
const isSubmittingReport = ref(false)

// Toast state
const toast = ref<{
  show: boolean
  message: string
  title: string
  type: 'success' | 'error'
}>({
  show: false,
  message: '',
  title: '',
  type: 'success'
})

const showToast = (title: string, message: string, type: 'success' | 'error' = 'success') => {
  toast.value = {
    show: true,
    title,
    message,
    type
  }
  setTimeout(() => {
    toast.value.show = false
  }, 5000)
}

const handlePrint = () => {
  window.print()
}

const isGeneratingPDF = ref(false)

const handleDownloadPDF = async () => {
  if (!ad.value) return
  
  isGeneratingPDF.value = true
  const element = document.querySelector('.main-content') as HTMLElement
  
  if (!element) {
    isGeneratingPDF.value = false
    return
  }

  const opt = {
    margin: [10, 10] as [number, number],
    filename: `ogloszenie-${ad.value.id}.pdf`,
    image: { type: 'jpeg' as const, quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true },
    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' as const }
  }

  try {
    await html2pdf().set(opt).from(element).save()
    showToast('Sukces', 'PDF został pobrany', 'success')
  } catch (error) {
    console.error('Error generating PDF:', error)
    showToast('Błąd', 'Nie udało się wygenerować PDF', 'error')
  } finally {
    isGeneratingPDF.value = false
  }
}

// Share Logic
const showShareModal = ref(false)

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
    showToast('Skopiowano', 'Link został skopiowany do schowka', 'success')
  } catch (err) {
    console.error('Failed to copy:', err)
    showToast('Błąd', 'Nie udało się skopiować linku', 'error')
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
    
    const { error } = await supabase
      .from('reports')
      .insert({
        advertisement_id: ad.value.id,
        reason: reportForm.value.reason,
        details: reportForm.value.details
      })

    if (error) throw error

    closeReportModal()
    showToast('Zgłoszenie wysłane', 'Dziękujemy za zgłoszenie. Przyjrzymy się tej sprawie.', 'success')
  } catch (error) {
    console.error('Error submitting report:', error)
    showToast('Błąd', 'Wystąpił błąd podczas wysyłania zgłoszenia. Spróbuj ponownie później.', 'error')
  } finally {
    isSubmittingReport.value = false
  }
}

onMounted(() => {
  loadAd()
})
</script>

<template>
  <div class="ad-detail-page">
    <div v-if="isLoading" class="loading-container">
      <div class="spinner"></div>
      <p>Ładowanie ogłoszenia...</p>
    </div>

    <div v-else-if="ad" class="page-container">
      <button @click="router.push('/')" class="back-button">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
          <path d="M15 10H5M5 10L10 15M5 10L10 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Powrót do listy
      </button>

      <div class="content-layout">
        <div class="main-content">
          <div class="image-gallery">
            <div class="main-image-wrapper">
              <img
                v-if="images.length > 0"
                :src="images[currentImageIndex]"
                :alt="ad.title"
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
                <img :src="img" :alt="`Miniatura ${index + 1}`" />
              </div>
            </div>
          </div>

          <div class="specs-section">
            <h1 class="ad-title">{{ ad.title }}</h1>
            <div class="price-section">
              <div class="price-main">{{ ad.price }} PLN</div>
              <div class="price-details">
                <span>{{ pricePerSqm }} PLN/m²</span>
                <span>•</span>
                <span>{{ ad.price_unit === 'day' ? 'za dzień' : ad.price_unit === 'week' ? 'za tydzień' : ad.price_unit === 'month' ? 'za miesiąc' : 'za rok' }}</span>
              </div>
            </div>

            <div class="specs-grid">
              <div class="spec-item">
                <div class="spec-label">Typ powierzchni</div>
                <div class="spec-value">{{ ad.type }}</div>
              </div>

              <div class="spec-item">
                <div class="spec-label">Wymiary</div>
                <div class="spec-value">{{ ad.width }}m × {{ ad.height }}m ({{ surfaceArea }} m²)</div>
              </div>

              <div class="spec-item">
                <div class="spec-label">Orientacja</div>
                <div class="spec-value">{{ ad.orientation === 'horizontal' ? 'Poziom' : 'Pion' }}</div>
              </div>

              <div class="spec-item">
                <div class="spec-label">Lokalizacja</div>
                <div class="spec-value">{{ formatLocation(ad.location, ad.city) }}</div>
              </div>

              <div class="spec-item">
                <div class="spec-label">Województwo</div>
                <div class="spec-value">{{ ad.region }}</div>
              </div>

              <div class="spec-item">
                <div class="spec-label">Natężenie ruchu</div>
                <div class="spec-value">
                  {{ ad.traffic_intensity === 'low' ? 'Niskie' : ad.traffic_intensity === 'medium' ? 'Średnie' : 'Wysokie' }}
                </div>
              </div>

              <div class="spec-item" v-if="ad.has_lighting">
                <div class="spec-label">Podświetlenie</div>
                <div class="spec-value spec-yes">Tak</div>
              </div>

              <div class="spec-item" v-if="ad.price_includes_print">
                <div class="spec-label">Druk i montaż</div>
                <div class="spec-value spec-yes">W cenie</div>
              </div>

              <div class="spec-item" v-if="ad.graphic_design_help">
                <div class="spec-label">Pomoc graficzna</div>
                <div class="spec-value spec-yes">Dostępna</div>
              </div>

              <div class="spec-item">
                <div class="spec-label">Rodzaj oferty</div>
                <div class="spec-value">{{ ad.offer_type === 'owner' ? 'Właściciel' : 'Agencja' }}</div>
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

          <div class="description-section">
            <h2>Opis</h2>
            <p class="description-text">{{ cleanDescription }}</p>
          </div>

          <div v-if="ad.contact_preference !== 'phone'" class="contact-form-section">
            <h2>Formularz kontaktowy</h2>

            <div v-if="contactSuccess" class="success-message">
              Wiadomość została wysłana pomyślnie!
            </div>

            <form @submit.prevent="submitContactForm" class="contact-form">
              <div class="form-group">
                <label class="form-label">Twój e-mail</label>
                <input
                  v-model="contactForm.email"
                  type="email"
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

        <div class="sidebar">
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
                <span>{{ ad.views || 0 }} wyświetleń</span>
              </div>
            </div>

            <div v-if="ad.phone && ad.phone.trim()" class="phone-section">
              <button v-if="!showPhone" @click="showPhone = true" class="btn btn-phone">
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

          <div v-if="similarAds.length > 0" class="similar-ads">
            <h3>Podobne oferty</h3>
            <div class="similar-ads-list">
              <router-link
                v-for="similarAd in similarAds"
                :key="similarAd.id"
                :to="`/ogloszenie/${similarAd.id}`"
                class="similar-ad-card"
              >
                <div class="similar-ad-image">
                  <img v-if="similarAd.image_url" :src="similarAd.image_url" :alt="similarAd.title" />
                  <div v-else class="similar-ad-no-image">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
                      <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                      <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                      <path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2"/>
                    </svg>
                  </div>
                </div>
                <div class="similar-ad-content">
                  <h4>{{ similarAd.title }}</h4>
                  <div class="similar-ad-price">{{ similarAd.price }} PLN</div>
                  <div class="similar-ad-location">{{ similarAd.city }}</div>
                </div>
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container">
      <div v-if="toast.show" class="toast" :class="`toast-${toast.type}`">
        <div class="toast-icon">
          <svg v-if="toast.type === 'success'" width="24" height="24" viewBox="0 0 24 24" fill="none">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M22 4L12 14.01l-3-3" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="#EF4444" stroke-width="2"/>
            <path d="M12 8v4M12 16h.01" stroke="#EF4444" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <div class="toast-content">
          <div class="toast-title">{{ toast.title }}</div>
          <div class="toast-message">{{ toast.message }}</div>
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
  </div>
</template>

<style scoped>
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
.ad-detail-page {
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
  padding-bottom: 0.5rem;
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

.ad-title {
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
  background: #10B981;
  color: white;
}

.btn-primary:hover {
  background: #059669;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
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

.similar-ads {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.similar-ads h3 {
  font-size: 1.25rem;
  color: #1f2937;
  margin: 0 0 1.5rem 0;
  font-weight: 700;
}

.similar-ads-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.similar-ad-card {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  text-decoration: none;
  transition: all 0.2s;
}

.similar-ad-card:hover {
  border-color: #10B981;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
  transform: translateY(-2px);
}

.similar-ad-image {
  width: 80px;
  height: 80px;
  flex-shrink: 0;
  border-radius: 8px;
  overflow: hidden;
  background: #f3f4f6;
}

.similar-ad-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.similar-ad-no-image {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
}

.similar-ad-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.similar-ad-content h4 {
  margin: 0;
  font-size: 0.95rem;
  color: #1f2937;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.similar-ad-price {
  font-weight: 700;
  color: #10B981;
  font-size: 1rem;
}

.similar-ad-location {
  font-size: 0.875rem;
  color: #6b7280;
}

@media (max-width: 1024px) {
  .content-layout {
    grid-template-columns: 1fr;
  }

  .sidebar {
    order: -1;
  }
}

@media (max-width: 640px) {
  .page-container {
    padding: 0 1rem;
  }

  .image-container {
    height: 300px;
  }

  .ad-title {
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
  top: 0;
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
}

.modal-content {
  background: white;
  border-radius: 20px;
  width: 100%;
  max-width: 500px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  animation: scaleIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-header {
  background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
  padding: 1.5rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e5e7eb;
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
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.radio-group {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.radio-option {
  display: flex;
  align-items: center;
  gap: 1rem;
  cursor: pointer;
  padding: 1rem;
  border: 2px solid #f3f4f6;
  border-radius: 12px;
  transition: all 0.2s;
  background: #f9fafb;
}

.radio-option:hover {
  border-color: #EF4444;
  background: #fef2f2;
  transform: translateX(4px);
}

.radio-option input[type="radio"] {
  accent-color: #EF4444;
  width: 1.25rem;
  height: 1.25rem;
  margin: 0;
}

.radio-option span {
  font-weight: 500;
  color: #374151;
}

.form-textarea {
  width: 100%;
  min-height: 100px;
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

@media print {
  .app-header,
  .app-footer,
  .sidebar,
  .back-nav,
  .toast-container,
  .image-gallery-thumbnails,
  .gallery-nav-btn,
  .contact-form-section,
  .map-section,
  .favorites-panel,
  .comparison-panel,
  .email-modal,
  .feedback-button {
    display: none !important;
  }

  .ad-detail-page {
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
</style>
