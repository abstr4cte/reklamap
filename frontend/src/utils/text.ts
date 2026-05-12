/**
 * Przycina tekst do maksymalnej długości, ale na granicy słowa — nigdy w połowie wyrazu.
 * Używane do generowania <meta name="description"> z dłuższych opisów.
 *
 * @param text   Tekst źródłowy
 * @param maxLen Maksymalna długość wyniku (bez wielokropka)
 * @param ellipsis Sufiks dołączany gdy tekst został przycięty (domyślnie '…')
 */
export function truncateAtWord(text: string, maxLen: number, ellipsis = '…'): string {
  const trimmed = text.trim()
  if (trimmed.length <= maxLen) return trimmed

  const slice = trimmed.slice(0, maxLen)
  const lastSpace = slice.lastIndexOf(' ')
  // Jeśli ostatnia spacja jest sensownie daleko od początku, tnij na niej;
  // w przeciwnym razie (np. jeden bardzo długi wyraz) tnij twardo.
  const base = lastSpace > maxLen * 0.6 ? slice.slice(0, lastSpace) : slice
  // Usuń końcowe znaki interpunkcyjne, żeby nie wyszło "tekst,…"
  return base.replace(/[\s.,;:–-]+$/u, '') + ellipsis
}
