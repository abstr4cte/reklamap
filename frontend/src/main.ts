import { createApp } from 'vue'
import { createPinia } from 'pinia'
import * as Sentry from '@sentry/vue'
import './style.css'
import './assets/custom-inputs.css'
import App from './App.vue'
import router from './router'
import { useSearchStore } from './stores/useSearchStore'

// Import axios configuration
import './api/axios'

const pinia = createPinia()
const app = createApp(App)

// Initialize Sentry (only if DSN is configured)
if (import.meta.env.VITE_SENTRY_DSN) {
  Sentry.init({
    app,
    dsn: import.meta.env.VITE_SENTRY_DSN,
    integrations: [
      Sentry.browserTracingIntegration({ router }),
      Sentry.replayIntegration({
        maskAllText: false,
        blockAllMedia: false,
      }),
    ],
    // Performance Monitoring
    tracesSampleRate: parseFloat(import.meta.env.VITE_SENTRY_TRACES_SAMPLE_RATE || '1.0'),
    // Session Replay
    replaysSessionSampleRate: parseFloat(import.meta.env.VITE_SENTRY_REPLAYS_SESSION_SAMPLE_RATE || '0.1'),
    replaysOnErrorSampleRate: parseFloat(import.meta.env.VITE_SENTRY_REPLAYS_ON_ERROR_SAMPLE_RATE || '1.0'),
    // Environment
    environment: import.meta.env.MODE,
    // Send default PII data (IP addresses, etc.)
    sendDefaultPii: true,
  })
}

app.use(pinia)
app.use(router)
app.mount('#app')

// Build-time prerender (scripts/prerender.mjs) czyta ten kolektor przez puppeteer i wstrzykuje
// wynik jako <script>window.__INITIAL_STATE__=…</script> do prerenderowanego HTML — dzięki temu
// hydratacja seeduje store i nie kasuje treści do pustki. Bez znaczenia dla użytkownika; dane
// listingów są publiczne. Patrz useSearchStore (_ssr).
if (typeof window !== 'undefined') {
  ;(window as any).__collectSSRState = () => {
    try {
      const out: Record<string, unknown> = {}
      const s = useSearchStore(pinia)
      if (Array.isArray(s.listings) && s.listings.length > 0) {
        out.search = { listings: s.listings, serverTotal: s.serverTotal, serverLastPage: s.serverLastPage }
      }
      // Dane pojedynczego ogłoszenia (ustawiane przez AdDetailPage po udanym loadAd) — dla stron szczegółów.
      const ad = (window as any).__ssrAd
      if (ad && ad.id) out.ad = ad
      return Object.keys(out).length ? out : null
    } catch {
      return null
    }
  }
}
