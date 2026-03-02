<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-panel">
      <!-- Botón cerrar -->
      <button class="btn-close" @click="$emit('close')" aria-label="Cerrar">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>

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
          <span>🍽️</span>
        </div>

        <!-- Indicador de 3D -->
        <div v-if="producto.tiene_ar" class="hint-3d">
          <span>🔄 Arrastra para rotar</span>
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
          <div v-if="producto.es_destacado" class="dest-badge">⭐ Destacado</div>
        </div>

        <div v-if="producto.tiene_ar" class="ar-info">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
          </svg>
          <span>Modelo 3D interactivo · Rota, acerca y aleja · AR en móvil</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import ModelViewer3D from './ModelViewer3D.vue'

defineProps({
  producto: { type: Object, required: true }
})

defineEmits(['close'])
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

/* ── Cerrar ── */
.btn-close {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(0,0,0,0.08);
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-main, #333);
  z-index: 10;
  transition: background 0.2s;
}

.btn-close:hover {
  background: rgba(0,0,0,0.15);
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
  font-size: 5rem;
  opacity: 0.2;
}

.hint-3d {
  position: absolute;
  bottom: 12px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(0,0,0,0.55);
  color: #fff;
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
  pointer-events: none;
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
</style>
