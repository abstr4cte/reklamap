<script setup lang="ts">
const currentYear = new Date().getFullYear()

const popularCities = [
  { name: 'Warszawa', slug: 'warszawa' },
  { name: 'Kraków', slug: 'krakow' },
  { name: 'Wrocław', slug: 'wroclaw' },
  { name: 'Poznań', slug: 'poznan' },
  { name: 'Gdańsk', slug: 'gdansk' },
  { name: 'Łódź', slug: 'lodz' },
  { name: 'Katowice', slug: 'katowice' },
  { name: 'Szczecin', slug: 'szczecin' },
  { name: 'Bydgoszcz', slug: 'bydgoszcz' },
  { name: 'Lublin', slug: 'lublin' },
  { name: 'Białystok', slug: 'bialystok' },
  { name: 'Gdynia', slug: 'gdynia' }
]

const popularSearches = [
  // Warszawa - wszystkie typy
  { label: 'Billboardy Warszawa', type: 'billboardy', city: 'warszawa' },
  { label: 'Citylighty Warszawa', type: 'citylighty', city: 'warszawa' },
  { label: 'Ekrany LED Warszawa', type: 'ekrany-led', city: 'warszawa' },
  { label: 'Banery Warszawa', type: 'banery', city: 'warszawa' },
  { label: 'Ściany reklamowe Warszawa', type: 'sciany-reklamowe', city: 'warszawa' },
  { label: 'Totemy reklamowe Warszawa', type: 'totemy-reklamowe', city: 'warszawa' },
  { label: 'Reklama w transporcie Warszawa', type: 'reklama-w-transporcie', city: 'warszawa' },
  { label: 'Reklama mobilna Warszawa', type: 'reklama-mobilna', city: 'warszawa' },
  
  // Kraków - wszystkie typy
  { label: 'Billboardy Kraków', type: 'billboardy', city: 'krakow' },
  { label: 'Citylighty Kraków', type: 'citylighty', city: 'krakow' },
  { label: 'Ekrany LED Kraków', type: 'ekrany-led', city: 'krakow' },
  { label: 'Banery Kraków', type: 'banery', city: 'krakow' },
  { label: 'Ściany reklamowe Kraków', type: 'sciany-reklamowe', city: 'krakow' },
  { label: 'Totemy reklamowe Kraków', type: 'totemy-reklamowe', city: 'krakow' },
  { label: 'Reklama w transporcie Kraków', type: 'reklama-w-transporcie', city: 'krakow' },
  { label: 'Reklama mobilna Kraków', type: 'reklama-mobilna', city: 'krakow' },
  
  // Wrocław - wszystkie typy
  { label: 'Billboardy Wrocław', type: 'billboardy', city: 'wroclaw' },
  { label: 'Citylighty Wrocław', type: 'citylighty', city: 'wroclaw' },
  { label: 'Ekrany LED Wrocław', type: 'ekrany-led', city: 'wroclaw' },
  { label: 'Banery Wrocław', type: 'banery', city: 'wroclaw' },
  { label: 'Ściany reklamowe Wrocław', type: 'sciany-reklamowe', city: 'wroclaw' },
  { label: 'Totemy reklamowe Wrocław', type: 'totemy-reklamowe', city: 'wroclaw' },
  { label: 'Reklama w transporcie Wrocław', type: 'reklama-w-transporcie', city: 'wroclaw' },
  { label: 'Reklama mobilna Wrocław', type: 'reklama-mobilna', city: 'wroclaw' },
  
  // Poznań - wszystkie typy
  { label: 'Billboardy Poznań', type: 'billboardy', city: 'poznan' },
  { label: 'Citylighty Poznań', type: 'citylighty', city: 'poznan' },
  { label: 'Ekrany LED Poznań', type: 'ekrany-led', city: 'poznan' },
  { label: 'Banery Poznań', type: 'banery', city: 'poznan' },
  { label: 'Ściany reklamowe Poznań', type: 'sciany-reklamowe', city: 'poznan' },
  { label: 'Totemy reklamowe Poznań', type: 'totemy-reklamowe', city: 'poznan' },
  { label: 'Reklama w transporcie Poznań', type: 'reklama-w-transporcie', city: 'poznan' },
  { label: 'Reklama mobilna Poznań', type: 'reklama-mobilna', city: 'poznan' },
  
  // Gdańsk - wszystkie typy
  { label: 'Billboardy Gdańsk', type: 'billboardy', city: 'gdansk' },
  { label: 'Citylighty Gdańsk', type: 'citylighty', city: 'gdansk' },
  { label: 'Ekrany LED Gdańsk', type: 'ekrany-led', city: 'gdansk' },
  { label: 'Banery Gdańsk', type: 'banery', city: 'gdansk' },
  { label: 'Ściany reklamowe Gdańsk', type: 'sciany-reklamowe', city: 'gdansk' },
  { label: 'Totemy reklamowe Gdańsk', type: 'totemy-reklamowe', city: 'gdansk' },
  { label: 'Reklama w transporcie Gdańsk', type: 'reklama-w-transporcie', city: 'gdansk' },
  { label: 'Reklama mobilna Gdańsk', type: 'reklama-mobilna', city: 'gdansk' },
  
  // Łódź - wszystkie typy
  { label: 'Billboardy Łódź', type: 'billboardy', city: 'lodz' },
  { label: 'Citylighty Łódź', type: 'citylighty', city: 'lodz' },
  { label: 'Ekrany LED Łódź', type: 'ekrany-led', city: 'lodz' },
  { label: 'Banery Łódź', type: 'banery', city: 'lodz' },
  { label: 'Ściany reklamowe Łódź', type: 'sciany-reklamowe', city: 'lodz' },
  { label: 'Totemy reklamowe Łódź', type: 'totemy-reklamowe', city: 'lodz' },
  { label: 'Reklama w transporcie Łódź', type: 'reklama-w-transporcie', city: 'lodz' },
  { label: 'Reklama mobilna Łódź', type: 'reklama-mobilna', city: 'lodz' },
  
  // Katowice - wszystkie typy
  { label: 'Billboardy Katowice', type: 'billboardy', city: 'katowice' },
  { label: 'Citylighty Katowice', type: 'citylighty', city: 'katowice' },
  { label: 'Ekrany LED Katowice', type: 'ekrany-led', city: 'katowice' },
  { label: 'Banery Katowice', type: 'banery', city: 'katowice' },
  { label: 'Ściany reklamowe Katowice', type: 'sciany-reklamowe', city: 'katowice' },
  { label: 'Totemy reklamowe Katowice', type: 'totemy-reklamowe', city: 'katowice' },
  { label: 'Reklama w transporcie Katowice', type: 'reklama-w-transporcie', city: 'katowice' },
  { label: 'Reklama mobilna Katowice', type: 'reklama-mobilna', city: 'katowice' }
]
</script>

<template>
  <footer class="app-footer">
    <div class="footer-container">
      <div class="footer-content">
        <div class="footer-section brand-section">
          <h3>ReklaMap</h3>
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

        <div class="footer-section">
          <h4>Nawigacja</h4>
          <ul class="footer-links">
            <li><router-link to="/">Strona główna</router-link></li>
            <li><router-link to="/powierzchnie-reklamowe">Wszystkie ogłoszenia</router-link></li>
            <li><router-link to="/dodaj-powierzchnie-reklamowa">Dodaj ogłoszenie</router-link></li>
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

        <div class="footer-section">
          <h4>Kategorie powierzchni</h4>
          <ul class="footer-links">
            <li><router-link to="/powierzchnie-reklamowe/billboardy">Billboardy</router-link></li>
            <li><router-link to="/powierzchnie-reklamowe/citylighty">Citylighty</router-link></li>
            <li><router-link to="/powierzchnie-reklamowe/ekrany-led">Ekrany LED</router-link></li>
            <li><router-link to="/powierzchnie-reklamowe/banery">Banery</router-link></li>
            <li><router-link to="/powierzchnie-reklamowe/sciany-reklamowe">Ściany reklamowe</router-link></li>
            <li><router-link to="/powierzchnie-reklamowe/totemy-reklamowe">Totemy reklamowe</router-link></li>
            <li><router-link to="/powierzchnie-reklamowe/reklama-w-transporcie">Reklama w transporcie</router-link></li>
            <li><router-link to="/powierzchnie-reklamowe/reklama-mobilna">Reklama mobilna</router-link></li>
            <li><router-link to="/powierzchnie-reklamowe/inne">Inne</router-link></li>
          </ul>
        </div>

        <div class="footer-section">
          <h4>Popularne miasta</h4>
          <ul class="footer-links">
            <li v-for="city in popularCities" :key="city.slug">
              <router-link :to="`/powierzchnie-reklamowe/${city.slug}`">{{ city.name }}</router-link>
            </li>
          </ul>
        </div>
      </div>

      <!-- Popularne wyszukiwania - SEO -->
      <div class="popular-searches-section">
        <h4>Popularne wyszukiwania</h4>
        <div class="popular-searches-grid">
          <router-link
            v-for="search in popularSearches"
            :key="`${search.type}-${search.city}`"
            :to="`/powierzchnie-reklamowe/${search.type}/${search.city}`"
            class="search-tag"
          >
            {{ search.label }}
          </router-link>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; {{ currentYear }} ReklaMap. Wszelkie prawa zastrzeżone.</p>
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
  gap: 0.75rem;
}

.search-tag {
  display: inline-block;
  padding: 0.5rem 1rem;
  background: rgba(102, 126, 234, 0.1);
  border: 1px solid rgba(102, 126, 234, 0.3);
  border-radius: 20px;
  color: #a5b4fc;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

.search-tag:hover {
  background: rgba(102, 126, 234, 0.2);
  border-color: rgba(102, 126, 234, 0.5);
  color: #c7d2fe;
  transform: translateY(-2px);
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
  
  .popular-searches-section {
    margin-bottom: 2rem;
  }
  
  .popular-searches-section h4 {
    font-size: 1rem;
    margin-bottom: 1rem;
  }

  .footer-bottom {
    padding-top: 1.5rem;
  }
}
</style>
