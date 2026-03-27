import { createApp } from 'vue'
import { createPinia } from 'pinia'
import piniaPersistedstate from 'pinia-plugin-persistedstate'
import App from './App.vue'
import router from './router/index.js'
import '@vuepic/vue-datepicker/dist/main.css'
import './assets/base.css'
import './assets/theme.css'
import './assets/admin.css'
import './assets/datepicker-theme.css'

const pinia = createPinia()
pinia.use(piniaPersistedstate)

const app = createApp(App)
app.use(pinia)
app.use(router)
app.mount('#app')
