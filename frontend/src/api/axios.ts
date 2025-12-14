import axios from 'axios';
import { API_URL } from '../config';

// Set the base URL for all axios requests
// Extract the base URL from API_URL (remove '/api' at the end)
const baseURL = API_URL.replace(/\/api$/, '');
axios.defaults.baseURL = baseURL;



export default axios;
