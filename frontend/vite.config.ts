import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  build: {
    // Disable sourcemaps in production to save memory
    sourcemap: false,
    // Use esbuild (faster and less memory than terser)
    minify: 'esbuild',
    // Increase chunk size warning limit
    chunkSizeWarningLimit: 1500,
    // Target modern browsers to reduce polyfills
    target: 'es2020',
    rollupOptions: {
      external: ['@tensorflow/tfjs', 'nsfwjs'],
      maxParallelFileOps: 2,
      output: {
        globals: {
          '@tensorflow/tfjs': 'tf',
          'nsfwjs': 'nsfwjs'
        },
        // Hash to chunk filenames for cache busting
        chunkFileNames: 'js/[name]-[hash].js',
        entryFileNames: 'js/[name]-[hash].js',
        manualChunks: (id) => {
          // Split large vendors into separate chunks to reduce memory pressure
          if (id.includes('node_modules')) {
            if (id.includes('tensorflow') || id.includes('tfjs')) {
              return 'tensorflow-vendor'
            }
            if (id.includes('nsfwjs')) {
              return 'nsfwjs-vendor'
            }
            if (id.includes('leaflet')) {
              return 'leaflet-vendor'
            }
            if (id.includes('chart.js') || id.includes('vue-chartjs')) {
              return 'chart-vendor'
            }
            if (id.includes('vue-router')) {
              return 'vue-router-vendor'
            }
            if (id.includes('pinia')) {
              return 'pinia-vendor'
            }
            if (id.includes('axios')) {
              return 'axios-vendor'
            }
            if (id.includes('vue-datepicker')) {
              return 'datepicker-vendor'
            }
            // All other node_modules
            return 'vendor'
          }
        },
        assetFileNames: (assetInfo) => {
          const name = assetInfo.name || ''
          const info = name.split('.')
          const ext = info[info.length - 1]
          if (/png|jpe?g|gif|tiff|bmp|ico/i.test(ext)) {
            return `images/[name]-[hash][extname]`
          } else if (/woff|woff2|ttf|otf|eot/i.test(ext)) {
            return `fonts/[name]-[hash][extname]`
          } else if (ext === 'css') {
            return `css/[name]-[hash][extname]`
          }
          return `assets/[name]-[hash][extname]`
        }
      }
    }
  },
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
      '/storage': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      }
    }
  }
})
