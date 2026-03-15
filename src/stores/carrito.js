import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useCarritoStore = defineStore('carrito', () => {
  const items = ref([])

  const agregar = (producto, observacion = '') => {
    const existente = items.value.find(i => i.producto.id === producto.id && i.observacion === observacion)
    if (existente) {
      existente.cantidad++
    } else {
      items.value.push({ producto, cantidad: 1, observacion })
    }
  }

  const vaciar = () => {
    items.value = []
  }

  const total = () => items.value.reduce((sum, i) => sum + i.producto.precio * i.cantidad, 0)

  return { items, agregar, vaciar, total }
}, {
  persist: true, // pinia-plugin-persistedstate → localStorage
})
