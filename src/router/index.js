import { createRouter, createWebHistory } from 'vue-router'
import MenuPublico from '../views/MenuPublico.vue'
import AdminLogin from '../views/admin/Login.vue'
import AdminRestaurantes from '../views/admin/Restaurantes.vue'
import AdminProductos from '../views/admin/Productos.vue'

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
  },
  {
    path: '/admin/restaurantes/:id/productos',
    name: 'AdminProductos',
    component: AdminProductos
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// Protección de rutas admin
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('admin_token')
  // si no hay token y la ruta comienza con /admin (excepto login), redirige a login
  if (to.path.startsWith('/admin') && to.path !== '/admin') {
    if (!token) {
      return next({ path: '/admin' })
    }
  }
  // si hay token y va a login, redirige al dashboard restaurantes
  if (to.path === '/admin' && token) {
    return next({ path: '/admin/restaurantes' })
  }
  next()
})

export default router
