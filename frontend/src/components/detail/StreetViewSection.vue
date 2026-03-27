<script setup lang="ts">
const props = defineProps<{
  showStreetView: boolean
  streetViewUrl: string
  streetViewLoading: boolean
  streetViewError: boolean
}>()

const emit = defineEmits<{
  'toggle-street-view': []
  'handle-street-view-error': []
  'handle-street-view-load': [event: Event]
}>()
</script>

<template>
  <div class="street-view-section">
    <div class="street-view-header">
      <button 
        v-if="!showStreetView" 
        @click="$emit('toggle-street-view')"
        class="btn btn-secondary street-view-toggle"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z" fill="currentColor"/>
        </svg>
        Pokaż Street View
      </button>
      <button 
        v-else 
        @click="$emit('toggle-street-view')"
        class="btn btn-secondary street-view-toggle"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z" fill="currentColor"/>
        </svg>
        Ukryj Street View
      </button>
    </div>

    <!-- Iframe always in DOM - never removed to prevent reloading -->
    <div
      class="street-view-cached-iframe"
      :style="{ display: streetViewUrl && !showStreetView ? 'none' : 'block' }"
    >
      <iframe
        v-if="streetViewUrl"
        :src="streetViewUrl"
        width="100%"
        height="400"
        style="border: none; border-radius: 8px;"
        allowfullscreen
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        @error="$emit('handle-street-view-error')"
        @load="$emit('handle-street-view-load', $event)"
      ></iframe>
    </div>

    <div v-if="showStreetView" class="street-view-container">
      <div v-if="streetViewError" class="street-view-error">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
          <path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <div>
          <h3>Street View niedostępny</h3>
          <p>Niestety, Google Street View nie jest dostępny dla tej lokalizacji.</p>
          <button @click="$emit('toggle-street-view')" class="street-view-error-close">
            Zamknij
          </button>
        </div>
      </div>
      <div v-else class="street-view-iframe-wrapper">
        <p class="street-view-info">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
            <path d="M12 16v-4M12 8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          Wirtualny spacer (Google Street View) pozwala zobaczyć lokalizację powierzchni reklamowej z perspektywy ulicy.
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.street-view-section {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.street-view-header {
  display: flex;
  justify-content: flex-end;
}

.street-view-toggle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  font-weight: 700;
  border-radius: 12px;
  background: var(--primary-gradient, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
  color: white;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.street-view-toggle:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.street-view-toggle:active {
  transform: translateY(0);
}

.street-view-cached-iframe {
  width: 100%;
}

.street-view-container {
  border-radius: 12px;
  overflow: hidden;
  background: white;
}

.street-view-error {
  display: flex;
  gap: 1.5rem;
  padding: 2rem;
  background: #fef2f2;
  border-radius: 12px;
  color: #991b1b;
}

.street-view-error-close {
  margin-top: 1rem;
  padding: 0.5rem 1rem;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 600;
}

.street-view-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: #6b7280;
  margin-top: 0.5rem;
}
</style>
