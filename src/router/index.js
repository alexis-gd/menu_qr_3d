import { createRouter, createWebHistory } from 'vue-router'
import MenuPublico from '../views/MenuPublico.vue'

const routes = [
  {
    path: '/',
    name: 'MenuPublico',
    component: MenuPublico
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

export default router
