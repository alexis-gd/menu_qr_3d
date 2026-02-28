/**
 * useApi - Composable para llamadas fetch a la API
 */
import { ref } from 'vue'

export function useApi() {
  const loading = ref(false)
  const error = ref(null)

  /**
   * Realiza un GET a /api/?route=...
   * @param {string} route - Nombre de la ruta sin /api/?route=
   * @param {object} params - Parámetros adicionales para la query string
   * @returns {Promise}
   */
  async function get(route, params = {}) {
    loading.value = true
    error.value = null

    try {
      const queryParams = new URLSearchParams({ route, ...params })
      const response = await fetch(`/api/?${queryParams}`)

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`)
      }

      const data = await response.json()
      loading.value = false
      return data
    } catch (err) {
      error.value = err.message
      loading.value = false
      throw err
    }
  }

  return {
    loading,
    error,
    get
  }
}
