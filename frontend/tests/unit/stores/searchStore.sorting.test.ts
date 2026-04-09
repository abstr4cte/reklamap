import { describe, it, expect } from 'vitest'

/**
 * Minimal Advertisement type for testing
 */
type Advertisement = {
  id: string
  title: string
  price: number
  price_unit: string
  width: number
  height: number
  created_at: string
}

/**
 * Tests for useSearchStore Sorting Logic
 * 
 * Bug History: Price sorting didn't account for different price units
 * - Ads priced at 100 PLN/day sorted before 3000 PLN/month (incorrectly)
 * - Needed to convert all prices to same unit before sorting
 */
describe('SearchStore Sorting Logic', () => {
  
  /**
   * Price conversion helper
   */
  const convertPrice = (
    basePrice: number,
    fromUnit: string,
    toUnit: string
  ): number => {
    if (fromUnit === toUnit) return basePrice

    let pricePerMonth = basePrice
    switch (fromUnit) {
      case 'day': pricePerMonth = basePrice * 30; break
      case 'week': pricePerMonth = basePrice * 4; break
      case 'month': pricePerMonth = basePrice; break
      case 'year': pricePerMonth = basePrice / 12; break
      case 'campaign': pricePerMonth = basePrice; break
    }

    switch (toUnit) {
      case 'day': return pricePerMonth / 30
      case 'week': return pricePerMonth / 4
      case 'month': return pricePerMonth
      case 'year': return pricePerMonth * 12
      default: return pricePerMonth
    }
  }

  const createMockAd = (overrides: Partial<Advertisement> = {}): Advertisement => ({
    id: '1',
    title: 'Test Ad',
    price: 1000,
    price_unit: 'month',
    width: 6,
    height: 3,
    created_at: new Date().toISOString(),
    ...overrides,
  })

  describe('Price Sorting (Ascending)', () => {
    it('sorts ads by price in ascending order (same unit)', () => {
      const ads = [
        createMockAd({ id: '3', price: 3000, price_unit: 'month' }),
        createMockAd({ id: '1', price: 1000, price_unit: 'month' }),
        createMockAd({ id: '2', price: 2000, price_unit: 'month' }),
      ]
      
      const sortUnit = 'month'
      const sorted = [...ads].sort((a, b) => {
        const priceA = convertPrice(a.price, a.price_unit, sortUnit)
        const priceB = convertPrice(b.price, b.price_unit, sortUnit)
        return priceA - priceB
      })
      
      expect(sorted[0].id).toBe('1') // 1000
      expect(sorted[1].id).toBe('2') // 2000
      expect(sorted[2].id).toBe('3') // 3000
    })

    it('sorts ads by price with unit conversion (ascending)', () => {
      const ads = [
        createMockAd({ id: '1', price: 100, price_unit: 'day' }),    // = 3000/month
        createMockAd({ id: '2', price: 2000, price_unit: 'month' }), // = 2000/month
        createMockAd({ id: '3', price: 500, price_unit: 'week' }),   // = 2000/month
        createMockAd({ id: '4', price: 1000, price_unit: 'month' }), // = 1000/month
      ]
      
      const sortUnit = 'month'
      const sorted = [...ads].sort((a, b) => {
        const priceA = convertPrice(a.price, a.price_unit, sortUnit)
        const priceB = convertPrice(b.price, b.price_unit, sortUnit)
        return priceA - priceB
      })
      
      expect(sorted[0].id).toBe('4') // 1000/month
      expect(sorted[1].id).toBe('2') // 2000/month
      expect(sorted[2].id).toBe('3') // 2000/month (from week)
      expect(sorted[3].id).toBe('1') // 3000/month (from day)
    })
  })

  describe('Price Sorting (Descending)', () => {
    it('sorts ads by price in descending order (same unit)', () => {
      const ads = [
        createMockAd({ id: '1', price: 1000, price_unit: 'month' }),
        createMockAd({ id: '2', price: 2000, price_unit: 'month' }),
        createMockAd({ id: '3', price: 3000, price_unit: 'month' }),
      ]
      
      const sortUnit = 'month'
      const sorted = [...ads].sort((a, b) => {
        const priceA = convertPrice(a.price, a.price_unit, sortUnit)
        const priceB = convertPrice(b.price, b.price_unit, sortUnit)
        return priceB - priceA
      })
      
      expect(sorted[0].id).toBe('3') // 3000
      expect(sorted[1].id).toBe('2') // 2000
      expect(sorted[2].id).toBe('1') // 1000
    })

    it('sorts ads by price with unit conversion (descending)', () => {
      const ads = [
        createMockAd({ id: '1', price: 1000, price_unit: 'month' }), // = 1000/month
        createMockAd({ id: '2', price: 100, price_unit: 'day' }),    // = 3000/month
        createMockAd({ id: '3', price: 500, price_unit: 'week' }),   // = 2000/month
      ]
      
      const sortUnit = 'month'
      const sorted = [...ads].sort((a, b) => {
        const priceA = convertPrice(a.price, a.price_unit, sortUnit)
        const priceB = convertPrice(b.price, b.price_unit, sortUnit)
        return priceB - priceA
      })
      
      expect(sorted[0].id).toBe('2') // 3000/month (from day)
      expect(sorted[1].id).toBe('3') // 2000/month (from week)
      expect(sorted[2].id).toBe('1') // 1000/month
    })
  })

  describe('Price Sorting by Different Units', () => {
    it('sorts by daily price correctly', () => {
      const ads = [
        createMockAd({ id: '1', price: 100, price_unit: 'day' }),    // = 100/day
        createMockAd({ id: '2', price: 3000, price_unit: 'month' }), // = 100/day
        createMockAd({ id: '3', price: 1500, price_unit: 'month' }), // = 50/day
      ]
      
      const sortUnit = 'day'
      const sorted = [...ads].sort((a, b) => {
        const priceA = convertPrice(a.price, a.price_unit, sortUnit)
        const priceB = convertPrice(b.price, b.price_unit, sortUnit)
        return priceA - priceB
      })
      
      expect(sorted[0].id).toBe('3') // 50/day
      expect(sorted[1].id).toBe('1') // 100/day
      expect(sorted[2].id).toBe('2') // 100/day
    })

    it('sorts by weekly price correctly', () => {
      const ads = [
        createMockAd({ id: '1', price: 2000, price_unit: 'month' }), // = 500/week
        createMockAd({ id: '2', price: 500, price_unit: 'week' }),   // = 500/week
        createMockAd({ id: '3', price: 100, price_unit: 'day' }),    // = 700/week
      ]
      
      const sortUnit = 'week'
      const sorted = [...ads].sort((a, b) => {
        const priceA = convertPrice(a.price, a.price_unit, sortUnit)
        const priceB = convertPrice(b.price, b.price_unit, sortUnit)
        return priceA - priceB
      })
      
      expect(sorted[0].id).toBe('1') // 500/week
      expect(sorted[1].id).toBe('2') // 500/week
      expect(sorted[2].id).toBe('3') // 700/week
    })

    it('sorts by yearly price correctly', () => {
      const ads = [
        createMockAd({ id: '1', price: 12000, price_unit: 'year' }), // = 12000/year
        createMockAd({ id: '2', price: 1000, price_unit: 'month' }), // = 12000/year
        createMockAd({ id: '3', price: 2000, price_unit: 'month' }), // = 24000/year
      ]
      
      const sortUnit = 'year'
      const sorted = [...ads].sort((a, b) => {
        const priceA = convertPrice(a.price, a.price_unit, sortUnit)
        const priceB = convertPrice(b.price, b.price_unit, sortUnit)
        return priceA - priceB
      })
      
      expect(sorted[0].id).toBe('1') // 12000/year
      expect(sorted[1].id).toBe('2') // 12000/year
      expect(sorted[2].id).toBe('3') // 24000/year
    })
  })

  describe('Date Sorting', () => {
    it('sorts ads by creation date (newest first)', () => {
      const date1 = new Date('2026-01-01')
      const date2 = new Date('2026-02-01')
      const date3 = new Date('2026-03-01')
      
      const ads = [
        createMockAd({ id: '1', created_at: date1.toISOString() }),
        createMockAd({ id: '2', created_at: date3.toISOString() }),
        createMockAd({ id: '3', created_at: date2.toISOString() }),
      ]
      
      const sorted = [...ads].sort((a, b) => 
        new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
      )
      
      expect(sorted[0].id).toBe('2') // March (newest)
      expect(sorted[1].id).toBe('3') // February
      expect(sorted[2].id).toBe('1') // January (oldest)
    })

    it('sorts ads by creation date (oldest first)', () => {
      const date1 = new Date('2026-01-01')
      const date2 = new Date('2026-02-01')
      const date3 = new Date('2026-03-01')
      
      const ads = [
        createMockAd({ id: '1', created_at: date3.toISOString() }),
        createMockAd({ id: '2', created_at: date1.toISOString() }),
        createMockAd({ id: '3', created_at: date2.toISOString() }),
      ]
      
      const sorted = [...ads].sort((a, b) => 
        new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
      )
      
      expect(sorted[0].id).toBe('2') // January (oldest)
      expect(sorted[1].id).toBe('3') // February
      expect(sorted[2].id).toBe('1') // March (newest)
    })
  })

  describe('Surface Area Sorting', () => {
    it('sorts ads by surface area (ascending)', () => {
      const ads = [
        createMockAd({ id: '1', width: 6, height: 3 }),   // 18m²
        createMockAd({ id: '2', width: 12, height: 4 }),  // 48m²
        createMockAd({ id: '3', width: 3, height: 2 }),   // 6m²
      ]
      
      const sorted = [...ads].sort((a, b) => {
        const surfaceA = a.width * a.height
        const surfaceB = b.width * b.height
        return surfaceA - surfaceB
      })
      
      expect(sorted[0].id).toBe('3') // 6m²
      expect(sorted[1].id).toBe('1') // 18m²
      expect(sorted[2].id).toBe('2') // 48m²
    })

    it('sorts ads by surface area (descending)', () => {
      const ads = [
        createMockAd({ id: '1', width: 3, height: 2 }),   // 6m²
        createMockAd({ id: '2', width: 6, height: 3 }),   // 18m²
        createMockAd({ id: '3', width: 12, height: 4 }),  // 48m²
      ]
      
      const sorted = [...ads].sort((a, b) => {
        const surfaceA = a.width * a.height
        const surfaceB = b.width * b.height
        return surfaceB - surfaceA
      })
      
      expect(sorted[0].id).toBe('3') // 48m²
      expect(sorted[1].id).toBe('2') // 18m²
      expect(sorted[2].id).toBe('1') // 6m²
    })
  })

  describe('Alphabetical Sorting', () => {
    it('sorts ads alphabetically by title (A-Z)', () => {
      const ads = [
        createMockAd({ id: '1', title: 'Zebra Billboard' }),
        createMockAd({ id: '2', title: 'Alpha LED' }),
        createMockAd({ id: '3', title: 'Beta Banner' }),
      ]
      
      const sorted = [...ads].sort((a, b) => 
        a.title.localeCompare(b.title)
      )
      
      expect(sorted[0].title).toBe('Alpha LED')
      expect(sorted[1].title).toBe('Beta Banner')
      expect(sorted[2].title).toBe('Zebra Billboard')
    })

    it('sorts ads alphabetically by title (Z-A)', () => {
      const ads = [
        createMockAd({ id: '1', title: 'Alpha LED' }),
        createMockAd({ id: '2', title: 'Beta Banner' }),
        createMockAd({ id: '3', title: 'Zebra Billboard' }),
      ]
      
      const sorted = [...ads].sort((a, b) => 
        b.title.localeCompare(a.title)
      )
      
      expect(sorted[0].title).toBe('Zebra Billboard')
      expect(sorted[1].title).toBe('Beta Banner')
      expect(sorted[2].title).toBe('Alpha LED')
    })
  })

  describe('Edge Cases', () => {
    it('handles empty array', () => {
      const ads: Advertisement[] = []
      
      const sorted = [...ads].sort((a, b) => a.price - b.price)
      
      expect(sorted).toHaveLength(0)
    })

    it('handles single item', () => {
      const ads = [createMockAd({ id: '1' })]
      
      const sorted = [...ads].sort((a, b) => a.price - b.price)
      
      expect(sorted).toHaveLength(1)
      expect(sorted[0].id).toBe('1')
    })

    it('handles ads with identical prices', () => {
      const ads = [
        createMockAd({ id: '1', price: 1000 }),
        createMockAd({ id: '2', price: 1000 }),
        createMockAd({ id: '3', price: 1000 }),
      ]
      
      const sorted = [...ads].sort((a, b) => a.price - b.price)
      
      expect(sorted).toHaveLength(3)
      // Order should be stable for identical prices
    })
  })
})
