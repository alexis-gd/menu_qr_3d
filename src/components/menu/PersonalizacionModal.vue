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

        <!-- Imagen compacta -->
        <div v-if="producto.foto_principal" class="modal-visual">
          <img :src="producto.foto_principal" :alt="producto.nombre" class="modal-foto" />
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

        <!-- ── Grupos de opciones ── -->
        <div
          v-for="grupo in producto.grupos"
          :key="grupo.id"
          :class="['grupo-section', { 'grupo-error': intentoEnvio && grupo.requerido && !tieneSeleccion(grupo) }]"
          :ref="el => { if (el) grupoRefs[grupo.id] = el }"
        >
          <div class="grupo-header">
            <div class="grupo-titulo-row">
              <span class="grupo-nombre">{{ grupo.nombre }}</span>
              <span :class="grupo.requerido ? 'badge-req' : 'badge-opc'">
                {{ grupo.requerido ? 'Requerido' : 'Opcional' }}
              </span>
            </div>
            <div v-if="grupo.tipo === 'checkbox'" class="grupo-hint">
              Elige hasta {{ maxEfectivo(grupo) }}
              <template v-if="cantCheck(grupo)">
                · {{ cantCheck(grupo) }} elegido{{ cantCheck(grupo) !== 1 ? 's' : '' }}
              </template>
            </div>
            <div v-if="intentoEnvio && grupo.requerido && !tieneSeleccion(grupo)" class="grupo-error-msg">
              Debes elegir una opción
            </div>
          </div>

          <!-- Opciones tipo radio -->
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

          <!-- Opciones tipo checkbox -->
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

        <!-- Aviso complemento -->
        <div v-if="producto.aviso_complemento" class="aviso-complemento">
          <span class="aviso-texto">{{ producto.aviso_complemento }}</span>
          <button
            v-if="producto.aviso_categoria_id"
            class="aviso-btn"
            @click="$emit('ir-categoria', producto.aviso_categoria_id)"
          >
            Ver →
          </button>
        </div>

      </div><!-- /.modal-scroll -->

      <!-- ── Sticky footer CTA ── -->
      <div class="modal-footer">
        <button class="btn-agregar" @click="emitirAgregar">
          <span>+ Agregar al carrito</span>
          <span class="btn-precio">${{ precioTotal.toFixed(2) }}</span>
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { ucfirst } from '../../utils/ucfirst.js'

const props = defineProps({
  producto: { type: Object, required: true }
})

const emit = defineEmits(['close', 'agregar', 'ir-categoria'])

// ── Estado ──
const seleccionRadio = reactive({})     // { [grupoId]: opcionId }
const seleccionCheck = reactive({})     // { [grupoId]: number[] }
const observacion    = ref('')
const intentoEnvio   = ref(false)
const grupoRefs      = {}

// ── Max efectivo (puede ser controlado por un grupo radio externo) ──
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

// ── Helpers ──
const cantCheck      = (grupo) => (seleccionCheck[grupo.id] || []).length
const esCheck        = (grupoId, opId) => (seleccionCheck[grupoId] || []).includes(opId)
const tieneSeleccion = (grupo) =>
  grupo.tipo === 'radio'
    ? seleccionRadio[grupo.id] != null
    : cantCheck(grupo) > 0

// ── Mutaciones ──
const seleccionarRadio = (grupoId, opId) => {
  seleccionRadio[grupoId] = opId
}

const toggleCheck = (grupo, opId) => {
  if (!seleccionCheck[grupo.id]) seleccionCheck[grupo.id] = []
  const arr = seleccionCheck[grupo.id]
  const idx = arr.indexOf(opId)
  if (idx !== -1) {
    arr.splice(idx, 1)
  } else {
    if (arr.length >= maxEfectivo(grupo)) return
    arr.push(opId)
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
  props.producto.grupos.filter(g => g.requerido).every(g => tieneSeleccion(g))
)

// ── Emitir ──
const emitirAgregar = () => {
  if (!puedeAgregar.value) {
    intentoEnvio.value = true
    const primerError = props.producto.grupos.find(g => g.requerido && !tieneSeleccion(g))
    if (primerError && grupoRefs[primerError.id]) {
      grupoRefs[primerError.id].scrollIntoView({ behavior: 'smooth', block: 'center' })
    }
    return
  }
  emit('agregar', {
    producto:    props.producto,
    observacion: observacion.value.trim(),
    opciones:    opcionesSeleccionadas.value
  })
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

/* ── Panel — flex column para sticky header/footer ── */
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

/* ── Top bar sticky ── */
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

/* ── Área scrollable ── */
.modal-scroll {
  flex: 1;
  overflow-y: auto;
  overscroll-behavior: contain;
  -webkit-overflow-scrolling: touch;
}

.modal-scroll::-webkit-scrollbar { width: 4px; }
.modal-scroll::-webkit-scrollbar-thumb {
  background: var(--divider, #ddd);
  border-radius: 2px;
}

/* ── Imagen compacta ── */
.modal-visual {
  width: 100%;
  height: 180px;
  overflow: hidden;
  background: var(--accent-light, #f5f5f5);
}

.modal-foto {
  width: 100%;
  height: 100%;
  object-fit: cover;
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

.precio-main {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.precio-label {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: var(--text-sub, #aaa);
  font-weight: 600;
}

.precio-valor {
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--accent, #FF6B35);
  line-height: 1;
}

.precio-detalle {
  font-size: 0.78rem;
  color: var(--text-sub, #aaa);
  text-align: right;
}

/* ── Descripción ── */
.prod-desc {
  padding: 14px 18px 0;
  font-size: 0.9rem;
  color: var(--text-sub, #777);
  line-height: 1.5;
  margin: 0;
}

/* ── Grupos de opciones ── */
.grupo-section {
  margin: 14px 0 0;
  border-top: 1px solid var(--divider, #f0f0f0);
  padding: 14px 18px 6px;
  transition: background 0.2s;
}

.grupo-section.grupo-error {
  background: rgba(220, 38, 38, 0.04);
  border-top-color: rgba(220, 38, 38, 0.3);
}

.grupo-header {
  margin-bottom: 10px;
}

.grupo-titulo-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.grupo-nombre {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--text-main, #222);
}

.badge-req,
.badge-opc {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 20px;
}

.badge-req {
  background: rgba(220, 38, 38, 0.1);
  color: #dc2626;
}

.badge-opc {
  background: rgba(0,0,0,0.06);
  color: var(--text-sub, #888);
}

.grupo-hint {
  font-size: 0.78rem;
  color: var(--text-sub, #aaa);
}

.grupo-error-msg {
  font-size: 0.78rem;
  color: #dc2626;
  font-weight: 600;
  margin-top: 2px;
}

/* ── Lista de opciones ── */
.opciones-lista {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.opcion-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 14px;
  background: var(--card-bg, #fff);
  border: 1.5px solid var(--divider, #e8e8e8);
  border-radius: 10px;
  cursor: pointer;
  text-align: left;
  width: 100%;
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

.opcion-row.opcion-disabled {
  opacity: 0.45;
  cursor: default;
}

/* Indicadores radio / check */
.opcion-indicator {
  flex-shrink: 0;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 2px solid var(--divider, #ccc);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: border-color 0.15s, background 0.15s;
}

.opcion-row.selected .opcion-indicator {
  border-color: var(--accent, #FF6B35);
}

/* Radio inner dot */
.indicator-inner {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: var(--accent, #FF6B35);
}

/* Checkbox */
.opcion-indicator.check {
  border-radius: 5px;
}

.opcion-indicator.check.checked {
  background: var(--accent, #FF6B35);
  border-color: var(--accent, #FF6B35);
}

.opcion-nombre {
  flex: 1;
  font-size: 0.9rem;
  color: var(--text-main, #333);
  font-weight: 500;
}

.opcion-extra {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--accent, #FF6B35);
  white-space: nowrap;
}

/* ── Observación ── */
.obs-section {
  padding: 14px 18px 4px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.obs-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-sub, #888);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.obs-input {
  width: 100%;
  box-sizing: border-box;
  padding: 10px 12px;
  border: 1.5px solid var(--divider, #e0e0e0);
  border-radius: 10px;
  font-size: 0.88rem;
  font-family: inherit;
  resize: none;
  outline: none;
  background: var(--card-bg, #fafafa);
  color: var(--text-main, #333);
  transition: border-color 0.2s;
}

.obs-input:focus { border-color: var(--accent, #FF6B35); }

/* ── Aviso complemento ── */
.aviso-complemento {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin: 14px 18px 18px;
  padding: 12px 14px;
  background: var(--accent-light, #fff8f0);
  border: 1px solid var(--divider, #ffe0c8);
  border-radius: 10px;
}

.aviso-texto {
  font-size: 0.88rem;
  color: var(--text-main, #333);
  flex: 1;
}

.aviso-btn {
  flex-shrink: 0;
  padding: 6px 14px;
  background: var(--accent, #FF6B35);
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
}

/* ── Footer sticky ── */
.modal-footer {
  flex-shrink: 0;
  padding: 12px 16px 16px;
  border-top: 1px solid var(--divider, #f0f0f0);
  background: var(--card-bg, #fff);
}

.btn-agregar {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px;
  background: var(--accent, #FF6B35);
  color: #fff;
  border: none;
  border-radius: 14px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s, transform 0.1s;
}

.btn-agregar:active { transform: scale(0.98); }

.btn-precio {
  font-size: 1.05rem;
  font-weight: 800;
}

/* ── Desktop ── */
@media (min-width: 640px) {
  .modal-overlay {
    align-items: center;
    padding: 20px;
  }

  .modal-panel {
    border-radius: 20px;
    max-height: 88vh;
  }
}
</style>
