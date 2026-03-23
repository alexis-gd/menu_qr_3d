<template>
  <div class="producto-card" :class="{ 'card-bloqueada': bloqueado }" @click="$emit('click')">
    <!-- Imagen -->
    <div class="card-imagen" :class="{ 'img-grayscale': noDisponible }">
      <img
        v-if="producto.foto_principal"
        :src="thumbSrc"
        :alt="producto.nombre"
        class="card-img"
        loading="lazy"
        width="110"
        height="110"
        @error="$event.target.src = producto.foto_principal"
      />
      <div v-else class="img-placeholder">
        <SvgIcon :path="mdiSilverwareForkKnife" :size="36" />
      </div>

      <!-- Watermark del logo -->
      <div
        v-if="logoUrl && producto.foto_principal"
        class="watermark"
        :style="{ '--wm-url': `url(${logoUrl})` }"
      ></div>

      <!-- Overlay "No disponible" -->
      <div v-if="noDisponible" class="overlay-no-disponible">
        <span>Agotado</span>
      </div>

      <!-- Badge "Próximamente" -->
      <div v-else-if="esProximamente" class="badge-proximamente">
        Próximamente
      </div>

      <!-- Badge "Últimas piezas" -->
      <div v-else-if="stockBajo" class="badge-stock-bajo">
        Últimas {{ producto.stock }}
      </div>

      <!-- Badges 3D/AR y Destacado -->
      <div class="badges" v-if="!noDisponible">
        <span v-if="producto.tiene_ar" class="badge badge-3d">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
          </svg>
          3D/AR
        </span>
        <span v-if="producto.es_destacado" class="badge badge-dest">
          <SvgIcon :path="mdiStar" :size="11" /> Destacado
        </span>
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
        <div class="card-bottom-actions">
          <button class="btn-primary btn-ver" aria-label="Ver detalles del platillo">
            {{ producto.tiene_ar ? 'Ver en 3D' : 'Ver más' }}
          </button>
          <button
            v-if="pedidosActivos && !bloqueado"
            class="btn-agregar"
            @click.stop="$emit('agregar')"
            aria-label="Agregar al carrito"
          >+</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { mdiSilverwareForkKnife, mdiStar } from '@mdi/js'
import SvgIcon from '../SvgIcon.vue'

const props = defineProps({
  producto: { type: Object, required: true },
  pedidosActivos: { type: Boolean, default: false },
  logoUrl: { type: String, default: null },
  stockMinimoAviso: { type: Number, default: 5 }
})

defineEmits(['click', 'agregar'])

// Deriva la URL del thumbnail a partir de foto_principal:
// "https://...fotos/1/foto_1_0_123.webp" → "https://...fotos/1/thumb_foto_1_0_123.webp"
const thumbSrc = computed(() => {
  const url = props.producto.foto_principal
  if (!url) return null
  const idx = url.lastIndexOf('/')
  return url.slice(0, idx + 1) + 'thumb_' + url.slice(idx + 1)
})

const noDisponible  = computed(() => props.producto.stock !== null && props.producto.stock !== undefined && props.producto.stock === 0)
const esProximamente = computed(() => props.producto.disponible === false || props.producto.disponible === 0)
const stockBajo     = computed(() => {
  const s = props.producto.stock
  const min = props.stockMinimoAviso
  return min > 0 && s !== null && s !== undefined && s > 0 && s <= min
})
const bloqueado     = computed(() => noDisponible.value || esProximamente.value)

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

.card-bloqueada {
  opacity: 0.82;
}

/* ── Imagen ── */
.card-imagen {
  position: relative;
  width: 110px;
  min-width: 110px;
  min-height: 110px;
  align-self: stretch;
  flex-shrink: 0;
  overflow: hidden;
  background: var(--accent-light, #f5f5f5);
}

.img-grayscale .card-img {
  filter: grayscale(1);
}

.card-img {
  display: block;
  position: absolute;
  top: 0; left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.producto-card:hover .card-img {
  transform: scale(1.06);
}

.img-placeholder {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--accent, #FF6B35);
  opacity: 0.35;
}

/* ── Watermark ── */
.watermark {
  position: absolute;
  inset: 0;
  background-image: var(--wm-url);
  background-repeat: no-repeat;
  background-position: center;
  background-size: 40%;
  opacity: 0.15;
  pointer-events: none;
}

/* ── Overlay No Disponible ── */
.overlay-no-disponible {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.52);
  display: flex;
  align-items: center;
  justify-content: center;
}

.overlay-no-disponible span {
  color: #fff;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  text-align: center;
  padding: 0 6px;
}

/* ── Badge Próximamente ── */
.badge-proximamente {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  text-align: center;
  background: var(--accent, #FF6B35);
  color: #fff;
  font-size: 0.66rem;
  font-weight: 700;
  padding: 5px 0;
  letter-spacing: 0.04em;
}

/* ── Badge Últimas piezas ── */
.badge-stock-bajo {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  text-align: center;
  background: rgba(230, 126, 34, 0.88);
  color: #fff;
  font-size: 0.66rem;
  font-weight: 700;
  padding: 5px 0;
  letter-spacing: 0.04em;
}

/* ── Badges 3D / Destacado ── */
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
  margin: 0 0 6px;
}

/* ── Bottom (precio + botón) ── */
.card-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 10px;
  gap: 8px;
}

.card-bottom-actions {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}

.btn-agregar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: none;
  background: var(--accent, #FF6B35);
  color: #fff;
  font-size: 1.3rem;
  font-weight: 700;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.15s, opacity 0.15s;
  flex-shrink: 0;
}

.btn-agregar:hover {
  transform: scale(1.12);
  opacity: 0.88;
}

.card-precio {
  font-size: 1.2rem;
  font-weight: 800;
  color: var(--accent, #FF6B35);
  letter-spacing: -0.3px;
}

/* .btn-ver extiende .btn-primary (global en theme.css) — solo sobreescribe tamaño */
.btn-ver {
  padding: 7px 10px;
  border-radius: 8px;
  font-size: 0.78rem;
}

/* ── Responsive: en grid de 2 columnas (560px-999px) la card se apila verticalmente ── */
@media (min-width: 560px) and (max-width: 999px) {
  .producto-card {
    flex-direction: column;
  }

  .card-imagen {
    width: 100%;
    min-width: unset;
    height: 160px;
    min-height: unset;
    border-radius: 0;
  }

  .card-info {
    padding: 12px 12px 14px;
  }

  .btn-ver {
    font-size: 0.75rem;
    padding: 6px 8px;
  }
}

/* ── Responsive: card horizontal en 1 columna muy estrecha ── */
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
