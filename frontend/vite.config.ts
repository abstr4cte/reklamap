import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      '/api': {
        target: 'https://api.reklamap.pl',
        changeOrigin: true,
      },
      '/storage': {
        target: 'https://api.reklamap.pl',
        changeOrigin: true,
      }
    }
  }
})
