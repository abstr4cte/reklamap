/**
 * Budowa schematu FAQPage z treści artykułu blogowego.
 *
 * Rich snippet z rozwijanymi pytaniami to jedyny typ schematu, który realnie podnosi CTR —
 * a CTR jest wąskim gardłem (9,8 tys. wyświetleń → 179 kliknięć w 3 miesiące).
 *
 * Wydzielone z BlogPostPage.vue, żeby dało się testować bez montowania komponentu
 * (ten sam wzorzec co utils/listingsSeo.ts).
 */

export interface FaqQuestion {
  '@type': 'Question'
  name: string
  acceptedAnswer: { '@type': 'Answer'; text: string }
}

export interface FaqPageSchema {
  '@context': 'https://schema.org'
  '@type': 'FAQPage'
  mainEntity: FaqQuestion[]
}

/**
 * Warianty nagłówka sekcji FAQ spotykane w artykułach: „Najczęściej zadawane pytania",
 * „FAQ", „FAQ — najczęstsze pytania o citylight", „Często zadawane pytania".
 * Wcześniejsze dopasowanie po twardym stringu „Najczęściej zadawane pytania" gubiło
 * artykuły z krótkim „## FAQ" — 3 z 28 opublikowanych nie miały rich snippetu.
 */
export const FAQ_HEADING_RE =
  /\bFAQ\b|naj(?:częściej|częstsze)\s+(?:zadawane\s+)?pytani|często\s+zadawane\s+pytani/i

/**
 * Parsuje sekcję FAQ z HTML artykułu. Zwraca null, gdy nie ma nagłówka FAQ
 * albo gdy pod nagłówkiem nie ma ani jednej pary pytanie/odpowiedź.
 *
 * Oczekiwana struktura (wynik renderowania markdownu):
 *   <h2>FAQ</h2>
 *   <p><strong>Pytanie?</strong>Odpowiedź…</p>
 */
export function extractFaqSchema(html: string): FaqPageSchema | null {
  if (typeof window === 'undefined' || typeof DOMParser === 'undefined') return null

  const doc = new DOMParser().parseFromString(html, 'text/html')

  let faqHeading: Element | null = null
  doc.querySelectorAll('h2').forEach(h => {
    if (!faqHeading && FAQ_HEADING_RE.test(h.textContent ?? '')) {
      faqHeading = h
    }
  })
  if (!faqHeading) return null

  const mainEntity: FaqQuestion[] = []
  let sibling: Element | null = (faqHeading as Element).nextElementSibling

  while (sibling && sibling.tagName !== 'H2') {
    if (sibling.tagName === 'P') {
      const strong = sibling.querySelector('strong')
      if (strong) {
        const name = strong.textContent?.trim() ?? ''
        const clone = sibling.cloneNode(true) as Element
        clone.querySelector('strong')?.remove()
        clone.querySelectorAll('br').forEach(br => br.replaceWith(' '))
        const text = clone.textContent?.trim() ?? ''
        if (name && text) {
          mainEntity.push({
            '@type': 'Question',
            name,
            acceptedAnswer: { '@type': 'Answer', text },
          })
        }
      }
    }
    sibling = sibling.nextElementSibling
  }

  if (mainEntity.length === 0) return null

  return { '@context': 'https://schema.org', '@type': 'FAQPage', mainEntity }
}
