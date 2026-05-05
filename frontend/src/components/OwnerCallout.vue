<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

const goToAddAd = (): void => {
  router.push('/dodaj-powierzchnie-reklamowa')
}
</script>

<template>
  <section id="owner-callout" class="owner-section" aria-labelledby="owner-heading">
    <div class="owner-container">
      <div class="owner-header">
        <h2 id="owner-heading" class="owner-title">
          Masz wolną powierzchnię reklamową? <span class="highlight">Wystaw ją bezpłatnie</span>
        </h2>
        <p class="owner-subtitle">
          Bez kont, bez haseł, bez prowizji. Dodanie ogłoszenia zajmuje około 5&nbsp;minut.
        </p>

        <ul class="usp-pills" role="list">
          <li class="usp-pill">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            Bezpłatnie
          </li>
          <li class="usp-pill">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            Bez zakładania konta
          </li>
          <li class="usp-pill">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            Bez prowizji od transakcji
          </li>
        </ul>
      </div>

      <h3 class="steps-heading">Jak dodać ogłoszenie?</h3>
      <ol class="steps-grid" role="list">
        <li class="step">
          <div class="step-num">1</div>
          <div class="step-body">
            <h4 class="step-title">Wypełnij formularz</h4>
            <p class="step-desc">Lokalizacja, zdjęcia, cena i&nbsp;kilka parametrów nośnika. Zajmuje około 5&nbsp;minut.</p>
          </div>
        </li>
        <li class="step">
          <div class="step-num">2</div>
          <div class="step-body">
            <h4 class="step-title">Ogłoszenie pojawia się na&nbsp;mapie</h4>
            <p class="step-desc">Od&nbsp;razu jest widoczne dla reklamodawców szukających powierzchni w&nbsp;Twojej okolicy.</p>
          </div>
        </li>
        <li class="step">
          <div class="step-num">3</div>
          <div class="step-body">
            <h4 class="step-title">Reklamodawca kontaktuje się bezpośrednio</h4>
            <p class="step-desc">Wiadomości z&nbsp;formularza idą na&nbsp;Twój e&#8209;mail, a&nbsp;numer telefonu jest widoczny w&nbsp;ogłoszeniu <span class="nowrap">–&nbsp;bez</span> pośrednictwa platformy.</p>
          </div>
        </li>
      </ol>

      <div class="manage-info">
        <div class="manage-info-icon" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </div>
        <div class="manage-info-body">
          <h3 class="manage-info-title">Jak później edytować ogłoszenie?</h3>
          <p class="manage-info-desc">
            Klikasz <strong>„Zarządzaj"</strong>, podajesz e&#8209;mail użyty przy dodawaniu i&nbsp;dostajesz link
            do&nbsp;panelu ważny <strong>30&nbsp;dni</strong> –&nbsp;możesz go&nbsp;wygenerować ponownie
            w&nbsp;każdej chwili. Z&nbsp;poziomu panelu edytujesz, usuwasz, sprawdzasz statystyki. Bez kont, bez haseł.
          </p>
        </div>
      </div>

      <div class="cta-wrapper">
        <button type="button" class="cta-button" @click="goToAddAd" aria-label="Dodaj powierzchnię reklamową bezpłatnie">
          <svg width="22" height="22" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="M10 5V15M5 10H15" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
          </svg>
          Dodaj powierzchnię reklamową
        </button>
        <p class="cta-microcopy">Bez rejestracji · Bez opłat · Wypełnienie ~5 min</p>
      </div>
    </div>
  </section>
</template>

<style scoped>
.owner-section {
  background: #f4f5fc;
  padding: 4.5rem 0;
  position: relative;
  overflow: hidden;
  scroll-margin-top: 100px;
}


.owner-container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 2rem;
  position: relative;
  z-index: 1;
}

.owner-header {
  text-align: center;
  margin-bottom: 3rem;
}

.owner-title {
  font-size: 2.4rem;
  font-weight: 800;
  color: var(--text-main, #1f2937);
  margin: 0 0 0.85rem 0;
  line-height: 1.25;
  letter-spacing: -0.02em;
}

.owner-title .highlight {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.owner-subtitle {
  font-size: 1.1rem;
  color: var(--text-muted, #6b7280);
  margin: 0 0 1.75rem 0;
  font-weight: 500;
  text-wrap: pretty;
}

.usp-pills {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 0.75rem;
  list-style: none;
  padding: 0;
  margin: 0;
}

.usp-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1.1rem;
  background: #ffffff;
  border: 1.5px solid rgba(102, 126, 234, 0.25);
  border-radius: 999px;
  font-size: 0.95rem;
  font-weight: 600;
  color: #4f46e5;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.08);
}

.usp-pill svg {
  color: #10b981;
  flex-shrink: 0;
}

.steps-heading {
  font-size: 1.5rem;
  font-weight: 700;
  text-align: center;
  color: var(--text-main, #1f2937);
  margin: 0 0 2rem 0;
}

.steps-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
  list-style: none;
  padding: 0;
  margin: 0 0 3rem 0;
}

.step {
  background: #ffffff;
  border: 1px solid rgba(102, 126, 234, 0.15);
  border-radius: 16px;
  padding: 1.75rem 1.5rem;
  display: flex;
  gap: 1rem;
  align-items: flex-start;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.08);
}

.step:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 32px rgba(102, 126, 234, 0.18);
}

.step-num {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 1.1rem;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.35);
}

.step-body {
  flex: 1;
}

.step-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-main, #1f2937);
  margin: 0.15rem 0 0.6rem 0;
  line-height: 1.35;
  min-height: calc(1.35em * 2);
}

.nowrap {
  white-space: nowrap;
}

.step-desc {
  font-size: 0.9rem;
  color: var(--text-muted, #6b7280);
  margin: 0;
  line-height: 1.55;
  text-wrap: pretty;
}

.manage-info {
  display: flex;
  gap: 1.25rem;
  align-items: flex-start;
  padding: 1.5rem 1.75rem;
  margin: 0 auto 2.5rem;
  max-width: 820px;
  background: #ffffff;
  border: 1px solid rgba(102, 126, 234, 0.2);
  border-left: 4px solid #667eea;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.08);
}

.manage-info-icon {
  flex-shrink: 0;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
}

.manage-info-body {
  flex: 1;
}

.manage-info-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--text-main, #1f2937);
  margin: 0 0 0.5rem 0;
  line-height: 1.35;
}

.manage-info-desc {
  font-size: 0.92rem;
  color: var(--text-muted, #6b7280);
  margin: 0;
  line-height: 1.65;
  text-wrap: pretty;
}

.manage-info-desc strong {
  color: var(--text-main, #1f2937);
  font-weight: 700;
}

.cta-wrapper {
  text-align: center;
}

.cta-button {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  padding: 1.1rem 2.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #fff;
  border: none;
  border-radius: 12px;
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.35);
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.cta-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 32px rgba(102, 126, 234, 0.45);
}

.cta-button:active {
  transform: translateY(0);
}

.cta-microcopy {
  margin: 0.85rem 0 0 0;
  font-size: 0.9rem;
  color: var(--text-muted, #6b7280);
  font-weight: 500;
}

@media (max-width: 900px) {
  .steps-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .step-title {
    min-height: 0;
  }
}

@media (max-width: 768px) {
  .owner-section {
    padding: 2.75rem 0;
  }

  .owner-container {
    padding: 0 1rem;
  }

  .owner-header {
    margin-bottom: 2.25rem;
  }

  .owner-title {
    font-size: 1.4rem;
    line-height: 1.3;
    margin-bottom: 0.6rem;
  }

  .owner-subtitle {
    font-size: 0.95rem;
    margin-bottom: 1.25rem;
    text-align: justify;
    text-justify: inter-word;
    hyphens: auto;
    -webkit-hyphens: auto;
  }

  .step-desc,
  .manage-info-desc {
    text-align: justify;
    text-justify: inter-word;
    hyphens: auto;
    -webkit-hyphens: auto;
  }

  .usp-pills {
    gap: 0.4rem;
  }

  .usp-pill {
    font-size: 0.78rem;
    padding: 0.4rem 0.75rem;
    gap: 0.35rem;
  }

  .usp-pill svg {
    width: 13px;
    height: 13px;
  }

  .steps-heading {
    font-size: 1.15rem;
    margin-bottom: 1.25rem;
  }

  .steps-grid {
    margin-bottom: 1.75rem;
  }

  .step {
    padding: 1.1rem 1.25rem;
    gap: 0.85rem;
    border-radius: 12px;
  }

  .step-num {
    width: 32px;
    height: 32px;
    font-size: 0.95rem;
  }

  .step-title {
    font-size: 0.95rem;
    margin: 0 0 0.3rem 0;
  }

  .step-desc {
    font-size: 0.85rem;
    line-height: 1.5;
  }

  .manage-info {
    flex-direction: row;
    gap: 0.85rem;
    padding: 1rem 1.1rem;
    margin-bottom: 1.75rem;
    border-radius: 10px;
  }

  .manage-info-icon {
    width: 36px;
    height: 36px;
  }

  .manage-info-icon svg {
    width: 18px;
    height: 18px;
  }

  .manage-info-title {
    font-size: 0.92rem;
    margin-bottom: 0.3rem;
  }

  .manage-info-desc {
    font-size: 0.82rem;
    line-height: 1.55;
  }

  .cta-button {
    width: 100%;
    justify-content: center;
    padding: 0.95rem 1rem;
    font-size: 0.95rem;
    gap: 0.45rem;
  }

  .cta-button svg {
    width: 18px;
    height: 18px;
  }

  .cta-microcopy {
    font-size: 0.82rem;
  }
}
</style>
