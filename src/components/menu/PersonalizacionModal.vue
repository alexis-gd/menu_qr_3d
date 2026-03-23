<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-panel">

      <!-- ── Sticky top bar ── -->
      <div class="modal-topbar">
        <span class="topbar-nombre">{{ producto.nombre }}</span>
        <button class="btn-close" @click="$emit('close')" aria-label="Cerrar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>

      <!-- ── Área scrollable ── -->
      <div class="modal-scroll">

        <!-- Visual: 3D o foto -->
        <div v-if="producto.tiene_ar || producto.foto_principal" class="modal-visual">
          <ModelViewer3D
            v-if="producto.tiene_ar"
            :modelo-url="producto.modelo_glb_url"
          />
          <img
            v-else
            :src="producto.foto_principal"
            :alt="producto.nombre"
            class="modal-foto"
          />
          <div v-if="producto.tiene_ar" class="hint-3d">
            <SvgIcon :path="mdiRefresh" :size="14" />
            <span>Arrastra para rotar</span>
          </div>
          <div v-if="producto.tiene_ar" class="hint-ar-arrow">
            Toca el botón para verlo en tu espacio real ↓
          </div>
        </div>

        <!-- Precio dinámico -->
        <div class="precio-dinamico">
          <div class="precio-main">
            <span class="precio-label">Total</span>
            <span class="precio-valor">${{ precioTotal.toFixed(2) }}</span>
          </div>
          <div v-if="extraTotal > 0" class="precio-detalle">
            Base ${{ Number(producto.precio).toFixed(2) }} + extras ${{ extraTotal.toFixed(2) }}
          </div>
        </div>

        <!-- Descripción -->
        <p v-if="producto.descripcion" class="prod-desc">{{ producto.descripcion }}</p>

        <!-- ── Grupos de opciones (acordeón progresivo) ── -->
        <div
          v-for="(grupo, idx) in producto.grupos"
          :key="grupo.id"
          :class="[
            'grupo-section',
            {
              'grupo-activo':     idx === pasoActivo,
              'grupo-completado': idx < pasoActivo && tieneSeleccionValida(grupo),
              'grupo-bloqueado':  idx > pasoActivo && !grupoDesbloqueado(idx),
              'grupo-error':      intentoEnvio && esRequerido(grupo) && !tieneSeleccionValida(grupo)
            }
          ]"
          :ref="el => { if (el) grupoRefs[grupo.id] = el }"
        >
          <!-- Header: siempre visible, clickeable para re-abrir o volver -->
          <div
            class="grupo-header"
            @click="toggleGrupo(idx)"
            :style="idx > pasoActivo && !grupoDesbloqueado(idx) ? 'cursor:default' : 'cursor:pointer'"
          >
            <div class="grupo-titulo-row">
              <span :class="['grupo-step', { 'step-done': idx < pasoActivo && tieneSeleccionValida(grupo), 'step-activo': idx === pasoActivo }]">
                <svg v-if="idx < pasoActivo && tieneSeleccionValida(grupo)" width="12" height="12" viewBox="0 0 12 12">
                  <polyline points="2,6 5,9 10,3" stroke="white" stroke-width="2.2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <template v-else>{{ idx + 1 }}</template>
              </span>
              <span class="grupo-nombre">{{ grupo.nombre }}</span>
              <span :class="esRequerido(grupo) ? 'badge-req' : 'badge-opc'">
                {{ esRequerido(grupo) ? 'Requerido' : 'Opcional' }}
              </span>
              <span :class="['grupo-chevron', { 'chevron-abierto': idx === pasoActivo }]">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <polyline points="6 9 12 15 18 9"/>
                </svg>
              </span>
            </div>

            <!-- Resumen de selección cuando está colapsado -->
            <div v-if="idx !== pasoActivo && tieneSeleccionValida(grupo)" class="grupo-resumen">
              {{ resumenGrupo(grupo) }}
            </div>

            <!-- Error -->
            <div v-if="intentoEnvio && esRequerido(grupo) && !tieneSeleccionValida(grupo)" class="grupo-error-msg">
              {{ grupo.tipo === 'checkbox'
                  ? `Elige al menos ${grupo.min_selecciones || 1}`
                  : 'Debes elegir una opción' }}
            </div>
          </div>

          <!-- Body: solo visible cuando es el paso activo -->
          <div v-show="idx === pasoActivo" class="grupo-body">

            <!-- Hint para checkbox -->
            <div v-if="grupo.tipo === 'checkbox'" class="grupo-hint">
              <template v-if="grupo.min_selecciones > 0 && maxEfectivo(grupo) > grupo.min_selecciones">
                Elige entre {{ grupo.min_selecciones }} y {{ maxEfectivo(grupo) }}
              </template>
              <template v-else-if="grupo.min_selecciones > 0">
                Elige {{ grupo.min_selecciones }}
              </template>
              <template v-else>
                Elige hasta {{ maxEfectivo(grupo) }}
              </template>
              <template v-if="cantCheck(grupo) > 0">
                &nbsp;·&nbsp;<strong>{{ cantCheck(grupo) }}</strong> elegido{{ cantCheck(grupo) !== 1 ? 's' : '' }}
              </template>
            </div>

            <!-- Opciones radio -->
            <div v-if="grupo.tipo === 'radio'" class="opciones-lista">
              <button
                v-for="op in grupo.opciones"
                :key="op.id"
                :class="['opcion-row', { selected: seleccionRadio[grupo.id] === op.id }]"
                @click="seleccionarRadio(grupo.id, op.id)"
              >
                <span class="opcion-indicator radio">
                  <span v-if="seleccionRadio[grupo.id] === op.id" class="indicator-inner" />
                </span>
                <span class="opcion-nombre">{{ op.nombre }}</span>
                <span v-if="Number(op.precio_extra) > 0" class="opcion-extra">
                  +${{ Number(op.precio_extra).toFixed(2) }}
                </span>
              </button>
            </div>

            <!-- Opciones checkbox -->
            <div v-else class="opciones-lista">
              <button
                v-for="op in grupo.opciones"
                :key="op.id"
                :class="[
                  'opcion-row',
                  { selected: esCheck(grupo.id, op.id) },
                  { 'opcion-disabled': !esCheck(grupo.id, op.id) && cantCheck(grupo) >= maxEfectivo(grupo) }
                ]"
                @click="toggleCheck(grupo, op.id)"
              >
                <span :class="['opcion-indicator', 'check', { checked: esCheck(grupo.id, op.id) }]">
                  <svg v-if="esCheck(grupo.id, op.id)" width="12" height="12" viewBox="0 0 12 12">
                    <polyline points="2,6 5,9 10,3" stroke="white" stroke-width="2" fill="none"
                      stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </span>
                <span class="opcion-nombre">{{ op.nombre }}</span>
                <span v-if="Number(op.precio_extra) > 0" class="opcion-extra">
                  +${{ Number(op.precio_extra).toFixed(2) }}
                </span>
              </button>
            </div>


          </div><!-- /.grupo-body -->
        </div>

        <!-- Observación libre -->
        <div class="obs-section">
          <label class="obs-label">Observaciones (opcional)</label>
          <textarea
            :value="observacion"
            @input="observacion = ucfirst($event.target.value)"
            class="obs-input"
            rows="2"
            placeholder="Sin cebolla, extra salsa, bien cocido..."
          ></textarea>
        </div>

      </div><!-- /.modal-scroll -->

      <!-- ── Sticky footer CTA ── -->
      <div class="modal-footer">
        <button
          :class="['btn-agregar', { 'btn-agregar--listo': puedeAgregar }]"
          @click="emitirAgregar"
        >
          <span>+ Agregar al carrito</span>
          <span class="btn-precio">${{ precioTotal.toFixed(2) }}</span>
        </button>
      </div>

    </div><!-- /.modal-panel -->
  </div><!-- /.modal-overlay -->

</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { mdiRefresh } from '@mdi/js'
import { ucfirst } from '../../utils/ucfirst.js'
import ModelViewer3D from './ModelViewer3D.vue'
import SvgIcon from '../SvgIcon.vue'

const props = defineProps({
  producto: { type: Object, required: true }
})

const emit = defineEmits(['close', 'agregar', 'ir-categoria'])

// ── Estado ──
const seleccionRadio   = reactive({})
const seleccionCheck   = reactive({})
const observacion      = ref('')
const intentoEnvio     = ref(false)
const grupoRefs        = {}
const pasoActivo       = ref(0)

// ── Helpers ──
const esRequerido = (grupo) => !!(grupo.obligatorio || grupo.requerido) || (grupo.min_selecciones > 0)

const maxEfectivo = (grupo) => {
  const controlador = props.producto.grupos.find(
    g => g.max_dinamico_grupo_id === grupo.id && g.tipo === 'radio'
  )
  if (controlador) {
    const selId = seleccionRadio[controlador.id]
    if (selId != null) {
      const op = controlador.opciones.find(o => o.id === selId)
      if (op?.max_override != null) return op.max_override
    }
  }
  return grupo.max_selecciones
}

const cantCheck      = (grupo) => (seleccionCheck[grupo.id] || []).length
const esCheck        = (grupoId, opId) => (seleccionCheck[grupoId] || []).includes(opId)

const tieneSeleccionValida = (grupo) => {
  if (grupo.tipo === 'radio') return seleccionRadio[grupo.id] != null
  const cnt = cantCheck(grupo)
  const min = grupo.min_selecciones > 0 ? grupo.min_selecciones : (esRequerido(grupo) ? 1 : 0)
  return cnt >= min
}

const grupoDesbloqueado = (idx) => {
  for (let i = 0; i < idx; i++) {
    const g = props.producto.grupos[i]
    if (esRequerido(g) && !tieneSeleccionValida(g)) return false
  }
  return true
}

const resumenGrupo = (grupo) => {
  if (grupo.tipo === 'radio') {
    const id = seleccionRadio[grupo.id]
    return grupo.opciones?.find(o => o.id === id)?.nombre || ''
  }
  const ids = seleccionCheck[grupo.id] || []
  return ids.map(id => grupo.opciones?.find(o => o.id === id)?.nombre).filter(Boolean).join(', ')
}

// ── Navegación acordeón ──
const toggleGrupo = (idx) => {
  if (idx === pasoActivo.value) return
  if (!grupoDesbloqueado(idx)) return
  pasoActivo.value = idx
}

const avanzarSiguiente = (idx) => {
  const next = idx + 1
  if (next < props.producto.grupos.length) {
    setTimeout(() => { pasoActivo.value = next }, 220)
  }
}

// ── Mutaciones ──
const seleccionarRadio = (grupoId, opId) => {
  const grupo = props.producto.grupos.find(g => g.id === grupoId)
  // Grupo opcional: tocar la opción ya seleccionada la desmarca
  if (seleccionRadio[grupoId] === opId && !esRequerido(grupo)) {
    seleccionRadio[grupoId] = null
    return
  }
  seleccionRadio[grupoId] = opId
  const idx = props.producto.grupos.findIndex(g => g.id === grupoId)
  if (idx === pasoActivo.value) {
    avanzarSiguiente(idx)
  }
}

const toggleCheck = (grupo, opId) => {
  if (!seleccionCheck[grupo.id]) seleccionCheck[grupo.id] = []
  const arr = seleccionCheck[grupo.id]
  const pos = arr.indexOf(opId)

  if (pos !== -1) {
    arr.splice(pos, 1)
  } else {
    if (arr.length >= maxEfectivo(grupo)) return
    arr.push(opId)
  }

  // Auto-avanzar cuando se alcanza el máximo (o cuando max=1, comportamiento radio)
  const max = maxEfectivo(grupo)
  const grupoIdx = props.producto.grupos.findIndex(g => g.id === grupo.id)
  if (
    max > 0 &&
    grupoIdx === pasoActivo.value &&
    arr.length >= max &&
    grupoIdx < props.producto.grupos.length - 1
  ) {
    avanzarSiguiente(grupoIdx)
  }
}

// ── Opciones seleccionadas como array plano ──
const opcionesSeleccionadas = computed(() => {
  const result = []
  for (const grupo of props.producto.grupos) {
    if (grupo.tipo === 'radio') {
      const id = seleccionRadio[grupo.id]
      if (id != null) {
        const op = grupo.opciones.find(o => o.id === id)
        if (op) result.push({
          grupo_id: grupo.id, grupo_nombre: grupo.nombre,
          opcion_id: op.id,   opcion_nombre: op.nombre,
          precio_extra: Number(op.precio_extra) || 0
        })
      }
    } else {
      for (const id of (seleccionCheck[grupo.id] || [])) {
        const op = grupo.opciones.find(o => o.id === id)
        if (op) result.push({
          grupo_id: grupo.id, grupo_nombre: grupo.nombre,
          opcion_id: op.id,   opcion_nombre: op.nombre,
          precio_extra: Number(op.precio_extra) || 0
        })
      }
    }
  }
  return result
})

const extraTotal  = computed(() => opcionesSeleccionadas.value.reduce((s, o) => s + o.precio_extra, 0))
const precioTotal = computed(() => Number(props.producto.precio) + extraTotal.value)

const puedeAgregar = computed(() =>
  props.producto.grupos.every(g => !esRequerido(g) || tieneSeleccionValida(g))
)

// ── Flujo principal ──
const emitirAgregar = () => {
  if (!puedeAgregar.value) {
    intentoEnvio.value = true
    const idxError = props.producto.grupos.findIndex(g => esRequerido(g) && !tieneSeleccionValida(g))
    if (idxError !== -1) {
      pasoActivo.value = idxError
      const grupo = props.producto.grupos[idxError]
      if (grupoRefs[grupo.id]) {
        setTimeout(() => grupoRefs[grupo.id].scrollIntoView({ behavior: 'smooth', block: 'center' }), 50)
      }
    }
    return
  }

  emit('agregar', {
    producto:    props.producto,
    observacion: observacion.value.trim(),
    opciones:    opcionesSeleccionadas.value
  })
  emit('close')
}
</script>

<style scoped>
/* ── Overlay ── */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.65);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.2s ease-out;
  backdrop-filter: blur(2px);
}

@keyframes fadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}

/* ── Panel ── */
.modal-panel {
  display: flex;
  flex-direction: column;
  background: var(--card-bg, #fff);
  border-radius: 24px 24px 0 0;
  width: 100%;
  max-width: 640px;
  max-height: 92vh;
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  overflow: hidden;
}

@keyframes slideUp {
  from { transform: translateY(40px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}

/* ── Top bar ── */
.modal-topbar {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 16px 16px 14px;
  border-bottom: 1px solid var(--divider, #f0f0f0);
  background: var(--card-bg, #fff);
}

.topbar-nombre {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-main, #222);
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.btn-close {
  flex-shrink: 0;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: rgba(0,0,0,0.07);
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-main, #333);
  transition: background 0.2s;
}
.btn-close:hover { background: rgba(0,0,0,0.14); }

/* ── Scroll ── */
.modal-scroll {
  flex: 1;
  overflow-y: auto;
  overscroll-behavior: contain;
  -webkit-overflow-scrolling: touch;
}
.modal-scroll::-webkit-scrollbar { width: 4px; }
.modal-scroll::-webkit-scrollbar-thumb { background: var(--divider, #ddd); border-radius: 2px; }

/* ── Visual (3D o foto) ── */
.modal-visual { position: relative; width: 100%; height: 280px; overflow: hidden; background: var(--accent-light, #f5f5f5); }
.modal-foto   { width: 100%; height: 100%; object-fit: cover; }
.hint-3d {
  position: absolute; top: 12px; left: 50%; transform: translateX(-50%);
  background: rgba(0,0,0,0.45); color: #fff;
  padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; white-space: nowrap;
  display: flex; align-items: center; gap: 4px;
}
.hint-ar-arrow {
  position: absolute; bottom: 62px; left: 50%; transform: translateX(-50%);
  color: rgba(255,255,255,0.85); font-size: 0.72rem;
  text-shadow: 0 1px 4px rgba(0,0,0,0.5); white-space: nowrap;
}

/* ── Precio dinámico ── */
.precio-dinamico {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--accent-light, #f8f8f8);
  padding: 14px 18px;
  border-bottom: 1px solid var(--divider, #f0f0f0);
}
.precio-main   { display: flex; align-items: baseline; gap: 8px; }
.precio-label  { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-sub, #aaa); font-weight: 600; }
.precio-valor  { font-size: 1.6rem; font-weight: 800; color: var(--accent, #FF6B35); line-height: 1; }
.precio-detalle { font-size: 0.78rem; color: var(--text-sub, #aaa); text-align: right; }

/* ── Descripción ── */
.prod-desc { padding: 14px 18px 10px; font-size: 0.9rem; color: var(--text-sub, #777); line-height: 1.5; margin: 0; }

/* ── Grupos — acordeón ── */
.grupo-section {
  border-top: 1px solid var(--divider, #f0f0f0);
  transition: background 0.2s;
}
.grupo-section.grupo-completado > .grupo-header {
  background: rgba(34, 197, 94, 0.04);
}
.grupo-section.grupo-bloqueado { opacity: 0.42; }
.grupo-section.grupo-error > .grupo-header {
  background: rgba(220, 38, 38, 0.04);
  border-left: 3px solid rgba(220, 38, 38, 0.4);
}

/* ── Header del grupo ── */
.grupo-header {
  padding: 14px 18px 10px;
  user-select: none;
  transition: background 0.15s;
}

.grupo-titulo-row { display: flex; align-items: center; gap: 8px; }

/* Badge de paso */
.grupo-step {
  flex-shrink: 0;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: var(--divider, #e8e8e8);
  color: var(--text-sub, #888);
  font-size: 0.72rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.25s, color 0.25s;
}
.grupo-step.step-activo { background: var(--accent, #FF6B35); color: #fff; }
.grupo-step.step-done   { background: #22c55e; color: #fff; }

.grupo-nombre { flex: 1; font-size: 0.95rem; font-weight: 700; color: var(--text-main, #222); }

.badge-req, .badge-opc {
  font-size: 0.7rem; font-weight: 700;
  padding: 2px 8px; border-radius: 20px;
}
.badge-req { background: rgba(220, 38, 38, 0.1); color: #dc2626; }
.badge-opc { background: rgba(0,0,0,0.06); color: var(--text-sub, #888); }

.grupo-chevron {
  flex-shrink: 0;
  color: var(--text-sub, #bbb);
  transition: transform 0.25s;
  display: flex; align-items: center;
}
.grupo-chevron.chevron-abierto { transform: rotate(180deg); }

/* Resumen cuando está colapsado */
.grupo-resumen {
  margin-top: 4px;
  padding-left: 30px;
  font-size: 0.82rem;
  color: var(--accent, #FF6B35);
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.grupo-hint { font-size: 0.78rem; color: var(--text-sub, #aaa); padding: 0 18px 8px; }

.grupo-error-msg {
  margin-top: 4px;
  padding-left: 30px;
  font-size: 0.78rem;
  color: #dc2626;
  font-weight: 600;
}

/* ── Body expandible ── */
.grupo-body { padding: 0 18px 14px; }

/* ── Lista de opciones ── */
.opciones-lista { display: flex; flex-direction: column; gap: 6px; }

.opcion-row {
  display: flex; align-items: center; gap: 12px;
  padding: 11px 14px;
  background: var(--card-bg, #fff);
  border: 1.5px solid var(--divider, #e8e8e8);
  border-radius: 10px;
  cursor: pointer; text-align: left; width: 100%;
  transition: border-color 0.15s, background 0.15s;
}
.opcion-row:hover:not(.opcion-disabled) {
  border-color: var(--accent, #FF6B35);
  background: var(--accent-light, #fff5f0);
}
.opcion-row.selected {
  border-color: var(--accent, #FF6B35);
  background: var(--accent-light, #fff5f0);
}
.opcion-row.opcion-disabled { opacity: 0.45; cursor: default; }

.opcion-indicator {
  flex-shrink: 0; width: 20px; height: 20px; border-radius: 50%;
  border: 2px solid var(--divider, #ccc);
  display: flex; align-items: center; justify-content: center;
  transition: border-color 0.15s, background 0.15s;
}
.opcion-row.selected .opcion-indicator { border-color: var(--accent, #FF6B35); }

.indicator-inner { width: 10px; height: 10px; border-radius: 50%; background: var(--accent, #FF6B35); }

.opcion-indicator.check { border-radius: 5px; }
.opcion-indicator.check.checked { background: var(--accent, #FF6B35); border-color: var(--accent, #FF6B35); }

.opcion-nombre { flex: 1; font-size: 0.9rem; color: var(--text-main, #333); font-weight: 500; }
.opcion-extra  { font-size: 0.85rem; font-weight: 700; color: var(--accent, #FF6B35); white-space: nowrap; }


/* ── Observación ── */
.obs-section {
  padding: 14px 18px 18px;
  border-top: 1px solid var(--divider, #f0f0f0);
  display: flex; flex-direction: column; gap: 6px;
}
.obs-label { font-size: 0.8rem; font-weight: 600; color: var(--text-sub, #888); text-transform: uppercase; letter-spacing: 0.5px; }
.obs-input {
  width: 100%; box-sizing: border-box;
  padding: 10px 12px;
  border: 1.5px solid var(--divider, #e0e0e0);
  border-radius: 10px;
  font-size: 0.88rem; font-family: inherit; resize: none; outline: none;
  background: var(--card-bg, #fafafa); color: var(--text-main, #333);
  transition: border-color 0.2s;
}
.obs-input:focus { border-color: var(--accent, #FF6B35); }

/* ── Footer sticky ── */
.modal-footer {
  flex-shrink: 0;
  padding: 12px 16px 16px;
  border-top: 1px solid var(--divider, #f0f0f0);
  background: var(--card-bg, #fff);
}

.btn-agregar {
  width: 100%;
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 20px;
  background: rgba(0,0,0,0.12);
  color: rgba(0,0,0,0.35);
  border: none; border-radius: 14px;
  font-size: 1rem; font-weight: 700;
  cursor: pointer;
  transition: background 0.25s, color 0.25s, transform 0.1s;
}

/* Activo cuando todos los requeridos están llenos */
.btn-agregar.btn-agregar--listo {
  background: var(--accent, #FF6B35);
  color: #fff;
}
.btn-agregar.btn-agregar--listo:active { transform: scale(0.98); }

.btn-precio { font-size: 1.05rem; font-weight: 800; }

/* ── Desktop ── */
@media (min-width: 640px) {
  .modal-overlay { align-items: center; padding: 20px; }
  .modal-panel   { border-radius: 20px; max-height: 88vh; }
}
</style>
