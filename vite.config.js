import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { version } from './package.json'

export default defineConfig({
  define: {
    __APP_VERSION__: JSON.stringify(version),
  },
  base: '/menu/',
  plugins: [vue()],
  server: {
    port: 5173,
    proxy: {
      '/menu/api': {
        target: 'http://menu.local',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/menu/, '')
      },
      '/menu/uploads': {
        target: 'http://menu.local',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/menu/, '')
      }
    }
  }
})
