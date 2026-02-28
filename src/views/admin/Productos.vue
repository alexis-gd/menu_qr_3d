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
            <td>${{ Number(prod.precio).toFixed(2) }}</td>
            <td>
              <span v-if="prod.tiene_ar" class="badge-ar">3D listo</span>
              <span v-else class="badge-sin-ar">Sin modelo</span>
            </td>
            <td class="acciones-col">
              <button @click="eliminar(prod.id)" class="btn-danger">Eliminar</button>
              <label class="btn-mini btn-upload" :title="'Subir fotos del platillo'">
                Fotos
                <input type="file" multiple accept="image/*" @change="subirFotos(prod.id, $event)" hidden />
              </label>
              <label v-if="!prod.tiene_ar" class="btn-mini btn-glb" title="Sube el .glb generado en meshy.ai">
                Subir 3D (.glb)
                <input type="file" accept=".glb" @change="subirGlb(prod.id, $event)" hidden />
              </label>
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
    const res = await fetch(`/api/?route=upload-fotos&token=${token}`, {
      method: 'POST',
      body: formData
    })
    if (!res.ok) throw new Error('Error al subir fotos')
    await loadProductos()
  } catch (err) {
    error.value = err.message
  }
}

async function subirGlb(prodId, event) {
  const file = event.target.files[0]
  if (!file) return
  const formData = new FormData()
  formData.append('producto_id', prodId)
  formData.append('modelo', file)
  try {
    const token = localStorage.getItem('admin_token')
    const res = await fetch(`/api/?route=upload-glb&token=${token}`, {
      method: 'POST',
      body: formData
    })
    const data = await res.json()
    if (!res.ok) throw new Error(data.error || 'Error al subir el modelo')
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
.badge-ar { background:#4caf50; color:white; padding:2px 6px; border-radius:4px; font-size:0.8rem; }
.badge-sin-ar { background:#bdbdbd; color:white; padding:2px 6px; border-radius:4px; font-size:0.8rem; }
.acciones-col { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
.btn-danger { background:#e53935; color:white; border:none; padding:4px 8px; border-radius:4px; font-size:0.8rem; cursor:pointer; }
.btn-mini { border:none; padding:4px 8px; border-radius:4px; font-size:0.8rem; cursor:pointer; }
.btn-upload { background:#2196f3; color:white; }
.btn-glb { background:#7b1fa2; color:white; }
</style>