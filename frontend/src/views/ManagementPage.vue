<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { api } from '../services/api'
import axios from '../api/axios'
import type { Advertisement } from '../types'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import ToastNotification from '../components/ToastNotification.vue'
import WebPImage from '../components/WebPImage.vue'
import EngagementChart from '../components/EngagementChart.vue'
import { nsfwService } from '../services/nsfwService'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import icon from 'leaflet/dist/images/marker-icon.png'
import iconShadow from 'leaflet/dist/images/marker-shadow.png'
import booleanPointInPolygon from '@turf/boolean-point-in-polygon'
import { point } from '@turf/helpers'
import polandGeoJson from '../assets/poland_highres.json'
import { slugify } from '../utils/slugify'
import { mapTypeToUrlFormat } from '../utils/typeMapping'

// Fix Leaflet icon paths
const DefaultIcon = L.icon({
  iconUrl: icon,
  shadowUrl: iconShadow,
  iconSize: [25, 41],
  iconAnchor: [12, 41]
})
L.Marker.prototype.options.icon = DefaultIcon

const router = useRouter()
const route = useRoute()
const listings = ref<Advertisement[]>([])
const isLoading = ref(true)
const tokenEmail = ref('')
const tokenExpiresAt = ref('')
const hasToken = ref(false)

// Zmienne dla formularza email
const email = ref('')
const isSubmitting = ref(false)
const isSuccess = ref(false)
const errorMessage = ref('')
const expandedRows = ref<Set<string>>(new Set())
const editingAd = ref<Advertisement | null>(null)
const confirmDialog = ref<InstanceType<typeof ConfirmDialog> | null>(null)
const toast = ref<InstanceType<typeof ToastNotification> | null>(null)
const adToDelete = ref<string>('')
const pendingTopAdsToAdd = ref<string[]>([])
const confirmDialogTitle = ref('Potwierdzenie')
const confirmDialogMessage = ref('')
const confirmDialogType = ref<'info' | 'warning' | 'danger'>('info')
const pendingStatusChanges = ref<Record<string, string>>({})
const showDateModal = ref(false)
const pendingAdId = ref<string>('')
const availableFromDate = ref<Date | null>(null)
const unifiedImages = ref<{ type: 'existing' | 'new', url?: string, file?: File, preview?: string, id: string, loading?: boolean }[]>([])
const isDragging = ref(false)
const draggedImageIndex = ref<number | null>(null)
const addressSuggestions = ref<any[]>([])
const showAddressSuggestions = ref(false)
const isResolvingAddress = ref(false)
let searchTimeout: ReturnType<typeof setTimeout> | null = null
const showMapModal = ref(false)
const modalMapContainer = ref<HTMLElement | null>(null)
let modalMap: L.Map | null = null
let modalMarker: L.Marker | null = null
const modalSearchQuery = ref('')
const modalSearchSuggestions = ref<any[]>([])
const showModalSearchSuggestions = ref(false)
let modalSearchTimeout: ReturnType<typeof setTimeout> | null = null

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

const dragOverTarget = ref<{ index: number, type: 'existing' | 'new' } | null>(null)
const isSaving = ref(false)

const isTokenInvalid = ref(false)
const activeTab = ref<'listings' | 'statistics'>('listings')
const engagementChartRef = ref<any>(null)

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
        `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=pl&limit=10&addressdetails=1`
      )
      const data = await response.json()
      addressSuggestions.value = data
      showAddressSuggestions.value = data.length > 0
    } catch (error) {
      console.error('Error searching address:', error)
    } finally {
      isResolvingAddress.value = false
    }
  }, 500)
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
    }
  } catch (error) {
    console.error('Error resolving address:', error)
  } finally {
    isResolvingAddress.value = false
  }
}

const selectAddress = (suggestion: any) => {
  if (!editingAd.value) return
  
  // Handle both LocationResult and raw Nominatim response
  const isLocationResult = suggestion.name !== undefined && suggestion.displayName !== undefined
  
  if (isLocationResult) {
    // LocationResult from locationService
    editingAd.value.location = suggestion.displayName
    editingAd.value.city = suggestion.name
    editingAd.value.region = suggestion.state || ''
    editingAd.value.latitude = suggestion.lat
    editingAd.value.longitude = suggestion.lng
  } else {
    // Raw Nominatim response
    const address = suggestion.address
    editingAd.value.location = suggestion.display_name
    let city = address.city || address.town || address.village || address.municipality || address.county || address.administrative || ''
    // Usuń prefix "gmina" jeśli pochodzi z municipality
    if (!address.city && !address.town && !address.village && address.municipality) {
      city = city.replace(/^gmina\s+/i, '')
    }
    editingAd.value.city = city
    editingAd.value.region = address.state || ''
    
    // Update coordinates from suggestion
    const lat = parseFloat(suggestion.lat)
    const lng = parseFloat(suggestion.lon)
    if (!isNaN(lat) && !isNaN(lng)) {
      editingAd.value.latitude = lat
      editingAd.value.longitude = lng
    }
  }
  
  showAddressSuggestions.value = false
}

const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as HTMLElement
  if (!target.closest('.address-input-wrapper')) {
    showAddressSuggestions.value = false
  }
}

const handleBlur = async () => {
  setTimeout(() => {
    showAddressSuggestions.value = false
  }, 200)
  
  // If location is filled but city/region missing, try to resolve
  if (editingAd.value && editingAd.value.location && (!editingAd.value.city || !editingAd.value.region)) {
    await resolveAddressFromInput(editingAd.value.location)
  }
}

const clearLocation = () => {
  if (!editingAd.value) return
  editingAd.value.location = ''
  editingAd.value.city = ''
  editingAd.value.region = ''
  editingAd.value.latitude = 52.0
  editingAd.value.longitude = 19.0
  
  // Reset map view to show all of Poland if modal is open
  if (modalMap && modalMarker) {
    modalMap.setView([52.0, 19.0], 6)
    modalMarker.setLatLng([52.0, 19.0])
  }
}

const loadAdvertisements = async () => {
  try {
    isLoading.value = true
    isTokenInvalid.value = false
    
    // Sprawdź, czy mamy token w parametrach ścieżki (priorytet) lub URL query
    const token = (route.params.token as string) || (route.query.token as string)
    
    if (token) {
      // Jeśli mamy token, pobierz ogłoszenia dla tego tokena
      try {
        const response = await axios.get(`/api/management/validate/${token}`)
        if (response.data.valid) {
          listings.value = response.data.listings || []
          tokenEmail.value = response.data.email
          tokenExpiresAt.value = new Date(response.data.expires_at).toLocaleString()
          hasToken.value = true
          isTokenInvalid.value = false
        } else {
          // Token jest nieprawidłowy
          hasToken.value = false
          isTokenInvalid.value = true
        }
      } catch (error) {
        console.error('Error validating token:', error)
        // W przypadku błędu walidacji
        hasToken.value = false
        isTokenInvalid.value = true
      }
    } else {
      // Brak tokena, pokaż formularz email
      hasToken.value = false
      isTokenInvalid.value = false
      // Nie pobieramy ogłoszeń, gdy nie ma tokena
    }
  } catch (error) {
    console.error('Error loading listings:', error)
  } finally {
    isLoading.value = false
  }
}

// ... (skipping unchanged parts)

const uploadImage = async (file: File): Promise<string> => {
  try {
    return await api.storage.upload(file)
  } catch (error) {
    console.error('Error uploading image:', error)
    throw error
  }
}

// ...

const updateStatus = async (id: string, newStatus: string, availableFrom?: Date | null) => {
  try {
    const availableFromISO = availableFrom ? new Date(availableFrom).toISOString().split('T')[0] : null;
    
    const updatedAd = await api.updateAdvertisementStatus(id, newStatus, availableFromISO);

    const adIndex = listings.value.findIndex(a => a.id === id);
    if (adIndex !== -1) {
      listings.value[adIndex] = { ...listings.value[adIndex], ...updatedAd };
    }
    toast.value?.add('Status został zaktualizowany', 'success');
  } catch (error: any) {
    console.error('Error updating status:', error);
    if (error.response && error.response.data && error.response.data.errors) {
      const serverErrors = error.response.data.errors;
      let toastMessage = 'Błąd walidacji:';
      for (const key in serverErrors) {
        toastMessage += `\n- ${serverErrors[key][0]}`;
      }
      toast.value?.add(toastMessage, 'error', 6000);
    } else {
      toast.value?.add(`Błąd podczas aktualizacji statusu: ${error.message || 'Nieznany błąd'}`, 'error');
    }
  }
}

const updateActiveStatus = async (id: string, isActive: boolean) => {
  try {
    const ad = listings.value.find(a => a.id === id)
    if (!ad) return

    // Use a dedicated endpoint that only updates is_active
    await axios.patch(`/api/advertisements/${id}/active`, { is_active: isActive })

    ad.is_active = isActive
    toast.value?.add(isActive ? 'Ogłoszenie zostało aktywowane' : 'Ogłoszenie zostało dezaktywowane', 'success')
  } catch (error) {
    console.error('Error updating active status:', error)
    toast.value?.add('Błąd podczas zmiany stanu aktywności', 'error')
  }
}

const toggleActive = async (id: string) => {
  try {
    const ad = listings.value.find(a => a.id === id)
    if (!ad) return

    const newActiveState = !ad.is_active
    await updateActiveStatus(id, newActiveState)
  } catch (error) {
    console.error('Error toggling active state:', error)
    toast.value?.add('Błąd podczas zmiany stanu aktywności', 'error')
  }
}

const saveChanges = async (id: string) => {
  if (!editingAd.value || isSaving.value) return

  // Validate variant field for types that require it
  const typesWithVariant = ['billboard', 'citylight', 'led_screen', 'banner', 'wall', 'totem', 'transport', 'mobile']
  if (typesWithVariant.includes(editingAd.value.type) && !(editingAd.value as any).variant) {
    alert('Wariant jest wymagany dla tego typu powierzchni reklamowej')
    return
  }

  try {
    isSaving.value = true
    // Process all images in order
    const finalImageUrls: string[] = []
    
    for (const img of unifiedImages.value) {
      if (img.type === 'existing' && img.url) {
        finalImageUrls.push(img.url)
      } else if (img.type === 'new' && img.file) {
        const url = await uploadImage(img.file)
        finalImageUrls.push(url)
      }
    }
    
    // Fallback for main image_url (use first image or empty)
    const mainImageUrl = finalImageUrls.length > 0 ? finalImageUrls[0] : ''

    await api.updateAdvertisement(id, {
        title: editingAd.value.title,
        description: editingAd.value.description,
        price: editingAd.value.price,
        price_unit: editingAd.value.price_unit,
        price_negotiable: editingAd.value.price_negotiable,
        location: editingAd.value.location,
        city: editingAd.value.city,
        region: editingAd.value.region,
        latitude: editingAd.value.latitude,
        longitude: editingAd.value.longitude,
        type: editingAd.value.type,
        width: editingAd.value.width,
        height: editingAd.value.height,
        orientation: editingAd.value.width >= editingAd.value.height ? 'horizontal' : 'vertical',
        traffic_intensity: editingAd.value.traffic_intensity,
        has_backlight: editingAd.value.has_backlight,
        price_includes_print: editingAd.value.price_includes_print,
        price_includes_mounting: (editingAd.value as any).price_includes_mounting || false,
        graphic_design_help: editingAd.value.graphic_design_help,
        offer_type: editingAd.value.offer_type,
        has_vat_invoice: editingAd.value.has_vat_invoice,
        status: editingAd.value.status,
        owner_email: editingAd.value.owner_email,
        images: finalImageUrls,
        image_url: mainImageUrl,
        has_image: finalImageUrls.length > 0,
        phone: (editingAd.value as any).phone ? `+48${(editingAd.value as any).phone}` : '',
        contact_preference: (editingAd.value as any).contact_preference || 'email',
        // Type-specific fields
        variant: (editingAd.value as any).variant || null,
        road_class: (editingAd.value as any).road_class || null,
        traffic_direction: (editingAd.value as any).traffic_direction && (editingAd.value as any).traffic_direction.length > 0 
          ? (editingAd.value as any).traffic_direction 
          : null,
        traffic_type: (editingAd.value as any).traffic_type && (editingAd.value as any).traffic_type.length > 0 
          ? (editingAd.value as any).traffic_type 
          : null,
        environment: (editingAd.value as any).environment || null,
        spot_duration: (editingAd.value as any).spot_duration || null,
        loop_duration: (editingAd.value as any).loop_duration || null,
        transport_scope: (editingAd.value as any).transport_scope || null,
        vehicle_count: (editingAd.value as any).vehicle_count || null,
        mobile_exposure_mode: (editingAd.value as any).mobile_exposure_mode || null,
        operating_hours: (editingAd.value as any).operating_hours || null,
        route_area: (editingAd.value as any).route_area || null,
    })

    const ad = listings.value.find(a => a.id === id)
    if (ad && editingAd.value) {
      Object.assign(ad, editingAd.value)
      ad.images = finalImageUrls
      ad.image_url = mainImageUrl
      ad.has_image = finalImageUrls.length > 0
      ad.phone = (editingAd.value as any).phone ? `+48${(editingAd.value as any).phone}` : ''
      ad.contact_preference = (editingAd.value as any).contact_preference || 'email'
    }

    toggleRow(id)
    nextTick(() => {
      const row = document.getElementById(`listing-row-${id}`)
      if (row) {
        row.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
      }
    })
    toast.value?.add('Zmiany zostały zapisane', 'success')
  } catch (error) {
    console.error('Error saving changes:', error)
    toast.value?.add('Błąd podczas zapisywania zmian', 'error')
  } finally {
    isSaving.value = false
  }
}

const deleteAd = (id: string) => {
  adToDelete.value = id
  confirmDialog.value?.open()
}

const handleConfirmDelete = async () => {
  if (!adToDelete.value) return

  try {
    await api.deleteAdvertisement(adToDelete.value)

    listings.value = listings.value.filter(a => a.id !== adToDelete.value)
    expandedRows.value.delete(adToDelete.value)
    adToDelete.value = ''
    toast.value?.add('Ogłoszenie zostało usunięte', 'success')
  } catch (error) {
    console.error('Error deleting advertisement:', error)
    toast.value?.add('Błąd podczas usuwania ogłoszenia', 'error')
  }
}

// Handler dla dynamicznego dialogu potwierdzenia
// Handler dla dynamicznego dialogu potwierdzenia
const handleConfirmDialog = () => {
  if (pendingTopAdsToAdd.value.length > 0) {
    executeAddTopAdsToChart(pendingTopAdsToAdd.value, pendingTopAdsMetric.value)
    pendingTopAdsToAdd.value = []
    pendingTopAdsMetric.value = undefined
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

const openPreview = (id: string) => {
  const ad = listings.value.find(a => a.id === id)
  if (!ad) return
  
  const city = slugify(ad.city)
  const title = slugify(ad.title)
  const type = mapTypeToUrlFormat(ad.type)
  const path = `/powierzchnia-reklamowa/${type}/${city}/${title}-${id}`
  
  const { href } = router.resolve({ path })
  window.open(href, '_blank')
}

const toggleRow = (id: string) => {
  if (expandedRows.value.has(id)) {
    expandedRows.value.delete(id)
  } else {
    expandedRows.value.add(id)
    const ad = listings.value.find(a => a.id === id)
    if (ad) {
      editingAd.value = { ...ad }
      // Strip +48 prefix from phone for editing
      if ((editingAd.value as any).phone) {
        (editingAd.value as any).phone = (editingAd.value as any).phone.replace(/^\+48\s*/g, '')
      }
      // Convert traffic_direction from string to array for checkboxes (backward compatibility)
      if ((editingAd.value as any).traffic_direction) {
        const direction = (editingAd.value as any).traffic_direction
        // If already an array, keep it as is
        if (Array.isArray(direction)) {
          (editingAd.value as any).traffic_direction = direction
        }
        // Convert old string format to array
        else if (direction === 'both') {
          (editingAd.value as any).traffic_direction = ['entry', 'exit']
        } else if (direction === 'entry' || direction === 'exit') {
          (editingAd.value as any).traffic_direction = [direction]
        } else {
          (editingAd.value as any).traffic_direction = []
        }
      } else {
        (editingAd.value as any).traffic_direction = []
      }
      // Initialize traffic_type for banners
      if ((editingAd.value as any).traffic_type) {
        const trafficType = (editingAd.value as any).traffic_type
        if (Array.isArray(trafficType)) {
          (editingAd.value as any).traffic_type = trafficType
        } else {
          (editingAd.value as any).traffic_type = []
        }
      } else {
        (editingAd.value as any).traffic_type = []
      }
      // Initialize price_includes_mounting if not present
      if ((editingAd.value as any).price_includes_mounting === undefined) {
        (editingAd.value as any).price_includes_mounting = false
      }
      // Initialize unifiedImages for the edited ad
      unifiedImages.value = (editingAd.value.images || []).map(url => ({
        type: 'existing',
        url,
        id: Math.random().toString(36).substr(2, 9)
      }))
    }
  }
}

const handleStatusChange = (id: string, newStatus: string) => {
  pendingStatusChanges.value[id] = newStatus
}

const confirmStatusChange = async (id: string) => {
  const status = pendingStatusChanges.value[id]
  if (status) {
    if (status === 'soon_available') {
      pendingAdId.value = id
      showDateModal.value = true
      return
    }
    await updateStatus(id, status)
    delete pendingStatusChanges.value[id]
  }
}

const cancelStatusChange = (id: string) => {
  delete pendingStatusChanges.value[id]
}

const cancelDateModal = () => {
  showDateModal.value = false
  pendingAdId.value = ''
  availableFromDate.value = null
}

const confirmDateAndStatus = async () => {
  if (pendingAdId.value && availableFromDate.value) {
    await updateStatus(pendingAdId.value, 'soon_available', availableFromDate.value)
    showDateModal.value = false
    delete pendingStatusChanges.value[pendingAdId.value]
    pendingAdId.value = ''
    availableFromDate.value = null
  }
}

const getTotalImagesCount = () => unifiedImages.value.length

const processFiles = async (files: FileList | null) => {
  if (!files) return

  for (let i = 0; i < files.length; i++) {
    if (unifiedImages.value.length >= 5) break
    const file = files[i]
    if (!file.type.startsWith('image/')) continue

    const tempId = Math.random().toString(36).substr(2, 9)
    
    // Add placeholder with loading state
    unifiedImages.value.push({
      type: 'new',
      file,
      preview: '',
      id: tempId,
      loading: true
    })

    // NSFW Check
    try {
      const nsfwResult = await nsfwService.checkImage(file)
      if (!nsfwResult.isSafe) {
        // Remove placeholder if image failed verification
        const index = unifiedImages.value.findIndex(img => img.id === tempId)
        if (index !== -1) {
          unifiedImages.value.splice(index, 1)
        }
        toast.value?.add(`Zdjęcie ${file.name} zostało odrzucone: wykryto treści niedozwolone`, 'error')
        continue
      }
    } catch (error) {
      console.error('NSFW check error:', error)
    }

    // Read file
    const reader = new FileReader()
    reader.onload = (e) => {
      const index = unifiedImages.value.findIndex(img => img.id === tempId)
      if (index !== -1) {
        unifiedImages.value[index].preview = e.target?.result as string
        unifiedImages.value[index].loading = false
      }
    }
    reader.readAsDataURL(file)
  }
}

const handleImageSelect = (event: Event) => {
  const input = event.target as HTMLInputElement
  processFiles(input.files)
  input.value = '' // Reset input
}

const removeImage = (index: number) => {
  unifiedImages.value.splice(index, 1)
}

const handleImageDragStart = (event: DragEvent, index: number) => {
  draggedImageIndex.value = index
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.dropEffect = 'move'
  }
}

const handleImageDragOver = (index: number) => {
  dragOverTarget.value = { index, type: unifiedImages.value[index].type }
}

const handleDragEnd = () => {
  draggedImageIndex.value = null
  dragOverTarget.value = null
  isDragging.value = false
}

const handleImageDrop = (index: number) => {
  if (draggedImageIndex.value !== null && draggedImageIndex.value !== index) {
    const item = unifiedImages.value.splice(draggedImageIndex.value, 1)[0]
    unifiedImages.value.splice(index, 0, item)
  }
  handleDragEnd()
}

const handleDrop = (event: DragEvent) => {
  // Handle file drop from OS
  const files = event.dataTransfer?.files
  if (files && files.length > 0) {
    processFiles(files)
  }
  isDragging.value = false
}

const isInPoland = (lat: number, lng: number): boolean => {
  const pt = point([lng, lat])
  // @ts-ignore
  for (const feature of polandGeoJson.features) {
    if (booleanPointInPolygon(pt, feature.geometry as any)) {
      return true
    }
  }
  return false
}

const openMapModal = () => {
  showMapModal.value = true
  setTimeout(() => initModalMap(), 100)
}

const closeMapModal = () => {
  showMapModal.value = false
  modalSearchQuery.value = ''
  modalSearchSuggestions.value = []
  showModalSearchSuggestions.value = false
  if (modalMap) {
    modalMap.remove()
    modalMap = null
    modalMarker = null
  }
}

const initModalMap = () => {
  if (!modalMapContainer.value || modalMap || !editingAd.value) return

  const polandBounds = L.latLngBounds([48.5, 13.5], [55.5, 24.5])

  // Check if coordinates are default (center of Poland) or actual location
  const isDefaultLocation = editingAd.value.latitude === 52.0 && editingAd.value.longitude === 19.0
  const zoomLevel = isDefaultLocation ? 6 : 13
  
  modalMap = L.map(modalMapContainer.value, {
    maxBounds: polandBounds,
    maxBoundsViscosity: 1.0,
    minZoom: 6,
    maxZoom: 18,
    zoomControl: true
  }).setView([editingAd.value.latitude || 52.0, editingAd.value.longitude || 19.0], zoomLevel)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(modalMap)

  modalMarker = L.marker([editingAd.value.latitude || 52.0, editingAd.value.longitude || 19.0], {
    draggable: true
  }).addTo(modalMap)

  modalMarker.on('dragend', async () => {
    const position = modalMarker!.getLatLng()
    if (!isInPoland(position.lat, position.lng)) {
      toast.value?.add('Lokalizacja musi być w Polsce', 'error')
      modalMarker!.setLatLng([editingAd.value!.latitude, editingAd.value!.longitude])
      return
    }
  })

  modalMap.on('click', async (e: L.LeafletMouseEvent) => {
    if (!isInPoland(e.latlng.lat, e.latlng.lng)) {
      toast.value?.add('Lokalizacja musi być w Polsce', 'error')
      return
    }
    modalMarker!.setLatLng(e.latlng)
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

      if (editingAd.value) {
        const address = data.address
        let city = address.city || address.town || address.village || address.municipality || address.county || address.administrative || ''
        // Usuń prefix "gmina" jeśli pochodzi z municipality
        if (!address.city && !address.town && !address.village && address.municipality) {
          city = city.replace(/^gmina\s+/i, '')
        }
        editingAd.value.city = city
        editingAd.value.region = address.state || ''
        editingAd.value.location = data.display_name || ''
      }
      return true
    }
    return false
  } catch (error) {
    console.error('Error reverse geocoding:', error)
    return false
  }
}

const searchModalLocation = () => {
  if (modalSearchTimeout) {
    clearTimeout(modalSearchTimeout)
  }

  if (modalSearchQuery.value.length < 3) {
    modalSearchSuggestions.value = []
    showModalSearchSuggestions.value = false
    return
  }

  modalSearchTimeout = setTimeout(async () => {
    try {
      const response = await fetch(
        `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(modalSearchQuery.value)}&countrycodes=pl&limit=10&addressdetails=1`
      )
      const data = await response.json()
      modalSearchSuggestions.value = data
      showModalSearchSuggestions.value = data.length > 0
    } catch (error) {
      console.error('Error searching location:', error)
    }
  }, 500)
}

const selectModalLocation = (suggestion: any) => {
  const lat = suggestion.lat
  const lng = suggestion.lng
  
  // Handle both LocationResult and raw Nominatim response
  const isLocationResult = suggestion.name !== undefined && suggestion.displayName !== undefined

  if (!isInPoland(lat, lng)) {
    toast.value?.add('Lokalizacja musi być w Polsce', 'error')
    return
  }

  if (modalMap && modalMarker) {
    modalMap.setView([lat, lng], 16)
    modalMarker.setLatLng([lat, lng])
  }

  // Update coordinates in editingAd
  if (editingAd.value) {
    editingAd.value.latitude = lat
    editingAd.value.longitude = lng
    
    if (isLocationResult) {
      // LocationResult from locationService
      editingAd.value.location = suggestion.displayName
      editingAd.value.city = suggestion.name
      editingAd.value.region = suggestion.state || ''
    } else {
      // Raw Nominatim response
      if (suggestion.address) {
        const address = suggestion.address
        let city = address.city || address.town || address.village || address.municipality || address.county || address.administrative || ''
        if (!address.city && !address.town && !address.village && address.municipality) {
          city = city.replace(/^gmina\s+/i, '')
        }
        editingAd.value.city = city
        editingAd.value.region = address.state || ''
        editingAd.value.location = suggestion.display_name || ''
      }
    }
  }

  // Update search query with selected location
  if (isLocationResult) {
    modalSearchQuery.value = suggestion.displayName
  } else {
    modalSearchQuery.value = suggestion.display_name || ''
  }
  
  modalSearchSuggestions.value = []
  showModalSearchSuggestions.value = false
}

const confirmModalLocation = async () => {
  if (!modalMarker || !editingAd.value) return

  const position = modalMarker.getLatLng()
  
  if (!isInPoland(position.lat, position.lng)) {
    toast.value?.add('Lokalizacja musi być w Polsce', 'error')
    return
  }

  const isValid = await reverseGeocode(position.lat, position.lng)
  if (!isValid) {
    toast.value?.add('Lokalizacja musi być w Polsce', 'error')
    return
  }

  editingAd.value.latitude = position.lat
  editingAd.value.longitude = position.lng
  
  closeMapModal()
}

// Computed properties for field visibility based on ad type
const showDimensionsFields = computed(() => {
  if (!editingAd.value) return false
  return ['billboard', 'citylight', 'banner', 'wall'].includes(editingAd.value.type)
})

// Computed property for available price units based on ad type
const availablePriceUnits = computed(() => {
  if (!editingAd.value) return []
  const type = editingAd.value.type
  
  if (type === 'billboard') {
    return [
      { value: 'day', label: 'za dzień' },
      { value: 'month', label: 'za miesiąc' }
    ]
  } else if (type === 'wall') {
    return [
      { value: 'month', label: 'za miesiąc' },
      { value: 'year', label: 'za rok' }
    ]
  } else if (type === 'banner') {
    return [
      { value: 'day', label: 'za dzień' },
      { value: 'week', label: 'za tydzień' },
      { value: 'month', label: 'za miesiąc' }
    ]
  } else if (type === 'citylight') {
    return [{ value: 'month', label: 'za miesiąc' }]
  } else if (type === 'led_screen') {
    return [
      { value: 'day', label: 'za dzień (emisje)' },
      { value: 'month', label: 'za miesiąc (emisje)' },
      { value: 'campaign', label: 'za kampanię' }
    ]
  } else if (type === 'totem') {
    return [{ value: 'month', label: 'za miesiąc' }]
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
  } else if (type === 'other') {
    return [
      { value: 'day', label: 'za dzień' },
      { value: 'month', label: 'za miesiąc' },
      { value: 'campaign', label: 'za kampanię' }
    ]
  }
  
  return [
    { value: 'day', label: 'za dzień' },
    { value: 'week', label: 'za tydzień' },
    { value: 'month', label: 'za miesiąc' },
    { value: 'year', label: 'za rok' }
  ]
})

const showTrafficIntensity = computed(() => {
  if (!editingAd.value) return false
  return ['billboard', 'banner', 'wall'].includes(editingAd.value.type)
})

const showLightingOption = computed(() => {
  if (!editingAd.value) return false
  return ['citylight', 'totem'].includes(editingAd.value.type)
})

const showPrintOption = computed(() => {
  if (!editingAd.value) return false
  return ['billboard', 'banner'].includes(editingAd.value.type)
})

const showMountingOption = computed(() => {
  if (!editingAd.value) return false
  return ['billboard', 'banner', 'wall'].includes(editingAd.value.type)
})

const showGraphicDesignOption = computed(() => {
  if (!editingAd.value) return false
  return ['billboard', 'banner', 'wall'].includes(editingAd.value.type)
})

const showVariantField = computed(() => {
  if (!editingAd.value) return false
  return ['billboard', 'citylight', 'led_screen', 'banner', 'wall', 'totem', 'transport', 'mobile'].includes(editingAd.value.type)
})

const showRoadClassField = computed(() => {
  if (!editingAd.value) return false
  return editingAd.value.type === 'billboard'
})

const showTrafficDirection = computed(() => {
  if (!editingAd.value) return false
  return editingAd.value.type === 'billboard'
})

const showTrafficType = computed(() => {
  if (!editingAd.value) return false
  return editingAd.value.type === 'banner'
})

const showEnvironmentField = computed(() => {
  if (!editingAd.value) return false
  return ['citylight', 'led_screen', 'totem', 'banner', 'mobile', 'other'].includes(editingAd.value.type)
})

const showLEDFields = computed(() => {
  if (!editingAd.value) return false
  return editingAd.value.type === 'led_screen'
})

const showTransportFields = computed(() => {
  if (!editingAd.value) return false
  return editingAd.value.type === 'transport'
})

const showMobileFields = computed(() => {
  if (!editingAd.value) return false
  return editingAd.value.type === 'mobile'
})

const getVariantOptions = (type: string) => {
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
        { value: 'double', label: 'Podwójny' },
        { value: 'digital', label: 'Cyfrowy' }
      ]
    case 'led_screen':
      return [
        { value: 'standard', label: 'Standardowy' },
        { value: 'interactive', label: 'Interaktywny' }
      ]
    case 'banner':
      return [
        { value: 'pvc', label: 'PCV' },
        { value: 'mesh', label: 'Siatkowy/Mesh' },
        { value: 'textile', label: 'Tekstylny' }
      ]
    case 'wall':
      return [
        { value: 'mural', label: 'Mural' },
        { value: 'foil', label: 'Folia' },
        { value: 'construction', label: 'Konstrukcja' }
      ]
    case 'totem':
      return [
        { value: 'single_sided', label: 'Jednostronny' },
        { value: 'double_sided', label: 'Dwustronny' },
        { value: 'multi_sided', label: 'Wielostronny' },
        { value: 'digital', label: 'Digital' }
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
        { value: 'bike', label: 'Rower' },
        { value: 'other', label: 'Inna' }
      ]
    default:
      return []
  }
}

const getEnvironmentOptions = (type: string) => {
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
}

const handleSubmit = async () => {
  if (!email.value || !email.value.includes('@')) {
    errorMessage.value = 'Proszę podać poprawny adres email'
    return
  }

  errorMessage.value = ''
  isSubmitting.value = true

  try {
    // Send request to backend API
    await axios.post('/api/management/send-link', {
      email: email.value
    })

    isSuccess.value = true
    
    // Redirect after 5 seconds
    setTimeout(() => {
      router.push('/')
    }, 5000)
  } catch (error) {
    console.error('Error sending management link:', error)
    errorMessage.value = 'Wystąpił błąd podczas wysyłania linku. Spróbuj ponownie.'
  } finally {
    isSubmitting.value = false
  }
}

// Sprawdź czy ogłoszenie jest już na wykresie
const isAdOnChart = (adId: string): boolean => {
  if (!engagementChartRef.value) return false
  const chartComponent = engagementChartRef.value as any
  let selectedAds: string[] = []
  
  if (chartComponent.selectedAds?.value) {
    selectedAds = chartComponent.selectedAds.value
  } else if (Array.isArray(chartComponent.selectedAds)) {
    selectedAds = chartComponent.selectedAds
  }
  
  return selectedAds.includes(adId)
}

// Dodaj ogłoszenie do wykresu i scroll (lub pokaż komunikat jeśli max)
const addAdToChart = (adId: string) => {
  if (engagementChartRef.value) {
    // Sprawdź czy jest miejsce
    const chartComponent = engagementChartRef.value as any
    let selectedAds: string[] = []
    
    // Spróbuj różne sposoby dostępu do selectedAds
    if (chartComponent.selectedAds?.value) {
      selectedAds = chartComponent.selectedAds.value
    } else if (Array.isArray(chartComponent.selectedAds)) {
      selectedAds = chartComponent.selectedAds
    }
    
    // Sprawdź czy jest maksimum i ogłoszenie nie jest już dodane
    if (selectedAds.length >= 5 && !selectedAds.includes(adId)) {
      // Pokaż toast/komunikat - NIE scrolluj
      if (toast.value) {
        toast.value.add('Maksymalna ilość ogłoszeń (5) już dodana do wykresu', 'error')
      }
      return
    }
    
    // Dodaj ogłoszenie
    engagementChartRef.value.addAdsToChart([adId])
    
    // Scroll do wykresu z offsetem dla headera
    nextTick(() => {
      const chartElement = document.querySelector('.engagement-chart-container')
      if (chartElement) {
        const headerHeight = 80 // Przybliżona wysokość headera
        const elementPosition = chartElement.getBoundingClientRect().top + window.scrollY
        window.scrollTo({
          top: elementPosition - headerHeight,
          behavior: 'smooth'
        })
      }
    })
  }
}

const pendingTopAdsMetric = ref<'views' | 'clicks' | undefined>(undefined)

// Dodaj top ogłoszenia do wykresu (nadpisz wszystkie)
const addTopAdsToChart = (adIds: string[], metric?: 'views' | 'clicks') => {
  if (engagementChartRef.value) {
    const chartComponent = engagementChartRef.value as any
    let selectedAds: string[] = []
    
    // Pobierz aktualnie wybrane ogłoszenia
    if (chartComponent.selectedAds?.value) {
      selectedAds = chartComponent.selectedAds.value
    } else if (Array.isArray(chartComponent.selectedAds)) {
      selectedAds = chartComponent.selectedAds
    }
    
    // Jeśli są już wybrane ogłoszenia, pokaż potwierdzenie
    if (selectedAds.length > 0) {
      pendingTopAdsToAdd.value = adIds
      pendingTopAdsMetric.value = metric
      confirmDialogTitle.value = 'Nadpisać wybrane ogłoszenia?'
      confirmDialogMessage.value = `Masz już dodane ogłoszenia na wykresie. Nadpisać i dodać nowe z wybranej tabeli?`
      confirmDialogType.value = 'warning'
      confirmDialog.value?.open()
      return
    }
    
    // Jeśli nie ma poprzednich wyborów, dodaj od razu
    executeAddTopAdsToChart(adIds, metric)
  }
}

// Wykonaj dodanie top ogłoszeń
const executeAddTopAdsToChart = (adIds: string[], metric?: 'views' | 'clicks') => {
  if (engagementChartRef.value) {
    const chartComponent = engagementChartRef.value as any
    
    // Wyczyść poprzednie wybory i dodaj nowe
    if (chartComponent.selectedAds) {
      // Wyczyść wszystko
      chartComponent.selectedAds.length = 0
      // Dodaj nowe top 5
      adIds.forEach((id: string) => {
        chartComponent.selectedAds.push(id)
      })
    }
    
    // Ustaw metrykę jeśli podana
    if (metric && typeof chartComponent.setMetric === 'function') {
      chartComponent.setMetric(metric)
    }

    // Scroll do wykresu z offsetem dla headera
    nextTick(() => {
      const chartElement = document.querySelector('.engagement-chart-container')
      if (chartElement) {
        const headerHeight = 80 // Przybliżona wysokość headera
        const elementPosition = chartElement.getBoundingClientRect().top + window.scrollY
        window.scrollTo({
          top: elementPosition - headerHeight,
          behavior: 'smooth'
        })
      }
    })
  }
}

// Statistics computed properties
const totalViews = computed(() => {
  return listings.value.reduce((sum, ad) => sum + (ad.views_30d || 0), 0)
})

const totalPhoneClicks = computed(() => {
  return listings.value.reduce((sum, ad) => sum + (ad.phone_clicks_30d || 0), 0)
})

const totalEmailClicks = computed(() => {
  return listings.value.reduce((sum, ad) => sum + (ad.email_clicks_30d || 0), 0)
})

const totalEngagement = computed(() => {
  return totalPhoneClicks.value + totalEmailClicks.value
})

const engagementRate = computed(() => {
  if (totalViews.value === 0) return 0
  return ((totalEngagement.value / totalViews.value) * 100).toFixed(2)
})

const topPerformingAds = computed(() => {
  return [...listings.value]
    .sort((a, b) => ((b.views_30d || 0) - (a.views_30d || 0)))
    .slice(0, 5)
})

const mostEngagingAds = computed(() => {
  return [...listings.value]
    .sort((a, b) => {
      const aEngagement = (a.phone_clicks_30d || 0) + (a.email_clicks_30d || 0)
      const bEngagement = (b.phone_clicks_30d || 0) + (b.email_clicks_30d || 0)
      return bEngagement - aEngagement
    })
    .slice(0, 5)
})

const adsByType = computed(() => {
  const types: Record<string, number> = {}
  listings.value.forEach(ad => {
    types[ad.type] = (types[ad.type] || 0) + 1
  })
  return Object.entries(types).map(([type, count]) => ({
    type,
    label: getTypeLabel(type),
    count
  }))
})

const adsByStatus = computed(() => {
  const statuses = {
    active: listings.value.filter(ad => ad.status === 'active').length,
    reserved: listings.value.filter(ad => ad.status === 'reserved').length,
    soon_available: listings.value.filter(ad => ad.status === 'soon_available').length
  }
  return statuses
})

onMounted(async () => {
  await loadAdvertisements()
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="management-page">
    <div class="page-header" v-if="hasToken">
      <div class="container">
        <button @click="router.back()" class="back-button">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M15 10H5M5 10L10 15M5 10L10 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Powrót
        </button>
        <div class="header-content">
          <h1>Panel zarządzania ogłoszeniami</h1>
          <p class="header-subtitle" v-if="!hasToken">Zarządzaj swoimi ogłoszeniami w jednym miejscu</p>
          <div v-else class="token-info-header">
            <p>Email: <strong>{{ tokenEmail }}</strong></p>
            <p>Link ważny do: <strong>{{ tokenExpiresAt }}</strong></p>
          </div>
        </div>
      </div>
    </div>

    <div class="page-content">
      <div class="container">
        <div v-if="isLoading" class="loading-state">
          <div class="spinner"></div>
          <p>Ładowanie ogłoszeń...</p>
        </div>
        
        <div v-else-if="isTokenInvalid" class="empty-state">
          <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="1.5">
            <circle cx="12" cy="12" r="10" />
            <path d="M12 8v4M12 16h.01" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
          <h2>Ups...</h2>
          <p>Widocznie Twój link stracił ważność lub jest nieprawidłowy.</p>
          <button @click="isTokenInvalid = false; hasToken = false" class="btn-primary">
            Wyślij nowy link
          </button>
        </div>
        
        <div v-else-if="hasToken">
          <div v-if="listings.length === 0" class="empty-state">
            <svg width="120" height="120" viewBox="0 0 24 24" fill="none">
              <rect x="3" y="3" width="18" height="18" rx="2" stroke="#d1d5db" stroke-width="2"/>
              <path d="M3 9h18M9 3v18" stroke="#d1d5db" stroke-width="2"/>
            </svg>
            <h2>Brak ogłoszeń</h2>
            <p>Nie masz jeszcze żadnych ogłoszeń do zarządzania</p>
            <button @click="router.push('/dodaj-powierzchnie-reklamowa')" class="btn-primary">
              Dodaj pierwsze ogłoszenie
            </button>
          </div>

          <div v-else-if="listings.length > 0">
            <!-- Tabs Navigation -->
            <div class="tabs-navigation">
              <button 
                @click="activeTab = 'listings'" 
                class="tab-button" 
                :class="{ active: activeTab === 'listings' }"
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <rect x="3" y="3" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
                  <rect x="14" y="3" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
                  <rect x="3" y="14" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
                  <rect x="14" y="14" width="7" height="7" stroke="currentColor" stroke-width="2" rx="1"/>
                </svg>
                Ogłoszenia
              </button>
              <button 
                @click="activeTab = 'statistics'" 
                class="tab-button" 
                :class="{ active: activeTab === 'statistics' }"
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <path d="M3 3v18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  <path d="M7 16v-6M12 16V8M17 16v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Statystyki
              </button>
            </div>

            <!-- Statistics Dashboard -->
            <div v-if="activeTab === 'statistics'" class="statistics-dashboard">
              <!-- Summary Cards -->
              <div class="stats-grid">
                <div class="stat-card">
                  <div class="stat-icon views">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/>
                      <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                    </svg>
                  </div>
                  <div class="stat-content">
                    <div class="stat-label">Łączne wyświetlenia</div>
                    <div class="stat-value">{{ totalViews.toLocaleString('pl-PL') }}</div>
                  </div>
                </div>

                <div class="stat-card">
                  <div class="stat-icon phone">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                      <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                  </div>
                  <div class="stat-content">
                    <div class="stat-label">Kliknięcia w telefon</div>
                    <div class="stat-value">{{ totalPhoneClicks.toLocaleString('pl-PL') }}</div>
                  </div>
                </div>

                <div class="stat-card">
                  <div class="stat-icon email">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                      <rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/>
                      <path d="M3 7l9 6 9-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                  </div>
                  <div class="stat-content">
                    <div class="stat-label">Wysłane wiadomości</div>
                    <div class="stat-value">{{ totalEmailClicks.toLocaleString('pl-PL') }}</div>
                  </div>
                </div>

                <div class="stat-card">
                  <div class="stat-icon engagement">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                      <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </div>
                  <div class="stat-content">
                    <div class="stat-label">Wskaźnik zaangażowania</div>
                    <div class="stat-value">{{ engagementRate }}%</div>
                    <div class="stat-sublabel">{{ totalEngagement }} z {{ totalViews }} wyświetleń</div>
                  </div>
                </div>
              </div>

              <!-- Charts Section -->
              <div class="charts-section">
                <!-- Top Performing Ads -->
                <div class="chart-card">
                  <div class="chart-card-header">
                    <h3>Najczęściej wyświetlane ogłoszenia</h3>
                    <button 
                      v-if="topPerformingAds.length > 0"
                      @click="addTopAdsToChart(topPerformingAds.slice(0, 5).map(ad => ad.id), 'views')"
                      class="chart-quick-add-btn"
                      title="Dodaj top 5 do wykresu"
                    >
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                      </svg>
                      Porównaj wszystkie
                    </button>
                  </div>
                  <div class="chart-list">
                    <div v-for="(ad, index) in topPerformingAds" :key="ad.id" class="chart-item">
                      <div class="chart-item-rank">{{ index + 1 }}</div>
                      <div class="chart-item-info">
                        <div class="chart-item-title">{{ ad.title }}</div>
                        <div class="chart-item-meta">{{ ad.city }} • {{ getTypeLabel(ad.type) }}</div>
                      </div>
                      <div class="chart-item-value">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/>
                          <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        {{ ad.views_30d || 0 }}
                      </div>
                      <div class="chart-item-bar">
                        <div 
                          class="chart-item-bar-fill" 
                          :style="{ width: `${(ad.views_30d || 0) / (topPerformingAds[0]?.views_30d || 1) * 100}%` }"
                        ></div>
                      </div>
                      <button 
                        @click="addAdToChart(ad.id)" 
                        :disabled="isAdOnChart(ad.id)"
                        class="chart-item-btn" 
                        :title="isAdOnChart(ad.id) ? 'Ogłoszenie już na wykresie' : 'Porównaj na wykresie'"
                      >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                          <path d="M3 3v18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                          <path d="M7 16v-6M12 16V8M17 16v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                      </button>
                    </div>
                    <div v-if="topPerformingAds.length === 0" class="chart-empty">
                      Brak danych do wyświetlenia
                    </div>
                  </div>
                </div>

                <!-- Most Engaging Ads -->
                <div class="chart-card">
                  <div class="chart-card-header">
                    <h3>Najbardziej angażujące ogłoszenia</h3>
                    <button 
                      v-if="mostEngagingAds.length > 0"
                      @click="addTopAdsToChart(mostEngagingAds.slice(0, 5).map(ad => ad.id), 'clicks')"
                      class="chart-quick-add-btn"
                      title="Dodaj top 5 do wykresu"
                    >
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                      </svg>
                      Porównaj wszystkie
                    </button>
                  </div>
                  <div class="chart-list">
                    <div v-for="(ad, index) in mostEngagingAds" :key="ad.id" class="chart-item">
                      <div class="chart-item-rank">{{ index + 1 }}</div>
                      <div class="chart-item-info">
                        <div class="chart-item-title">{{ ad.title }}</div>
                        <div class="chart-item-meta">{{ ad.city }} • {{ getTypeLabel(ad.type) }}</div>
                      </div>
                      <div class="chart-item-value">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                          <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        {{ (ad.phone_clicks_30d || 0) + (ad.email_clicks_30d || 0) }}
                      </div>
                      <div class="chart-item-bar">
                        <div 
                          class="chart-item-bar-fill engagement" 
                          :style="{ width: `${((ad.phone_clicks_30d || 0) + (ad.email_clicks_30d || 0)) / Math.max(((mostEngagingAds[0]?.phone_clicks_30d || 0) + (mostEngagingAds[0]?.email_clicks_30d || 0)), 1) * 100}%` }"
                        ></div>
                      </div>
                      <button 
                        @click="addAdToChart(ad.id)" 
                        :disabled="isAdOnChart(ad.id)"
                        class="chart-item-btn" 
                        :title="isAdOnChart(ad.id) ? 'Ogłoszenie już na wykresie' : 'Porównaj na wykresie'"
                      >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                          <path d="M3 3v18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                          <path d="M7 16v-6M12 16V8M17 16v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                      </button>
                    </div>
                    <div v-if="mostEngagingAds.length === 0" class="chart-empty">
                      Brak danych do wyświetlenia
                    </div>
                  </div>
                </div>
              </div>

              <!-- Engagement Chart -->
              <EngagementChart ref="engagementChartRef" :ads="listings" />

              <!-- Additional Stats -->
              <div class="additional-stats">
                <!-- Ads by Type -->
                <div class="stat-breakdown-card">
                  <h3>Ogłoszenia według typu</h3>
                  <div class="breakdown-list">
                    <div v-for="item in adsByType" :key="item.type" class="breakdown-item">
                      <div class="breakdown-label">{{ item.label }}</div>
                      <div class="breakdown-value">{{ item.count }}</div>
                      <div class="breakdown-bar">
                        <div 
                          class="breakdown-bar-fill" 
                          :style="{ width: `${(item.count / listings.length) * 100}%` }"
                        ></div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Ads by Status -->
                <div class="stat-breakdown-card">
                  <h3>Ogłoszenia według statusu</h3>
                  <div class="breakdown-list">
                    <div class="breakdown-item">
                      <div class="breakdown-label">
                        <span class="status-dot active"></span>
                        Wolne
                      </div>
                      <div class="breakdown-value">{{ adsByStatus.active }}</div>
                    </div>
                    <div class="breakdown-item">
                      <div class="breakdown-label">
                        <span class="status-dot reserved"></span>
                        Zarezerwowane
                      </div>
                      <div class="breakdown-value">{{ adsByStatus.reserved }}</div>
                    </div>
                    <div class="breakdown-item">
                      <div class="breakdown-label">
                        <span class="status-dot soon"></span>
                        Wkrótce dostępne
                      </div>
                      <div class="breakdown-value">{{ adsByStatus.soon_available }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Listings View -->
            <div v-else class="listings-list">
            <div class="stats-bar">
              <div class="stat">
                <span class="stat-label">Wszystkie ogłoszenia</span>
                <span class="stat-value">{{ listings.length }}</span>
              </div>
              <div class="stat">
                <span class="stat-label">Aktywne</span>
                <span class="stat-value">{{ listings.filter(ad => ad.is_active).length }}</span>
              </div>
              <div class="stat">
                <span class="stat-label">Nieaktywne</span>
                <span class="stat-value">{{ listings.filter(ad => !ad.is_active).length }}</span>
              </div>
            </div>

            <div v-for="ad in listings" :key="ad.id" :id="'listing-row-' + ad.id" class="listing-row" :class="{ expanded: expandedRows.has(ad.id) }">
              <div class="listing-summary" @click="toggleRow(ad.id)">
                <div class="listing-thumbnail">
                  <WebPImage v-if="ad.image_url" :src="ad.image_url" :alt="ad.title" />
                  <div v-else class="no-image">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
                      <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                      <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                      <path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2"/>
                    </svg>
                  </div>
                </div>

                <div class="listing-info">
                  <h3 class="listing-title">{{ ad.title }}</h3>
                  <p class="listing-meta">
                    {{ ad.city }} • {{ getTypeLabel(ad.type) }}
                    <template v-if="ad.width && ad.height && ad.width > 0 && ad.height > 0">
                      • {{ ad.width }}m × {{ ad.height }}m
                    </template>
                  </p>
                </div>

                <div class="listing-controls" @click.stop>
                  <div class="status-dropdown">
                    <select 
                      :value="pendingStatusChanges[ad.id] || ad.status" 
                      @change="handleStatusChange(ad.id, ($event.target as HTMLSelectElement).value)" 
                      class="status-select"
                      :class="{ 'has-pending': pendingStatusChanges[ad.id] }"
                    >
                      <option value="active">Wolne</option>
                      <option value="reserved">Zarezerwowane</option>
                      <option value="soon_available">Wkrótce dostępne</option>
                    </select>
                    
                    <div v-if="pendingStatusChanges[ad.id]" class="status-actions">
                      <button @click.stop="confirmStatusChange(ad.id)" class="status-btn confirm" title="Zapisz">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                          <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </button>
                      <button @click.stop="cancelStatusChange(ad.id)" class="status-btn cancel" title="Anuluj">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                          <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </button>
                    </div>
                  </div>

                  <label class="switch">
                    <input type="checkbox" :checked="ad.is_active" @change="toggleActive(ad.id)" />
                    <span class="slider"></span>
                    <span class="switch-label">{{ ad.is_active ? 'Aktywne' : 'Nieaktywne' }}</span>
                  </label>

                  <div class="views-counter">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                      <path d="M12 5C7 5 2.73 8.11 1 12.5 2.73 16.89 7 20 12 20s9.27-3.11 11-7.5C21.27 8.11 17 5 12 5zm0 12.5c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" fill="currentColor"/>
                    </svg>
                    <span>{{ ad.views || 0 }}</span>
                  </div>

                  <button @click.stop="openPreview(ad.id)" class="preview-btn" title="Zobacz ogłoszenie">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                      <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>

                  <button @click.stop="deleteAd(ad.id)" class="delete-btn" title="Usuń ogłoszenie">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                      <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14zM10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                  </button>
                </div>

                <div class="expand-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>
              </div>

              <div v-if="expandedRows.has(ad.id) && editingAd" class="listing-details">
                <form @submit.prevent="saveChanges(ad.id)" class="edit-form">
                  <div class="form-grid">
                  <div class="form-group full-width">
                      <label>Zdjęcia (max 5)</label>
                      <p class="help-text">Pierwsze zdjęcie będzie zdjęciem głównym. Przeciągnij, aby zmienić kolejność.</p>
                      <div 
                        class="images-grid"
                        :class="{ 'dragging': isDragging }"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="handleDrop"
                      >
                        <div 
                          v-for="(img, index) in unifiedImages" 
                          :key="img.id" 
                          class="image-item"
                          :class="{ 
                            'drag-over': dragOverTarget?.index === index,
                            'dragging': draggedImageIndex === index,
                            'new': img.type === 'new'
                          }"
                          draggable="true"
                          @dragstart="handleImageDragStart($event, index)"
                          @dragover.prevent="handleImageDragOver(index)"
                          @dragend="handleDragEnd"
                          @drop.prevent="handleImageDrop(index)"
                        >
                          <div v-if="img.loading" class="image-loader">
                            <div class="spinner-small"></div>
                          </div>
                          <WebPImage v-else-if="img.type === 'existing'" :src="img.url || ''" alt="Zdjęcie" />
                          <img v-else :src="img.preview" alt="Zdjęcie" />
                          <span v-if="index === 0" class="main-badge">Główne</span>
                          <button type="button" @click="removeImage(index)" class="remove-btn" title="Usuń">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                              <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                          </button>
                        </div>

                        <!-- Upload Button -->
                        <div v-if="getTotalImagesCount() < 5" class="upload-btn-wrapper">
                          <input 
                            type="file" 
                            accept="image/*" 
                            multiple
                            @change="handleImageSelect" 
                            :id="'image-upload-' + ad.id"
                            class="file-input"
                            style="display: none"
                          />
                          <label :for="'image-upload-' + ad.id" class="upload-btn" title="Kliknij lub upuść tutaj">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                              <path d="M12 4v16m-8-8h16" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span class="upload-text">Dodaj</span>
                          </label>
                        </div>
                      </div>
                      <p class="help-text">Przeciągnij i upuść zdjęcia tutaj lub kliknij "Dodaj"</p>
                    </div>

                    <div class="form-group full-width">
                      <label>Tytuł</label>
                      <input v-model="editingAd.title" type="text" required />
                    </div>

                    <div class="form-group full-width">
                      <label>Opis</label>
                      <textarea v-model="editingAd.description" rows="4" required></textarea>
                    </div>

                    <div class="form-group">
                      <label>Cena</label>
                      <input v-model.number="editingAd.price" type="number" required />
                    </div>

                    <div class="form-group">
                      <label>Jednostka cenowa</label>
                      <select v-model="editingAd.price_unit" required>
                        <option v-for="unit in availablePriceUnits" :key="unit.value" :value="unit.value">
                          {{ unit.label }}
                        </option>
                      </select>
                    </div>

                    <div class="form-group checkbox-group full-width">
                      <label>
                        <input v-model="editingAd.price_negotiable" type="checkbox" />
                        <span>Cena do negocjacji</span>
                      </label>
                    </div>

                    <div class="form-group">
                      <label>Typ powierzchni</label>
                      <select v-model="editingAd.type" required disabled>
                        <option value="billboard">Billboardy</option>
                        <option value="citylight">Citylighty</option>
                        <option value="led_screen">Ekrany LED</option>
                        <option value="banner">Banery</option>
                        <option value="wall">Ściany reklamowe</option>
                        <option value="totem">Totemy reklamowe</option>
                        <option value="transport">Reklama w transporcie</option>
                        <option value="mobile">Reklama mobilna</option>
                        <option value="other">Inne</option>
                      </select>
                    </div>

                    <div class="form-group">
                      <label>Lokalizacja</label>
                      <div class="location-input-group">
                        <div class="address-input-wrapper">
                          <input 
                            v-model="editingAd.location" 
                            type="text" 
                            required 
                            @input="searchAddress(editingAd.location)"
                            @blur="handleBlur"
                            class="location-input"
                          />
                          <div v-if="isResolvingAddress" class="input-spinner">
                            <svg class="spinner-icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                          </div>
                          <button 
                            v-if="editingAd.location && !isResolvingAddress" 
                            type="button" 
                            @click="clearLocation" 
                            class="clear-location-btn"
                            title="Wyczyść lokalizację"
                          >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                              <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                          </button>
                          <div v-if="showAddressSuggestions && addressSuggestions.length > 0" class="address-suggestions">
                            <div 
                              v-for="suggestion in addressSuggestions" 
                              :key="suggestion.name"
                              @click="selectAddress(suggestion)"
                              class="suggestion-item"
                            >
                              {{ suggestion.displayName || suggestion.display_name }}
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
                    </div>

                    <!-- SEKCJA: Wymiary i lokalizacja -->
                    <div v-if="showDimensionsFields" class="form-section-divider">
                      <h4>Wymiary</h4>
                    </div>

                    <div v-if="showDimensionsFields" class="form-group">
                      <label>Szerokość (m)</label>
                      <input v-model.number="editingAd.width" type="number" step="0.1" :required="showDimensionsFields" />
                    </div>

                    <div v-if="showDimensionsFields" class="form-group">
                      <label>Wysokość (m)</label>
                      <input v-model.number="editingAd.height" type="number" step="0.1" :required="showDimensionsFields" />
                    </div>

                    <!-- SEKCJA: Opcje specyficzne dla typu -->
                    <div v-if="editingAd.type" class="form-section-divider">
                      <h4>Opcje specyficzne dla typu</h4>
                    </div>

                    <!-- Natężenie ruchu -->
                    <div v-if="showTrafficIntensity" class="form-group">
                      <label>Natężenie ruchu</label>
                      <select v-model="editingAd.traffic_intensity" :required="showTrafficIntensity">
                        <option value="">Wybierz</option>
                        <option value="low">Niskie</option>
                        <option value="medium">Średnie</option>
                        <option value="high">Wysokie</option>
                      </select>
                    </div>

                    <!-- Wariant -->
                    <div v-if="showVariantField" class="form-group">
                      <label>Wariant <span style="color: red;">*</span></label>
                      <select v-model="(editingAd as any).variant">
                        <option value="">Wybierz wariant</option>
                        <option v-for="variant in getVariantOptions(editingAd.type)" :key="variant.value" :value="variant.value">
                          {{ variant.label }}
                        </option>
                      </select>
                    </div>

                    <!-- Klasa drogi (Billboard) -->
                    <div v-if="showRoadClassField" class="form-group">
                      <label>Klasa drogi</label>
                      <select v-model="(editingAd as any).road_class">
                        <option value="">Wybierz klasę drogi</option>
                        <option value="highway">Autostrada</option>
                        <option value="expressway">Droga ekspresowa</option>
                        <option value="national">Droga krajowa</option>
                        <option value="regional">Droga wojewódzka</option>
                        <option value="local">Droga lokalna</option>
                        <option value="urban">Droga miejska</option>
                      </select>
                    </div>

                    <!-- Kierunek ruchu (Billboard, Wall) -->
                    <div v-if="showTrafficDirection" class="form-group full-width">
                      <label>Kierunek ruchu</label>
                      <div class="checkbox-group">
                        <label>
                          <input type="checkbox" value="entry" v-model="(editingAd as any).traffic_direction" />
                          <span>Wjazd do miasta</span>
                        </label>
                        <label>
                          <input type="checkbox" value="exit" v-model="(editingAd as any).traffic_direction" />
                          <span>Wyjazd z miasta</span>
                        </label>
                      </div>
                    </div>

                    <!-- Rodzaj ruchu (Banner) -->
                    <div v-if="showTrafficType" class="form-group full-width">
                      <label>Rodzaj ruchu</label>
                      <div class="checkbox-group">
                        <label>
                          <input type="checkbox" value="pedestrian" v-model="(editingAd as any).traffic_type" />
                          <span>Pieszy</span>
                        </label>
                        <label>
                          <input type="checkbox" value="vehicular" v-model="(editingAd as any).traffic_type" />
                          <span>Samochodowy</span>
                        </label>
                      </div>
                    </div>

                    <!-- Środowisko -->
                    <div v-if="showEnvironmentField" class="form-group">
                      <label>Środowisko</label>
                      <select v-model="(editingAd as any).environment">
                        <option value="">Wybierz środowisko (opcjonalnie)</option>
                        <option v-for="env in getEnvironmentOptions(editingAd.type)" :key="env.value" :value="env.value">
                          {{ env.label }}
                        </option>
                      </select>
                    </div>

                    <!-- Pola LED Screen -->
                    <div v-if="showLEDFields" class="form-group">
                      <label>Czas spotu (sekundy)</label>
                      <input v-model.number="(editingAd as any).spot_duration" type="number" step="1" placeholder="10" />
                    </div>

                    <div v-if="showLEDFields" class="form-group">
                      <label>Pętla emisji (sekundy)</label>
                      <input v-model.number="(editingAd as any).loop_duration" type="number" step="1" placeholder="120" />
                    </div>

                    <!-- Pola Transport -->
                    <div v-if="showTransportFields" class="form-group">
                      <label>Zakres reklamy</label>
                      <select v-model="(editingAd as any).transport_scope">
                        <option value="">Wybierz zakres</option>
                        <option value="internal">Wewnętrzna</option>
                        <option value="external">Zewnętrzna</option>
                        <option value="full_vehicle">Całopojazdowa</option>
                      </select>
                    </div>

                    <div v-if="showTransportFields" class="form-group">
                      <label>Liczba pojazdów</label>
                      <input v-model.number="(editingAd as any).vehicle_count" type="number" step="1" placeholder="1" />
                    </div>

                    <!-- Pola Mobile -->
                    <div v-if="showMobileFields" class="form-group">
                      <label>Tryb ekspozycji</label>
                      <select v-model="(editingAd as any).mobile_exposure_mode">
                        <option value="">Wybierz tryb</option>
                        <option value="moving">Jeżdżąca</option>
                        <option value="stationary">Stojąca</option>
                        <option value="mixed">Mieszana</option>
                      </select>
                    </div>

                    <div v-if="showMobileFields" class="form-group">
                      <label>Godziny działania</label>
                      <input v-model="(editingAd as any).operating_hours" type="text" placeholder="np. 8:00-20:00" />
                    </div>

                    <div v-if="showMobileFields" class="form-group full-width">
                      <label>Trasa / Obszar</label>
                      <textarea v-model="(editingAd as any).route_area" rows="3" placeholder="Opis trasy lub obszaru działania..."></textarea>
                    </div>

                    <!-- SEKCJA: Dostępność i typ oferty -->
                    <div class="form-section-divider">
                      <h4>Dostępność i typ oferty</h4>
                    </div>

                    <div class="form-group">
                      <label>Rodzaj oferty</label>
                      <select v-model="editingAd.offer_type" required>
                        <option value="owner">Właściciel</option>
                        <option value="agency">Agencja</option>
                      </select>
                    </div>

                    <!-- SEKCJA: Wyposażenie i dodatki -->
                    <div class="form-section-divider">
                      <h4>Wyposażenie i dodatki</h4>
                    </div>

                    <div class="form-group checkbox-group full-width">
                      <label v-if="showLightingOption">
                        <input v-model="editingAd.has_backlight" type="checkbox" />
                        <span>Podświetlenie</span>
                      </label>
                      <label v-if="showPrintOption">
                        <input v-model="editingAd.price_includes_print" type="checkbox" />
                        <span>Druk w cenie</span>
                      </label>
                      <label v-if="showMountingOption">
                        <input v-model="(editingAd as any).price_includes_mounting" type="checkbox" />
                        <span>Montaż w cenie</span>
                      </label>
                      <label v-if="showGraphicDesignOption">
                        <input v-model="editingAd.graphic_design_help" type="checkbox" />
                        <span>Pomoc graficzna</span>
                      </label>
                      <label>
                        <input v-model="editingAd.has_vat_invoice" type="checkbox" />
                        <span>Faktura VAT</span>
                      </label>
                    </div>

                    <div class="form-group full-width">
                      <label>Preferowana forma kontaktu</label>
                      <select v-model="(editingAd as any).contact_preference" required>
                        <option value="email">Tylko formularz kontaktowy</option>
                        <option value="phone">Tylko telefon</option>
                        <option value="both">Formularz i telefon</option>
                      </select>
                    </div>

                    <div v-if="(editingAd as any).contact_preference === 'phone' || (editingAd as any).contact_preference === 'both'" class="form-group full-width">
                      <label>Numer telefonu</label>
                      <div class="phone-input-with-prefix">
                        <div class="phone-prefix">
                          <svg class="flag-icon" viewBox="0 0 640 480" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                            <rect width="640" height="240" fill="#fff"/>
                            <rect y="240" width="640" height="240" fill="#dc143c"/>
                          </svg>
                          <span>+48</span>
                        </div>
                        <input
                          v-model="(editingAd as any).phone"
                          type="tel"
                          class="phone-input-field"
                          placeholder="123 456 789"
                          maxlength="9"
                          @input="(editingAd as any).phone = (editingAd as any).phone.replace(/[^0-9]/g, '')"
                        />
                      </div>
                    </div>
                  </div>

                    <div class="form-actions">
                      <button type="button" @click="toggleRow(ad.id)" class="btn-cancel" :disabled="isSaving">
                        Anuluj
                      </button>
                      <button type="submit" class="btn-save" :disabled="isSaving">
                        <template v-if="isSaving">
                          <svg class="spinner-icon" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                          </svg>
                          Zapisywanie...
                        </template>
                        <template v-else>
                          <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" stroke="currentColor" stroke-width="2"/>
                            <path d="M17 21v-8H7v8M7 3v5h8" stroke="currentColor" stroke-width="2"/>
                          </svg>
                          Zapisz zmiany
                        </template>
                      </button>
                    </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        
        <div v-else class="content-card">
          <div v-if="!isSuccess" class="card-body">
            <div class="icon-wrapper">
              <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="3" y="3" width="18" height="18" rx="2" stroke="#667eea" stroke-width="2"/>
                <path d="M3 9h18M9 3v18" stroke="#667eea" stroke-width="2"/>
              </svg>
            </div>

            <h1>Panel zarządzania ogłoszeniami</h1>
            <p class="description">
              Podaj swój adres e-mail, aby otrzymać link do panelu zarządzania Twoimi ogłoszeniami.
            </p>

            <form @submit.prevent="handleSubmit" class="email-form">
              <div class="input-wrapper">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3 4H17C17.55 4 18 4.45 18 5V15C18 15.55 17.55 16 17 16H3C2.45 16 2 15.55 2 15V5C2 4.45 2.45 4 3 4Z" stroke="#4F46E5" stroke-width="1.5"/>
                  <path d="M18 5L10 11L2 5" stroke="#4F46E5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <input
                  v-model="email"
                  type="email"
                  placeholder="twoj@email.com"
                  required
                  class="email-input"
                />
              </div>
              
              <div v-if="errorMessage" class="error-message">
                {{ errorMessage }}
              </div>

              <button type="submit" :disabled="isSubmitting" class="submit-btn">
                <span v-if="!isSubmitting">Wyślij link do panelu</span>
                <span v-else class="loading">Wysyłam...</span>
              </button>
              
              <p class="info-text">
                Link będzie ważny przez 24 godziny
              </p>
            </form>
          </div>

          <div v-else class="success-body">
            <div class="success-icon">
              <svg width="80" height="80" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="32" cy="32" r="32" fill="#10B981" opacity="0.1"/>
                <circle cx="32" cy="32" r="24" fill="#10B981"/>
                <path d="M22 32L28 38L42 24" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h2 class="success-title">Link został wysłany!</h2>
            <p class="success-description">
              Sprawdź swoją skrzynkę odbiorczą na adresie <strong>{{ email }}</strong>
              <br>
              <span class="redirect-info">Za chwilę nastąpi przekierowanie na stronę główną...</span>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Date Modal for Soon Available Status -->
  <div v-if="showDateModal" class="date-modal-overlay" @click="cancelDateModal">
    <div class="date-modal-content" @click.stop>
      <h3>Data dostępności</h3>
      <p>Wybierz datę, od kiedy powierzchnia będzie dostępna:</p>
      
      <div class="form-group">
        <label class="form-label">Data dostępności</label>
        <VueDatePicker
          v-model="availableFromDate"
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
                :value="formatDate(availableFromDate)"
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

      <div class="date-modal-actions">
        <button @click="cancelDateModal" class="btn btn-secondary">
          Anuluj
        </button>
        <button @click="confirmDateAndStatus" class="btn btn-primary">
          Zapisz
        </button>
      </div>
    </div>
  </div>

  <ConfirmDialog
    ref="confirmDialog"
    :title="confirmDialogTitle"
    :message="confirmDialogMessage"
    :type="confirmDialogType"
    confirm-text="Potwierdź"
    cancel-text="Anuluj"
    @confirm="handleConfirmDialog"
  />
  <ToastNotification ref="toast" />

  <!-- Map Modal -->
  <div v-if="showMapModal" class="modal-overlay" @click="closeMapModal">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3>Zaznacz lokalizację na mapie</h3>
        <button type="button" @click="closeMapModal" class="modal-close">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
            <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </button>
      </div>
      <div class="modal-search">
        <div class="modal-search-wrapper">
          <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
            <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
            <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <input
            v-model="modalSearchQuery"
            @input="searchModalLocation"
            type="text"
            placeholder="Wyszukaj miasto, ulicę..."
            class="modal-search-input"
          />
          <button
            v-if="modalSearchQuery"
            type="button"
            @click="modalSearchQuery = ''"
            class="modal-clear-button"
            title="Wyczyść wyszukiwanie"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
              <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
          <div v-if="showModalSearchSuggestions && modalSearchSuggestions.length > 0" class="modal-suggestions">
            <div
              v-for="(suggestion, index) in modalSearchSuggestions"
              :key="index"
              @click="selectModalLocation(suggestion)"
              class="modal-suggestion-item"
            >
              {{ suggestion.displayName || suggestion.display_name }}
            </div>
          </div>
        </div>
      </div>
      <div class="modal-body">
        <div ref="modalMapContainer" class="modal-map"></div>
        <p class="modal-hint">Wyszukaj lokalizację powyżej lub kliknij na mapie / przeciągnij marker</p>
      </div>
      <div class="modal-footer">
        <button type="button" @click="closeMapModal" class="btn-cancel-modal">Anuluj</button>
        <button type="button" @click="confirmModalLocation" class="btn-primary-modal">Potwierdź lokalizację</button>
      </div>
    </div>
  </div>
  </div>
</template>

<style scoped>
.management-page {
  min-height: calc(100vh - 200px);
  background: #f9fafb;
}

.page-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 3rem 0;
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
  display: flex;
  align-items: center;
  gap: 2rem;
  position: relative;
  z-index: 1;
}

.back-button {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-size: 0.95rem;
  backdrop-filter: blur(10px);
}

.back-button:hover {
  background: rgba(255, 255, 255, 0.25);
  border-color: rgba(255, 255, 255, 0.5);
  transform: translateX(-4px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.header-content {
  flex: 1;
}

.header-content h1 {
  margin: 0 0 0.5rem 0;
  font-size: 2.5rem;
  font-weight: 800;
  color: white;
  letter-spacing: -0.5px;
}

.header-subtitle {
  margin: 0;
  color: rgba(255, 255, 255, 0.9);
  font-size: 1.1rem;
  font-weight: 500;
}

.token-info-header {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.1);
  padding: 1rem 1.5rem;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
}

.token-info-header p {
  margin: 0;
  color: rgba(255, 255, 255, 0.95);
  font-size: 0.95rem;
  font-weight: 500;
}

.token-info-header strong {
  color: white;
  font-weight: 700;
}

.page-content {
  padding: 3rem 0;
}

.container {
  max-width: 1400px;
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

.stats-bar {
  display: flex;
  gap: 2rem;
  padding: 2rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
}

.stat {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.stat-label {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}

.stat-value {
  font-size: 2rem;
  font-weight: 800;
  color: #667eea;
}

.listings-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.listing-row {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  overflow: hidden;
  transition: all 0.3s;
}

.listing-row:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.listing-summary {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.5rem;
  cursor: pointer;
  transition: background 0.2s;
}

.listing-summary:hover {
  background: #f9fafb;
}

.listing-thumbnail {
  width: 100px;
  height: 70px;
  border-radius: 8px;
  overflow: hidden;
  flex-shrink: 0;
  background: #f3f4f6;
}

.listing-thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.no-image {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
}

.listing-info {
  flex: 1;
  min-width: 0;
}

.listing-title {
  margin: 0 0 0.5rem 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: #1f2937;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.listing-meta {
  margin: 0;
  color: #6b7280;
  font-size: 0.9rem;
}

.listing-controls {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex-shrink: 0;
}

.status-dropdown {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-select {
  padding: 0.5rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  background: white;
  color: #374151;
}

.status-select.has-pending {
  border-color: #F59E0B;
  background-color: #FFFBEB;
}

.status-select:hover {
  border-color: #667eea;
}

.status-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.status-actions {
  display: flex;
  gap: 0.25rem;
  animation: fadeIn 0.2s ease;
}

.status-btn {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.status-btn.confirm {
  background: #ECFDF5;
  border: 1px solid #10B981;
  color: #10B981;
}

.status-btn.confirm:hover {
  background: #D1FAE5;
  transform: scale(1.1);
}

.status-btn.cancel {
  background: #FEF2F2;
  border: 1px solid #EF4444;
  color: #EF4444;
}

.status-btn.cancel:hover {
  background: #dc2626;
  transform: scale(1.05);
}

/* Date Modal Styles */
.date-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10001;
  backdrop-filter: blur(4px);
}

.date-modal-content {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  max-width: 400px;
  width: 90%;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.date-modal-content h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
}

.date-modal-content p {
  margin: 0 0 1.5rem 0;
  color: #6b7280;
  font-size: 0.95rem;
}

.date-modal-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.5rem;
}

.btn {
  flex: 1;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateX(-10px); }
  to { opacity: 1; transform: translateX(0); }
}

.switch {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  position: relative;
}

.switch input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  width: 48px;
  height: 26px;
  background: #e5e7eb;
  border-radius: 13px;
  position: relative;
  transition: all 0.3s;
}

.slider::before {
  content: '';
  position: absolute;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: white;
  top: 3px;
  left: 3px;
  transition: all 0.3s;
}

.switch input:checked + .slider {
  background: #10B981;
}

.switch input:checked + .slider::before {
  transform: translateX(22px);
}

.switch-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: #374151;
}

.views-counter {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #f3f4f6;
  border-radius: 8px;
  color: #6b7280;
  font-weight: 600;
  font-size: 0.9rem;
}

.preview-btn {
  width: 40px;
  height: 40px;
  border: 2px solid #e5e7eb;
  background: white;
  color: #6b7280;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.preview-btn:hover {
  border-color: #667eea;
  color: #667eea;
  transform: scale(1.1);
}

.delete-btn {
  width: 40px;
  height: 40px;
  border: 2px solid #fecaca;
  background: #fef2f2;
  color: #dc2626;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.delete-btn:hover {
  background: #fee2e2;
  border-color: #fca5a5;
  transform: scale(1.1);
}

.expand-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
  transition: transform 0.3s;
}

.listing-row.expanded .expand-icon {
  transform: rotate(180deg);
}

.listing-details {
  border-top: 2px solid #f3f4f6;
  padding: 2rem;
  background: #f9fafb;
}

.edit-form {
  max-width: 1200px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  font-weight: 600;
  color: #374151;
  font-size: 0.9rem;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: all 0.2s;
  font-family: inherit;
  background-color: white;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
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

.form-group textarea {
  resize: vertical;
  min-height: 100px;
}

.form-section-divider {
  grid-column: 1 / -1;
  margin: 1.5rem 0 1rem 0;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #e5e7eb;
}

.form-section-divider h4 {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: #4f46e5;
}

.checkbox-group {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.checkbox-group label {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  transition: all 0.2s;
}

.checkbox-group label:hover {
  border-color: #667eea;
  background: #f5f3ff;
}

.checkbox-group input[type="checkbox"] {
  width: 20px;
  height: 20px;
  cursor: pointer;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 1.5rem;
  border-top: 2px solid #e5e7eb;
}

.btn-cancel,
.btn-save {
  padding: 0.875rem 1.75rem;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-cancel {
  background: white;
  border: 2px solid #e5e7eb;
  color: #6b7280;
}

.btn-cancel:hover {
  border-color: #9ca3af;
  background: #f3f4f6;
}

.btn-save {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
}

.btn-save:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.btn-save:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.spinner-icon {
  animation: spin 1s linear infinite;
  height: 20px;
  width: 20px;
}

.opacity-25 {
  opacity: 0.25;
}

.opacity-75 {
  opacity: 0.75;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 1200px) {
  .listing-controls {
    flex-wrap: wrap;
  }
}

@media (max-width: 768px) {
  .page-header {
    padding: 2rem 0;
  }

  .page-header .container {
    flex-direction: column;
    align-items: flex-start;
    gap: 1.5rem;
  }

  .header-content h1 {
    font-size: 1.75rem;
  }

  .token-info-header {
    width: 100%;
  }

  .listing-summary {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
  }

  .listing-thumbnail {
    width: 100%;
    height: 200px;
    border-radius: 12px;
  }

  .listing-info {
    width: 100%;
  }

  .listing-title {
    font-size: 1.2rem;
    margin-bottom: 0.75rem;
  }

  .listing-meta {
    font-size: 0.95rem;
    margin-bottom: 1rem;
  }

  .listing-controls {
    width: 100%;
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.5rem;
    justify-content: flex-start;
  }

  .status-dropdown {
    width: 100%;
    display: flex;
    grid-column: 1 / -1;
  }

  .status-select {
    width: 100%;
    padding: 0.5rem 0.75rem;
    font-size: 0.85rem;
  }

  .switch {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
  }

  .views-counter {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: #f3f4f6;
    border-radius: 8px;
    font-size: 0.85rem;
    white-space: nowrap;
  }

  .preview-btn,
  .delete-btn {
    width: auto;
    height: auto;
    padding: 0.5rem 0.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    font-size: 0.8rem;
  }

  .expand-icon {
    width: auto;
    height: auto;
    padding: 0.5rem;
    display: none;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .checkbox-group {
    grid-template-columns: 1fr;
  }

  .stats-bar {
    flex-direction: column;
    gap: 1rem;
  }
}

@media (max-width: 480px) {
  .listing-row {
    border-radius: 12px;
    margin-bottom: 0.5rem;
  }

  .listing-summary {
    padding: 0.75rem;
    gap: 0.75rem;
    border-radius: 12px;
  }

  .listing-thumbnail {
    height: 160px;
    border-radius: 10px;
  }

  .listing-title {
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
  }

  .listing-meta {
    font-size: 0.85rem;
    margin-bottom: 0.75rem;
  }

  .listing-controls {
    gap: 0.35rem;
    grid-template-columns: 1fr;
  }

  .status-dropdown {
    width: 100%;
    grid-column: 1 / -1;
  }

  .status-select {
    width: 100%;
    padding: 0.4rem 0.5rem;
    font-size: 0.75rem;
  }

  .switch {
    padding: 0.35rem 0.5rem;
    gap: 0.35rem;
    flex: 0 1 auto;
  }

  .slider {
    width: 36px;
    height: 20px;
  }

  .slider::before {
    width: 14px;
    height: 14px;
  }

  .switch input:checked + .slider::before {
    transform: translateX(16px);
  }

  .switch-label {
    font-size: 0.65rem;
    display: none;
  }

  .views-counter {
    font-size: 0.65rem;
    padding: 0.35rem 0.5rem;
    flex: 0 1 auto;
  }

  .views-counter svg {
    width: 12px;
    height: 12px;
  }

  .preview-btn,
  .delete-btn {
    padding: 0.35rem 0.5rem;
    font-size: 0.65rem;
    width: auto;
    flex: 0 1 auto;
  }

  .preview-btn svg,
  .delete-btn svg {
    width: 14px;
    height: 14px;
  }

  .expand-icon {
    padding: 0.35rem;
    width: auto;
    display: inline-flex;
  }

  .expand-icon svg {
    width: 16px;
    height: 16px;
  }

  .listing-details {
    padding: 1rem;
    border-radius: 0 0 12px 12px;
  }
}

.images-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 1rem;
  margin-top: 0.5rem;
  padding: 1rem;
  border: 2px dashed transparent;
  border-radius: 12px;
  transition: all 0.2s;
}

.images-grid.dragging {
  border-color: #667eea;
  background: #f5f3ff;
}

.image-item {
  position: relative;
  aspect-ratio: 1;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid #e5e7eb;
  background: white;
  cursor: grab;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.image-item.drag-over {
  transform: scale(1.05);
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.5);
  z-index: 10;
  border-color: #667eea;
}

.image-item.dragging {
  opacity: 0.4;
  transform: scale(0.95);
  border: 2px dashed #9ca3af;
  filter: grayscale(0.5);
}

.image-item:active {
  cursor: grabbing;
}

.image-item:hover {
  transform: scale(1.02);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.image-item.new {
  border-color: #667eea;
  border-style: dashed;
}

.image-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  pointer-events: none;
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
  z-index: 2;
}

.remove-btn:hover {
  background: #fee2e2;
  transform: scale(1.1);
}

.main-badge {
  position: absolute;
  bottom: 4px;
  left: 4px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  z-index: 2;
  pointer-events: none;
}

.upload-btn-wrapper {
  aspect-ratio: 1;
}

.upload-btn {
  width: 100%;
  height: 100%;
  border: 2px dashed #d1d5db;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.2s;
  background: #f9fafb;
}

.upload-btn:hover {
  border-color: #10B981;
  background: #f0fdf4;
  color: #10B981;
}

.upload-btn svg path {
  transition: stroke 0.2s;
}

.upload-btn:hover svg path {
  stroke: #10B981;
}

.upload-text {
  font-size: 0.8rem;
  font-weight: 600;
  margin-top: 0.25rem;
}

.help-text {
  font-size: 0.85rem;
  color: #6b7280;
  margin-top: 0.5rem;
  text-align: center;
}

/* Tabs Navigation */
.tabs-navigation {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  border-bottom: 2px solid #e5e7eb;
  padding-bottom: 0;
}

.tab-button {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  color: #6b7280;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.3s;
  position: relative;
  bottom: -2px;
}

.tab-button:hover {
  color: #667eea;
}

.tab-button.active {
  color: #667eea;
  border-bottom-color: #667eea;
}

/* Statistics Dashboard */
.statistics-dashboard {
  animation: fadeIn 0.3s ease-out;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  transition: all 0.3s;
  border: 1px solid #e5e7eb;
}

.stat-card:hover {
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: white;
  font-weight: 600;
}

.stat-icon.views {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon.phone {
  background: linear-gradient(135deg, #10B981 0%, #059669 100%);
}

.stat-icon.email {
  background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
}

.stat-icon.engagement {
  background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.stat-value {
  font-size: 2rem;
  font-weight: 800;
  color: #1f2937;
  line-height: 1;
  margin-bottom: 0.25rem;
}

.stat-sublabel {
  font-size: 0.75rem;
  color: #9ca3af;
  margin-top: 0.5rem;
}

/* Charts Section */
.charts-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 2rem;
  margin-bottom: 2rem;
}

.chart-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
}

.chart-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  gap: 1rem;
}

.chart-card h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
}

.chart-quick-add-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 8px;
  color: white;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
}

.chart-quick-add-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.chart-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.chart-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  transition: all 0.2s;
}

.chart-item:hover {
  background: #f3f4f6;
}

.chart-item-rank {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.chart-item-info {
  flex: 1;
  min-width: 0;
}

.chart-item-title {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.95rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chart-item-meta {
  font-size: 0.8rem;
  color: #9ca3af;
  margin-top: 0.25rem;
}

.chart-item-value {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 700;
  color: #667eea;
  font-size: 0.95rem;
  flex-shrink: 0;
}

.chart-item-bar {
  width: 100px;
  height: 6px;
  background: #e5e7eb;
  border-radius: 3px;
  overflow: hidden;
  flex-shrink: 0;
}

.chart-item-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  border-radius: 3px;
  transition: width 0.3s ease;
}

.chart-item-bar-fill.engagement {
  background: linear-gradient(90deg, #EF4444 0%, #DC2626 100%);
}

.chart-item-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 6px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}

.chart-item-btn:hover:not(:disabled) {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.chart-item-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
}

.chart-empty {
  padding: 2rem;
  text-align: center;
  color: #9ca3af;
  font-size: 0.95rem;
}

/* Engagement Chart Container */
:deep(.engagement-chart-container) {
  margin-bottom: 3rem;
}

/* Additional Stats */
.additional-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.stat-breakdown-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
}

.stat-breakdown-card h3 {
  margin: 0 0 1.5rem 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
}

.breakdown-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.breakdown-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem 0;
}

.breakdown-label {
  flex: 1;
  font-weight: 500;
  color: #374151;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.status-dot.active {
  background: #10B981;
}

.status-dot.reserved {
  background: #F59E0B;
}

.status-dot.soon {
  background: #667eea;
}

.breakdown-value {
  font-weight: 700;
  color: #667eea;
  font-size: 1.1rem;
  min-width: 40px;
  text-align: right;
}

.breakdown-bar {
  width: 80px;
  height: 6px;
  background: #e5e7eb;
  border-radius: 3px;
  overflow: hidden;
  flex-shrink: 0;
}

.breakdown-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  border-radius: 3px;
  transition: width 0.3s ease;
}

@media (max-width: 1024px) {
  .charts-section {
    grid-template-columns: 1fr;
  }

  .additional-stats {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .tabs-navigation {
    gap: 0.5rem;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .tab-button {
    padding: 0.75rem 1rem;
    font-size: 0.85rem;
    white-space: nowrap;
  }

  .chart-card,
  .stat-breakdown-card {
    padding: 1.5rem;
  }

  .chart-item {
    flex-wrap: wrap;
  }

  .chart-item-bar {
    width: 60px;
  }
}
</style>

<style scoped>
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

/* Style dla formularza email */
.content-card {
  background: white;
  padding: 3rem;
  border-radius: 24px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  text-align: center;
  max-width: 500px;
  margin: 0 auto;
}

.card-body {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.icon-wrapper {
  margin-bottom: 2rem;
  display: flex;
  justify-content: center;
  animation: slideDown 0.5s ease-out;
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

.content-card h1 {
  margin: 0 0 1rem 0;
  font-size: 2rem;
  font-weight: 800;
  color: #1f2937;
}

.description {
  color: #6b7280;
  font-size: 1rem;
  margin-bottom: 2rem;
  line-height: 1.6;
}

.email-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-wrapper svg {
  position: absolute;
  left: 1rem;
  pointer-events: none;
}

.email-input {
  width: 100%;
  padding: 1rem 1rem 1rem 3rem;
  border: 2px solid #E5E7EB;
  border-radius: 12px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.email-input:focus {
  outline: none;
  border-color: #4F46E5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.error-message {
  color: #EF4444;
  font-size: 0.875rem;
  margin: 0.5rem 0;
  padding: 0.5rem;
  background-color: #FEF2F2;
  border-radius: 6px;
  border-left: 3px solid #EF4444;
  text-align: left;
}

.submit-btn {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.submit-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
}

.submit-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.loading {
  display: inline-block;
  animation: pulse 1.5s ease-in-out infinite;
}

.success-body {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.success-icon {
  margin-bottom: 1.5rem;
  animation: scaleIn 0.5s ease-out;
}

.success-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1F2937;
  margin: 0 0 1rem 0;
}

.success-description {
  font-size: 1rem;
  color: #6B7280;
  line-height: 1.6;
  margin: 0;
}

.info-text {
  margin-top: 1rem;
  font-size: 0.875rem;
  color: #9CA3AF;
}

.redirect-info {
  display: block;
  margin-top: 1rem;
  font-size: 0.9rem;
  color: #6B7280;
  font-style: italic;
}

.image-loader {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f3f4f6;
  border-radius: 0.5rem;
}

.spinner-small {
  width: 24px;
  height: 24px;
  border: 3px solid #e5e7eb;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

@keyframes scaleIn {
  0% { transform: scale(0); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

.location-input-group {
  display: flex;
  gap: 0.5rem;
  align-items: stretch;
}

.location-input-group .address-input-wrapper {
  flex: 1;
  position: relative;
}

.location-input {
  width: 100%;
  padding-right: 2.5rem !important;
}

.input-spinner {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
}

.clear-location-btn {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  padding: 4px;
  cursor: pointer;
  color: #9ca3af;
  transition: all 0.2s;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.clear-location-btn:hover {
  background: #f3f4f6;
  color: #ef4444;
}

.address-suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  max-height: 200px;
  overflow-y: auto;
  z-index: 1000;
  margin-top: 4px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.suggestion-item {
  padding: 10px 12px;
  cursor: pointer;
  border-bottom: 1px solid #f3f4f6;
  transition: background 0.15s;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.suggestion-item:last-child {
  border-bottom: none;
}

.suggestion-item:hover {
  background: #f9fafb;
}

.suggestion-name {
  font-weight: 500;
  color: #1f2937;
}

.suggestion-type {
  font-size: 12px;
  color: #6b7280;
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
}

.map-button-modern:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
}

.map-button-modern svg {
  flex-shrink: 0;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  padding: 1rem;
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-content {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 900px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  animation: slideUp 0.3s ease-out;
  overflow: hidden;
  padding: 0 !important;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px 16px 0 0;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: white;
}

.modal-close {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  padding: 0.5rem;
  cursor: pointer;
  color: white;
  transition: all 0.2s;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-close:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(90deg);
}

.modal-search {
  padding: 1.5rem 2rem;
  background: white;
  border-bottom: 1px solid #e5e7eb;
}

.modal-search-wrapper {
  position: relative;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  pointer-events: none;
}

.modal-search-input {
  width: 100%;
  padding: 0.875rem 1rem 0.875rem 3rem;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 0.95rem;
  transition: all 0.2s;
  outline: none;
}

.modal-search-input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.modal-clear-button {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: #9ca3af;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.15s;
}

.modal-clear-button:hover {
  color: #6b7280;
}

.modal-suggestions {
  position: absolute;
  top: calc(100% + 0.5rem);
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  max-height: 300px;
  overflow-y: auto;
  z-index: 1000;
}

.modal-suggestion-item {
  padding: 0.875rem 1rem;
  cursor: pointer;
  transition: background 0.15s;
  color: #374151;
  border-bottom: 1px solid #f3f4f6;
}

.modal-suggestion-item:last-child {
  border-bottom: none;
}

.modal-suggestion-item:hover {
  background: #f9fafb;
  color: #667eea;
}

.modal-body {
  padding: 0 !important;
  flex: 1;
  overflow: auto;
}

.modal-map {
  width: 100%;
  height: 500px;
  border-radius: 0;
  overflow: hidden;
}

:deep(.leaflet-control-attribution) {
  display: none !important;
}

.modal-hint {
  padding: 1rem 2rem;
  text-align: center;
  color: #6b7280;
  font-size: 0.875rem;
  font-weight: 500;
  background: #fafafa;
  margin: 0;
}

.modal-footer {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  padding: 1.5rem 2rem;
  border-top: 1px solid #f3f4f6;
  background: #fafafa;
  border-radius: 0 0 16px 16px;
}

.btn-cancel-modal {
  padding: 0.75rem 1.5rem;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  color: #6b7280;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cancel-modal:hover {
  background: #f9fafb;
  border-color: #d1d5db;
  transform: translateY(-1px);
}

.btn-primary-modal {
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 8px;
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.25);
}

.btn-primary-modal:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
}

@media (max-width: 768px) {
  .location-input-group {
    flex-direction: column;
  }

  .map-button {
    width: 100%;
    justify-content: center;
  }

  .map-button-modern {
    width: 100%;
    justify-content: center;
    padding: 0.75rem 1rem;
    font-size: 0.8125rem;
  }

  .map-button-modern span {
    display: none;
  }

  .modal-map {
    height: 400px;
  }
}
@media (max-width: 640px) {
  .listing-controls {
    display: flex;
    flex-wrap: wrap;
    width: 100%;
    gap: 10px;
  }

  .listing-controls .status-dropdown {
    width: 100%;
  }

  .listing-controls > :not(.status-dropdown) {
    display: flex;
    align-items: center;
  }

  .views-counter {
    margin-left: auto;
  }
}

</style>
