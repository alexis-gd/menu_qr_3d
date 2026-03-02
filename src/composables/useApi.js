/**
 * useApi - Composable para llamadas fetch a la API
 */
import { ref } from 'vue'

function authHeader() {
  const token = localStorage.getItem('admin_token')
  return token ? { Authorization: 'Bearer ' + token } : {}
}

export function useApi() {
  const loading = ref(false)
  const error = ref(null)

  async function request(method, route, body = null, params = {}, includeAuth = true) {
    loading.value = true
    error.value = null

    try {
      // Si requiere auth, agrega el token como parámetro query (temporal workaround)
      const queryParams = { route, ...params }
      if (includeAuth) {
        const token = localStorage.getItem('admin_token')
        if (token) {
          queryParams.token = token
        }
      }
      
      const url = import.meta.env.BASE_URL + 'api/?' + new URLSearchParams(queryParams)
      const headers = Object.assign({ 'Content-Type': 'application/json' }, includeAuth ? authHeader() : {})
      const opts = { method, headers }
      if (body != null) opts.body = JSON.stringify(body)

      const res = await fetch(url, opts)
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

  function get(route, params = {}, includeAuth = true) {
    return request('GET', route, null, params, includeAuth)
  }

  function post(route, body = {}, includeAuth = true) {
    return request('POST', route, body, {}, includeAuth)
  }

  function put(route, body = {}, params = {}, includeAuth = true) {
    return request('PUT', route, body, params, includeAuth)
  }

  function del(route, params = {}, includeAuth = true) {
    return request('DELETE', route, null, params, includeAuth)
  }

  return { loading, error, get, post, put, del }
}
