/**
 * Format price for display: removes decimals and adds thousand separators.
 * Example: 9500.00 → "9 500", 12000 → "12 000"
 */
export function formatPrice(price: number | string): string {
  const num = typeof price === 'string' ? parseFloat(price) : price
  if (isNaN(num)) return '0'
  return Math.round(num).toLocaleString('pl-PL')
}

/**
 * Format dimension value: strips trailing zeros.
 * Example: 5.00 → "5", 5.50 → "5.5", 5.25 → "5.25"
 * Handles both number and string (Laravel decimal:2 cast returns "5.00" as string).
 */
export function formatDim(val: number | string): string {
  const n = parseFloat(String(val))
  if (isNaN(n)) return String(val)
  return parseFloat(n.toFixed(10)).toString()
}
