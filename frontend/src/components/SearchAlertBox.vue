<script setup lang="ts">
const props = defineProps<{
  locationLabel: string
  adTypeLabel: string
}>()

const emit = defineEmits<{
  click: []
}>()
</script>

<template>
  <div class="alert-box">
    <div class="alert-content">
      <div class="alert-info">
        <div class="alert-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
            <path d="M18 8C18 6.4087 17.3679 4.88258 16.2426 3.75736C15.1174 2.63214 13.5913 2 12 2C10.4087 2 8.88258 2.63214 7.75736 3.75736C6.63214 4.88258 6 6.4087 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div class="alert-text">
          <h3 class="alert-title">Nic nie wpadło Ci w oko?</h3>
          <p class="alert-desc">
            Zapisz to wyszukiwanie, a wyślemy Ci wiadomość, gdy tylko pojawią się nowe
            <strong>{{ adTypeLabel || 'ogłoszenie' }}</strong> 
            {{ locationLabel ? 'w: ' + locationLabel : 'w tej okolicy' }}.
          </p>
        </div>
      </div>
      <button @click="emit('click')" class="alert-btn">
        Powiadom mnie o nowościach
      </button>
    </div>
  </div>
</template>

<style scoped>
.alert-box {
  background: var(--card-bg, white);
  border-radius: 16px;
  padding: 2rem;
  border: 2px dashed var(--border-color, #e5e7eb);
  margin-top: 2rem;
  margin-bottom: 4rem;
  transition: all 0.3s ease;
  color: var(--text-main, #111827);
}

.alert-box:hover {
  border-color: var(--primary-color, #667eea);
  background: var(--bg-secondary, #f8faff);
}


.alert-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
}

.alert-info {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.alert-icon {
  width: 48px;
  height: 48px;
  background: var(--bg-tertiary, #f3f4f6);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary-color, #667eea);
  flex-shrink: 0;
}


.alert-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--text-main, #111827);
  margin-bottom: 0.25rem;
}

.alert-desc {
  color: var(--text-muted, #6b7280);
  font-size: 0.95rem;
  margin: 0;
  line-height: 1.5;
}

.alert-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.875rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s;
  box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.2);
}

.alert-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.3);
}


@media (max-width: 768px) {
  .alert-content {
    flex-direction: column;
    text-align: center;
    gap: 1.5rem;
  }
  
  .alert-info {
    flex-direction: column;
    gap: 1rem;
  }
  
  .alert-btn {
    width: 100%;
  }
}
</style>
