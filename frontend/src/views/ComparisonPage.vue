<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { supabase } from '../lib/supabase'
import type { Advertisement } from '../lib/supabase'
import ConfirmDialog from '../components/ConfirmDialog.vue'

const router = useRouter()
const comparisonAds = ref<Advertisement[]>([])
const isLoading = ref(true)
const priceUnit = ref<'day' | 'week' | 'month' | 'year'>('month')
const confirmDialog = ref<InstanceType<typeof ConfirmDialog> | null>(null)

const loadComparison = async () => {
  const comparisonIds = JSON.parse(localStorage.getItem('comparison') || '[]')

  if (comparisonIds.length === 0) {
    isLoading.value = false
    return
  }

  try {
    isLoading.value = true
    const { data, error } = await supabase
      .from('advertisements')
      .select('*')
      .in('id', comparisonIds)

    if (error) throw error
    comparisonAds.value = data || []
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
  // Trigger storage event to update header counter
  window.dispatchEvent(new Event('storage'))
}

const clearAll = () => {
  confirmDialog.value?.open()
}

const handleConfirmClear = () => {
  localStorage.setItem('comparison', JSON.stringify([]))
  comparisonAds.value = []
  // Trigger storage event to update header counter
  window.dispatchEvent(new Event('storage'))
}

const getSurfaceArea = (ad: Advertisement) => {
  if (ad.width && ad.height) {
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
  // Assuming base price is always monthly as per app logic
  switch (priceUnit.value) {
    case 'day':
      return (basePrice / 30).toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    case 'week':
      return (basePrice / 4).toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    case 'month':
      return basePrice.toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    case 'year':
      return (basePrice * 12).toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
  }
}

const priceUnitLabel = computed(() => {
  switch (priceUnit.value) {
    case 'day': return '/ dzień'
    case 'week': return '/ tydzień'
    case 'month': return '/ miesiąc'
    case 'year': return '/ rok'
  }
})

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
    billboard: 'Billboard',
    citylight: 'Citylight',
    led_screen: 'Ekran LED',
    digital: 'Digital',
    banner: 'Banner',
    poster: 'Plakat'
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

onMounted(() => {
  loadComparison()
})
</script>

<template>
  <div class="comparison-page">
    <div class="page-header">
      <div class="container">
        <button @click="router.push('/')" class="back-button">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M15 10H5M5 10L10 15M5 10L10 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Powrót do listy
        </button>
        <h1>Porównanie ogłoszeń</h1>
        <button v-if="comparisonAds.length > 0" @click="clearAll" class="clear-button">
          Wyczyść wszystkie
        </button>
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

        <div v-else class="comparison-table-wrapper">
          <div class="controls-bar">
            <div class="price-toggle">
              <span class="toggle-label">Jednostka ceny:</span>
              <div class="toggle-buttons">
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

          <table class="comparison-table">
            <thead>
              <tr>
                <th class="feature-column">Cecha</th>
                <th v-for="ad in comparisonAds" :key="ad.id" class="ad-column">
                  <div class="ad-header">
                    <router-link :to="`/ogloszenie/${ad.id}`" class="ad-image-link">
                      <img
                        v-if="ad.image_url"
                        :src="ad.image_url"
                        :alt="ad.title"
                        class="ad-image"
                      />
                      <div v-else class="no-image">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none">
                          <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                          <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                          <path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2"/>
                        </svg>
                      </div>
                    </router-link>
                    <router-link :to="`/ogloszenie/${ad.id}`" class="ad-title">
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
            <tbody>
              <tr>
                <td class="feature-name">Cena</td>
                <td v-for="ad in comparisonAds" :key="ad.id" class="feature-value highlight">
                  <strong>{{ getPrice(ad) }} PLN {{ priceUnitLabel }}</strong>
                </td>
              </tr>
              <tr>
                <td class="feature-name">Cena za m²</td>
                <td v-for="ad in comparisonAds" :key="ad.id" class="feature-value">
                  {{ getPricePerSqm(ad) }} PLN/m²
                </td>
              </tr>
              <tr>
                <td class="feature-name">Typ powierzchni</td>
                <td v-for="ad in comparisonAds" :key="ad.id" class="feature-value">
                  {{ getTypeLabel(ad.type) }}
                </td>
              </tr>
              <tr>
                <td class="feature-name">Wymiary (szer × wys)</td>
                <td v-for="ad in comparisonAds" :key="ad.id" class="feature-value">
                  {{ ad.width }}m × {{ ad.height }}m
                </td>
              </tr>
              <tr>
                <td class="feature-name">Powierzchnia</td>
                <td v-for="ad in comparisonAds" :key="ad.id" class="feature-value highlight">
                  <strong>{{ getSurfaceArea(ad) }} m²</strong>
                </td>
              </tr>
              <tr>
                <td class="feature-name">Orientacja</td>
                <td v-for="ad in comparisonAds" :key="ad.id" class="feature-value">
                  {{ ad.orientation === 'horizontal' ? 'Poziom' : 'Pion' }}
                </td>
              </tr>
              <tr>
                <td class="feature-name">Lokalizacja</td>
                <td v-for="ad in comparisonAds" :key="ad.id" class="feature-value">
                  {{ formatLocation(ad.location, ad.city) }}
                </td>
              </tr>
              <tr>
                <td class="feature-name">Województwo</td>
                <td v-for="ad in comparisonAds" :key="ad.id" class="feature-value">
                  {{ ad.region }}
                </td>
              </tr>
              <tr>
                <td class="feature-name">Natężenie ruchu</td>
                <td v-for="ad in comparisonAds" :key="ad.id" class="feature-value">
                  {{ ad.traffic_intensity === 'low' ? 'Niskie' : ad.traffic_intensity === 'medium' ? 'Średnie' : 'Wysokie' }}
                </td>
              </tr>
              <tr>
                <td class="feature-name">Podświetlenie</td>
                <td v-for="ad in comparisonAds" :key="ad.id" class="feature-value">
                  <span :class="ad.has_lighting ? 'value-yes' : 'value-no'">
                    {{ ad.has_lighting ? 'Tak' : 'Nie' }}
                  </span>
                </td>
              </tr>
              <tr>
                <td class="feature-name">Druk i montaż w cenie</td>
                <td v-for="ad in comparisonAds" :key="ad.id" class="feature-value">
                  <span :class="ad.price_includes_print ? 'value-yes' : 'value-no'">
                    {{ ad.price_includes_print ? 'Tak' : 'Nie' }}
                  </span>
                </td>
              </tr>
              <tr>
                <td class="feature-name">Pomoc graficzna</td>
                <td v-for="ad in comparisonAds" :key="ad.id" class="feature-value">
                  <span :class="ad.graphic_design_help ? 'value-yes' : 'value-no'">
                    {{ ad.graphic_design_help ? 'Tak' : 'Nie' }}
                  </span>
                </td>
              </tr>
              <tr>
                <td class="feature-name">Status</td>
                <td v-for="ad in comparisonAds" :key="ad.id" class="feature-value">
                  {{ getStatusLabel(ad.status) }}
                </td>
              </tr>
              <tr>
                <td class="feature-name">Rodzaj oferty</td>
                <td v-for="ad in comparisonAds" :key="ad.id" class="feature-value">
                  {{ ad.offer_type === 'owner' ? 'Właściciel' : 'Agencja' }}
                </td>
              </tr>
              <tr>
                <td class="feature-name">Faktura VAT</td>
                <td v-for="ad in comparisonAds" :key="ad.id" class="feature-value">
                  <span :class="ad.has_vat_invoice ? 'value-yes' : 'value-no'">
                    {{ ad.has_vat_invoice ? 'Tak' : 'Nie' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
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
  justify-content: space-between;
  gap: 2rem;
}

.page-header h1 {
  margin: 0;
  font-size: 2rem;
  font-weight: 800;
  color: #1f2937;
  flex: 1;
  text-align: center;
}

.back-button,
.clear-button {
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
  border-color: #10B981;
  color: #10B981;
  transform: translateX(-4px);
}

.clear-button {
  background: #fef2f2;
  border-color: #fecaca;
  color: #dc2626;
}

.clear-button:hover {
  background: #fee2e2;
  border-color: #fca5a5;
}

.page-content {
  padding: 3rem 0;
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

.comparison-table-wrapper {
  overflow-x: auto;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.comparison-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 800px;
}

.comparison-table thead {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.comparison-table th {
  padding: 1.5rem 1rem;
  text-align: left;
  font-weight: 700;
  font-size: 1rem;
}

.feature-column {
  width: 200px;
  position: sticky;
  left: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  z-index: 10;
}

.ad-column {
  min-width: 280px;
  max-width: 320px;
}

.ad-header {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  position: relative;
}

.ad-image-link {
  width: 100%;
  height: 160px;
  border-radius: 8px;
  overflow: hidden;
  display: block;
}

.ad-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s;
}

.ad-image-link:hover .ad-image {
  transform: scale(1.05);
}

.no-image {
  width: 100%;
  height: 100%;
  background: rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255, 255, 255, 0.6);
}

.ad-title {
  font-size: 1.05rem;
  font-weight: 600;
  color: white;
  text-decoration: none;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.ad-title:hover {
  text-decoration: underline;
}

.remove-btn {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  width: 32px;
  height: 32px;
  background: rgba(220, 38, 38, 0.9);
  border: none;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  color: white;
}

.remove-btn:hover {
  background: #dc2626;
  transform: scale(1.1);
}

.comparison-table tbody tr {
  border-bottom: 1px solid #e5e7eb;
}

.comparison-table tbody tr:last-child {
  border-bottom: none;
}

.comparison-table tbody tr:hover {
  background: #f9fafb;
}

.feature-name {
  padding: 1.25rem 1rem;
  font-weight: 600;
  color: #374151;
  background: white;
  position: sticky;
  left: 0;
  border-right: 2px solid #e5e7eb;
  z-index: 5;
}

.feature-value {
  padding: 1.25rem 1rem;
  color: #6b7280;
  font-size: 0.95rem;
}

.feature-value.highlight {
  background: #f0f9ff;
  color: #1e40af;
  font-size: 1rem;
}

.value-yes {
  color: #10B981;
  font-weight: 600;
}

.value-no {
  color: #6b7280;
}

@media (max-width: 768px) {
  .page-header .container {
    flex-direction: column;
    align-items: stretch;
  }

  .page-header h1 {
    font-size: 1.5rem;
  }

  .back-button {
    align-self: flex-start;
  }

  .clear-button {
    align-self: flex-end;
  }

  .comparison-table-wrapper {
    border-radius: 8px;
  }

  .ad-column {
    min-width: 240px;
  }

  .ad-image-link {
    height: 120px;
  }
}

.controls-bar {
  padding: 1rem;
  background: white;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
}

.price-toggle {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.toggle-label {
  font-weight: 600;
  color: #374151;
  font-size: 0.9rem;
}

.toggle-buttons {
  display: flex;
  background: #f3f4f6;
  padding: 0.25rem;
  border-radius: 8px;
  gap: 0.25rem;
}

.toggle-btn {
  padding: 0.375rem 0.75rem;
  border: none;
  background: transparent;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 500;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s;
}

.toggle-btn:hover {
  color: #374151;
}

.toggle-btn.active {
  background: white;
  color: #4f46e5;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  font-weight: 600;
}
</style>
