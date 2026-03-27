/**
 * Format price for display: removes decimals and adds thousand separators.
 * Example: 9500.00 → "9 500", 12000 → "12 000"
 */
export function formatPrice(price: number | string): string {
  const num = typeof price === 'string' ? parseFloat(price) : price
  if (isNaN(num)) return '0'
  return Math.round(num).toLocaleString('pl-PL')
}
