<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { supabase } from '../lib/supabase'
import ToastNotification from '../components/ToastNotification.vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import booleanPointInPolygon from '@turf/boolean-point-in-polygon'
import { point } from '@turf/helpers'
import polandGeoJson from '../assets/poland_highres.json'
import { nsfwService } from '../services/nsfwService'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

const router = useRouter()

const currentStep = ref(1)
const totalSteps = 6

const formData = ref({
  email: '',
  title: '',
  description: '',
  type: '',
  price: null as number | null,
  priceUnit: 'day' as 'day' | 'week' | 'month' | 'year',
  priceNegotiable: false,
  width: null as number | null,
  height: null as number | null,
  orientation: 'horizontal' as 'horizontal' | 'vertical',
  location: '',
  city: '',
  region: '',
  latitude: 52.0,
  longitude: 19.0,
  phone: '',
  countryCode: '+48',
  contactPreference: 'email' as 'email' | 'phone' | 'both',
  hasLighting: false,
  graphicDesignHelp: false,
  priceIncludesPrint: false,
  hasVatInvoice: false,
  status: 'available' as 'available' | 'reserved' | 'soon_available',
  availableFrom: null as Date | null,
  trafficIntensity: 'medium' as 'low' | 'medium' | 'high',
  imageFiles: [] as { file: File, preview: string, id: string }[],
  acceptTerms: false
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
const isSubmitting = ref(false)
const addressSuggestions = ref<any[]>([])
const showAddressSuggestions = ref(false)
const mapContainer = ref<HTMLElement | null>(null)
const showToast = ref(false)
const toastMessage = ref('')
const isResolvingAddress = ref(false)



const toast = ref<InstanceType<typeof ToastNotification> | null>(null)
const isDragging = ref(false)
const draggedImageIndex = ref<number | null>(null)
const dragOverTarget = ref<number | null>(null)
let map: L.Map | null = null
let marker: L.Marker | null = null

const displayToast = (message: string) => {
  toastMessage.value = message
  showToast.value = true
  window.setTimeout(() => {
    showToast.value = false
  }, 3000)
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
    console.error('Error resolving address:', error)
  } finally {
    isResolvingAddress.value = false
  }
}

onMounted(() => {
  if (currentStep.value === 3) {
    initMap()
  }
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})

const surface = computed(() => {
  if (formData.value.width && formData.value.height) {
    return (formData.value.width * formData.value.height).toFixed(2)
  }
  return '0'
})

const pricePerDay = computed(() => {
  if (!formData.value.price) return 0

  switch (formData.value.priceUnit) {
    case 'day':
      return formData.value.price
    case 'week':
      return formData.value.price / 7
    case 'month':
      return formData.value.price / 30
    case 'year':
      return formData.value.price / 365
    default:
      return 0
  }
})

const pricePerSqm = computed(() => {
  const area = parseFloat(surface.value)
  if (area > 0 && formData.value.price) {
    return (formData.value.price / area).toFixed(2)
  }
  return '0'
})

const calculatePrice = (unit: 'day' | 'week' | 'month' | 'year') => {
  const basePrice = pricePerDay.value

  switch (unit) {
    case 'day':
      return basePrice.toFixed(2)
    case 'week':
      return (basePrice * 7).toFixed(2)
    case 'month':
      return (basePrice * 30).toFixed(2)
    case 'year':
      return (basePrice * 365).toFixed(2)
    default:
      return '0'
  }
}

const isInPoland = (lat: number, lng: number): boolean => {
  const pt = point([lng, lat])
  // Iterate over all features (voivodeships) to check if point is in Poland
  // @ts-ignore
  for (const feature of polandGeoJson.features) {
    if (booleanPointInPolygon(pt, feature.geometry as any)) {
      return true
    }
  }
  return false
}

const initMap = () => {
  if (!mapContainer.value || map) return

  // Center on Poland (approximate geographic center)
  map = L.map(mapContainer.value).setView([52.0, 19.0], 6)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map)

  marker = L.marker([formData.value.latitude, formData.value.longitude], {
    draggable: true
  }).addTo(map)

  marker.on('dragend', async () => {
    const position = marker!.getLatLng()
    // First check client-side GeoJSON
    if (!isInPoland(position.lat, position.lng)) {
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

  map.on('click', async (e: L.LeafletMouseEvent) => {
    // First check client-side GeoJSON
    if (!isInPoland(e.latlng.lat, e.latlng.lng)) {
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
      formData.value.city = address.city || address.town || address.village || ''
      formData.value.region = address.state || ''
      // Use full display_name for location field
      formData.value.location = data.display_name || ''
      return true
    }
    return false
  } catch (error) {
    console.error('Error reverse geocoding:', error)
    return false
  }
}

const searchAddress = async (query: string) => {
  if (query.length < 3) {
    addressSuggestions.value = []
    showAddressSuggestions.value = false
    isResolvingAddress.value = false
    return
  }

  isResolvingAddress.value = true
  try {
    const response = await fetch(
      `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=pl&limit=5&addressdetails=1`
    )
    const data = await response.json()
    addressSuggestions.value = data
    showAddressSuggestions.value = data.length > 0
  } catch (error) {
    console.error('Error searching address:', error)
  } finally {
    isResolvingAddress.value = false
  }
}

const selectAddress = (suggestion: any) => {
  const address = suggestion.address
  const lat = parseFloat(suggestion.lat)
  const lng = parseFloat(suggestion.lon)

  // Check if location is in Poland
  if (!isInPoland(lat, lng)) {
    displayToast('Lokalizacja musi być w Polsce')
    showAddressSuggestions.value = false
    return
  }

  formData.value.location = suggestion.display_name
  formData.value.city = address.city || address.town || address.village || ''
  formData.value.region = address.state || ''
  formData.value.latitude = lat
  formData.value.longitude = lng

  if (map && marker) {
    map.setView([formData.value.latitude, formData.value.longitude], 16)
    marker.setLatLng([formData.value.latitude, formData.value.longitude])
  }

  showAddressSuggestions.value = false
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

  for (const file of filesToProcess) {
    if (file.size > 5 * 1024 * 1024) {
      errors.value.image = `Plik ${file.name} jest za duży (max 5MB)`
      continue
    }

    if (!file.type.startsWith('image/')) {
      errors.value.image = `Plik ${file.name} nie jest obrazem`
      continue
    }

    // NSFW Check
    try {
      const nsfwResult = await nsfwService.checkImage(file)
      if (!nsfwResult.isSafe) {
        errors.value.image = `Zdjęcie ${file.name} zawiera niedozwolone treści`
        toast.value?.add(`Zdjęcie ${file.name} zostało odrzucone: wykryto treści niedozwolone`, 'error')
        continue
      }
    } catch (error) {
      console.error('NSFW check error:', error)
    }

    const reader = new FileReader()
    reader.onload = (e) => {
      formData.value.imageFiles.push({ 
        file, 
        preview: e.target?.result as string,
        id: `img-${Date.now()}-${Math.random()}`
      })
    }
    reader.readAsDataURL(file)
    delete errors.value.image
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
      }
      if (!formData.value.description) {
        errors.value.description = 'Opis jest wymagany'
      }
      if (!formData.value.type) {
        errors.value.type = 'Rodzaj powierzchni jest wymagany'
      }
      break

    case 2:
      if (!formData.value.price || formData.value.price <= 0) {
        errors.value.price = 'Cena jest wymagana'
      }
      break

    case 3:
      if (!formData.value.width || formData.value.width <= 0) {
        errors.value.width = 'Szerokość jest wymagana'
      }
      if (!formData.value.height || formData.value.height <= 0) {
        errors.value.height = 'Wysokość jest wymagana'
      }
      if (!formData.value.location) {
        errors.value.location = 'Lokalizacja jest wymagana'
      }
      if (formData.value.contactPreference === 'phone' || formData.value.contactPreference === 'both') {
        if (!formData.value.phone) {
          errors.value.phone = 'Numer telefonu jest wymagany dla wybranej opcji kontaktu'
        }
      }
      break

    case 6:
      if (!formData.value.acceptTerms) {
        errors.value.acceptTerms = 'Musisz zaakceptować regulamin i politykę prywatności'
      }
      break
  }

  return Object.keys(errors.value).length === 0
}

const nextStep = () => {
  if (validateStep(currentStep.value)) {
    if (currentStep.value < totalSteps) {
      currentStep.value++
      window.scrollTo({ top: 0, behavior: 'smooth' })

      if (currentStep.value === 3) {
        setTimeout(() => initMap(), 100)
      }
    }
  }
}

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const uploadImages = async (): Promise<string[]> => {
  if (formData.value.imageFiles.length === 0) return []

  const uploadPromises = formData.value.imageFiles.map(async (item) => {
    const fileExt = item.file.name.split('.').pop()
    const fileName = `${Math.random().toString(36).substring(2)}.${fileExt}`
    const filePath = `advertisements/${fileName}`

    const { error: uploadError } = await supabase.storage
      .from('images')
      .upload(filePath, item.file)

    if (uploadError) {
      console.error('Error uploading image:', uploadError)
      return ''
    }

    const { data } = supabase.storage
      .from('images')
      .getPublicUrl(filePath)

    return data.publicUrl
  })

  const results = await Promise.all(uploadPromises)
  return results.filter(url => url !== '')
}

const handleSubmit = async () => {
  if (!validateStep(currentStep.value)) return

  try {
    isSubmitting.value = true

    const imageUrls = await uploadImages()
    const mainImageUrl = imageUrls.length > 0 ? imageUrls[0] : ''
    
    // Save all image URLs in description with special marker
    let descriptionWithImages = formData.value.description
    if (imageUrls.length > 1) {
      descriptionWithImages += '\n\n[IMAGES]' + JSON.stringify(imageUrls.slice(1)) + '[/IMAGES]'
    }

    const { data, error } = await supabase
      .from('advertisements')
      .insert({
        owner_email: formData.value.email,
        title: formData.value.title,
        description: descriptionWithImages,
        type: formData.value.type,
        price: formData.value.price,
        base_price_per_day: pricePerDay.value,
        price_unit: formData.value.priceUnit,
        price_negotiable: formData.value.priceNegotiable,
        width: formData.value.width,
        height: formData.value.height,
        orientation: formData.value.orientation,
        location: formData.value.location,
        city: formData.value.city,
        region: formData.value.region,
        latitude: formData.value.latitude,
        longitude: formData.value.longitude,
        phone: formData.value.phone ? `${formData.value.countryCode} ${formData.value.phone}` : '',
        contact_preference: formData.value.contactPreference,
        has_lighting: formData.value.hasLighting,
        graphic_design_help: formData.value.graphicDesignHelp,
        price_includes_print: formData.value.priceIncludesPrint,
        has_vat_invoice: formData.value.hasVatInvoice,
        status: formData.value.status === 'available' ? 'active' : formData.value.status,
        available_from: formData.value.availableFrom 
          ? (() => {
              const date = new Date(formData.value.availableFrom)
              date.setHours(0, 0, 0, 0)
              return date.toISOString().split('T')[0]
            })()
          : new Date().toISOString().split('T')[0],
        traffic_intensity: formData.value.trafficIntensity,
        image_url: mainImageUrl,
        dimensions: `${formData.value.width}m x ${formData.value.height}m`,
        has_image: imageUrls.length > 0,
        rental_period: 'long_term'
      })
      .select()

    if (error) throw error

    if (data && data[0]) {
      toast.value?.add('Ogłoszenie zostało dodane pomyślnie!', 'success')
      setTimeout(() => {
        router.push(`/ogloszenie/${data[0].id}`)
      }, 1000)
    } else {
      router.push('/')
    }
  } catch (error) {
    console.error('Error creating advertisement:', error)
    errors.value.submit = 'Wystąpił błąd podczas dodawania ogłoszenia'
    toast.value?.add('Wystąpił błąd podczas dodawania ogłoszenia', 'error')
  } finally {
    isSubmitting.value = false
  }
}


const surfaceTypes = [
  { value: 'billboard', label: 'Billboard' },
  { value: 'citylight', label: 'Citylight' },
  { value: 'led_screen', label: 'Ekran LED' },
  { value: 'banner', label: 'Baner' },
  { value: 'wall', label: 'Ściana' },
  { value: 'other', label: 'Inne' }
]

onMounted(() => {
  if (currentStep.value === 3) {
    initMap()
  }
})
</script>

<template>
  <div class="add-ad-page">
    <!-- Toast Notification -->
    <ToastNotification ref="toast" />
    
    <!-- Legacy Toast (for map errors) -->
    <div v-if="showToast" class="toast-notification">
      {{ toastMessage }}
    </div>

    <div class="page-container">
      <div class="page-header">
        <button @click="router.push('/')" class="back-button">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M15 10H5M5 10L10 15M5 10L10 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Powrót
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
              type="email"
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
              <option v-for="type in surfaceTypes" :key="type.value" :value="type.value">
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
            />
            <span v-if="errors.title" class="error-text">{{ errors.title }}</span>
          </div>

          <div class="form-group">
            <label class="form-label">Opis <span class="required">*</span></label>
            <textarea
              v-model="formData.description"
              rows="5"
              class="form-textarea"
              :class="{ 'error': errors.description }"
              placeholder="Szczegółowy opis powierzchni reklamowej..."
            ></textarea>
            <span v-if="errors.description" class="error-text">{{ errors.description }}</span>
          </div>
        </div>

        <div v-show="currentStep === 2" class="step-section">
          <h2>Cena i typ</h2>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Cena <span class="required">*</span></label>
              <input
                v-model.number="formData.price"
                type="number"
                step="0.01"
                class="form-input"
                :class="{ 'error': errors.price }"
                placeholder="1000"
              />
              <span v-if="errors.price" class="error-text">{{ errors.price }}</span>
            </div>

            <div class="form-group">
              <label class="form-label">Jednostka <span class="required">*</span></label>
              <select v-model="formData.priceUnit" class="form-select">
                <option value="day">za dzień</option>
                <option value="week">za tydzień</option>
                <option value="month">za miesiąc</option>
                <option value="year">za rok</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="checkbox-option">
              <input type="checkbox" v-model="formData.priceNegotiable" />
              <span>Cena do negocjacji</span>
            </label>
          </div>

          <div class="price-info-box">
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
          </div>
        </div>

        <div v-show="currentStep === 3" class="step-section">
          <h2>Wymiary i lokalizacja</h2>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Szerokość (m) <span class="required">*</span></label>
              <input
                v-model.number="formData.width"
                type="number"
                step="0.01"
                class="form-input"
                :class="{ 'error': errors.width }"
                placeholder="5.0"
              />
              <span v-if="errors.width" class="error-text">{{ errors.width }}</span>
            </div>

            <div class="form-group">
              <label class="form-label">Wysokość (m) <span class="required">*</span></label>
              <input
                v-model.number="formData.height"
                type="number"
                step="0.01"
                class="form-input"
                :class="{ 'error': errors.height }"
                placeholder="3.0"
              />
              <span v-if="errors.height" class="error-text">{{ errors.height }}</span>
            </div>
          </div>

          <div class="surface-display">
            <strong>Powierzchnia:</strong> {{ surface }} m²
            <span v-if="formData.price" class="surface-price">
              ({{ pricePerSqm }} PLN/m²)
            </span>
          </div>

          <div class="form-group">
            <label class="form-label">Orientacja</label>
            <div class="radio-group">
              <label class="radio-option">
                <input type="radio" v-model="formData.orientation" value="horizontal" />
                <span>Poziom</span>
              </label>
              <label class="radio-option">
                <input type="radio" v-model="formData.orientation" value="vertical" />
                <span>Pion</span>
              </label>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Lokalizacja <span class="required">*</span></label>
            <div class="address-input-wrapper">
              <input
                v-model="formData.location"
                type="text"
                class="form-input"
                :class="{ 'error': errors.location }"
                placeholder="Wpisz adres (np. ul. Marszałkowska 1, Warszawa)"
                @input="searchAddress(formData.location)"
                @blur="handleBlur"
                style="padding-right: 2.5rem;"
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
                  <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
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
            <span v-if="errors.location" class="error-text">{{ errors.location }}</span>
          </div>



          <div class="map-container" ref="mapContainer"></div>
          <p class="map-hint">Kliknij na mapie lub przeciągnij marker, aby ustawić lokalizację</p>

          <div class="form-group">
            <label class="form-label">Opcje kontaktu</label>
            <select v-model="formData.contactPreference" class="form-select">
              <option value="email">Tylko formularz kontaktowy</option>
              <option value="phone">Tylko telefon</option>
              <option value="both">Formularz i telefon</option>
            </select>
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
              />
            </div>
            <span v-if="errors.phone" class="error-text">{{ errors.phone }}</span>
          </div>
        </div>

        <div v-show="currentStep === 4" class="step-section">
          <h2>Wyposażenie i opcje dodatkowe</h2>

          <div class="checkbox-group">
            <label class="checkbox-option">
              <input type="checkbox" v-model="formData.hasLighting" />
              <span>Podświetlenie</span>
            </label>

            <label class="checkbox-option">
              <input type="checkbox" v-model="formData.graphicDesignHelp" />
              <span>Pomoc przy projekcie graficznym</span>
            </label>

            <label class="checkbox-option">
              <input type="checkbox" v-model="formData.priceIncludesPrint" />
              <span>Cena zawiera druk i montaż</span>
            </label>

            <label class="checkbox-option">
              <input type="checkbox" v-model="formData.hasVatInvoice" />
              <span>Faktura VAT</span>
            </label>
          </div>

          <div class="form-group">
            <label class="form-label">Status dostępności</label>
            <select v-model="formData.status" class="form-select">
              <option value="available">Wolne</option>
              <option value="reserved">Zarezerwowane</option>
              <option value="soon_available">Wkrótce dostępne</option>
            </select>
          </div>

          <div v-if="formData.status === 'soon_available'" class="form-group">
            <label class="form-label">Data dostępności</label>
            <VueDatePicker
              v-model="formData.availableFrom"
              :enable-time-picker="false"
              auto-apply
              :min-date="minDate"
              :clearable="false"
              class="w-full"
            >
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
          </div>

          <div class="form-group">
            <label class="form-label">Natężenie ruchu</label>
            <select v-model="formData.trafficIntensity" class="form-select">
              <option value="low">Niskie</option>
              <option value="medium">Średnie</option>
              <option value="high">Wysokie</option>
            </select>
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
                  'dragging': draggedImageIndex === index
                }"
                draggable="true"
                @dragstart="handleImageDragStart($event, index)"
                @dragover.prevent="handleImageDragOver($event, index)"
                @dragend="handleDragEnd"
                @drop.prevent="handleImageDrop($event, index)"
              >
                <img :src="img.preview" alt="Podgląd" />
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
  </div>
</template>

<style scoped>
.add-ad-page {
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

.phone-input-field {
  flex: 1;
  padding: 0.75rem;
  border: none;
  font-size: 0.95rem;
  color: #374151;
  background: white;
}

.phone-input-field:focus {
  outline: none;
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

.radio-option input[type="radio"],
.checkbox-option input[type="checkbox"] {
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
  .add-ad-page {
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
</style>
