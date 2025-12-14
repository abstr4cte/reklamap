import type { Advertisement } from '../types'
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

export const api = {
    async getAdvertisements(): Promise<Advertisement[]> {
        const response = await fetch(`${API_URL}/advertisements`)
        if (!response.ok) throw new Error('Failed to fetch advertisements')
        return response.json()
    },

    async getAdvertisement(id: string): Promise<Advertisement | null> {
        const response = await fetch(`${API_URL}/advertisements/${id}`)
        if (!response.ok) {
            if (response.status === 404) return null
            throw new Error('Failed to fetch advertisement')
        }
        return response.json()
    },

    async getAdvertisementsByIds(ids: string[]): Promise<Advertisement[]> {
        if (ids.length === 0) return []
        const response = await fetch(`${API_URL}/advertisements?ids=${ids.join(',')}`)
        if (!response.ok) throw new Error('Failed to fetch advertisements by ids')
        return response.json()
    },

    async createAdvertisement(ad: Omit<Advertisement, 'id' | 'created_at' | 'updated_at'>): Promise<Advertisement> {
        const response = await fetch(`${API_URL}/advertisements`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(ad),
        })
        if (!response.ok) {
            const error = await response.json()
            throw new Error(error.message || 'Failed to create advertisement')
        }
        return response.json()
    },

    async updateAdvertisement(id: string, updates: Partial<Advertisement>): Promise<void> {
        const response = await fetch(`${API_URL}/advertisements/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(updates),
        })
        if (!response.ok) throw new Error('Failed to update advertisement')
    },

    async deleteAdvertisement(id: string): Promise<void> {
        const response = await fetch(`${API_URL}/advertisements/${id}`, {
            method: 'DELETE',
        })
        if (!response.ok) throw new Error('Failed to delete advertisement')
    },

    async incrementViews(id: string): Promise<void> {
        await fetch(`${API_URL}/advertisements/${id}/increment-views`, {
            method: 'POST',
        })
    },

    async getSimilarAdvertisements(ad: Advertisement): Promise<Advertisement[]> {
        const response = await fetch(`${API_URL}/advertisements/${ad.id}/similar`)
        if (!response.ok) return []
        return response.json()
    },

    async submitReport(report: { advertisement_id: string; reason: string; details: string }): Promise<void> {
        const response = await fetch(`${API_URL}/reports`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(report),
        })
        if (!response.ok) throw new Error('Failed to submit report')
    },

    async contactAdvertisementOwner(id: string, contact: { email: string; message: string }): Promise<{ message: string }> {
        const response = await fetch(`${API_URL}/advertisements/${id}/contact`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(contact),
        })
        if (!response.ok) {
            const error = await response.json()
            throw new Error(error.message || 'Failed to send contact message')
        }
        return response.json()
    },

    storage: {
        async upload(file: File): Promise<string> {
            const formData = new FormData()
            formData.append('file', file)

            const response = await fetch(`${API_URL}/upload`, {
                method: 'POST',
                body: formData,
            })

            if (!response.ok) {
                throw new Error('Failed to upload file')
            }

            const responseText = await response.text()

            try {
                const path = JSON.parse(responseText)
                return path
            } catch (e) {
                return responseText
            }
        }
    }
}
