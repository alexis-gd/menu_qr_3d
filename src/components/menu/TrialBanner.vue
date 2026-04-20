<template>
  <div v-if="mostrar" :class="['trial-banner', vencido ? 'trial-banner--vencido' : 'trial-banner--aviso']">
    <span class="trial-banner__texto">
      <template v-if="vencido">
        ⏰ Período de prueba vencido
      </template>
      <template v-else>
        ⏳ Tu prueba vence {{ diasRestantes === 1 ? 'mañana' : `en ${diasRestantes} días` }}
      </template>
    </span>
    <a
      v-if="vencido"
      :href="`https://wa.me/${CONTACTO_WA}?text=${encodeURIComponent('Hola, quiero contratar el menú digital.')}`"
      target="_blank"
      rel="noopener noreferrer"
      class="trial-banner__btn"
    >
      Contratar →
    </a>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const CONTACTO_WA = '529231311146'

const props = defineProps({
  trialActivo: { type: Boolean, default: true },
  diasRestantes: { type: Number, default: null },
})

const vencido = computed(() => !props.trialActivo)
const mostrar = computed(() =>
  vencido.value || (props.diasRestantes !== null && props.diasRestantes <= 2)
)
</script>

<style scoped>
.trial-banner {
  position: sticky;
  top: 0;
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 10px 16px;
  font-size: 14px;
  font-weight: 600;
  text-align: center;
}

.trial-banner--vencido {
  background: #c0392b;
  color: #fff;
}

.trial-banner--aviso {
  background: #f39c12;
  color: #fff;
}

.trial-banner__btn {
  background: rgba(255,255,255,0.25);
  color: #fff;
  text-decoration: none;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 13px;
  white-space: nowrap;
  transition: background 0.2s;
}

.trial-banner__btn:hover {
  background: rgba(255,255,255,0.4);
}
</style>
