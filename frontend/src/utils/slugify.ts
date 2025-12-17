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
 * Mapa polskich miast ze slugów na poprawne nazwy z polskimi znakami
 */
const cityMap: Record<string, string> = {
    'warszawa': 'Warszawa',
    'krakow': 'Kraków',
    'wroclaw': 'Wrocław',
    'poznan': 'Poznań',
    'gdansk': 'Gdańsk',
    'lodz': 'Łódź',
    'katowice': 'Katowice',
    'szczecin': 'Szczecin',
    'bydgoszcz': 'Bydgoszcz',
    'lublin': 'Lublin',
    'bialystok': 'Białystok',
    'gdynia': 'Gdynia',
    'czestochowa': 'Częstochowa',
    'radom': 'Radom',
    'sosnowiec': 'Sosnowiec',
    'torun': 'Toruń',
    'kielce': 'Kielce',
    'gliwice': 'Gliwice',
    'zabrze': 'Zabrze',
    'bytom': 'Bytom',
    'olsztyn': 'Olsztyn',
    'bielsko-biala': 'Bielsko-Biała',
    'rzeszow': 'Rzeszów',
    'ruda-slaska': 'Ruda Śląska',
    'rybnik': 'Rybnik',
    'tychy': 'Tychy',
    'dabrowa-gornicza': 'Dąbrowa Górnicza',
    'plock': 'Płock',
    'elblag': 'Elbląg',
    'opole': 'Opole',
    'gorzow-wielkopolski': 'Gorzów Wielkopolski',
    'walbrzych': 'Wałbrzych',
    'zielona-gora': 'Zielona Góra',
    'tarnow': 'Tarnów',
    'chorzow': 'Chorzów',
    'koszalin': 'Koszalin',
    'kalisz': 'Kalisz',
    'legnica': 'Legnica',
    'grudziadz': 'Grudziądz',
    'slupsk': 'Słupsk',
    'jaworzno': 'Jaworzno',
    'jastrzebie-zdroj': 'Jastrzębie-Zdrój',
    'nowy-sacz': 'Nowy Sącz',
    'jelenia-gora': 'Jelenia Góra',
    'siedlce': 'Siedlce',
    'mysłowice': 'Mysłowice',
    'konin': 'Konin',
    'piotrkow-trybunalski': 'Piotrków Trybunalski',
    'inowroclaw': 'Inowrocław',
    'lubin': 'Lubin',
    'ostrow-wielkopolski': 'Ostrów Wielkopolski',
    'suwalki': 'Suwałki',
    'stargard': 'Stargard',
    'gniezno': 'Gniezno',
    'glogow': 'Głogów',
    'chelm': 'Chełm',
    'tomaszow-mazowiecki': 'Tomaszów Mazowiecki',
    'przemysl': 'Przemyśl',
    'stalowa-wola': 'Stalowa Wola',
    'zamosc': 'Zamość',
    'kedzierzyn-kozle': 'Kędzierzyn-Koźle',
    'lomza': 'Łomża',
    'leszno': 'Leszno',
    'belchatow': 'Bełchatów',
    'pabianice': 'Pabianice',
    'skierniewice': 'Skierniewice'
}

/**
 * Konwertuje slug (np. 'wroclaw') na nazwę z kapitalizacją (np. 'Wrocław')
 * Używa mapy miast dla polskich znaków, w przeciwnym razie kapitalizuje pierwszą literę
 */
export const deslugify = (slug: string): string => {
    // Sprawdź czy to znane miasto
    const lowerSlug = slug.toLowerCase()
    if (cityMap[lowerSlug]) {
        return cityMap[lowerSlug]
    }
    
    // W przeciwnym razie kapitalizuj pierwszą literę każdego słowa
    return slug
        .split('-')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')
}
