<template>
  <div class="tab-content">
    <div class="card">
      <div class="card-header">
        <h2>Pedidos recibidos</h2>
        <button @click="loadPedidos" class="btn-refresh"><SvgIcon :path="mdiRefresh" :size="16" /> Actualizar</button>
      </div>
      <div class="card-body no-pad">
        <div v-if="loadingPedidos" class="loading-inline"><div class="spinner"></div></div>
        <div v-else-if="!pedidos.length" class="empty-state" style="padding:40px">
          <SvgIcon :path="mdiCart" :size="40" />
          <p>Sin pedidos todavía.</p>
        </div>
        <div v-else class="pedidos-lista">
          <div v-for="ped in pedidos" :key="ped.id" class="pedido-card">
            <div class="pedido-header">
              <div class="pedido-id">
                <strong>#{{ ped.numero_pedido }}</strong>
                <span class="pedido-hora">{{ formatHora(ped.created_at) }}</span>
              </div>
              <span :class="['pedido-status', 'status-' + ped.status]">{{ statusLabel(ped.status) }}</span>
            </div>
            <div class="pedido-body">
              <div class="pedido-cliente">
                <span><SvgIcon :path="mdiAccount" :size="14" /> {{ ped.nombre_cliente }}</span>
                <span v-if="ped.telefono"><SvgIcon :path="mdiPhone" :size="14" /> {{ ped.telefono }}</span>
                <span v-if="ped.mesa"><SvgIcon :path="mdiSeat" :size="14" /> Mesa {{ ped.mesa }}</span>
              </div>
              <div class="pedido-entrega">
                <span class="pedido-tag" :class="ped.tipo_entrega === 'envio' ? 'tag-envio' : 'tag-recoger'">
                  <SvgIcon :path="ped.tipo_entrega === 'envio' ? mdiMoped : mdiHome" :size="13" />
                  {{ ped.tipo_entrega === 'envio' ? 'Envío a domicilio' : 'Recoger en local' }}
                </span>
                <span v-if="ped.tipo_entrega === 'envio' && ped.direccion" class="pedido-dir">{{ ped.direccion }}</span>
                <span class="pedido-tag tag-pago">
                  <SvgIcon :path="ped.metodo_pago === 'transferencia' ? mdiBank : mdiCash" :size="13" />
                  {{ ped.metodo_pago === 'transferencia' ? 'Transferencia' : 'Efectivo' }}
                </span>
                <span v-if="ped.denominacion" class="pedido-denominacion">Con ${{ Number(ped.denominacion).toFixed(0) }}</span>
              </div>
              <div class="pedido-items-list">
                <div v-for="item in ped.items" :key="item.id" class="pedido-item-row">
                  <span class="pedido-item-cant">{{ item.cantidad }}×</span>
                  <span class="pedido-item-nombre">{{ item.nombre_producto }}</span>
                  <span v-if="item.observacion" class="pedido-item-obs">— {{ item.observacion }}</span>
                  <span class="pedido-item-precio">${{ Number(item.subtotal).toFixed(2) }}</span>
                </div>
              </div>
              <div class="pedido-totales">
                <span v-if="ped.costo_envio > 0">Envío: ${{ Number(ped.costo_envio).toFixed(2) }}</span>
                <span v-if="Number(ped.descuento_recompensa) > 0" class="pedido-descuento">🎁 Recompensa: -${{ Number(ped.descuento_recompensa).toFixed(2) }}</span>
                <span v-if="Number(ped.descuento_promo) > 0" class="pedido-descuento">🏷️ {{ ped.codigo_promo }}: -${{ Number(ped.descuento_promo).toFixed(2) }}</span>
                <strong>Total: ${{ Number(ped.total).toFixed(2) }}</strong>
              </div>
            </div>
            <div class="pedido-acciones">
              <button v-if="ped.status === 'nuevo'"          @click="cambiarStatus(ped.id, 'visto')"          class="btn-status btn-visto">Visto</button>
              <button v-if="ped.status === 'visto'"          @click="cambiarStatus(ped.id, 'en_preparacion')" class="btn-status btn-prep">En preparación</button>
              <button v-if="ped.status === 'en_preparacion'" @click="cambiarStatus(ped.id, 'listo')"          class="btn-status btn-listo"><SvgIcon :path="mdiCheck" :size="14" /> Listo</button>
              <button v-if="ped.status === 'listo'"          @click="cambiarStatus(ped.id, 'entregado')"      class="btn-status btn-entregado"><SvgIcon :path="mdiCheckCircle" :size="14" /> Entregado</button>
              <button v-if="!['entregado','cancelado'].includes(ped.status)" @click="cambiarStatus(ped.id, 'cancelado')" class="btn-status btn-cancelar">Cancelar</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onUnmounted } from 'vue'
import {
  mdiCart, mdiRefresh, mdiAccount, mdiPhone, mdiSeat,
  mdiMoped, mdiHome, mdiBank, mdiCash, mdiCheck, mdiCheckCircle,
} from '@mdi/js'
import { useApi } from '../../../composables/useApi.js'
import SvgIcon from '../../SvgIcon.vue'

const props = defineProps({
  restauranteId: { type: Number, required: true },
  active:        { type: Boolean, default: false },
})

const emit = defineEmits(['notif'])

const { get, put } = useApi()

const pedidos        = ref([])
const loadingPedidos = ref(false)
let   pedidosInterval = null

const formatHora = (ts) => {
  const d = new Date(ts)
  return d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }) + ' · ' + d.toLocaleDateString('es-MX', { day: 'numeric', month: 'short' })
}

const statusLabel = (s) => ({ nuevo: 'Nuevo', visto: 'Visto', en_preparacion: 'En preparación', listo: 'Listo', entregado: 'Entregado', cancelado: 'Cancelado' })[s] || s

async function loadPedidos() {
  loadingPedidos.value = true
  try {
    const res = await get('pedidos', { restaurante_id: props.restauranteId })
    pedidos.value = res.pedidos || []
  } finally {
    loadingPedidos.value = false
  }
}

async function cambiarStatus(id, status) {
  try {
    await put('pedidos', { status }, { id })
    await loadPedidos()
    emit('notif', { texto: 'Pedido actualizado', tipo: 'ok' })
  } catch (err) {
    emit('notif', { texto: err.message, tipo: 'error' })
  }
}

// Auto-refresh mientras el tab esté activo
watch(() => props.active, (isActive) => {
  clearInterval(pedidosInterval)
  if (isActive) {
    loadPedidos()
    pedidosInterval = setInterval(loadPedidos, 30000)
  }
})

onUnmounted(() => clearInterval(pedidosInterval))
</script>

<style scoped>
.btn-refresh { display: inline-flex; align-items: center; gap: 5px; background: #f5f5f5; border: 1px solid #e0e0e0; color: #555; padding: 5px 12px; border-radius: 7px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: background 0.15s; }
.btn-refresh:hover { background: #ebebeb; }

.pedidos-lista { display: flex; flex-direction: column; }
.pedido-card   { border-bottom: 1px solid #f0f0f0; padding: 16px 20px; }
.pedido-card:last-child { border-bottom: none; }

.pedido-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.pedido-id     { display: flex; align-items: center; gap: 10px; }
.pedido-id strong { font-size: 0.95rem; color: #1a1a1a; }
.pedido-hora   { font-size: 0.75rem; color: #aaa; }

.pedido-status { padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
.status-nuevo          { background: #ffebee; color: #c62828; }
.status-visto          { background: #fff3e0; color: #e65100; }
.status-en_preparacion { background: #e3f2fd; color: #1565c0; }
.status-listo          { background: #e8f5e9; color: #2e7d32; }
.status-entregado      { background: #f5f5f5; color: #9e9e9e; }
.status-cancelado      { background: #fce4ec; color: #880e4f; }

.pedido-body     { display: flex; flex-direction: column; gap: 8px; }
.pedido-cliente  { display: flex; flex-wrap: wrap; gap: 12px; font-size: 0.85rem; color: #555; }
.pedido-entrega  { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; font-size: 0.82rem; }
.pedido-tag      { padding: 2px 8px; border-radius: 5px; font-weight: 600; background: #f0f0f0; color: #555; }
.tag-envio   { background: #e3f2fd; color: #1565c0; }
.tag-recoger { background: #f3e5f5; color: #6a1b9a; }
.tag-pago    { background: #e8f5e9; color: #2e7d32; }
.pedido-dir          { font-size: 0.8rem; color: #888; }
.pedido-denominacion { font-size: 0.8rem; color: #888; }

.pedido-items-list { background: #fafafa; border-radius: 8px; padding: 10px 12px; display: flex; flex-direction: column; gap: 5px; }
.pedido-item-row    { display: flex; align-items: baseline; gap: 6px; font-size: 0.85rem; }
.pedido-item-cant   { font-weight: 700; color: var(--accent); min-width: 24px; }
.pedido-item-nombre { flex: 1; font-weight: 600; color: #1a1a1a; }
.pedido-item-obs    { font-size: 0.78rem; color: #999; font-style: italic; }
.pedido-item-precio { font-weight: 700; color: #555; }

.pedido-totales { display: flex; justify-content: flex-end; align-items: center; gap: 12px; font-size: 0.88rem; color: #888; flex-wrap: wrap; }
.pedido-totales strong { color: #1a1a1a; font-size: 0.95rem; }
.pedido-descuento { font-size: 0.78rem; font-weight: 700; color: #27ae60; background: #eafaf1; border-radius: 6px; padding: 2px 8px; }

.pedido-acciones { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
.btn-status  { padding: 6px 14px; border: none; border-radius: 7px; font-size: 0.82rem; font-weight: 700; cursor: pointer; transition: opacity 0.15s; }
.btn-status:hover { opacity: 0.82; }
.btn-visto     { background: #fff3e0; color: #e65100; }
.btn-prep      { background: #e3f2fd; color: #1565c0; }
.btn-listo     { background: #e8f5e9; color: #2e7d32; }
.btn-entregado { background: #c8e6c9; color: #1b5e20; }
.btn-cancelar  { background: #ffebee; color: #c62828; }
</style>
