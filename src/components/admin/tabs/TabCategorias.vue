<template>
  <div class="tab-content">
    <div class="card">
      <div class="card-header">
        <h2>Nueva categoría</h2>
      </div>
      <div class="card-body">
        <div class="form-row">
          <div class="field" style="flex:1">
            <label>Nombre</label>
            <input :value="formCat.nombre" @input="formCat.nombre = ucfirst($event.target.value)" placeholder="Ej: Entradas, Bebidas..." @keyup.enter="crearCategoria" />
          </div>
          <div class="field">
            <label>Ícono</label>
            <div class="icono-wrap">
              <button type="button" class="icono-btn" @click.stop="togglePicker('nuevo')">
                <SvgIcon v-if="formCat.icono" :path="resolverIcono(formCat.icono)" :size="20" />
                <SvgIcon v-else :path="mdiFormatListBulleted" :size="20" />
                <span class="picker-caret">▾</span>
              </button>
              <div v-if="pickerAbierto === 'nuevo'" class="icono-picker" @click.stop>
                <div v-for="g in ICONO_GRUPOS" :key="g.nombre" class="icono-grupo">
                  <div class="icono-grupo-titulo">{{ g.nombre }}</div>
                  <div class="icono-grid">
                    <button
                      v-for="ic in g.iconos" :key="ic.key"
                      type="button"
                      class="icono-opt"
                      :class="{ selected: formCat.icono === ic.key }"
                      :title="ic.label"
                      @click="seleccionarIcono(ic.key, 'nuevo')"
                    >
                      <SvgIcon :path="ic.path" :size="18" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="field field-btn">
            <label>&nbsp;</label>
            <button @click="crearCategoria" class="btn-primary" :disabled="guardando">
              {{ guardando ? 'Agregando...' : 'Agregar' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h2>Categorías</h2>
        <span class="count-badge">{{ categorias.length }}</span>
      </div>
      <div class="card-body no-pad">
        <div v-if="!categorias.length" class="empty-state" style="padding:32px">
          <SvgIcon :path="mdiFormatListBulleted" :size="36" />
          <p>Sin categorías todavía.</p>
        </div>
        <div v-else class="cat-lista">
          <div v-for="(cat, idx) in categorias" :key="cat.id" class="cat-item">
            <!-- Modo edición -->
            <div v-if="catEditando === cat.id" class="cat-edit-form">
              <div class="icono-wrap">
                <button type="button" class="icono-btn icono-btn-sm" @click.stop="togglePicker(cat.id)">
                  <SvgIcon v-if="formCatEdit.icono" :path="resolverIcono(formCatEdit.icono)" :size="18" />
                  <SvgIcon v-else :path="mdiFormatListBulleted" :size="18" />
                  <span class="picker-caret">▾</span>
                </button>
                <div v-if="pickerAbierto === cat.id" class="icono-picker icono-picker-right" @click.stop>
                  <div v-for="g in ICONO_GRUPOS" :key="g.nombre" class="icono-grupo">
                    <div class="icono-grupo-titulo">{{ g.nombre }}</div>
                    <div class="icono-grid">
                      <button
                        v-for="ic in g.iconos" :key="ic.key"
                        type="button"
                        class="icono-opt"
                        :class="{ selected: formCatEdit.icono === ic.key }"
                        :title="ic.label"
                        @click="seleccionarIcono(ic.key, cat.id)"
                      >
                        <SvgIcon :path="ic.path" :size="18" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <input :value="formCatEdit.nombre" @input="formCatEdit.nombre = ucfirst($event.target.value)" placeholder="Nombre" class="input-nombre" @keyup.enter="guardarEdicionCategoria(cat.id)" />
              <button @click="guardarEdicionCategoria(cat.id)" class="btn-save-sm">✓</button>
              <button @click="catEditando = null" class="btn-cancel-sm">✕</button>
            </div>

            <!-- Modo normal -->
            <template v-else>
              <span class="cat-icono">
                <SvgIcon v-if="cat.icono" :path="resolverIcono(cat.icono)" :size="20" />
                <SvgIcon v-else :path="mdiFormatListBulleted" :size="20" />
              </span>
              <span class="cat-nombre">{{ cat.nombre }}</span>
              <div class="cat-ord-btns">
                <button @click="moverCategoria(idx, -1)" class="btn-ord" :disabled="idx === 0" title="Subir">▲</button>
                <button @click="moverCategoria(idx, 1)" class="btn-ord" :disabled="idx === categorias.length - 1" title="Bajar">▼</button>
              </div>
              <button @click="iniciarEdicionCategoria(cat)" class="btn-icon btn-edit" title="Editar"><SvgIcon :path="mdiPencil" :size="15" /></button>
              <button @click="eliminarCategoria(cat.id)" class="btn-icon btn-del" title="Eliminar"><SvgIcon :path="mdiTrashCanOutline" :size="15" /></button>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { mdiFormatListBulleted, mdiPencil, mdiTrashCanOutline } from '@mdi/js'
import { useApi } from '../../../composables/useApi.js'
import { ucfirst } from '../../../utils/ucfirst.js'
import { ICONO_GRUPOS, resolverIcono } from '../../../utils/iconosCategorias.js'
import SvgIcon from '../../SvgIcon.vue'

const props = defineProps({
  restauranteId: { type: Number, required: true },
  categorias:    { type: Array, default: () => [] },
})

const emit = defineEmits(['notif', 'categorias-changed'])

const { post, put, del } = useApi()

const catEditando   = ref(null)
const formCatEdit   = ref({})
const pickerAbierto = ref(null)
const formCat       = ref({ nombre: '', icono: '' })
const guardando     = ref(false)

const togglePicker = (id) => {
  pickerAbierto.value = pickerAbierto.value === id ? null : id
}

const seleccionarIcono = (key, target) => {
  if (target === 'nuevo') formCat.value.icono = key
  else formCatEdit.value.icono = key
  pickerAbierto.value = null
}

const cerrarPickerGlobal = (e) => {
  if (!e.target.closest('.icono-wrap')) pickerAbierto.value = null
}

const iniciarEdicionCategoria = (cat) => {
  catEditando.value = cat.id
  formCatEdit.value = { nombre: cat.nombre, icono: cat.icono || '' }
}

async function crearCategoria() {
  if (!formCat.value.nombre.trim()) { emit('notif', { texto: 'Escribe un nombre', tipo: 'error' }); return }
  guardando.value = true
  try {
    await post('categorias', { restaurante_id: props.restauranteId, nombre: formCat.value.nombre.trim(), icono: formCat.value.icono || null })
    formCat.value = { nombre: '', icono: '' }
    emit('categorias-changed')
    emit('notif', { texto: 'Categoría creada', tipo: 'ok' })
  } catch (err) { emit('notif', { texto: err.message, tipo: 'error' }) }
  finally { guardando.value = false }
}

async function guardarEdicionCategoria(id) {
  if (!formCatEdit.value.nombre?.trim()) { emit('notif', { texto: 'El nombre es requerido', tipo: 'error' }); return }
  try {
    await put('categorias', { nombre: formCatEdit.value.nombre.trim(), icono: formCatEdit.value.icono || null }, { id })
    catEditando.value = null
    emit('categorias-changed')
    emit('notif', { texto: 'Categoría actualizada', tipo: 'ok' })
  } catch (err) { emit('notif', { texto: err.message, tipo: 'error' }) }
}

async function eliminarCategoria(id) {
  if (!confirm('¿Eliminar esta categoría?')) return
  try {
    await del('categorias', { id })
    emit('categorias-changed')
    emit('notif', { texto: 'Categoría eliminada', tipo: 'ok' })
  } catch (err) { emit('notif', { texto: err.message, tipo: 'error' }) }
}

async function moverCategoria(idx, dir) {
  const arr = [...props.categorias]
  const newIdx = idx + dir
  if (newIdx < 0 || newIdx >= arr.length) return
  ;[arr[idx], arr[newIdx]] = [arr[newIdx], arr[idx]]
  arr.forEach((c, i) => { c.orden = i })
  try {
    await Promise.all([
      put('categorias', { orden: arr[idx].orden }, { id: arr[idx].id }),
      put('categorias', { orden: arr[newIdx].orden }, { id: arr[newIdx].id }),
    ])
    emit('categorias-changed')
  } catch (err) {
    emit('notif', { texto: err.message, tipo: 'error' })
    emit('categorias-changed')
  }
}

onMounted(() => document.addEventListener('click', cerrarPickerGlobal))
onUnmounted(() => document.removeEventListener('click', cerrarPickerGlobal))
</script>

<style scoped>
/* ─── Categorías ─── */
.cat-lista { display: flex; flex-direction: column; }
.cat-item {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 16px; border-bottom: 1px solid #f5f5f5;
}
.cat-item:last-child { border-bottom: none; }
.cat-icono  { width: 28px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--accent, #FF6B35); }
.cat-nombre { flex: 1; font-weight: 600; font-size: 0.9rem; color: #333; }
.cat-ord-btns { display: flex; flex-direction: column; gap: 2px; flex-shrink: 0; }
.btn-ord { width: 22px; height: 18px; border: 1px solid #e0e0e0; border-radius: 4px; background: #fafafa; font-size: 0.6rem; cursor: pointer; line-height: 1; padding: 0; }
.btn-ord:hover:not(:disabled) { background: #eee; }
.btn-ord:disabled { opacity: 0.25; cursor: default; }

/* Edición inline */
.cat-edit-form { display: flex; align-items: center; gap: 8px; width: 100%; }
.input-nombre { flex: 1; padding: 7px 10px; border: 1.5px solid #e0e0e0; border-radius: 7px; font-size: 0.9rem; outline: none; }
.input-nombre:focus { border-color: var(--accent); }

/* ─── Icon picker ─── */
.icono-wrap { position: relative; display: inline-block; }
.icono-btn {
  display: flex; align-items: center; gap: 6px;
  padding: 9px 12px; border: 1.5px solid #e0e0e0; border-radius: 8px;
  background: #fafafa; cursor: pointer; transition: border-color 0.2s, background 0.2s; white-space: nowrap;
  color: var(--accent, #FF6B35);
}
.icono-btn:hover { border-color: var(--accent); background: #fff; }
.icono-btn-sm { padding: 6px 10px; }
.picker-caret  { font-size: 0.65rem; color: #bbb; }

.icono-picker {
  position: absolute; top: calc(100% + 6px); left: 0; z-index: 300;
  background: #fff; border: 1px solid #e8e8e8; border-radius: 14px;
  box-shadow: 0 10px 36px rgba(0,0,0,0.16); padding: 14px;
  width: 300px; max-height: 360px; overflow-y: auto; scrollbar-width: thin;
}
.icono-picker-right { left: 0; right: auto; }
.icono-grupo { margin-bottom: 12px; }
.icono-grupo:last-child { margin-bottom: 0; }
.icono-grupo-titulo { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.7px; color: #bbb; margin-bottom: 6px; padding-left: 2px; }
.icono-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 2px; }
.icono-opt {
  width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
  border: none; background: transparent; cursor: pointer;
  border-radius: 6px; transition: background 0.12s, transform 0.12s; padding: 0;
  color: #555;
}
.icono-opt:hover { background: #f0f0f0; transform: scale(1.2); color: var(--accent, #FF6B35); }
.icono-opt.selected { background: #fff3e0; outline: 2px solid var(--accent); color: var(--accent, #FF6B35); }
</style>
