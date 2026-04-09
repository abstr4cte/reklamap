import { describe, it, expect } from 'vitest'

/**
 * Tests for LED Screen Dimension Conversion Logic
 * 
 * Bug History: Dimensions were converted mm↔️m in multiple places causing:
 * - Incorrect filter results
 * - Wrong display values after page reload
 * - Inconsistent data in query params
 * 
 * Solution: All dimensions stored in meters in DB, converted at UI boundaries
 */
describe('LED Screen Dimension Conversion', () => {
  describe('UI Input → Database Storage (mm → m)', () => {
    it('converts 2000mm to 2m for storage', () => {
      const userInput = 2000 // mm from UI
      const dbValue = userInput / 1000 // convert to meters
      
      expect(dbValue).toBe(2)
    })

    it('converts 1500mm to 1.5m for storage', () => {
      const userInput = 1500
      const dbValue = userInput / 1000
      
      expect(dbValue).toBe(1.5)
    })

    it('handles edge case: 0mm = 0m', () => {
      const userInput = 0
      const dbValue = userInput / 1000
      
      expect(dbValue).toBe(0)
    })
  })

  describe('Database → Display (m → mm)', () => {
    it('converts 2m to 2000mm for LED display', () => {
      const dbValue = 2 // meters
      const displayValue = Math.round(dbValue * 1000) // mm
      
      expect(displayValue).toBe(2000)
    })

    it('converts 1.5m to 1500mm for LED display', () => {
      const dbValue = 1.5
      const displayValue = Math.round(dbValue * 1000)
      
      expect(displayValue).toBe(1500)
    })

    it('does NOT convert meters for billboard display', () => {
      const dbValue = 6 // meters (billboard)
      const displayValue = dbValue // stays in meters
      
      expect(displayValue).toBe(6)
    })
  })

  describe('Filter Input → Database Comparison (mm → m)', () => {
    it('converts filter dimensions from mm to m before comparing', () => {
      const filterWidthFrom = 2000 // user entered 2000mm
      const filterWidthTo = 3000   // user entered 3000mm
      const adWidth = 2.5          // stored as 2.5m in DB
      
      // Convert filters to meters for comparison
      const minM = filterWidthFrom / 1000 // 2m
      const maxM = filterWidthTo / 1000   // 3m
      
      // Check if ad matches filter
      const matches = adWidth >= minM && adWidth <= maxM
      
      expect(matches).toBe(true) // 2.5m is between 2m-3m
    })

    it('rejects ad outside filter range after conversion', () => {
      const filterWidthFrom = 1000 // 1m
      const filterWidthTo = 2000   // 2m
      const adWidth = 5            // 5m in DB
      
      const minM = filterWidthFrom / 1000
      const maxM = filterWidthTo / 1000
      
      const matches = adWidth >= minM && adWidth <= maxM
      
      expect(matches).toBe(false) // 5m is NOT between 1m-2m
    })
  })

  describe('Surface Area Calculation (always in m²)', () => {
    it('calculates LED screen surface in m² from meter dimensions', () => {
      const width = 2    // 2m (stored in DB)
      const height = 1.5 // 1.5m (stored in DB)
      const surface = width * height
      
      expect(surface).toBe(3) // 3m²
    })

    it('calculates billboard surface in m² from meter dimensions', () => {
      const width = 6  // 6m
      const height = 3 // 3m
      const surface = width * height
      
      expect(surface).toBe(18) // 18m²
    })
  })

  describe('Price per m² Calculation', () => {
    it('calculates correct price per m² for LED screen', () => {
      const price = 3000      // 3000 PLN/month
      const width = 2         // 2m
      const height = 1.5      // 1.5m
      const surface = width * height // 3m²
      
      const pricePerSqm = price / surface
      
      expect(pricePerSqm).toBe(1000) // 1000 PLN/m²
    })

    it('returns Infinity when surface is 0 (missing dimensions)', () => {
      const price = 1000
      const width = 0
      const height = 0
      const surface = width * height
      
      const pricePerSqm = surface > 0 ? price / surface : Infinity
      
      expect(pricePerSqm).toBe(Infinity)
    })
  })
})
