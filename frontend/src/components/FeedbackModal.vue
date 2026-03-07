<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue'
import { api } from '../services/api'
import { getRecaptchaToken, isRecaptchaAvailable } from '../services/recaptchaService'

const isOpen = ref(false)
const feedbackType = ref('suggestion')
const email = ref('')
const message = ref('')
const isSubmitting = ref(false)
const showSuccess = ref(false)
const error = ref('')

const emit = defineEmits<{
  close: []
}>()

// Prevent body scroll when modal is open
watch(isOpen, (open) => {
  if (typeof document !== 'undefined') {
    if (open) {
      document.body.style.overflow = 'hidden'
      document.body.style.position = 'fixed'
      document.body.style.width = '100%'
    } else {
      document.body.style.overflow = 'auto'
      document.body.style.position = 'static'
      document.body.style.width = 'auto'
    }
  }
})

// Cleanup on unmount
onUnmounted(() => {
  if (typeof document !== 'undefined') {
    document.body.style.overflow = 'auto'
    document.body.style.position = 'static'
    document.body.style.width = 'auto'
  }
})

const openModal = () => {
  isOpen.value = true
}

const closeModal = () => {
  isOpen.value = false
  emit('close')
}

const handleSubmit = async () => {
  const emailTrimmed = email.value.trim()
  const messageTrimmed = message.value.trim()

  if (!emailTrimmed) {
    error.value = 'Proszę wpisać adres e-mail'
    return
  }

  // Validate email format
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(emailTrimmed)) {
    error.value = 'Proszę wpisać prawidłowy adres e-mail (np. nazwa@domena.pl)'
    return
  }

  // Additional validation - check for common mistakes
  if (emailTrimmed.includes('..')) {
    error.value = 'Adres e-mail zawiera błędy'
    return
  }

  if (emailTrimmed.startsWith('.') || emailTrimmed.endsWith('.')) {
    error.value = 'Adres e-mail zawiera błędy'
    return
  }

  const [localPart, domain] = emailTrimmed.split('@')
  if (!localPart || localPart.length === 0 || localPart.length > 64) {
    error.value = 'Część przed @ jest za długa lub pusta'
    return
  }

  if (!domain || domain.length === 0 || domain.length > 255) {
    error.value = 'Domena jest za długa lub pusta'
    return
  }

  if (!domain.includes('.')) {
    error.value = 'Domena musi zawierać kropkę (np. domena.pl)'
    return
  }

  if (!messageTrimmed) {
    error.value = 'Proszę wpisać wiadomość'
    return
  }

  isSubmitting.value = true
  error.value = ''

  try {
    // Get reCAPTCHA token
    let recaptchaToken = ''
    if (isRecaptchaAvailable()) {
      recaptchaToken = await getRecaptchaToken('feedback')
    }

    const feedbackData = {
      type: feedbackType.value,
      email: emailTrimmed,
      message: messageTrimmed,
      url: typeof window !== 'undefined' ? window.location.href : '',
      userAgent: typeof navigator !== 'undefined' ? navigator.userAgent : '',
      recaptcha_token: recaptchaToken
    }

    await api.submitFeedback(feedbackData)

    isSubmitting.value = false
    showSuccess.value = true

    // Reset form
    setTimeout(() => {
      feedbackType.value = 'suggestion'
      email.value = ''
      message.value = ''
      error.value = ''
      showSuccess.value = false
      closeModal()
    }, 2000)
  } catch (err) {
    isSubmitting.value = false
    error.value = err instanceof Error ? err.message : 'Błąd podczas wysyłania feedback'
    console.error('Error submitting feedback:', err)
  }
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
              <div v-if="error" class="error-message">
                {{ error }}
              </div>

              <div class="type-selector">
                <button
                  type="button"
                  @click="feedbackType = 'bug'"
                  :class="{ active: feedbackType === 'bug' }"
                  class="type-btn"
                >
                  <img src="/icons/blad.svg" alt="" class="type-icon" />
                  Błąd
                </button>
                <button
                  type="button"
                  @click="feedbackType = 'suggestion'"
                  :class="{ active: feedbackType === 'suggestion' }"
                  class="type-btn"
                >
                  <img src="/icons/sugestia.svg" alt="" class="type-icon" />
                  Sugestia
                </button>
                <button
                  type="button"
                  @click="feedbackType = 'question'"
                  :class="{ active: feedbackType === 'question' }"
                  class="type-btn"
                >
                  <img src="/icons/pytanie.svg" alt="" class="type-icon" />
                  Pytanie
                </button>
              </div>

              <div class="input-wrapper">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="#4F46E5" stroke-width="1.5"/>
                  <path d="m22 6-10 7L2 6" stroke="#4F46E5" stroke-width="1.5"/>
                </svg>
                <input
                  v-model="email"
                  type="text"
                  placeholder="twoj@email.pl"
                  class="form-input"
                  required
                />
              </div>

              <textarea
                v-model="message"
                placeholder="Opisz swój problem, sugestię lub pytanie..."
                rows="4"
                required
                maxlength="2000"
                class="form-textarea"
              ></textarea>
              <div class="char-counter" :class="{ 'near-limit': message.length > 1800 }">
                {{ message.length }}/2000 znaków
              </div>

              <button type="submit" :disabled="isSubmitting || !email.trim() || !message.trim()" class="submit-btn">
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
  overflow: hidden;
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

.error-message {
  padding: 0.75rem 1rem;
  background: #FEE2E2;
  border: 1px solid #FECACA;
  border-radius: 8px;
  color: #DC2626;
  font-size: 0.875rem;
  font-weight: 500;
}

.type-selector {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.type-btn {
  padding: 0.75rem 1rem;
  border: 2px solid #E5E7EB;
  background: white;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.875rem;
  transition: all 0.2s;
  color: #6b7280;
  display: flex;
  align-items: center;
  justify-content: center;
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

.type-icon {
  width: 24px;
  height: 24px;
  margin-right: 0.5rem;
  vertical-align: middle;
  filter: invert(48%) sepia(79%) saturate(2476%) hue-rotate(224deg) brightness(94%) contrast(91%);
  -webkit-filter: invert(48%) sepia(79%) saturate(2476%) hue-rotate(224deg) brightness(94%) contrast(91%);
}

.type-btn.active .type-icon {
  filter: brightness(0) invert(1); /* Białe ikonki dla aktywnego przycisku */
  -webkit-filter: brightness(0) invert(1);
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

.char-counter {
  font-size: 0.875rem;
  color: #9CA3AF;
  text-align: right;
  margin-top: 0.25rem;
  transition: color 0.2s ease;
}

.char-counter.near-limit {
  color: #F59E0B;
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
    padding: 1.5rem 1.25rem;
    max-height: 90vh;
    overflow-y: auto;
  }

  .modal-title,
  .success-title {
    font-size: 1.35rem;
    margin-bottom: 0.5rem;
  }

  .modal-description {
    font-size: 0.9rem;
    margin-bottom: 1.25rem;
  }

  .type-selector {
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
  }

  .type-btn {
    padding: 0.5rem;
    font-size: 0.75rem;
    flex-direction: column;
    gap: 0.25rem;
  }

  .type-icon {
    margin-right: 0;
    width: 20px;
    height: 20px;
  }

  .icon-wrapper {
    margin-bottom: 1rem;
  }
}
</style>
