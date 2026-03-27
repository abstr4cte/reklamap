<script setup lang="ts">
import { computed, nextTick } from 'vue'
import type { Advertisement } from '../../types'
import EngagementChart from '../EngagementChart.vue'
import { useSearchStore } from '../../stores/useSearchStore'

const props = defineProps<{
  listings: Advertisement[]
  engagementChartRef: any
}>()

const emit = defineEmits<{
  'show-toast': [message: string, type: 'success' | 'error']
  'open-confirm-dialog': [title: string, message: string, type: 'info' | 'warning' | 'danger', adIds: string[], metric?: 'views' | 'clicks']
}>()

const searchStore = useSearchStore()

const totalViews = computed(() => {
  return props.listings.reduce((sum, ad) => sum + (ad.views_30d || 0), 0)
})

const totalPhoneClicks = computed(() => {
  return props.listings.reduce((sum, ad) => sum + (ad.phone_clicks_30d || 0), 0)
})

const totalEmailClicks = computed(() => {
  return props.listings.reduce((sum, ad) => sum + (ad.email_clicks_30d || 0), 0)
})

const totalEngagement = computed(() => {
  return totalPhoneClicks.value + totalEmailClicks.value
})

const engagementRate = computed(() => {
  if (totalViews.value === 0) return 0
  return ((totalEngagement.value / totalViews.value) * 100).toFixed(2)
})

const topPerformingAds = computed(() => {
  return [...props.listings]
    .sort((a, b) => ((b.views_30d || 0) - (a.views_30d || 0)))
    .slice(0, 5)
})

const mostEngagingAds = computed(() => {
  return [...props.listings]
    .sort((a, b) => {
      const aEngagement = (a.phone_clicks_30d || 0) + (a.email_clicks_30d || 0)
      const bEngagement = (b.phone_clicks_30d || 0) + (b.email_clicks_30d || 0)
      return bEngagement - aEngagement
    })
    .slice(0, 5)
})

const isAdOnChart = (adId: string): boolean => {
  if (props.engagementChartRef) {
    const chartComponent = props.engagementChartRef as any
    if (chartComponent.selectedAds?.value) {
      return chartComponent.selectedAds.value.includes(adId)
    } else if (Array.isArray(chartComponent.selectedAds)) {
      return chartComponent.selectedAds.includes(adId)
    }
  }
  return false
}

const addAdToChart = (adId: string) => {
  if (props.engagementChartRef) {
    const chartComponent = props.engagementChartRef as any
    let selectedAds: string[] = []
    
    if (chartComponent.selectedAds?.value) {
      selectedAds = chartComponent.selectedAds.value
    } else if (Array.isArray(chartComponent.selectedAds)) {
      selectedAds = chartComponent.selectedAds
    }
    
    if (selectedAds.length >= 5 && !selectedAds.includes(adId)) {
      emit('show-toast', 'Maksymalna ilość ogłoszeń (5) już dodana do wykresu', 'error')
      return
    }
    
    props.engagementChartRef.addAdsToChart([adId])
    
    nextTick(() => {
      const chartElement = document.querySelector('.engagement-chart-container')
      if (chartElement) {
        const headerHeight = 80
        const elementPosition = chartElement.getBoundingClientRect().top + window.scrollY
        window.scrollTo({
          top: elementPosition - headerHeight,
          behavior: 'smooth'
        })
      }
    })
  }
}

const addTopAdsToChart = (adIds: string[], metric?: 'views' | 'clicks') => {
  if (props.engagementChartRef) {
    const chartComponent = props.engagementChartRef as any
    let selectedAds: string[] = []
    
    if (chartComponent.selectedAds?.value) {
      selectedAds = chartComponent.selectedAds.value
    } else if (Array.isArray(chartComponent.selectedAds)) {
      selectedAds = chartComponent.selectedAds
    }
    
    if (selectedAds.length > 0) {
      emit('open-confirm-dialog', 'Nadpisać wybrane ogłoszenia?', `Masz już dodane ogłoszenia na wykresie. Nadpisać i dodać nowe z wybranej tabeli?`, 'warning', adIds, metric)
      return
    }
    
    executeAddTopAdsToChart(adIds, metric)
  }
}

const executeAddTopAdsToChart = (adIds: string[], metric?: 'views' | 'clicks') => {
  if (props.engagementChartRef) {
    const chartComponent = props.engagementChartRef as any
    
    if (chartComponent.selectedAds) {
      chartComponent.selectedAds.length = 0
      adIds.forEach((id: string) => {
        chartComponent.selectedAds.push(id)
      })
    }
    
    if (metric && typeof chartComponent.setMetric === 'function') {
      chartComponent.setMetric(metric)
    }

    nextTick(() => {
      const chartElement = document.querySelector('.engagement-chart-container')
      if (chartElement) {
        const headerHeight = 80
        const elementPosition = chartElement.getBoundingClientRect().top + window.scrollY
        window.scrollTo({
          top: elementPosition - headerHeight,
          behavior: 'smooth'
        })
      }
    })
  }
}

const adsByType = computed(() => {
  const types: Record<string, number> = {}
  props.listings.forEach(ad => {
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
    active: props.listings.filter(ad => ad.status === 'active').length,
    reserved: props.listings.filter(ad => ad.status === 'reserved').length,
    soon_available: props.listings.filter(ad => ad.status === 'soon_available').length
  }
  return statuses
})

const getTypeLabel = (type: string) => searchStore.getTypeLabel(type)

defineExpose({
  executeAddTopAdsToChart
})
</script>

<template>
  <div class="statistics-dashboard">
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
            <div class="chart-item-value clicks">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="2"/>
              </svg>
              {{ (ad.phone_clicks_30d || 0) + (ad.email_clicks_30d || 0) }}
            </div>
            <div class="chart-item-bar">
              <div 
                class="chart-item-bar-fill clicks" 
                :style="{ width: `${((ad.phone_clicks_30d || 0) + (ad.email_clicks_30d || 0)) / ((mostEngagingAds[0]?.phone_clicks_30d || 0) + (mostEngagingAds[0]?.email_clicks_30d || 0) || 1) * 100}%` }"
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

    <!-- Main Chart -->
    <EngagementChart
      ref="props.engagementChartRef"
      :ads="props.listings"
    />

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
                :style="{ width: `${(item.count / props.listings.length) * 100}%` }"
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
</template>

<style scoped>
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
}

.chart-item-info {
  flex: 1;
}

.chart-item-title {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.9rem;
  margin-bottom: 0.25rem;
  display: -webkit-box;
  -webkit-line-clamp: 1;
  line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.chart-item-meta {
  font-size: 0.75rem;
  color: #6b7280;
}

.chart-item-value {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-weight: 700;
  color: #667eea;
  font-size: 1.1rem;
}

.chart-item-value.clicks {
  color: #10B981;
}

.chart-item-bar {
  width: 80px;
  height: 6px;
  background: #e5e7eb;
  border-radius: 3px;
  overflow: hidden;
}

.chart-item-bar-fill {
  height: 100%;
  background: #667eea;
}

.chart-item-bar-fill.clicks {
  background: #10B981;
}

.chart-item-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  background: white;
  color: #6b7280;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.chart-item-btn:hover:not(:disabled) {
  border-color: #667eea;
  color: #667eea;
  background: #f5f3ff;
}

.chart-item-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: #f9fafb;
}

.chart-empty {
  padding: 2rem;
  text-align: center;
  color: #9ca3af;
  font-style: italic;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
  .charts-section {
    grid-template-columns: 1fr;
  }
}

/* Additional Stats/Breakdown Styles */
.additional-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  margin-top: 2rem;
}

.stat-breakdown-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
}

.stat-breakdown-card h3 {
  margin: 0 0 1.5rem 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: #1f2937;
}

.breakdown-list {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.breakdown-item {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 0.5rem;
  align-items: center;
}

.breakdown-label {
  font-size: 0.9rem;
  color: #4b5563;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.breakdown-value {
  font-weight: 700;
  color: #1f2937;
  font-size: 1rem;
}

.breakdown-bar {
  grid-column: 1 / -1;
  height: 6px;
  background: #f3f4f6;
  border-radius: 3px;
  overflow: hidden;
}

.breakdown-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  border-radius: 3px;
}

.status-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
}

.status-dot.active { background-color: #10b981; }
.status-dot.reserved { background-color: #f59e0b; }
.status-dot.soon { background-color: #3b82f6; }
</style>
