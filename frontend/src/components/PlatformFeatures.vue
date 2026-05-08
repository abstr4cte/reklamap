<script setup lang="ts">
const goToMap = (): void => {
  const mapContainer = document.querySelector('[data-poland-map] .map-container')
  const header = document.querySelector('.app-header')

  if (mapContainer && header) {
    const headerRect = header.getBoundingClientRect()
    const headerStyles = window.getComputedStyle(header)
    const headerHeight = headerRect.height + parseFloat(headerStyles.marginTop) + parseFloat(headerStyles.marginBottom)

    const elementPosition = mapContainer.getBoundingClientRect().top + window.pageYOffset
    const offsetPosition = elementPosition - headerHeight

    window.scrollTo({
      top: offsetPosition,
      behavior: 'smooth'
    })
  }
}
</script>

<template>
  <section class="features-section" aria-labelledby="features-heading">
    <div class="features-container">
      <h2 id="features-heading" class="features-title">
        Narzędzia, które ułatwiają wybór powierzchni
      </h2>
      <p class="features-subtitle">
        Mniej wyjazdów na miejsce, szybsza decyzja, jasne porównanie ofert obok siebie.
      </p>

      <div class="features-grid">
        <button type="button" class="feature-card" @click="goToMap">
          <div class="feature-icon" aria-hidden="true">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/>
              <line x1="8" y1="2" x2="8" y2="18"/>
              <line x1="16" y1="6" x2="16" y2="22"/>
            </svg>
          </div>
          <h3 class="feature-name">Interaktywna mapa Polski</h3>
          <p class="feature-desc">
            Wszystkie powierzchnie na jednej mapie. Filtruj po typie, cenie i lokalizacji
            w czasie rzeczywistym.
          </p>
          <span class="feature-cta">Zobacz mapę →</span>
        </button>

        <div class="feature-card feature-card-static">
          <div class="feature-icon" aria-hidden="true">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </div>
          <h3 class="feature-name">Widok Street View</h3>
          <p class="feature-desc">
            Sprawdź otoczenie nośnika bez wyjazdu na miejsce — bezpośrednio
            z poziomu ogłoszenia.
          </p>
          <span class="feature-cta feature-cta-muted">Dostępne w każdym ogłoszeniu</span>
        </div>

        <div class="feature-card feature-card-static">
          <div class="feature-icon" aria-hidden="true">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
              <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
              <line x1="9" y1="12" x2="15" y2="12"/>
              <line x1="9" y1="16" x2="13" y2="16"/>
            </svg>
          </div>
          <h3 class="feature-name">Porównywarka ofert</h3>
          <p class="feature-desc">
            Zobacz parametry, ceny i lokalizacje kilku powierzchni obok siebie —
            kliknij ikonę porównywarki na kafelku ogłoszenia lub w jego widoku.
            Porównywać można nośniki tego samego typu.
          </p>
          <span class="feature-cta feature-cta-muted">Przycisk na każdym kafelku i w ogłoszeniu</span>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.features-section {
  background: #ffffff;
  padding: 4.5rem 0;
  position: relative;
}

.features-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
}

.features-title {
  font-size: 2.25rem;
  font-weight: 800;
  text-align: center;
  color: var(--text-main, #1f2937);
  margin: 0 0 0.75rem;
  letter-spacing: -0.01em;
}

.features-subtitle {
  text-align: center;
  color: var(--text-muted, #6b7280);
  font-size: 1.05rem;
  max-width: 640px;
  margin: 0 auto 3rem;
  line-height: 1.6;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
}

.feature-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  text-align: left;
  background: #f9fafb;
  border: 1.5px solid var(--border-color, #e5e7eb);
  border-radius: 16px;
  padding: 1.75rem;
  font-family: inherit;
  cursor: pointer;
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease, background 0.25s ease;
}

.feature-card:hover {
  transform: translateY(-4px);
  background: #ffffff;
  border-color: rgba(102, 126, 234, 0.4);
  box-shadow: 0 12px 28px -10px rgba(102, 126, 234, 0.25);
}

.feature-card-static {
  cursor: default;
}

.feature-card-static:hover {
  transform: none;
  background: #f9fafb;
  border-color: var(--border-color, #e5e7eb);
  box-shadow: none;
}

.feature-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #ffffff;
  margin-bottom: 1.25rem;
  box-shadow: 0 6px 16px -4px rgba(102, 126, 234, 0.4);
}

.feature-name {
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--text-main, #1f2937);
  margin: 0 0 0.5rem;
}

.feature-desc {
  font-size: 0.95rem;
  color: var(--text-muted, #6b7280);
  line-height: 1.6;
  margin: 0 0 1.25rem;
  flex: 1;
}

.feature-cta {
  font-size: 0.9rem;
  font-weight: 700;
  color: #4f46e5;
  align-self: flex-start;
}

.feature-cta-muted {
  color: var(--text-muted, #6b7280);
  font-weight: 500;
}

@media (max-width: 900px) {
  .features-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
    max-width: 520px;
    margin: 0 auto;
  }

  .features-section {
    padding: 3rem 0;
  }

  .features-title {
    font-size: 1.65rem;
  }

  .features-subtitle {
    font-size: 1rem;
    margin-bottom: 2rem;
  }

  .feature-card {
    padding: 1.5rem;
  }
}
</style>
