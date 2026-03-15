import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useCarritoStore = defineStore('carrito', () => {
  const items = ref([])

  /**
   * @param {Object} producto
   * @param {string} observacion
   * @param {Array}  opciones  — [{ grupo_id, grupo_nombre, opcion_id, opcion_nombre, precio_extra }]
   */
  const agregar = (producto, observacion = '', opciones = []) => {
    const extraTotal = opciones.reduce((sum, o) => sum + (Number(o.precio_extra) || 0), 0)
    const precio_unitario = Number(producto.precio) + extraTotal

    // Solo deduplicar si no hay opciones (productos simples)
    if (!opciones.length) {
      const existente = items.value.find(
        i => i.producto.id === producto.id &&
             i.observacion === observacion &&
             !(i.opciones?.length)
      )
      if (existente) {
        existente.cantidad++
        return
      }
    }

    items.value.push({ producto, cantidad: 1, observacion, opciones, precio_unitario })
  }

  const vaciar = () => {
    items.value = []
  }

  // precio_unitario con fallback para items migrados de localStorage (formato anterior sin opciones)
  const total = () => items.value.reduce(
    (sum, i) => sum + (i.precio_unitario ?? Number(i.producto.precio)) * i.cantidad,
    0
  )

  return { items, agregar, vaciar, total }
}, {
  persist: true, // pinia-plugin-persistedstate → localStorage
})
