import { createRouter, createWebHistory } from 'vue-router'
import MenuPublico from '../views/MenuPublico.vue'
import AdminLogin from '../views/admin/Login.vue'
import AdminDashboard from '../views/admin/Dashboard.vue'
import AdminRestaurantes from '../views/admin/Restaurantes.vue'
import AdminProductos from '../views/admin/Productos.vue'
import AdminMesas from '../views/admin/Mesas.vue'

const routes = [
  {
    path: '/',
    name: 'MenuPublico',
    component: MenuPublico
  },
  {
    path: '/admin',
    name: 'AdminLogin',
    component: AdminLogin
  },
  {
    path: '/admin/dashboard',
    name: 'AdminDashboard',
    component: AdminDashboard
  },
  {
    path: '/admin/restaurantes',
    name: 'AdminRestaurantes',
    component: AdminRestaurantes
  },
  {
    path: '/admin/restaurantes/:id/productos',
    name: 'AdminProductos',
    component: AdminProductos
  },
  {
    path: '/admin/restaurantes/:id/mesas',
    name: 'AdminMesas',
    component: AdminMesas
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// Protección de rutas admin
router.beforeEach((to, _from, next) => {
  const token = localStorage.getItem('admin_token')
  if (to.path.startsWith('/admin') && to.path !== '/admin') {
    if (!token) {
      return next({ path: '/admin' })
    }
  }
  if (to.path === '/admin' && token) {
    return next({ path: '/admin/dashboard' })
  }
  next()
})

export default router
