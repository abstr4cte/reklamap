import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router'

// Import axios configuration
import './api/axios'

createApp(App).use(router).mount('#app')
