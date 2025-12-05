<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

defineProps<{
  isOpen: boolean
}>()

const emit = defineEmits<{
  close: []
}>()

const router = useRouter()

const email = ref('')
const isSubmitting = ref(false)
const isSuccess = ref(false)

const handleSubmit = async () => {
  if (!email.value || !email.value.includes('@')) {
    return
  }

  isSubmitting.value = true

  await new Promise(resolve => setTimeout(resolve, 1000))

  isSuccess.value = true
  isSubmitting.value = false

  setTimeout(() => {
    handleClose()
  }, 3000)
}

const handleClose = () => {
  email.value = ''
  isSuccess.value = false
  emit('close')
}

const navigateToManagement = () => {
  handleClose()
  router.push('/zarzadzaj')
}
</script>

<template>
  <Transition name="modal">
    <div v-if="isOpen" class="modal-overlay" @click="handleClose">
      <div class="modal-content" @click.stop>
        <button class="close-btn" @click="handleClose">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </button>

        <div v-if="!isSuccess" class="modal-body">
          <div class="icon-wrapper">
            <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect width="48" height="48" rx="24" fill="#EEF2FF"/>
              <path d="M14 18L24 24L34 18M14 18V30L24 36L34 30V18M14 18L24 12L34 18" stroke="#4F46E5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>

          <h2 class="modal-title">Panel zarządzania ogłoszeniami</h2>
          <p class="modal-description">
            Podaj swój adres e-mail, a wyślemy Ci link do Twojego panelu zarządzania.
          </p>

          <form @submit.prevent="handleSubmit" class="email-form">
            <div class="input-wrapper">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 4H17C17.55 4 18 4.45 18 5V15C18 15.55 17.55 16 17 16H3C2.45 16 2 15.55 2 15V5C2 4.45 2.45 4 3 4Z" stroke="#4F46E5" stroke-width="1.5"/>
                <path d="M18 5L10 11L2 5" stroke="#4F46E5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <input
                v-model="email"
                type="email"
                placeholder="twoj@email.com"
                required
                class="email-input"
              />
            </div>

            <button type="submit" :disabled="isSubmitting" class="submit-btn">
              <span v-if="!isSubmitting">Wyślij link</span>
              <span v-else class="loading">Wysyłam...</span>
            </button>
          </form>

          <p class="info-text">
            Link będzie ważny przez 24 godziny
          </p>

          <div class="divider">
            <span>lub</span>
          </div>

          <button @click="navigateToManagement" class="test-btn" type="button">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z" fill="currentColor"/>
            </svg>
            Przejdź do panelu (testy)
          </button>
        </div>

        <div v-else class="success-body">
          <div class="success-icon">
            <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="32" cy="32" r="32" fill="#10B981" opacity="0.1"/>
              <circle cx="32" cy="32" r="24" fill="#10B981"/>
              <path d="M22 32L28 38L42 24" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <h2 class="success-title">Link został wysłany!</h2>
          <p class="success-description">
            Sprawdź swoją skrzynkę odbiorczą na adresie <strong>{{ email }}</strong>
          </p>
        </div>
      </div>
    </div>
  </Transition>
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
  z-index: 2000;
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 16px;
  padding: 2.5rem;
  max-width: 480px;
  width: 100%;
  position: relative;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
}

.close-btn {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: transparent;
  border: none;
  color: #6B7280;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 8px;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-btn:hover {
  background: #F3F4F6;
  color: #1F2937;
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
  font-size: 1.75rem;
  font-weight: 700;
  color: #1F2937;
  margin: 0 0 1rem 0;
}

.modal-description {
  font-size: 1rem;
  color: #6B7280;
  line-height: 1.6;
  margin: 0 0 2rem 0;
}

.email-form {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-wrapper svg {
  position: absolute;
  left: 1rem;
  pointer-events: none;
}

.email-input {
  width: 100%;
  padding: 1rem 1rem 1rem 3rem;
  border: 2px solid #E5E7EB;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.email-input:focus {
  outline: none;
  border-color: #4F46E5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.submit-btn {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.submit-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
}

.submit-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.loading {
  display: inline-block;
  animation: pulse 1.5s ease-in-out infinite;
}

.info-text {
  margin-top: 1rem;
  font-size: 0.875rem;
  color: #9CA3AF;
}

.divider {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 1rem;
  margin: 1.5rem 0;
  color: #9CA3AF;
  font-size: 0.875rem;
}

.divider::before,
.divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #E5E7EB;
}

.test-btn {
  width: 100%;
  padding: 0.875rem;
  background: white;
  color: #6B7280;
  border: 2px solid #E5E7EB;
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.test-btn:hover {
  border-color: #667eea;
  color: #667eea;
  background: #f5f3ff;
  transform: translateY(-1px);
}

.success-body {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.success-icon {
  margin-bottom: 1.5rem;
  animation: scaleIn 0.5s ease-out;
}

.success-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1F2937;
  margin: 0 0 1rem 0;
}

.success-description {
  font-size: 1rem;
  color: #6B7280;
  line-height: 1.6;
  margin: 0;
}

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

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

@keyframes scaleIn {
  0% {
    transform: scale(0);
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
  }
}

@media (max-width: 640px) {
  .modal-content {
    padding: 2rem 1.5rem;
  }

  .modal-title, .success-title {
    font-size: 1.5rem;
  }
}
</style>
