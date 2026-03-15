<template>
  <div class="checkout-overlay" @click.self="$emit('close')">
    <div class="checkout-panel">

      <!-- Header -->
      <div class="checkout-header">
        <h2>Tu pedido</h2>
        <button class="btn-cerrar" @click="$emit('close')">✕</button>
      </div>

      <div class="checkout-body">

        <!-- ── 1. Resumen del carrito ── -->
        <section class="checkout-section">
          <h3 class="section-title">Platillos</h3>
          <div class="items-lista">
            <div v-for="(item, idx) in carritoLocal" :key="idx" class="item-row">
              <div class="item-cant-ctrl">
                <button class="cant-btn" @click="reducir(idx)">−</button>
                <span class="cant-num">{{ item.cantidad }}</span>
                <button class="cant-btn" @click="item.cantidad++">+</button>
              </div>
              <div class="item-info">
                <span class="item-nombre">{{ item.producto.nombre }}</span>
                <input
                  :value="item.observacion"
                  @input="item.observacion = ucfirst($event.target.value)"
                  class="item-obs-input"
                  placeholder="Observación (opcional)"
                  maxlength="100"
                />
              </div>
              <span class="item-subtotal">${{ (item.producto.precio * item.cantidad).toFixed(2) }}</span>
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
              <span class="opcion-sub">+${{ Number(pedidosConfig.pedidos_envio_costo || 0).toFixed(2) }}</span>
            </label>
          </div>
        </section>

        <!-- ── 3. Datos del cliente ── -->
        <section class="checkout-section">
          <h3 class="section-title">Tus datos</h3>
          <div class="campos">
            <div class="campo">
              <label>Nombre *</label>
              <input :value="nombre" @input="nombre = ucfirst($event.target.value)" placeholder="¿Cómo te llamamos?" maxlength="60" />
            </div>
            <template v-if="tipoEntrega === 'envio'">
              <div class="campo">
                <label>Teléfono *</label>
                <input v-model="telefono" type="tel" placeholder="Para coordinar la entrega" maxlength="10" />
              </div>
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
          <div class="opciones-grid">
            <label :class="['opcion-card', { selected: metodoPago === 'efectivo' }]">
              <input type="radio" v-model="metodoPago" value="efectivo" />
              <span class="opcion-icon">💵</span>
              <span class="opcion-label">Efectivo</span>
            </label>
            <label
              v-if="pedidosConfig.pedidos_trans_activo"
              :class="['opcion-card', { selected: metodoPago === 'transferencia' }]"
            >
              <input type="radio" v-model="metodoPago" value="transferencia" />
              <span class="opcion-icon">🏦</span>
              <span class="opcion-label">Transferencia</span>
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
          <div v-if="costoEnvio > 0" class="total-row">
            <span>Envío</span>
            <span>${{ costoEnvio.toFixed(2) }}</span>
          </div>
          <div class="total-row total-final">
            <strong>Total</strong>
            <strong>${{ total.toFixed(2) }}</strong>
          </div>
        </div>

        <!-- Error de validación -->
        <p v-if="errorMsg" class="error-msg">{{ errorMsg }}</p>

        <!-- Botón confirmar -->
        <button
          class="btn-primary btn-confirmar"
          :disabled="enviando"
          @click="confirmar"
        >
          {{ enviando ? 'Enviando...' : '✓ Confirmar y enviar pedido por WhatsApp' }}
        </button>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useApi } from '../../composables/useApi.js'
import { ucfirst } from '../../utils/ucfirst.js'

const props = defineProps({
  carrito:       { type: Array,  required: true },
  pedidosConfig: { type: Object, required: true },
  mesa:          { type: String, default: null  },
  restauranteId: { type: [Number, String], required: true }
})

const emit = defineEmits(['close', 'confirmado'])
const { post } = useApi()

// Copia local del carrito para poder editar cantidades y observaciones
const carritoLocal = ref(props.carrito.map(i => ({ ...i, observacion: i.observacion || '' })))

// Sincronizar cuando el padre agrega/quita items (necesario con v-show que no remonta el componente)
watch(() => props.carrito, (newCarrito) => {
  carritoLocal.value = newCarrito.map(i => ({ ...i, observacion: i.observacion || '' }))
}, { deep: true })

const tipoEntrega  = ref('recoger')
const metodoPago   = ref('efectivo')
const nombre       = ref('')
const telefono     = ref('')
const direccion    = ref('')
const referencia   = ref('')
const denominacion = ref('')
const enviando     = ref(false)
const errorMsg     = ref('')
const copiados     = ref({ banco: false, titular: false, clabe: false, cuenta: false })

const copiarDato = async (campo, valor) => {
  await navigator.clipboard.writeText(valor)
  copiados.value[campo] = true
  setTimeout(() => { copiados.value[campo] = false }, 2000)
}

const costoEnvio = computed(() =>
  tipoEntrega.value === 'envio' ? parseFloat(props.pedidosConfig.pedidos_envio_costo || 0) : 0
)

const subtotal = computed(() =>
  carritoLocal.value.reduce((s, i) => s + i.producto.precio * i.cantidad, 0)
)

const total = computed(() => subtotal.value + costoEnvio.value)

const tieneDatosTransferencia = computed(() =>
  !!(props.pedidosConfig.pedidos_trans_clabe || props.pedidosConfig.pedidos_trans_banco)
)

const faltante = computed(() => {
  const den = parseFloat(denominacion.value)
  if (!den || den <= 0) return 0
  return Math.max(0, total.value - den)
})

const reducir = (idx) => {
  if (carritoLocal.value[idx].cantidad > 1) {
    carritoLocal.value[idx].cantidad--
  } else {
    carritoLocal.value.splice(idx, 1)
  }
}

const validar = () => {
  if (!carritoLocal.value.length) return 'El carrito está vacío.'
  if (!nombre.value.trim()) return 'Por favor escribe tu nombre.'
  if (tipoEntrega.value === 'envio') {
    if (!telefono.value.trim()) return 'El teléfono es requerido para envío a domicilio.'
    if (!direccion.value.trim()) return 'La dirección de entrega es requerida.'
  }
  return ''
}

const confirmar = async () => {
  errorMsg.value = validar()
  if (errorMsg.value) return

  enviando.value = true
  try {
    const items = carritoLocal.value.map(i => ({
      producto_id: i.producto.id,
      nombre: i.producto.nombre,
      precio: i.producto.precio,
      cantidad: i.cantidad,
      observacion: i.observacion || null,
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
    }

    const res = await post('pedidos', body, false)

    // Construir mensaje de WhatsApp
    const lineas = [
      `*-- NUEVO PEDIDO --*`,
      `*#${res.numero_pedido}*`,
      ``,
      `*Pedido:*`,
      ...carritoLocal.value.map(i => {
        const obs = i.observacion ? ` _(${i.observacion})_` : ''
        return `  ${i.cantidad}x ${i.producto.nombre}${obs} — $${(i.producto.precio * i.cantidad).toFixed(2)}`
      }),
      ``,
      `Subtotal: $${subtotal.value.toFixed(2)}`,
      ...(costoEnvio.value > 0 ? [`Envio: $${costoEnvio.value.toFixed(2)}`] : []),
      `*Total: $${total.value.toFixed(2)}*`,
      ``,
      `*Entrega:* ${tipoEntrega.value === 'envio' ? 'A domicilio' : 'Recoger en local'}`,
      ...(tipoEntrega.value === 'envio' && direccion.value ? [`*Direccion:* ${direccion.value.trim()}`] : []),
      ...(tipoEntrega.value === 'envio' && referencia.value ? [`*Referencia:* ${referencia.value.trim()}`] : []),
      ...(telefono.value ? [`*Tel:* ${telefono.value.trim()}`] : []),
      `*Pago:* ${metodoPago.value === 'transferencia' ? 'Transferencia' : 'Efectivo'}`,
      ...(metodoPago.value === 'efectivo' && denominacion.value ? [`Con: $${parseFloat(denominacion.value).toFixed(0)}`] : []),
      `*Nombre:* ${nombre.value.trim()}`,
      ...(props.mesa ? [`*Mesa:* ${props.mesa}`] : []),
    ]

    const waPhone = props.pedidosConfig.pedidos_whatsapp?.replace(/\D/g, '') || ''
    const waUrl   = `https://wa.me/${waPhone}?text=${encodeURIComponent(lineas.join('\n'))}`
    window.open(waUrl, '_blank')

    emit('confirmado')
  } catch (err) {
    errorMsg.value = 'Error al enviar el pedido. Intenta de nuevo.'
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
  padding: 16px 20px 32px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* ── Secciones ── */
.checkout-section { display: flex; flex-direction: column; gap: 12px; }

.section-title {
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #aaa;
  margin: 0;
}

/* ── Items ── */
.items-lista { display: flex; flex-direction: column; gap: 10px; }

.item-row {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #fafafa;
  border-radius: 10px;
  padding: 10px 12px;
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

.item-info { flex: 1; display: flex; flex-direction: column; gap: 5px; min-width: 0; }
.item-nombre { font-size: 0.9rem; font-weight: 700; color: #1a1a1a; }

.item-obs-input {
  padding: 5px 8px; border: 1px solid #e0e0e0; border-radius: 6px;
  font-size: 0.8rem; font-family: inherit; outline: none;
}
.item-obs-input:focus { border-color: var(--accent, #FF6B35); }

.item-subtotal { font-size: 0.9rem; font-weight: 800; color: var(--accent, #FF6B35); flex-shrink: 0; }

/* ── Opciones card ── */
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
  background: #f9f9f9;
  border-radius: 10px;
  padding: 14px 16px;
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

/* .btn-confirmar extiende .btn-primary (global en theme.css) — solo sobreescribe tamaño */
.btn-confirmar {
  width: 100%;
  padding: 15px;
  border-radius: 14px;
  font-size: 1rem;
}
</style>
