import { LocationCoords } from '../types'

export interface MapBounds {
  northEast: { lat: number; lng: number }
  southWest: { lat: number; lng: number }
}

export interface FilterParams {
  keyword: string
  type: string
  region: string
  city: string
  priceFrom: number | null
  priceTo: number | null
  priceUnit: string
  rentalPeriod: string
  orientation: string
  widthFrom: number | null
  widthTo: number | null
  heightFrom: number | null
  heightTo: number | null
  surfaceFrom: number | null
  surfaceTo: number | null
  trafficIntensity: string
  trafficDirection: string[]
  trafficType: string[]
  status: string[]
  hasBacklight: boolean
  onlyWithImage: boolean
  priceIncludesPrint: boolean
  priceIncludesMounting: boolean
  graphicDesignHelp: boolean
  offerType: string
  hasVatInvoice: boolean
  selectedLocationCoords: LocationCoords | null
  cityStrict: boolean
  // Type-specific filters
  variant: string
  roadClass: string
  environment: string
  // LED screen filters
  resolution: string
  pixelPitchFrom: number | null
  pixelPitchTo: number | null
  brightnessFrom: number | null
  brightnessTo: number | null
  transportScope: string
  vehicleCountFrom: number | null
  vehicleCountTo: number | null
  mobileExposureMode: string
  campaignDurationFrom: number | null
  campaignDurationTo: number | null
  // Extended options
  lightingType: string
  dailyPassengersFrom: number | null
  dailyPassengersTo: number | null
  operatingZone: string
  ambientLightControl: boolean
  // Beacons/Backlight checkboxes
  hasLightingTypeBanner: boolean
  hasLightingTypeBillboard: boolean
  // OTS
  estimatedDailyViewsFrom: number | null
  estimatedDailyViewsTo: number | null
  locationLabel: string
  street: string
  mapBounds: MapBounds | null
}

export const DEFAULT_FILTERS: FilterParams = {
  keyword: '',
  type: '',
  region: '',
  city: '',
  street: '',
  priceFrom: null,
  priceTo: null,
  priceUnit: 'month',
  rentalPeriod: '',
  orientation: '',
  widthFrom: null,
  widthTo: null,
  heightFrom: null,
  heightTo: null,
  surfaceFrom: null,
  surfaceTo: null,
  trafficIntensity: '',
  trafficDirection: [],
  trafficType: [],
  status: [],
  hasBacklight: false,
  onlyWithImage: false,
  priceIncludesPrint: false,
  priceIncludesMounting: false,
  graphicDesignHelp: false,
  offerType: '',
  hasVatInvoice: false,
  selectedLocationCoords: null,
  cityStrict: false,
  variant: '',
  roadClass: '',
  environment: '',
  resolution: '',
  pixelPitchFrom: null,
  pixelPitchTo: null,
  brightnessFrom: null,
  brightnessTo: null,
  transportScope: '',
  vehicleCountFrom: null,
  vehicleCountTo: null,
  mobileExposureMode: '',
  campaignDurationFrom: null,
  campaignDurationTo: null,
  lightingType: '',
  dailyPassengersFrom: null,
  dailyPassengersTo: null,
  operatingZone: '',
  ambientLightControl: false,
  hasLightingTypeBanner: false,
  hasLightingTypeBillboard: false,
  estimatedDailyViewsFrom: null,
  estimatedDailyViewsTo: null,
  locationLabel: '',
  mapBounds: null,
}
