<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'
import type { Advertisement } from '../types'
import { api } from '../services/api'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

interface Props {
  ads: Advertisement[]
}

const props = defineProps<Props>()

const selectedAds = ref<string[]>([])
const chartMetric = ref<'clicks' | 'views'>('views')
const clicksType = ref<'all' | 'phone' | 'email'>('all')
const dailyStatsCache = ref<Record<string, any>>({})
const isLoading = ref(false)
const showAdSelector = ref(false)
const searchQuery = ref('')
const sortBy = ref<'title' | 'views' | 'phone' | 'email' | 'total'>('views')

// Zakres dat
const dateRangeType = ref<'30days' | 'custom'>('30days')
const startDate = ref<Date | null>(null)
const endDate = ref<Date | null>(null)

// Inicjalizuj daty
const initializeDates = () => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const thirtyDaysAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000)
  
  endDate.value = today
  startDate.value = thirtyDaysAgo
}

// Formatuj datę do wyświetlenia
const formatDate = (date: Date | null): string => {
  if (!date) return ''
  const d = new Date(date)
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const year = d.getFullYear()
  return `${day}.${month}.${year}`
}

// Kolory dla różnych ogłoszeń
const colors = [
  '#667eea',
  '#764ba2',
  '#f093fb',
  '#4facfe',
  '#00f2fe',
  '#43e97b',
  '#fa709a',
  '#fee140',
  '#30cfd0',
  '#330867'
]

// Pobierz rzeczywiste dane dzienne z backendu
const fetchDailyStats = async (adIds: string[]) => {
  if (adIds.length === 0) return
  
  console.log('[EngagementChart] Fetching daily stats for:', adIds)
  isLoading.value = true
  try {
    const stats = await api.getMultipleDailyStats(adIds, 30)
    console.log('[EngagementChart] Received stats from API:', stats)
    
    const newCache = { ...dailyStatsCache.value }
    stats.forEach((stat: any) => {
      newCache[stat.advertisement_id] = stat
    })
    dailyStatsCache.value = newCache
    console.log('[EngagementChart] Updated cache:', dailyStatsCache.value)
  } catch (error) {
    console.error('Failed to fetch daily stats:', error)
  } finally {
    isLoading.value = false
  }
}

// Pobierz dane dzienne dla wybranego ogłoszenia
const getDailyData = (adId: string, metric: 'clicks' | 'views') => {
  const cached = dailyStatsCache.value[adId]
  // console.log(`[EngagementChart] Getting data for ${adId}, cached:`, cached)
  const start = startDate.value || new Date()
  const end = endDate.value || new Date()
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  
  if (cached && cached.stats && cached.stats.length > 0) {
    // Użyj rzeczywistych danych z backendu
    const allData = cached.stats
      .map((stat: any) => {
        let value = 0
        if (metric === 'clicks') {
          if (clicksType.value === 'all') {
            value = stat.total_clicks
          } else if (clicksType.value === 'phone') {
            value = stat.phone_clicks
          } else if (clicksType.value === 'email') {
            value = stat.email_clicks
          }
        } else {
          value = stat.views
        }
        
        return {
          date: new Date(stat.date),
          dateStr: new Date(stat.date).toLocaleDateString('pl-PL', { month: 'short', day: 'numeric' }),
          value
        }
      })
    
    // Generuj pełny zakres dat
    const data = []
    const days = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1
    
    for (let i = 0; i < days; i++) {
      const date = new Date(start)
      date.setDate(date.getDate() + i)
      date.setHours(0, 0, 0, 0)
      
      // Jeśli data jest w przyszłości, ustaw wartość na 0
      if (date > today) {
        data.push({
          date: date.toLocaleDateString('pl-PL', { month: 'short', day: 'numeric' }),
          value: 0
        })
      } else {
        // Szukaj danych dla tej daty
        // Użyj lokalnych komponentów daty aby uniknąć przesunięć stref czasowych
        const year = date.getFullYear()
        const month = String(date.getMonth() + 1).padStart(2, '0')
        const day = String(date.getDate()).padStart(2, '0')
        const dateString = `${year}-${month}-${day}`
        
        const stat = allData.find((d: any) => {
          const dDate = new Date(d.date)
          const dYear = dDate.getFullYear()
          const dMonth = String(dDate.getMonth() + 1).padStart(2, '0')
          const dDay = String(dDate.getDate()).padStart(2, '0')
          const dDateString = `${dYear}-${dMonth}-${dDay}`
          
          return dDateString === dateString
        })
        
        data.push({
          date: date.toLocaleDateString('pl-PL', { month: 'short', day: 'numeric' }),
          value: stat ? stat.value : 0
        })
      }
    }
    
    return data
  }
  
  // Fallback: zwróć zera jeśli brak danych (nie symuluj)
  const data = []
  
  // Oblicz liczbę dni w wybranym zakresie
  const days = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1
  
  for (let i = 0; i < days; i++) {
    const date = new Date(start)
    date.setDate(date.getDate() + i)
    date.setHours(0, 0, 0, 0)
    
    data.push({
      date: date.toLocaleDateString('pl-PL', { month: 'short', day: 'numeric' }),
      value: 0
    })
  }
  
  return data
}

// Przygotuj dane do wykresu
const chartData = computed(() => {
  // Dodaj dependency na daty aby chart się aktualizował
  void ((startDate.value?.getTime() || 0) + (endDate.value?.getTime() || 0))
  
  if (selectedAds.value.length === 0) {
    return {
      labels: [],
      datasets: []
    }
  }
  
  // Pobierz wybrane ogłoszenia
  const selected = props.ads.filter(ad => selectedAds.value.includes(ad.id))
  
  // Generuj dane dla każdego ogłoszenia
  const datasets = selected.map((ad, index) => {
    const dailyData = getDailyData(ad.id, chartMetric.value)
    const color = colors[index % colors.length]
    
    return {
      label: ad.title,
      data: dailyData.map((d: any) => d.value),
      borderColor: color,
      backgroundColor: color + '20',
      fill: true,
      tension: 0.4,
      pointRadius: 4,
      pointBackgroundColor: color,
      pointBorderColor: '#fff',
      pointBorderWidth: 2,
      pointHoverRadius: 6
    }
  })
  
  // Etykiety (dni)
  const labels = selected.length > 0 ? getDailyData(selected[0].id, chartMetric.value).map((d: any) => d.date) : []
  
  return {
    labels,
    datasets
  }
})

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: true,
  plugins: {
    legend: {
      display: true,
      position: 'top' as const,
      labels: {
        usePointStyle: true,
        padding: 15,
        font: {
          size: 12,
          weight: 600
        },
        color: '#374151'
      }
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      padding: 12,
      titleFont: {
        size: 13,
        weight: 600
      },
      bodyFont: {
        size: 12
      },
      cornerRadius: 8,
      displayColors: true,
      callbacks: {
        label: function(context: any) {
          return `${context.dataset.label}: ${context.parsed.y}`
        }
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        color: '#e5e7eb',
        drawBorder: false
      },
      ticks: {
        color: '#6b7280',
        font: {
          size: 11
        }
      },
      title: {
        display: true,
        text: chartMetric.value === 'clicks' ? 'Liczba kliknięć' : 'Liczba wyświetleń',
        color: '#374151',
        font: {
          size: 12,
          weight: 600
        }
      }
    },
    x: {
      grid: {
        display: false
      },
      ticks: {
        color: '#6b7280',
        font: {
          size: 11
        }
      }
    }
  }
}))

// Watcher - pobierz dane gdy zmieni się selekcja
watch(selectedAds, (newIds) => {
  if (newIds.length > 0) {
    fetchDailyStats(newIds)
  }
}, { deep: true })

// Watcher - zablokuj scroll w tle gdy modal jest otwarty
watch(showAdSelector, (isOpen) => {
  if (isOpen) {
    document.body.classList.add('modal-open')
  } else {
    document.body.classList.remove('modal-open')
  }
})

// Watcher - aktualizuj wykres gdy zmienią się daty
watch([startDate, endDate], () => {
  // Trigger reactivity na chartData poprzez zmianę któregoś z obserwowanych properties
  // chartData będzie się automatycznie przeliczać dzięki computed property
}, { deep: true })

// Pobierz dane przy montowaniu komponentu
onMounted(() => {
  // Inicjalizuj daty
  initializeDates()
})

// Cleanup przy unmountowaniu
onBeforeUnmount(() => {
  document.body.classList.remove('modal-open')
})

// Normalizuj polskie znaki do wersji bez diakrytyków
const normalizeText = (text: string): string => {
  return text
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/ł/g, 'l')
    .replace(/ó/g, 'o')
    .replace(/ą/g, 'a')
    .replace(/ć/g, 'c')
    .replace(/ę/g, 'e')
    .replace(/ń/g, 'n')
    .replace(/ś/g, 's')
    .replace(/ź/g, 'z')
    .replace(/ż/g, 'z')
}

// Filtruj i sortuj ogłoszenia
const filteredAds = computed(() => {
  let filtered = props.ads
  
  // Filtruj po wyszukiwaniu
  if (searchQuery.value.trim()) {
    const query = normalizeText(searchQuery.value)
    filtered = filtered.filter(ad => 
      normalizeText(ad.title).includes(query) || 
      normalizeText(ad.city).includes(query)
    )
  }
  
  // Sortuj
  const sorted = [...filtered]
  sorted.sort((a, b) => {
    switch (sortBy.value) {
      case 'title':
        return a.title.localeCompare(b.title, 'pl')
      case 'views':
        return (b.views || 0) - (a.views || 0)
      case 'phone':
        return ((b as any).phone_clicks || 0) - ((a as any).phone_clicks || 0)
      case 'email':
        return ((b as any).email_clicks || 0) - ((a as any).email_clicks || 0)
      case 'total':
        const aTotal = ((a as any).phone_clicks || 0) + ((a as any).email_clicks || 0)
        const bTotal = ((b as any).phone_clicks || 0) + ((b as any).email_clicks || 0)
        return bTotal - aTotal
      default:
        return 0
    }
  })
  
  return sorted
})

const toggleAdSelection = (adId: string) => {
  const index = selectedAds.value.indexOf(adId)
  if (index > -1) {
    selectedAds.value.splice(index, 1)
  } else if (selectedAds.value.length < 5) {
    selectedAds.value.push(adId)
  }
}

const clearSelection = () => {
  selectedAds.value = []
  searchQuery.value = ''
}

// Expose metody dla rodzica
const addAdsToChart = (adIds: string[]) => {
  adIds.forEach(id => {
    if (!selectedAds.value.includes(id) && selectedAds.value.length < 5) {
      selectedAds.value.push(id)
    }
  })
  showAdSelector.value = false
}

const setMetric = (metric: 'views' | 'clicks') => {
  chartMetric.value = metric
}

defineExpose({
  addAdsToChart,
  setMetric,
  selectedAds
})
</script>

<template>
  <div class="engagement-chart-container">
    <div class="chart-header">
      <div>
        <h3>Trendy Zaangażowania</h3>
        <p class="chart-subtitle">Porównaj trendy kliknięć i wyświetleń w ciągu ostatnich 30 dni</p>
      </div>
      <button 
        v-if="selectedAds.length > 0"
        @click="clearSelection" 
        class="chart-clear-btn"
        title="Wyczyść wszystkie wybory"
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
          <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        Wyczyść
      </button>
    </div>

    <!-- Controls Section -->
    <div class="chart-controls">
      <!-- Left Column: Metric -->
      <div class="controls-column">
        <!-- Metric Selector -->
        <div class="control-group">
          <label class="control-label">Metryka:</label>
          <div class="metric-selector">
            <button
              @click="chartMetric = 'views'"
              :class="{ active: chartMetric === 'views' }"
              class="metric-btn"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/>
                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
              </svg>
              Wyświetlenia
            </button>
            <button
              @click="chartMetric = 'clicks'"
              :class="{ active: chartMetric === 'clicks' }"
              class="metric-btn"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              Kliknięcia
            </button>
          </div>
        </div>

        <!-- Clicks Type Selector (pokazuj tylko gdy metric = 'clicks') -->
        <div v-if="chartMetric === 'clicks'" class="clicks-type-selector">
          <button
            @click="clicksType = 'all'"
            :class="{ active: clicksType === 'all' }"
            class="clicks-type-btn"
          >
            Wszystkie
          </button>
          <button
            @click="clicksType = 'phone'"
            :class="{ active: clicksType === 'phone' }"
            class="clicks-type-btn"
          >
            Telefon
          </button>
          <button
            @click="clicksType = 'email'"
            :class="{ active: clicksType === 'email' }"
            class="clicks-type-btn"
          >
            Email
          </button>
        </div>
      </div>

      <!-- Right Column: Date Range -->
      <div class="controls-column">
        <!-- Date Range Selector -->
        <div class="control-group">
          <label class="control-label">Zakres:</label>
          <div class="date-range-buttons">
            <button
              @click="dateRangeType = '30days'"
              :class="{ active: dateRangeType === '30days' }"
              class="date-range-btn"
            >
              Ostatnie 30 dni
            </button>
            <button
              @click="dateRangeType = 'custom'"
              :class="{ active: dateRangeType === 'custom' }"
              class="date-range-btn"
            >
              Własny zakres
            </button>
          </div>
        </div>

        <!-- Custom Date Inputs (separate from control-group to not affect layout) -->
        <div v-if="dateRangeType === 'custom'" class="date-inputs">
          <div class="date-input-group">
            <label>Od:</label>
            <VueDatePicker
              v-model="startDate"
              :enable-time-picker="false"
              auto-apply
              :clearable="false"
              class="w-full"
            >
              <template #trigger>
                <div class="date-picker-wrapper">
                  <input
                    type="text"
                    readonly
                    :value="startDate ? formatDate(startDate) : 'dd.mm.rrrr'"
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
          <div class="date-input-group">
            <label>Do:</label>
            <VueDatePicker
              v-model="endDate"
              :enable-time-picker="false"
              auto-apply
              :clearable="false"
              class="w-full"
            >
              <template #trigger>
                <div class="date-picker-wrapper">
                  <input
                    type="text"
                    readonly
                    :value="endDate ? formatDate(endDate) : 'dd.mm.rrrr'"
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
        </div>
      </div>
    </div>

    <!-- Ad Selector Button -->
    <div class="ad-selector-button-wrapper">
      <button @click="showAdSelector = !showAdSelector" class="ad-selector-btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <span>Wybierz ogłoszenia</span>
        <span v-if="selectedAds.length > 0" class="badge">{{ selectedAds.length }}/5</span>
        <svg :class="{ rotated: showAdSelector }" width="16" height="16" viewBox="0 0 24 24" fill="none">
          <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>

      <!-- Selected Ads Pills -->
      <div v-if="selectedAds.length > 0" class="selected-ads-pills">
        <div v-for="adId in selectedAds" :key="adId" class="ad-pill">
          <span>{{ props.ads.find(a => a.id === adId)?.title }}</span>
          <button @click="toggleAdSelection(adId)" class="pill-remove">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
              <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Ad Selector Modal -->
    <div v-if="showAdSelector" class="ad-selector-modal">
      <div class="modal-content">
        <div class="modal-header">
          <h4>Wybierz ogłoszenia do porównania</h4>
          <button @click="showAdSelector = false" class="modal-close">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>

        <div class="modal-search">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
            <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Szukaj po tytule lub mieście..."
            class="search-input"
          />
        </div>

        <!-- Sort Options -->
        <div class="modal-sort">
          <button
            @click="sortBy = 'title'"
            :class="{ active: sortBy === 'title' }"
            class="sort-btn"
          >
            A-Z
          </button>
          <button
            @click="sortBy = 'views'"
            :class="{ active: sortBy === 'views' }"
            class="sort-btn"
            title="Najczęściej wyświetlane"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/>
              <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
            </svg>
            Wyświetlenia
          </button>
          <button
            @click="sortBy = 'phone'"
            :class="{ active: sortBy === 'phone' }"
            class="sort-btn"
            title="Najczęściej klikane - telefon"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="2" fill="none"/>
            </svg>
            Tel
          </button>
          <button
            @click="sortBy = 'email'"
            :class="{ active: sortBy === 'email' }"
            class="sort-btn"
            title="Najczęściej klikane - email"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
              <rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/>
              <path d="M3 7l9 6 9-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Email
          </button>
          <button
            @click="sortBy = 'total'"
            :class="{ active: sortBy === 'total' }"
            class="sort-btn"
            title="Najczęściej klikane - razem"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
              <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Razem
          </button>
        </div>

        <div class="modal-list">
          <button
            v-for="ad in filteredAds"
            :key="ad.id"
            @click="toggleAdSelection(ad.id)"
            :class="{ selected: selectedAds.includes(ad.id), disabled: selectedAds.length >= 5 && !selectedAds.includes(ad.id) }"
            class="modal-list-item"
          >
            <span class="checkbox">
              <svg v-if="selectedAds.includes(ad.id)" width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>
            <div class="item-info">
              <div class="item-title">{{ ad.title }}</div>
              <div class="item-meta">{{ ad.city }} • {{ ad.type }}</div>
            </div>
          </button>
        </div>

        <div class="modal-footer">
          <button @click="clearSelection" class="btn-clear">Wyczyść</button>
          <button @click="showAdSelector = false" class="btn-done">Gotowe</button>
        </div>
      </div>
    </div>

    <!-- Chart -->
    <div v-if="selectedAds.length > 0" class="chart-wrapper">
      <Line :data="chartData" :options="chartOptions" />
    </div>
    <div v-else class="chart-empty">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
        <path d="M3 3v18h18" stroke="#d1d5db" stroke-width="2" stroke-linecap="round"/>
        <path d="M7 16v-6M12 16V8M17 16v-4" stroke="#d1d5db" stroke-width="2" stroke-linecap="round"/>
      </svg>
      <p>Wybierz co najmniej jedno ogłoszenie, aby zobaczyć wykres</p>
    </div>
  </div>
</template>

<style scoped>
.engagement-chart-container {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 2rem;
}

.chart-header h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
}

.chart-subtitle {
  margin: 0;
  color: #6b7280;
  font-size: 0.9rem;
}

.chart-clear-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  color: #dc2626;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.chart-clear-btn:hover {
  background: #fee2e2;
  border-color: #fca5a5;
}

/* Chart Controls */
.chart-controls {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: #f9fafb;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.controls-column {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.control-group {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.controls-column:last-child .control-group {
  align-items: flex-end;
}

.control-label {
  font-size: 0.85rem;
  font-weight: 700;
  color: #374151;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Metric Selector */
.metric-selector {
  display: flex;
  gap: 0.5rem;
}

.metric-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  background: #f3f4f6;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  color: #6b7280;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
}

.metric-btn:hover {
  border-color: #667eea;
  color: #667eea;
}

.metric-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-color: transparent;
  color: white;
}

/* Clicks Type Selector */
.clicks-type-selector {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  animation: slideDown 0.3s ease-out;
  align-content: flex-start;
  min-height: 80px;
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

.clicks-type-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  color: #6b7280;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
}

.clicks-type-btn:hover {
  border-color: #667eea;
  color: #667eea;
  background: #f5f3ff;
}

.clicks-type-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-color: transparent;
  color: white;
}

/* Date Range Selector */
.date-range-selector {
  margin-bottom: 2rem;
}

.date-range-buttons {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.date-range-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  background: #f3f4f6;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  color: #6b7280;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
}

.date-range-btn:hover {
  border-color: #667eea;
  color: #667eea;
}

.date-range-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-color: transparent;
  color: white;
}

.date-inputs {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  flex-wrap: nowrap;
}

.date-input-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex: 1;
}

.date-input-group label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #6b7280;
}

.date-input {
  padding: 0.625rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 0.9rem;
  color: #1f2937;
  background: white;
  transition: all 0.2s;
}

.date-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.date-picker-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.date-picker-wrapper .dp__input {
  padding: 0.875rem 2.5rem 0.875rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 0.95rem;
  transition: all 0.2s ease;
  font-family: inherit;
  background: white;
  cursor: pointer;
  color: #1f2937;
  width: 100%;
}

.date-picker-wrapper .dp__input:hover {
  border-color: #9ca3af;
}

.date-picker-wrapper .dp__input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  outline: none;
}

.date-picker-icon {
  position: absolute;
  right: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
  color: #6b7280;
}

/* Ad Selector */
.ad-selector {
  margin-bottom: 2rem;
}

.selector-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.selector-header h4 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 600;
  color: #374151;
}

.clear-btn {
  padding: 0.5rem 1rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 6px;
  color: #dc2626;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s;
}

.clear-btn:hover {
  background: #fee2e2;
  border-color: #fca5a5;
}

.ads-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 0.75rem;
}

.ad-chip {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: #f9fafb;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
}

.ad-chip:hover {
  border-color: #667eea;
  background: #f5f3ff;
}

.ad-chip.selected {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
  border-color: #667eea;
}

.chip-checkbox {
  width: 20px;
  height: 20px;
  border-radius: 4px;
  background: white;
  border: 2px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #667eea;
  transition: all 0.2s;
}

.ad-chip.selected .chip-checkbox {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-color: transparent;
  color: white;
}

.chip-text {
  flex: 1;
  font-weight: 600;
  color: #1f2937;
  font-size: 0.9rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chip-city {
  font-size: 0.8rem;
  color: #9ca3af;
  white-space: nowrap;
}

/* Chart */
.chart-wrapper {
  margin-top: 2rem;
  padding: 1.5rem;
  background: #f9fafb;
  border-radius: 8px;
  min-height: 400px;
}

.chart-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 2rem;
  text-align: center;
  color: #9ca3af;
}

.chart-empty svg {
  margin-bottom: 1rem;
  opacity: 0.5;
}

.chart-empty p {
  margin: 0;
  font-size: 0.95rem;
}

/* Ad Selector Button Wrapper */
.ad-selector-button-wrapper {
  margin-bottom: 2rem;
}

.ad-selector-btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
  padding: 1rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 10px;
  color: white;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.ad-selector-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.ad-selector-btn svg:last-child {
  margin-left: auto;
  transition: transform 0.3s;
}

.ad-selector-btn svg.rotated {
  transform: rotate(180deg);
}

.badge {
  background: rgba(255, 255, 255, 0.3);
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 700;
}

/* Selected Ads Pills */
.selected-ads-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 1rem;
}

.ad-pill {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #f0f4ff;
  border: 1px solid #667eea;
  border-radius: 20px;
  color: #667eea;
  font-weight: 600;
  font-size: 0.9rem;
}

.pill-remove {
  background: none;
  border: none;
  color: #667eea;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  transition: all 0.2s;
}

.pill-remove:hover {
  color: #764ba2;
}

/* Ad Selector Modal */
.ad-selector-modal {
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
  padding: 1rem;
  animation: fadeIn 0.2s ease-out;
  overflow: hidden;
}

/* Zablokuj scroll w tle gdy modal jest otwarty */
.ad-selector-modal + * {
  overflow: hidden;
}

:global(body.modal-open) {
  overflow: hidden;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.modal-content {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 600px;
  max-height: calc(100vh - 2rem);
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  animation: slideUp 0.3s ease-out;
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
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h4 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: #1f2937;
}

.modal-close {
  background: none;
  border: none;
  color: #6b7280;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  transition: all 0.2s;
}

.modal-close:hover {
  color: #1f2937;
}

.modal-search {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  color: #9ca3af;
}

.search-input {
  flex: 1;
  background: none;
  border: none;
  outline: none;
  font-size: 0.95rem;
  color: #1f2937;
}

.search-input::placeholder {
  color: #d1d5db;
}

/* Sort Options */
.modal-sort {
  display: flex;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  justify-content: center;
  flex-wrap: wrap;
}

.sort-btn {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.875rem;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  color: #6b7280;
  font-weight: 600;
  font-size: 0.8rem;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.sort-btn:hover {
  border-color: #667eea;
  color: #667eea;
  background: #f5f3ff;
}

.sort-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-color: transparent;
  color: white;
}

.modal-list {
  flex: 1;
  overflow-y: auto;
  padding: 0.5rem;
  min-height: 200px;
  max-height: 400px;
}

.modal-list-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  width: 100%;
  padding: 1rem;
  background: none;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
}

.modal-list-item:hover:not(.disabled) {
  background: #f9fafb;
}

.modal-list-item.selected {
  background: #f0f4ff;
}

.modal-list-item.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.checkbox {
  width: 20px;
  height: 20px;
  border-radius: 4px;
  background: white;
  border: 2px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #667eea;
  transition: all 0.2s;
}

.modal-list-item.selected .checkbox {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-color: transparent;
  color: white;
}

.item-info {
  flex: 1;
  min-width: 0;
}

.item-title {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.95rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.item-meta {
  font-size: 0.8rem;
  color: #9ca3af;
  margin-top: 0.25rem;
}

.modal-footer {
  display: flex;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.btn-clear {
  flex: 1;
  padding: 0.75rem 1.5rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  color: #dc2626;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-clear:hover {
  background: #fee2e2;
  border-color: #fca5a5;
}

.btn-done {
  flex: 1;
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 8px;
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-done:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

@media (max-width: 768px) {
  .engagement-chart-container {
    padding: 1.5rem;
  }

  .ads-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  }

  .metric-selector {
    flex-wrap: wrap;
  }

  .chart-wrapper {
    min-height: 300px;
  }
}
</style>
