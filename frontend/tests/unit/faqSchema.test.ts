import { describe, it, expect } from 'vitest'
import { extractFaqSchema, FAQ_HEADING_RE } from '@/utils/faqSchema'

/**
 * Regresja (audyt 2026-07-25): schemat FAQPage powstawał tylko dla artykułów z nagłówkiem
 * dokładnie „Najczęściej zadawane pytania" (dopasowanie po twardym stringu). Trzy z 28
 * opublikowanych artykułów używają krótkiego „## FAQ" albo „## FAQ — najczęstsze pytania o…"
 * i nie dostawały rich snippetu: citylight-reklama, czy-oplaca-sie-wynajmowac-powierzchnie-reklamowa,
 * jak-zarobic-na-wynajmie-powierzchni-reklamowej.
 * Potwierdzone na prodzie: `curl -A Googlebot <url> | grep '"@type":"Question"'` = 0 trafień.
 */

const faqBody = `
  <p><strong>Ile kosztuje citylight?</strong>Od 200 zł/mc w mniejszym mieście do 4 500 zł/mc w centrum.</p>
  <p><strong>Czy citylight jest tańszy niż billboard?</strong>Zwykle tak, w przeliczeniu na nośnik.</p>
`

describe('FAQ_HEADING_RE — warianty nagłówka używane w artykułach', () => {
  it.each([
    'Najczęściej zadawane pytania',
    'FAQ',
    'FAQ — najczęstsze pytania o citylight',
    'Często zadawane pytania',
    'Najczęstsze pytania',
  ])('rozpoznaje „%s"', heading => {
    expect(FAQ_HEADING_RE.test(heading)).toBe(true)
  })

  it.each([
    'Podsumowanie',
    'Ile kosztuje billboard',
    'Powiązane artykuły',
  ])('nie rozpoznaje „%s"', heading => {
    expect(FAQ_HEADING_RE.test(heading)).toBe(false)
  })
})

describe('extractFaqSchema', () => {
  it('buduje FAQPage dla nagłówka „Najczęściej zadawane pytania"', () => {
    const schema = extractFaqSchema(`<h2>Najczęściej zadawane pytania</h2>${faqBody}`)
    expect(schema).not.toBeNull()
    expect(schema!['@type']).toBe('FAQPage')
    expect(schema!.mainEntity).toHaveLength(2)
    expect(schema!.mainEntity[0].name).toBe('Ile kosztuje citylight?')
    expect(schema!.mainEntity[0].acceptedAnswer.text).toContain('200 zł/mc')
  })

  it('buduje FAQPage dla krótkiego „FAQ" (regresja — 3 artykuły bez snippetu)', () => {
    const schema = extractFaqSchema(`<h2>FAQ</h2>${faqBody}`)
    expect(schema).not.toBeNull()
    expect(schema!.mainEntity).toHaveLength(2)
  })

  it('buduje FAQPage dla „FAQ — najczęstsze pytania o citylight"', () => {
    const schema = extractFaqSchema(`<h2>FAQ — najczęstsze pytania o citylight</h2>${faqBody}`)
    expect(schema).not.toBeNull()
    expect(schema!.mainEntity).toHaveLength(2)
  })

  it('zwraca null, gdy artykuł nie ma sekcji FAQ', () => {
    expect(extractFaqSchema('<h2>Podsumowanie</h2><p>Treść bez pytań.</p>')).toBeNull()
  })

  it('zwraca null, gdy pod nagłówkiem FAQ nie ma par pytanie/odpowiedź', () => {
    expect(extractFaqSchema('<h2>FAQ</h2><p>Sam akapit bez pogrubionego pytania.</p>')).toBeNull()
  })

  it('nie wychodzi poza sekcję — zatrzymuje się na kolejnym H2', () => {
    const html = `
      <h2>FAQ</h2>
      <p><strong>Pytanie w sekcji?</strong>Odpowiedź.</p>
      <h2>Powiązane artykuły</h2>
      <p><strong>To nie jest pytanie FAQ</strong>Tego nie chcemy w schemacie.</p>
    `
    const schema = extractFaqSchema(html)
    expect(schema!.mainEntity).toHaveLength(1)
    expect(schema!.mainEntity[0].name).toBe('Pytanie w sekcji?')
  })

  it('pomija pytanie bez treści odpowiedzi', () => {
    const html = '<h2>FAQ</h2><p><strong>Pytanie bez odpowiedzi?</strong></p>'
    expect(extractFaqSchema(html)).toBeNull()
  })
})
