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
          <tr>
            <th>Nombre</th>
            <th>Categoría</th>
            <th>Precio</th>
            <th>3D</th>
            <th>Disponible</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="prod in productos" :key="prod.id">
            <tr :class="{ 'fila-inactiva': !prod.disponible }">
              <td>{{ prod.nombre }}</td>
              <td>{{ catMap[prod.categoria_id] || '' }}</td>
              <td>${{ Number(prod.precio).toFixed(2) }}</td>
              <td>
                <span v-if="prod.tiene_ar" class="badge-ar">3D listo</span>
                <span v-else class="badge-sin-ar">Sin modelo</span>
              </td>
              <td>
                <input
                  type="checkbox"
                  class="toggle-disponible"
                  :checked="prod.disponible"
                  :title="prod.disponible ? 'Producto activo — click para desactivar' : 'Producto inactivo — click para activar'"
                  @change="toggleDisponible(prod)"
                />
              </td>
              <td class="acciones-col">
                <button @click="eliminar(prod.id)" class="btn-danger">Eliminar</button>
                <button @click="iniciarEdicion(prod)" class="btn-mini btn-edit">
                  {{ editando === prod.id ? 'Cerrar' : 'Editar' }}
                </button>
                <label class="btn-mini btn-upload" title="Subir fotos del platillo">
                  Fotos
                  <input type="file" multiple accept="image/*" @change="subirFotos(prod.id, $event)" hidden />
                </label>
                <label v-if="!prod.tiene_ar" class="btn-mini btn-glb" title="Sube el .glb generado en meshy.ai">
                  Subir 3D (.glb)
                  <input type="file" accept=".glb" @change="subirGlb(prod.id, $event)" hidden />
                </label>
              </td>
            </tr>

            <!-- Fila de edición expandida -->
            <tr v-if="editando === prod.id" class="fila-edicion">
              <td colspan="6">
                <div class="edicion-inner">
                  <div class="edicion-campos">
                    <div class="edicion-campo">
                      <label>Nombre</label>
                      <input v-model="editForm.nombre" />
                    </div>
                    <div class="edicion-campo">
                      <label>Precio</label>
                      <input v-model.number="editForm.precio" type="number" min="0" step="0.5" />
                    </div>
                    <div class="edicion-campo">
                      <label>Stock</label>
                      <div v-if="editForm.stock === null" class="stock-inactivo">
                        <span class="stock-hint">Sin control</span>
                        <button class="btn-mini btn-activar-stock" @click="editForm.stock = 0">Activar</button>
                      </div>
                      <div v-else class="stock-ctrl">
                        <button class="stock-btn" @click="editForm.stock = Math.max(0, editForm.stock - 1)">−</button>
                        <span class="stock-num">{{ editForm.stock }}</span>
                        <button class="stock-btn" @click="editForm.stock++">+</button>
                      </div>
                    </div>
                  </div>
                  <div class="edicion-acciones">
                    <button class="btn-mini btn-guardar" @click="guardarEdicion">Guardar</button>
                    <button class="btn-mini btn-cancelar" @click="editando = null">Cancelar</button>
                  </div>
                </div>
              </td>
            </tr>
          </template>
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
const { get, post, put, del } = useApi()

// Estado de edición inline
const editando = ref(null)   // id del producto siendo editado
const editForm = ref({})

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

async function toggleDisponible(prod) {
  const nuevoValor = !prod.disponible
  try {
    await put('productos', { disponible: nuevoValor ? 1 : 0 }, { id: prod.id })
    prod.disponible = nuevoValor
  } catch (err) {
    error.value = err.message
  }
}

function iniciarEdicion(prod) {
  if (editando.value === prod.id) {
    editando.value = null
    return
  }
  editando.value = prod.id
  editForm.value = {
    nombre: prod.nombre,
    precio: prod.precio,
    stock: prod.stock ?? null,
  }
}

async function guardarEdicion() {
  if (!editando.value) return
  try {
    await put('productos', {
      nombre: editForm.value.nombre,
      precio: editForm.value.precio,
      stock: editForm.value.stock,
    }, { id: editando.value })
    editando.value = null
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
.admin-productos { max-width: 960px; margin: 20px auto; padding: 20px }
.categorias-section, .nuevo-producto, .lista-productos { margin-bottom: 24px }
.nuevo-categoria input { padding:6px; width:200px }
.nuevo-categoria button { padding:6px 12px; margin-left:8px }
table { width:100%; border-collapse:collapse }
th,td { text-align:left; padding:8px; border-bottom:1px solid #ddd; vertical-align:middle }
.error { color:#d32f2f; margin-top:12px }
.cargando { font-style:italic }

/* Fila inactiva */
.fila-inactiva td:not(:nth-child(5)) { opacity: 0.45; }

/* Badges 3D */
.badge-ar { background:#4caf50; color:white; padding:2px 6px; border-radius:4px; font-size:0.8rem; }
.badge-sin-ar { background:#bdbdbd; color:white; padding:2px 6px; border-radius:4px; font-size:0.8rem; }

/* Toggle disponible */
.toggle-disponible { width:16px; height:16px; cursor:pointer; accent-color:#4caf50; }

/* Columna acciones */
.acciones-col { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
.btn-danger { background:#e53935; color:white; border:none; padding:4px 8px; border-radius:4px; font-size:0.8rem; cursor:pointer; }
.btn-mini { border:none; padding:4px 8px; border-radius:4px; font-size:0.8rem; cursor:pointer; }
.btn-upload { background:#2196f3; color:white; }
.btn-glb { background:#7b1fa2; color:white; }
.btn-edit { background:#ff9800; color:white; }

/* Fila de edición */
.fila-edicion td { background:#f9f9f9; padding:12px 16px; }
.edicion-inner { display:flex; align-items:flex-end; gap:20px; flex-wrap:wrap; }
.edicion-campos { display:flex; gap:14px; flex-wrap:wrap; }
.edicion-campo { display:flex; flex-direction:column; gap:4px; }
.edicion-campo label { font-size:0.75rem; font-weight:600; color:#555; }
.edicion-campo input { padding:6px 8px; border:1.5px solid #ccc; border-radius:6px; font-size:0.88rem; outline:none; width:140px; }
.edicion-campo input:focus { border-color:#1976d2; }

/* Stock controls */
.stock-inactivo { display:flex; align-items:center; gap:8px; }
.stock-hint { font-size:0.8rem; color:#aaa; }
.btn-activar-stock { background:#607d8b; color:white; }
.stock-ctrl { display:flex; align-items:center; gap:6px; }
.stock-btn {
  width:28px; height:28px; border-radius:50%;
  border:1.5px solid #ccc; background:#fff;
  font-size:1rem; font-weight:700; cursor:pointer;
  display:flex; align-items:center; justify-content:center;
  line-height:1;
}
.stock-btn:hover { background:#f0f0f0; }
.stock-num { font-size:1rem; font-weight:700; min-width:28px; text-align:center; color:#1a1a1a; }

/* Acciones de edición */
.edicion-acciones { display:flex; gap:8px; margin-left:auto; }
.btn-guardar { background:#388e3c; color:white; padding:6px 14px; font-size:0.85rem; }
.btn-cancelar { background:#9e9e9e; color:white; padding:6px 14px; font-size:0.85rem; }
</style>
