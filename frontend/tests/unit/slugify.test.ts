import { describe, it, expect } from 'vitest'
import { slugify } from '@/utils/slugify'

/**
 * Tests for slugify — w szczególności czytelność wymiarów w slugu URL.
 *
 * Bug History (GSC 2026-06-15, import Optokom): tytuły z wymiarami
 * („Billboard 5.04×2.38 m") dawały nieczytelny, wyglądający na duplikat slug
 * „billboard-504238-m", bo krok usuwania znaków kasował kropkę i „×" bez separatora.
 *
 * MUSI pozostać zgodny z Advertisement::slugifyTitle() w backendzie — sitemap (PHP)
 * i canonical/linki (TS) generują slug niezależnie i muszą dać identyczny wynik.
 */
describe('slugify', () => {
    it('zachowuje czytelne wymiary zamiast zlewać je w jeden ciąg', () => {
        expect(slugify('Billboard 5.04×2.38 m – Jaworzno')).toBe('billboard-5-04-x-2-38-m-jaworzno')
    })

    it('obsługuje literę „x" i przecinek dziesiętny', () => {
        expect(slugify('Baner 3,2x1,5 m')).toBe('baner-3-2-x-1-5-m')
    })

    it('nie zmienia tytułów bez wymiarów', () => {
        expect(slugify('Citylight przystanek Centrum')).toBe('citylight-przystanek-centrum')
    })

    it('poprawnie transliteruje polskie znaki w nazwach miast', () => {
        expect(slugify('Dąbrowa Górnicza')).toBe('dabrowa-gornicza')
        expect(slugify('Łódź')).toBe('lodz')
    })
})
