/**
 * Microsoft Clarity — heatmapy i nagrania sesji (darmowe, bez limitu ruchu).
 *
 * Po co: GA4 mówi ILE osób weszło na ogłoszenie i ile kliknęło kontakt, ale nie mówi DLACZEGO
 * reszta odpada. Clarity pokazuje, gdzie ludzie faktycznie szukają kontaktu i gdzie się zacinają.
 *
 * Bramki (obie konieczne):
 *  1. brak VITE_CLARITY_ID  → nic się nie ładuje (dev, CI, buildy bez konfiguracji)
 *  2. UA bota               → nic się nie ładuje; prerender (puppeteer) i crawlery nie mają
 *                             zaśmiecać nagrań ani obciążać renderu botów dodatkowym skryptem.
 *                             Ten sam wzorzec co guard stale-deploy w index.html.
 */

const BOT_UA_RE = /bot|crawl|spider|slurp|mediapartners-google|google-inspectiontool|googleother|google-extended|facebookexternalhit|headlesschrome/i

type ClarityFn = ((...args: unknown[]) => void) & { q?: unknown[][] }

declare global {
  interface Window {
    clarity?: ClarityFn
  }
}

export function initClarity(): void {
  if (typeof window === 'undefined' || typeof document === 'undefined') return

  const projectId = import.meta.env.VITE_CLARITY_ID as string | undefined
  if (!projectId) return

  const ua = navigator.userAgent || ''
  if (!ua || BOT_UA_RE.test(ua)) return

  if (window.clarity) return

  const queue: ClarityFn = function (...args: unknown[]): void {
    ;(queue.q = queue.q || []).push(args)
  }
  window.clarity = queue

  const script = document.createElement('script')
  script.async = true
  script.src = `https://www.clarity.ms/tag/${projectId}`
  document.head.appendChild(script)
}
