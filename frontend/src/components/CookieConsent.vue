<script setup lang="ts">
import { ref, onMounted } from 'vue'

const isVisible = ref(false)
const showDetails = ref(false)
const consentPreferences = ref({
  essential: true, // Always true
  analytical: true,
  marketing: true
})

onMounted(() => {
  const savedConsent = localStorage.getItem('cookie_consent_v2')
  if (!savedConsent) {
    // Reveal after a short delay
    setTimeout(() => {
      isVisible.value = true
    }, 1200)
  }
})

const acceptAll = () => {
  const preferences = {
    essential: true,
    analytical: true,
    marketing: true,
    timestamp: new Date().toISOString()
  }
  saveConsent(preferences)
}

const rejectAll = () => {
  const preferences = {
    essential: true,
    analytical: false,
    marketing: false,
    timestamp: new Date().toISOString()
  }
  saveConsent(preferences)
}

const saveCustom = () => {
  saveConsent({
    ...consentPreferences.value,
    timestamp: new Date().toISOString()
  })
}

const saveConsent = (preferences: any) => {
  localStorage.setItem('cookie_consent_v2', JSON.stringify(preferences))
  // For backward compatibility if needed
  localStorage.setItem('cookie_consent', 'true')
  isVisible.value = false
  
  // Trigger event for analytics scripts to initialize
  window.dispatchEvent(new CustomEvent('cookieConsentUpdated', { detail: preferences }))
}

const toggleDetails = () => {
  showDetails.value = !showDetails.value
}
</script>

<template>
  <Transition name="premium-slide">
    <div v-if="isVisible" class="cookie-banner-overlay">
      <div class="cookie-premium-card" :class="{ 'expanded': showDetails }">
        <div class="card-header">
          <div class="icon-orb">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2"/>
              <circle cx="12" cy="12" r="3" fill="currentColor" fill-opacity="0.2"/>
              <path d="M12 8V12L14 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </div>
          <div class="header-text">
            <h3>Szanujemy Twoją prywatność</h3>
            <p>Używamy plików cookies, aby zapewnić Ci najlepsze doświadczenia na ReklaMap.</p>
          </div>
        </div>

        <div v-if="!showDetails" class="simple-actions">
          <button @click="acceptAll" class="btn-primary">Akceptuję wszystkie</button>
          <div class="secondary-actions">
            <button @click="rejectAll" class="btn-text">Tylko niezbędne</button>
            <span class="dot">•</span>
            <button @click="toggleDetails" class="btn-text">Ustawienia</button>
          </div>
        </div>

        <div v-else class="detailed-preferences">
          <div class="preference-list">
            <div class="preference-item disabled">
              <div class="pref-info">
                <h4>Niezbędne</h4>
                <p>Wymagane do prawidłowego działania strony.</p>
              </div>
              <div class="toggle disabled">
                <input type="checkbox" checked disabled>
                <span class="slider"></span>
              </div>
            </div>

            <div class="preference-item">
              <div class="pref-info">
                <h4>Analityczne</h4>
                <p>Pomagają nam usunąć błędy i ulepszać platformę.</p>
              </div>
              <label class="toggle">
                <input type="checkbox" v-model="consentPreferences.analytical">
                <span class="slider"></span>
              </label>
            </div>

            <div class="preference-item">
              <div class="pref-info">
                <h4>Marketingowe</h4>
                <p>Pozwalają wyświetlać treści dopasowane do Ciebie.</p>
              </div>
              <label class="toggle">
                <input type="checkbox" v-model="consentPreferences.marketing">
                <span class="slider"></span>
              </label>
            </div>
          </div>

          <div class="details-footer">
            <router-link to="/polityka-prywatnosci" class="policy-link">Polityka Prywatności</router-link>
            <button @click="saveCustom" class="btn-save">Zapisz ustawienia</button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.cookie-banner-overlay {
  position: fixed;
  bottom: 1.5rem;
  left: 1.5rem;
  z-index: 10000;
  max-width: 420px;
  width: calc(100% - 3rem);
  pointer-events: none;
}

.cookie-premium-card {
  pointer-events: auto;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: 24px;
  padding: 1.5rem;
  box-shadow: 
    0 10px 25px -5px rgba(0, 0, 0, 0.1),
    0 8px 10px -6px rgba(0, 0, 0, 0.1),
    0 0 0 1px rgba(0, 0, 0, 0.05);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}

.cookie-premium-card.expanded {
  max-width: 500px;
}

.card-header {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.icon-orb {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
  box-shadow: 0 8px 16px rgba(102, 126, 234, 0.25);
}

.header-text h3 {
  margin: 0 0 0.25rem 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: #111827;
}

.header-text p {
  margin: 0;
  font-size: 0.9rem;
  color: #4b5563;
  line-height: 1.4;
}

/* Actions */
.simple-actions {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.875rem;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.secondary-actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
}

.btn-text {
  background: none;
  border: none;
  color: #6b7280;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  padding: 0.25rem 0.5rem;
  transition: color 0.2s;
}

.btn-text:hover {
  color: #111827;
}

.dot {
  color: #d1d5db;
  font-size: 0.8rem;
}

/* Detailed Prefs */
.detailed-preferences {
  animation: fadeIn 0.3s ease-out;
}

.preference-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin: 1.5rem 0;
  background: rgba(243, 244, 246, 0.5);
  padding: 1rem;
  border-radius: 16px;
}

.preference-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.pref-info h4 {
  margin: 0 0 0.125rem 0;
  font-size: 0.9rem;
  font-weight: 600;
  color: #111827;
}

.pref-info p {
  margin: 0;
  font-size: 0.8rem;
  color: #6b7280;
}

/* Toggle Switch */
.toggle {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 28px;
  flex-shrink: 0;
}

.toggle input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%);
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 14px;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.05);
}

.slider:before {
  position: absolute;
  content: "";
  height: 22px;
  width: 22px;
  left: 3px;
  top: 3px;
  background-color: white;
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 50%;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15), 0 1px 3px rgba(0, 0, 0, 0.1);
}

input:checked + .slider {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1), 0 0 0 3px rgba(102, 126, 234, 0.1);
}

input:checked + .slider:before {
  transform: translateX(22px);
  box-shadow: 0 3px 8px rgba(102, 126, 234, 0.3), 0 1px 3px rgba(0, 0, 0, 0.1);
}

.slider:hover {
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.05);
}

input:checked + .slider:hover {
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1), 0 0 0 3px rgba(102, 126, 234, 0.15);
}

.toggle.disabled .slider {
  background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%);
  cursor: not-allowed;
}

/* Detailed Footer */
.details-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.5rem;
}

.policy-link {
  font-size: 0.85rem;
  color: #6366f1;
  text-decoration: none;
  font-weight: 500;
}

.policy-link:hover {
  text-decoration: underline;
}

.btn-save {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.25rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-save:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}

.premium-slide-enter-active {
  transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

.premium-slide-leave-active {
  transition: all 0.4s cubic-bezier(0.7, 0, 0.84, 0);
}

.premium-slide-enter-from {
  transform: translateY(100%) scale(0.9);
  opacity: 0;
}

.premium-slide-leave-to {
  transform: translateY(20px);
  opacity: 0;
}

@media (max-width: 640px) {
  .cookie-banner-overlay {
    bottom: 1rem;
    left: 1rem;
    width: calc(100% - 2rem);
  }
}
</style>
