/**
 * useApi - Composable para llamadas fetch a la API.
 * La autenticación se maneja via cookie HttpOnly — el browser la envía
 * automáticamente en cada request a la misma origin. No se lee ni guarda
 * ningún token en JS.
 */
import { ref } from 'vue'

export function useApi() {
  const loading = ref(false)
  const error = ref(null)

  async function request(method, route, body = null, params = {}) {
    loading.value = true
    error.value = null

    try {
      const queryParams = { route, ...params }
      const url = import.meta.env.BASE_URL + 'api/?' + new URLSearchParams(queryParams)
      const headers = { 'Content-Type': 'application/json' }
      const opts = { method, headers, credentials: 'include', cache: 'no-store' }
      if (body != null) opts.body = JSON.stringify(body)

      const res = await fetch(url, opts)
      if (res.status === 401) {
        // Sesión expirada — redirigir al login
        window.location.href = import.meta.env.BASE_URL + 'admin'
        throw new Error('Sesión expirada')
      }
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      loading.value = false
      return data
    } catch (err) {
      error.value = err.message
      loading.value = false
      throw err
    }
  }

  // Nota: el parámetro includeAuth se mantiene por compatibilidad con call sites
  // existentes pero ya no tiene efecto — la cookie se envía automáticamente.
  function get(route, params = {}, includeAuth = true) {
    return request('GET', route, null, params)
  }

  function post(route, body = {}, includeAuth = true) {
    return request('POST', route, body, {})
  }

  function put(route, body = {}, params = {}, includeAuth = true) {
    return request('PUT', route, body, params)
  }

  function del(route, params = {}, includeAuth = true) {
    return request('DELETE', route, null, params)
  }

  return { loading, error, get, post, put, del }
}
