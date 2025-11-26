<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { supabase } from '../lib/supabase'

interface Filters {
  keyword: string
  type: string
  region: string
  city: string
  priceFrom: number | null
  priceTo: number | null
  priceUnit: string
  rentalPeriod: string
  orientation: string
  widthFrom: number | null
  widthTo: number | null
  heightFrom: number | null
  heightTo: number | null
  trafficIntensity: string
  status: string
  hasLighting: boolean
  onlyWithImage: boolean
  priceIncludesPrint: boolean
  graphicDesignHelp: boolean
  offerType: string
  hasVatInvoice: boolean
}

const emit = defineEmits<{
  search: [filters: Filters]
  reset: [filters: Filters]
}>()

const showAdvanced = ref(false)

const filters = ref<Filters>({
  keyword: '',
  type: '',
  region: '',
  city: '',
  priceFrom: null,
  priceTo: null,
  priceUnit: 'month',
  rentalPeriod: '',
  orientation: '',
  widthFrom: null,
  widthTo: null,
  heightFrom: null,
  heightTo: null,
  trafficIntensity: '',
  status: '',
  hasLighting: false,
  onlyWithImage: false,
  priceIncludesPrint: false,
  graphicDesignHelp: false,
  offerType: '',
  hasVatInvoice: false,
})

const adTypes = [
  { value: '', label: 'Wszystkie typy' },
  { value: 'billboard', label: 'Billboard' },
  { value: 'citylight', label: 'Citylight' },
  { value: 'led_screen', label: 'Ekran LED' },
]

const regions = [
  { value: '', label: 'Wszystkie' },
  { value: 'mazowieckie', label: 'Mazowieckie' },
  { value: 'małopolskie', label: 'Małopolskie' },
  { value: 'pomorskie', label: 'Pomorskie' },
  { value: 'dolnośląskie', label: 'Dolnośląskie' },
  { value: 'wielkopolskie', label: 'Wielkopolskie' },
  { value: 'łódzkie', label: 'Łódzkie' },
]

const cities = ref<string[]>([])
const showCitySuggestions = ref(false)

const filteredCities = computed(() => {
  if (!filters.value.city) return cities.value.slice(0, 5)
  const query = filters.value.city.toLowerCase()
  return cities.value
    .filter(city => city.toLowerCase().includes(query))
    .slice(0, 5)
})

const loadCities = async () => {
  try {
    const { data, error } = await supabase
      .from('advertisements')
      .select('city')

    if (error) throw error

    const uniqueCities = [...new Set(data?.map(ad => ad.city) || [])]
    cities.value = uniqueCities.sort()
  } catch (error) {
    console.error('Error loading cities:', error)
  }
}

const selectCity = (city: string) => {
  filters.value.city = city
  showCitySuggestions.value = false
}

const handleCityBlur = () => {
  window.setTimeout(() => {
    showCitySuggestions.value = false
  }, 200)
}

const handleSearch = () => {
  emit('search', { ...filters.value })
}

const resetFilters = () => {
  filters.value = {
    keyword: '',
    type: '',
    region: '',
    city: '',
    priceFrom: null,
    priceTo: null,
    priceUnit: 'month',
    rentalPeriod: '',
    orientation: '',
    widthFrom: null,
    widthTo: null,
    heightFrom: null,
    heightTo: null,
    trafficIntensity: '',
    status: '',
    hasLighting: false,
    onlyWithImage: false,
    priceIncludesPrint: false,
    graphicDesignHelp: false,
    offerType: '',
    hasVatInvoice: false,
  }
  emit('reset', { ...filters.value })
}

onMounted(() => {
  loadCities()
})
</script>

<template>
  <section class="hero-section">
    <div class="hero-background">
      <div class="gradient-overlay"></div>
      <img
        src="https://images.pexels.com/photos/220365/pexels-photo-220365.jpeg?auto=compress&cs=tinysrgb&w=1920"
        alt="Advertising surfaces"
        class="hero-image"
      />
    </div>

    <div class="hero-content">
      <div class="hero-text">
        <h1 class="hero-title">Znajdź idealną powierzchnię reklamową</h1>
        <p class="hero-subtitle">Tysiące ofert w całej Polsce. Sprawdź dostępność w Twojej okolicy!</p>
      </div>

      <div class="search-card">
        <form @submit.prevent="handleSearch" class="search-form">
          <div class="basic-filters">
            <div class="search-row">
              <div class="input-group">
                <label for="search-keyword" class="input-label">
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="8" cy="8" r="6" stroke="#4F46E5" stroke-width="1.5"/>
                    <path d="M12.5 12.5L16 16" stroke="#4F46E5" stroke-width="1.5" stroke-linecap="round"/>
                  </svg>
                  Nazwa / słowo kluczowe
                </label>
                <input
                  id="search-keyword"
                  v-model="filters.keyword"
                  type="text"
                  placeholder="np. billboard centrum"
                  class="search-input"
                />
              </div>

              <div class="input-group">
                <label for="search-type" class="input-label">
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="2" width="14" height="14" rx="2" stroke="#4F46E5" stroke-width="1.5"/>
                    <path d="M2 7H16M7 2V16" stroke="#4F46E5" stroke-width="1.5"/>
                  </svg>
                  Typ powierzchni
                </label>
                <select id="search-type" v-model="filters.type" class="search-select">
                  <option v-for="type in adTypes" :key="type.value" :value="type.value">
                    {{ type.label }}
                  </option>
                </select>
              </div>

              <div class="input-group">
                <label for="search-region" class="input-label">
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 9C10.1046 9 11 8.10457 11 7C11 5.89543 10.1046 5 9 5C7.89543 5 7 5.89543 7 7C7 8.10457 7.89543 9 9 9Z" stroke="#4F46E5" stroke-width="1.5"/>
                    <path d="M9 16C9 16 14 11.5 14 7C14 4.23858 11.7614 2 9 2C6.23858 2 4 4.23858 4 7C4 11.5 9 16 9 16Z" stroke="#4F46E5" stroke-width="1.5"/>
                  </svg>
                  Województwo
                </label>
                <select id="search-region" v-model="filters.region" class="search-select">
                  <option v-for="region in regions" :key="region.value" :value="region.value">
                    {{ region.label }}
                  </option>
                </select>
              </div>
            </div>

            <div class="search-row">
              <div class="input-group city-autocomplete">
                <label for="search-city" class="input-label">Miasto</label>
                <input
                  id="search-city"
                  v-model="filters.city"
                  type="text"
                  placeholder="np. Warszawa"
                  class="search-input"
                  @focus="showCitySuggestions = true"
                  @blur="handleCityBlur"
                />
                <div v-if="showCitySuggestions && filteredCities.length > 0" class="city-suggestions">
                  <div
                    v-for="city in filteredCities"
                    :key="city"
                    class="city-suggestion"
                    @click="selectCity(city)"
                  >
                    {{ city }}
                  </div>
                </div>
              </div>

              <div class="input-group price-group">
                <label class="input-label">Cena</label>
                <div class="price-filter">
                  <input
                    v-model.number="filters.priceFrom"
                    type="number"
                    placeholder="Od"
                    class="search-input price-input"
                  />
                  <span class="separator">-</span>
                  <input
                    v-model.number="filters.priceTo"
                    type="number"
                    placeholder="Do"
                    class="search-input price-input"
                  />
                  <select v-model="filters.priceUnit" class="search-select price-unit">
                    <option value="day">dzień</option>
                    <option value="week">tydzień</option>
                    <option value="month">miesiąc</option>
                    <option value="year">rok</option>
                    <option value="sqm">m²</option>
                  </select>
                </div>
              </div>

              <div class="input-group">
                <label for="search-rental" class="input-label">Czas wynajmu</label>
                <select id="search-rental" v-model="filters.rentalPeriod" class="search-select">
                  <option value="">Wszystkie</option>
                  <option value="short_term">Krótkoterminowy (&lt;1 miesiąc)</option>
                  <option value="long_term">Długoterminowy</option>
                </select>
              </div>
            </div>
          </div>

          <button type="button" class="toggle-advanced" @click="showAdvanced = !showAdvanced">
            {{ showAdvanced ? 'Ukryj' : 'Pokaż' }} filtrowanie zaawansowane
            <span class="arrow" :class="{ expanded: showAdvanced }">▼</span>
          </button>

          <transition name="slide">
            <div v-if="showAdvanced" class="advanced-filters">
              <div class="filter-section">
                <h4 class="section-title">Wymiary i powierzchnia</h4>
                <div class="search-row">
                  <div class="input-group">
                    <label for="orientation" class="input-label">Orientacja</label>
                    <select id="orientation" v-model="filters.orientation" class="search-select">
                      <option value="">Wszystkie</option>
                      <option value="vertical">Pion</option>
                      <option value="horizontal">Poziom</option>
                    </select>
                  </div>
                  <div class="input-group">
                    <label class="input-label">Szerokość (m)</label>
                    <div class="range-input">
                      <input
                        v-model.number="filters.widthFrom"
                        type="number"
                        placeholder="Od"
                        step="0.1"
                        class="search-input"
                      />
                      <span class="separator">-</span>
                      <input
                        v-model.number="filters.widthTo"
                        type="number"
                        placeholder="Do"
                        step="0.1"
                        class="search-input"
                      />
                    </div>
                  </div>
                  <div class="input-group">
                    <label class="input-label">Wysokość (m)</label>
                    <div class="range-input">
                      <input
                        v-model.number="filters.heightFrom"
                        type="number"
                        placeholder="Od"
                        step="0.1"
                        class="search-input"
                      />
                      <span class="separator">-</span>
                      <input
                        v-model.number="filters.heightTo"
                        type="number"
                        placeholder="Do"
                        step="0.1"
                        class="search-input"
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div class="filter-section">
                <h4 class="section-title">Lokalizacja i dostępność</h4>
                <div class="search-row">
                  <div class="input-group">
                    <label for="traffic" class="input-label">Natężenie ruchu</label>
                    <select id="traffic" v-model="filters.trafficIntensity" class="search-select">
                      <option value="">Wszystkie</option>
                      <option value="low">Niskie</option>
                      <option value="medium">Średnie</option>
                      <option value="high">Wysokie</option>
                    </select>
                  </div>
                  <div class="input-group">
                    <label for="status" class="input-label">Status</label>
                    <select id="status" v-model="filters.status" class="search-select">
                      <option value="">Wszystkie</option>
                      <option value="active">Wolne</option>
                      <option value="reserved">Zarezerwowane</option>
                      <option value="soon">Wkrótce dostępne</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="filter-section">
                <h4 class="section-title">Wyposażenie i dodatki</h4>
                <div class="checkbox-grid">
                  <label class="checkbox-label">
                    <input type="checkbox" v-model="filters.hasLighting" />
                    <span>Podświetlenie</span>
                  </label>
                  <label class="checkbox-label">
                    <input type="checkbox" v-model="filters.onlyWithImage" />
                    <span>Tylko ze zdjęciem</span>
                  </label>
                  <label class="checkbox-label">
                    <input type="checkbox" v-model="filters.priceIncludesPrint" />
                    <span>Cena zawiera druk i montaż</span>
                  </label>
                  <label class="checkbox-label">
                    <input type="checkbox" v-model="filters.graphicDesignHelp" />
                    <span>Pomoc przy projekcie graficznym</span>
                  </label>
                </div>
              </div>

              <div class="filter-section">
                <h4 class="section-title">Typ oferty i formalności</h4>
                <div class="search-row">
                  <div class="input-group">
                    <label for="offer-type" class="input-label">Rodzaj oferty</label>
                    <select id="offer-type" v-model="filters.offerType" class="search-select">
                      <option value="">Wszystkie</option>
                      <option value="owner">Właściciel</option>
                      <option value="agency">Agencja</option>
                    </select>
                  </div>
                  <div class="input-group">
                    <label class="checkbox-label vat-checkbox">
                      <input type="checkbox" v-model="filters.hasVatInvoice" />
                      <span>Faktura VAT</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </transition>

          <div class="button-row">
            <button type="button" class="reset-button" @click="resetFilters">
              Wyczyść filtry
            </button>
            <button type="submit" class="search-button">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="9" cy="9" r="6" stroke="white" stroke-width="2"/>
                <path d="M13.5 13.5L17 17" stroke="white" stroke-width="2" stroke-linecap="round"/>
              </svg>
              Szukaj
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<style scoped>
.hero-section {
  position: relative;
  min-height: 580px;
  overflow: visible;
}

.hero-background {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 100%;
  overflow: hidden;
}

.hero-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.gradient-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.85) 0%, rgba(118, 75, 162, 0.85) 100%);
  z-index: 1;
}

.hero-content {
  position: relative;
  z-index: 2;
  max-width: 1400px;
  margin: 0 auto;
  padding: 4rem 2rem 4rem;
  min-height: 580px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.hero-text {
  text-align: center;
  margin-bottom: 3rem;
}

.hero-title {
  font-size: 3.5rem;
  font-weight: 800;
  color: white;
  margin: 0 0 1rem 0;
  line-height: 1.2;
  text-shadow: 0 2px 20px rgba(0, 0, 0, 0.2);
}

.hero-subtitle {
  font-size: 1.35rem;
  color: rgba(255, 255, 255, 0.95);
  margin: 0;
  font-weight: 400;
  text-shadow: 0 1px 10px rgba(0, 0, 0, 0.15);
}

.search-card {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  width: 100%;
  max-width: 1100px;
  transform: translateY(60px);
  margin-bottom: 60px;
}

.search-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.basic-filters {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.search-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.input-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.input-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #4B5563;
}

.search-input,
.search-select {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid #E5E7EB;
  border-radius: 10px;
  font-size: 0.95rem;
  transition: all 0.2s ease;
  background: white;
}

.search-input:focus,
.search-select:focus {
  outline: none;
  border-color: #4F46E5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.search-select {
  cursor: pointer;
}

.price-filter,
.range-input {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.price-input {
  flex: 1;
  min-width: 0;
}

.price-unit {
  min-width: 100px;
}

.separator {
  color: #9CA3AF;
  font-weight: 500;
  flex-shrink: 0;
}

.price-group {
  min-width: 320px;
}

.price-input {
  min-width: 80px;
  flex: 1;
}

.city-autocomplete {
  position: relative;
}

.city-suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 2px solid #E5E7EB;
  border-radius: 10px;
  margin-top: 0.25rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 10;
  max-height: 200px;
  overflow-y: auto;
}

.city-suggestion {
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background-color 0.2s ease;
  font-size: 0.95rem;
  color: #1F2937;
}

.city-suggestion:hover {
  background-color: #F3F4F6;
}

.city-suggestion:first-child {
  border-radius: 8px 8px 0 0;
}

.city-suggestion:last-child {
  border-radius: 0 0 8px 8px;
}

.toggle-advanced {
  padding: 0.875rem 1.5rem;
  background: #F3F4F6;
  border: none;
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 600;
  color: #4F46E5;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.toggle-advanced:hover {
  background: #E5E7EB;
}

.arrow {
  transition: transform 0.3s ease;
  font-size: 0.75rem;
}

.arrow.expanded {
  transform: rotate(180deg);
}

.advanced-filters {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  padding-top: 1rem;
}

.filter-section {
  padding: 1.25rem;
  background: #F9FAFB;
  border-radius: 10px;
}

.section-title {
  font-size: 1rem;
  font-weight: 700;
  color: #1F2937;
  margin: 0 0 1rem 0;
}

.checkbox-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-size: 0.875rem;
  color: #4B5563;
  font-weight: 500;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #4F46E5;
}

.vat-checkbox {
  padding-top: 1.5rem;
}

.button-row {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.reset-button {
  padding: 0.875rem 2rem;
  background: white;
  border: 2px solid #E5E7EB;
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 600;
  color: #6B7280;
  cursor: pointer;
  transition: all 0.2s ease;
}

.reset-button:hover {
  background: #F9FAFB;
  border-color: #9CA3AF;
}

.search-button {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.875rem 2.5rem;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  white-space: nowrap;
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.search-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
}

.search-button:active {
  transform: translateY(0);
}

.slide-enter-active,
.slide-leave-active {
  transition: all 0.3s ease;
  max-height: 1000px;
  overflow: hidden;
}

.slide-enter-from,
.slide-leave-to {
  max-height: 0;
  opacity: 0;
}

@media (max-width: 1024px) {
  .hero-title {
    font-size: 2.75rem;
  }

  .hero-subtitle {
    font-size: 1.15rem;
  }

  .search-row {
    grid-template-columns: 1fr;
  }

  .button-row {
    flex-direction: column;
  }

  .reset-button,
  .search-button {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 640px) {
  .hero-section {
    min-height: auto;
  }

  .hero-content {
    min-height: auto;
    padding: 3rem 1rem 0;
  }

  .hero-title {
    font-size: 2rem;
  }

  .hero-subtitle {
    font-size: 1rem;
  }

  .search-card {
    padding: 1.5rem;
    transform: translateY(40px);
    margin-bottom: 80px;
  }

  .checkbox-grid {
    grid-template-columns: 1fr;
  }
}
</style>
