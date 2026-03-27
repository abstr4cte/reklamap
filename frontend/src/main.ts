import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'
import './assets/custom-inputs.css'
import App from './App.vue'
import router from './router'

// Import axios configuration
import './api/axios'

const pinia = createPinia()
const app = createApp(App)

app.use(pinia)
app.use(router)
app.mount('#app')
