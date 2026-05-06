<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { api } from '../services/api'

interface CityEntry {
  city: string
  slug: string
  count: number
}

interface TypeEntry {
  type: string
  slug: string
  label: string
  count: number
}

interface SilosResponse {
  other_cities: CityEntry[]
  other_types: TypeEntry[]
}

const props = defineProps<{
  currentTypeSlug?: string
  currentCitySlug?: string
  currentCityLabel?: string
}>()

const isLoading = ref(false)
const data = ref<SilosResponse>({ other_cities: [], other_types: [] })
const hasError = ref(false)

const fetchSilos = async () => {
  isLoading.value = true
  hasError.value = false
  try {
    const params = new URLSearchParams()
    if (props.currentTypeSlug) params.set('type', props.currentTypeSlug)
    if (props.currentCitySlug) params.set('city', props.currentCitySlug)
    const qs = params.toString()
    data.value = await api.get(`/silos${qs ? '?' + qs : ''}`)
  } catch {
    hasError.value = true
    data.value = { other_cities: [], other_types: [] }
  } finally {
    isLoading.value = false
  }
}

watch(
  () => [props.currentTypeSlug, props.currentCitySlug],
  () => fetchSilos(),
  { immediate: true }
)

// Tytuł nagłówka sekcji "inne miasta" zależny od kontekstu — jeśli mamy typ,
// chcemy "Billboardy w innych miastach", inaczej "Powierzchnie reklamowe…".
const citiesSectionTitle = computed(() => {
  const typeLabel = otherCitiesTypeLabel.value
  return typeLabel
    ? `${typeLabel} w innych miastach`
    : 'Powierzchnie reklamowe w innych miastach'
})

// Etykieta typu wnioskowana z aktualnego slugu (do nagłówka). Mapa spójna
// z routingiem (router.ts) i kontrolerem SilosController.
const typeSlugToLabel: Record<string, string> = {
  billboardy: 'Billboardy',
  citylighty: 'Citylighty',
  banery: 'Banery',
  'sciany-reklamowe': 'Ściany reklamowe',
  'totemy-reklamowe': 'Totemy reklamowe',
  'reklama-w-transporcie': 'Reklama w transporcie',
  'reklama-mobilna': 'Reklama mobilna',
  'ekrany-led': 'Ekrany LED',
  inne: 'Inne powierzchnie reklamowe',
}

const otherCitiesTypeLabel = computed(() =>
  props.currentTypeSlug ? typeSlugToLabel[props.currentTypeSlug] ?? null : null
)

const typesSectionTitle = computed(() => {
  const cityLabel = props.currentCityLabel
  return cityLabel ? `Inne typy powierzchni w ${cityLabel}` : ''
})

// Ścieżka do listingu dla pilla "inne miasto" — uwzględnia aktualny typ.
const cityPillHref = (citySlug: string): string => {
  return props.currentTypeSlug
    ? `/powierzchnie-reklamowe/${props.currentTypeSlug}/${citySlug}`
    : `/powierzchnie-reklamowe/${citySlug}`
}

// Ścieżka do listingu dla pilla "inny typ" — zawsze w kontekście aktualnego miasta
// (sekcja types pokazuje się tylko gdy mamy miasto).
const typePillHref = (typeSlug: string): string => {
  return `/powierzchnie-reklamowe/${typeSlug}/${props.currentCitySlug}`
}

const showTypesSection = computed(
  () => !!props.currentCitySlug && data.value.other_types.length > 0
)
const showCitiesSection = computed(() => data.value.other_cities.length > 0)
const showAnything = computed(
  () => !isLoading.value && !hasError.value && (showCitiesSection.value || showTypesSection.value)
)
</script>

<template>
  <section v-if="showAnything" class="related-silos" aria-label="Powiązane kategorie i miasta">
    <div v-if="showCitiesSection" class="silo-block">
      <h2 class="silo-title">{{ citiesSectionTitle }}</h2>
      <ul class="pill-list">
        <li v-for="entry in data.other_cities" :key="`city-${entry.slug}`">
          <router-link :to="cityPillHref(entry.slug)" class="pill">
            <span class="pill-label">{{ otherCitiesTypeLabel ?? 'Powierzchnie reklamowe' }} {{ entry.city }}</span>
            <span class="pill-count">{{ entry.count }}</span>
          </router-link>
        </li>
      </ul>
    </div>

    <div v-if="showTypesSection" class="silo-block">
      <h2 class="silo-title">{{ typesSectionTitle }}</h2>
      <ul class="pill-list">
        <li v-for="entry in data.other_types" :key="`type-${entry.slug}`">
          <router-link :to="typePillHref(entry.slug)" class="pill">
            <span class="pill-label">{{ entry.label }} {{ currentCityLabel }}</span>
            <span class="pill-count">{{ entry.count }}</span>
          </router-link>
        </li>
      </ul>
    </div>
  </section>
</template>

<style scoped>
.related-silos {
  background: var(--card-bg, linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%));
  border-radius: 16px;
  padding: 2rem;
  margin-top: 1.5rem;
  border: 1px solid var(--border-color, rgba(102, 126, 234, 0.1));
  box-shadow: var(--card-shadow, 0 4px 12px rgba(0, 0, 0, 0.05));
}

.silo-block + .silo-block {
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid var(--border-color, rgba(102, 126, 234, 0.1));
}

.silo-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-main, #1f2937);
  margin: 0 0 1rem 0;
}

.pill-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.pill {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: var(--bg-secondary, white);
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 999px;
  text-decoration: none;
  color: var(--text-main, #374151);
  font-size: 0.9rem;
  font-weight: 500;
  transition: all 0.2s ease;
}

.pill:hover {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-color: transparent;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
}

.pill-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.5rem;
  height: 1.5rem;
  padding: 0 0.4rem;
  background: rgba(102, 126, 234, 0.12);
  color: #667eea;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
}

.pill:hover .pill-count {
  background: rgba(255, 255, 255, 0.25);
  color: white;
}

@media (max-width: 768px) {
  .related-silos {
    padding: 1.5rem;
  }

  .silo-title {
    font-size: 1.05rem;
  }

  .pill {
    font-size: 0.85rem;
    padding: 0.4rem 0.85rem;
  }
}
</style>
