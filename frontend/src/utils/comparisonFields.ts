// Konfiguracja pól do wyświetlenia w porównywarce dla każdego typu ogłoszenia

export interface ComparisonField {
  key: string
  label: string
  required?: boolean // czy pole jest wymagane dla tego typu
}

// Wspólne pola dla wszystkich typów
const commonFields: ComparisonField[] = [
  { key: 'price', label: 'Cena', required: true },
  { key: 'type', label: 'Typ powierzchni', required: true },
  { key: 'location', label: 'Lokalizacja', required: true },
  { key: 'status', label: 'Status', required: true },
  { key: 'offer_type', label: 'Rodzaj oferty', required: true },
  { key: 'has_vat_invoice', label: 'Faktura VAT', required: true }
]

// Konfiguracja pól dla każdego typu
export const typeFieldsConfig: Record<string, ComparisonField[]> = {
  billboard: [
    { key: 'price', label: 'Cena', required: true },
    { key: 'price_per_sqm', label: 'Cena za m²', required: true },
    { key: 'type', label: 'Typ powierzchni', required: true },
    { key: 'variant', label: 'Wariant', required: true },
    { key: 'dimensions', label: 'Wymiary (szer × wys)', required: true },
    { key: 'surface_area', label: 'Powierzchnia', required: true },
    { key: 'orientation', label: 'Orientacja', required: true },
    { key: 'location', label: 'Lokalizacja', required: true },
    { key: 'location_tier', label: 'Klasa lokalizacji', required: false },
    { key: 'road_class', label: 'Klasa drogi', required: false },
    { key: 'traffic_intensity', label: 'Natężenie ruchu', required: true },
    { key: 'traffic_direction', label: 'Kierunek ruchu', required: false },
    { key: 'price_includes_print', label: 'Druk w cenie', required: false },
    { key: 'price_includes_mounting', label: 'Montaż w cenie', required: false },
    { key: 'graphic_design_help', label: 'Pomoc graficzna', required: false },
    { key: 'status', label: 'Status', required: true },
    { key: 'offer_type', label: 'Rodzaj oferty', required: true },
    { key: 'has_vat_invoice', label: 'Faktura VAT', required: true }
  ],
  citylight: [
    { key: 'price', label: 'Cena', required: true },
    { key: 'type', label: 'Typ powierzchni', required: true },
    { key: 'variant', label: 'Wariant', required: true },
    { key: 'dimensions', label: 'Wymiary (szer × wys)', required: false },
    { key: 'surface_area', label: 'Powierzchnia', required: false },
    { key: 'orientation', label: 'Orientacja', required: true },
    { key: 'location', label: 'Lokalizacja', required: true },
    { key: 'environment', label: 'Środowisko', required: false },
    { key: 'has_backlight', label: 'Podświetlenie', required: false },
    { key: 'price_includes_print', label: 'Druk w cenie', required: false },
    { key: 'price_includes_mounting', label: 'Montaż w cenie', required: false },
    { key: 'graphic_design_help', label: 'Pomoc graficzna', required: false },
    { key: 'status', label: 'Status', required: true },
    { key: 'offer_type', label: 'Rodzaj oferty', required: true },
    { key: 'has_vat_invoice', label: 'Faktura VAT', required: true }
  ],
  led_screen: [
    { key: 'price', label: 'Cena', required: true },
    { key: 'type', label: 'Typ powierzchni', required: true },
    { key: 'variant', label: 'Wariant', required: true },
    { key: 'dimensions', label: 'Wymiary (szer × wys)', required: false },
    { key: 'surface_area', label: 'Powierzchnia', required: false },
    { key: 'location', label: 'Lokalizacja', required: true },
    { key: 'environment', label: 'Środowisko', required: false },
    { key: 'spot_duration', label: 'Czas spotu (s)', required: true },
    { key: 'loop_duration', label: 'Pętla emisji (s)', required: true },
    { key: 'has_backlight', label: 'Podświetlenie', required: false },
    { key: 'status', label: 'Status', required: true },
    { key: 'offer_type', label: 'Rodzaj oferty', required: true },
    { key: 'has_vat_invoice', label: 'Faktura VAT', required: true }
  ],
  banner: [
    { key: 'price', label: 'Cena', required: true },
    { key: 'price_per_sqm', label: 'Cena za m²', required: true },
    { key: 'type', label: 'Typ powierzchni', required: true },
    { key: 'variant', label: 'Wariant', required: true },
    { key: 'dimensions', label: 'Wymiary (szer × wys)', required: true },
    { key: 'surface_area', label: 'Powierzchnia', required: true },
    { key: 'orientation', label: 'Orientacja', required: true },
    { key: 'location', label: 'Lokalizacja', required: true },
    { key: 'environment', label: 'Środowisko', required: false },
    { key: 'traffic_intensity', label: 'Natężenie ruchu', required: false },
    { key: 'traffic_type', label: 'Rodzaj ruchu', required: false },
    { key: 'price_includes_print', label: 'Druk w cenie', required: false },
    { key: 'price_includes_mounting', label: 'Montaż w cenie', required: false },
    { key: 'graphic_design_help', label: 'Pomoc graficzna', required: false },
    { key: 'status', label: 'Status', required: true },
    { key: 'offer_type', label: 'Rodzaj oferty', required: true },
    { key: 'has_vat_invoice', label: 'Faktura VAT', required: true }
  ],
  wall: [
    { key: 'price', label: 'Cena', required: true },
    { key: 'price_per_sqm', label: 'Cena za m²', required: true },
    { key: 'type', label: 'Typ powierzchni', required: true },
    { key: 'variant', label: 'Wariant', required: true },
    { key: 'dimensions', label: 'Wymiary (szer × wys)', required: true },
    { key: 'surface_area', label: 'Powierzchnia', required: true },
    { key: 'orientation', label: 'Orientacja', required: true },
    { key: 'location', label: 'Lokalizacja', required: true },
    { key: 'traffic_intensity', label: 'Natężenie ruchu', required: false },
    { key: 'price_includes_mounting', label: 'Montaż w cenie', required: false },
    { key: 'graphic_design_help', label: 'Pomoc graficzna', required: false },
    { key: 'status', label: 'Status', required: true },
    { key: 'offer_type', label: 'Rodzaj oferty', required: true },
    { key: 'has_vat_invoice', label: 'Faktura VAT', required: true }
  ],
  totem: [
    { key: 'price', label: 'Cena', required: true },
    { key: 'type', label: 'Typ powierzchni', required: true },
    { key: 'variant', label: 'Wariant', required: true },
    { key: 'dimensions', label: 'Wymiary (szer × wys)', required: false },
    { key: 'surface_area', label: 'Powierzchnia', required: false },
    { key: 'orientation', label: 'Orientacja', required: false },
    { key: 'location', label: 'Lokalizacja', required: true },
    { key: 'environment', label: 'Środowisko', required: false },
    { key: 'has_backlight', label: 'Podświetlenie', required: false },
    { key: 'status', label: 'Status', required: true },
    { key: 'offer_type', label: 'Rodzaj oferty', required: true },
    { key: 'has_vat_invoice', label: 'Faktura VAT', required: true }
  ],
  transport: [
    { key: 'price', label: 'Cena', required: true },
    { key: 'type', label: 'Typ powierzchni', required: true },
    { key: 'variant', label: 'Wariant', required: true },
    { key: 'location', label: 'Lokalizacja', required: true },
    { key: 'transport_scope', label: 'Zakres reklamy', required: true },
    { key: 'vehicle_count', label: 'Liczba pojazdów', required: false },
    { key: 'route_area', label: 'Obszar trasy', required: false },
    { key: 'operating_hours', label: 'Godziny operacyjne', required: false },
    { key: 'status', label: 'Status', required: true },
    { key: 'offer_type', label: 'Rodzaj oferty', required: true },
    { key: 'has_vat_invoice', label: 'Faktura VAT', required: true }
  ],
  mobile: [
    { key: 'price', label: 'Cena', required: true },
    { key: 'type', label: 'Typ powierzchni', required: true },
    { key: 'variant', label: 'Wariant', required: true },
    { key: 'location', label: 'Lokalizacja', required: true },
    { key: 'environment', label: 'Środowisko', required: false },
    { key: 'mobile_exposure_mode', label: 'Tryb ekspozycji', required: true },
    { key: 'route_area', label: 'Obszar trasy', required: false },
    { key: 'operating_hours', label: 'Godziny operacyjne', required: false },
    { key: 'status', label: 'Status', required: true },
    { key: 'offer_type', label: 'Rodzaj oferty', required: true },
    { key: 'has_vat_invoice', label: 'Faktura VAT', required: true }
  ],
  other: [
    { key: 'price', label: 'Cena', required: true },
    { key: 'type', label: 'Typ powierzchni', required: true },
    { key: 'location', label: 'Lokalizacja', required: true },
    { key: 'environment', label: 'Środowisko', required: false },
    { key: 'status', label: 'Status', required: true },
    { key: 'offer_type', label: 'Rodzaj oferty', required: true },
    { key: 'has_vat_invoice', label: 'Faktura VAT', required: true }
  ]
}

// Funkcja zwracająca pola dla danego typu
export function getFieldsForType(type: string): ComparisonField[] {
  return typeFieldsConfig[type] || commonFields
}

// Funkcja sprawdzająca czy pole powinno być wyświetlone
export function shouldShowField(field: ComparisonField, ads: any[]): boolean {
  // Zawsze pokazuj wymagane pola
  if (field.required) {
    return true
  }
  
  // Dla opcjonalnych pól, sprawdź czy którekolwiek ogłoszenie ma wypełnioną wartość
  return ads.some(ad => {
    const value = getFieldValue(field.key, ad)
    return value !== null && value !== undefined && value !== '' && value !== false
  })
}

// Funkcja pomocnicza do pobierania wartości pola
function getFieldValue(key: string, ad: any): any {
  switch (key) {
    case 'price_per_sqm':
      const area = ad.width && ad.height ? ad.width * ad.height : 0
      return area > 0 ? ad.price / area : null
    case 'surface_area':
      return ad.width && ad.height ? ad.width * ad.height : null
    case 'dimensions':
      return ad.width && ad.height ? `${ad.width}m × ${ad.height}m` : null
    case 'location_tier':
      // Oblicz klasę lokalizacji dla billboardu
      if (ad.type !== 'billboard') return null
      const trafficIntensity = ad.traffic_intensity
      const roadClass = ad.road_class
      if (trafficIntensity === 'high' && ['highway', 'expressway', 'national'].includes(roadClass || '')) {
        return 'PREMIUM'
      }
      return 'STANDARD'
    default:
      return ad[key]
  }
}
