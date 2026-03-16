import { defineStore } from 'pinia'
import { ref } from 'vue'

// Variable de módulo — no persiste en localStorage, se resetea en cada recarga
let _avisosMostrados = new Set()

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
    _avisosMostrados = new Set() // resetear al vaciar el carrito
  }

  // precio_unitario con fallback para items migrados de localStorage (formato anterior sin opciones)
  const total = () => items.value.reduce(
    (sum, i) => sum + (i.precio_unitario ?? Number(i.producto.precio)) * i.cantidad,
    0
  )

  const tieneCategoriaEnCarrito = (catId) =>
    items.value.some(i => i.producto.categoria_id === catId)

  const marcarAvisoMostrado = (catId) => { _avisosMostrados.add(catId) }

  const avisoYaMostrado = (catId) => _avisosMostrados.has(catId)

  return { items, agregar, vaciar, total, tieneCategoriaEnCarrito, marcarAvisoMostrado, avisoYaMostrado }
}, {
  persist: { paths: ['items'] },
})
