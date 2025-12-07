import { LocationCoords } from '../types'

/**
 * Normalizuje tekst usuwając polskie znaki diakrytyczne
 * @param text Tekst do normalizacji
 * @returns Znormalizowany tekst bez polskich znaków
 */
export function normalizePolishChars(text: string): string {
  if (!text) return text
  
  const polishChars: Record<string, string> = {
    'ą': 'a', 'Ą': 'A',  // ą, Ą
    'ć': 'c', 'Ć': 'C',  // ć, Ć
    'ę': 'e', 'Ę': 'E',  // ę, Ę
    'ł': 'l', 'Ł': 'L',  // ł, Ł
    'ń': 'n', 'Ń': 'N',  // ń, Ń
    'ó': 'o', 'Ó': 'O',  // ó, Ó
    'ś': 's', 'Ś': 'S',  // ś, Ś
    'ź': 'z', 'Ź': 'Z',  // ź, Ź
    'ż': 'z', 'Ż': 'Z'   // ż, Ż
  }
  
  return text.split('').map(char => polishChars[char] || char).join('')
}

export interface FilterParams {
  keyword?: string
  type?: string
  region?: string
  city?: string
  priceFrom?: number | null
  priceTo?: number | null
  priceUnit?: string
  rentalPeriod?: string
  orientation?: string
  widthFrom?: number | null
  widthTo?: number | null
  heightFrom?: number | null
  heightTo?: number | null
  trafficIntensity?: string
  status?: string[]
  hasLighting?: boolean
  onlyWithImage?: boolean
  priceIncludesPrint?: boolean
  graphicDesignHelp?: boolean
  offerType?: string
  hasVatInvoice?: boolean
  selectedLocationCoords?: LocationCoords | null
}

// Mapowanie wartości na polskie odpowiedniki dla URL (bez polskich znaków)
const valueMapping: Record<string, Record<string, string>> = {
  priceUnit: {
    day: 'dzien',
    week: 'tydzien',
    month: 'miesiac',
    year: 'rok',
    sqm: 'm2'
  },
  rentalPeriod: {
    short_term: 'krotkoterminowy',
    long_term: 'dlugoterminowy'
  },
  orientation: {
    vertical: 'pion',
    horizontal: 'poziom'
  },
  trafficIntensity: {
    low: 'niskie',
    medium: 'srednie',
    high: 'wysokie'
  },
  offerType: {
    owner: 'wlasciciel',
    agency: 'agencja'
  },
  status: {
    active: 'wolne',
    reserved: 'zarezerwowane',
    soon: 'wkrotce'
  },
  sort: {
    newest: 'najnowsze',
    oldest: 'najstarsze',
    'name-asc': 'nazwa-a-z',
    'name-desc': 'nazwa-z-a',
    'price-day-asc': 'cena-dzien-rosnaco',
    'price-day-desc': 'cena-dzien-malejaco',
    'price-week-asc': 'cena-tydzien-rosnaco',
    'price-week-desc': 'cena-tydzien-malejaco',
    'price-month-asc': 'cena-miesiac-rosnaco',
    'price-month-desc': 'cena-miesiac-malejaco',
    'price-year-asc': 'cena-rok-rosnaco',
    'price-year-desc': 'cena-rok-malejaco',
    'price-sqm-asc': 'cena-m2-rosnaco',
    'price-sqm-desc': 'cena-m2-malejaco'
  }
}

// Odwrotne mapowanie dla konwersji z URL do filtrów
const reverseValueMapping: Record<string, Record<string, string>> = {}
Object.entries(valueMapping).forEach(([category, mappings]) => {
  reverseValueMapping[category] = {}
  Object.entries(mappings).forEach(([key, value]) => {
    reverseValueMapping[category][value] = key
  })
})

/**
 * Konwertuje obiekt filtrów na parametry URL z polskimi wartościami bez polskich znaków
 * @param filters Obiekt zawierający filtry
 * @returns Obiekt z parametrami URL z polskimi wartościami
 */
export function filtersToQueryParams(filters: FilterParams): Record<string, string> {
  const params: Record<string, string> = {}
  
  // Dodaj tylko niepuste wartości do parametrów URL
  if (filters.keyword) params.q = normalizePolishChars(filters.keyword)
  if (filters.type) params.type = filters.type
  if (filters.region) params.region = normalizePolishChars(filters.region)
  if (filters.city) params.city = normalizePolishChars(filters.city)
  
  // Wartości liczbowe
  if (filters.priceFrom !== null && filters.priceFrom !== undefined) {
    params.priceFrom = filters.priceFrom.toString()
  }
  if (filters.priceTo !== null && filters.priceTo !== undefined) {
    params.priceTo = filters.priceTo.toString()
  }
  if (filters.widthFrom !== null && filters.widthFrom !== undefined) {
    params.widthFrom = filters.widthFrom.toString()
  }
  if (filters.widthTo !== null && filters.widthTo !== undefined) {
    params.widthTo = filters.widthTo.toString()
  }
  if (filters.heightFrom !== null && filters.heightFrom !== undefined) {
    params.heightFrom = filters.heightFrom.toString()
  }
  if (filters.heightTo !== null && filters.heightTo !== undefined) {
    params.heightTo = filters.heightTo.toString()
  }
  
  // Wartości z mapowaniem na polskie odpowiedniki bez polskich znaków
  if (filters.priceUnit) {
    params.priceUnit = valueMapping.priceUnit[filters.priceUnit] || normalizePolishChars(filters.priceUnit)
  }
  if (filters.rentalPeriod) {
    params.rentalPeriod = valueMapping.rentalPeriod[filters.rentalPeriod] || normalizePolishChars(filters.rentalPeriod)
  }
  if (filters.orientation) {
    params.orientation = valueMapping.orientation[filters.orientation] || normalizePolishChars(filters.orientation)
  }
  if (filters.trafficIntensity) {
    params.trafficIntensity = valueMapping.trafficIntensity[filters.trafficIntensity] || normalizePolishChars(filters.trafficIntensity)
  }
  if (filters.offerType) {
    params.offerType = valueMapping.offerType[filters.offerType] || normalizePolishChars(filters.offerType)
  }
  
  // Konwersja tablicy statusów na string z polskimi nazwami bez polskich znaków
  if (filters.status && filters.status.length > 0) {
    const polishStatuses = filters.status.map(status => valueMapping.status[status] || status)
    params.status = polishStatuses.join(',')
  }
  
  // Konwersja wartości boolean na polskie odpowiedniki
  if (filters.hasLighting) params.hasLighting = 'tak'
  if (filters.onlyWithImage) params.onlyWithImage = 'tak'
  if (filters.priceIncludesPrint) params.priceIncludesPrint = 'tak'
  if (filters.graphicDesignHelp) params.graphicDesignHelp = 'tak'
  if (filters.hasVatInvoice) params.hasVatInvoice = 'tak'
  
  // Konwersja współrzędnych lokalizacji
  if (filters.selectedLocationCoords) {
    params.lat = filters.selectedLocationCoords.lat.toString()
    params.lng = filters.selectedLocationCoords.lng.toString()
  }
  
  return params
}

/**
 * Konwertuje parametry URL z polskimi wartościami na obiekt filtrów
 * @param query Obiekt z parametrami URL
 * @returns Obiekt zawierający filtry
 */
export function queryParamsToFilters(query: Record<string, string>): FilterParams {
  const filters: FilterParams = {}
  
  // Konwersja parametrów URL na odpowiednie typy danych
  if (query.q) filters.keyword = query.q
  if (query.type) filters.type = query.type
  if (query.region) filters.region = query.region
  if (query.city) filters.city = query.city
  
  // Wartości liczbowe
  if (query.priceFrom) filters.priceFrom = parseFloat(query.priceFrom) || null
  if (query.priceTo) filters.priceTo = parseFloat(query.priceTo) || null
  if (query.widthFrom) filters.widthFrom = parseFloat(query.widthFrom) || null
  if (query.widthTo) filters.widthTo = parseFloat(query.widthTo) || null
  if (query.heightFrom) filters.heightFrom = parseFloat(query.heightFrom) || null
  if (query.heightTo) filters.heightTo = parseFloat(query.heightTo) || null
  
  // Wartości z mapowaniem - konwersja polskich wartości na angielskie klucze
  if (query.priceUnit) {
    filters.priceUnit = reverseValueMapping.priceUnit[query.priceUnit] || query.priceUnit
  }
  if (query.rentalPeriod) {
    filters.rentalPeriod = reverseValueMapping.rentalPeriod[query.rentalPeriod] || query.rentalPeriod
  }
  if (query.orientation) {
    filters.orientation = reverseValueMapping.orientation[query.orientation] || query.orientation
  }
  if (query.trafficIntensity) {
    filters.trafficIntensity = reverseValueMapping.trafficIntensity[query.trafficIntensity] || query.trafficIntensity
  }
  if (query.offerType) {
    filters.offerType = reverseValueMapping.offerType[query.offerType] || query.offerType
  }
  
  // Konwersja string na tablicę statusów z tłumaczeniem
  if (query.status) {
    const statuses = query.status.split(',')
    filters.status = statuses.map(status => {
      return reverseValueMapping.status[status] || status
    })
  } else {
    filters.status = []
  }
  
  // Konwersja string na wartości boolean
  filters.hasLighting = query.hasLighting === 'tak' || query.hasLighting === 'true'
  filters.onlyWithImage = query.onlyWithImage === 'tak' || query.onlyWithImage === 'true'
  filters.priceIncludesPrint = query.priceIncludesPrint === 'tak' || query.priceIncludesPrint === 'true'
  filters.graphicDesignHelp = query.graphicDesignHelp === 'tak' || query.graphicDesignHelp === 'true'
  filters.hasVatInvoice = query.hasVatInvoice === 'tak' || query.hasVatInvoice === 'true'
  
  // Konwersja współrzędnych lokalizacji
  if (query.lat && query.lng) {
    filters.selectedLocationCoords = {
      lat: parseFloat(query.lat),
      lng: parseFloat(query.lng)
    }
  } else {
    filters.selectedLocationCoords = null
  }
  
  return filters
}
