<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { supabase } from '../lib/supabase'
import type { Advertisement } from '../lib/supabase'

const router = useRouter()
const advertisements = ref<Advertisement[]>([])
const isLoading = ref(true)
const expandedRows = ref<Set<string>>(new Set())
const editingAd = ref<Advertisement | null>(null)

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
  } else {
    expandedRows.value.add(id)
    const ad = advertisements.value.find(a => a.id === id)
    if (ad) {
      editingAd.value = { ...ad }
    }
  }
}

const updateStatus = async (id: string, newStatus: string) => {
  try {
    const { error } = await supabase
      .from('advertisements')
      .update({ status: newStatus })
      .eq('id', id)

    if (error) throw error

    const ad = advertisements.value.find(a => a.id === id)
    if (ad) ad.status = newStatus
  } catch (error) {
    console.error('Error updating status:', error)
    alert('Błąd podczas aktualizacji statusu')
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
  } catch (error) {
    console.error('Error toggling active state:', error)
    alert('Błąd podczas zmiany stanu aktywności')
  }
}

const saveChanges = async (id: string) => {
  if (!editingAd.value) return

  try {
    const { error } = await supabase
      .from('advertisements')
      .update({
        title: editingAd.value.title,
        description: editingAd.value.description,
        price: editingAd.value.price,
        price_unit: editingAd.value.price_unit,
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
        status: editingAd.value.status
      })
      .eq('id', id)

    if (error) throw error

    const ad = advertisements.value.find(a => a.id === id)
    if (ad && editingAd.value) {
      Object.assign(ad, editingAd.value)
    }

    toggleRow(id)
    alert('Zmiany zostały zapisane')
  } catch (error) {
    console.error('Error saving changes:', error)
    alert('Błąd podczas zapisywania zmian')
  }
}

const deleteAd = async (id: string) => {
  if (!confirm('Czy na pewno chcesz usunąć to ogłoszenie?')) return

  try {
    const { error } = await supabase
      .from('advertisements')
      .delete()
      .eq('id', id)

    if (error) throw error

    advertisements.value = advertisements.value.filter(a => a.id !== id)
    expandedRows.value.delete(id)
  } catch (error) {
    console.error('Error deleting advertisement:', error)
    alert('Błąd podczas usuwania ogłoszenia')
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

          <div v-for="ad in advertisements" :key="ad.id" class="ad-row" :class="{ expanded: expandedRows.has(ad.id) }">
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
                  <select :value="ad.status" @change="updateStatus(ad.id, ($event.target as HTMLSelectElement).value)" class="status-select">
                    <option value="active">Wolne</option>
                    <option value="reserved">Zarezerwowane</option>
                    <option value="soon_available">Wkrótce dostępne</option>
                  </select>
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
                </div>

                <div class="form-actions">
                  <button type="button" @click="toggleRow(ad.id)" class="btn-cancel">
                    Anuluj
                  </button>
                  <button type="submit" class="btn-save">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                      <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" stroke="currentColor" stroke-width="2"/>
                      <path d="M17 21v-8H7v8M7 3v5h8" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    Zapisz zmiany
                  </button>
                </div>
              </form>
            </div>
          </div>
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

.status-select:hover {
  border-color: #667eea;
}

.status-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
</style>
