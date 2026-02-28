<template>
  <div class="admin-productos">
    <h1>Productos del restaurante</h1>

    <section class="categorias-section">
      <h2>Categorías</h2>
      <div class="nuevo-categoria">
        <input v-model="nuevaCategoria" placeholder="Nombre de categoría" />
        <button @click="crearCategoria">Crear</button>
      </div>
      <ul class="lista-categorias">
        <li v-for="cat in categorias" :key="cat.id">
          {{ cat.nombre }}
        </li>
      </ul>
    </section>

    <section class="nuevo-producto">
      <h2>Nuevo producto</h2>
      <select v-model="form.categoria_id">
        <option value="" disabled>Selecciona categoría</option>
        <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
      </select>
      <input v-model="form.nombre" placeholder="Nombre" />
      <input v-model="form.precio" type="number" placeholder="Precio" />
      <textarea v-model="form.descripcion" placeholder="Descripción"></textarea>
      <button @click="crearProducto">Crear producto</button>
    </section>

    <section class="lista-productos">
      <h2>Productos existentes</h2>
      <div v-if="loading" class="cargando">Cargando...</div>
      <div v-if="error" class="error">{{ error }}</div>
      <table v-if="!loading && productos.length">
        <thead>
          <tr><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Acciones</th></tr>
        </thead>
        <tbody>
          <tr v-for="prod in productos" :key="prod.id">
            <td>{{ prod.nombre }}</td>
            <td>{{ catMap[prod.categoria_id] || '' }}</td>
            <td>${{ prod.precio.toFixed(2) }}</td>
            <td>
              <button @click="eliminar(prod.id)">Eliminar</button>
              <input type="file" multiple @change="subirFotos(prod.id,$event)" />
            </td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '../../composables/useApi.js'
import { useRoute } from 'vue-router'

const route = useRoute()
const restauranteId = route.params.id

const categorias = ref([])
const productos = ref([])
const catMap = ref({})
const nuevaCategoria = ref('')
const form = ref({ categoria_id: '', nombre: '', precio: 0, descripcion: '' })
const error = ref(null)
const loading = ref(false)
const { get, post, del } = useApi()

async function loadCategorias() {
  try {
    const res = await get('categorias', { restaurante_id: restauranteId })
    categorias.value = res.categorias || []
    catMap.value = {}
    categorias.value.forEach(c => { catMap.value[c.id] = c.nombre })
  } catch (err) {
    error.value = err.message
  }
}

async function loadProductos() {
  loading.value = true
  try {
    const res = await get('productos', { restaurante_id: restauranteId })
    productos.value = res.productos || []
  } catch (err) {
    error.value = err.message
  } finally {
    loading.value = false
  }
}

async function crearCategoria() {
  if (!nuevaCategoria.value) return
  try {
    await post('categorias', { restaurante_id: restauranteId, nombre: nuevaCategoria.value })
    nuevaCategoria.value = ''
    await loadCategorias()
  } catch (err) {
    error.value = err.message
  }
}

async function crearProducto() {
  if (!form.value.categoria_id || !form.value.nombre) {
    error.value = 'Categoria y nombre requeridos'
    return
  }
  try {
    await post('productos', { ...form.value })
    form.value = { categoria_id: '', nombre: '', precio: 0, descripcion: '' }
    await loadProductos()
  } catch (err) {
    error.value = err.message
  }
}

async function eliminar(id) {
  try {
    await del('productos', { id })
    await loadProductos()
  } catch (err) {
    error.value = err.message
  }
}

async function subirFotos(prodId, event) {
  const files = event.target.files
  if (!files.length) return
  const formData = new FormData()
  formData.append('producto_id', prodId)
  for (let i = 0; i < files.length; i++) {
    formData.append('fotos[]', files[i])
  }
  try {
    const token = localStorage.getItem('admin_token')
    const res = await fetch(`/api/?route=upload-fotos`, {
      method: 'POST',
      headers: { Authorization: 'Bearer ' + token },
      body: formData
    })
    if (!res.ok) throw new Error('Error upload')
    await loadProductos()
  } catch (err) {
    error.value = err.message
  }
}

onMounted(async () => {
  await loadCategorias()
  await loadProductos()
})
</script>

<style scoped>
.admin-productos { max-width: 900px; margin: 20px auto; padding: 20px }
.categorias-section, .nuevo-producto, .lista-productos { margin-bottom: 24px }
.nuevo-categoria input { padding:6px; width:200px }
.nuevo-categoria button { padding:6px 12px; margin-left:8px }
table { width:100%; border-collapse:collapse }
th,td { text-align:left; padding:8px; border-bottom:1px solid #ddd }
.error { color:#d32f2f; margin-top:12px }
.cargando { font-style:italic }
</style>