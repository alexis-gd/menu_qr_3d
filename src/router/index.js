import { createRouter, createWebHistory } from 'vue-router'
import MenuPublico from '../views/MenuPublico.vue'
import AdminLogin from '../views/admin/Login.vue'
import AdminRestaurantes from '../views/admin/Restaurantes.vue'

const routes = [
  {
    path: '/',
    name: 'MenuPublico',
    component: MenuPublico
  }
  ,
  {
    path: '/admin',
    name: 'AdminLogin',
    component: AdminLogin
  },
  {
    path: '/admin/restaurantes',
    name: 'AdminRestaurantes',
    component: AdminRestaurantes
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

export default router
