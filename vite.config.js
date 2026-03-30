import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'
import { version } from './package.json'

export default defineConfig({
  define: {
    __APP_VERSION__: JSON.stringify(version),
  },
  base: '/menu/',
  plugins: [
    vue(),
    VitePWA({
      strategies: 'injectManifest',
      srcDir: 'src',
      filename: 'sw.js',
      injectRegister: 'inline',
      manifest: {
        name: 'Menú Digital 3D',
        short_name: 'Menú Admin',
        description: 'Panel de administración del menú digital',
        display: 'standalone',
        background_color: '#ffffff',
        theme_color: '#e8631a',
        start_url: '/menu/admin/dashboard',
        scope: '/menu/',
        icons: [
          {
            src: '/menu/pwa-icon.svg',
            sizes: 'any',
            type: 'image/svg+xml',
            purpose: 'any',
          },
        ],
      },
      injectManifest: {
        // No precachear — solo necesitamos el SW para push
        globPatterns: [],
      },
      devOptions: {
        enabled: false, // Activar en true solo para depurar el SW en dev
      },
    }),
  ],
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
