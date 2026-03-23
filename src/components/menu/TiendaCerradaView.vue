<template>
  <div class="cerrado-wrap">
    <div class="cerrado-ilustracion" aria-hidden="true">
      <img
        :src="`${base}imgs/cerrado_dog.svg`"
        alt=""
        width="320"
        height="300"
        draggable="false"
      />
    </div>

    <h2 class="cerrado-titulo">Estamos recargando baterías.</h2>
    <p class="cerrado-subtitulo">
      {{ nombre }} no está disponible en este momento.
    </p>

    <!-- Horarios si están configurados -->
    <div v-if="horariosFormateados.length" class="cerrado-horarios">
      <p class="horarios-label">Horario de atención:</p>
      <div v-for="d in horariosFormateados" :key="d.dia" class="horario-row">
        <span class="dia-nombre">{{ d.nombre }}</span>
        <span class="dia-rango">{{ d.rango }}</span>
      </div>
    </div>
    <p v-else class="cerrado-fallback">
      Próximamente abriremos de nuevo
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
const base = import.meta.env.BASE_URL

const props = defineProps({
  horarios: { type: Object, default: null },
  nombre:   { type: String, default: '' }
})

const DIAS_ORDEN = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo']
const DIAS_DISPLAY = {
  lunes: 'Lunes', martes: 'Martes', miercoles: 'Miércoles',
  jueves: 'Jueves', viernes: 'Viernes', sabado: 'Sábado', domingo: 'Domingo'
}

const horariosFormateados = computed(() => {
  if (!props.horarios) return []
  return DIAS_ORDEN
    .filter(dia => props.horarios[dia]?.activo)
    .map(dia => ({
      dia,
      nombre: DIAS_DISPLAY[dia],
      rango: `${props.horarios[dia].apertura} – ${props.horarios[dia].cierre}`
    }))
})
</script>

<style scoped>
.cerrado-wrap {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 24px 60px;
  text-align: center;
}

.cerrado-ilustracion {
  margin-bottom: 20px;
  opacity: 0.85;
}

.cerrado-titulo {
  font-size: 1.8rem;
  font-weight: 800;
  color: var(--text-main, #222);
  margin-bottom: 8px;
  letter-spacing: -0.3px;
}

.cerrado-subtitulo {
  font-size: 0.95rem;
  color: var(--text-sub, #888);
  margin-bottom: 28px;
  max-width: 280px;
  line-height: 1.5;
}

/* ── Horarios ── */
.cerrado-horarios {
  background: var(--card-bg, #fff);
  border: 1px solid var(--card-border, #eee);
  border-radius: 14px;
  padding: 16px 20px;
  width: 100%;
  max-width: 300px;
}

.horarios-label {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--text-sub, #aaa);
  font-weight: 700;
  margin-bottom: 10px;
}

.horario-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 5px 0;
  font-size: 0.88rem;
  border-bottom: 1px solid var(--card-border, #f0f0f0);
}

.horario-row:last-child {
  border-bottom: none;
}

.dia-nombre {
  font-weight: 600;
  color: var(--text-main, #333);
}

.dia-rango {
  color: var(--accent, #FF6B35);
  font-weight: 700;
  font-size: 0.85rem;
}

/* ── Fallback sin horarios ── */
.cerrado-fallback {
  font-size: 0.9rem;
  color: var(--text-sub, #aaa);
  font-style: italic;
}
</style>
