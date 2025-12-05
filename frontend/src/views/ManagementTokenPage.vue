<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const email = ref('')
const isSubmitting = ref(false)
const isSuccess = ref(false)

const handleSubmit = async () => {
  if (!email.value || !email.value.includes('@')) {
    return
  }

  isSubmitting.value = true

  // Simulate API call
  await new Promise(resolve => setTimeout(resolve, 1000))

  isSuccess.value = true
  isSubmitting.value = false

  // Redirect after 5 seconds
  setTimeout(() => {
    router.push('/')
  }, 5000)
}
</script>

<template>
  <div class="token-page">
    <div class="container">
      <div class="content-card">
        <div v-if="!isSuccess" class="card-body">
          <div class="icon-wrapper">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="12" r="10" stroke="#EF4444" stroke-width="2"/>
              <path d="M12 8V12" stroke="#EF4444" stroke-width="2" stroke-linecap="round"/>
              <circle cx="12" cy="16" r="1" fill="#EF4444"/>
            </svg>
          </div>

          <h1>Ups... widocznie Twój link jest już nieaktywny</h1>
          <p class="description">
            Podaj swój adres e-mail, aby wygenerować nowy link do panelu zarządzania.
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
              <span v-if="!isSubmitting">Wyślij nowy link</span>
              <span v-else class="loading">Wysyłam...</span>
            </button>
            
            <p class="info-text">
              Link będzie ważny przez 24 godziny
            </p>
          </form>
        </div>

        <div v-else class="success-body">
          <div class="success-icon">
            <svg width="80" height="80" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="32" cy="32" r="32" fill="#10B981" opacity="0.1"/>
              <circle cx="32" cy="32" r="24" fill="#10B981"/>
              <path d="M22 32L28 38L42 24" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <h2 class="success-title">Link został wysłany!</h2>
          <p class="success-description">
            Sprawdź swoją skrzynkę odbiorczą na adresie <strong>{{ email }}</strong>
            <br>
            <span class="redirect-info">Za chwilę nastąpi przekierowanie na stronę główną...</span>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.token-page {
  min-height: 80vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f9fafb;
  padding: 2rem;
}

.container {
  max-width: 500px;
  width: 100%;
}

.content-card {
  background: white;
  padding: 3rem;
  border-radius: 24px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  text-align: center;
}

.icon-wrapper {
  margin-bottom: 1.5rem;
  display: flex;
  justify-content: center;
}

h1 {
  font-size: 1.5rem;
  color: #1f2937;
  margin-bottom: 1rem;
  font-weight: 800;
  line-height: 1.3;
}

.description {
  color: #6b7280;
  font-size: 1rem;
  margin-bottom: 2rem;
  line-height: 1.6;
}

.email-form {
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
  border-radius: 12px;
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
  border-radius: 12px;
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

.success-body {
  display: flex;
  flex-direction: column;
  align-items: center;
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

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

@keyframes scaleIn {
  0% { transform: scale(0); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

@media (max-width: 640px) {
  .content-card {
    padding: 2rem 1.5rem;
  }

  h1 {
    font-size: 1.25rem;
  }
}

.info-text {
  margin-top: 1rem;
  font-size: 0.875rem;
  color: #9CA3AF;
}

.redirect-info {
  display: block;
  margin-top: 1rem;
  font-size: 0.9rem;
  color: #6B7280;
  font-style: italic;
}
</style>
