export const slugify = (text: string): string => {
    // Mapa polskich znaków na ich odpowiedniki ASCII
    const polishChars: Record<string, string> = {
        'ą': 'a', 'Ą': 'a',
        'ć': 'c', 'Ć': 'c',
        'ę': 'e', 'Ę': 'e',
        'ł': 'l', 'Ł': 'l',
        'ń': 'n', 'Ń': 'n',
        'ó': 'o', 'Ó': 'o',
        'ś': 's', 'Ś': 's',
        'ź': 'z', 'Ź': 'z',
        'ż': 'z', 'Ż': 'z'
    }
    
    return text
        .toString()
        .toLowerCase()
        // Zamień polskie znaki na ASCII
        .split('')
        .map(char => polishChars[char] || char)
        .join('')
        .normalize('NFD') // Normalize to decompose combined characters
        .replace(/[\u0300-\u036f]/g, '') // Remove diacritics
        .replace(/\s+/g, '-') // Replace spaces with -
        .replace(/[^\w\-]+/g, '') // Remove all non-word chars
        .replace(/\-\-+/g, '-') // Replace multiple - with single -
        .replace(/^-+/, '') // Trim - from start of text
        .replace(/-+$/, '') // Trim - from end of text
}

/**
 * Konwertuje slug (np. 'wroclaw') na nazwę z kapitalizacją (np. 'Wrocław')
 * Używa prostej kapitalizacji pierwszej litery każdego słowa
 */
export const deslugify = (slug: string): string => {
    return slug
        .split('-')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')
}
