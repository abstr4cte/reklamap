<script setup lang="ts">
const currentYear = new Date().getFullYear()
</script>

<template>
  <footer class="app-footer">
    <div class="footer-container">
      <div class="footer-content">
        <div class="footer-section">
          <h3>AdSpace</h3>
          <p class="footer-description">
            Platforma do wynajmu powierzchni reklamowych w całej Polsce.
            Szybko, wygodnie i bezpiecznie.
          </p>
        </div>

        <div class="footer-section">
          <h4>Szybkie linki</h4>
          <ul class="footer-links">
            <li><router-link to="/">Strona główna</router-link></li>
            <li><router-link to="/dodaj-ogloszenie">Dodaj ogłoszenie</router-link></li>
            <li><router-link to="/blog">Blog</router-link></li>
            <li><router-link to="/faq">FAQ</router-link></li>
          </ul>
        </div>

        <div class="footer-section">
          <h4>Kontakt</h4>
          <ul class="footer-contact">
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="2"/>
                <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2"/>
              </svg>
              Warszawa, Polska
            </li>
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2"/>
                <path d="m22 6-10 7L2 6" stroke="currentColor" stroke-width="2"/>
              </svg>
              kontakt@adspace.pl
            </li>
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="2"/>
              </svg>
              +48 123 456 789
            </li>
          </ul>
        </div>

        <div class="footer-section">
          <h4>Śledź nas</h4>
          <div class="social-links">
            <a href="#" aria-label="Facebook">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
              </svg>
            </a>
            <a href="#" aria-label="Twitter">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/>
              </svg>
            </a>
            <a href="#" aria-label="LinkedIn">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6zM2 9h4v12H2z"/>
                <circle cx="4" cy="4" r="2"/>
              </svg>
            </a>
            <a href="#" aria-label="Instagram">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" fill="none" stroke="white" stroke-width="2"/>
                <circle cx="17.5" cy="6.5" r="1.5" fill="white"/>
              </svg>
            </a>
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; {{ currentYear }} AdSpace. Wszelkie prawa zastrzeżone.</p>
        <div class="footer-bottom-links">
          <router-link to="/regulamin">Regulamin</router-link>
          <span>•</span>
          <router-link to="/kontakt">Kontakt</router-link>
          <span>•</span>
          <router-link to="/faq">FAQ</router-link>
        </div>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.app-footer {
  background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
  color: #e5e7eb;
  padding: 4rem 0 2rem;
  margin-top: 4rem;
}

.footer-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

.footer-content {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 3rem;
  margin-bottom: 3rem;
}

.footer-section h3 {
  font-size: 1.75rem;
  margin: 0 0 1rem 0;
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
}

.footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-links li {
  margin-bottom: 0.75rem;
}

.footer-links a {
  color: #d1d5db;
  text-decoration: none;
  transition: color 0.2s;
  display: inline-block;
}

.footer-links a:hover {
  color: #10B981;
}

.footer-contact {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-contact li {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
  color: #d1d5db;
}

.footer-contact svg {
  flex-shrink: 0;
  color: #10B981;
}

.social-links {
  display: flex;
  gap: 1rem;
}

.social-links a {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  transition: all 0.3s;
}

.social-links a:hover {
  background: #10B981;
  transform: translateY(-3px);
}

.footer-bottom {
  padding-top: 2rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.footer-bottom p {
  margin: 0;
  color: #9ca3af;
}

.footer-bottom-links {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.footer-bottom-links a {
  color: #9ca3af;
  text-decoration: none;
  transition: color 0.2s;
}

.footer-bottom-links a:hover {
  color: #10B981;
}

.footer-bottom-links span {
  color: #6b7280;
}

@media (max-width: 1024px) {
  .footer-content {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 640px) {
  .app-footer {
    padding: 3rem 0 1.5rem;
  }

  .footer-content {
    grid-template-columns: 1fr;
    gap: 2rem;
  }

  .footer-bottom {
    flex-direction: column;
    text-align: center;
  }

  .footer-bottom-links {
    flex-wrap: wrap;
    justify-content: center;
  }
}
</style>
