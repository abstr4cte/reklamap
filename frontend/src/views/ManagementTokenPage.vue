<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from '../api/axios'

interface Advertisement {
  id: string
  title: string
  type: string
  city: string
  price: number
  image_url: string | null
  images: string[]
  created_at: string
  updated_at: string
  views: number
  status: string
}

const router = useRouter()
const route = useRoute()
const token = ref(route.params.token as string)

const email = ref('')
const isSubmitting = ref(false)
const isSuccess = ref(false)
const errorMessage = ref('')
const isLoading = ref(true)
const isTokenValid = ref(false)
const advertisements = ref<Advertisement[]>([])
const tokenEmail = ref('')
const expiresAt = ref('')

onMounted(async () => {
  await validateToken()
})

const validateToken = async () => {
  isLoading.value = true
  
  try {
    const response = await axios.get(`/api/management/validate/${token.value}`)
    if (response.data.valid) {
      isTokenValid.value = true
      advertisements.value = response.data.advertisements
      tokenEmail.value = response.data.email
      expiresAt.value = new Date(response.data.expires_at).toLocaleString()
    } else {
      isTokenValid.value = false
    }
  } catch (error) {
    console.error('Error validating token:', error)
    isTokenValid.value = false
  } finally {
    isLoading.value = false
  }
}

const handleSubmit = async () => {
  if (!email.value || !email.value.includes('@')) {
    errorMessage.value = 'Proszę podać poprawny adres email'
    return
  }

  errorMessage.value = ''
  isSubmitting.value = true

  try {
    // Send request to backend API
    await axios.post('/api/management/send-link', {
      email: email.value
    })

    isSuccess.value = true
    
    // Redirect after 5 seconds
    setTimeout(() => {
      router.push('/')
    }, 5000)
  } catch (error) {
    console.error('Error sending management link:', error)
    errorMessage.value = 'Wystąpił błąd podczas wysyłania linku. Spróbuj ponownie.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="token-page">
    <div class="container">
      <!-- Loading state -->
      <div v-if="isLoading" class="loading-container">
        <div class="loading-spinner"></div>
        <p>Weryfikacja linku...</p>
      </div>
      
      <!-- Valid token with advertisements -->
      <div v-else-if="isTokenValid" class="ads-container">
        <div class="header-section">
          <h1>Panel zarządzania ogłoszeniami</h1>
          <div class="token-info">
            <p>Email: <strong>{{ tokenEmail }}</strong></p>
            <p>Link ważny do: <strong>{{ expiresAt }}</strong></p>
          </div>
        </div>
        
        <div v-if="advertisements.length > 0" class="ads-grid">
          <div v-for="ad in advertisements" :key="ad.id" class="ad-card">
            <div class="ad-image">
              <img v-if="ad.image_url" :src="ad.image_url" :alt="ad.title" />
              <img v-else-if="ad.images && ad.images.length > 0" :src="ad.images[0]" :alt="ad.title" />
              <div v-else class="no-image">Brak zdjęcia</div>
            </div>
            <div class="ad-content">
              <h3>{{ ad.title }}</h3>
              <div class="ad-details">
                <span class="ad-type">{{ ad.type }}</span>
                <span class="ad-location">{{ ad.city }}</span>
              </div>
              <div class="ad-price">{{ ad.price.toLocaleString() }} PLN</div>
              <div class="ad-stats">
                <span>Wyświetlenia: {{ ad.views }}</span>
                <span :class="['ad-status', `status-${ad.status}`]">{{ ad.status === 'active' ? 'Aktywne' : 'Nieaktywne' }}</span>
              </div>
              <div class="ad-actions">
                <router-link :to="`/powierzchnia-reklamowa/${ad.type}/${ad.city}/${ad.title.toLowerCase().replace(/ /g, '-')}-${ad.id}`" class="view-btn">Zobacz</router-link>
                <button class="edit-btn">Edytuj</button>
              </div>
            </div>
          </div>
        </div>
        
        <div v-else class="no-ads">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 8V12" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 16H12.01" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <h2>Brak ogłoszeń</h2>
          <p>Nie znaleziono żadnych ogłoszeń powiązanych z tym adresem email.</p>
          <router-link to="/dodaj-powierzchnie-reklamowa" class="add-ad-btn">Dodaj pierwsze ogłoszenie</router-link>
        </div>
      </div>
      
      <!-- Invalid or expired token -->
      <div v-else-if="!isLoading && !isTokenValid" class="content-card">
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
            
            <div v-if="errorMessage" class="error-message">
              {{ errorMessage }}
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
  align-items: flex-start;
  justify-content: center;
  background: #f9fafb;
  padding: 2rem;
}

.container {
  max-width: 1000px;
  width: 100%;
}

/* Loading state */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 300px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  padding: 2rem;
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid rgba(103, 126, 234, 0.2);
  border-radius: 50%;
  border-top-color: #667eea;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Advertisements container */
.ads-container {
  width: 100%;
}

.header-section {
  background: white;
  padding: 2rem;
  border-radius: 16px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
}

.header-section h1 {
  margin: 0;
  font-size: 1.75rem;
  color: #1f2937;
  font-weight: 700;
}

.token-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  text-align: right;
}

.token-info p {
  margin: 0;
  color: #6b7280;
  font-size: 0.95rem;
}

/* Ads grid */
.ads-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.ad-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.ad-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
}

.ad-image {
  height: 180px;
  overflow: hidden;
  position: relative;
}

.ad-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.no-image {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f3f4f6;
  color: #9ca3af;
  font-weight: 500;
}

.ad-content {
  padding: 1.25rem;
}

.ad-content h3 {
  margin: 0 0 0.75rem 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #1f2937;
  line-height: 1.4;
  height: 3rem;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
}

.ad-details {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.ad-type, .ad-location {
  font-size: 0.85rem;
  color: #6b7280;
  background: #f3f4f6;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
}

.ad-price {
  font-size: 1.25rem;
  font-weight: 700;
  color: #4f46e5;
  margin-bottom: 0.75rem;
}

.ad-stats {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  font-size: 0.85rem;
  color: #6b7280;
}

.ad-status {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-weight: 500;
}

.status-active {
  background: #d1fae5;
  color: #065f46;
}

.status-inactive {
  background: #fee2e2;
  color: #b91c1c;
}

.ad-actions {
  display: flex;
  gap: 0.75rem;
}

.view-btn, .edit-btn {
  flex: 1;
  padding: 0.75rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.9rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.view-btn {
  background: #4f46e5;
  color: white;
  text-decoration: none;
}

.edit-btn {
  background: white;
  color: #4f46e5;
  border: 1px solid #4f46e5;
}

.view-btn:hover {
  background: #4338ca;
}

.edit-btn:hover {
  background: #f5f3ff;
}

/* No ads state */
.no-ads {
  background: white;
  padding: 3rem;
  border-radius: 16px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.no-ads svg {
  margin-bottom: 1.5rem;
}

.no-ads h2 {
  margin: 0 0 1rem 0;
  font-size: 1.5rem;
  color: #1f2937;
}

.no-ads p {
  margin: 0 0 2rem 0;
  color: #6b7280;
}

.add-ad-btn {
  background: linear-gradient(135deg, #10B981 0%, #059669 100%);
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
}

.add-ad-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 15px rgba(16, 185, 129, 0.3);
}

/* Invalid token card */
.content-card {
  background: white;
  padding: 3rem;
  border-radius: 24px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  text-align: center;
  max-width: 500px;
  margin: 0 auto;
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

.error-message {
  color: #EF4444;
  font-size: 0.875rem;
  margin: 0.5rem 0;
  padding: 0.5rem;
  background-color: #FEF2F2;
  border-radius: 6px;
  border-left: 3px solid #EF4444;
  text-align: left;
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

@media (max-width: 768px) {
  .header-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .token-info {
    text-align: left;
  }
  
  .ads-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .content-card {
    padding: 2rem 1.5rem;
  }

  h1 {
    font-size: 1.25rem;
  }
  
  .token-page {
    padding: 1rem;
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
