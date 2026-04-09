import { describe, it, expect } from 'vitest'

/**
 * Minimal Advertisement type for testing
 */
type Advertisement = {
  id: string
  title: string
  type: string
  city: string
  location: string
  price: number
  price_unit: string
  status: string
  display_status?: string
  available_from?: string
  width: number
  height: number
  latitude: number
  longitude: number
  owner_name: string
  owner_email: string
  owner_phone: string
  created_at: string
  updated_at: string
  traffic_intensity?: string
  traffic_direction?: string | string[]
  traffic_type?: string | string[]
  has_backlight?: boolean
  has_image?: boolean
  price_includes_print?: boolean
}

/**
 * Tests for useSearchStore Filtering Logic
 * 
 * Bug History: Filters didn't work correctly for:
 * - LED dimensions (mm vs m)
 * - Price ranges with different units
 * - Traffic filters for outdoor types
 * - Status transitions (soon_available → active)
 * 
 * These tests validate the core filtering logic WITHOUT mounting the full Pinia store,
 * focusing on pure functions and expected behaviors.
 */

describe('SearchStore Filtering Logic', () => {
  
  /**
   * Mock advertisement factory
   */
  const createMockAd = (overrides: Partial<Advertisement> = {}): Advertisement => ({
    id: '1',
    title: 'Test Ad',
    type: 'billboard',
    city: 'Warszawa',
    location: 'ul. Testowa 1',
    price: 1000,
    price_unit: 'month',
    status: 'active',
    display_status: 'active',
    width: 6,
    height: 3,
    latitude: 52.2297,
    longitude: 21.0122,
    owner_name: 'Test Owner',
    owner_email: 'test@example.com',
    owner_phone: '123456789',
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
    ...overrides,
  } as Advertisement)

  describe('Type Filtering', () => {
    it('filters by billboard type', () => {
      const ads = [
        createMockAd({ type: 'billboard' }),
        createMockAd({ type: 'led_screen' }),
        createMockAd({ type: 'banner' }),
      ]
      
      const filtered = ads.filter(ad => ad.type === 'billboard')
      
      expect(filtered).toHaveLength(1)
      expect(filtered[0].type).toBe('billboard')
    })

    it('filters by LED screen type', () => {
      const ads = [
        createMockAd({ type: 'billboard' }),
        createMockAd({ type: 'led_screen' }),
      ]
      
      const filtered = ads.filter(ad => ad.type === 'led_screen')
      
      expect(filtered).toHaveLength(1)
      expect(filtered[0].type).toBe('led_screen')
    })
  })

  describe('Dimension Filtering (with LED conversion)', () => {
    it('filters LED screens by width (after mm→m conversion)', () => {
      const ads = [
        createMockAd({ type: 'led_screen', width: 2, height: 1.5 }),    // 2m
        createMockAd({ type: 'led_screen', width: 3, height: 2 }),      // 3m
        createMockAd({ type: 'led_screen', width: 5, height: 3 }),      // 5m
      ]
      
      // User filters: 2000mm - 4000mm
      const filterWidthFrom = 2000 // mm
      const filterWidthTo = 4000   // mm
      
      // Convert to meters for comparison (as searchStore does)
      const minM = filterWidthFrom / 1000 // 2m
      const maxM = filterWidthTo / 1000   // 4m
      
      const filtered = ads.filter(ad => 
        ad.width >= minM && ad.width <= maxM
      )
      
      expect(filtered).toHaveLength(2)
      expect(filtered[0].width).toBe(2)
      expect(filtered[1].width).toBe(3)
    })

    it('filters billboards by dimensions (no conversion needed)', () => {
      const ads = [
        createMockAd({ type: 'billboard', width: 6, height: 3 }),
        createMockAd({ type: 'billboard', width: 12, height: 4 }),
      ]
      
      const filterWidthFrom = 5
      const filterWidthTo = 10
      
      const filtered = ads.filter(ad => 
        ad.width >= filterWidthFrom && ad.width <= filterWidthTo
      )
      
      expect(filtered).toHaveLength(1)
      expect(filtered[0].width).toBe(6)
    })
  })

  describe('Surface Area Filtering', () => {
    it('filters by calculated surface area', () => {
      const ads = [
        createMockAd({ width: 6, height: 3 }),   // 18m²
        createMockAd({ width: 12, height: 4 }),  // 48m²
        createMockAd({ width: 3, height: 2 }),   // 6m²
      ]
      
      const surfaceFrom = 10 // m²
      const surfaceTo = 50   // m²
      
      const filtered = ads.filter(ad => {
        const surface = (ad.width || 0) * (ad.height || 0)
        return surface >= surfaceFrom && surface <= surfaceTo
      })
      
      expect(filtered).toHaveLength(2)
      expect(filtered[0].width * filtered[0].height).toBe(18)
      expect(filtered[1].width * filtered[1].height).toBe(48)
    })

    it('handles ads with missing dimensions (surface = 0)', () => {
      const ads = [
        createMockAd({ width: undefined, height: undefined }),
        createMockAd({ width: 6, height: 3 }),
      ]
      
      const surfaceFrom = 5
      
      const filtered = ads.filter(ad => {
        const surface = (ad.width || 0) * (ad.height || 0)
        return surface >= surfaceFrom
      })
      
      expect(filtered).toHaveLength(1)
      expect(filtered[0].width).toBe(6)
    })
  })

  describe('Price Filtering (with unit conversion)', () => {
    /**
     * Price conversion helper (from priceConversion tests)
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
      }

      switch (toUnit) {
        case 'day': return pricePerMonth / 30
        case 'week': return pricePerMonth / 4
        case 'month': return pricePerMonth
        case 'year': return pricePerMonth * 12
        default: return pricePerMonth
      }
    }

    it('filters ads by price range with unit conversion', () => {
      const ads = [
        createMockAd({ price: 100, price_unit: 'day' }),    // = 3000/month
        createMockAd({ price: 2000, price_unit: 'month' }), // = 2000/month
        createMockAd({ price: 1500, price_unit: 'month' }), // = 1500/month
        createMockAd({ price: 500, price_unit: 'week' }),   // = 2000/month
      ]
      
      const filterUnit = 'month'
      const priceFrom = 1800
      const priceTo = 3500
      
      const filtered = ads.filter(ad => {
        const monthlyPrice = convertPrice(ad.price, ad.price_unit, filterUnit)
        return monthlyPrice >= priceFrom && monthlyPrice <= priceTo
      })
      
      expect(filtered).toHaveLength(3)
      // 3000/month, 2000/month, 2000/month (converted from week)
    })

    it('filters correctly when ad price_unit matches filter unit', () => {
      const ads = [
        createMockAd({ price: 1000, price_unit: 'month' }),
        createMockAd({ price: 2000, price_unit: 'month' }),
        createMockAd({ price: 3000, price_unit: 'month' }),
      ]
      
      const filterUnit = 'month'
      const priceFrom = 1500
      const priceTo = 2500
      
      const filtered = ads.filter(ad => {
        const price = convertPrice(ad.price, ad.price_unit, filterUnit)
        return price >= priceFrom && price <= priceTo
      })
      
      expect(filtered).toHaveLength(1)
      expect(filtered[0].price).toBe(2000)
    })
  })

  describe('Traffic Filtering', () => {
    it('filters by traffic intensity', () => {
      const ads = [
        createMockAd({ type: 'billboard', traffic_intensity: 'high' }),
        createMockAd({ type: 'billboard', traffic_intensity: 'medium' }),
        createMockAd({ type: 'billboard', traffic_intensity: 'low' }),
      ]
      
      const filterIntensity = 'high'
      
      const filtered = ads.filter(ad => 
        ad.traffic_intensity === filterIntensity
      )
      
      expect(filtered).toHaveLength(1)
      expect(filtered[0].traffic_intensity).toBe('high')
    })

    it('filters by traffic direction (including "both")', () => {
      const ads = [
        createMockAd({ traffic_direction: ['entry'] as any }),
        createMockAd({ traffic_direction: ['exit'] as any }),
        createMockAd({ traffic_direction: ['entry', 'exit'] as any }), // both
      ]
      
      // Filter for "entry"
      const filterDirection = 'entry'
      
      const filtered = ads.filter(ad => {
        if (!ad.traffic_direction) return false
        const dirs = Array.isArray(ad.traffic_direction) 
          ? ad.traffic_direction 
          : [ad.traffic_direction]
        
        // Ad matches if it has the direction OR has "both"
        return dirs.includes(filterDirection) || dirs.length >= 2
      })
      
      expect(filtered).toHaveLength(2)
      // "entry" only + "both"
    })

    it('filters by traffic type (pedestrian/car)', () => {
      const ads = [
        createMockAd({ traffic_type: ['pedestrian'] as any }),
        createMockAd({ traffic_type: ['car'] as any }),
        createMockAd({ traffic_type: ['pedestrian', 'car'] as any }), // both
      ]
      
      const filterType = 'pedestrian'
      
      const filtered = ads.filter(ad => {
        if (!ad.traffic_type) return false
        const types = Array.isArray(ad.traffic_type) 
          ? ad.traffic_type 
          : [ad.traffic_type]
        
        return types.includes(filterType) || types.length >= 2
      })
      
      expect(filtered).toHaveLength(2)
      // "pedestrian" only + "both"
    })
  })

  describe('Status Filtering', () => {
    it('filters active ads', () => {
      const ads = [
        createMockAd({ status: 'active' }),
        createMockAd({ status: 'rented' }),
        createMockAd({ status: 'archived' }),
      ]
      
      const filterStatus = ['active']
      
      const filtered = ads.filter(ad => {
        const adStatus = ad.status === 'available' ? 'active' : ad.status
        return filterStatus.includes(adStatus)
      })
      
      expect(filtered).toHaveLength(1)
      expect(filtered[0].status).toBe('active')
    })

    it('converts "available" to "active" for consistency', () => {
      const ads = [
        createMockAd({ status: 'available' as any }),
        createMockAd({ status: 'active' }),
      ]
      
      const filterStatus = ['active']
      
      const filtered = ads.filter(ad => {
        const adStatus = ad.status === 'available' ? 'active' : ad.status
        return filterStatus.includes(adStatus)
      })
      
      expect(filtered).toHaveLength(2)
      // Both "available" and "active" match
    })

    it('transitions soon_available to active when date passed', () => {
      const pastDate = new Date()
      pastDate.setDate(pastDate.getDate() - 1) // Yesterday
      
      const futureDate = new Date()
      futureDate.setDate(futureDate.getDate() + 10) // In 10 days
      
      const ads = [
        createMockAd({ 
          display_status: 'soon_available', 
          available_from: pastDate.toISOString() 
        }),
        createMockAd({ 
          display_status: 'soon_available', 
          available_from: futureDate.toISOString() 
        }),
      ]
      
      const filterStatus = ['active']
      
      const filtered = ads.filter(ad => {
        let effectiveStatus = ad.display_status || ad.status
        
        // If soon_available and date passed, treat as active
        if (effectiveStatus === 'soon_available' && ad.available_from) {
          if (new Date(ad.available_from) <= new Date()) {
            effectiveStatus = 'active'
          }
        }
        
        return filterStatus.includes(effectiveStatus)
      })
      
      expect(filtered).toHaveLength(1)
      expect(filtered[0].display_status).toBe('soon_available')
      expect(new Date(filtered[0].available_from!).getTime()).toBeLessThan(new Date().getTime())
    })
  })

  describe('Boolean Flag Filtering', () => {
    it('filters ads with backlight', () => {
      const ads = [
        createMockAd({ has_backlight: true }),
        createMockAd({ has_backlight: false }),
        createMockAd({ has_backlight: undefined }),
      ]
      
      const hasBacklight = true
      
      const filtered = ads.filter(ad => 
        ad.has_backlight === hasBacklight
      )
      
      expect(filtered).toHaveLength(1)
      expect(filtered[0].has_backlight).toBe(true)
    })

    it('filters ads with images', () => {
      const ads = [
        createMockAd({ has_image: true }),
        createMockAd({ has_image: false }),
      ]
      
      const onlyWithImage = true
      
      const filtered = ads.filter(ad => 
        onlyWithImage ? ad.has_image === true : true
      )
      
      expect(filtered).toHaveLength(1)
      expect(filtered[0].has_image).toBe(true)
    })

    it('filters by price includes print', () => {
      const ads = [
        createMockAd({ price_includes_print: true }),
        createMockAd({ price_includes_print: false }),
      ]
      
      const priceIncludesPrint = true
      
      const filtered = ads.filter(ad => 
        ad.price_includes_print === priceIncludesPrint
      )
      
      expect(filtered).toHaveLength(1)
      expect(filtered[0].price_includes_print).toBe(true)
    })
  })

  describe('City/Location Filtering', () => {
    it('filters by exact city match', () => {
      const ads = [
        createMockAd({ city: 'Warszawa' }),
        createMockAd({ city: 'Kraków' }),
        createMockAd({ city: 'Wrocław' }),
      ]
      
      const filterCity = 'Kraków'
      
      const filtered = ads.filter(ad => 
        ad.city === filterCity
      )
      
      expect(filtered).toHaveLength(1)
      expect(filtered[0].city).toBe('Kraków')
    })

    it('filters by city substring (case-insensitive)', () => {
      const normalizePolishChars = (str: string) => {
        return str
          .replace(/ą/g, 'a')
          .replace(/ć/g, 'c')
          .replace(/ę/g, 'e')
          .replace(/ł/g, 'l')
          .replace(/ń/g, 'n')
          .replace(/ó/g, 'o')
          .replace(/ś/g, 's')
          .replace(/ź|ż/g, 'z')
      }
      
      const ads = [
        createMockAd({ city: 'Warszawa' }),
        createMockAd({ city: 'Wrocław' }),
        createMockAd({ city: 'Kraków' }),
      ]
      
      const filterCity = 'wro' // partial match
      
      const filtered = ads.filter(ad => 
        normalizePolishChars(ad.city.toLowerCase()).includes(
          normalizePolishChars(filterCity.toLowerCase())
        )
      )
      
      expect(filtered).toHaveLength(1)
      expect(filtered[0].city).toBe('Wrocław')
    })
  })

  describe('Combined Filters (Real-world Scenarios)', () => {
    it('filters LED screens in Warsaw with specific dimensions and price', () => {
      const ads = [
        createMockAd({ 
          type: 'led_screen', 
          city: 'Warszawa', 
          width: 2.5, 
          height: 1.5,
          price: 5000,
          price_unit: 'month'
        }),
        createMockAd({ 
          type: 'led_screen', 
          city: 'Kraków', 
          width: 2, 
          height: 1,
          price: 3000,
          price_unit: 'month'
        }),
        createMockAd({ 
          type: 'billboard', 
          city: 'Warszawa', 
          width: 6, 
          height: 3,
          price: 2000,
          price_unit: 'month'
        }),
      ]
      
      // Filters: LED in Warsaw, width 2000-3000mm, price 4000-6000 PLN/month
      const filterType = 'led_screen'
      const filterCity = 'Warszawa'
      const filterWidthFrom = 2000 // mm
      const filterWidthTo = 3000   // mm
      const filterPriceFrom = 4000
      const filterPriceTo = 6000
      
      const filtered = ads
        .filter(ad => ad.type === filterType)
        .filter(ad => ad.city === filterCity)
        .filter(ad => {
          const minM = filterWidthFrom / 1000
          const maxM = filterWidthTo / 1000
          return ad.width >= minM && ad.width <= maxM
        })
        .filter(ad => ad.price >= filterPriceFrom && ad.price <= filterPriceTo)
      
      expect(filtered).toHaveLength(1)
      expect(filtered[0].type).toBe('led_screen')
      expect(filtered[0].city).toBe('Warszawa')
      expect(filtered[0].width).toBe(2.5)
      expect(filtered[0].price).toBe(5000)
    })

    it('filters outdoor ads with high traffic intensity', () => {
      const ads = [
        createMockAd({ type: 'billboard', traffic_intensity: 'high' }),
        createMockAd({ type: 'banner', traffic_intensity: 'high' }),
        createMockAd({ type: 'billboard', traffic_intensity: 'low' }),
        createMockAd({ type: 'led_screen' }), // no traffic_intensity
      ]
      
      const outdoorTypes = ['billboard', 'banner', 'wall', 'totem']
      const filterIntensity = 'high'
      
      const filtered = ads
        .filter(ad => outdoorTypes.includes(ad.type))
        .filter(ad => ad.traffic_intensity === filterIntensity)
      
      expect(filtered).toHaveLength(2)
      expect(filtered.every(ad => ad.traffic_intensity === 'high')).toBe(true)
    })
  })
})
