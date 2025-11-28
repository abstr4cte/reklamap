<script setup lang="ts">
import { ref } from 'vue'

const isOpen = ref(false)
const feedbackType = ref('suggestion')
const email = ref('')
const message = ref('')
const isSubmitting = ref(false)
const showSuccess = ref(false)

const emit = defineEmits<{
  close: []
}>()

const openModal = () => {
  isOpen.value = true
}

const closeModal = () => {
  isOpen.value = false
  emit('close')
}

const handleSubmit = async () => {
  if (!message.value.trim()) {
    return
  }

  isSubmitting.value = true

  // TODO: Wysłać na backend
  const feedbackData = {
    type: feedbackType.value,
    email: email.value || null,
    message: message.value,
    timestamp: new Date().toISOString(),
    userAgent: navigator.userAgent,
    url: window.location.href
  }

  console.log('Feedback data to send:', feedbackData)

  // Symulacja wysyłki
  await new Promise(resolve => setTimeout(resolve, 500))

  isSubmitting.value = false
  showSuccess.value = true

  // Reset form
  setTimeout(() => {
    feedbackType.value = 'suggestion'
    email.value = ''
    message.value = ''
    showSuccess.value = false
    closeModal()
  }, 2000)
}

defineExpose({
  openModal
})
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="isOpen" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content" @click.stop>
          <button @click="closeModal" class="close-btn" aria-label="Zamknij">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
              <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>

          <div v-if="!showSuccess" class="modal-body">
            <div class="icon-wrapper">
              <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                <rect width="48" height="48" rx="24" fill="#EEF2FF"/>
                <path d="M24 14v10m0 4h.01" stroke="#4F46E5" stroke-width="3" stroke-linecap="round"/>
                <circle cx="24" cy="24" r="10" stroke="#4F46E5" stroke-width="2"/>
              </svg>
            </div>

            <h2 class="modal-title">Wyślij feedback</h2>
            <p class="modal-description">
              Pomóż nam ulepszyć platformę! Zgłoś błąd, zaproponuj nową funkcję lub zadaj pytanie.
            </p>

            <form @submit.prevent="handleSubmit" class="feedback-form">
              <div class="type-selector">
                <button
                  type="button"
                  @click="feedbackType = 'bug'"
                  :class="{ active: feedbackType === 'bug' }"
                  class="type-btn"
                >
                  🐛 Błąd
                </button>
                <button
                  type="button"
                  @click="feedbackType = 'suggestion'"
                  :class="{ active: feedbackType === 'suggestion' }"
                  class="type-btn"
                >
                  💡 Sugestia
                </button>
                <button
                  type="button"
                  @click="feedbackType = 'question'"
                  :class="{ active: feedbackType === 'question' }"
                  class="type-btn"
                >
                  ❓ Pytanie
                </button>
              </div>

              <div class="input-wrapper">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="#4F46E5" stroke-width="1.5"/>
                  <path d="m22 6-10 7L2 6" stroke="#4F46E5" stroke-width="1.5"/>
                </svg>
                <input
                  v-model="email"
                  type="email"
                  placeholder="twoj@email.pl (opcjonalnie)"
                  class="form-input"
                />
              </div>

              <textarea
                v-model="message"
                placeholder="Opisz swój problem, sugestię lub pytanie..."
                rows="4"
                required
                class="form-textarea"
              ></textarea>

              <button type="submit" :disabled="isSubmitting || !message.trim()" class="submit-btn">
                <span v-if="isSubmitting">Wysyłanie...</span>
                <span v-else>Wyślij feedback</span>
              </button>
            </form>
          </div>

          <div v-else class="success-body">
            <div class="success-icon">
              <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
                <circle cx="32" cy="32" r="32" fill="#10B981" opacity="0.1"/>
                <circle cx="32" cy="32" r="24" fill="#10B981"/>
                <path d="M22 32L28 38L42 24" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h2 class="success-title">Dziękujemy za feedback!</h2>
            <p class="success-description">
              Twoja wiadomość została wysłana. Postaramy się odpowiedzieć jak najszybciej.
            </p>
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

.feedback-form {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.type-selector {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.type-btn {
  padding: 0.75rem 0.5rem;
  border: 2px solid #E5E7EB;
  background: white;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.875rem;
  transition: all 0.2s;
  color: #6b7280;
}

.type-btn:hover {
  border-color: #4F46E5;
  background: #f5f3ff;
}

.type-btn.active {
  border-color: #4F46E5;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
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

.form-input {
  width: 100%;
  padding: 1rem 1rem 1rem 3rem;
  border: 2px solid #E5E7EB;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.form-input:focus {
  outline: none;
  border-color: #4F46E5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-textarea {
  width: 100%;
  padding: 1rem;
  border: 2px solid #E5E7EB;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.3s ease;
  font-family: inherit;
  resize: vertical;
  min-height: 100px;
}

.form-textarea:focus {
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

  .modal-title,
  .success-title {
    font-size: 1.5rem;
  }

  .type-selector {
    grid-template-columns: 1fr;
  }
}
</style>
