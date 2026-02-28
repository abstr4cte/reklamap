import axios from 'axios';
import { API_URL } from '../config';

// Set the base URL for all axios requests
// Extract the base URL from API_URL (remove '/api' at the end)
const baseURL = API_URL.replace(/\/api$/, '');
axios.defaults.baseURL = baseURL;
axios.defaults.headers.common['X-App-Key'] = import.meta.env.VITE_INTERNAL_APP_KEY as string;

// Automatycznie dodawaj X-Management-Token do wrażliwych operacji
const SENSITIVE_METHODS = ['put', 'delete', 'patch'];

axios.interceptors.request.use((config) => {
    const method = (config.method || '').toLowerCase();
    const url = config.url || '';

    // Dodaj token do wrażliwych metod lub uploadu
    if (SENSITIVE_METHODS.includes(method) || url.includes('/upload')) {
        const token = sessionStorage.getItem('management_token');
        if (token) {
            config.headers['X-Management-Token'] = token;
        }
    }

    return config;
});

/**
 * Ustaw aktywny token zarządzający (wywoływane przez ManagementPage po weryfikacji)
 */
export function setManagementToken(token: string | null) {
    if (token) {
        sessionStorage.setItem('management_token', token);
    } else {
        sessionStorage.removeItem('management_token');
    }
}

export default axios;
