<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import FeedbackModal from './FeedbackModal.vue'

const feedbackModal = ref<InstanceType<typeof FeedbackModal> | null>(null)
const showTooltip = ref(false)
const tooltipDismissed = ref(false)
let tooltipTimer: number | null = null

const openFeedback = () => {
  feedbackModal.value?.openModal()
  dismissTooltip()
}

const dismissTooltip = () => {
  showTooltip.value = false
  tooltipDismissed.value = true
  if (typeof window !== 'undefined') {
    localStorage.setItem('feedbackTooltipDismissed', 'true')
  }
}

onMounted(() => {
  // Sprawdź czy tooltip był już zamknięty
  if (typeof window !== 'undefined') {
    const dismissed = localStorage.getItem('feedbackTooltipDismissed')
    if (dismissed) {
      tooltipDismissed.value = true
      return
    }
  }
  
  // Pokaż tooltip po 5 sekundach
  tooltipTimer = window.setTimeout(() => {
    if (!tooltipDismissed.value) {
      showTooltip.value = true
    }
  }, 5000)
})

onUnmounted(() => {
  if (tooltipTimer) {
    clearTimeout(tooltipTimer)
  }
})
</script>

<template>
  <div class="feedback-container">
    <Transition name="tooltip">
      <div v-if="showTooltip" class="feedback-tooltip">
        <button @click="dismissTooltip" class="tooltip-close" aria-label="Zamknij">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
            <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </button>
        <div class="tooltip-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div class="tooltip-content">
          <strong>Pomóż nam ulepszyć platformę!</strong>
          <p>Zgłoś błąd lub zaproponuj nową funkcję</p>
        </div>
        <div class="tooltip-arrow"></div>
      </div>
    </Transition>

    <button @click="openFeedback" class="feedback-button" aria-label="Wyślij feedback">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>

    <FeedbackModal ref="feedbackModal" />
  </div>
</template>

<style scoped>
.feedback-container {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  z-index: 1000;
}

.feedback-button {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 1rem;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  font-weight: 600;
  font-size: 1rem;
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
  transition: all 0.3s ease;
  width: 56px;
  height: 56px;
}

.feedback-button:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(102, 126, 234, 0.5);
}

.feedback-button:active {
  transform: translateY(-2px);
}

.feedback-tooltip {
  position: absolute;
  bottom: calc(100% + 0.75rem);
  right: 0;
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  padding: 0.875rem 1rem;
  min-width: 285px;
  max-width: 285px;
  border: 1px solid #e5e7eb;
}

.tooltip-icon {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.625rem;
  color: white;
}

.tooltip-icon svg {
  width: 16px;
  height: 16px;
}

.tooltip-content {
  position: relative;
}

.tooltip-content strong {
  display: block;
  color: #1f2937;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
  line-height: 1.4;
  font-weight: 700;
}

.tooltip-content p {
  margin: 0;
  color: #6b7280;
  font-size: 0.8125rem;
  line-height: 1.4;
  font-weight: 500;
}

.tooltip-close {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  background: transparent;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s;
}

.tooltip-close:hover {
  background: #f3f4f6;
  color: #6b7280;
}

.tooltip-arrow {
  position: absolute;
  bottom: -6px;
  right: 20px;
  width: 12px;
  height: 12px;
  background: white;
  transform: rotate(45deg);
  border-right: 1px solid #e5e7eb;
  border-bottom: 1px solid #e5e7eb;
}

/* Animacje tooltipa */
.tooltip-enter-active {
  animation: tooltipIn 0.4s ease-out;
}

.tooltip-leave-active {
  animation: tooltipOut 0.3s ease-in;
}

@keyframes tooltipIn {
  from {
    opacity: 0;
    transform: translateY(10px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes tooltipOut {
  from {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
  to {
    opacity: 0;
    transform: translateY(10px) scale(0.95);
  }
}

@media (max-width: 768px) {
  .feedback-container {
    bottom: 1rem;
    right: 1rem;
  }

  .feedback-button {
    padding: 0.875rem;
    width: 48px;
    height: 48px;
  }
  
  .feedback-button svg {
    width: 20px;
    height: 20px;
  }

  .feedback-tooltip {
    min-width: 200px;
    max-width: 220px;
    padding: 0.75rem 0.875rem;
  }

  .tooltip-icon {
    width: 28px;
    height: 28px;
    margin-bottom: 0.5rem;
  }

  .tooltip-icon svg {
    width: 14px;
    height: 14px;
  }

  .tooltip-content strong {
    font-size: 0.8125rem;
  }

  .tooltip-content p {
    font-size: 0.75rem;
  }

  .tooltip-close {
    top: 0.5rem;
    right: 0.5rem;
    padding: 0.1875rem;
  }

  .tooltip-close svg {
    width: 12px;
    height: 12px;
  }

  .tooltip-arrow {
    width: 10px;
    height: 10px;
    bottom: -5px;
  }
}
</style>
