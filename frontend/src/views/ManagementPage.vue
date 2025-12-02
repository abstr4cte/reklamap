<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { supabase } from '../lib/supabase'
import type { Advertisement } from '../lib/supabase'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import ToastNotification from '../components/ToastNotification.vue'

const router = useRouter()
const advertisements = ref<Advertisement[]>([])
const isLoading = ref(true)
const expandedRows = ref<Set<string>>(new Set())
const editingAd = ref<Advertisement | null>(null)
const confirmDialog = ref<InstanceType<typeof ConfirmDialog> | null>(null)
const toast = ref<InstanceType<typeof ToastNotification> | null>(null)
const adToDelete = ref<string>('')
const pendingStatusChanges = ref<Record<string, string>>({})
const showDateModal = ref(false)
const pendingAdId = ref<string>('')
const availableFromDate = ref('')
const selectedImageFile = ref<File | null>(null) // Deprecated
const newImageFiles = ref<{ file: File, preview: string }[]>([]) // Deprecated but kept for type safety if needed
const unifiedImages = ref<{ type: 'existing' | 'new', url?: string, file?: File, preview?: string, id: string }[]>([])
const isDragging = ref(false)
const draggedImageIndex = ref<number | null>(null)
const draggedImageType = ref<'existing' | 'new' | null>(null)
const dragOverTarget = ref<{ index: number, type: 'existing' | 'new' } | null>(null)
const isSaving = ref(false)



const loadAdvertisements = async () => {
  try {
    isLoading.value = true
    const { data, error } = await supabase
      .from('advertisements')
      .select('*')
      .order('created_at', { ascending: false })

    if (error) throw error
    advertisements.value = data || []
  } catch (error) {
    console.error('Error loading advertisements:', error)
  } finally {
    isLoading.value = false
  }
}

const toggleRow = (id: string) => {
  if (expandedRows.value.has(id)) {
    expandedRows.value.delete(id)
    editingAd.value = null
    unifiedImages.value = []
    isDragging.value = false
  } else {
    expandedRows.value.add(id)
    const ad = advertisements.value.find(a => a.id === id)
    if (ad) {
      const images = ad.images || []
      
      // Parse phone number - always use +48 prefix
      let phoneNumber = ad.phone || ''
      
      if (ad.phone) {
        // Remove any country code prefix if present
        const phoneMatch = ad.phone.match(/^(?:\+\d+\s+)?(.+)$/)
        if (phoneMatch) {
          phoneNumber = phoneMatch[1]
        }
      }
      
      editingAd.value = { 
        ...ad, 
        images,
        phone: phoneNumber,
        contact_preference: ad.contact_preference || 'email' as any
      }
      // Initialize unified images
      unifiedImages.value = images.map((url, index) => ({
        type: 'existing',
        url,
        id: `existing-${index}-${Date.now()}`
      }))
    }
  }
}

const getTotalImagesCount = () => {
  return unifiedImages.value.length
}

const handleImageSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = target.files
  processFiles(files)
  target.value = ''
}

const handleDrop = (event: DragEvent) => {
  isDragging.value = false
  const files = event.dataTransfer?.files
  processFiles(files)
}

const processFiles = (files: FileList | null | undefined) => {
  if (!files) return

  const currentCount = getTotalImagesCount()
  const remainingSlots = 5 - currentCount

  if (remainingSlots <= 0) {
    toast.value?.add('Osiągnięto limit 5 zdjęć', 'error')
    return
  }

  if (files.length > remainingSlots) {
    toast.value?.add(`Możesz dodać jeszcze tylko ${remainingSlots} zdjęć`, 'info')
  }

  const filesToProcess = Array.from(files).slice(0, remainingSlots)

  for (const file of filesToProcess) {
    if (file.size > 5 * 1024 * 1024) {
      toast.value?.add(`Plik ${file.name} jest za duży (max 5MB)`, 'error')
      continue
    }

    if (!file.type.startsWith('image/')) {
      toast.value?.add(`Plik ${file.name} nie jest obrazem`, 'error')
      continue
    }

    const reader = new FileReader()
    reader.onload = (e) => {
      unifiedImages.value.push({
        type: 'new',
        file,
        preview: e.target?.result as string,
        id: `new-${Date.now()}-${Math.random()}`
      })
    }
    reader.readAsDataURL(file)
  }
}

// Drag and drop reordering functions
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
    
    if (dragOverTarget.value?.index !== index) {
      dragOverTarget.value = { index, type: 'existing' } // type doesn't matter much now
    }
  }
}

const handleDragEnd = () => {
  draggedImageIndex.value = null
  draggedImageType.value = null
  dragOverTarget.value = null
  isDragging.value = false
}

const handleImageDrop = (event: DragEvent, targetIndex: number) => {
  event.preventDefault()
  event.stopPropagation()
  
  dragOverTarget.value = null
  
  if (draggedImageIndex.value === null) return

  const sourceIndex = draggedImageIndex.value

  // Don't do anything if dropping on itself
  if (sourceIndex === targetIndex) {
    draggedImageIndex.value = null
    return
  }

  // Reorder unifiedImages
  const items = [...unifiedImages.value]
  const [movedItem] = items.splice(sourceIndex, 1)
  items.splice(targetIndex, 0, movedItem)
  
  unifiedImages.value = items
  draggedImageIndex.value = null
}

const removeImage = (index: number) => {
  unifiedImages.value.splice(index, 1)
}

// Keep these for backward compatibility if referenced elsewhere, but they are not used in new logic
const removeExistingImage = (index: number) => {}
const removeNewImage = (index: number) => {}

const uploadImage = async (file: File): Promise<string> => {
  const fileExt = file.name.split('.').pop()
  const fileName = `${Math.random().toString(36).substring(2)}.${fileExt}`
  const filePath = `advertisements/${fileName}`

  const { error: uploadError } = await supabase.storage
    .from('images')
    .upload(filePath, file)

  if (uploadError) {
    console.error('Error uploading image:', uploadError)
    throw uploadError
  }

  const { data } = supabase.storage
    .from('images')
    .getPublicUrl(filePath)

  return data.publicUrl
}

const handleStatusChange = (id: string, newStatus: string) => {
  const ad = advertisements.value.find(a => a.id === id)
  if (ad && ad.status === newStatus) {
    const newPending = { ...pendingStatusChanges.value }
    delete newPending[id]
    pendingStatusChanges.value = newPending
    return
  }
  pendingStatusChanges.value = { ...pendingStatusChanges.value, [id]: newStatus }
}

const confirmStatusChange = async (id: string) => {
  const newStatus = pendingStatusChanges.value[id]
  if (!newStatus) return

  // If status is soon_available, show date modal
  if (newStatus === 'soon_available') {
    pendingAdId.value = id
    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    availableFromDate.value = tomorrow.toISOString().split('T')[0]
    showDateModal.value = true
    return
  }

  // Otherwise update directly
  await updateStatus(id, newStatus)

  const newPending = { ...pendingStatusChanges.value }
  delete newPending[id]
  pendingStatusChanges.value = newPending
}

const confirmDateAndStatus = async () => {
  if (!pendingAdId.value) return
  
  await updateStatus(pendingAdId.value, 'soon_available', availableFromDate.value)
  
  const newPending = { ...pendingStatusChanges.value }
  delete newPending[pendingAdId.value]
  pendingStatusChanges.value = newPending
  
  showDateModal.value = false
  pendingAdId.value = ''
  availableFromDate.value = ''
}

const cancelDateModal = () => {
  showDateModal.value = false
  pendingAdId.value = ''
  availableFromDate.value = ''
}

const cancelStatusChange = (id: string) => {
  const newPending = { ...pendingStatusChanges.value }
  delete newPending[id]
  pendingStatusChanges.value = newPending
}

const updateStatus = async (id: string, newStatus: string, availableFrom?: string) => {
  try {
    const updateData: any = { status: newStatus }
    if (newStatus === 'soon_available' && availableFrom) {
      updateData.available_from = availableFrom
    }
    
    const { error } = await supabase
      .from('advertisements')
      .update(updateData)
      .eq('id', id)

    if (error) throw error

    const ad = advertisements.value.find(a => a.id === id)
    if (ad) {
      ad.status = newStatus
      if (availableFrom) {
        ad.available_from = availableFrom
      }
    }
    toast.value?.add('Status został zaktualizowany', 'success')
  } catch (error: any) {
    console.error('Error updating status:', error)
    toast.value?.add(`Błąd podczas aktualizacji statusu: ${error.message || error}`, 'error')
  }
}

const toggleActive = async (id: string) => {
  try {
    const ad = advertisements.value.find(a => a.id === id)
    if (!ad) return

    const newActiveState = !ad.is_active

    const { error } = await supabase
      .from('advertisements')
      .update({ is_active: newActiveState })
      .eq('id', id)

    if (error) throw error

    ad.is_active = newActiveState
    toast.value?.add(newActiveState ? 'Ogłoszenie zostało aktywowane' : 'Ogłoszenie zostało dezaktywowane', 'success')
  } catch (error) {
    console.error('Error toggling active state:', error)
    toast.value?.add('Błąd podczas zmiany stanu aktywności', 'error')
  }
}

const saveChanges = async (id: string) => {
  if (!editingAd.value || isSaving.value) return

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

    const { error } = await supabase
      .from('advertisements')
      .update({
        title: editingAd.value.title,
        description: editingAd.value.description,
        price: editingAd.value.price,
        price_unit: editingAd.value.price_unit,
        price_negotiable: editingAd.value.price_negotiable,
        location: editingAd.value.location,
        city: editingAd.value.city,
        region: editingAd.value.region,
        type: editingAd.value.type,
        width: editingAd.value.width,
        height: editingAd.value.height,
        orientation: editingAd.value.orientation,
        traffic_intensity: editingAd.value.traffic_intensity,
        has_lighting: editingAd.value.has_lighting,
        price_includes_print: editingAd.value.price_includes_print,
        graphic_design_help: editingAd.value.graphic_design_help,
        offer_type: editingAd.value.offer_type,
        has_vat_invoice: editingAd.value.has_vat_invoice,
        status: editingAd.value.status,
        images: finalImageUrls,
        image_url: mainImageUrl,
        has_image: finalImageUrls.length > 0,
        phone: (editingAd.value as any).phone ? `+48 ${(editingAd.value as any).phone}` : '',
        contact_preference: (editingAd.value as any).contact_preference || 'email',
      })
      .eq('id', id)

    if (error) throw error

    const ad = advertisements.value.find(a => a.id === id)
    if (ad && editingAd.value) {
      Object.assign(ad, editingAd.value)
      ad.images = finalImageUrls
      ad.image_url = mainImageUrl
      ad.has_image = finalImageUrls.length > 0
      ad.phone = (editingAd.value as any).phone ? `+48 ${(editingAd.value as any).phone}` : ''
      ad.contact_preference = (editingAd.value as any).contact_preference || 'email'
    }

    toggleRow(id)
    nextTick(() => {
      const row = document.getElementById(`ad-row-${id}`)
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
    const { error } = await supabase
      .from('advertisements')
      .delete()
      .eq('id', adToDelete.value)

    if (error) throw error

    advertisements.value = advertisements.value.filter(a => a.id !== adToDelete.value)
    expandedRows.value.delete(adToDelete.value)
    adToDelete.value = ''
    toast.value?.add('Ogłoszenie zostało usunięte', 'success')
  } catch (error) {
    console.error('Error deleting advertisement:', error)
    toast.value?.add('Błąd podczas usuwania ogłoszenia', 'error')
  }
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    active: 'Wolne',
    reserved: 'Zarezerwowane',
    soon_available: 'Wkrótce dostępne'
  }
  return labels[status] || status
}

const getTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    billboard: 'Billboard',
    citylight: 'Citylight',
    led_screen: 'Ekran LED',
    digital: 'Digital',
    banner: 'Banner',
    poster: 'Plakat'
  }
  return labels[type] || type
}

const openPreview = (id: string) => {
  const { href } = router.resolve({ path: `/ogloszenie/${id}` })
  window.open(href, '_blank')
}

onMounted(() => {
  loadAdvertisements()
})
</script>

<template>
  <div class="management-page">
    <div class="page-header">
      <div class="container">
        <button @click="router.push('/')" class="back-button">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M15 10H5M5 10L10 15M5 10L10 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Powrót
        </button>
        <div class="header-content">
          <h1>Panel zarządzania ogłoszeniami</h1>
          <p class="header-subtitle">Zarządzaj swoimi ogłoszeniami w jednym miejscu</p>
        </div>
      </div>
    </div>

    <div class="page-content">
      <div class="container">
        <div v-if="isLoading" class="loading-state">
          <div class="spinner"></div>
          <p>Ładowanie ogłoszeń...</p>
        </div>

        <div v-else-if="advertisements.length === 0" class="empty-state">
          <svg width="120" height="120" viewBox="0 0 24 24" fill="none">
            <rect x="3" y="3" width="18" height="18" rx="2" stroke="#d1d5db" stroke-width="2"/>
            <path d="M3 9h18M9 3v18" stroke="#d1d5db" stroke-width="2"/>
          </svg>
          <h2>Brak ogłoszeń</h2>
          <p>Nie masz jeszcze żadnych ogłoszeń do zarządzania</p>
          <button @click="router.push('/dodaj-ogloszenie')" class="btn-primary">
            Dodaj pierwsze ogłoszenie
          </button>
        </div>

        <div v-else class="ads-list">
          <div class="stats-bar">
            <div class="stat">
              <span class="stat-label">Wszystkie ogłoszenia</span>
              <span class="stat-value">{{ advertisements.length }}</span>
            </div>
            <div class="stat">
              <span class="stat-label">Aktywne</span>
              <span class="stat-value">{{ advertisements.filter(ad => ad.is_active).length }}</span>
            </div>
            <div class="stat">
              <span class="stat-label">Nieaktywne</span>
              <span class="stat-value">{{ advertisements.filter(ad => !ad.is_active).length }}</span>
            </div>
          </div>

          <div v-for="ad in advertisements" :key="ad.id" :id="'ad-row-' + ad.id" class="ad-row" :class="{ expanded: expandedRows.has(ad.id) }">
            <div class="ad-summary" @click="toggleRow(ad.id)">
              <div class="ad-thumbnail">
                <img v-if="ad.image_url" :src="ad.image_url" :alt="ad.title" />
                <div v-else class="no-image">
                  <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
                    <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                    <path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2"/>
                  </svg>
                </div>
              </div>

              <div class="ad-info">
                <h3 class="ad-title">{{ ad.title }}</h3>
                <p class="ad-meta">{{ ad.city }} • {{ getTypeLabel(ad.type) }} • {{ ad.width }}m × {{ ad.height }}m</p>
              </div>

              <div class="ad-controls" @click.stop>
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

            <div v-if="expandedRows.has(ad.id) && editingAd" class="ad-details">
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
                        @dragover.prevent="handleImageDragOver($event, index)"
                        @dragend="handleDragEnd"
                        @drop.prevent="handleImageDrop($event, index)"
                      >
                        <img :src="img.type === 'existing' ? img.url : img.preview" alt="Zdjęcie" />
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

                  <div class="form-group">
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
                      <option value="day">za dzień</option>
                      <option value="week">za tydzień</option>
                      <option value="month">za miesiąc</option>
                      <option value="year">za rok</option>
                    </select>
                  </div>

                  <div class="form-group checkbox-group full-width">
                    <label>
                      <input v-model="editingAd.price_negotiable" type="checkbox" />
                      <span>Cena do negocjacji</span>
                    </label>
                  </div>

                  <div class="form-group">
                    <label>Miasto</label>
                    <input v-model="editingAd.city" type="text" required />
                  </div>

                  <div class="form-group">
                    <label>Lokalizacja</label>
                    <input v-model="editingAd.location" type="text" required />
                  </div>

                  <div class="form-group">
                    <label>Województwo</label>
                    <input v-model="editingAd.region" type="text" required />
                  </div>

                  <div class="form-group">
                    <label>Typ powierzchni</label>
                    <select v-model="editingAd.type" required>
                      <option value="billboard">Billboard</option>
                      <option value="citylight">Citylight</option>
                      <option value="led_screen">Ekran LED</option>
                      <option value="digital">Digital</option>
                      <option value="banner">Banner</option>
                      <option value="poster">Plakat</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Szerokość (m)</label>
                    <input v-model.number="editingAd.width" type="number" step="0.1" required />
                  </div>

                  <div class="form-group">
                    <label>Wysokość (m)</label>
                    <input v-model.number="editingAd.height" type="number" step="0.1" required />
                  </div>

                  <div class="form-group">
                    <label>Orientacja</label>
                    <select v-model="editingAd.orientation" required>
                      <option value="horizontal">Poziom</option>
                      <option value="vertical">Pion</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Natężenie ruchu</label>
                    <select v-model="editingAd.traffic_intensity" required>
                      <option value="low">Niskie</option>
                      <option value="medium">Średnie</option>
                      <option value="high">Wysokie</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Rodzaj oferty</label>
                    <select v-model="editingAd.offer_type" required>
                      <option value="owner">Właściciel</option>
                      <option value="agency">Agencja</option>
                    </select>
                  </div>

                  <div class="form-group checkbox-group full-width">
                    <label>
                      <input v-model="editingAd.has_lighting" type="checkbox" />
                      <span>Podświetlenie</span>
                    </label>
                    <label>
                      <input v-model="editingAd.price_includes_print" type="checkbox" />
                      <span>Druk i montaż w cenie</span>
                    </label>
                    <label>
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
    </div>
  </div>

  <!-- Date Modal for Soon Available Status -->
  <div v-if="showDateModal" class="modal-overlay" @click="cancelDateModal">
    <div class="modal-content" @click.stop>
      <h3>Data dostępności</h3>
      <p>Wybierz datę, od kiedy powierzchnia będzie dostępna:</p>
      
      <div class="form-group">
        <label class="form-label">Data dostępności</label>
        <input
          v-model="availableFromDate"
          type="date"
          class="form-input"
        />
      </div>

      <div class="modal-actions">
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
    title="Usuń ogłoszenie"
    message="Czy na pewno chcesz usunąć to ogłoszenie? Tej operacji nie można cofnąć."
    type="danger"
    confirm-text="Usuń"
    cancel-text="Anuluj"
    @confirm="handleConfirmDelete"
  />
  <ToastNotification ref="toast" />
</template>

<style scoped>
.management-page {
  min-height: calc(100vh - 200px);
  background: #f9fafb;
}

.page-header {
  background: white;
  border-bottom: 2px solid #e5e7eb;
  padding: 2rem 0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.page-header .container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  align-items: center;
  gap: 2rem;
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
  font-size: 0.95rem;
}

.back-button:hover {
  border-color: #667eea;
  color: #667eea;
  transform: translateX(-4px);
}

.header-content {
  flex: 1;
}

.header-content h1 {
  margin: 0 0 0.5rem 0;
  font-size: 2rem;
  font-weight: 800;
  color: #1f2937;
}

.header-subtitle {
  margin: 0;
  color: #6b7280;
  font-size: 1rem;
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

.ads-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.ad-row {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  overflow: hidden;
  transition: all 0.3s;
}

.ad-row:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.ad-summary {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.5rem;
  cursor: pointer;
  transition: background 0.2s;
}

.ad-summary:hover {
  background: #f9fafb;
}

.ad-thumbnail {
  width: 100px;
  height: 70px;
  border-radius: 8px;
  overflow: hidden;
  flex-shrink: 0;
  background: #f3f4f6;
}

.ad-thumbnail img {
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

.ad-info {
  flex: 1;
  min-width: 0;
}

.ad-title {
  margin: 0 0 0.5rem 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: #1f2937;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ad-meta {
  margin: 0;
  color: #6b7280;
  font-size: 0.9rem;
}

.ad-controls {
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
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.modal-content {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  max-width: 400px;
  width: 90%;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-content h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
}

.modal-content p {
  margin: 0 0 1.5rem 0;
  color: #6b7280;
  font-size: 0.95rem;
}

.modal-actions {
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

.ad-row.expanded .expand-icon {
  transform: rotate(180deg);
}

.ad-details {
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
  .ad-controls {
    flex-wrap: wrap;
  }
}

@media (max-width: 768px) {
  .page-header .container {
    flex-direction: column;
    align-items: flex-start;
  }

  .header-content h1 {
    font-size: 1.5rem;
  }

  .ad-summary {
    flex-wrap: wrap;
  }

  .ad-controls {
    width: 100%;
    justify-content: space-between;
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
</style>
