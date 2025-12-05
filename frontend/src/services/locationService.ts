export interface LocationResult {
    name: string
    displayName: string
    state?: string  // Voivodeship name
    lat: number
    lng: number
    type: 'city' | 'town' | 'village' | 'region'
    importance: number
    osmType?: string  // Original OSM type
    osmClass?: string  // Original OSM class
    addresstype?: string  // Address type from Nominatim
    city?: string // City/Town/Village name
}

const NOMINATIM_BASE_URL = 'https://nominatim.openstreetmap.org'
const USER_AGENT = 'ReklaMap/1.0'

// Debounce helper
let searchTimeout: NodeJS.Timeout | null = null

/**
 * Search for locations in Poland using Nominatim API
 * @param query Search query
 * @returns Array of location results
 */
export async function searchLocations(query: string): Promise<LocationResult[]> {
    if (!query || query.length < 2) {
        return []
    }

    try {
        // Add wildcard to query for better partial matching (e.g., "szklar" -> "szklar*")
        const searchQuery = query.trim()

        const params = new URLSearchParams({
            q: searchQuery,
            countrycodes: 'pl',
            format: 'json',
            addressdetails: '1',
            limit: '10',
            'accept-language': 'pl'
        })

        const response = await fetch(`${NOMINATIM_BASE_URL}/search?${params}`, {
            headers: {
                'User-Agent': USER_AGENT
            }
        })

        if (!response.ok) {
            throw new Error(`Nominatim API error: ${response.status}`)
        }

        const data = await response.json()

        return data.map((item: any) => {
            // Determine location type
            let type: LocationResult['type'] = 'city'
            if (item.type === 'administrative' || item.class === 'boundary') {
                type = 'region'
            } else if (item.type === 'town' || item.addresstype === 'town') {
                type = 'town'
            } else if (item.type === 'village' || item.addresstype === 'village') {
                type = 'village'
            }

            return {
                name: item.name || item.address?.city || item.address?.town || item.address?.village,
                displayName: item.display_name,
                state: item.address?.state || '',  // Extract voivodeship
                lat: parseFloat(item.lat),
                lng: parseFloat(item.lon),
                type,
                importance: item.importance || 0,
                osmType: item.type,  // Store original OSM type
                osmClass: item.class,  // Store original OSM class
                addresstype: item.addresstype,  // Store addresstype
                city: item.address?.city || item.address?.town || item.address?.village || ''
            }
        }).filter((loc: LocationResult) => loc.name) // Filter out invalid results
    } catch (error) {
        console.error('Error searching locations:', error)
        return []
    }
}

/**
 * Debounced search to respect Nominatim rate limits (1 req/sec)
 * @param query Search query
 * @param callback Callback with results
 */
export function debouncedSearchLocations(
    query: string,
    callback: (results: LocationResult[]) => void
): void {
    if (searchTimeout) {
        clearTimeout(searchTimeout)
    }

    searchTimeout = setTimeout(async () => {
        const results = await searchLocations(query)
        callback(results)
    }, 1000) // 1 second debounce to respect rate limits
}
