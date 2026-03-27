<template>
  <Teleport to="body">
    <div class="tcp-overlay" @click.self="$emit('cerrar')">
      <div class="tcp-card" role="dialog" aria-modal="true">

        <!-- PASO 1: Tienda cerrada -->
        <div v-if="paso === 1" class="tcp-body">
          <img
            :src="`${base}imgs/cerrado_dog.svg`"
            alt=""
            class="tcp-dog"
            draggable="false"
          />

          <h2 class="tcp-titulo">{{ tituloCierre }}</h2>
          <p class="tcp-subtitulo">{{ nombre }} no está disponible en este momento.</p>

          <!-- Horarios -->
          <div v-if="horariosFormateados.length" class="tcp-horarios">
            <p class="tcp-horarios-label">Horario de atención:</p>
            <div v-for="d in horariosFormateados" :key="d.dia" class="tcp-horario-row">
              <span class="tcp-dia">{{ d.nombre }}</span>
              <span class="tcp-rango">{{ d.rango }}</span>
            </div>
          </div>
          <p v-else class="tcp-fallback">Próximamente abriremos de nuevo.</p>

          <!-- Acciones -->
          <div class="tcp-actions">
            <button
              v-if="programarActivo"
              class="tcp-btn-primary"
              @click="paso = 2"
            >
              Programar pedido
            </button>
            <button class="tcp-btn-ghost" @click="$emit('cerrar')">
              Ver menú →
            </button>
          </div>
        </div>

        <!-- PASO 2: Explicar pedido programado -->
        <div v-else class="tcp-body">
          <div class="tcp-calendario-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <rect x="3" y="4" width="18" height="18" rx="3"/>
              <line x1="3" y1="9" x2="21" y2="9"/>
              <line x1="8" y1="2" x2="8" y2="6"/>
              <line x1="16" y1="2" x2="16" y2="6"/>
              <circle cx="8" cy="14" r="0.8" fill="currentColor"/>
              <circle cx="12" cy="14" r="0.8" fill="currentColor"/>
              <circle cx="16" cy="14" r="0.8" fill="currentColor"/>
            </svg>
          </div>

          <h2 class="tcp-titulo">¿Cómo funciona el pedido programado?</h2>
          <p class="tcp-desc">
            Elige los productos que quieres, y al momento de confirmar tu pedido
            selecciona la <strong>fecha y hora</strong> en que deseas recibirlo.
            Te confirmamos disponibilidad una vez nos envies el pedido por WhatsApp.
          </p>

          <div class="tcp-actions">
            <button class="tcp-btn-primary" @click="aceptarProgramar">
              Entendido, ver menú
            </button>
            <button class="tcp-btn-ghost" @click="paso = 1">
              ← Volver
            </button>
          </div>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue'

const base = import.meta.env.BASE_URL

const props = defineProps({
  horarios:       { type: Object,  default: null },
  nombre:         { type: String,  default: '' },
  programarActivo:{ type: Boolean, default: false },
  cerradoManual:  { type: Boolean, default: false }
})

const emit = defineEmits(['cerrar', 'programar'])

const paso = ref(1)

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

const tituloCierre = computed(() => {
  if (props.cerradoManual) return 'Estamos cerrados por el momento'
  if (!props.horarios) return 'Estamos cerrados por el momento'

  const diasIndice = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado']
  const hoy = diasIndice[new Date().getDay()]
  const diaHoy = props.horarios[hoy]

  if (!diaHoy?.activo) return 'Estamos cerrados hoy'

  const ahora = new Date().toTimeString().slice(0, 5)
  if (ahora < diaHoy.apertura) return 'Aún no hemos abierto'
  return 'Ya cerramos por hoy'
})

function aceptarProgramar() {
  emit('cerrar')
  emit('programar')
}
</script>

<style scoped>
.tcp-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  z-index: 1200;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding: 0;
  animation: tcp-fade-in 0.2s ease;
}

@keyframes tcp-fade-in {
  from { opacity: 0 }
  to   { opacity: 1 }
}

.tcp-card {
  background: var(--card-bg, #fff);
  width: 100%;
  max-width: 480px;
  border-radius: 24px 24px 0 0;
  overflow: hidden;
  animation: tcp-slide-up 0.28s cubic-bezier(0.4, 0, 0.2, 1);
  max-height: 92dvh;
  overflow-y: auto;
}

@keyframes tcp-slide-up {
  from { transform: translateY(60px); opacity: 0 }
  to   { transform: translateY(0);    opacity: 1 }
}

.tcp-body {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 28px 24px 36px;
  text-align: center;
  gap: 0;
}

/* Ilustración */
.tcp-dog {
  width: 110px;
  height: auto;
  margin-bottom: 16px;
  opacity: 0.9;
}

/* Ícono calendario */
.tcp-calendario-icon {
  width: 72px;
  height: 72px;
  border-radius: 18px;
  background: color-mix(in srgb, var(--accent, #FF6B35) 12%, transparent);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
  color: var(--accent, #FF6B35);
}

.tcp-calendario-icon svg {
  width: 36px;
  height: 36px;
}

/* Textos */
.tcp-titulo {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--text-main, #222);
  margin: 0 0 8px;
  letter-spacing: -0.2px;
  line-height: 1.25;
}

.tcp-subtitulo {
  font-size: 0.9rem;
  color: var(--text-sub, #888);
  margin: 0 0 20px;
  line-height: 1.5;
}

.tcp-desc {
  font-size: 0.92rem;
  color: var(--text-sub, #666);
  margin: 0 0 28px;
  line-height: 1.65;
  max-width: 300px;
}

/* Horarios */
.tcp-horarios {
  background: var(--bg-secondary, #f8f8f8);
  border: 1px solid var(--card-border, #eee);
  border-radius: 14px;
  padding: 14px 18px;
  width: 100%;
  margin-bottom: 24px;
  text-align: left;
}

.tcp-horarios-label {
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--text-sub, #aaa);
  font-weight: 700;
  margin: 0 0 10px;
}

.tcp-horario-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 5px 0;
  font-size: 0.85rem;
  border-bottom: 1px solid var(--card-border, #f0f0f0);
}

.tcp-horario-row:last-child { border-bottom: none; }

.tcp-dia {
  font-weight: 600;
  color: var(--text-main, #333);
}

.tcp-rango {
  color: var(--accent, #FF6B35);
  font-weight: 700;
  font-size: 0.82rem;
}

.tcp-fallback {
  font-size: 0.88rem;
  color: var(--text-sub, #aaa);
  font-style: italic;
  margin: 0 0 24px;
}

/* Acciones */
.tcp-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: 100%;
}

.tcp-btn-primary {
  width: 100%;
  padding: 14px;
  border: none;
  border-radius: 12px;
  background: var(--accent, #FF6B35);
  color: #fff;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  transition: opacity 0.15s;
}

.tcp-btn-primary:hover { opacity: 0.88; }

.tcp-btn-ghost {
  background: none;
  border: none;
  color: var(--accent, #FF6B35);
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  padding: 8px;
  text-decoration: underline;
  text-underline-offset: 3px;
}
</style>
