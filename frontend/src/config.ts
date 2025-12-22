// Konfiguracja aplikacji
const isDev = import.meta.env.DEV
export const API_URL = isDev ? '/api' : 'https://api.reklamap.pl/api'
export const STORAGE_URL = isDev ? '/storage' : 'https://api.reklamap.pl/storage'