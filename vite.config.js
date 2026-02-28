import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  base: '/menu/',
  plugins: [vue()],
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://menu.local',
        changeOrigin: true,
        rewrite: (path) => path
      }
    }
  }
})
