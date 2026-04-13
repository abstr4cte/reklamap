import { describe, it, expect } from 'vitest'

/**
 * Tests for Advertisement Form Validation Logic
 * 
 * Based on AddAdPage.vue validateStep() function
 * 
 * Features tested:
 * - Step 1: Email, title, description, type
 * - Step 2: Price, campaign duration
 * - Step 3: Dimensions, location, contact preference, phone
 * - Step 4: Road class, traffic intensity, status, offer type, variants
 * - Step 5: Terms acceptance
 */

type FormErrors = Record<string, string>

/**
 * Validates Step 1: Basic Information
 */
const validateStep1 = (formData: {
  email?: string
  title?: string
  description?: string
  type?: string
}): FormErrors => {
  const errors: FormErrors = {}

  // Email validation
  if (!formData.email) {
    errors.email = 'E-mail jest wymagany'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
    errors.email = 'Nieprawidłowy format e-mail'
  }

  // Title validation
  if (!formData.title) {
    errors.title = 'Tytuł jest wymagany'
  } else if (formData.title.length > 200) {
    errors.title = 'Tytuł nie może być dłuższy niż 200 znaków'
  }

  // Description validation
  if (!formData.description) {
    errors.description = 'Opis jest wymagany'
  } else if (formData.description.length > 5000) {
    errors.description = 'Opis nie może być dłuższy niż 5000 znaków'
  }

  // Type validation
  if (!formData.type) {
    errors.type = 'Rodzaj powierzchni jest wymagany'
  }

  return errors
}

/**
 * Validates Step 2: Pricing
 */
const validateStep2 = (formData: {
  price?: number
  priceUnit?: string
  campaignDuration?: number
}): FormErrors => {
  const errors: FormErrors = {}

  // Price validation
  if (!formData.price || formData.price <= 0) {
    errors.price = 'Cena jest wymagana'
  } else if (formData.price > 999999) {
    errors.price = 'Cena nie może przekraczać 999 999 zł'
  }

  // Campaign duration validation (only for campaign price unit)
  if (formData.priceUnit === 'campaign') {
    if (!formData.campaignDuration || formData.campaignDuration <= 0) {
      errors.campaignDuration = 'Czas trwania kampanii jest wymagany'
    }
  }

  return errors
}

/**
 * Validates Step 3: Location & Contact
 */
const validateStep3 = (formData: {
  type?: string
  width?: number
  height?: number
  location?: string
  contactPreference?: string
  phone?: string
}): FormErrors => {
  const errors: FormErrors = {}

  // Dimensions validation (for specific types)
  const typesWithDimensions = ['billboard', 'citylight', 'banner', 'wall', 'totem', 'led_screen']
  if (formData.type && typesWithDimensions.includes(formData.type)) {
    const isLed = formData.type === 'led_screen'
    const maxW = isLed ? 100000 : 100
    const maxH = isLed ? 100000 : 100
    const unit = isLed ? 'mm' : 'm'

    if (!formData.width || formData.width <= 0) {
      errors.width = 'Szerokość jest wymagana'
    } else if (formData.width > maxW) {
      errors.width = `Szerokość nie może przekraczać ${maxW} ${unit}`
    }

    if (!formData.height || formData.height <= 0) {
      errors.height = 'Wysokość jest wymagana'
    } else if (formData.height > maxH) {
      errors.height = `Wysokość nie może przekraczać ${maxH} ${unit}`
    }
  }

  // Location validation
  if (!formData.location) {
    errors.location = 'Lokalizacja jest wymagana'
  }

  // Contact preference validation
  if (!formData.contactPreference) {
    errors.contactPreference = 'Opcja kontaktu jest wymagana'
  }

  // Phone validation (if required by contact preference)
  if (formData.contactPreference === 'phone' || formData.contactPreference === 'both') {
    if (!formData.phone) {
      errors.phone = 'Numer telefonu jest wymagany dla wybranej opcji kontaktu'
    } else if (formData.phone.length !== 9) {
      errors.phone = 'Numer telefonu musi mieć dokładnie 9 cyfr'
    } else if (!/^[0-9]{9}$/.test(formData.phone)) {
      errors.phone = 'Numer telefonu może zawierać tylko cyfry'
    }
  }

  return errors
}

/**
 * Validates Step 4: Details & Features
 */
const validateStep4 = (formData: {
  type?: string
  roadClass?: string
  trafficIntensity?: string
  status?: string
  availableFrom?: string
  offerType?: string
  transportScope?: string
  variant?: string
  mobileExposureMode?: string
}): FormErrors => {
  const errors: FormErrors = {}

  // Road class (required for billboards)
  if (formData.type === 'billboard' && !formData.roadClass) {
    errors.roadClass = 'Klasa drogi jest wymagana dla billboardów'
  }

  // Traffic intensity (required for outdoor types)
  const outdoorTypes = ['billboard', 'banner', 'wall', 'totem', 'led_screen']
  if (formData.type && outdoorTypes.includes(formData.type) && !formData.trafficIntensity) {
    errors.trafficIntensity = 'Natężenie ruchu jest wymagane'
  }

  // Status validation
  if (!formData.status) {
    errors.status = 'Status dostępności jest wymagany'
  }

  // Available from (required if status is soon_available)
  if (formData.status === 'soon_available' && !formData.availableFrom) {
    errors.availableFrom = 'Data dostępności jest wymagana'
  }

  // Offer type validation
  if (!formData.offerType) {
    errors.offerType = 'Rodzaj oferty jest wymagany'
  }

  // Transport scope (required for transport type)
  if (formData.type === 'transport' && !formData.transportScope) {
    errors.transportScope = 'Zakres reklamy jest wymagany'
  }

  // Variant (required for specific types)
  const typesWithVariant = ['billboard', 'citylight', 'led_screen', 'totem', 'transport', 'mobile']
  if (formData.type && typesWithVariant.includes(formData.type) && !formData.variant) {
    errors.variant = 'Wariant jest wymagany'
  }

  // Mobile exposure mode (required for mobile type)
  if (formData.type === 'mobile' && !formData.mobileExposureMode) {
    errors.mobileExposureMode = 'Tryb ekspozycji jest wymagany'
  }

  return errors
}

/**
 * Validates Step 5: Terms & Conditions
 */
const validateStep5 = (formData: {
  acceptTerms?: boolean
}): FormErrors => {
  const errors: FormErrors = {}

  if (!formData.acceptTerms) {
    errors.acceptTerms = 'Musisz zaakceptować regulamin'
  }

  return errors
}

describe('Form Validation - Step 1 (Basic Information)', () => {
  describe('Email Validation', () => {
    it('requires email', () => {
      const errors = validateStep1({})
      expect(errors.email).toBe('E-mail jest wymagany')
    })

    it('validates email format', () => {
      const errors = validateStep1({ email: 'invalid-email' })
      expect(errors.email).toBe('Nieprawidłowy format e-mail')
    })

    it('accepts valid email', () => {
      const errors = validateStep1({ email: 'test@example.com', title: 'Test', description: 'Test', type: 'billboard' })
      expect(errors.email).toBeUndefined()
    })
  })

  describe('Title Validation', () => {
    it('requires title', () => {
      const errors = validateStep1({ email: 'test@example.com' })
      expect(errors.title).toBe('Tytuł jest wymagany')
    })

    it('enforces max 200 characters', () => {
      const longTitle = 'a'.repeat(201)
      const errors = validateStep1({ email: 'test@example.com', title: longTitle })
      expect(errors.title).toBe('Tytuł nie może być dłuższy niż 200 znaków')
    })

    it('accepts title within limit', () => {
      const errors = validateStep1({
        email: 'test@example.com',
        title: 'Valid Title',
        description: 'Test',
        type: 'billboard'
      })
      expect(errors.title).toBeUndefined()
    })
  })

  describe('Description Validation', () => {
    it('requires description', () => {
      const errors = validateStep1({ email: 'test@example.com', title: 'Test' })
      expect(errors.description).toBe('Opis jest wymagany')
    })

    it('enforces max 5000 characters', () => {
      const longDesc = 'a'.repeat(5001)
      const errors = validateStep1({ email: 'test@example.com', title: 'Test', description: longDesc })
      expect(errors.description).toBe('Opis nie może być dłuższy niż 5000 znaków')
    })

    it('accepts description within limit', () => {
      const errors = validateStep1({
        email: 'test@example.com',
        title: 'Test',
        description: 'Valid description',
        type: 'billboard'
      })
      expect(errors.description).toBeUndefined()
    })
  })

  describe('Type Validation', () => {
    it('requires type', () => {
      const errors = validateStep1({ email: 'test@example.com', title: 'Test', description: 'Test' })
      expect(errors.type).toBe('Rodzaj powierzchni jest wymagany')
    })

    it('accepts valid type', () => {
      const errors = validateStep1({
        email: 'test@example.com',
        title: 'Test',
        description: 'Test',
        type: 'billboard'
      })
      expect(errors.type).toBeUndefined()
    })
  })
})

describe('Form Validation - Step 2 (Pricing)', () => {
  describe('Price Validation', () => {
    it('requires price', () => {
      const errors = validateStep2({})
      expect(errors.price).toBe('Cena jest wymagana')
    })

    it('rejects zero price', () => {
      const errors = validateStep2({ price: 0 })
      expect(errors.price).toBe('Cena jest wymagana')
    })

    it('rejects negative price', () => {
      const errors = validateStep2({ price: -100 })
      expect(errors.price).toBe('Cena jest wymagana')
    })

    it('enforces max price', () => {
      const errors = validateStep2({ price: 1000000 })
      expect(errors.price).toBe('Cena nie może przekraczać 999 999 zł')
    })

    it('accepts valid price', () => {
      const errors = validateStep2({ price: 1000 })
      expect(errors.price).toBeUndefined()
    })
  })

  describe('Campaign Duration Validation', () => {
    it('requires campaign duration when price unit is campaign', () => {
      const errors = validateStep2({ price: 1000, priceUnit: 'campaign' })
      expect(errors.campaignDuration).toBe('Czas trwania kampanii jest wymagany')
    })

    it('rejects zero campaign duration', () => {
      const errors = validateStep2({ price: 1000, priceUnit: 'campaign', campaignDuration: 0 })
      expect(errors.campaignDuration).toBe('Czas trwania kampanii jest wymagany')
    })

    it('accepts valid campaign duration', () => {
      const errors = validateStep2({ price: 1000, priceUnit: 'campaign', campaignDuration: 30 })
      expect(errors.campaignDuration).toBeUndefined()
    })

    it('does not require campaign duration for other price units', () => {
      const errors = validateStep2({ price: 1000, priceUnit: 'month' })
      expect(errors.campaignDuration).toBeUndefined()
    })
  })
})

describe('Form Validation - Step 3 (Location & Contact)', () => {
  describe('Dimensions Validation', () => {
    it('requires dimensions for billboard', () => {
      const errors = validateStep3({ type: 'billboard', location: 'Test', contactPreference: 'email' })
      expect(errors.width).toBe('Szerokość jest wymagana')
      expect(errors.height).toBe('Wysokość jest wymagana')
    })

    it('enforces max width for billboard (100m)', () => {
      const errors = validateStep3({ type: 'billboard', width: 101, height: 3, location: 'Test', contactPreference: 'email' })
      expect(errors.width).toBe('Szerokość nie może przekraczać 100 m')
    })

    it('enforces max width for LED screen (100000mm)', () => {
      const errors = validateStep3({ type: 'led_screen', width: 100001, height: 2000, location: 'Test', contactPreference: 'email' })
      expect(errors.width).toBe('Szerokość nie może przekraczać 100000 mm')
    })

    it('accepts valid dimensions', () => {
      const errors = validateStep3({
        type: 'billboard',
        width: 6,
        height: 3,
        location: 'Test',
        contactPreference: 'email'
      })
      expect(errors.width).toBeUndefined()
      expect(errors.height).toBeUndefined()
    })

    it('does not require dimensions for transport type', () => {
      const errors = validateStep3({ type: 'transport', location: 'Test', contactPreference: 'email' })
      expect(errors.width).toBeUndefined()
      expect(errors.height).toBeUndefined()
    })
  })

  describe('Location Validation', () => {
    it('requires location', () => {
      const errors = validateStep3({ type: 'billboard', contactPreference: 'email' })
      expect(errors.location).toBe('Lokalizacja jest wymagana')
    })

    it('accepts valid location', () => {
      const errors = validateStep3({
        type: 'billboard',
        width: 6,
        height: 3,
        location: 'ul. Testowa 1, Warszawa',
        contactPreference: 'email'
      })
      expect(errors.location).toBeUndefined()
    })
  })

  describe('Phone Validation', () => {
    it('requires phone when contact preference is phone', () => {
      const errors = validateStep3({
        type: 'billboard',
        width: 6,
        height: 3,
        location: 'Test',
        contactPreference: 'phone'
      })
      expect(errors.phone).toBe('Numer telefonu jest wymagany dla wybranej opcji kontaktu')
    })

    it('requires phone when contact preference is both', () => {
      const errors = validateStep3({
        type: 'billboard',
        width: 6,
        height: 3,
        location: 'Test',
        contactPreference: 'both'
      })
      expect(errors.phone).toBe('Numer telefonu jest wymagany dla wybranej opcji kontaktu')
    })

    it('enforces exactly 9 digits', () => {
      const errors = validateStep3({
        type: 'billboard',
        width: 6,
        height: 3,
        location: 'Test',
        contactPreference: 'phone',
        phone: '12345678'
      })
      expect(errors.phone).toBe('Numer telefonu musi mieć dokładnie 9 cyfr')
    })

    it('rejects non-numeric phone', () => {
      const errors = validateStep3({
        type: 'billboard',
        width: 6,
        height: 3,
        location: 'Test',
        contactPreference: 'phone',
        phone: '12345678a'
      })
      expect(errors.phone).toBe('Numer telefonu może zawierać tylko cyfry')
    })

    it('accepts valid phone', () => {
      const errors = validateStep3({
        type: 'billboard',
        width: 6,
        height: 3,
        location: 'Test',
        contactPreference: 'phone',
        phone: '123456789'
      })
      expect(errors.phone).toBeUndefined()
    })

    it('does not require phone when contact preference is email', () => {
      const errors = validateStep3({
        type: 'billboard',
        width: 6,
        height: 3,
        location: 'Test',
        contactPreference: 'email'
      })
      expect(errors.phone).toBeUndefined()
    })
  })
})

describe('Form Validation - Step 4 (Details & Features)', () => {
  describe('Billboard-specific Validation', () => {
    it('requires road class for billboards', () => {
      const errors = validateStep4({ type: 'billboard', status: 'active', offerType: 'sale' })
      expect(errors.roadClass).toBe('Klasa drogi jest wymagana dla billboardów')
    })

    it('does not require road class for other types', () => {
      const errors = validateStep4({ type: 'led_screen', status: 'active', offerType: 'sale', trafficIntensity: 'high' })
      expect(errors.roadClass).toBeUndefined()
    })
  })

  describe('Traffic Intensity Validation', () => {
    it('requires traffic intensity for outdoor types', () => {
      const errors = validateStep4({ type: 'billboard', status: 'active', offerType: 'sale', roadClass: 'A' })
      expect(errors.trafficIntensity).toBe('Natężenie ruchu jest wymagane')
    })

    it('requires traffic intensity for LED screen', () => {
      const errors = validateStep4({ type: 'led_screen', status: 'active', offerType: 'sale' })
      expect(errors.trafficIntensity).toBe('Natężenie ruchu jest wymagane')
    })

    it('does not require traffic intensity for transport', () => {
      const errors = validateStep4({
        type: 'transport',
        status: 'active',
        offerType: 'sale',
        transportScope: 'internal',
        variant: 'bus'
      })
      expect(errors.trafficIntensity).toBeUndefined()
    })
  })

  describe('Status Validation', () => {
    it('requires status', () => {
      const errors = validateStep4({ type: 'billboard', offerType: 'sale' })
      expect(errors.status).toBe('Status dostępności jest wymagany')
    })

    it('requires availableFrom when status is soon_available', () => {
      const errors = validateStep4({ type: 'billboard', status: 'soon_available', offerType: 'sale' })
      expect(errors.availableFrom).toBe('Data dostępności jest wymagana')
    })

    it('does not require availableFrom when status is active', () => {
      const errors = validateStep4({ type: 'billboard', status: 'active', offerType: 'sale', trafficIntensity: 'high', roadClass: 'A', variant: 'standard' })
      expect(errors.availableFrom).toBeUndefined()
    })
  })

  describe('Variant Validation', () => {
    it('requires variant for billboard', () => {
      const errors = validateStep4({ type: 'billboard', status: 'active', offerType: 'sale', roadClass: 'A', trafficIntensity: 'high' })
      expect(errors.variant).toBe('Wariant jest wymagany')
    })

    it('requires variant for transport', () => {
      const errors = validateStep4({ type: 'transport', status: 'active', offerType: 'sale', transportScope: 'internal' })
      expect(errors.variant).toBe('Wariant jest wymagany')
    })

    it('does not require variant for banner', () => {
      const errors = validateStep4({ type: 'banner', status: 'active', offerType: 'sale', trafficIntensity: 'high' })
      expect(errors.variant).toBeUndefined()
    })
  })

  describe('Transport-specific Validation', () => {
    it('requires transport scope for transport type', () => {
      const errors = validateStep4({ type: 'transport', status: 'active', offerType: 'sale' })
      expect(errors.transportScope).toBe('Zakres reklamy jest wymagany')
    })
  })

  describe('Mobile-specific Validation', () => {
    it('requires exposure mode for mobile type', () => {
      const errors = validateStep4({ type: 'mobile', status: 'active', offerType: 'sale', variant: 'trailer' })
      expect(errors.mobileExposureMode).toBe('Tryb ekspozycji jest wymagany')
    })
  })
})

describe('Form Validation - Step 5 (Terms & Conditions)', () => {
  it('requires terms acceptance', () => {
    const errors = validateStep5({})
    expect(errors.acceptTerms).toBe('Musisz zaakceptować regulamin')
  })

  it('accepts when terms are accepted', () => {
    const errors = validateStep5({ acceptTerms: true })
    expect(errors.acceptTerms).toBeUndefined()
  })
})
