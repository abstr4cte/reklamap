import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { usePreferencesStore } from '../../../src/stores/usePreferencesStore'
import { api } from '../../../src/services/api'

/**
 * Tests for usePreferencesStore
 * 
 * Features tested:
 * - Favorites: add, remove, check, localStorage sync
 * - Comparison: add, remove, check, clear, localStorage sync
 * - Validation: max 5 items in comparison
 * - Validation: only same type in comparison
 * - Validation: only active ads
 */

// Mock API
vi.mock('../../../src/services/api', () => ({
  api: {
    getAdvertisement: vi.fn(),
    getAdvertisementsByIds: vi.fn(),
  },
}))

// Mock toast
vi.mock('../../../src/composables/useToast', () => ({
  useToast: () => ({
    showToast: vi.fn(),
  }),
}))

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {}
  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => { store[key] = value },
    removeItem: (key: string) => { delete store[key] },
    clear: () => { store = {} },
  }
})()

Object.defineProperty(window, 'localStorage', {
  value: localStorageMock,
})

describe('usePreferencesStore - Favorites', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorageMock.clear()
    vi.clearAllMocks()
  })

  it('adds advertisement to favorites', async () => {
    const store = usePreferencesStore()
    
    // Mock API response - active ad
    vi.mocked(api.getAdvertisement).mockResolvedValue({
      id: 1,
      is_active: true,
    } as any)

    await store.toggleFavorite(1)

    expect(store.favorites).toContain(1)
    expect(store.isFavorite(1)).toBe(true)
  })

  it('removes advertisement from favorites', async () => {
    const store = usePreferencesStore()
    
    // Add first
    vi.mocked(api.getAdvertisement).mockResolvedValue({ id: 1, is_active: true } as any)
    await store.toggleFavorite(1)
    expect(store.favorites).toContain(1)

    // Remove
    await store.toggleFavorite(1)
    expect(store.favorites).not.toContain(1)
    expect(store.isFavorite(1)).toBe(false)
  })

  it('does not add inactive advertisement to favorites', async () => {
    const store = usePreferencesStore()
    
    // Mock API response - inactive ad
    vi.mocked(api.getAdvertisement).mockResolvedValue({
      id: 1,
      is_active: false,
    } as any)

    await store.toggleFavorite(1)

    expect(store.favorites).not.toContain(1)
  })

  it('persists favorites to localStorage', async () => {
    const store = usePreferencesStore()
    
    vi.mocked(api.getAdvertisement).mockResolvedValue({ id: 1, is_active: true } as any)
    await store.toggleFavorite(1)

    // Wait for watcher to trigger
    await new Promise(resolve => setTimeout(resolve, 10))

    const stored = JSON.parse(localStorageMock.getItem('favorites') || '[]')
    expect(stored).toContain(1)
  })
})

describe('usePreferencesStore - Comparison', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorageMock.clear()
    vi.clearAllMocks()
  })

  it('adds advertisement to comparison', async () => {
    const store = usePreferencesStore()
    
    vi.mocked(api.getAdvertisement).mockResolvedValue({
      id: 1,
      type: 'billboard',
      is_active: true,
    } as any)

    const result = await store.toggleComparison(1)

    expect(result.success).toBe(true)
    expect(store.comparison).toContain(1)
    expect(store.isCompared(1)).toBe(true)
  })

  it('removes advertisement from comparison', async () => {
    const store = usePreferencesStore()
    
    // Add first
    vi.mocked(api.getAdvertisement).mockResolvedValue({
      id: 1,
      type: 'billboard',
      is_active: true,
    } as any)
    await store.toggleComparison(1)
    
    // Remove
    const result = await store.toggleComparison(1)
    
    expect(result.success).toBe(true)
    expect(store.comparison).not.toContain(1)
    expect(store.isCompared(1)).toBe(false)
  })

  it('enforces max 5 items in comparison', async () => {
    const store = usePreferencesStore()
    
    // Add 5 ads
    for (let i = 1; i <= 5; i++) {
      vi.mocked(api.getAdvertisement).mockResolvedValue({
        id: i,
        type: 'billboard',
        is_active: true,
      } as any)
      
      if (i === 1) {
        await store.toggleComparison(i)
      } else {
        vi.mocked(api.getAdvertisementsByIds).mockResolvedValue([
          { id: 1, type: 'billboard', is_active: true },
        ] as any)
        await store.toggleComparison(i)
      }
    }
    
    expect(store.comparison).toHaveLength(5)
    
    // Try to add 6th
    vi.mocked(api.getAdvertisement).mockResolvedValue({
      id: 6,
      type: 'billboard',
      is_active: true,
    } as any)
    
    const result = await store.toggleComparison(6)
    
    expect(result.success).toBe(false)
    expect(result.error).toBe('Możesz porównać maksymalnie 5 ogłoszeń')
    expect(store.comparison).toHaveLength(5)
    expect(store.comparison).not.toContain(6)
  })

  it('enforces same type validation in comparison', async () => {
    const store = usePreferencesStore()
    
    // Add billboard
    vi.mocked(api.getAdvertisement).mockResolvedValue({
      id: 1,
      type: 'billboard',
      is_active: true,
    } as any)
    await store.toggleComparison(1)
    
    // Try to add LED screen
    vi.mocked(api.getAdvertisement).mockResolvedValue({
      id: 2,
      type: 'led_screen',
      is_active: true,
    } as any)
    
    vi.mocked(api.getAdvertisementsByIds).mockResolvedValue([
      { id: 1, type: 'billboard', is_active: true },
    ] as any)
    
    const result = await store.toggleComparison(2)
    
    expect(result.success).toBe(false)
    expect(result.error).toBe('Możesz porównywać tylko ogłoszenia tego samego typu')
    expect(store.comparison).toHaveLength(1)
    expect(store.comparison).not.toContain(2)
  })

  it('does not add inactive advertisement to comparison', async () => {
    const store = usePreferencesStore()
    
    vi.mocked(api.getAdvertisement).mockResolvedValue({
      id: 1,
      type: 'billboard',
      is_active: false,
    } as any)

    const result = await store.toggleComparison(1)

    expect(result.success).toBe(false)
    expect(result.error).toBe('Ogłoszenie jest zarchiwizowane lub nieaktywne')
    expect(store.comparison).not.toContain(1)
  })

  it('clears all comparison items', async () => {
    const store = usePreferencesStore()
    
    // Add 3 ads
    for (let i = 1; i <= 3; i++) {
      vi.mocked(api.getAdvertisement).mockResolvedValue({
        id: i,
        type: 'billboard',
        is_active: true,
      } as any)
      
      if (i === 1) {
        await store.toggleComparison(i)
      } else {
        vi.mocked(api.getAdvertisementsByIds).mockResolvedValue([
          { id: 1, type: 'billboard', is_active: true },
        ] as any)
        await store.toggleComparison(i)
      }
    }
    
    expect(store.comparison).toHaveLength(3)
    
    store.clearComparison()
    
    expect(store.comparison).toHaveLength(0)
  })

  it('persists comparison to localStorage', async () => {
    const store = usePreferencesStore()
    
    vi.mocked(api.getAdvertisement).mockResolvedValue({
      id: 1,
      type: 'billboard',
      is_active: true,
    } as any)
    await store.toggleComparison(1)

    // Wait for watcher to trigger
    await new Promise(resolve => setTimeout(resolve, 10))

    const stored = JSON.parse(localStorageMock.getItem('comparison') || '[]')
    expect(stored).toContain(1)
  })
})

describe('usePreferencesStore - LocalStorage Sync', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorageMock.clear()
    vi.clearAllMocks()
  })

  it('loads favorites from localStorage on init', () => {
    localStorageMock.setItem('favorites', JSON.stringify([1, 2, 3]))
    
    const store = usePreferencesStore()
    
    expect(store.favorites).toEqual([1, 2, 3])
  })

  it('loads comparison from localStorage on init', () => {
    localStorageMock.setItem('comparison', JSON.stringify([5, 6]))
    
    const store = usePreferencesStore()
    
    expect(store.comparison).toEqual([5, 6])
  })

  it('handles corrupted localStorage data gracefully', () => {
    localStorageMock.setItem('favorites', 'invalid json')
    localStorageMock.setItem('comparison', '{not an array}')
    
    const store = usePreferencesStore()
    
    expect(store.favorites).toEqual([])
    expect(store.comparison).toEqual([])
  })
})
