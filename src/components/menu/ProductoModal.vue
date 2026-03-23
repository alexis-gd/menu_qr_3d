<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-panel">
      <!-- Botón cerrar — sticky sobre el visual -->
      <div class="close-sticky">
        <button class="btn-close" @click="$emit('close')" aria-label="Cerrar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>

      <!-- Sección visual (3D o foto) -->
      <div class="modal-visual">
        <ModelViewer3D
          v-if="producto.tiene_ar"
          :modelo-url="producto.modelo_glb_url"
        />
        <img
          v-else-if="producto.foto_principal"
          :src="producto.foto_principal"
          :alt="producto.nombre"
          class="modal-foto"
        />
        <div v-else class="modal-placeholder">
          <SvgIcon :path="mdiSilverwareForkKnife" :size="80" />
        </div>

        <!-- Indicador de 3D -->
        <div v-if="producto.tiene_ar" class="hint-3d">
          <SvgIcon :path="mdiRefresh" :size="14" />
          <span>Arrastra para rotar</span>
        </div>

        <!-- Flecha hacia el botón AR -->
        <div v-if="producto.tiene_ar" class="hint-ar-arrow">
          Toca el botón para verlo en tu espacio real ↓
        </div>
      </div>

      <!-- Información del platillo -->
      <div class="modal-info">
        <div class="modal-header-info">
          <h2 class="modal-nombre">{{ producto.nombre }}</h2>
          <span v-if="producto.tiene_ar" class="pill-3d">3D/AR disponible</span>
        </div>

        <p v-if="producto.descripcion" class="modal-desc">{{ producto.descripcion }}</p>

        <div class="modal-precio-row">
          <div class="precio-bloque">
            <span class="precio-label">Precio</span>
            <span class="precio-valor">${{ Number(producto.precio).toFixed(2) }}</span>
          </div>
          <div v-if="producto.es_destacado" class="dest-badge">
          <SvgIcon :path="mdiStar" :size="14" /> Destacado
        </div>
        </div>

        <div v-if="producto.tiene_ar" class="ar-info">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
          </svg>
          <span>Modelo 3D interactivo · Rota, acerca y aleja · AR en móvil</span>
        </div>

        <!-- Chip de estado bloqueado -->
        <div v-if="noDisponible" class="chip-estado chip-no-disponible">
          Sin stock — no disponible por el momento
        </div>
        <div v-else-if="esProximamente" class="chip-estado chip-proximamente">
          Próximamente disponible
        </div>

        <!-- Agregar al carrito (solo si no está bloqueado) -->
        <div v-if="pedidosActivos && !bloqueado" class="carrito-section">
          <textarea
            :value="observacion"
            @input="observacion = ucfirst($event.target.value)"
            class="observacion-input"
            rows="2"
            placeholder="Observaciones: sin cebolla, bien cocido, extra salsa... (opcional)"
          ></textarea>
          <button class="btn-primary btn-agregar-carrito" @click="emitirAgregar">
            + Agregar al carrito
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { mdiSilverwareForkKnife, mdiRefresh, mdiStar } from '@mdi/js'
import ModelViewer3D from './ModelViewer3D.vue'
import SvgIcon from '../SvgIcon.vue'
import { ucfirst } from '../../utils/ucfirst.js'

const props = defineProps({
  producto: { type: Object, required: true },
  pedidosActivos: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'agregar'])

const observacion = ref('')

const noDisponible = computed(() =>
  props.producto.stock !== null &&
  props.producto.stock !== undefined &&
  props.producto.stock === 0
)
const esProximamente = computed(() => props.producto.disponible === false || props.producto.disponible === 0)
const bloqueado = computed(() => noDisponible.value || esProximamente.value)

const emitirAgregar = () => {
  emit('agregar', { producto: props.producto, observacion: observacion.value.trim() })
  observacion.value = ''
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
  padding: 0;
  animation: fadeIn 0.2s ease-out;
  backdrop-filter: blur(2px);
}

@keyframes fadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}

/* ── Panel del modal ── */
.modal-panel {
  position: relative;
  background: var(--card-bg, #fff);
  border-radius: 24px 24px 0 0;
  width: 100%;
  max-width: 640px;
  max-height: 92vh;
  overflow-y: auto;
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
  from { transform: translateY(40px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}

/* Scrollbar estilizado */
.modal-panel::-webkit-scrollbar { width: 4px; }
.modal-panel::-webkit-scrollbar-thumb {
  background: var(--divider, #ddd);
  border-radius: 2px;
}

/* ── Cerrar — sticky sobre el visual ── */
.close-sticky {
  position: sticky;
  top: 0;
  z-index: 10;
  display: flex;
  justify-content: flex-end;
  padding: 12px 16px 0;
  pointer-events: none;
  margin-bottom: -56px; /* se superpone sobre el visual, no consume espacio */
}

.btn-close {
  pointer-events: all;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(0,0,0,0.35);
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  transition: background 0.2s;
  backdrop-filter: blur(4px);
}

.btn-close:hover {
  background: rgba(0,0,0,0.5);
}

/* ── Visual (3D / foto) ── */
.modal-visual {
  position: relative;
  width: 100%;
  height: 300px;
  background: var(--accent-light, #f5f5f5);
  overflow: hidden;
}

.modal-foto {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.modal-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--accent, #FF6B35);
  opacity: 0.2;
}

.hint-3d {
  position: absolute;
  top: 12px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(0,0,0,0.45);
  color: #fff;
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
  pointer-events: none;
  display: flex;
  align-items: center;
  gap: 5px;
}

.hint-ar-arrow {
  position: absolute;
  bottom: 62px;
  left: 50%;
  transform: translateX(-50%);
  color: rgba(255,255,255,0.85);
  font-size: 0.72rem;
  font-weight: 600;
  white-space: nowrap;
  pointer-events: none;
  text-shadow: 0 1px 4px rgba(0,0,0,0.5);
}

/* ── Info ── */
.modal-info {
  padding: 20px 20px 32px;
}

.modal-header-info {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.modal-nombre {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--text-main, #222);
  line-height: 1.2;
  letter-spacing: -0.3px;
  flex: 1;
}

.pill-3d {
  flex-shrink: 0;
  display: inline-block;
  background: var(--accent, #FF6B35);
  color: #fff;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 700;
  white-space: nowrap;
  margin-top: 4px;
}

.modal-desc {
  font-size: 0.95rem;
  color: var(--text-sub, #777);
  line-height: 1.6;
  margin-bottom: 20px;
}

/* ── Precio ── */
.modal-precio-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--accent-light, #f8f8f8);
  border-radius: 12px;
  padding: 16px 18px;
  margin-bottom: 16px;
}

.precio-bloque {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.precio-label {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: var(--text-sub, #aaa);
  font-weight: 600;
}

.precio-valor {
  font-size: 2rem;
  font-weight: 800;
  color: var(--accent, #FF6B35);
  line-height: 1;
  letter-spacing: -0.5px;
}

.dest-badge {
  background: rgba(255, 193, 7, 0.18);
  color: #856304;
  padding: 6px 12px;
  border-radius: 10px;
  font-size: 0.82rem;
  font-weight: 700;
}

/* ── AR info ── */
.ar-info {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--text-sub, #aaa);
  font-size: 0.8rem;
  line-height: 1.4;
}

.ar-info svg {
  flex-shrink: 0;
  stroke: var(--accent, #FF6B35);
}

/* ── Responsive desktop ── */
@media (min-width: 640px) {
  .modal-overlay {
    align-items: center;
    padding: 20px;
  }

  .modal-panel {
    border-radius: 20px;
    max-height: 88vh;
  }

  .modal-visual {
    height: 340px;
    border-radius: 20px 20px 0 0;
  }

  .modal-nombre {
    font-size: 1.7rem;
  }
}

/* ── Carrito section ── */
.carrito-section {
  margin-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  border-top: 1px solid var(--divider, #f0f0f0);
  padding-top: 16px;
}

.observacion-input {
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

.observacion-input:focus {
  border-color: var(--accent, #FF6B35);
}

/* .btn-agregar-carrito extiende .btn-primary (global en theme.css) — solo sobreescribe tamaño */
.btn-agregar-carrito {
  width: 100%;
  padding: 13px;
  border-radius: 12px;
  font-size: 1rem;
}

/* ── Chips de estado bloqueado ── */
.chip-estado {
  margin-top: 14px;
  padding: 10px 16px;
  border-radius: 10px;
  font-size: 0.88rem;
  font-weight: 600;
  text-align: center;
}

.chip-no-disponible {
  background: rgba(0, 0, 0, 0.06);
  color: var(--text-sub, #888);
}

.chip-proximamente {
  background: color-mix(in srgb, var(--accent, #FF6B35) 12%, transparent);
  color: var(--accent, #FF6B35);
}
</style>
