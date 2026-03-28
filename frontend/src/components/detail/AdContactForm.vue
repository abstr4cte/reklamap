<script setup lang="ts">
import { ref } from 'vue'
import axios from '../../api/axios'
import { api } from '../../services/api'
import { getRecaptchaToken, isRecaptchaAvailable } from '../../services/recaptchaService'

const props = defineProps<{
  adId: string
}>()

const emit = defineEmits<{
  success: [message: string]
  error: [message: string]
}>()

const contactForm = ref({
  email: '',
  message: ''
})
const contactErrors = ref<Record<string, string>>({})
const isSubmittingContact = ref(false)
const contactSuccess = ref(false)

const validateContactForm = () => {
  const errors: Record<string, string> = {}
  if (!contactForm.value.email) {
    errors.email = 'Adres e-mail jest wymagany'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(contactForm.value.email)) {
    errors.email = 'Nieprawidłowy adres e-mail'
  }
  if (!contactForm.value.message) {
    errors.message = 'Wiadomość jest wymagana'
  } else if (contactForm.value.message.length < 10) {
    errors.message = 'Wiadomość musi mieć co najmniej 10 znaków'
  }
  contactErrors.value = errors
  return Object.keys(errors).length === 0
}

const submitContactForm = async () => {
  if (!validateContactForm()) return
  
  isSubmittingContact.value = true
  contactErrors.value = {}
  
  try {
    let recaptcha_token = ''
    if (isRecaptchaAvailable()) {
      recaptcha_token = await getRecaptchaToken('contact_form')
    }

    await axios.post(`/api/listings/${props.adId}/contact`, {
      ...contactForm.value,
      recaptcha_token
    })
    
    contactSuccess.value = true
    contactForm.value = { email: '', message: '' }
    emit('success', 'Wiadomość została wysłana pomyślnie!')
    
    // Track email click in statistics
    api.incrementEmailClicks(props.adId).catch(() => {})
    
    setTimeout(() => {
      contactSuccess.value = false
    }, 5000)
  } catch (error: any) {
    console.error('Contact form error:', error)
    const message = error.response?.data?.message || 'Nie udało się wysłać wiadomości. Spróbuj ponownie.'
    contactErrors.value.submit = message
    emit('error', message)
  } finally {
    isSubmittingContact.value = false
  }
}
</script>

<template>
  <div class="contact-form-section">
    <h2>Formularz kontaktowy</h2>

    <div v-if="contactSuccess" class="success-message">
      Wiadomość została wysłana pomyślnie!
    </div>
    
    <div v-if="contactErrors.submit" class="submit-error-message">
      {{ contactErrors.submit }}
    </div>

    <form @submit.prevent="submitContactForm" class="contact-form">
      <div class="form-group">
        <label class="form-label">Twój e-mail</label>
        <input
          v-model="contactForm.email"
          type="text"
          class="form-input"
          :class="{ 'error': contactErrors.email }"
          placeholder="twoj@email.pl"
        />
        <span v-if="contactErrors.email" class="error-text">{{ contactErrors.email }}</span>
      </div>

      <div class="form-group">
        <label class="form-label">Wiadomość</label>
        <textarea
          v-model="contactForm.message"
          rows="5"
          class="form-textarea"
          :class="{ 'error': contactErrors.message }"
          placeholder="Dzień dobry, interesuje mnie wynajem tej powierzchni reklamowej..."
        ></textarea>
        <span v-if="contactErrors.message" class="error-text">{{ contactErrors.message }}</span>
      </div>

      <button
        type="submit"
        class="btn btn-primary"
        :disabled="isSubmittingContact"
      >
        {{ isSubmittingContact ? 'Wysyłanie...' : 'Wyślij wiadomość' }}
      </button>
    </form>
  </div>
</template>

<style scoped>
.contact-form-section {
  background: var(--card-bg, white);
  padding: 2.5rem;
  border-radius: 20px;
  box-shadow: var(--card-shadow, 0 4px 6px -1px rgba(0, 0, 0, 0.1));
}

.contact-form-section h2 {
  font-size: 1.5rem;
  margin-bottom: 2rem;
  color: var(--text-main, #1f2937);
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: var(--text-main, #374151);
}

.form-input, .form-textarea {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid var(--border-color, #e5e7eb);
  border-radius: 10px;
  font-family: inherit;
  font-size: 1rem;
  transition: all 0.2s;
  background: var(--bg-secondary, #f9fafb);
  color: var(--text-main, #111827);
}

.form-input:focus, .form-textarea:focus {
  outline: none;
  border-color: #667eea;
  background: var(--card-bg, white);
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.form-input.error, .form-textarea.error {
  border-color: #ef4444;
}

.error-text {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: block;
}

.success-message {
  background: #ecfdf5;
  color: #059669;
  padding: 1rem;
  border-radius: 10px;
  margin-bottom: 1.5rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.submit-error-message {
  background: #fef2f2;
  color: #dc2626;
  padding: 1rem;
  border-radius: 10px;
  margin-bottom: 1.5rem;
  font-weight: 600;
}

.btn-primary {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 6px rgba(102, 126, 234, 0.25);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.4);
}

.btn-primary:active:not(:disabled) {
  transform: translateY(0);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
