<template>
  <div class="checkout-overlay" @click.self="$emit('close')">
    <div class="checkout-panel">

      <!-- Header -->
      <div class="checkout-header">
        <h2>Tu pedido</h2>
        <button class="btn-cerrar" @click="$emit('close')">✕</button>
      </div>

      <!-- Banner pedido programado -->
      <div v-if="pedidoProgramado" class="banner-programado">
        📅 Estás haciendo un pedido programado
      </div>

      <div class="checkout-body">

        <!-- ── 1. Resumen del carrito ── -->
        <section class="checkout-section">
          <h3 class="section-title">Platillos</h3>
          <div class="items-lista">
            <div v-for="(item, idx) in carritoStore.items" :key="idx"
               class="item-row" :class="{ 'item-bloqueado': esItemBloqueado(item) }">
              <!-- Fila superior: controles + nombre + precio -->
              <div class="item-top">
                <div class="item-cant-ctrl">
                  <button class="cant-btn" @click="reducir(idx)">−</button>
                  <span class="cant-num">{{ item.cantidad }}</span>
                  <button class="cant-btn"
                    :disabled="esItemBloqueado(item) || (item.producto.stock !== null && item.producto.stock !== undefined && item.cantidad >= item.producto.stock)"
                    @click="item.cantidad++">+</button>
                </div>
                <span class="item-nombre">{{ item.producto.nombre }}</span>
                <span class="item-subtotal">${{ ((item.precio_unitario ?? item.producto.precio) * item.cantidad).toFixed(2) }}</span>
              </div>
              <!-- Fila inferior: chips + observación a todo el ancho -->
              <div class="item-bottom">
                <span v-if="esItemBloqueado(item)" class="chip-item-bloqueado">
                  {{ item.producto.stock === 0 ? 'Sin stock' : 'Próximamente' }} — elimina este ítem para continuar
                </span>
                <div v-if="item.opciones?.length" class="item-opciones">
                  <span v-for="op in item.opciones" :key="op.opcion_id" class="chip-opcion">
                    {{ op.opcion_nombre }}<template v-if="op.precio_extra > 0"> +${{ Number(op.precio_extra).toFixed(2) }}</template>
                  </span>
                </div>
                <input
                  :value="item.observacion"
                  @input="item.observacion = ucfirst($event.target.value)"
                  class="item-obs-input"
                  placeholder="Observación (opcional)"
                  maxlength="100"
                />
              </div>
            </div>
          </div>
        </section>

        <!-- ── 2. Tipo de entrega ── -->
        <section class="checkout-section">
          <h3 class="section-title">Tipo de entrega</h3>
          <div class="opciones-grid">
            <label :class="['opcion-card', { selected: tipoEntrega === 'recoger' }]">
              <input type="radio" v-model="tipoEntrega" value="recoger" />
              <span class="opcion-icon">🏠</span>
              <span class="opcion-label">Recoger en local</span>
              <span class="opcion-sub">Sin costo adicional</span>
            </label>
            <label
              v-if="pedidosConfig.pedidos_envio_activo"
              :class="['opcion-card', { selected: tipoEntrega === 'envio' }]"
            >
              <input type="radio" v-model="tipoEntrega" value="envio" />
              <span class="opcion-icon">🛵</span>
              <span class="opcion-label">Envío a domicilio</span>
              <span v-if="envioEsGratis" class="opcion-sub envio-gratis-badge">¡Envío gratis!</span>
              <span v-else-if="umbralGratis !== null" class="opcion-sub">
                +${{ Number(pedidosConfig.pedidos_envio_costo || 0).toFixed(2) }}
                <small class="envio-gratis-hint">Gratis desde ${{ umbralGratis.toFixed(0) }}</small>
              </span>
              <span v-else class="opcion-sub">+${{ Number(pedidosConfig.pedidos_envio_costo || 0).toFixed(2) }}</span>
            </label>
          </div>
        </section>

        <!-- ── 3. Fecha y hora programada (solo si pedidoProgramado) ── -->
        <!-- v-show en lugar de v-if: evita el error parentNode null de VueDatePicker al desmontar -->
        <section v-show="pedidoProgramado" class="checkout-section seccion-programado">
          <h3 class="section-title">📅 ¿Cuándo lo quieres recibir?</h3>
          <div class="campos">
            <div class="campo">
              <label>Fecha *</label>
              <VueDatePicker
                v-model="fechaProgramada"
                :min-date="minFecha"
                :time-config="{ enableTimePicker: false }"
                :format="fmtFechaDisplay"
                :disabled-dates="disabledDates"
                auto-apply
                placeholder="Selecciona una fecha"
              />
            </div>
            <div class="campo">
              <label>Hora aproximada *</label>
              <VueDatePicker
                v-model="horaProgramada"
                time-picker
                :hours-increment="1"
                :minutes-increment="15"
                :min-time="minHora"
                :max-time="maxHora"
                auto-apply
                placeholder="Selecciona una hora"
                :disabled="!fechaProgramada"
              />
              <span v-if="fechaProgramada && minHoraStr" class="campo-hint">
                Horario: {{ minHoraStr }} – {{ maxHoraStr }}
              </span>
            </div>
          </div>
        </section>

        <!-- ── 4. Datos del cliente ── -->
        <section class="checkout-section">
          <h3 class="section-title">Tus datos</h3>
          <div class="campos">
            <div class="campo">
              <label>Teléfono *</label>
              <input v-model="telefono" type="tel" inputmode="numeric" placeholder="10 dígitos" maxlength="10" />
              <!-- Cuponera de recompensas -->
              <div v-if="historial?.activo" class="cuponera">
                <div v-if="historial.tiene_recompensa" class="cuponera-recompensa">
                  🎁 ¡Premio ganado! Completaste tus {{ historial.necesarias }} compras —
                  {{ historial.tipo === 'descuento_fijo' ? '$' + Number(historial.valor).toFixed(0) : historial.valor + '%' }} de descuento aplicado.
                </div>
                <template v-else>
                  <p class="cuponera-texto">
                    <template v-if="historial.compras_en_ciclo + 1 >= historial.necesarias">
                      🎁 ¡Con esta compra ganas tu recompensa!
                    </template>
                    <template v-else>
                      Con esta compra tendrías <strong>{{ historial.compras_en_ciclo + 1 }}</strong> de <strong>{{ historial.necesarias }}</strong>
                    </template>
                  </p>
                  <p class="cuponera-premio">
                    Premio: {{ historial.tipo === 'descuento_fijo' ? '$' + Number(historial.valor).toFixed(0) : historial.valor + '%' }} de descuento
                  </p>
                  <div class="cuponera-sellos">
                    <span
                      v-for="i in Math.min(historial.necesarias, 10)"
                      :key="i"
                      class="sello"
                      :class="{ 'sello-lleno': i <= Math.min(historial.compras_en_ciclo + 1, historial.necesarias) }"
                    >★</span>
                    <span v-if="historial.necesarias > 10" class="cuponera-resto">+{{ historial.necesarias - 10 }}</span>
                  </div>
                </template>
              </div>
            </div>
            <div class="campo">
              <label>Nombre *</label>
              <input :value="nombre" @input="nombre = ucfirst($event.target.value)" placeholder="¿Cómo te llamamos?" maxlength="60" />
            </div>
            <!-- Código de descuento / promotor -->
            <div v-if="pedidosConfig.codigos_promo_habilitado" class="campo">
              <label>Código de descuento (opcional)</label>
              <div class="promo-wrap">
                <input
                  v-model="codigoPromo"
                  :disabled="!!historial?.tiene_recompensa"
                  placeholder="Ej: JUAN10"
                  maxlength="20"
                  @input="codigoPromo = codigoPromo.toUpperCase(); onCodigoPromoInput()"
                />
                <span v-if="historial?.tiene_recompensa" class="promo-status promo-wait">No disponible con recompensa activa</span>
                <template v-else>
                  <span v-if="promoValidando" class="promo-status promo-wait">…</span>
                  <span v-else-if="promoValidada && !promoEfectiva && tipoEntrega !== 'envio'" class="promo-status promo-wait">
                    Solo aplica para envíos a domicilio
                  </span>
                  <span v-else-if="promoValidada && !promoEfectiva" class="promo-status promo-wait">
                    Ya tienes envío gratis por superar los ${{ umbralGratis?.toFixed(0) }}
                  </span>
                  <span v-else-if="promoValidada" class="promo-status promo-ok">
                    ✓ {{ promoValidada.tipo === 'envio_gratis' ? 'Envío gratis' : promoValidada.tipo === 'descuento_fijo' ? '-$' + Number(promoValidada.valor).toFixed(2) : '-' + promoValidada.valor + '%' }}
                  </span>
                  <span v-else-if="promoError === 'agotado'" class="promo-status promo-err">✗ Código agotado</span>
                  <span v-else-if="promoError === 'telefono'" class="promo-status promo-err">✗ Este cupón es personal, verifica tu número</span>
                  <span v-else-if="promoError" class="promo-status promo-err">✗ Inválido</span>
                </template>
              </div>
            </div>

            <template v-if="tipoEntrega === 'envio'">
              <div class="campo">
                <label>Dirección de entrega *</label>
                <textarea :value="direccion" @input="direccion = ucfirst($event.target.value)" rows="2" placeholder="Calle, número, colonia..." maxlength="150"></textarea>
              </div>
              <div class="campo">
                <label>Referencia de entrega</label>
                <input :value="referencia" @input="referencia = ucfirst($event.target.value)" placeholder="Ej: edificio azul, junto a la farmacia..." maxlength="150" />
              </div>
            </template>
          </div>
        </section>

        <!-- ── 4. Método de pago ── -->
        <section class="checkout-section">
          <h3 class="section-title">Método de pago</h3>
          <div class="opciones-filas">
            <label :class="['opcion-fila', { selected: metodoPago === 'efectivo' }]">
              <input type="radio" v-model="metodoPago" value="efectivo" />
              <span class="opcion-fila-icon">💵</span>
              <span class="opcion-fila-label">Efectivo</span>
              <span v-if="metodoPago === 'efectivo'" class="opcion-fila-check">✓</span>
            </label>
            <label
              v-if="pedidosConfig.pedidos_trans_activo"
              :class="['opcion-fila', { selected: metodoPago === 'transferencia' }]"
            >
              <input type="radio" v-model="metodoPago" value="transferencia" />
              <span class="opcion-fila-icon">🏦</span>
              <span class="opcion-fila-label">Transferencia bancaria</span>
              <span v-if="metodoPago === 'transferencia'" class="opcion-fila-check">✓</span>
            </label>
            <label
              v-if="pedidosConfig.pedidos_terminal_activo && tipoEntrega === 'envio'"
              :class="['opcion-fila', { selected: metodoPago === 'terminal' }]"
            >
              <input type="radio" v-model="metodoPago" value="terminal" />
              <span class="opcion-fila-icon">💳</span>
              <span class="opcion-fila-label">Terminal a domicilio</span>
              <span v-if="metodoPago === 'terminal'" class="opcion-fila-check">✓</span>
            </label>
          </div>

          <!-- Denominación (efectivo + envío) -->
          <div v-if="metodoPago === 'efectivo' && tipoEntrega === 'envio'" class="campo" style="margin-top:12px; max-width:220px">
            <label>¿Con cuánto pagarás?</label>
            <input v-model="denominacion" type="number" min="0" max="99999" step="10" placeholder="Ej: 200" />
            <p v-if="faltante > 0" class="warn-denominacion">
              ⚠️ Faltan ${{ faltante.toFixed(2) }} para cubrir el total
            </p>
          </div>

          <!-- Datos de transferencia -->
          <div v-if="metodoPago === 'transferencia' && tieneDatosTransferencia" class="trans-box">
            <p class="trans-title">Datos para tu transferencia:</p>
            <div v-if="pedidosConfig.pedidos_trans_banco" class="trans-row">
              <span class="trans-label">Banco</span>
              <div class="trans-val-group">
                <span class="trans-val">{{ pedidosConfig.pedidos_trans_banco }}</span>
                <button class="btn-copiar-dato" @click="copiarDato('banco', pedidosConfig.pedidos_trans_banco)">{{ copiados.banco ? '✓' : 'Copiar' }}</button>
              </div>
            </div>
            <div v-if="pedidosConfig.pedidos_trans_titular" class="trans-row">
              <span class="trans-label">Titular</span>
              <div class="trans-val-group">
                <span class="trans-val">{{ pedidosConfig.pedidos_trans_titular }}</span>
                <button class="btn-copiar-dato" @click="copiarDato('titular', pedidosConfig.pedidos_trans_titular)">{{ copiados.titular ? '✓' : 'Copiar' }}</button>
              </div>
            </div>
            <div v-if="pedidosConfig.pedidos_trans_clabe" class="trans-row">
              <span class="trans-label">CLABE</span>
              <div class="trans-val-group">
                <strong class="trans-val trans-clabe">{{ pedidosConfig.pedidos_trans_clabe }}</strong>
                <button class="btn-copiar-dato" @click="copiarDato('clabe', pedidosConfig.pedidos_trans_clabe)">{{ copiados.clabe ? '✓' : 'Copiar' }}</button>
              </div>
            </div>
            <div v-if="pedidosConfig.pedidos_trans_cuenta" class="trans-row">
              <span class="trans-label">Cuenta</span>
              <div class="trans-val-group">
                <span class="trans-val">{{ pedidosConfig.pedidos_trans_cuenta }}</span>
                <button class="btn-copiar-dato" @click="copiarDato('cuenta', pedidosConfig.pedidos_trans_cuenta)">{{ copiados.cuenta ? '✓' : 'Copiar' }}</button>
              </div>
            </div>
          </div>
        </section>

        <!-- ── Totales ── -->
        <div class="totales">
          <div class="total-row">
            <span>Subtotal</span>
            <span>${{ subtotal.toFixed(2) }}</span>
          </div>
          <div v-if="tipoEntrega === 'envio'" class="total-row">
            <span>Envío</span>
            <span v-if="envioEsGratis" class="envio-gratis-total">¡Gratis!</span>
            <span v-else>${{ costoEnvio.toFixed(2) }}</span>
          </div>
          <div v-if="descuento > 0" class="total-row total-descuento">
            <span>🎁 Descuento recompensa</span>
            <span>−${{ descuento.toFixed(2) }}</span>
          </div>
          <div v-if="descuentoPromo > 0" class="total-row total-descuento">
            <span>🏷️ Cupón {{ codigoPromo.trim().toUpperCase() }}</span>
            <span>−${{ descuentoPromo.toFixed(2) }}</span>
          </div>
          <div class="total-row total-final">
            <strong>Total</strong>
            <strong>${{ total.toFixed(2) }}</strong>
          </div>
        </div>

        <!-- Advertencia ítems bloqueados -->
        <div v-if="hayItemsBloqueados" class="alerta-bloqueados">
          ⚠️ Hay platillos no disponibles en tu carrito. Elimínalos para continuar.
        </div>

        <!-- Error de validación -->
        <p v-if="errorMsg" class="error-msg">{{ errorMsg }}</p>

        <!-- Botón confirmar -->
        <button
          class="btn-primary btn-confirmar"
          :disabled="enviando || hayItemsBloqueados"
          @click="confirmar"
        >
          {{ enviando ? 'Abriendo WhatsApp...' : '📲 Abrir WhatsApp y enviar →' }}
        </button>
        <p v-if="!enviando" class="hint-whatsapp">
          Solo da clic en "Enviar" en WhatsApp para completar tu pedido
        </p>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import { useApi } from '../../composables/useApi.js'
import { ucfirst } from '../../utils/ucfirst.js'
import { useCarritoStore } from '../../stores/carrito.js'

const props = defineProps({
  pedidosConfig:    { type: Object,  required: true },
  mesa:             { type: String,  default: null  },
  restauranteId:    { type: [Number, String], default: null },
  pedidoProgramado: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'confirmado', 'stock-agotado'])
const { get, post } = useApi()
const carritoStore = useCarritoStore()

const tipoEntrega    = ref('recoger')
const metodoPago     = ref('efectivo')
const nombre         = ref('')
const telefono       = ref('')
const direccion      = ref('')
const referencia     = ref('')
const denominacion   = ref('')
const codigoPromo    = ref('')
const promoValidada  = ref(null)   // { tipo, valor, descripcion } si es válido
const promoError     = ref('')     // '' = sin error | 'invalido' | 'agotado'
const promoValidando = ref(false)
let _promoTimer = null
const enviando          = ref(false)
const errorMsg          = ref('')
const fechaProgramada   = ref(null)   // Date object (VueDatePicker)
const horaProgramada    = ref(null)   // { hours, minutes } (VueDatePicker time-picker)

// ── Horarios para pedido programado ──
const _DIAS = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado']

const _horarioDelDia = (date) => {
  const horarios = props.pedidosConfig?.tienda_horarios
  if (!horarios || !date) return null
  // Acepta Date object o string "yyyy-MM-dd"
  const d = date instanceof Date ? date : new Date(date + 'T12:00:00')
  return horarios[_DIAS[d.getDay()]] || null
}

// Mínimo: mañana como Date object
const minFecha = computed(() => {
  const d = new Date()
  d.setDate(d.getDate() + 1)
  d.setHours(0, 0, 0, 0)
  return d
})

const fmtFechaDisplay = (d) =>
  d ? d.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' }) : ''

// Deshabilita días cerrados en el calendario
const disabledDates = (date) => {
  const h = _horarioDelDia(date)
  return !h || !h.activo
}

// min/max hora para el time-picker según el día elegido
const minHora = computed(() => {
  const h = _horarioDelDia(fechaProgramada.value)
  if (!h?.apertura) return null
  const [hours, minutes] = h.apertura.split(':').map(Number)
  return { hours, minutes }
})
const maxHora = computed(() => {
  const h = _horarioDelDia(fechaProgramada.value)
  if (!h?.cierre) return null
  const [hours, minutes] = h.cierre.split(':').map(Number)
  return { hours, minutes }
})

// Strings legibles para el hint y para el POST
const minHoraStr = computed(() => _horarioDelDia(fechaProgramada.value)?.apertura || null)
const maxHoraStr = computed(() => _horarioDelDia(fechaProgramada.value)?.cierre   || null)

const fechaProgramadaStr = computed(() => {
  if (!fechaProgramada.value) return null
  const d = fechaProgramada.value
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
})
const horaProgramadaStr = computed(() => {
  if (!horaProgramada.value) return null
  const { hours, minutes } = horaProgramada.value
  return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`
})

// Limpiar hora si cambia la fecha (el rango válido puede cambiar)
watch(fechaProgramada, () => { horaProgramada.value = null })
const copiados       = ref({ banco: false, titular: false, clabe: false, cuenta: false })

// ── Recompensas ──
const historial = ref(null)

watch(telefono, async (val) => {
  const digits = val.replace(/\D/g, '')

  // Re-validar restricción de teléfono del cupón en cada cambio
  if (promoValidada.value?.telefono_restringido) {
    const telRest = promoValidada.value.telefono_restringido.replace(/\D/g, '')
    if (digits !== telRest) {
      promoValidada.value = null
      promoError.value    = 'telefono'
    }
  }

  // Si el cupón está en espera de teléfono correcto, re-validar cuando el número cambia
  if (promoError.value === 'telefono' && codigoPromo.value.trim().length >= 3) {
    onCodigoPromoInput()
  }

  if (digits.length !== 10) { historial.value = null; return }
  if (!props.restauranteId) return
  try {
    const data = await get('cliente-historial', { telefono: digits, restaurante_id: props.restauranteId }, false)
    historial.value = data.activo ? data : null
  } catch { historial.value = null }

  // Si el cliente tiene recompensa activa, limpiar cupón (no se permiten ambos)
  if (historial.value?.tiene_recompensa) {
    codigoPromo.value   = ''
    promoValidada.value = null
    promoError.value    = ''
  }
})

const descuento = computed(() => {
  if (!historial.value?.tiene_recompensa) return 0
  const { tipo, valor } = historial.value
  if (tipo === 'descuento_porcentaje') return Math.min(subtotal.value * (valor / 100), subtotal.value)
  return Math.min(valor, subtotal.value)
})

const descuentoPromo = computed(() => {
  if (!promoEfectiva.value) return 0
  if (historial.value?.tiene_recompensa) return 0
  const { tipo, valor } = promoEfectiva.value
  if (tipo === 'envio_gratis') return 0  // el delivery se anula, no hay descuento en subtotal
  if (tipo === 'descuento_porcentaje') return Math.min(subtotal.value * (valor / 100), subtotal.value)
  return Math.min(valor, subtotal.value)
})

const onCodigoPromoInput = () => {
  clearTimeout(_promoTimer)
  promoValidada.value = null
  promoError.value = ''
  const codigo = codigoPromo.value.trim()
  if (codigo.length < 3) return
  promoValidando.value = true
  _promoTimer = setTimeout(async () => {
    try {
      const data = await get('validar-codigo-promo', { codigo, restaurante_id: props.restauranteId }, false)
      if (data.valido) {
        // Validar restricción de teléfono si aplica
        if (data.telefono_restringido) {
          const telLimpio = telefono.value.replace(/\D/g, '')
          const telRest   = data.telefono_restringido.replace(/\D/g, '')
          if (telLimpio !== telRest) {
            promoValidada.value = null
            promoError.value    = 'telefono'
            return
          }
        }
        promoValidada.value = data
        promoError.value    = ''
      } else {
        promoValidada.value = null
        promoError.value    = data.motivo === 'agotado' ? 'agotado' : 'invalido'
      }
    } catch { promoError.value = 'invalido' }
    finally  { promoValidando.value = false }
  }, 600)
}

const copiarDato = async (campo, valor) => {
  await navigator.clipboard.writeText(valor)
  copiados.value[campo] = true
  setTimeout(() => { copiados.value[campo] = false }, 2000)
}

const umbralGratis = computed(() => {
  const v = props.pedidosConfig.pedidos_envio_gratis_desde
  return v !== null && v !== undefined ? parseFloat(v) : null
})

// Envío gratis ya cubierto solo por el umbral de monto (independiente de cupón)
const envioGratisPorUmbral = computed(() =>
  tipoEntrega.value === 'envio' && umbralGratis.value !== null && subtotal.value >= umbralGratis.value
)

// Cupón efectivo: null cuando el cupón envio_gratis no puede aportar nada.
// Casos: cliente eligió recoger (no hay delivery) o el umbral de monto ya cubre el envío.
// Evita consumir canjes sin que el cliente reciba beneficio real.
const promoEfectiva = computed(() => {
  if (!promoValidada.value) return null
  if (promoValidada.value.tipo === 'envio_gratis') {
    if (tipoEntrega.value !== 'envio') return null   // recoger en tienda — no hay delivery
    if (envioGratisPorUmbral.value) return null       // umbral ya lo cubre
  }
  return promoValidada.value
})

const envioEsGratis = computed(() =>
  tipoEntrega.value === 'envio' && (
    envioGratisPorUmbral.value ||
    promoEfectiva.value?.tipo === 'envio_gratis'
  )
)

const costoEnvio = computed(() => {
  if (tipoEntrega.value !== 'envio') return 0
  if (envioEsGratis.value) return 0
  return parseFloat(props.pedidosConfig.pedidos_envio_costo || 0)
})

const subtotal = computed(() =>
  carritoStore.items.reduce((s, i) => s + (i.precio_unitario ?? Number(i.producto.precio)) * i.cantidad, 0)
)

const total = computed(() => Math.max(0, subtotal.value + costoEnvio.value - descuento.value - descuentoPromo.value))

const tieneDatosTransferencia = computed(() =>
  !!(props.pedidosConfig.pedidos_trans_clabe || props.pedidosConfig.pedidos_trans_banco)
)

const esItemBloqueado = (item) => {
  const p = item.producto
  const sinStock = p.stock !== null && p.stock !== undefined && p.stock === 0
  const proximamente = p.disponible === false || p.disponible === 0
  return sinStock || proximamente
}

const hayItemsBloqueados = computed(() => carritoStore.items.some(esItemBloqueado))

const faltante = computed(() => {
  const den = parseFloat(denominacion.value)
  if (!den || den <= 0) return 0
  return Math.max(0, total.value - den)
})

// Si cambia a recoger y tenía terminal seleccionado, volver a efectivo
watch(tipoEntrega, (val) => {
  if (val !== 'envio' && metodoPago.value === 'terminal') {
    metodoPago.value = 'efectivo'
  }
})

const reducir = (idx) => {
  if (carritoStore.items[idx].cantidad > 1) {
    carritoStore.items[idx].cantidad--
  } else {
    carritoStore.items.splice(idx, 1)
  }
}

const validar = () => {
  if (!carritoStore.items.length) return 'El carrito está vacío.'
  if (props.pedidoProgramado && !fechaProgramada.value) return 'Selecciona la fecha para tu pedido programado.'
  if (props.pedidoProgramado && !horaProgramada.value) return 'Selecciona la hora para tu pedido programado.'
  if (!telefono.value.trim()) return 'Por favor escribe tu teléfono.'
  if (!nombre.value.trim()) return 'Por favor escribe tu nombre.'
  if (tipoEntrega.value === 'envio') {
    if (!direccion.value.trim()) return 'La dirección de entrega es requerida.'
  }
  return ''
}

const confirmar = async () => {
  errorMsg.value = validar()
  if (errorMsg.value) return

  enviando.value = true
  try {
    const items = carritoStore.items.map(i => ({
      producto_id: i.producto.id,
      nombre: i.producto.nombre,
      precio: i.precio_unitario ?? i.producto.precio,
      cantidad: i.cantidad,
      observacion: i.observacion || null,
      opciones: i.opciones || [],
    }))

    const body = {
      restaurante_id: props.restauranteId,
      nombre_cliente: nombre.value.trim(),
      telefono: telefono.value.trim() || null,
      tipo_entrega: tipoEntrega.value,
      direccion: direccion.value.trim() || null,
      referencia: referencia.value.trim() || null,
      metodo_pago: metodoPago.value,
      denominacion: denominacion.value ? parseFloat(denominacion.value) : null,
      mesa: props.mesa || null,
      items,
      subtotal: subtotal.value,
      costo_envio: costoEnvio.value,
      total: total.value,
      aplicar_recompensa:   historial.value?.tiene_recompensa || false,
      descuento_recompensa: descuento.value,
      descuento_promo:      descuentoPromo.value,
      codigo_promo: promoEfectiva.value ? codigoPromo.value.trim().toUpperCase() : null,
      fecha_programada: fechaProgramadaStr.value,
      hora_programada:  horaProgramadaStr.value,
    }

    const res = await post('pedidos', body, false)

    // Construir mensaje de WhatsApp
    const lineas = [
      `*-- NUEVO PEDIDO --*`,
      `*#${res.numero_pedido}*`,
      ``,
      `*Pedido:*`,
      ...carritoStore.items.flatMap((i, idx, arr) => {
        const precioUnit = i.precio_unitario ?? Number(i.producto.precio)
        const obs = i.observacion ? ` _(${i.observacion})_` : ''
        const linea = `  ${i.cantidad}x ${i.producto.nombre}${obs} — $${(precioUnit * i.cantidad).toFixed(2)}`
        const opLines = (i.opciones || []).map(o =>
          `    · ${o.opcion_nombre}${o.precio_extra > 0 ? ` +$${Number(o.precio_extra).toFixed(2)}` : ''}`
        )
        const sep = idx < arr.length - 1 ? ['──────────'] : []
        return [linea, ...opLines, ...sep]
      }),
      ``,
      `Subtotal: $${subtotal.value.toFixed(2)}`,
      ...(tipoEntrega.value === 'envio' ? [envioEsGratis.value ? `Envio: GRATIS` : `Envio: $${costoEnvio.value.toFixed(2)}`] : []),
      ...(descuento.value > 0 ? [`Descuento recompensa: -$${descuento.value.toFixed(2)}`] : []),
      ...(descuentoPromo.value > 0 ? [`Codigo ${codigoPromo.value.trim()}: -$${descuentoPromo.value.toFixed(2)}`] : []),
      `*Total: $${total.value.toFixed(2)}*`,
      ``,
      `*Entrega:* ${tipoEntrega.value === 'envio' ? 'A domicilio' : 'Recoger en local'}`,
      ...(tipoEntrega.value === 'envio' && direccion.value ? [`*Direccion:* ${direccion.value.trim()}`] : []),
      ...(tipoEntrega.value === 'envio' && referencia.value ? [`*Referencia:* ${referencia.value.trim()}`] : []),
      `*Tel:* ${telefono.value.trim()}`,
      `*Pago:* ${metodoPago.value === 'transferencia' ? 'Transferencia' : metodoPago.value === 'terminal' ? 'Terminal a domicilio' : 'Efectivo'}`,
      ...(metodoPago.value === 'efectivo' && denominacion.value ? [`Con: $${parseFloat(denominacion.value).toFixed(0)}`] : []),
      `*Nombre:* ${nombre.value.trim()}`,
      ...(props.mesa ? [`*Mesa:* ${props.mesa}`] : []),
      ...(props.pedidoProgramado && fechaProgramadaStr.value ? [`📅 *Pedido programado para:* ${fmtFechaDisplay(fechaProgramada.value)} a las ${horaProgramadaStr.value}`] : []),
    ]

    const waPhone = props.pedidosConfig.pedidos_whatsapp?.replace(/\D/g, '') || ''
    const waUrl   = `https://wa.me/${waPhone}?text=${encodeURIComponent(lineas.join('\n'))}`
    window.open(waUrl, '_blank')

    // Resetear form para el próximo pedido
    nombre.value         = ''
    telefono.value       = ''
    direccion.value      = ''
    referencia.value     = ''
    denominacion.value   = ''
    codigoPromo.value    = ''
    promoValidada.value  = null
    promoError.value     = ''
    historial.value      = null
    tipoEntrega.value    = 'recoger'
    metodoPago.value     = 'efectivo'
    errorMsg.value       = ''
    fechaProgramada.value = null
    horaProgramada.value  = null

    emit('confirmado')
  } catch (err) {
    if (err.status === 409 && err.data?.tipo === 'stock_agotado') {
      const productoId = err.data.producto_id
      const nombreProd = err.data.nombre || carritoStore.items.find(i => i.producto.id === productoId)?.producto.nombre || 'Un platillo'
      carritoStore.eliminar(productoId)
      errorMsg.value = `"${nombreProd}" ya no está disponible y fue eliminado de tu carrito. Revisa tu pedido y confirma de nuevo.`
      emit('stock-agotado', err.message)
    } else {
      errorMsg.value = 'Error al enviar el pedido. Intenta de nuevo.'
    }
  } finally {
    enviando.value = false
  }
}
</script>

<style scoped>
.checkout-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  z-index: 600;
  display: flex;
  align-items: flex-end;
  animation: fadeIn 0.2s ease-out;
}

@media (min-width: 640px) {
  .checkout-overlay { align-items: center; padding: 20px; }
  .checkout-panel   { border-radius: 20px; max-height: 88vh; }
}

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp {
  from { transform: translateY(60px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}

.checkout-panel {
  background: #fff;
  width: 100%;
  max-width: 560px;
  margin: 0 auto;
  border-radius: 24px 24px 0 0;
  max-height: 92vh;
  display: flex;
  flex-direction: column;
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.checkout-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px 14px;
  border-bottom: 1px solid #f0f0f0;
  flex-shrink: 0;
}

.checkout-header h2 { font-size: 1.1rem; font-weight: 800; color: #1a1a1a; margin: 0; }

.btn-cerrar {
  width: 32px; height: 32px; border-radius: 50%; border: none;
  background: #f0f0f0; color: #555; font-size: 0.9rem; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}

.checkout-body {
  overflow-y: auto;
  padding: 10px 12px 28px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  background: #f4f4f6;
}

/* ── Secciones como tarjetas ── */
.checkout-section {
  display: flex;
  flex-direction: column;
  gap: 10px;
  background: #fff;
  border-radius: 12px;
  padding: 12px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.07);
}

.section-title {
  font-size: 0.78rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: var(--accent, #FF6B35);
  margin: 0;
  padding-bottom: 4px;
  border-bottom: 1px solid #f0f0f0;
}

/* ── Items ── */
.items-lista { display: flex; flex-direction: column; gap: 8px; }

.item-row {
  display: flex;
  flex-direction: column;
  gap: 8px;
  background: #fafafa;
  border-radius: 8px;
  padding: 10px 12px;
  border: 1px solid #efefef;
}

.item-top {
  display: flex;
  align-items: center;
  gap: 8px;
}

.item-bottom {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.item-cant-ctrl {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}

.cant-btn {
  width: 26px; height: 26px; border-radius: 50%; border: 1.5px solid #e0e0e0;
  background: #fff; color: #555; font-size: 1rem; font-weight: 700;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  line-height: 1;
}
.cant-btn:hover { background: #f0f0f0; }

.cant-num { font-weight: 800; font-size: 0.95rem; min-width: 18px; text-align: center; }

.item-nombre { flex: 1; font-size: 0.9rem; font-weight: 700; color: #1a1a1a; min-width: 0; }

.item-opciones {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-bottom: 2px;
}

.chip-opcion {
  display: inline-block;
  background: var(--accent-light, #fff0e8);
  color: var(--accent, #FF6B35);
  border: 1px solid rgba(255,107,53,0.25);
  border-radius: 20px;
  padding: 2px 8px;
  font-size: 0.72rem;
  font-weight: 600;
  white-space: normal;
  word-break: break-word;
}

.item-obs-input {
  width: 100%;
  padding: 6px 8px; border: 1px solid #e0e0e0; border-radius: 6px;
  font-size: 0.82rem; font-family: inherit; outline: none; box-sizing: border-box;
}
.item-obs-input:focus { border-color: var(--accent, #FF6B35); }

.item-subtotal { font-size: 0.9rem; font-weight: 800; color: var(--accent, #FF6B35); flex-shrink: 0; }

/* ── Ítem bloqueado ── */
.item-bloqueado {
  opacity: 0.75;
  background: rgba(0,0,0,0.03);
  border-radius: 8px;
}

.chip-item-bloqueado {
  display: block;
  font-size: 0.72rem;
  color: #c0392b;
  font-weight: 600;
  margin-top: 2px;
}

/* ── Alerta ítems bloqueados ── */
.alerta-bloqueados {
  background: #fff3cd;
  border: 1px solid #ffc107;
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 0.85rem;
  font-weight: 600;
  color: #856404;
  margin-bottom: 8px;
}

/* ── Opciones card (entrega) ── */
.opciones-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.opcion-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 14px 10px;
  border: 2px solid #e0e0e0;
  border-radius: 12px;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
  text-align: center;
}

.opcion-card input { display: none; }

.opcion-card.selected {
  border-color: var(--accent, #FF6B35);
  background: #fff8f4;
}

.opcion-icon { font-size: 1.6rem; }
.opcion-label { font-size: 0.88rem; font-weight: 700; color: #1a1a1a; }
.opcion-sub { font-size: 0.75rem; color: #999; }

/* ── Opciones filas (pago) ── */
.opciones-filas {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.opcion-fila {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border: 2px solid #e0e0e0;
  border-radius: 12px;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
}

.opcion-fila input { display: none; }

.opcion-fila.selected {
  border-color: var(--accent, #FF6B35);
  background: #fff8f4;
}

.opcion-fila-icon { font-size: 1.4rem; flex-shrink: 0; }
.opcion-fila-label { font-size: 0.9rem; font-weight: 700; color: #1a1a1a; flex: 1; }
.opcion-fila-check {
  font-size: 0.85rem;
  font-weight: 800;
  color: var(--accent, #FF6B35);
  flex-shrink: 0;
}
.envio-gratis-badge { color: #2e7d32; font-weight: 700; }
.envio-gratis-hint { display: block; color: #4caf50; margin-top: 2px; }
.envio-gratis-total { color: #2e7d32; font-weight: 700; }

/* ── Campos ── */
.campos { display: flex; flex-direction: column; gap: 12px; }

.campo { display: flex; flex-direction: column; gap: 5px; }
.campo label { font-size: 0.8rem; font-weight: 600; color: #555; }
.campo input, .campo textarea {
  padding: 10px 12px;
  border: 1.5px solid #e0e0e0;
  border-radius: 9px;
  font-size: 0.9rem;
  font-family: inherit;
  outline: none;
  transition: border-color 0.2s;
}
.campo input:focus, .campo textarea:focus { border-color: var(--accent, #FF6B35); }
.campo textarea { resize: none; }

/* ── Cuponera ── */
.cuponera {
  margin-top: 8px;
  padding: 10px 14px;
  background: #fffbf0;
  border: 1.5px solid #f5c842;
  border-radius: 10px;
}
.cuponera-recompensa {
  font-size: 0.88rem;
  font-weight: 700;
  color: #b45309;
  text-align: center;
}
.cuponera-texto {
  font-size: 0.8rem;
  color: #666;
  margin: 0 0 4px;
}
.cuponera-premio {
  font-size: 0.75rem;
  color: #b45309;
  font-weight: 600;
  margin: 0 0 8px;
}
.cuponera-sellos {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  align-items: center;
}
.sello {
  font-size: 1.1rem;
  color: #ddd;
  line-height: 1;
  transition: color 0.15s;
}
.sello-lleno { color: #f5c842; }
.cuponera-resto { font-size: 0.75rem; color: #aaa; margin-left: 4px; }

/* ── Código promo ── */
.promo-wrap { position: relative; display: flex; align-items: center; }
.promo-wrap input { flex: 1; padding-right: 90px; }
.promo-status {
  position: absolute; right: 10px;
  font-size: 0.78rem; font-weight: 700; white-space: nowrap; pointer-events: none;
}
.promo-wait { color: #aaa; }
.promo-ok   { color: #27ae60; }
.promo-err  { color: #e74c3c; }

/* ── Fila descuento en totales ── */
.total-descuento { color: #2e7d32; font-weight: 600; }

/* ── Transferencia ── */
.trans-box {
  margin-top: 12px;
  background: #f8f8ff;
  border: 1.5px solid #e0e0f0;
  border-radius: 10px;
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.trans-title { font-size: 0.82rem; font-weight: 700; color: #555; margin: 0 0 4px; }

.trans-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
.trans-label { font-size: 0.78rem; color: #aaa; flex-shrink: 0; }
.trans-val-group { display: flex; align-items: center; gap: 8px; }
.trans-val { font-size: 0.88rem; color: #1a1a1a; text-align: right; }
.trans-clabe { font-family: monospace; font-size: 0.95rem; letter-spacing: 0.05em; }
.btn-copiar-dato {
  font-size: 0.72rem; font-weight: 700; padding: 3px 8px;
  border: 1.5px solid #ddd; border-radius: 5px;
  background: #fff; color: #555; cursor: pointer;
  flex-shrink: 0; transition: background 0.15s;
}
.btn-copiar-dato:hover { background: #f0f0f0; }

/* ── Totales ── */
.totales {
  background: #fff;
  border-radius: 12px;
  padding: 12px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.07);
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.total-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.9rem;
  color: #555;
}

.total-final { font-size: 1.05rem; color: #1a1a1a; border-top: 1px solid #e0e0e0; padding-top: 8px; margin-top: 4px; }

/* ── Confirmar ── */
.warn-denominacion { font-size: 0.8rem; color: #b45309; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 6px; padding: 5px 8px; margin: 4px 0 0; }

.error-msg { font-size: 0.85rem; color: #c62828; text-align: center; margin: 0; }

.hint-whatsapp {
  text-align: center;
  font-size: 0.78rem;
  color: #888;
  margin: 0;
}

/* .btn-confirmar extiende .btn-primary (global en theme.css) — solo sobreescribe tamaño */
.btn-confirmar {
  width: 100%;
  padding: 15px;
  border-radius: 14px;
  font-size: 1rem;
}

.seccion-programado {
  border-left: 3px solid #6C8EBF;
  background: color-mix(in srgb, #6C8EBF 8%, white);
}
.seccion-programado .campos {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
@media (max-width: 480px) {
  .seccion-programado .campos { grid-template-columns: 1fr; }
}
.seccion-programado .campo label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: #555;
  margin-bottom: 4px;
}
.campo-hint {
  display: block;
  color: #3A5A8C;
  font-size: 0.78rem;
  margin-top: 4px;
}

.banner-programado {
  background: #4a7abf;
  color: #fff;
  font-size: 0.88rem;
  font-weight: 600;
  padding: 10px 20px;
  text-align: center;
}
</style>
