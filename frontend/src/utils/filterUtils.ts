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
  cityStrict?: boolean
  street?: string
  mapBounds?: any
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
  trafficDirection?: string[]
  trafficType?: string[]
  status?: string[]
  hasBacklight?: boolean
  onlyWithImage?: boolean
  priceIncludesPrint?: boolean
  priceIncludesMounting?: boolean
  graphicDesignHelp?: boolean
  offerType?: string
  hasVatInvoice?: boolean
  selectedLocationCoords?: LocationCoords | null
  hasLightingTypeBanner?: boolean
  hasLightingTypeBillboard?: boolean
  estimatedDailyViewsFrom?: number | null
  estimatedDailyViewsTo?: number | null
  // Extended filters
  lightingType?: string
  dailyPassengersFrom?: number | null
  dailyPassengersTo?: number | null
  operatingZone?: string
  ambientLightControl?: boolean
  transportScope?: string
  vehicleCountFrom?: number | null
  vehicleCountTo?: number | null
  mobileExposureMode?: string
  campaignDurationFrom?: number | null
  campaignDurationTo?: number | null
  resolution?: string
  pixelPitchFrom?: number | null
  pixelPitchTo?: number | null
  brightnessFrom?: number | null
  brightnessTo?: number | null
  variant?: string
  roadClass?: string
  environment?: string
  locationLabel?: string
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
    agency: 'agencja',
    sublease: 'podnajem'
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
  if (filters.cityStrict) params.cityStrict = 'tak'

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
  if (filters.estimatedDailyViewsFrom !== null && filters.estimatedDailyViewsFrom !== undefined) {
    params.otsFrom = filters.estimatedDailyViewsFrom.toString()
  }
  if (filters.estimatedDailyViewsTo !== null && filters.estimatedDailyViewsTo !== undefined) {
    params.otsTo = filters.estimatedDailyViewsTo.toString()
  }

  // Wartości z mapowaniem na polskie odpowiedniki bez polskich znaków
  // Dodaj priceUnit TYLKO gdy użytkownik wpisał cenę
  if (filters.priceUnit && (filters.priceFrom !== null && filters.priceFrom !== undefined || filters.priceTo !== null && filters.priceTo !== undefined)) {
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
  if (filters.hasBacklight) params.hasBacklight = 'tak'
  if (filters.onlyWithImage) params.onlyWithImage = 'tak'
  if (filters.priceIncludesPrint) params.priceIncludesPrint = 'tak'
  if (filters.graphicDesignHelp) params.graphicDesignHelp = 'tak'
  if (filters.hasVatInvoice) params.hasVatInvoice = 'tak'
  if (filters.hasLightingTypeBanner) params.hasLightingTypeBanner = 'tak'
  if (filters.hasLightingTypeBillboard) params.hasLightingTypeBillboard = 'tak'
  if (filters.ambientLightControl) params.ambientLightControl = 'tak'
  if (filters.priceIncludesMounting) params.priceIncludesMounting = 'tak'
  
  // Lokalizacja szczegółowa
  if (filters.street) params.street = normalizePolishChars(filters.street)
  if (filters.locationLabel) params.loc = normalizePolishChars(filters.locationLabel)

  // Konwersja współrzędnych lokalizacji - usunięto z URL by nie zaśmiecać linków

  // Dodatkowe filtry
  if (filters.variant) params.variant = filters.variant
  if (filters.roadClass) params.roadClass = filters.roadClass
  if (filters.environment) params.environment = filters.environment
  
  // Traffic direction i traffic type (tablice)
  if (filters.trafficDirection && filters.trafficDirection.length > 0) {
    params.trafficDirection = filters.trafficDirection.join(',')
  }
  if (filters.trafficType && filters.trafficType.length > 0) {
    params.trafficType = filters.trafficType.join(',')
  }
  if (filters.transportScope) params.transportScope = filters.transportScope
  if (filters.mobileExposureMode) params.mobileExposureMode = filters.mobileExposureMode
  if (filters.operatingZone) params.operatingZone = filters.operatingZone
  if (filters.lightingType) params.lightingType = filters.lightingType
  if (filters.resolution) params.resolution = filters.resolution
  
  // Wartości liczbowe - extended
  if (filters.vehicleCountFrom !== null && filters.vehicleCountFrom !== undefined) {
    params.vehicleCountFrom = filters.vehicleCountFrom.toString()
  }
  if (filters.vehicleCountTo !== null && filters.vehicleCountTo !== undefined) {
    params.vehicleCountTo = filters.vehicleCountTo.toString()
  }
  if (filters.dailyPassengersFrom !== null && filters.dailyPassengersFrom !== undefined) {
    params.dailyPassengersFrom = filters.dailyPassengersFrom.toString()
  }
  if (filters.dailyPassengersTo !== null && filters.dailyPassengersTo !== undefined) {
    params.dailyPassengersTo = filters.dailyPassengersTo.toString()
  }
  if (filters.pixelPitchFrom !== null && filters.pixelPitchFrom !== undefined) {
    params.pixelPitchFrom = filters.pixelPitchFrom.toString()
  }
  if (filters.pixelPitchTo !== null && filters.pixelPitchTo !== undefined) {
    params.pixelPitchTo = filters.pixelPitchTo.toString()
  }
  if (filters.brightnessFrom !== null && filters.brightnessFrom !== undefined) {
    params.brightnessFrom = filters.brightnessFrom.toString()
  }
  if (filters.brightnessTo !== null && filters.brightnessTo !== undefined) {
    params.brightnessTo = filters.brightnessTo.toString()
  }
  if (filters.campaignDurationFrom !== null && filters.campaignDurationFrom !== undefined) {
    params.campaignDurationFrom = filters.campaignDurationFrom.toString()
  }
  if (filters.campaignDurationTo !== null && filters.campaignDurationTo !== undefined) {
    params.campaignDurationTo = filters.campaignDurationTo.toString()
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
  if (query.otsFrom) filters.estimatedDailyViewsFrom = parseFloat(query.otsFrom) || null
  if (query.otsTo) filters.estimatedDailyViewsTo = parseFloat(query.otsTo) || null

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
  filters.hasBacklight = query.hasBacklight === 'tak' || query.hasBacklight === 'true'
  filters.onlyWithImage = query.onlyWithImage === 'tak' || query.onlyWithImage === 'true'
  filters.priceIncludesPrint = query.priceIncludesPrint === 'tak' || query.priceIncludesPrint === 'true'
  filters.graphicDesignHelp = query.graphicDesignHelp === 'tak' || query.graphicDesignHelp === 'true'
  filters.hasVatInvoice = query.hasVatInvoice === 'tak' || query.hasVatInvoice === 'true'
  filters.hasLightingTypeBanner = query.hasLightingTypeBanner === 'tak' || query.hasLightingTypeBanner === 'true'
  filters.hasLightingTypeBillboard = query.hasLightingTypeBillboard === 'tak' || query.hasLightingTypeBillboard === 'true'
  filters.ambientLightControl = query.ambientLightControl === 'tak' || query.ambientLightControl === 'true'
  filters.cityStrict = query.cityStrict === 'tak' || query.cityStrict === 'true'
  filters.priceIncludesMounting = query.priceIncludesMounting === 'tak' || query.priceIncludesMounting === 'true'
  
  // Lokalizacja szczegółowa
  if (query.street) filters.street = query.street
  if (query.loc) filters.locationLabel = query.loc

  // Współrzędne lokalizacji - przestarzałe w query params, upewniamy się że nie wpadną gubiąc domyślnie
  filters.selectedLocationCoords = null

  // Dodatkowe filtry
  if (query.variant) filters.variant = query.variant
  if (query.roadClass) filters.roadClass = query.roadClass
  if (query.environment) filters.environment = query.environment
  
  // Traffic direction i traffic type (konwersja string na tablice)
  if (query.trafficDirection) {
    filters.trafficDirection = query.trafficDirection.split(',')
  }
  if (query.trafficType) {
    filters.trafficType = query.trafficType.split(',')
  }
  if (query.transportScope) filters.transportScope = query.transportScope
  if (query.mobileExposureMode) filters.mobileExposureMode = query.mobileExposureMode
  if (query.operatingZone) filters.operatingZone = query.operatingZone
  if (query.lightingType) filters.lightingType = query.lightingType
  if (query.resolution) filters.resolution = query.resolution
  
  // Wartości liczbowe - extended
  if (query.vehicleCountFrom) filters.vehicleCountFrom = parseFloat(query.vehicleCountFrom) || null
  if (query.vehicleCountTo) filters.vehicleCountTo = parseFloat(query.vehicleCountTo) || null
  if (query.dailyPassengersFrom) filters.dailyPassengersFrom = parseFloat(query.dailyPassengersFrom) || null
  if (query.dailyPassengersTo) filters.dailyPassengersTo = parseFloat(query.dailyPassengersTo) || null
  if (query.pixelPitchFrom) filters.pixelPitchFrom = parseFloat(query.pixelPitchFrom) || null
  if (query.pixelPitchTo) filters.pixelPitchTo = parseFloat(query.pixelPitchTo) || null
  if (query.brightnessFrom) filters.brightnessFrom = parseFloat(query.brightnessFrom) || null
  if (query.brightnessTo) filters.brightnessTo = parseFloat(query.brightnessTo) || null
  if (query.campaignDurationFrom) filters.campaignDurationFrom = parseFloat(query.campaignDurationFrom) || null
  if (query.campaignDurationTo) filters.campaignDurationTo = parseFloat(query.campaignDurationTo) || null

  return filters
}
