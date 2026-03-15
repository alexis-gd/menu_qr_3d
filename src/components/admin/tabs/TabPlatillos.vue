<template>
  <div class="tab-content">
    <!-- Formulario nuevo platillo -->
    <div class="card">
      <div class="card-header collapsible" @click="formAbierto = !formAbierto">
        <h2>+ Agregar platillo</h2>
        <span class="chevron">{{ formAbierto ? '▲' : '▼' }}</span>
      </div>
      <div v-show="formAbierto" class="card-body">
        <div class="form-grid">
          <div class="field">
            <label>Categoría *</label>
            <select v-model="formProd.categoria_id">
              <option value="" disabled>Selecciona categoría</option>
              <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                {{ cat.icono || '' }} {{ cat.nombre }}
              </option>
            </select>
          </div>
          <div class="field">
            <label>Nombre del platillo *</label>
            <input :value="formProd.nombre" @input="formProd.nombre = ucfirst($event.target.value)" placeholder="Ej: Tacos al Pastor" />
          </div>
          <div class="field">
            <label>Precio *</label>
            <input v-model="formProd.precio" type="number" min="0" step="0.01" placeholder="0.00" />
          </div>
          <div class="field field-full">
            <label>Descripción breve</label>
            <textarea :value="formProd.descripcion" @input="formProd.descripcion = ucfirst($event.target.value)" rows="2" placeholder="Descripción opcional..."></textarea>
          </div>
          <div class="field field-full">
            <button @click="crearProducto" class="btn-primary" :disabled="guardando">
              {{ guardando ? 'Guardando...' : '+ Agregar platillo' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Lista de productos -->
    <div class="card">
      <div class="card-header">
        <h2>Platillos del menú</h2>
        <span class="count-badge">{{ productos.length }}</span>
      </div>
      <div class="card-body no-pad">
        <div v-if="loadingProductos" class="loading-inline"><div class="spinner"></div></div>
        <div v-else-if="!productos.length" class="empty-state">
          <span>🍽️</span>
          <p>Sin platillos todavía.<br>Agrega el primero arriba.</p>
        </div>
        <div v-else class="prod-lista">
          <div
            v-for="prod in productosOrdenados"
            :key="prod.id"
            class="prod-item"
            :class="{ editing: prodEditando === prod.id }"
          >
            <!-- Modo edición -->
            <div v-if="prodEditando === prod.id" class="prod-edit-form">
              <div class="edit-thumb">
                <img v-if="prod.foto_principal" :src="prod.foto_principal" :alt="prod.nombre" />
                <span v-else>📷</span>
              </div>
              <div class="edit-fields">
                <select v-model="formEdit.categoria_id">
                  <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                </select>
                <input :value="formEdit.nombre" @input="formEdit.nombre = ucfirst($event.target.value)" placeholder="Nombre" />
                <input v-model="formEdit.precio" type="number" min="0" step="0.01" placeholder="Precio" />
                <textarea :value="formEdit.descripcion" @input="formEdit.descripcion = ucfirst($event.target.value)" rows="2" placeholder="Descripción"></textarea>
                <div class="edit-stock">
                  <span class="edit-stock-label">Stock:</span>
                  <template v-if="formEdit.stock === null">
                    <span class="stock-sin-ctrl">Sin control</span>
                    <button type="button" class="btn-activar-stock" @click="formEdit.stock = 0">Activar</button>
                  </template>
                  <template v-else>
                    <button type="button" class="stock-btn" @click="formEdit.stock = Math.max(0, formEdit.stock - 1)">−</button>
                    <span class="stock-num">{{ formEdit.stock }}</span>
                    <button type="button" class="stock-btn" @click="formEdit.stock++">+</button>
                  </template>
                </div>
                <!-- ── Personalización ── -->
                <div class="pers-toggle">
                  <label class="toggle-check-label">
                    <input type="checkbox" v-model="formEdit.tiene_personalizacion" />
                    Personalización por pasos
                  </label>
                </div>

                <div v-if="formEdit.tiene_personalizacion" class="pers-section">
                  <!-- Aviso complemento -->
                  <input
                    v-model="formEdit.aviso_complemento"
                    placeholder="Aviso sugerido (ej: ¿Quieres una bebida?)"
                    class="pers-aviso-input"
                  />
                  <select v-model="formEdit.aviso_categoria_id" class="pers-aviso-cat">
                    <option value="">Sin categoría sugerida</option>
                    <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                      {{ cat.icono || '' }} {{ cat.nombre }}
                    </option>
                  </select>

                  <!-- Grupos -->
                  <div v-if="gruposCargando" class="loading-inline"><div class="spinner"></div></div>
                  <div v-else class="grupos-editor">
                    <div class="grupos-label">Grupos de opciones:</div>

                    <div
                      v-for="(grupo, gi) in formEdit.grupos"
                      :key="grupo._key"
                      class="grupo-card"
                    >
                      <!-- Cabecera del grupo -->
                      <div class="grupo-head">
                        <input
                          :value="grupo.nombre"
                          @input="grupo.nombre = ucfirst($event.target.value)"
                          placeholder="Nombre del grupo (ej: Tamaño)"
                          class="grupo-nombre-input"
                        />
                        <select v-model="grupo.tipo" class="grupo-tipo">
                          <option value="radio">Única</option>
                          <option value="checkbox">Múltiple</option>
                        </select>
                        <button type="button" @click="eliminarGrupo(gi)" class="btn-del-grupo" title="Eliminar grupo">✕</button>
                      </div>

                      <!-- Config del grupo -->
                      <div class="grupo-config">
                        <label class="config-check">
                          <input type="checkbox" v-model="grupo.obligatorio" />
                          <span>Requerido</span>
                        </label>
                        <label v-if="grupo.tipo === 'checkbox'" class="config-max">
                          Máx selecciones:
                          <input type="number" v-model.number="grupo.max_selecciones" min="1" max="20" class="input-max" />
                        </label>
                        <!-- Control de max dinámico: solo para grupos radio cuando hay al menos un grupo checkbox -->
                        <label
                          v-if="grupo.tipo === 'radio' && formEdit.grupos.some((g, i) => i !== gi && g.tipo === 'checkbox')"
                          class="config-din"
                        >
                          Controla máx de:
                          <select v-model="grupo.max_dinamico_grupo_index" class="input-din">
                            <option :value="null">Ninguno</option>
                            <template v-for="(g, i) in formEdit.grupos" :key="g._key">
                              <option v-if="i !== gi && g.tipo === 'checkbox'" :value="i">
                                {{ g.nombre || `Grupo ${i + 1}` }}
                              </option>
                            </template>
                          </select>
                        </label>
                      </div>

                      <!-- Opciones del grupo -->
                      <div class="opciones-edit">
                        <div
                          v-for="(op, oi) in grupo.opciones"
                          :key="op._key"
                          class="opcion-edit-row"
                        >
                          <input
                            :value="op.nombre"
                            @input="op.nombre = ucfirst($event.target.value)"
                            placeholder="Opción"
                            class="op-nombre"
                          />
                          <span class="op-extra-wrap">
                            +$<input type="number" v-model.number="op.precio_extra" min="0" step="0.5" class="op-extra" />
                          </span>
                          <span
                            v-if="grupo.max_dinamico_grupo_index !== null"
                            class="op-override-wrap"
                            title="Max selecciones del grupo controlado cuando se elige esta opción"
                          >
                            Máx:<input type="number" v-model.number="op.max_override" min="0" class="op-override" />
                          </span>
                          <button type="button" @click="eliminarOpcion(gi, oi)" class="btn-del-op" title="Eliminar opción">✕</button>
                        </div>
                        <button type="button" @click="agregarOpcion(gi)" class="btn-add-op">+ opción</button>
                      </div>
                    </div><!-- /.grupo-card -->

                    <button type="button" @click="agregarGrupo" class="btn-add-grupo">+ Agregar grupo</button>
                  </div><!-- /.grupos-editor -->
                </div><!-- /.pers-section -->

                <div class="edit-actions">
                  <button @click="guardarEdicionProducto(prod.id)" class="btn-save">✓ Guardar</button>
                  <button @click="cancelarEdicion" class="btn-cancel">✕ Cancelar</button>
                </div>
              </div>
            </div>

            <!-- Modo normal -->
            <template v-else>
              <div class="prod-thumb" @click="abrirPreview(prod)" title="Ver foto">
                <img
                  v-if="prod.foto_principal"
                  :src="prod.foto_principal"
                  :alt="prod.nombre"
                  @error="($e) => $e.target.style.display='none'"
                />
                <span v-else class="thumb-empty">📷</span>
                <div v-if="prod.foto_principal" class="thumb-overlay">👁</div>
              </div>
              <div class="prod-info">
                <strong class="prod-nombre">{{ prod.nombre }}</strong>
                <span class="prod-cat">{{ catMap[prod.categoria_id] || '—' }}</span>
                <span class="prod-precio">${{ Number(prod.precio).toFixed(2) }}</span>
              </div>
              <div class="prod-badges">
                <span v-if="prod.tiene_ar" class="badge badge-3d">3D ✓</span>
                <span v-else class="badge badge-no3d">Sin 3D</span>
                <label class="badge-disp" :title="prod.disponible ? 'Disponible — click para desactivar' : 'No disponible — click para activar'">
                  <input type="checkbox" :checked="prod.disponible" @change="toggleDisponible(prod)" hidden />
                  <span :class="['disp-pill', prod.disponible ? 'disp-on' : 'disp-off']">
                    {{ prod.disponible ? 'Activo' : 'Inactivo' }}
                  </span>
                </label>
              </div>
              <div class="prod-actions">
                <button @click="iniciarEdicion(prod)" class="btn-icon btn-edit" title="Editar platillo">✏️</button>
                <label class="btn-icon btn-foto" title="Subir foto">
                  📷 <input type="file" multiple accept="image/*" @change="subirFotos(prod.id, $event)" hidden />
                </label>
                <label v-if="!prod.tiene_ar" class="btn-icon btn-3d" title="Subir modelo 3D (.glb)">
                  📦 <input type="file" accept=".glb" @change="subirGlb(prod.id, $event)" hidden />
                </label>
                <button @click="eliminarProducto(prod.id)" class="btn-icon btn-del" title="Eliminar">🗑</button>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal preview de foto -->
    <div v-if="preview" class="preview-overlay" @click="preview = null">
      <div class="preview-box" @click.stop>
        <button class="preview-close" @click="preview = null">✕</button>
        <img :src="preview.url" :alt="preview.nombre" class="preview-img" />
        <p class="preview-nombre">{{ preview.nombre }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useApi } from '../../../composables/useApi.js'
import { ucfirst } from '../../../utils/ucfirst.js'

const props = defineProps({
  restauranteId: { type: Number, required: true },
  categorias:    { type: Array, default: () => [] },
})

const emit = defineEmits(['notif'])

const { get, post, put, del } = useApi()

const productos        = ref([])
const loadingProductos = ref(false)
const guardando        = ref(false)
const formAbierto      = ref(true)
const prodEditando     = ref(null)
const formEdit         = ref({})
const preview          = ref(null)
const gruposCargando   = ref(false)

const formProd = ref({ categoria_id: '', nombre: '', precio: '', descripcion: '' })

let _uid = 0
const newKey = () => ++_uid

const catMap = computed(() => {
  const m = {}
  props.categorias.forEach(c => { m[c.id] = c.nombre })
  return m
})

const productosOrdenados = computed(() =>
  [...productos.value].sort((a, b) => {
    const ca = catMap.value[a.categoria_id] || ''
    const cb = catMap.value[b.categoria_id] || ''
    return ca.localeCompare(cb) || a.nombre.localeCompare(b.nombre)
  })
)

async function loadProductos() {
  loadingProductos.value = true
  try {
    const res = await get('productos', { restaurante_id: props.restauranteId })
    productos.value = res.productos || []
  } finally {
    loadingProductos.value = false
  }
}

async function crearProducto() {
  const f = formProd.value
  if (!f.categoria_id || !f.nombre.trim() || f.precio === '') {
    emit('notif', { texto: 'Categoría, nombre y precio son requeridos', tipo: 'error' })
    return
  }
  guardando.value = true
  try {
    await post('productos', { categoria_id: f.categoria_id, nombre: f.nombre.trim(), precio: parseFloat(f.precio), descripcion: f.descripcion.trim() })
    formProd.value = { categoria_id: '', nombre: '', precio: '', descripcion: '' }
    await loadProductos()
    emit('notif', { texto: 'Platillo agregado', tipo: 'ok' })
  } catch (err) { emit('notif', { texto: err.message, tipo: 'error' }) }
  finally { guardando.value = false }
}

async function eliminarProducto(id) {
  if (!confirm('¿Eliminar este platillo del menú?')) return
  try {
    await del('productos', { id })
    await loadProductos()
    emit('notif', { texto: 'Platillo eliminado', tipo: 'ok' })
  } catch (err) { emit('notif', { texto: err.message, tipo: 'error' }) }
}

const iniciarEdicion = async (prod) => {
  prodEditando.value = prod.id
  formEdit.value = {
    categoria_id:          prod.categoria_id,
    nombre:                prod.nombre,
    precio:                prod.precio,
    descripcion:           prod.descripcion || '',
    stock:                 prod.stock ?? null,
    tiene_personalizacion: !!prod.tiene_personalizacion,
    aviso_complemento:     prod.aviso_complemento || '',
    aviso_categoria_id:    prod.aviso_categoria_id || '',
    grupos:                [],
  }
  if (prod.tiene_personalizacion) {
    await loadGrupos(prod.id)
  }
}

const loadGrupos = async (productoId) => {
  gruposCargando.value = true
  try {
    const res = await get('producto-grupos', { producto_id: productoId })
    const gruposApi = res.grupos || []
    // Mapa id → índice para resolver max_dinamico_grupo_id → índice local
    const idToIndex = {}
    gruposApi.forEach((g, i) => { idToIndex[g.id] = i })

    formEdit.value.grupos = gruposApi.map(g => ({
      _key:                    newKey(),
      nombre:                  g.nombre,
      tipo:                    g.tipo,
      obligatorio:             g.obligatorio,
      max_selecciones:         g.max_selecciones,
      max_dinamico_grupo_index: g.max_dinamico_grupo_id != null
        ? (idToIndex[g.max_dinamico_grupo_id] ?? null)
        : null,
      opciones: g.opciones.map(o => ({
        _key:         newKey(),
        nombre:       o.nombre,
        precio_extra: o.precio_extra,
        max_override: o.max_override,
      })),
    }))
  } finally {
    gruposCargando.value = false
  }
}

const agregarGrupo = () => {
  formEdit.value.grupos.push({
    _key: newKey(), nombre: '', tipo: 'radio',
    obligatorio: true, max_selecciones: 1,
    max_dinamico_grupo_index: null, opciones: [],
  })
}

const eliminarGrupo = (idx) => {
  formEdit.value.grupos.splice(idx, 1)
  // Ajustar referencias de max_dinamico_grupo_index
  formEdit.value.grupos.forEach(g => {
    if (g.max_dinamico_grupo_index === idx) g.max_dinamico_grupo_index = null
    else if (g.max_dinamico_grupo_index !== null && g.max_dinamico_grupo_index > idx)
      g.max_dinamico_grupo_index--
  })
}

const agregarOpcion = (gi) => {
  formEdit.value.grupos[gi].opciones.push({ _key: newKey(), nombre: '', precio_extra: 0, max_override: null })
}

const eliminarOpcion = (gi, oi) => {
  formEdit.value.grupos[gi].opciones.splice(oi, 1)
}

const cancelarEdicion = () => {
  prodEditando.value = null
  formEdit.value = {}
}

const toggleDisponible = async (prod) => {
  const nuevoValor = !prod.disponible
  try {
    await put('productos', { disponible: nuevoValor ? 1 : 0 }, { id: prod.id })
    prod.disponible = nuevoValor
    emit('notif', { texto: nuevoValor ? 'Platillo activado' : 'Platillo desactivado', tipo: 'ok' })
  } catch (err) { emit('notif', { texto: err.message, tipo: 'error' }) }
}

const guardarEdicionProducto = async (id) => {
  if (!formEdit.value.nombre?.trim()) {
    emit('notif', { texto: 'El nombre es requerido', tipo: 'error' })
    return
  }
  guardando.value = true
  try {
    // 1. Guardar campos básicos
    const payload = {
      categoria_id: formEdit.value.categoria_id,
      nombre:       formEdit.value.nombre.trim(),
      precio:       parseFloat(formEdit.value.precio),
      descripcion:  formEdit.value.descripcion.trim(),
    }
    if (formEdit.value.stock !== null) payload.stock = formEdit.value.stock
    await put('productos', payload, { id })

    // 2. Guardar personalización (siempre, para sincronizar el flag)
    await post('producto-grupos', {
      producto_id:           id,
      tiene_personalizacion: formEdit.value.tiene_personalizacion ? 1 : 0,
      aviso_complemento:     formEdit.value.aviso_complemento?.trim() || null,
      aviso_categoria_id:    formEdit.value.aviso_categoria_id || null,
      grupos: formEdit.value.tiene_personalizacion
        ? formEdit.value.grupos.map((g, gi) => ({
            nombre:                  g.nombre.trim(),
            tipo:                    g.tipo,
            obligatorio:             g.obligatorio ? 1 : 0,
            min_selecciones:         0,
            max_selecciones:         parseInt(g.max_selecciones) || 1,
            max_dinamico_grupo_index: g.max_dinamico_grupo_index,
            orden:                   gi,
            opciones: g.opciones.map((o, oi) => ({
              nombre:       o.nombre.trim(),
              precio_extra: parseFloat(o.precio_extra) || 0,
              max_override: o.max_override !== null && o.max_override !== ''
                ? parseInt(o.max_override) : null,
              orden: oi,
            })),
          }))
        : [],
    })

    prodEditando.value = null
    await loadProductos()
    emit('notif', { texto: 'Platillo actualizado', tipo: 'ok' })
  } catch (err) { emit('notif', { texto: err.message, tipo: 'error' }) }
  finally { guardando.value = false }
}

async function subirFotos(prodId, event) {
  const files = event.target.files
  if (!files.length) return
  const fd = new FormData()
  fd.append('producto_id', prodId)
  for (let i = 0; i < files.length; i++) fd.append('fotos[]', files[i])
  try {
    const res = await fetch(`${import.meta.env.BASE_URL}api/?route=upload-fotos`, { method: 'POST', body: fd, credentials: 'include' })
    if (!res.ok) throw new Error('Error al subir fotos')
    event.target.value = ''
    await loadProductos()
    emit('notif', { texto: 'Foto subida correctamente', tipo: 'ok' })
  } catch (err) { emit('notif', { texto: err.message, tipo: 'error' }) }
}

async function subirGlb(prodId, event) {
  const file = event.target.files[0]
  if (!file) return
  const fd = new FormData()
  fd.append('producto_id', prodId)
  fd.append('modelo', file)
  try {
    const res = await fetch(`${import.meta.env.BASE_URL}api/?route=upload-glb`, { method: 'POST', body: fd, credentials: 'include' })
    const data = await res.json()
    if (!res.ok) throw new Error(data.error || 'Error al subir el modelo')
    event.target.value = ''
    await loadProductos()
    emit('notif', { texto: 'Modelo 3D subido. ¡Ya disponible en el menú!', tipo: 'ok' })
  } catch (err) { emit('notif', { texto: err.message, tipo: 'error' }) }
}

const abrirPreview = (prod) => {
  if (!prod.foto_principal) return
  preview.value = { url: prod.foto_principal, nombre: prod.nombre }
}

onMounted(loadProductos)
</script>

<style scoped>
/* ─── Lista de productos ─── */
.prod-lista { display: flex; flex-direction: column; }

.prod-item {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 16px; border-bottom: 1px solid #f5f5f5; transition: background 0.15s;
}
.prod-item:last-child { border-bottom: none; }
.prod-item:hover { background: #fafafa; }
.prod-item.editing { background: #fffde7; align-items: flex-start; }

/* Miniatura */
.prod-thumb {
  width: 56px; height: 56px; border-radius: 8px; background: #f0f0f0;
  overflow: hidden; flex-shrink: 0; cursor: pointer; position: relative;
}
.prod-thumb img { width: 100%; height: 100%; object-fit: cover; }
.thumb-empty { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; opacity: 0.35; }
.thumb-overlay {
  position: absolute; inset: 0; background: rgba(0,0,0,0.45);
  display: flex; align-items: center; justify-content: center;
  opacity: 0; transition: opacity 0.2s; font-size: 1.2rem;
}
.prod-thumb:hover .thumb-overlay { opacity: 1; }

/* Info */
.prod-info { flex: 1; display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.prod-nombre { font-size: 0.92rem; font-weight: 700; color: #1a1a1a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.prod-cat    { font-size: 0.75rem; color: #aaa; }
.prod-precio { font-size: 0.88rem; font-weight: 700; color: var(--accent); }

/* Badges */
.prod-badges { flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-end; gap: 5px; }
.badge        { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; }
.badge-3d     { background: #e8f5e9; color: #2e7d32; }
.badge-no3d   { background: #f5f5f5; color: #bbb; }
.badge-disp   { cursor: pointer; }
.disp-pill    { display: inline-block; padding: 3px 9px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; transition: background 0.15s; }
.disp-on  { background: #e8f5e9; color: #2e7d32; }
.disp-off { background: #fdecea; color: #c62828; }

.prod-actions { display: flex; gap: 5px; flex-shrink: 0; }

/* Formulario edición inline */
.prod-edit-form { display: flex; gap: 12px; width: 100%; padding: 4px 0; }
.edit-thumb {
  width: 56px; height: 56px; border-radius: 8px; background: #f0f0f0;
  overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
}
.edit-thumb img { width: 100%; height: 100%; object-fit: cover; }
.edit-fields { flex: 1; display: flex; flex-direction: column; gap: 7px; }
.edit-fields select, .edit-fields input, .edit-fields textarea {
  padding: 7px 10px; border: 1.5px solid #e0e0e0; border-radius: 7px;
  font-size: 0.88rem; outline: none; font-family: inherit; background: #fff;
}
.edit-fields select:focus, .edit-fields input:focus, .edit-fields textarea:focus { border-color: var(--accent); }
.edit-fields textarea { resize: vertical; min-height: 50px; }
.edit-actions { display: flex; gap: 8px; }

/* Stock */
.edit-stock       { display: flex; align-items: center; gap: 8px; }
.edit-stock-label { font-size: 0.78rem; font-weight: 600; color: #777; }
.stock-sin-ctrl   { font-size: 0.8rem; color: #bbb; }
.btn-activar-stock { font-size: 0.85rem; font-weight: 600; padding: 7px 14px; border: none; border-radius: 7px; background: #e3f2fd; color: #1565c0; cursor: pointer; }
.btn-activar-stock:hover { background: #bbdefb; }
.stock-btn { width: 26px; height: 26px; border-radius: 50%; border: 1.5px solid #e0e0e0; background: #fff; font-size: 1rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1; }
.stock-btn:hover { background: #f0f0f0; }
.stock-num { font-size: 0.95rem; font-weight: 800; min-width: 24px; text-align: center; color: #1a1a1a; }

/* Preview foto */
.preview-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.8);
  display: flex; align-items: center; justify-content: center;
  z-index: 2000; padding: 20px; cursor: pointer;
}
.preview-box {
  position: relative; background: #fff; border-radius: 16px;
  overflow: hidden; max-width: 480px; width: 100%; cursor: default;
}
.preview-close {
  position: absolute; top: 12px; right: 12px; width: 34px; height: 34px;
  border-radius: 50%; background: rgba(0,0,0,0.15); border: none;
  cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; z-index: 1;
}
.preview-img    { width: 100%; display: block; max-height: 70vh; object-fit: contain; }
.preview-nombre { padding: 12px 16px; font-weight: 700; font-size: 0.95rem; color: #333; margin: 0; text-align: center; }

/* Responsive */
@media (max-width: 600px) {
  .prod-badges { display: none; }
}

/* ── Personalización ── */
.pers-toggle {
  padding: 8px 0 4px;
  border-top: 1px solid #f0f0f0;
}

.toggle-check-label {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 0.85rem;
  font-weight: 600;
  color: #444;
  cursor: pointer;
  user-select: none;
}

.toggle-check-label input[type="checkbox"] {
  width: 15px;
  height: 15px;
  accent-color: var(--accent);
  cursor: pointer;
}

.pers-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 8px 0 4px;
}

.pers-aviso-input,
.pers-aviso-cat {
  padding: 7px 10px;
  border: 1.5px solid #e0e0e0;
  border-radius: 7px;
  font-size: 0.85rem;
  font-family: inherit;
  outline: none;
  background: #fff;
}
.pers-aviso-input:focus,
.pers-aviso-cat:focus { border-color: var(--accent); }

/* Grupos */
.grupos-editor { display: flex; flex-direction: column; gap: 8px; }
.grupos-label  { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #aaa; }

.grupo-card {
  border: 1.5px solid #e8e8e8;
  border-radius: 10px;
  padding: 10px 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  background: #fafafa;
}

.grupo-head {
  display: flex;
  align-items: center;
  gap: 6px;
}

.grupo-nombre-input {
  flex: 1;
  padding: 6px 9px;
  border: 1.5px solid #e0e0e0;
  border-radius: 6px;
  font-size: 0.85rem;
  font-family: inherit;
  outline: none;
  background: #fff;
}
.grupo-nombre-input:focus { border-color: var(--accent); }

.grupo-tipo {
  padding: 6px 7px;
  border: 1.5px solid #e0e0e0;
  border-radius: 6px;
  font-size: 0.82rem;
  background: #fff;
  outline: none;
  cursor: pointer;
  flex-shrink: 0;
}

.btn-del-grupo {
  flex-shrink: 0;
  width: 26px;
  height: 26px;
  border: none;
  border-radius: 50%;
  background: #fdecea;
  color: #c62828;
  font-size: 0.8rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
.btn-del-grupo:hover { background: #ffcdd2; }

.grupo-config {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
}

.config-check {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 0.82rem;
  color: #555;
  cursor: pointer;
}
.config-check input[type="checkbox"] { accent-color: var(--accent); }

.config-max,
.config-din {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 0.82rem;
  color: #555;
}

.input-max {
  width: 52px;
  padding: 4px 6px;
  border: 1.5px solid #e0e0e0;
  border-radius: 5px;
  font-size: 0.82rem;
  text-align: center;
  outline: none;
}
.input-max:focus { border-color: var(--accent); }

.input-din {
  padding: 4px 6px;
  border: 1.5px solid #e0e0e0;
  border-radius: 5px;
  font-size: 0.82rem;
  outline: none;
  background: #fff;
}
.input-din:focus { border-color: var(--accent); }

/* Opciones del grupo */
.opciones-edit {
  display: flex;
  flex-direction: column;
  gap: 5px;
  padding-top: 4px;
  border-top: 1px solid #efefef;
}

.opcion-edit-row {
  display: flex;
  align-items: center;
  gap: 6px;
}

.op-nombre {
  flex: 1;
  padding: 5px 8px;
  border: 1px solid #e0e0e0;
  border-radius: 5px;
  font-size: 0.82rem;
  font-family: inherit;
  outline: none;
  background: #fff;
}
.op-nombre:focus { border-color: var(--accent); }

.op-extra-wrap,
.op-override-wrap {
  display: flex;
  align-items: center;
  gap: 3px;
  font-size: 0.78rem;
  color: #888;
  flex-shrink: 0;
}

.op-extra {
  width: 58px;
  padding: 4px 5px;
  border: 1px solid #e0e0e0;
  border-radius: 5px;
  font-size: 0.82rem;
  text-align: right;
  outline: none;
}
.op-extra:focus { border-color: var(--accent); }

.op-override {
  width: 46px;
  padding: 4px 5px;
  border: 1px solid #e0e0e0;
  border-radius: 5px;
  font-size: 0.82rem;
  text-align: center;
  outline: none;
}
.op-override:focus { border-color: var(--accent); }

.btn-del-op {
  flex-shrink: 0;
  width: 22px;
  height: 22px;
  border: none;
  border-radius: 50%;
  background: #fdecea;
  color: #c62828;
  font-size: 0.72rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
.btn-del-op:hover { background: #ffcdd2; }

.btn-add-op {
  align-self: flex-start;
  padding: 4px 10px;
  border: 1.5px dashed #ccc;
  border-radius: 6px;
  background: transparent;
  color: #888;
  font-size: 0.8rem;
  cursor: pointer;
  margin-top: 2px;
}
.btn-add-op:hover { border-color: var(--accent); color: var(--accent); }

.btn-add-grupo {
  align-self: flex-start;
  padding: 7px 14px;
  border: 1.5px dashed #ccc;
  border-radius: 8px;
  background: transparent;
  color: #888;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
}
.btn-add-grupo:hover { border-color: var(--accent); color: var(--accent); }
</style>
