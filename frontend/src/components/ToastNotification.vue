<script setup lang="ts">
import { ref } from 'vue'

interface Toast {
  id: number
  message: string
  type: 'success' | 'error' | 'info'
}

const toasts = ref<Toast[]>([])
let nextId = 0

const add = (message: string, type: 'success' | 'error' | 'info' = 'info', duration: number = 5000) => {
  const id = nextId++
  toasts.value.push({ id, message, type })
  setTimeout(() => remove(id), duration)
}

const remove = (id: number) => {
  toasts.value = toasts.value.filter(t => t.id !== id)
}

defineExpose({ add })
</script>

<template>
  <Teleport to="body">
    <div class="toast-container">
      <TransitionGroup name="toast">
        <div v-for="toast in toasts" :key="toast.id" class="toast" :class="toast.type">
          <div class="toast-content">
            <div class="toast-icon">
              <!-- ... existing icons ... -->
              <svg v-if="toast.type === 'success'" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M22 11.08V12a10 10 0 11-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M22 4L12 14.01l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <svg v-else-if="toast.type === 'error'" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                <line x1="12" y1="8" x2="12" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <line x1="12" y1="16" x2="12.01" y2="16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
              <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                <path d="M12 16v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M12 8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </div>
            <span class="toast-message">{{ toast.message }}</span>
            <button @click="remove(toast.id)" class="toast-close">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>
          <div class="toast-progress"></div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.toast-container {
  position: fixed;
  top: 90px;
  right: 24px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  z-index: 9999;
  pointer-events: none;
}

.toast {
  position: relative;
  border-radius: 12px;
  background: var(--card-bg, white);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  min-width: 320px;
  max-width: 450px;
  pointer-events: auto;
  backdrop-filter: blur(10px);
  overflow: hidden;
}

.toast-content {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
}

.toast.success {
  border-left: 6px solid #10B981;
}
.toast.success .toast-icon {
  color: #10B981;
}

.toast.error {
  border-left: 6px solid #EF4444;
}
.toast.error .toast-icon {
  color: #EF4444;
}

.toast.info {
  border-left-color: #3B82F6;
  background: rgba(255, 255, 255, 0.95);
}
.toast.info .toast-icon {
  color: #3B82F6;
}

.toast-message {
  flex: 1;
  font-size: 0.95rem;
  color: #1F2937;
  font-weight: 600;
  line-height: 1.4;
  white-space: pre-wrap; /* Allow line breaks */
}

.toast-close {
  background: transparent;
  border: none;
  cursor: pointer;
  color: #9CA3AF;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.2s;
}

.toast-close:hover {
  background: #F3F4F6;
  color: #4B5563;
}

.toast-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 4px;
  width: 100%;
  background: rgba(0, 0, 0, 0.1);
  transform-origin: left;
  animation: progress 5s linear forwards;
}

.toast.success .toast-progress { background: #10B981; }
.toast.error .toast-progress { background: #EF4444; }
.toast.info .toast-progress { background: #3B82F6; }

@keyframes progress {
  from { transform: scaleX(1); }
  to { transform: scaleX(0); }
}

/* Transitions */
.toast-enter-active,
.toast-leave-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%) scale(0.9);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%) scale(0.9);
}
</style>
