/**
 * Mapuje typ ogłoszenia z formatu bazy danych na format URL
 * @param type Typ ogłoszenia z bazy danych (np. 'billboard', 'citylight')
 * @returns Typ w formacie URL (np. 'billboardy', 'citylighty')
 */
export function mapTypeToUrlFormat(type: string): string {
  const typeMapping: Record<string, string> = {
    'billboard': 'billboardy',
    'citylight': 'citylighty',
    'led_screen': 'ekrany-led',
    'banner': 'banery',
    'wall': 'sciany-reklamowe',
    'totem': 'totemy-reklamowe',
    'transport': 'reklama-w-transporcie',
    'mobile': 'reklama-mobilna',
    'other': 'inne'
  }
  return typeMapping[type] || 'inne'
}
