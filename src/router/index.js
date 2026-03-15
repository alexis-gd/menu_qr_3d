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

// Estado de autenticación — se cachea para no llamar la API en cada navegación interna.
// null = sin verificar (primera carga o después de logout), true/false = verificado.
let authenticated = null

async function checkAuth() {
  if (authenticated !== null) return authenticated
  try {
    const res = await fetch(import.meta.env.BASE_URL + 'api/?route=auth-check', {
      credentials: 'include'
    })
    authenticated = res.ok
  } catch {
    authenticated = false
  }
  return authenticated
}

// Exportado para que Dashboard.vue lo llame al hacer logout.
export function resetAuth() {
  authenticated = null
}

// Protección de rutas admin
router.beforeEach(async (to) => {
  const isAdminProtected = to.path.startsWith('/admin') && to.path !== '/admin'
  const isLoginPage     = to.path === '/admin'

  if (isAdminProtected) {
    const ok = await checkAuth()
    if (!ok) return { path: '/admin' }
  }

  if (isLoginPage) {
    const ok = await checkAuth()
    if (ok) return { path: '/admin/dashboard' }
  }
})

export default router
