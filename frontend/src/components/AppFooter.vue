<script setup lang="ts">
import { ref } from 'vue'
import logoTextImage from '../assets/logo-text.webp'
import { useNavStore } from '../stores/useNavStore'

// Huby nawigacyjne z REALNEJ podaży (backend) — zamiast sztywnych demand-miast (Warszawa/Kraków
// z 0 ofert). Linkowanie wewn. kieruje crawl/link-equity Google do stron, które mają treść i są index.
const navStore = useNavStore()

const currentYear = new Date().getFullYear()

const openSections = ref<Record<string, boolean>>({
  nav: false,
  info: false,
  categories: false,
  cities: false,
  searches: false
})

const toggleSection = (section: string) => {
  if (window.innerWidth <= 768) {
    openSections.value[section] = !openSections.value[section]
  }
}

// Wyczyść flagę user_initiated_search gdy użytkownik wchodzi z linku kategorii/miasta w stopce
const clearSearchFlag = () => {
  try {
    localStorage.removeItem('user_initiated_search')
    localStorage.removeItem('reklamap_last_search')
  } catch (e) { /* ignore */ }
}

const handleHomeClick = () => {
  try {
    sessionStorage.removeItem('homepage_scroll_position')
    sessionStorage.removeItem('listings_scroll_position')
  } catch (e) { /* ignore */ }
}

</script>

<template>
  <footer class="app-footer">
    <div class="footer-container">
      <div class="footer-content">
        <div class="footer-section brand-section">
          <div class="brand-header">
            <img :src="logoTextImage" alt="ReklaMap" class="brand-logo" />
          </div>
          <p class="footer-description">
            Platforma do wynajmu powierzchni reklamowych w całej Polsce.
            Szybko, wygodnie i bezpiecznie.
          </p>
          <div class="footer-contact">
            <a href="mailto:kontakt@reklamap.pl" class="email-link">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2"/>
                <path d="m22 6-10 7L2 6" stroke="currentColor" stroke-width="2"/>
              </svg>
              kontakt@reklamap.pl
            </a>
          </div>
        </div>

        <div class="footer-section" :class="{ 'is-open': openSections.nav }">
          <h4 @click="toggleSection('nav')">
            Nawigacja
            <svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none">
              <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </h4>
          <ul class="footer-links">
            <li><router-link to="/" @click="handleHomeClick">Strona główna</router-link></li>
            <li><router-link to="/powierzchnie-reklamowe">Wszystkie ogłoszenia</router-link></li>
            <li><router-link to="/dodaj-powierzchnie-reklamowa">Dodaj ogłoszenie</router-link></li>
            <li><router-link to="/dla-agencji">Dla agencji</router-link></li>
            <li><router-link to="/blog">Blog</router-link></li>
            <li><router-link to="/faq">FAQ</router-link></li>
          </ul>
        </div>

        <div class="footer-section" :class="{ 'is-open': openSections.info }">
          <h4 @click="toggleSection('info')">
            Informacje
            <svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none">
              <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </h4>
          <ul class="footer-links">
            <li><router-link to="/regulamin">Regulamin</router-link></li>
            <li><router-link to="/polityka-prywatnosci">Polityka prywatności</router-link></li>
            <li><router-link to="/kontakt">Kontakt</router-link></li>
          </ul>
        </div>

        <div class="footer-section" :class="{ 'is-open': openSections.categories }">
          <h4 @click="toggleSection('categories')">
            Kategorie powierzchni
            <svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none">
              <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </h4>
          <ul class="footer-links">
            <li><router-link to="/powierzchnie-reklamowe/billboardy" @click="clearSearchFlag">Billboardy</router-link></li>
            <li><router-link to="/powierzchnie-reklamowe/citylighty" @click="clearSearchFlag">Citylighty</router-link></li>
            <li><router-link to="/powierzchnie-reklamowe/ekrany-led" @click="clearSearchFlag">Ekrany LED</router-link></li>
            <li><router-link to="/powierzchnie-reklamowe/banery" @click="clearSearchFlag">Banery</router-link></li>
            <li><router-link to="/powierzchnie-reklamowe/sciany-reklamowe" @click="clearSearchFlag">Ściany reklamowe</router-link></li>
            <li><router-link to="/powierzchnie-reklamowe/totemy-reklamowe" @click="clearSearchFlag">Totemy reklamowe</router-link></li>
            <li><router-link to="/powierzchnie-reklamowe/reklama-w-transporcie" @click="clearSearchFlag">Reklama w transporcie</router-link></li>
            <li><router-link to="/powierzchnie-reklamowe/reklama-mobilna" @click="clearSearchFlag">Reklama mobilna</router-link></li>
            <li><router-link to="/powierzchnie-reklamowe/inne" @click="clearSearchFlag">Inne</router-link></li>
          </ul>
        </div>

        <div class="footer-section" :class="{ 'is-open': openSections.cities }">
          <h4 @click="toggleSection('cities')">
            Popularne miasta
            <svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none">
              <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </h4>
          <ul class="footer-links">
            <li v-for="city in navStore.cities" :key="city.slug">
              <router-link :to="`/powierzchnie-reklamowe/${city.slug}`" @click="clearSearchFlag">{{ city.name }}</router-link>
            </li>
          </ul>
        </div>
      </div>

      <!-- Popularne wyszukiwania - SEO -->
      <div class="popular-searches-section" :class="{ 'is-open': openSections.searches }">
        <h4 @click="toggleSection('searches')">
          Popularne wyszukiwania
          <svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none">
            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </h4>
        <div class="popular-searches-grid">
          <router-link
            v-for="search in navStore.combos"
            :key="`${search.typeSlug}-${search.citySlug}`"
            :to="`/powierzchnie-reklamowe/${search.typeSlug}/${search.citySlug}`"
            class="search-tag"
            @click="clearSearchFlag"
          >
            {{ search.label }}
          </router-link>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; {{ currentYear }} ReklaMap. Wszelkie prawa zastrzeżone.</p>
        <p class="recaptcha-notice">
          Ta strona jest chroniona przez reCAPTCHA i podlega
          <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Polityce prywatności</a>
          oraz
          <a href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer">Warunkom usługi</a>
          Google.
        </p>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.app-footer {
  background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
  color: #e5e7eb;
  padding: 4rem 0 2rem;
}

.footer-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

.footer-content {
  display: grid;
  grid-template-columns: 2fr 1.2fr 1fr 1.2fr 1fr;
  gap: 2rem;
  margin-bottom: 3rem;
}

.brand-section {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.brand-header {
  display: flex;
  align-items: center;
  gap: 0;
}

.brand-logo {
  height: 84px;
  width: auto;
  object-fit: contain;
}

.footer-section h3 {
  font-size: 1.75rem;
  margin: 0;
  color: white;
  font-weight: 700;
}

.footer-section h4 {
  font-size: 1.1rem;
  margin: 0 0 1.25rem 0;
  color: white;
  font-weight: 600;
}

.footer-description {
  color: #d1d5db;
  line-height: 1.7;
  margin: 0;
  font-size: 1rem;
}

.footer-contact {
  margin-top: 0;
}

.email-link {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  color: #10B981;
  text-decoration: none;
  font-size: 1rem;
  font-weight: 500;
  transition: all 0.2s;
  padding: 0.75rem 1.25rem;
  background: rgba(16, 185, 129, 0.1);
  border-radius: 8px;
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.email-link:hover {
  background: rgba(16, 185, 129, 0.15);
  border-color: rgba(16, 185, 129, 0.3);
  transform: translateY(-2px);
}

.email-link svg {
  flex-shrink: 0;
  color: #10B981;
}

.footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.footer-links li {
  margin: 0;
}

.footer-links a {
  color: #d1d5db;
  text-decoration: none;
  transition: color 0.2s;
  display: inline-block;
  font-size: 0.95rem;
}

.footer-links a:hover {
  color: #10B981;
}

.popular-searches-section {
  margin-bottom: 3rem;
  padding-top: 2rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.popular-searches-section h4 {
  font-size: 1.1rem;
  margin: 0 0 1.5rem 0;
  color: white;
  font-weight: 600;
}

.popular-searches-grid {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.75rem;
}

.search-tag {
  display: inline-block;
  padding: 0.5rem 1.25rem;
  background: rgba(102, 126, 234, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 100px;
  color: #a5b4fc;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.search-tag:hover {
  background: rgba(16, 185, 129, 0.1);
  border-color: rgba(16, 185, 129, 0.3);
  color: #10B981;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.footer-bottom {
  padding-top: 2rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  text-align: center;
}

.footer-bottom p {
  margin: 0;
  color: #9ca3af;
  font-size: 0.9rem;
}

.recaptcha-notice {
  margin-top: 1rem;
  font-size: 0.8rem;
  color: #6b7280;
  line-height: 1.5;
}

.recaptcha-notice a {
  color: #a5b4fc;
  text-decoration: none;
  transition: color 0.2s;
}

.recaptcha-notice a:hover {
  color: #10B981;
  text-decoration: underline;
}

.chevron {
  display: none;
  transition: transform 0.3s ease;
}

@media (max-width: 1024px) {
  .footer-content {
    grid-template-columns: 1fr 1fr;
    gap: 2.5rem;
  }

  .brand-section {
    grid-column: 1 / -1;
  }
  
  .popular-searches-grid {
    gap: 0.5rem;
  }
  
  .search-tag {
    font-size: 0.8rem;
    padding: 0.4rem 0.85rem;
  }
}

@media (max-width: 768px) {
  .app-footer {
    padding: 3rem 0 1.5rem;
  }

  .footer-content {
    grid-template-columns: 1fr;
    gap: 0;
  }

  .footer-section {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    padding: 1rem 0;
  }

  .footer-section:last-child {
    border-bottom: none;
  }

  .brand-section {
    padding-bottom: 2rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 1rem;
  }

  .footer-section h4 {
    margin-bottom: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    padding: 0.5rem 0;
  }

  .chevron {
    display: block;
  }

  .is-open .chevron {
    transform: rotate(180deg);
  }

  .footer-links {
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s ease-in-out;
    opacity: 0;
    gap: 0.5rem;
    padding-top: 0;
  }

  .is-open .footer-links {
    max-height: 500px;
    opacity: 1;
    padding-top: 1rem;
    padding-bottom: 1rem;
  }

  .popular-searches-section {
    padding-top: 1.5rem;
  }

  .popular-searches-section h4 {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
  }

  .popular-searches-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s ease-in-out;
    opacity: 0;
    padding-top: 0;
    gap: 0.5rem;
  }

  .search-tag {
    display: inline-block;
    width: auto;
    border-radius: 100px;
    padding: 0.4rem 0.85rem;
    font-size: 0.8rem;
  }

  .is-open .popular-searches-grid {
    max-height: 1500px;
    opacity: 1;
    padding-top: 1rem;
  }

  .footer-bottom {
    padding-top: 1.5rem;
    margin-top: 1rem;
  }
}
</style>
