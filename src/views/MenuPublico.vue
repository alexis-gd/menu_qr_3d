<template>
  <div class="menu-publico">
    <header class="header">
      <h1>{{ restaurante?.nombre || 'Cargando...' }}</h1>
      <p v-if="restaurante?.descripcion" class="descripcion">{{ restaurante.descripcion }}</p>
    </header>

    <div v-if="loading" class="loading">
      <p>Cargando menú...</p>
    </div>

    <div v-else-if="error" class="error">
      <p>Error al cargar el menú: {{ error }}</p>
    </div>

    <div v-else class="contenedor-categorias">
      <div v-for="categoria in categorias" :key="categoria.id" class="categoria-section">
        <h2 class="categoria-titulo">{{ categoria.nombre }}</h2>
        <div class="productos-grid">
          <ProductoCard
            v-for="producto in categoria.productos"
            :key="producto.id"
            :producto="producto"
            @click="abrirModal(producto)"
          />
        </div>
      </div>
    </div>

    <ProductoModal
      v-if="productoSeleccionado"
      :producto="productoSeleccionado"
      @close="productoSeleccionado = null"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '../composables/useApi.js'
import ProductoCard from '../components/ProductoCard.vue'
import ProductoModal from '../components/ProductoModal.vue'

const { get, loading, error } = useApi()
const restaurante = ref(null)
const categorias = ref([])
const productoSeleccionado = ref(null)

onMounted(async () => {
  try {
    const data = await get('menu', {
      restaurante: 'demo'
    })
    restaurante.value = data.restaurante
    categorias.value = data.categorias || []
  } catch (err) {
    console.error('Error cargando menú:', err)
  }
})

const abrirModal = (producto) => {
  productoSeleccionado.value = producto
}
</script>

<style scoped>
.menu-publico {
  width: 100%;
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 40px;
}

.header {
  background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
  color: white;
  padding: 40px 20px;
  text-align: center;
}

.header h1 {
  font-size: 2rem;
  margin-bottom: 10px;
  font-weight: 700;
}

.header .descripcion {
  font-size: 1rem;
  opacity: 0.95;
  max-width: 500px;
  margin: 0 auto;
}

.loading,
.error {
  text-align: center;
  padding: 40px 20px;
  font-size: 1.1rem;
}

.error {
  color: #d32f2f;
  background: #ffebee;
  border: 1px solid #ef5350;
  border-radius: 8px;
  margin: 20px;
}

.contenedor-categorias {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.categoria-section {
  margin-bottom: 40px;
}

.categoria-titulo {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 20px;
  color: #333;
  border-left: 4px solid #FF6B35;
  padding-left: 12px;
}

.productos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
}

@media (max-width: 600px) {
  .header h1 {
    font-size: 1.5rem;
  }
  .productos-grid {
    grid-template-columns: 1fr;
  }
}
</style>
