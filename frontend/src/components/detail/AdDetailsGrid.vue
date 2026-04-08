<script setup lang="ts">
import { computed } from 'vue'
import type { Advertisement } from '../../types'
import { useSearchStore } from '../../stores/useSearchStore'

const props = defineProps<{
  ad: Advertisement
}>()

const searchStore = useSearchStore()

const surfaceArea = computed(() => {
  if (!props.ad.width || !props.ad.height) return '0'
  return (props.ad.width * props.ad.height).toFixed(2)
})

const showDimensions = computed(() => {
  // Wymiary są ukryte dla transport, mobile, other
  const hideDimensionsTypes = ['transport', 'mobile', 'other']
  return !hideDimensionsTypes.includes(props.ad.type) && props.ad.width && props.ad.height
})

const showSurfaceArea = computed(() => {
  return props.ad.type !== 'citylight' && props.ad.width && props.ad.height && parseFloat(surfaceArea.value) > 0
})

const locationTier = computed(() => {
  if (props.ad.type !== 'billboard') return null
  const ti = props.ad.traffic_intensity
  const rc = props.ad.road_class
  return ti === 'high' && ['highway', 'expressway', 'national'].includes(rc || '') ? 'PREMIUM' : 'STANDARD'
})

const formatTrafficDirection = computed(() => {
  if (!props.ad.traffic_direction) return ''
  const directions = Array.isArray(props.ad.traffic_direction) 
    ? props.ad.traffic_direction 
    : (props.ad.traffic_direction as string).split(',').map(s => s.trim())
  return searchStore.formatTrafficDirection(directions)
})

const formatTrafficType = computed(() => searchStore.formatTrafficType(props.ad.traffic_type))

// Labels
const getRoadClassLabel = (rc: string) => searchStore.getRoadClassLabel(rc)
const getEnvironmentLabel = (env: string) => searchStore.formatEnvironment(env)
const getTransportScopeLabel = (scope: string) => searchStore.formatTransportScope(scope)
const getMobileExposureModeLabel = (mode: string) => searchStore.formatMobileExposureMode(mode)
const getLightingTypeLabel = (lt: string) => searchStore.formatLightingType(lt)
const getOperatingZoneLabel = (zone: string) => searchStore.formatOperatingZone(zone)
const getVariantLabel = (variant: string, type: string) => searchStore.getVariantLabel(variant, type)

</script>

<template>
  <div class="specifications-grid">
    <div v-if="showDimensions" class="spec-item">
      <div class="spec-label">Wymiary</div>
      <div v-if="ad.type === 'led_screen'" class="spec-value">{{ (ad.width * 1000).toFixed(0) }}mm × {{ (ad.height * 1000).toFixed(0) }}mm</div>
      <div v-else class="spec-value">{{ ad.width }}m × {{ ad.height }}m</div>
    </div>

    <div v-if="showDimensions && showSurfaceArea" class="spec-item">
      <div class="spec-label">Powierzchnia</div>
      <div class="spec-value">{{ surfaceArea }} m²</div>
    </div>

    <div v-if="ad.variant" class="spec-item">
      <div class="spec-label">Wariant</div>
      <div class="spec-value">{{ getVariantLabel(ad.variant, ad.type) }}</div>
    </div>

    <div v-if="ad.estimated_daily_views" class="spec-item">
      <div class="spec-label">Zasięg dzienny (OTS)</div>
      <div class="spec-value spec-premium">{{ ad.estimated_daily_views.toLocaleString('pl-PL') }} osób</div>
    </div>

    <div v-if="ad.type === 'billboard' && ad.road_class" class="spec-item">
      <div class="spec-label">Klasa drogi</div>
      <div class="spec-value">{{ getRoadClassLabel(ad.road_class) }}</div>
    </div>

    <div v-if="locationTier" class="spec-item">
      <div class="spec-label">Klasa lokalizacji</div>
      <div class="spec-value" :class="{ 'spec-premium': locationTier === 'PREMIUM', 'spec-standard': locationTier === 'STANDARD' }">
        {{ locationTier }}
      </div>
    </div>

    <div v-if="ad.traffic_direction" class="spec-item">
      <div class="spec-label">Kierunek ruchu</div>
      <div class="spec-value">{{ formatTrafficDirection }}</div>
    </div>

    <div v-if="ad.traffic_type" class="spec-item">
      <div class="spec-label">Rodzaj ruchu</div>
      <div class="spec-value">{{ formatTrafficType }}</div>
    </div>

    <div v-if="ad.environment" class="spec-item">
      <div class="spec-label">Środowisko</div>
      <div class="spec-value">{{ getEnvironmentLabel(ad.environment) }}</div>
    </div>

    <div v-if="ad.type === 'billboard' && (ad as any).lighting_type" class="spec-item">
      <div class="spec-label">Typ oświetlenia</div>
      <div class="spec-value">{{ getLightingTypeLabel((ad as any).lighting_type) }}</div>
    </div>

    <div v-if="['banner', 'wall'].includes(ad.type) && (ad as any).lighting_type_banner" class="spec-item">
      <div class="spec-label">Typ oświetlenia</div>
      <div class="spec-value">{{ getLightingTypeLabel((ad as any).lighting_type_banner) }}</div>
    </div>

    <div v-if="ad.type === 'transport' && (ad as any).daily_passengers" class="spec-item">
      <div class="spec-label">Liczba pasażerów dziennie</div>
      <div class="spec-value">{{ (ad as any).daily_passengers }}</div>
    </div>

    <div v-if="ad.type === 'mobile' && (ad as any).operating_zone" class="spec-item">
      <div class="spec-label">Strefa operacyjna</div>
      <div class="spec-value">{{ getOperatingZoneLabel((ad as any).operating_zone) }}</div>
    </div>

    <div v-if="ad.type === 'led_screen' && (ad as any).resolution" class="spec-item">
      <div class="spec-label">Rozdzielczość</div>
      <div class="spec-value">{{ (ad as any).resolution }}</div>
    </div>

    <div v-if="ad.type === 'led_screen' && (ad as any).pixel_pitch" class="spec-item">
      <div class="spec-label">Pixel Pitch</div>
      <div class="spec-value">{{ (ad as any).pixel_pitch }} mm</div>
    </div>

    <div v-if="ad.type === 'led_screen' && (ad as any).brightness" class="spec-item">
      <div class="spec-label">Jasność</div>
      <div class="spec-value">{{ (ad as any).brightness }} nits</div>
    </div>

    <div v-if="ad.type === 'led_screen' && (ad as any).ambient_light_control" class="spec-item">
      <div class="spec-label">Dostosowanie do otoczenia</div>
      <div class="spec-value spec-yes">Tak</div>
    </div>

    <div v-if="ad.price_unit === 'campaign' && ad.campaign_duration" class="spec-item">
      <div class="spec-label">Czas trwania kampanii</div>
      <div class="spec-value">{{ ad.campaign_duration }} dni</div>
    </div>

    <div v-if="ad.type === 'transport' && ad.transport_scope" class="spec-item">
      <div class="spec-label">Zakres</div>
      <div class="spec-value">{{ getTransportScopeLabel(ad.transport_scope) }}</div>
    </div>

    <div v-if="ad.type === 'transport' && ad.vehicle_count" class="spec-item">
      <div class="spec-label">Liczba pojazdów</div>
      <div class="spec-value">{{ ad.vehicle_count }}</div>
    </div>

    <div v-if="ad.type === 'mobile' && ad.mobile_exposure_mode" class="spec-item">
      <div class="spec-label">Tryb ekspozycji</div>
      <div class="spec-value">{{ getMobileExposureModeLabel(ad.mobile_exposure_mode) }}</div>
    </div>

    <div v-if="ad.type === 'mobile' && ad.operating_hours" class="spec-item">
      <div class="spec-label">Godziny działania</div>
      <div class="spec-value">{{ ad.operating_hours }}</div>
    </div>

    <div v-if="ad.type === 'mobile' && ad.route_area" class="spec-item">
      <div class="spec-label">Trasa / Obszar</div>
      <div class="spec-value">{{ ad.route_area }}</div>
    </div>

    <div v-if="ad.has_backlight" class="spec-item">
      <div class="spec-label">Podświetlenie</div>
      <div class="spec-value spec-yes">Tak</div>
    </div>

    <div v-if="ad.price_includes_print" class="spec-item">
      <div class="spec-label">Druk w cenie</div>
      <div class="spec-value spec-yes">Tak</div>
    </div>

    <div v-if="ad.price_includes_mounting" class="spec-item">
      <div class="spec-label">Montaż w cenie</div>
      <div class="spec-value spec-yes">Tak</div>
    </div>

    <div v-if="ad.graphic_design_help" class="spec-item">
      <div class="spec-label">Pomoc graficzna</div>
      <div class="spec-value spec-yes">Dostępna</div>
    </div>

    <div class="spec-item">
      <div class="spec-label">Rodzaj oferty</div>
      <div class="spec-value">{{ ad.offer_type === 'owner' ? 'Właściciel (bezpośrednio)' : ad.offer_type === 'agency' ? 'Agencja reklamowa' : ad.offer_type === 'sublease' ? 'Podnajmujący' : ad.offer_type }}</div>
    </div>

    <div class="spec-item" v-if="ad.has_vat_invoice">
      <div class="spec-label">Faktura VAT</div>
      <div class="spec-value spec-yes">Tak</div>
    </div>
  </div>
</template>

<style scoped>
.specifications-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1.5rem;
  background: var(--card-bg, white);
  padding: 2.5rem;
  border-radius: 20px;
  box-shadow: var(--card-shadow, 0 4px 6px -1px rgba(0, 0, 0, 0.1));
}

.spec-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.spec-label {
  font-size: 0.85rem;
  color: var(--text-muted, #6b7280);
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.spec-value {
  font-size: 1.1rem;
  color: var(--text-main, #1f2937);
  font-weight: 700;
}

.spec-premium {
  color: #7c3aed;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

.spec-yes {
  color: #059669;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.25rem 0.75rem;
  background: #ecfdf5;
  border-radius: 20px;
  font-size: 0.95rem;
  transition: transform 0.2s ease, background-color 0.2s ease;
  width: fit-content;
}

.spec-yes:hover {
  background: #d1fae5;
  transform: translateY(-1px);
}

.spec-yes::before {
  content: '✓';
  font-weight: 800;
}

.spec-standard {
  color: #3b82f6;
}

@media (max-width: 768px) {
  .specifications-grid {
    grid-template-columns: 1fr;
    padding: 1.5rem;
    gap: 1.25rem;
  }
  
  .spec-item {
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 0.75rem;
  }
  
  .spec-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }
  
  .spec-value {
    font-size: 1.05rem;
  }
}
</style>
