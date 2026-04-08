<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { api } from '../services/api'
import { getRecaptchaToken, isRecaptchaAvailable } from '../services/recaptchaService'
import { analytics } from '../utils/analytics'

onMounted(() => {
  document.body.style.overflow = 'hidden'
})

onUnmounted(() => {
  document.body.style.overflow = ''
})

const props = defineProps<{
  activeFilters: any
  locationLabel: string
}>()


const emit = defineEmits<{
  close: []
  submit: [email: string]
}>()

const email = ref('')
const emailError = ref('')
const isSubmitting = ref(false)
const isSuccess = ref(false)

const validateEmail = (email: string) => {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return re.test(email)
}

const handleSubmit = async () => {
  emailError.value = ''
  
  if (!email.value) {
    emailError.value = 'Email jest wymagany'
    return
  }
  
  if (!validateEmail(email.value)) {
    emailError.value = 'Proszę podać poprawny adres e-mail'
    return
  }
  
  isSubmitting.value = true

  try {
    let recaptchaToken = ''
    if (isRecaptchaAvailable()) {
      recaptchaToken = await getRecaptchaToken('search_alert')
    }

    await api.saveSearchAlert({
      email: email.value,
      type: props.activeFilters.type,
      city: props.activeFilters.city,
      region: props.activeFilters.region,
      filters: props.activeFilters,
      recaptcha_token: recaptchaToken
    })

    // Track search alert creation in GA4
    analytics.searchAlertCreate(props.activeFilters.city || 'all', props.activeFilters.type || 'all')

    emit('submit', email.value)
    isSuccess.value = true
    
    // Close after success
    setTimeout(() => {
      emit('close')
    }, 4000)
  } catch (error: any) {
    emailError.value = error.message || 'Wystąpił błąd. Spróbuj ponownie później.'
  } finally {
    isSubmitting.value = false
  }
}

</script>

<template>
  <div class="modal-overlay" @click.self="emit('close')">
    <div class="modal-content">
      <button class="close-btn" @click="emit('close')">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
          <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>

      <div v-if="!isSuccess" class="form-container">
        <div class="icon-header">
          <div class="icon-circle">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
              <path d="M18 8C18 6.4087 17.3679 4.88258 16.2426 3.75736C15.1174 2.63214 13.5913 2 12 2C10.4087 2 8.88258 2.63214 7.75736 3.75736C6.63214 4.88258 6 6.4087 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M13.73 21C13.5542 21.3031 13.3019 21.5547 12.9982 21.7295C12.6946 21.9044 12.3504 21.9965 12 21.9965C11.6496 21.9965 11.3054 21.9044 11.0018 21.7295C10.6981 21.5547 10.4458 21.3031 10.27 21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
        </div>

        <h2 class="modal-title">Nie przegap okazji!</h2>
        <p class="modal-desc">
          Powiadomimy Cię e-mailem, gdy tylko pojawi się nowa oferta pasująca do Twoich filtrów:
          <span class="highlight" v-if="locationLabel || activeFilters.type">
            {{ activeFilters.type ? 'Billboardy' : 'Ogłoszenia' }} 
            {{ locationLabel ? 'w: ' + locationLabel : 'w całej Polsce' }}
          </span>
        </p>

        <form @submit.prevent="handleSubmit" class="alert-form">
          <div class="input-group">
            <input 
              v-model="email" 
              type="text" 
              placeholder="Twój adres e-mail" 
              class="email-input"
              :class="{ 'error': emailError }"
              :disabled="isSubmitting"
              @input="emailError = ''"
            />
            <span v-if="emailError" class="error-text">{{ emailError }}</span>
          </div>

          <button type="submit" class="submit-btn" :disabled="isSubmitting">
            <span v-if="!isSubmitting">Zapisz się na powiadomienia</span>
            <span v-else class="loader"></span>
          </button>
        </form>

        <p class="footer-note">W każdej chwili możesz zrezygnować z subskrypcji.</p>
      </div>

      <div v-else class="success-container">
        <div class="success-icon">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M22 4L12 14.01l-3-3" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h2 class="modal-title">Gotowe!</h2>
        <p class="modal-desc">Będziemy Cię informować o nowych ofertach na adres: <strong>{{ email }}</strong></p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 4000;
  padding: 1.5rem;
}

.modal-content {
  background: var(--card-bg, white);
  border-radius: 24px;
  width: 100%;
  max-width: 480px;
  padding: 3rem 2rem;
  position: relative;
  box-shadow: var(--card-shadow, 0 25px 50px -12px rgba(0, 0, 0, 0.25));
  animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.close-btn {
  position: absolute;
  top: 1.5rem;
  right: 1.5rem;
  background: var(--bg-tertiary, #f3f4f6);
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--text-muted, #6b7280);
  transition: all 0.2s;
}

.close-btn:hover {
  background: var(--border-color, #e5e7eb);
  color: var(--text-main, #1f2937);
  transform: rotate(90deg);
}

.icon-header {
  display: flex;
  justify-content: center;
  margin-bottom: 2rem;
}

.icon-circle {
  width: 72px;
  height: 72px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.4);
}

.modal-title {
  font-size: 1.75rem;
  font-weight: 800;
  color: var(--text-main, #111827);
  text-align: center;
  margin-bottom: 1rem;
}

.modal-desc {
  color: var(--text-muted, #4b5563);
  text-align: center;
  line-height: 1.6;
  margin-bottom: 2rem;
  font-size: 1.05rem;
}

.highlight {
  display: block;
  margin-top: 0.5rem;
  font-weight: 700;
  color: #667eea;
}


.alert-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.email-input {
  width: 100%;
  padding: 1rem 1.25rem;
  border: 2px solid var(--border-color, #e5e7eb);
  background: var(--input-bg, white);
  color: var(--text-main, #111827);
  border-radius: 12px;
  font-size: 1rem;
  transition: all 0.2s;
  outline: none;
}

.email-input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.email-input.error {
  border-color: #ef4444;
}

.error-text {
  color: #ef4444;
  font-size: 0.85rem;
  margin-top: 0.5rem;
  display: block;
  font-weight: 500;
}


.submit-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 1.125rem;
  border-radius: 12px;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.2);
}

.submit-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.3);
}


.submit-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.footer-note {
  margin-top: 1.5rem;
  font-size: 0.85rem;
  color: #9ca3af;
  text-align: center;
}

.success-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 0;
}

.success-icon {
  margin-bottom: 1.5rem;
  animation: scaleIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes scaleIn {
  from { transform: scale(0); }
  to { transform: scale(1); }
}

.loader {
  width: 20px;
  height: 20px;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 480px) {
  .modal-content {
    padding: 2rem 1.5rem;
  }
  
  .modal-title {
    font-size: 1.5rem;
  }
}
</style>
