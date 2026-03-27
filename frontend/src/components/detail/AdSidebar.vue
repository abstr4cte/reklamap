<script setup lang="ts">
import { } from 'vue'
import type { Advertisement } from '../../types'

const props = defineProps<{
  ad: Advertisement
  displayViews: number
  statusLabel: string
  statusClass: string
  isFavorite: boolean
  isInComparison: boolean
  isGeneratingPDF: boolean
  showPhone: boolean
  showActionsMenu: boolean
}>()

const emit = defineEmits<{
  'toggle-favorite': []
  'toggle-comparison': []
  'handle-print': []
  'handle-download-pdf': []
  'handle-share': []
  'handle-show-phone': []
  'open-report-modal': []
  'toggle-actions-menu': []
}>()

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('pl-PL')
}

const getMaskedPhone = (phone: string) => {
  if (!phone) return '+48 XXX XXX XXX'
  let cleaned = phone.replace(/\D/g, '')
  if (cleaned.startsWith('48') && cleaned.length === 11) cleaned = cleaned.slice(2)
  if (cleaned.length < 9) return phone
  return `+48 ${cleaned.slice(0, 3)} XXX XXX`
}

const getFullPhone = (phone: string) => {
  if (!phone) return ''
  let cleaned = phone.replace(/\D/g, '')
  if (cleaned.startsWith('48') && cleaned.length === 11) cleaned = cleaned.slice(2)
  return `+48 ${cleaned.slice(0, 3)} ${cleaned.slice(3, 6)} ${cleaned.slice(6)}`
}

</script>

<template>
  <div class="sidebar-card">
    <div class="status-badge" :class="statusClass">
      {{ statusLabel }}
    </div>

    <div class="sidebar-info">
      <div class="info-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
          <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <span>Dodano: {{ formatDate(ad.created_at) }}</span>
      </div>
      <div class="info-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/>
          <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
        </svg>
        <span>{{ displayViews }} wyświetleń</span>
      </div>
    </div>

    <div v-if="ad.phone && ad.phone.trim()" class="phone-section">
      <button v-if="!showPhone" @click="$emit('handle-show-phone')" class="btn btn-phone">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="2"/>
        </svg>
        {{ getMaskedPhone(ad.phone) }}
      </button>
      <div v-else class="phone-display">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="2"/>
        </svg>
        <a :href="`tel:${ad.phone}`" class="phone-number">{{ getFullPhone(ad.phone) }}</a>
      </div>
    </div>

    <!-- Actions List -->
    <div class="actions-list">
      <button @click="$emit('toggle-favorite')" class="action-btn" :class="{ active: isFavorite }">
        <svg width="20" height="20" viewBox="0 0 24 24" :fill="isFavorite ? '#EF4444' : 'none'">
          <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" :stroke="isFavorite ? '#EF4444' : 'currentColor'" stroke-width="2"/>
        </svg>
        {{ isFavorite ? 'Usuń z ulubionych' : 'Dodaj do ulubionych' }}
      </button>

      <button @click="$emit('toggle-comparison')" class="action-btn" :class="{ active: isInComparison }">
        <svg width="20" height="20" viewBox="0 0 24 24" :fill="isInComparison ? '#667eea' : 'none'">
          <rect x="3" y="3" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
          <rect x="14" y="3" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
          <rect x="3" y="14" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
          <rect x="14" y="14" width="7" height="7" :stroke="isInComparison ? '#667eea' : 'currentColor'" stroke-width="2" rx="1"/>
        </svg>
        {{ isInComparison ? 'Usuń z porównania' : 'Dodaj do porównania' }}
      </button>

      <div class="actions-divider"></div>

      <button @click="$emit('handle-print')" class="action-btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M6 14h12v8H6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Drukuj ofertę
      </button>

      <button @click="$emit('handle-download-pdf')" class="action-btn" :disabled="isGeneratingPDF">
        <svg v-if="isGeneratingPDF" class="animate-spin" width="20" height="20" viewBox="0 0 24 24" fill="none">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        {{ isGeneratingPDF ? 'Generowanie...' : 'Pobierz PDF' }}
      </button>

      <button @click="$emit('handle-share')" class="action-btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <circle cx="18" cy="5" r="3" stroke="currentColor" stroke-width="2"/>
          <circle cx="6" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
          <circle cx="18" cy="19" r="3" stroke="currentColor" stroke-width="2"/>
          <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" stroke="currentColor" stroke-width="2"/>
          <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" stroke="currentColor" stroke-width="2"/>
        </svg>
        Udostępnij
      </button>

      <button @click="$emit('open-report-modal')" class="action-btn report-btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Zgłoś błąd / naruszenie
      </button>
    </div>
  </div>
</template>

<style scoped>
.sidebar-card {
  background: var(--card-bg, white);
  border-radius: 20px;
  padding: 2rem;
  box-shadow: var(--card-shadow, 0 10px 15px -3px rgba(0, 0, 0, 0.1));
  position: sticky;
  top: 100px;
}

.status-badge {
  display: block;
  width: 100%;
  text-align: center;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  font-weight: 700;
  font-size: 1rem;
  margin-bottom: 1.5rem;
}

.status-active { background: #ecfdf5; color: #059669; }
.status-reserved { background: #fffbeb; color: #d97706; }
.status-unavailable { background: #fef2f2; color: #dc2626; }
.status-soon { background: #eff6ff; color: #2563eb; }

.sidebar-info {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 2rem;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--text-muted, #6b7280);
  font-size: 0.95rem;
}

.phone-section {
  margin-bottom: 2rem;
}

.btn-phone {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 1rem;
  background: #10B981;
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 700;
  transition: all 0.2s;
}

.btn-phone:hover {
  background: #059669;
  transform: translateY(-2px);
}

.phone-display {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 1rem;
  background: var(--bg-tertiary, #f3f4f6);
  border-radius: 12px;
  font-weight: 700;
  font-size: 1.1rem;
  color: var(--text-main, #111827);
}

.phone-number {
  color: inherit;
  text-decoration: none;
}

.actions-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
  padding: 0.875rem 1rem;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 10px;
  color: var(--text-muted, #4b5563);
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn:hover {
  background: var(--bg-secondary, #f9fafb);
  color: var(--text-main, #111827);
}

.action-btn.active {
  background: var(--bg-tertiary, #f3f4f6);
  color: var(--text-main, #111827);
}

.action-btn.active.is-favorite { color: #ef4444; }

.actions-divider {
  height: 1px;
  background: var(--border-color, #e5e7eb);
  margin: 0.5rem 0;
}

.report-btn {
  color: #9ca3af;
  font-size: 0.85rem;
  margin-top: 1rem;
}

.report-btn:hover {
  color: #dc2626;
  background: #fef2f2;
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@media (max-width: 1024px) {
  .sidebar-card {
    position: static;
    margin-top: 2rem;
  }
}
</style>
