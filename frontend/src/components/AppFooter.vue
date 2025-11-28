<script setup lang="ts">
const currentYear = new Date().getFullYear()
</script>

<template>
  <footer class="app-footer">
    <div class="footer-container">
      <div class="footer-content">
        <div class="footer-section brand-section">
          <h3>AdSpace</h3>
          <p class="footer-description">
            Platforma do wynajmu powierzchni reklamowych w całej Polsce.
            Szybko, wygodnie i bezpiecznie.
          </p>
          <div class="footer-contact">
            <a href="mailto:kontakt@adspace.pl" class="email-link">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2"/>
                <path d="m22 6-10 7L2 6" stroke="currentColor" stroke-width="2"/>
              </svg>
              kontakt@adspace.pl
            </a>
          </div>
        </div>

        <div class="footer-section">
          <h4>Nawigacja</h4>
          <ul class="footer-links">
            <li><router-link to="/">Strona główna</router-link></li>
            <li><router-link to="/dodaj-ogloszenie">Dodaj ogłoszenie</router-link></li>
            <li><router-link to="/blog">Blog</router-link></li>
            <li><router-link to="/faq">FAQ</router-link></li>
          </ul>
        </div>

        <div class="footer-section">
          <h4>Informacje</h4>
          <ul class="footer-links">
            <li><router-link to="/regulamin">Regulamin</router-link></li>
            <li><router-link to="/polityka-prywatnosci">Polityka prywatności</router-link></li>
            <li><router-link to="/kontakt">Kontakt</router-link></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; {{ currentYear }} AdSpace. Wszelkie prawa zastrzeżone.</p>
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
  grid-template-columns: 2fr 1fr 1fr;
  gap: 4rem;
  margin-bottom: 3rem;
}

.brand-section {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
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
  margin-top: auto;
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

@media (max-width: 1024px) {
  .footer-content {
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
  }

  .brand-section {
    grid-column: 1 / -1;
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

  .brand-section {
    grid-column: 1;
  }

  .footer-bottom {
    padding-top: 1.5rem;
  }
}
</style>
