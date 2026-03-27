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
    road?: string // Specific road name
}

const NOMINATIM_BASE_URL = 'https://nominatim.openstreetmap.org'
const USER_AGENT = 'ReklaMap/1.0'

// Debounce helper
let searchTimeout: NodeJS.Timeout | null = null

/**
 * Filter Nominatim results to include only land-based locations (cities, towns, villages, roads, etc.)
 * Uses whitelist approach - only accepts specific addresstypes
 * @param data Array of Nominatim results
 * @returns Filtered array with only land-based locations
 */
export function filterWaterFeatures(data: any[]): any[] {
    // Whitelist of accepted addresstypes for land-based locations
    const acceptedAddressTypes = [
        'city',           // Miasta
        'town',           // Miasteczka
        'village',        // Wsie
        'hamlet',         // Przysiółki
        'municipality',   // Gminy
        'county',         // Powiaty
        'state',          // Województwa
        'administrative', // Jednostki administracyjne
        'suburb',         // Dzielnice
        'quarter',        // Kwartały
        'neighbourhood',  // Osiedla
        'road',           // Drogi/ulice
        'pedestrian',     // Ciągi piesze
        'residential',    // Obszary mieszkalne
        'house',          // Budynki
        'building',       // Budynki
        'postcode',       // Kody pocztowe
        'region',         // Regiony
        'locality'        // Miejscowości
    ]
    
    return data.filter((item: any) => {
        // Accept only if addresstype is in whitelist
        if (item.addresstype && acceptedAddressTypes.includes(item.addresstype)) {
            return true
        }
        
        // If no addresstype, check class/type combination
        // Accept place nodes (class=place)
        if (item.class === 'place' && item.type && 
            ['city', 'town', 'village', 'hamlet', 'suburb', 'neighbourhood', 'locality'].includes(item.type)) {
            return true
        }
        
        // Accept administrative boundaries (class=boundary, type=administrative)
        if (item.class === 'boundary' && item.type === 'administrative') {
            return true
        }
        
        // Accept highways/roads (class=highway)
        if (item.class === 'highway') {
            return true
        }
        
        // Reject everything else (including water features)
        return false
    })
}

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

        // Filter out water features (rivers, lakes, etc.)
        const filteredData = filterWaterFeatures(data)

        return filteredData.map((item: any) => {
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
                    city: item.address?.city || item.address?.town || item.address?.village || '',
                    road: item.address?.road || '' // Store specific road name
                }
            })
            .filter((loc: LocationResult) => loc.name) // Filter out invalid results
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
    }, 500) // 500ms debounce for better UX while respecting limits
}
