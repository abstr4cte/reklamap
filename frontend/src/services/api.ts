import type { Advertisement, MapPin } from '../types'
import { API_URL, STORAGE_URL } from '../config'

// Funkcja pomocnicza do konwersji względnych ścieżek na pełne URL-e
export const getFullImageUrl = (path: string): string => {
    // Jeśli ścieżka jest null lub undefined, zwróć pusty string
    if (!path) {
        return ''
    }

    // Jeśli ścieżka jest już pełnym URL-em, zwróć ją bez zmian
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path
    }

    // W przeciwnym razie dodaj prefiks STORAGE_URL
    const fullUrl = `${STORAGE_URL}/${path}`
    return fullUrl
}

const APP_KEY = import.meta.env.VITE_INTERNAL_APP_KEY as string
const withKey = (headers: HeadersInit = {}): HeadersInit => ({ ...(headers as any), 'X-App-Key': APP_KEY })

// Helper to include management token for sensitive operations (PATCH, PUT, DELETE)
const withManagementToken = (headers: HeadersInit = {}): HeadersInit => {
    const token = sessionStorage.getItem('management_token')
    if (token) {
        return { ...(headers as any), 'X-Management-Token': token }
    }
    return headers as any
}

export const api = {
    async get(endpoint: string): Promise<any> {
        const response = await fetch(`${API_URL}${endpoint}`, { headers: withKey() })
        if (!response.ok) throw new Error(`Failed to fetch ${endpoint}`)
        return response.json()
    },

    async getAdvertisements(params?: Record<string, any>): Promise<any> {
        let url = `${API_URL}/listings`
        if (params && Object.keys(params).length > 0) {
            const searchParams = new URLSearchParams()
            Object.entries(params).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    searchParams.set(key, String(value))
                }
            })
            const qs = searchParams.toString()
            if (qs) url += '?' + qs
        }
        const response = await fetch(url, { headers: withKey() })
        if (!response.ok) throw new Error('Failed to fetch listings')
        return response.json()
    },

    async getMapPins(params?: Record<string, any>): Promise<MapPin[]> {
        let url = `${API_URL}/listings/map-pins`
        if (params && Object.keys(params).length > 0) {
            const searchParams = new URLSearchParams()
            Object.entries(params).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    searchParams.set(key, String(value))
                }
            })
            const qs = searchParams.toString()
            if (qs) url += '?' + qs
        }
        const response = await fetch(url, { headers: withKey() })
        if (!response.ok) throw new Error('Failed to fetch map pins')
        return response.json()
    },

    async getAdvertisement(id: number): Promise<Advertisement | null> {
        const response = await fetch(`${API_URL}/listings/${id}`, { headers: withKey() })
        if (!response.ok) {
            if (response.status === 404) return null
            throw new Error('Failed to fetch advertisement')
        }
        return response.json()
    },

    async getAdvertisementsByIds(ids: number[]): Promise<Advertisement[]> {
        if (ids.length === 0) return []
        const response = await fetch(`${API_URL}/listings?ids=${ids.join(',')}`, { headers: withKey() })
        if (!response.ok) throw new Error('Failed to fetch listings by ids')
        const ads = await response.json()

        // Preserve the order from the ids parameter
        const adsMap = new Map(ads.map((ad: Advertisement) => [ad.id, ad]))
        return ids.map(id => adsMap.get(id)).filter(Boolean) as Advertisement[]
    },

    async createAdvertisement(ad: Omit<Advertisement, 'id' | 'created_at' | 'updated_at'>): Promise<Advertisement> {
        const response = await fetch(`${API_URL}/listings`, {
            method: 'POST',
            headers: {
                ...withKey({
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                })
            },
            body: JSON.stringify(ad),
        })
        if (!response.ok) {
            const errorData = await response.json();
            const error = new Error(errorData.message || 'Failed to create advertisement');
            (error as any).response = { data: errorData }; // Attach server response data
            throw error;
        }
        return response.json()
    },

    async updateAdvertisementStatus(id: string, status: string, available_from: string | null): Promise<Advertisement> {
        const response = await fetch(`${API_URL}/listings/${id}/status`, {
            method: 'PATCH',
            headers: {
                ...withManagementToken(withKey({
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                }))
            },
            body: JSON.stringify({ status, available_from }),
        });

        if (!response.ok) {
            const errorData = await response.json();
            const error = new Error(errorData.message || 'Failed to update status');
            (error as any).response = { data: errorData };
            throw error;
        }

        return response.json();
    },

    async updateAdvertisement(id: string, updates: Partial<Advertisement>): Promise<void> {
        const response = await fetch(`${API_URL}/listings/${id}`, {
            method: 'PUT',
            headers: {
                ...withManagementToken(withKey({
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                }))
            },
            body: JSON.stringify(updates),
        })
        if (!response.ok) throw new Error('Failed to update advertisement')
    },

    async deleteAdvertisement(id: number): Promise<void> {
        const response = await fetch(`${API_URL}/listings/${id}`, {
            method: 'DELETE',
            headers: withManagementToken(withKey()),
        })
        if (!response.ok) throw new Error('Failed to delete advertisement')
    },

    async incrementViews(id: string): Promise<void> {
        await fetch(`${API_URL}/listings/${id}/increment-views`, {
            method: 'POST',
            headers: withKey(),
        })
    },
    async incrementPhoneClicks(id: string): Promise<void> {
        await fetch(`${API_URL}/listings/${id}/increment-phone-clicks`, {
            method: 'POST',
            headers: withKey(),
        })
    },
    async incrementEmailClicks(id: string): Promise<void> {
        await fetch(`${API_URL}/listings/${id}/increment-email-clicks`, {
            method: 'POST',
            headers: withKey(),
        })
    },

    async getSimilarAdvertisements(ad: Advertisement): Promise<Advertisement[]> {
        const response = await fetch(`${API_URL}/listings/${ad.id}/similar`, { headers: withKey() })
        if (!response.ok) return []
        return response.json()
    },

    async getDailyStats(id: string): Promise<any> {
        const response = await fetch(`${API_URL}/listings/${id}/daily-stats`, { headers: withKey() })
        if (!response.ok) throw new Error('Failed to fetch daily stats')
        return response.json()
    },

    async getMultipleDailyStats(ids: number[], days: number = 30): Promise<any[]> {
        const response = await fetch(`${API_URL}/listings/daily-stats/multiple`, {
            method: 'POST',
            headers: {
                ...withKey({
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                })
            },
            body: JSON.stringify({ advertisement_ids: ids, days }),
        })
        if (!response.ok) throw new Error('Failed to fetch multiple daily stats')
        return response.json()
    },

    async submitReport(report: { advertisement_id: string; reason: string; details: string; recaptcha_token?: string }): Promise<void> {
        const response = await fetch(`${API_URL}/reports`, {
            method: 'POST',
            headers: {
                ...withKey({
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                })
            },
            body: JSON.stringify(report),
        })
        if (!response.ok) throw new Error('Failed to submit report')
    },

    async contactAdvertisementOwner(id: string, contact: { email: string; message: string; recaptcha_token?: string }): Promise<{ message: string }> {
        const response = await fetch(`${API_URL}/listings/${id}/contact`, {
            method: 'POST',
            headers: {
                ...withKey({
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                })
            },
            body: JSON.stringify(contact),
        })
        if (!response.ok) {
            const error = await response.json()
            throw new Error(error.message || 'Failed to send contact message')
        }
        return response.json()
    },

    async submitFeedback(feedback: { type: string; email: string; message: string; url: string; userAgent: string; recaptcha_token?: string }): Promise<{ message: string }> {
        const response = await fetch(`${API_URL}/feedback`, {
            method: 'POST',
            headers: {
                ...withKey({
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                })
            },
            body: JSON.stringify(feedback),
        })
        if (!response.ok) {
            const error = await response.json()
            throw new Error(error.message || 'Failed to submit feedback')
        }
        return response.json()
    },

    async submitContact(contact: { name: string; email: string; phone: string; subject: string; message: string; recaptcha_token?: string }): Promise<{ message: string }> {
        const response = await fetch(`${API_URL}/contact`, {
            method: 'POST',
            headers: {
                ...withKey({
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                })
            },
            body: JSON.stringify(contact),
        })
        if (!response.ok) {
            const error = await response.json()
            throw new Error(error.message || 'Failed to submit contact form')
        }
        return response.json()
    },

    async subscribeNewsletter(email: string, recaptcha_token?: string): Promise<{ message: string }> {
        const response = await fetch(`${API_URL}/newsletter/subscribe`, {
            method: 'POST',
            headers: {
                ...withKey({
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                })
            },
            body: JSON.stringify({ email, recaptcha_token }),
        })
        if (!response.ok) {
            const error = await response.json()
            throw new Error(error.message || 'Failed to subscribe to newsletter')
        }
        return response.json()
    },
    async saveSearchAlert(data: { email: string, type?: string, city?: string, region?: string, filters?: any, recaptcha_token?: string }): Promise<{ message: string }> {
        const response = await fetch(`${API_URL}/search-alerts`, {
            method: 'POST',
            headers: {
                ...withKey({
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                })
            },
            body: JSON.stringify(data),
        })
        if (!response.ok) {
            const error = await response.json()
            throw new Error(error.message || 'Failed to save search alert')
        }
        return response.json()
    },


    storage: {
        async upload(file: File): Promise<string> {
            const formData = new FormData()
            formData.append('file', file)

            const response = await fetch(`${API_URL}/upload`, {
                method: 'POST',
                headers: withKey(),
                body: formData,
            })

            if (!response.ok) {
                // Try to extract meaningful error message from server response
                try {
                    const errData = await response.json()
                    // Laravel validation errors: { message: '...', errors: { file: ['...'] } }
                    const message = errData?.errors?.file?.[0] || errData?.message || errData?.error || 'Nie udało się przesłać zdjęcia'
                    throw new Error(message)
                } catch (parseError: any) {
                    if (parseError?.message && parseError.message !== 'Unexpected token') {
                        throw parseError
                    }
                    throw new Error(`Nie udało się przesłać zdjęcia (HTTP ${response.status})`)
                }
            }

            const responseText = await response.text()

            try {
                const data = JSON.parse(responseText)

                // New format with WebP support
                if (typeof data === 'object' && data.default) {
                    // Backend returns: { jpg: '...', webp: '...', default: '...' }
                    // We store the JPG path (default) in database
                    // Frontend will automatically use WebP via WebPImage component
                    return data.default
                }

                // Old format (backward compatibility)
                if (typeof data === 'string') {
                    return data
                }

                return data
            } catch (e) {
                // Fallback for plain text response
                return responseText
            }
        }
    }
}
