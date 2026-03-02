<template>
  <div class="producto-card" @click="$emit('click')">
    <!-- Imagen -->
    <div class="card-imagen">
      <img
        v-if="producto.foto_principal"
        :src="producto.foto_principal"
        :alt="producto.nombre"
        class="card-img"
        loading="lazy"
      />
      <div v-else class="img-placeholder">
        <span>🍽️</span>
      </div>

      <!-- Badges superpuestos -->
      <div class="badges">
        <span v-if="producto.tiene_ar" class="badge badge-3d">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
          </svg>
          3D/AR
        </span>
        <span v-if="producto.es_destacado" class="badge badge-dest">⭐ Destacado</span>
      </div>
    </div>

    <!-- Info del platillo -->
    <div class="card-info">
      <div class="card-top">
        <h3 class="card-nombre">{{ producto.nombre }}</h3>
        <p v-if="producto.descripcion" class="card-desc">
          {{ truncar(producto.descripcion, 72) }}
        </p>
      </div>

      <div class="card-bottom">
        <span class="card-precio">${{ Number(producto.precio).toFixed(2) }}</span>
        <button class="btn-ver" aria-label="Ver detalles del platillo">
          {{ producto.tiene_ar ? 'Ver en 3D' : 'Ver más' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  producto: { type: Object, required: true }
})

defineEmits(['click'])

const truncar = (texto, len) =>
  texto && texto.length > len ? texto.substring(0, len) + '…' : texto
</script>

<style scoped>
.producto-card {
  display: flex;
  gap: 14px;
  background: var(--card-bg, #fff);
  border: 1px solid var(--card-border, #eee);
  border-radius: 14px;
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  padding: 0;
}

.producto-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

/* ── Imagen ── */
.card-imagen {
  position: relative;
  width: 110px;
  min-width: 110px;
  height: 110px;
  flex-shrink: 0;
  overflow: hidden;
  background: var(--accent-light, #f5f5f5);
}

.card-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.producto-card:hover .card-img {
  transform: scale(1.06);
}

.img-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  opacity: 0.35;
}

/* ── Badges ── */
.badges {
  position: absolute;
  top: 6px;
  left: 6px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 3px 7px;
  border-radius: 6px;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.2px;
  white-space: nowrap;
}

.badge svg {
  stroke: currentColor;
  fill: none;
  stroke-width: 2;
}

.badge-3d {
  background: var(--accent, #FF6B35);
  color: #fff;
}

.badge-dest {
  background: rgba(255, 193, 7, 0.92);
  color: #5d3a00;
}

/* ── Info ── */
.card-info {
  flex: 1;
  padding: 14px 14px 14px 2px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-width: 0;
}

.card-top {
  flex: 1;
}

.card-nombre {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-main, #333);
  margin-bottom: 5px;
  line-height: 1.25;
}

.card-desc {
  font-size: 0.82rem;
  color: var(--text-sub, #888);
  line-height: 1.45;
  margin: 0;
}

/* ── Bottom (precio + botón) ── */
.card-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 10px;
  gap: 8px;
}

.card-precio {
  font-size: 1.2rem;
  font-weight: 800;
  color: var(--accent, #FF6B35);
  letter-spacing: -0.3px;
}

.btn-ver {
  background: var(--accent, #FF6B35);
  color: #fff;
  border: none;
  padding: 7px 14px;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
  transition: opacity 0.2s;
}

.btn-ver:hover {
  opacity: 0.85;
}

/* ── Responsive ── */
@media (max-width: 360px) {
  .card-imagen {
    width: 90px;
    min-width: 90px;
    height: 90px;
  }

  .card-nombre {
    font-size: 0.92rem;
  }
}
</style>
