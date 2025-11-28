<script setup lang="ts">
import { ref } from 'vue'

interface Props {
  title?: string
  message: string
  confirmText?: string
  cancelText?: string
  type?: 'danger' | 'warning' | 'info'
}

withDefaults(defineProps<Props>(), {
  title: 'Potwierdź',
  confirmText: 'Tak',
  cancelText: 'Anuluj',
  type: 'warning'
})

const emit = defineEmits<{
  confirm: []
  cancel: []
}>()

const isOpen = ref(false)

const open = () => {
  isOpen.value = true
}

const close = () => {
  isOpen.value = false
}

const handleConfirm = () => {
  emit('confirm')
  close()
}

const handleCancel = () => {
  emit('cancel')
  close()
}

defineExpose({
  open,
  close
})
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="isOpen" class="modal-overlay" @click.self="handleCancel">
        <div class="modal-content" @click.stop>
          <div class="modal-body">
            <div class="icon-wrapper" :class="type">
              <svg v-if="type === 'danger'" width="48" height="48" viewBox="0 0 48 48" fill="none">
                <rect width="48" height="48" rx="24" fill="#FEE2E2"/>
                <path d="M24 16v8m0 4h.01" stroke="#DC2626" stroke-width="3" stroke-linecap="round"/>
                <circle cx="24" cy="24" r="10" stroke="#DC2626" stroke-width="2"/>
              </svg>
              <svg v-else-if="type === 'warning'" width="48" height="48" viewBox="0 0 48 48" fill="none">
                <rect width="48" height="48" rx="24" fill="#FEF3C7"/>
                <path d="M24 16v8m0 4h.01" stroke="#D97706" stroke-width="3" stroke-linecap="round"/>
                <path d="M24 12L10 32h28L24 12z" stroke="#D97706" stroke-width="2" fill="none"/>
              </svg>
              <svg v-else width="48" height="48" viewBox="0 0 48 48" fill="none">
                <rect width="48" height="48" rx="24" fill="#DBEAFE"/>
                <circle cx="24" cy="24" r="10" stroke="#2563EB" stroke-width="2"/>
                <path d="M24 20v8m0-12h.01" stroke="#2563EB" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </div>

            <h2 class="modal-title">{{ title }}</h2>
            <p class="modal-message">{{ message }}</p>

            <div class="modal-actions">
              <button @click="handleCancel" class="btn-cancel">
                {{ cancelText }}
              </button>
              <button @click="handleConfirm" class="btn-confirm" :class="type">
                {{ confirmText }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 16px;
  padding: 2.5rem;
  max-width: 420px;
  width: 100%;
  position: relative;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
}

.modal-body {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.icon-wrapper {
  margin-bottom: 1.5rem;
}

.modal-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1F2937;
  margin: 0 0 0.75rem 0;
}

.modal-message {
  font-size: 1rem;
  color: #6B7280;
  line-height: 1.6;
  margin: 0 0 2rem 0;
}

.modal-actions {
  display: flex;
  gap: 0.75rem;
  width: 100%;
}

.btn-cancel,
.btn-confirm {
  flex: 1;
  padding: 0.875rem 1.5rem;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
}

.btn-cancel {
  background: #F3F4F6;
  color: #374151;
}

.btn-cancel:hover {
  background: #E5E7EB;
}

.btn-confirm {
  color: white;
}

.btn-confirm.danger {
  background: linear-gradient(135deg, #DC2626 0%, #B91C1C 100%);
}

.btn-confirm.danger:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(220, 38, 38, 0.3);
}

.btn-confirm.warning {
  background: linear-gradient(135deg, #D97706 0%, #B45309 100%);
}

.btn-confirm.warning:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(217, 119, 6, 0.3);
}

.btn-confirm.info {
  background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
}

.btn-confirm.info:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
}

/* Animations */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-active .modal-content,
.modal-leave-active .modal-content {
  transition: transform 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
  transform: scale(0.9);
}

@media (max-width: 640px) {
  .modal-content {
    padding: 2rem 1.5rem;
  }

  .modal-title {
    font-size: 1.25rem;
  }

  .modal-actions {
    flex-direction: column;
  }
}
</style>
