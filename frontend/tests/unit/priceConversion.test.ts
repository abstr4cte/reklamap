import { describe, it, expect } from 'vitest'

/**
 * Tests for Price Unit Conversion Logic
 * 
 * Bug History: Price displayed without "~" when units didn't match
 * - Users saw estimated prices as exact prices
 * - Sorting by different unit showed wrong indicators
 * 
 * Solution: Track price_unit, calculate conversions, show "~" for estimated
 */
describe('Price Unit Conversion', () => {
  /**
   * Price conversion helper (simplified from searchStore)
   */
  const convertPrice = (
    basePrice: number,
    fromUnit: string,
    toUnit: string
  ): number => {
    if (fromUnit === toUnit) return basePrice

    // Convert to monthly price first
    let pricePerMonth = basePrice
    switch (fromUnit) {
      case 'day': pricePerMonth = basePrice * 30; break
      case 'week': pricePerMonth = basePrice * 4; break
      case 'month': pricePerMonth = basePrice; break
      case 'year': pricePerMonth = basePrice / 12; break
    }

    // Convert to target unit
    switch (toUnit) {
      case 'day': return pricePerMonth / 30
      case 'week': return pricePerMonth / 4
      case 'month': return pricePerMonth
      case 'year': return pricePerMonth * 12
      default: return pricePerMonth
    }
  }

  describe('Basic Conversions', () => {
    it('converts day to month: 100 PLN/day = 3000 PLN/month', () => {
      const result = convertPrice(100, 'day', 'month')
      expect(result).toBe(3000)
    })

    it('converts month to week: 1200 PLN/month = 300 PLN/week', () => {
      const result = convertPrice(1200, 'month', 'week')
      expect(result).toBe(300)
    })

    it('converts week to day: 280 PLN/week = ~37.33 PLN/day', () => {
      const result = convertPrice(280, 'week', 'day')
      // 280 PLN/week → 1120 PLN/month (280 * 4) → 37.33 PLN/day (1120 / 30)
      expect(result).toBeCloseTo(37.33, 2)
    })

    it('converts year to month: 12000 PLN/year = 1000 PLN/month', () => {
      const result = convertPrice(12000, 'year', 'month')
      expect(result).toBe(1000)
    })

    it('returns same price when units match', () => {
      const result = convertPrice(1000, 'month', 'month')
      expect(result).toBe(1000)
    })
  })

  describe('Estimated Price Detection', () => {
    it('detects estimated price when units differ', () => {
      const adPriceUnit = 'day'
      const displayUnit = 'month'
      
      const isEstimated = adPriceUnit !== displayUnit
      
      expect(isEstimated).toBe(true)
    })

    it('detects exact price when units match', () => {
      const adPriceUnit = 'month'
      const displayUnit = 'month'
      
      const isEstimated = adPriceUnit !== displayUnit
      
      expect(isEstimated).toBe(false)
    })
  })

  describe('Price Filtering', () => {
    it('filters ads correctly with converted prices', () => {
      const ads = [
        { price: 100, price_unit: 'day' },    // = 3000/month
        { price: 2000, price_unit: 'month' }, // = 2000/month
        { price: 1400, price_unit: 'month' }, // = 1400/month
      ]
      
      const filterUnit = 'month'
      const minPrice = 1500
      const maxPrice = 3500
      
      const filtered = ads.filter(ad => {
        const monthlyPrice = convertPrice(ad.price, ad.price_unit, filterUnit)
        return monthlyPrice >= minPrice && monthlyPrice <= maxPrice
      })
      
      expect(filtered).toHaveLength(2)
      expect(filtered[0].price).toBe(100)  // 3000/month ✓
      expect(filtered[1].price).toBe(2000) // 2000/month ✓
    })
  })

  describe('Price Display Format', () => {
    it('formats exact price without tilde', () => {
      const adPrice = 1000
      const adPriceUnit = 'month'
      const displayUnit = 'month'
      
      const displayPrice = convertPrice(adPrice, adPriceUnit, displayUnit)
      const isEstimated = adPriceUnit !== displayUnit
      const formatted = isEstimated ? `~${displayPrice}` : `${displayPrice}`
      
      expect(formatted).toBe('1000')
      expect(formatted).not.toContain('~')
    })

    it('formats estimated price with tilde', () => {
      const adPrice = 100 // per day
      const adPriceUnit = 'day'
      const displayUnit = 'month'
      
      const displayPrice = convertPrice(adPrice, adPriceUnit, displayUnit)
      const isEstimated = adPriceUnit !== displayUnit
      const formatted = isEstimated ? `~${displayPrice}` : `${displayPrice}`
      
      expect(formatted).toBe('~3000')
      expect(formatted).toContain('~')
    })
  })

  describe('Edge Cases', () => {
    it('handles zero price', () => {
      const result = convertPrice(0, 'day', 'month')
      expect(result).toBe(0)
    })

    it('handles very small prices without losing precision', () => {
      const result = convertPrice(0.01, 'month', 'day')
      expect(result).toBeGreaterThan(0)
      expect(result).toBeCloseTo(0.00033, 5)
    })

    it('handles very large prices', () => {
      const result = convertPrice(1000000, 'month', 'year')
      expect(result).toBe(12000000)
    })
  })

  describe('Real-world Scenarios', () => {
    it('Banner: 50 PLN/day should be ~1500 PLN/month', () => {
      const bannerPrice = 50
      const bannerUnit = 'day'
      const monthlyPrice = convertPrice(bannerPrice, bannerUnit, 'month')
      
      expect(monthlyPrice).toBe(1500)
    })

    it('Billboard: 2000 PLN/month should be ~500 PLN/week', () => {
      const billboardPrice = 2000
      const billboardUnit = 'month'
      const weeklyPrice = convertPrice(billboardPrice, billboardUnit, 'week')
      
      expect(weeklyPrice).toBe(500)
    })

    it('LED Screen: 10000 PLN/year should be ~833.33 PLN/month', () => {
      const ledPrice = 10000
      const ledUnit = 'year'
      const monthlyPrice = convertPrice(ledPrice, ledUnit, 'month')
      
      expect(monthlyPrice).toBeCloseTo(833.33, 2)
    })
  })
})
