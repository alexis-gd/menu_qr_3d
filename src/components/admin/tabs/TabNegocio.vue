<template>
  <div class="tab-content">
    <!-- Compartir menú -->
    <div class="card">
      <div class="card-header"><h2>Compartir menú</h2></div>
      <div class="card-body">
        <p class="helper-text">Escribe el mensaje introductorio. El nombre del restaurante y el enlace se adjuntan automáticamente.</p>
        <textarea
          v-model="formRest.compartir_mensaje"
          class="compartir-textarea"
          rows="2"
          placeholder="¡Hola! Te comparto el menú digital de"
        ></textarea>
        <p class="compartir-hint">Se adjuntará: <strong>{{ restaurante?.nombre }}</strong> · {{ menuUrl }}</p>
        <div class="compartir-actions">
          <a :href="'https://wa.me/?text=' + encodeURIComponent(textoCompartir)" target="_blank" rel="noopener" class="btn-wa">
            <SvgIcon :path="mdiWhatsapp" :size="18" /> Compartir por WhatsApp
          </a>
          <button @click="copiarTexto" class="btn-copy-link">
            <SvgIcon :path="copiado ? mdiCheck : mdiContentCopy" :size="16" />
            {{ copiado ? 'Copiado' : 'Copiar' }}
          </button>
          <a :href="menuUrl" target="_blank" rel="noopener" class="btn-ver-menu">
            <SvgIcon :path="mdiOpenInNew" :size="16" /> Ver menú
          </a>
        </div>
      </div>
    </div>

    <!-- Estado de la tienda -->
    <div class="card">
      <div class="card-header"><h2>Estado de la tienda</h2></div>
      <div class="card-body">
        <!-- Toggle cierre manual -->
        <div class="negocio-toggle-row">
          <div>
            <strong>Cerrar tienda ahora</strong>
            <p class="helper-text" style="margin:4px 0 0">Actívalo para cerrar el menú de inmediato. Los clientes verán una pantalla de "Cerrado" aunque sea horario de atención.</p>
          </div>
          <label class="sw">
            <input type="checkbox" v-model="formRest.tienda_cerrada_manual" />
            <span class="sw-track" :style="formRest.tienda_cerrada_manual ? { background: temaAccent } : {}"></span>
          </label>
        </div>

        <hr class="negocio-divider" />

        <!-- Horarios semanales -->
        <div>
          <strong style="display:block; margin-bottom:10px">Horario de atención</strong>
          <p class="helper-text" style="margin-bottom:14px">Si no configuras horarios, el menú estará siempre abierto (salvo cierre manual).</p>
          <div class="horarios-tabla">
            <div
              v-for="dia in DIAS_LISTA"
              :key="dia.key"
              class="horario-opcion-row"
              :class="{ 'horario-selected': horariosLocal[dia.key].activo }"
              @click="horariosLocal[dia.key].activo = !horariosLocal[dia.key].activo"
            >
              <div class="horario-indicator" :class="{ 'horario-indicator-on': horariosLocal[dia.key].activo }">
                <div v-if="horariosLocal[dia.key].activo" class="horario-indicator-inner"></div>
              </div>
              <span class="horario-dia-nombre">{{ dia.nombre }}</span>
              <div class="horario-horas" :class="{ 'horario-horas-disabled': !horariosLocal[dia.key].activo }" @click.stop>
                <input
                  type="time"
                  v-model="horariosLocal[dia.key].apertura"
                  :disabled="!horariosLocal[dia.key].activo"
                  class="input-time"
                />
                <span class="horario-sep">–</span>
                <input
                  type="time"
                  v-model="horariosLocal[dia.key].cierre"
                  :disabled="!horariosLocal[dia.key].activo"
                  class="input-time"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sistema de pedidos -->
    <div class="card">
      <div class="card-header"><h2>Sistema de pedidos</h2></div>
      <div class="card-body">
        <div class="negocio-toggle-row">
          <div>
            <strong>Activar pedidos en el menú</strong>
            <p class="helper-text" style="margin:4px 0 0">Los clientes podrán agregar platillos al carrito y enviarte el pedido por WhatsApp.</p>
          </div>
          <label class="sw">
            <input type="checkbox" v-model="formRest.pedidos_activos" />
            <span class="sw-track" :style="formRest.pedidos_activos ? { background: temaAccent } : {}"></span>
          </label>
        </div>
        <template v-if="formRest.pedidos_activos">
          <hr class="negocio-divider" />
          <div class="field" style="max-width:340px; margin-bottom:16px">
            <label>Número de WhatsApp del restaurante</label>
            <input v-model="formRest.pedidos_whatsapp" placeholder="Ej: 521XXXXXXXXXX (con código de país)" />
            <span class="field-hint">Los clientes enviarán su pedido a este número.</span>
          </div>
          <div class="negocio-toggle-row">
            <strong>Ofrecer envío a domicilio</strong>
            <label class="sw">
              <input type="checkbox" v-model="formRest.pedidos_envio_activo" />
              <span class="sw-track" :style="formRest.pedidos_envio_activo ? { background: temaAccent } : {}"></span>
            </label>
          </div>
          <div v-if="formRest.pedidos_envio_activo" class="envio-config">
            <div class="field" style="max-width:200px">
              <label>Costo del envío ($)</label>
              <input v-model="formRest.pedidos_envio_costo" type="number" min="0" step="0.50" placeholder="0.00" />
            </div>
            <div class="negocio-toggle-row" style="margin-top:14px">
              <div>
                <strong>Envío gratis por monto mínimo</strong>
                <p class="helper-text" style="margin:4px 0 0">Si el pedido supera el monto, el envío no se cobra.</p>
              </div>
              <label class="sw">
                <input type="checkbox" :checked="formRest.pedidos_envio_gratis_desde !== null" @change="toggleEnvioGratis" />
                <span class="sw-track" :style="formRest.pedidos_envio_gratis_desde !== null ? { background: temaAccent } : {}"></span>
              </label>
            </div>
            <div v-if="formRest.pedidos_envio_gratis_desde !== null" class="field" style="max-width:200px; margin-top:10px">
              <label>Monto mínimo para envío gratis ($)</label>
              <input v-model="formRest.pedidos_envio_gratis_desde" type="number" min="1" step="1" placeholder="Ej: 400" />
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Terminal a domicilio -->
    <div v-if="formRest.pedidos_activos" class="card">
      <div class="card-header"><h2>Terminal a domicilio</h2></div>
      <div class="card-body">
        <div class="negocio-toggle-row">
          <div>
            <strong>Aceptar terminal a domicilio</strong>
            <p class="helper-text" style="margin:4px 0 0">Los clientes con envío a domicilio verán la opción de pagar con terminal al recibir su pedido.</p>
          </div>
          <label class="sw">
            <input type="checkbox" v-model="formRest.pedidos_terminal_activo" />
            <span class="sw-track" :style="formRest.pedidos_terminal_activo ? { background: temaAccent } : {}"></span>
          </label>
        </div>
      </div>
    </div>

    <!-- Datos de transferencia -->
    <div v-if="formRest.pedidos_activos" class="card">
      <div class="card-header"><h2>Datos para transferencia</h2></div>
      <div class="card-body">
        <div class="negocio-toggle-row">
          <div>
            <strong>Aceptar transferencia bancaria</strong>
            <p class="helper-text" style="margin:4px 0 0">Los clientes verán la opción de pagar por transferencia en el checkout.</p>
          </div>
          <label class="sw">
            <input type="checkbox" v-model="formRest.pedidos_trans_activo" />
            <span class="sw-track" :style="formRest.pedidos_trans_activo ? { background: temaAccent } : {}"></span>
          </label>
        </div>
        <template v-if="formRest.pedidos_trans_activo">
          <hr class="negocio-divider" />
          <div class="form-grid">
            <div class="field">
              <label>Banco / Alias</label>
              <input v-model="formRest.pedidos_trans_banco" placeholder="Ej: BBVA, SPIN, CoDi..." />
            </div>
            <div class="field">
              <label>Titular de la cuenta</label>
              <input v-model="formRest.pedidos_trans_titular" placeholder="Nombre completo" />
            </div>
            <div class="field">
              <label>CLABE interbancaria</label>
              <input v-model="formRest.pedidos_trans_clabe" placeholder="18 dígitos" maxlength="18" />
            </div>
            <div class="field">
              <label>Número de cuenta</label>
              <input v-model="formRest.pedidos_trans_cuenta" placeholder="Número de cuenta" />
            </div>
          </div>
        </template>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { mdiWhatsapp, mdiContentCopy, mdiCheck, mdiOpenInNew, mdiContentSave } from '@mdi/js'
import { useApi } from '../../../composables/useApi.js'
import { THEMES as temas } from '../../../utils/themes.js'
import SvgIcon from '../../SvgIcon.vue'

const DIAS_LISTA = [
  { key: 'lunes',     nombre: 'Lunes'      },
  { key: 'martes',    nombre: 'Martes'     },
  { key: 'miercoles', nombre: 'Miércoles'  },
  { key: 'jueves',    nombre: 'Jueves'     },
  { key: 'viernes',   nombre: 'Viernes'    },
  { key: 'sabado',    nombre: 'Sábado'     },
  { key: 'domingo',   nombre: 'Domingo'    },
]

const DEFAULT_HORARIOS = () => Object.fromEntries(
  DIAS_LISTA.map(d => [d.key, { activo: !['sabado','domingo'].includes(d.key), apertura: '08:00', cierre: '22:00' }])
)

const props = defineProps({
  restauranteId: { type: Number, required: true },
  restaurante:   { type: Object, default: null },
  menuUrl:       { type: String, default: '' },
})

const emit = defineEmits(['notif', 'restaurante-updated'])

const { put } = useApi()

const guardando     = ref(false)
const copiado       = ref(false)
const horariosLocal = ref(DEFAULT_HORARIOS())

const formRest = ref({
  compartir_mensaje: '', pedidos_activos: false,
  pedidos_envio_activo: true, pedidos_envio_costo: 0, pedidos_envio_gratis_desde: null,
  pedidos_whatsapp: '', pedidos_terminal_activo: false,
  pedidos_trans_activo: false,
  pedidos_trans_clabe: '', pedidos_trans_cuenta: '',
  pedidos_trans_titular: '', pedidos_trans_banco: '',
  tienda_cerrada_manual: false,
})

watch(() => props.restaurante, (rest) => {
  if (!rest) return
  formRest.value = {
    compartir_mensaje:     rest.compartir_mensaje     || '¡Hola! Te comparto el menú digital de',
    pedidos_activos:       Boolean(rest.pedidos_activos ?? false),
    pedidos_envio_activo:  Boolean(rest.pedidos_envio_activo ?? true),
    pedidos_envio_costo:          parseFloat(rest.pedidos_envio_costo) || 0,
    pedidos_envio_gratis_desde:   rest.pedidos_envio_gratis_desde !== null && rest.pedidos_envio_gratis_desde !== undefined ? parseFloat(rest.pedidos_envio_gratis_desde) : null,
    pedidos_whatsapp:        rest.pedidos_whatsapp        || '',
    pedidos_terminal_activo: Boolean(rest.pedidos_terminal_activo ?? false),
    pedidos_trans_activo:    Boolean(rest.pedidos_trans_activo ?? false),
    pedidos_trans_clabe:   rest.pedidos_trans_clabe   || '',
    pedidos_trans_cuenta:  rest.pedidos_trans_cuenta  || '',
    pedidos_trans_titular: rest.pedidos_trans_titular || '',
    pedidos_trans_banco:   rest.pedidos_trans_banco   || '',
    tienda_cerrada_manual: Boolean(rest.tienda_cerrada_manual ?? false),
  }
  horariosLocal.value = rest.tienda_horarios
    ? { ...DEFAULT_HORARIOS(), ...rest.tienda_horarios }
    : DEFAULT_HORARIOS()
}, { immediate: true })

const temaAccent = computed(() => {
  const tema = props.restaurante?.tema || 'calido'
  return (temas.find(t => t.id === tema) || temas[0]).accent
})

const textoCompartir = computed(() =>
  `${formRest.value.compartir_mensaje}\n${props.restaurante?.nombre || ''}\n${props.menuUrl}`
)

const toggleEnvioGratis = () => {
  formRest.value.pedidos_envio_gratis_desde =
    formRest.value.pedidos_envio_gratis_desde === null ? 400 : null
}

const copiarTexto = async () => {
  await navigator.clipboard.writeText(textoCompartir.value)
  copiado.value = true
  setTimeout(() => { copiado.value = false }, 2000)
}

async function guardarRestaurante() {
  if (formRest.value.pedidos_activos && !formRest.value.pedidos_whatsapp?.trim()) {
    emit('notif', { texto: 'El número de WhatsApp es requerido para recibir pedidos', tipo: 'error' })
    return
  }
  if (formRest.value.pedidos_activos && formRest.value.pedidos_envio_activo &&
      (isNaN(parseFloat(formRest.value.pedidos_envio_costo)) || parseFloat(formRest.value.pedidos_envio_costo) < 0)) {
    emit('notif', { texto: 'El costo de envío debe ser un número positivo', tipo: 'error' })
    return
  }
  if (formRest.value.pedidos_envio_gratis_desde !== null &&
      (isNaN(parseFloat(formRest.value.pedidos_envio_gratis_desde)) || parseFloat(formRest.value.pedidos_envio_gratis_desde) <= 0)) {
    emit('notif', { texto: 'El monto mínimo para envío gratis debe ser mayor a 0', tipo: 'error' })
    return
  }
  if (formRest.value.pedidos_trans_activo && formRest.value.pedidos_trans_clabe &&
      formRest.value.pedidos_trans_clabe.replace(/\D/g, '').length !== 18) {
    emit('notif', { texto: 'La CLABE debe tener exactamente 18 dígitos', tipo: 'error' })
    return
  }
  guardando.value = true
  try {
    const rest = props.restaurante || {}
    const payload = {
      ...formRest.value,
      tienda_horarios: horariosLocal.value,
      // Preservar campos de apariencia que no edita este tab
      nombre:           rest.nombre           || '',
      descripcion:      rest.descripcion      || '',
      tema:             rest.tema             || 'calido',
      qr_frase:         rest.qr_frase         || '',
      qr_frase_activa:  rest.qr_frase_activa  ?? true,
      qr_wifi_nombre:   rest.qr_wifi_nombre   || '',
      qr_wifi_clave:    rest.qr_wifi_clave    || '',
      qr_wifi_activo:   rest.qr_wifi_activo   ?? false,
    }
    await put('restaurantes', payload, { id: props.restauranteId })
    emit('restaurante-updated', { ...formRest.value, tienda_horarios: horariosLocal.value })
    emit('notif', { texto: 'Cambios guardados', tipo: 'ok' })
  } catch (err) { emit('notif', { texto: err.message, tipo: 'error' }) }
  finally { guardando.value = false }
}
defineExpose({ guardar: guardarRestaurante, guardando })
</script>

<style scoped>
.compartir-textarea {
  width: 100%; box-sizing: border-box;
  border: 1px solid #ddd; border-radius: 8px;
  padding: 10px 12px; font-size: 0.88rem; line-height: 1.5;
  color: #444; resize: vertical; margin-bottom: 12px; font-family: inherit;
}
.compartir-textarea:focus { outline: none; border-color: #aaa; }
.compartir-hint { font-size: 0.78rem; color: #aaa; margin: -4px 0 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.compartir-hint strong { color: #888; }
.compartir-actions { display: flex; gap: 10px; flex-wrap: wrap; }

.btn-wa {
  display: inline-flex; align-items: center; gap: 8px;
  background: #25D366; color: #fff; text-decoration: none;
  padding: 10px 18px; border-radius: 9px; font-size: 0.9rem; font-weight: 700; transition: opacity 0.2s;
}
.btn-wa:hover { opacity: 0.88; }
.btn-copy-link {
  display: inline-flex; align-items: center; gap: 8px;
  background: #f0f0f0; color: #444; border: none;
  padding: 10px 18px; border-radius: 9px; font-size: 0.9rem; font-weight: 700;
  cursor: pointer; transition: background 0.2s;
}
.btn-copy-link:hover { background: #e0e0e0; }
.btn-ver-menu {
  display: inline-flex; align-items: center; gap: 8px;
  background: #fff; color: #444; text-decoration: none;
  border: 1.5px solid #ddd; padding: 10px 18px; border-radius: 9px;
  font-size: 0.9rem; font-weight: 700; transition: background 0.2s, border-color 0.2s;
}
.btn-ver-menu:hover { background: #f5f5f5; border-color: #bbb; }

/* ── Horarios ── */
.horarios-tabla { display: flex; flex-direction: column; gap: 6px; }

.horario-opcion-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 14px;
  background: #fff;
  border: 1.5px solid #e8e8e8;
  border-radius: 10px;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
  user-select: none;
}
.horario-opcion-row:hover {
  border-color: v-bind(temaAccent);
  background: color-mix(in srgb, v-bind(temaAccent) 8%, #fff);
}
.horario-selected {
  border-color: v-bind(temaAccent);
  background: color-mix(in srgb, v-bind(temaAccent) 8%, #fff);
}

/* Indicador circular (idéntico al PersonalizacionModal) */
.horario-indicator {
  flex-shrink: 0;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 2px solid #ccc;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: border-color 0.15s;
}
.horario-indicator-on {
  border-color: v-bind(temaAccent);
}
.horario-indicator-inner {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: v-bind(temaAccent);
}

.horario-dia-nombre {
  flex: 1;
  font-size: 0.9rem;
  font-weight: 500;
  color: #333;
}

.horario-horas {
  display: flex;
  align-items: center;
  gap: 6px;
}
.horario-horas-disabled { opacity: 0.38; pointer-events: none; }
.horario-sep { color: #aaa; font-size: 0.85rem; }

.input-time {
  padding: 6px 8px;
  border: 1.5px solid #ddd;
  border-radius: 8px;
  font-size: 0.85rem;
  font-family: inherit;
  outline: none;
  background: #fafafa;
  color: #333;
  width: 90px;
}
.input-time:focus { border-color: #aaa; }
.input-time:disabled { background: #f5f5f5; color: #bbb; }
</style>
