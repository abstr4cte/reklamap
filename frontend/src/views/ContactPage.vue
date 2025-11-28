<script setup lang="ts">
import { ref } from 'vue'

const formData = ref({
  name: '',
  email: '',
  phone: '',
  subject: '',
  message: ''
})

const isSubmitting = ref(false)
const submitSuccess = ref(false)

const handleSubmit = async () => {
  isSubmitting.value = true

  await new Promise(resolve => setTimeout(resolve, 1500))

  submitSuccess.value = true
  isSubmitting.value = false

  formData.value = {
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: ''
  }

  setTimeout(() => {
    submitSuccess.value = false
  }, 5000)
}
</script>

<template>
  <div class="contact-page">
    <div class="hero-section">
      <div class="container">
        <h1>Skontaktuj się z nami</h1>
        <p class="hero-subtitle">Masz pytania? Chętnie pomożemy! Skorzystaj z formularza lub wybierz inną formę kontaktu.</p>
      </div>
    </div>

    <div class="content-section">
      <div class="container">
        <div class="content-grid">
          <div class="contact-info">
            <div class="info-card">
              <div class="info-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                  <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <h3>Email</h3>
              <p>Napisz do nas</p>
              <a href="mailto:kontakt@adspace.pl">kontakt@adspace.pl</a>
            </div>

            <div class="info-card">
              <div class="info-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                  <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
              </div>
              <h3>Godziny pracy</h3>
              <p>Jesteśmy dostępni</p>
              <div class="hours">
                <div>Pon - Pt: 9:00 - 17:00</div>
                <div>Sob - Ndz: Zamknięte</div>
              </div>
            </div>
          </div>

          <div class="contact-form-wrapper">
            <form @submit.prevent="handleSubmit" class="contact-form">
              <h2>Wyślij wiadomość</h2>
              <p class="form-description">Wypełnij formularz, a my skontaktujemy się z Tobą najszybciej jak to możliwe.</p>

              <div class="form-group">
                <label for="name">Imię i nazwisko *</label>
                <input
                  id="name"
                  v-model="formData.name"
                  type="text"
                  required
                  placeholder="Jan Kowalski"
                />
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="email">Adres e-mail *</label>
                  <input
                    id="email"
                    v-model="formData.email"
                    type="email"
                    required
                    placeholder="jan@przykład.pl"
                  />
                </div>

                <div class="form-group">
                  <label for="phone">Numer telefonu</label>
                  <input
                    id="phone"
                    v-model="formData.phone"
                    type="tel"
                    placeholder="+48 123 456 789"
                  />
                </div>
              </div>

              <div class="form-group">
                <label for="subject">Temat *</label>
                <select id="subject" v-model="formData.subject" required>
                  <option value="" disabled>Wybierz temat</option>
                  <option value="pytanie">Pytanie ogólne</option>
                  <option value="ogloszenie">Problem z ogłoszeniem</option>
                  <option value="techniczny">Problem techniczny</option>
                  <option value="wspolpraca">Współpraca biznesowa</option>
                  <option value="inne">Inne</option>
                </select>
              </div>

              <div class="form-group">
                <label for="message">Wiadomość *</label>
                <textarea
                  id="message"
                  v-model="formData.message"
                  required
                  rows="6"
                  placeholder="Opisz swoją sprawę..."
                ></textarea>
              </div>

              <div v-if="submitSuccess" class="success-message">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                  <path d="M22 11.08V12a10 10 0 11-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M22 4L12 14.01l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Dziękujemy! Twoja wiadomość została wysłana. Odpowiemy najszybciej jak to możliwe.
              </div>

              <button type="submit" class="submit-btn" :disabled="isSubmitting">
                <span v-if="!isSubmitting">
                  Wyślij wiadomość
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </span>
                <span v-else class="loading">
                  <div class="spinner"></div>
                  Wysyłanie...
                </span>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.contact-page {
  min-height: 100vh;
  background: #f9fafb;
}

.hero-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 4rem 0;
  color: white;
  text-align: center;
}

.hero-section h1 {
  font-size: 3rem;
  font-weight: 800;
  margin: 0 0 1rem 0;
}

.hero-subtitle {
  font-size: 1.25rem;
  opacity: 0.95;
  margin: 0;
  max-width: 700px;
  margin-left: auto;
  margin-right: auto;
}

.content-section {
  padding: 4rem 0;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

.content-grid {
  display: grid;
  grid-template-columns: 400px 1fr;
  gap: 3rem;
  align-items: start;
}

.contact-info {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.info-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.3s;
}

.info-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.info-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  background: linear-gradient(135deg, #f0f3ff 0%, #e8eaff 100%);
  color: #667eea;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
}

.info-card h3 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
}

.info-card p {
  color: #6b7280;
  margin: 0 0 0.75rem 0;
  font-size: 0.95rem;
}

.info-card a {
  color: #667eea;
  font-weight: 600;
  text-decoration: none;
  transition: color 0.2s;
}

.info-card a:hover {
  color: #764ba2;
}

.info-card address {
  font-style: normal;
  color: #667eea;
  font-weight: 500;
  line-height: 1.6;
}

.hours {
  color: #667eea;
  font-weight: 500;
}

.hours div {
  margin-bottom: 0.25rem;
}

.social-links {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.social-links h3 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 1.5rem 0;
}

.social-icons {
  display: flex;
  gap: 1rem;
}

.social-icon {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, #f0f3ff 0%, #e8eaff 100%);
  color: #667eea;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.social-icon:hover {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  transform: translateY(-4px);
}

.contact-form-wrapper {
  background: white;
  border-radius: 12px;
  padding: 3rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.contact-form h2 {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
}

.form-description {
  color: #6b7280;
  margin: 0 0 2rem 0;
  line-height: 1.6;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.form-group label {
  display: block;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.2s;
  font-family: inherit;
  background: white;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group textarea {
  resize: vertical;
  min-height: 120px;
}

.success-message {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  background: #d1fae5;
  border: 2px solid #10b981;
  border-radius: 8px;
  color: #065f46;
  font-weight: 500;
  margin-bottom: 1.5rem;
}

.submit-btn {
  width: 100%;
  padding: 1.125rem 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 1.0625rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.submit-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
}

.submit-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.loading {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 1024px) {
  .content-grid {
    grid-template-columns: 1fr;
  }

  .contact-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
  }

  .social-links {
    grid-column: 1 / -1;
  }
}

@media (max-width: 640px) {
  .hero-section {
    padding: 3rem 0;
  }

  .hero-section h1 {
    font-size: 2rem;
  }

  .hero-subtitle {
    font-size: 1rem;
  }

  .contact-info {
    grid-template-columns: 1fr;
  }

  .contact-form-wrapper {
    padding: 2rem 1.5rem;
  }

  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
