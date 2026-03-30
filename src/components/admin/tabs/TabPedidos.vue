<template>
  <div class="tab-content">

    <!-- ── Corte de ventas (colapsable) ── -->
    <div class="card">
      <div class="card-header collapsible" @click="reporteAbierto = !reporteAbierto">
        <h2><SvgIcon :path="mdiChartBar" :size="18" /> Corte de ventas</h2>
        <span class="chevron">{{ reporteAbierto ? '▲' : '▼' }}</span>
      </div>
      <div v-show="reporteAbierto" class="card-body">
        <div class="periodo-pills">
          <button
            v-for="p in periodos" :key="p.id"
            :class="['periodo-pill', { active: periodoActivo === p.id }]"
            @click.stop="selPeriodo(p.id)"
          >{{ p.label }}</button>
        </div>
        <p v-if="labelRango" class="rango-label">{{ labelRango }}</p>
        <div v-if="periodoActivo === 'custom'" class="fechas-custom">
          <VueDatePicker
            v-show="periodoActivo === 'custom'"
            v-model="pickerDesde"
            :enable-time-picker="false"
            :max-date="hoyDate"
            :format="fmtDia"
            :teleport="true"
            auto-apply
            placeholder="Desde"
            @update:model-value="v => fechaDesde = fmtIso(v)"
          />
          <span class="fechas-sep">—</span>
          <VueDatePicker
            v-show="periodoActivo === 'custom'"
            v-model="pickerHasta"
            :enable-time-picker="false"
            :min-date="pickerDesde || undefined"
            :max-date="hoyDate"
            :format="fmtDia"
            :teleport="true"
            auto-apply
            placeholder="Hasta (opcional)"
            @update:model-value="v => fechaHasta = fmtIso(v)"
          />
          <button class="btn-primary btn-sm" @click="cargarReporte" :disabled="!pickerDesde">Consultar</button>
        </div>

        <div v-if="reporteCargando" class="loading-inline" style="margin-top:12px"><div class="spinner"></div></div>

        <template v-else-if="resumen">
          <div class="reporte-cards">
            <div class="stat-card stat-principal">
              <span class="stat-label">Ingresos netos</span>
              <span class="stat-valor">${{ fmt(resumen.ingresos_netos) }}</span>
              <span class="stat-sub">{{ resumen.total_pedidos }} pedido{{ resumen.total_pedidos !== 1 ? 's' : '' }}</span>
            </div>
            <div class="stat-card">
              <span class="stat-label">Efectivo</span>
              <span class="stat-valor">${{ fmt(resumen.efectivo) }}</span>
            </div>
            <div class="stat-card">
              <span class="stat-label">Transferencia</span>
              <span class="stat-valor">${{ fmt(resumen.transferencia) }}</span>
            </div>
            <div class="stat-card">
              <span class="stat-label">Terminal</span>
              <span class="stat-valor">${{ fmt(resumen.terminal) }}</span>
            </div>
            <div class="stat-card stat-envio">
              <span class="stat-label">Ingresos por envío</span>
              <span class="stat-valor">${{ fmt(resumen.total_envios) }}</span>
            </div>
            <div v-if="resumen.desc_recompensa > 0" class="stat-card stat-descuento">
              <span class="stat-label">Desc. recompensa</span>
              <span class="stat-valor">-${{ fmt(resumen.desc_recompensa) }}</span>
            </div>
            <div v-if="resumen.desc_promo > 0" class="stat-card stat-descuento">
              <span class="stat-label">Desc. cupón</span>
              <span class="stat-valor">-${{ fmt(resumen.desc_promo) }}</span>
            </div>
            <div v-if="resumen.cupones_envio_gratis > 0" class="stat-card stat-descuento">
              <span class="stat-label">Envíos gratis (cupón)</span>
              <span class="stat-valor">{{ resumen.cupones_envio_gratis }} pedido{{ resumen.cupones_envio_gratis !== 1 ? 's' : '' }}</span>
            </div>
            <div v-if="resumen.ajustes_negativos > 0" class="stat-card stat-descuento">
              <span class="stat-label">Ajustes manuales</span>
              <span class="stat-valor">-${{ fmt(resumen.ajustes_negativos) }}</span>
            </div>
          </div>

          <div v-if="porDia.length > 1" style="margin-top:14px">
            <table class="dia-tabla">
              <thead>
                <tr>
                  <th>Día</th>
                  <th class="txt-right">Pedidos</th>
                  <th class="txt-right">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="d in porDia" :key="d.dia">
                  <td>{{ fmtFecha(d.dia) }}</td>
                  <td class="txt-right">{{ d.pedidos }}</td>
                  <td class="txt-right">${{ fmt(d.total_dia) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <p v-if="resumen.total_pedidos === 0" class="empty-reporte">Sin pedidos en este período.</p>
        </template>
      </div>
    </div>

    <!-- ── Pedidos recibidos ── -->
    <div class="card">
      <div class="card-header">
        <h2>Pedidos recibidos</h2>
        <button @click="loadPedidos" class="btn-refresh"><SvgIcon :path="mdiRefresh" :size="16" /> Actualizar</button>
      </div>
      <div class="card-body no-pad">
        <div v-if="loadingPedidos && !pedidos.length" class="loading-inline"><div class="spinner"></div></div>
        <div v-else-if="!pedidos.length" class="empty-state" style="padding:40px">
          <SvgIcon :path="mdiCart" :size="40" />
          <p>Sin pedidos todavía.</p>
        </div>
        <div v-else class="pedidos-lista">
          <div v-for="ped in pedidos" :key="ped.id" :class="['pedido-card', { 'pedido-card--programado': ped.fecha_programada }]">
            <div class="pedido-header">
              <div class="pedido-id">
                <strong>#{{ ped.numero_pedido }}</strong>
                <span class="pedido-hora">{{ formatHora(ped.created_at) }}</span>
                <span v-if="ped.fecha_programada" class="chip-programado">
                  📅 {{ fmtFechaProgramada(ped.fecha_programada, ped.hora_programada) }}
                </span>
              </div>
              <span :class="['pedido-status', 'status-' + ped.status]">{{ statusLabel(ped.status) }}</span>
            </div>
            <div class="pedido-body">
              <div class="pedido-cliente">
                <span><SvgIcon :path="mdiAccount" :size="14" /> {{ ped.nombre_cliente }}</span>
                <a v-if="ped.telefono" :href="`https://wa.me/${ped.telefono.replace(/\D/g, '')}`" target="_blank" class="pedido-tel-wa">
                  <SvgIcon :path="mdiWhatsapp" :size="14" /> {{ ped.telefono }}
                </a>
                <span v-if="ped.mesa"><SvgIcon :path="mdiSeat" :size="14" /> Mesa {{ ped.mesa }}</span>
              </div>
              <div class="pedido-entrega">
                <span class="pedido-tag" :class="ped.tipo_entrega === 'envio' ? 'tag-envio' : 'tag-recoger'">
                  <SvgIcon :path="ped.tipo_entrega === 'envio' ? mdiMoped : mdiHome" :size="13" />
                  {{ ped.tipo_entrega === 'envio' ? 'Envío a domicilio' : 'Recoger en local' }}
                </span>
                <span v-if="ped.tipo_entrega === 'envio' && ped.direccion" class="pedido-dir"><SvgIcon :path="mdiMapMarker" :size="13" /> {{ ped.direccion }}</span>
                <span v-if="ped.tipo_entrega === 'envio' && ped.referencia" class="pedido-referencia">📍 {{ ped.referencia }}</span>
                <span class="pedido-tag tag-pago">
                  <SvgIcon :path="ped.metodo_pago === 'transferencia' ? mdiBank : mdiCash" :size="13" />
                  {{ ped.metodo_pago === 'transferencia' ? 'Transferencia' : ped.metodo_pago === 'terminal' ? 'Terminal' : 'Efectivo' }}
                </span>
                <span v-if="ped.denominacion" class="pedido-denominacion">Con ${{ Number(ped.denominacion).toFixed(0) }}</span>
              </div>
              <div class="pedido-items-list">
                <div v-for="item in ped.items" :key="item.id" class="pedido-item-row">
                  <div class="pedido-item-top">
                    <span class="pedido-item-cant">{{ item.cantidad }}×</span>
                    <span class="pedido-item-nombre">{{ item.nombre_producto }}</span>
                    <span class="pedido-item-precio">${{ Number(item.subtotal).toFixed(2) }}</span>
                  </div>
                  <div v-if="item.opciones && item.opciones.length" class="pedido-item-opciones">
                    <div v-for="(opc, i) in agruparOpciones(item.opciones)" :key="i" class="pedido-opc-grupo">
                      <span class="pedido-opc-label">{{ opc.grupo }}:</span>
                      <span v-for="(o, j) in opc.items" :key="j" class="pedido-opc-chip">
                        {{ o.nombre }}<span v-if="o.precio_extra > 0" class="pedido-opc-extra"> +${{ Number(o.precio_extra).toFixed(0) }}</span>
                      </span>
                    </div>
                  </div>
                  <div v-if="item.observacion" class="pedido-item-obs">
                    <span>📝 {{ item.observacion }}</span>
                  </div>
                </div>
              </div>
              <div class="pedido-totales">
                <span v-if="ped.costo_envio > 0">Envío: ${{ Number(ped.costo_envio).toFixed(2) }}</span>
                <span v-if="Number(ped.descuento_recompensa) > 0" class="pedido-descuento">🎁 Recompensa: -${{ Number(ped.descuento_recompensa).toFixed(2) }}</span>
                <span v-if="ped.codigo_promo" class="pedido-descuento">🏷️ {{ ped.codigo_promo }}: {{ Number(ped.descuento_promo) > 0 ? '-$' + Number(ped.descuento_promo).toFixed(2) : 'Envío gratis' }}</span>
                <span v-if="Number(ped.ajuste_manual) < 0" class="pedido-descuento">✏️ Ajuste: -${{ Math.abs(Number(ped.ajuste_manual)).toFixed(2) }}<small v-if="ped.ajuste_nota"> ({{ ped.ajuste_nota }})</small></span>
                <span v-else-if="Number(ped.ajuste_manual) > 0" class="pedido-cargo">✏️ +${{ Number(ped.ajuste_manual).toFixed(2) }}<small v-if="ped.ajuste_nota"> ({{ ped.ajuste_nota }})</small></span>
                <strong>Total: ${{ Number(ped.total_final ?? ped.total).toFixed(2) }}</strong>
              </div>
            </div>
            <div class="pedido-acciones">
              <button v-if="ped.status === 'nuevo'"          @click="cambiarStatus(ped.id, 'visto')"          class="btn-status btn-visto">Visto</button>
              <button v-if="ped.status === 'visto'"          @click="cambiarStatus(ped.id, 'en_preparacion')" class="btn-status btn-prep">En preparación</button>
              <button v-if="ped.status === 'en_preparacion'" @click="cambiarStatus(ped.id, 'listo')"          class="btn-status btn-listo"><SvgIcon :path="mdiCheck" :size="14" /> Listo</button>
              <button v-if="ped.status === 'listo'"          @click="cambiarStatus(ped.id, 'entregado')"      class="btn-status btn-entregado"><SvgIcon :path="mdiCheckCircle" :size="14" /> Entregado</button>
              <button v-if="!['entregado','cancelado'].includes(ped.status)" @click="cambiarStatus(ped.id, 'cancelado')" class="btn-status btn-cancelar">Cancelar</button>
              <button @click="iniciarAjuste(ped)" class="btn-status btn-ajustar"><SvgIcon :path="mdiPencil" :size="14" /> Ajustar</button>
              <button @click="editarId === ped.id ? (editarId = null) : iniciarEditar(ped)" class="btn-status btn-editar">
                <SvgIcon :path="mdiPlaylistEdit" :size="14" /> {{ editarId === ped.id ? 'Cerrar' : 'Editar' }}
              </button>
              <button @click="copiarPedido(ped)" class="btn-status btn-copiar">
                <SvgIcon :path="copiadoId === ped.id ? mdiCheck : mdiContentCopy" :size="14" />
                {{ copiadoId === ped.id ? 'Copiado' : 'Copiar' }}
              </button>
              <button @click="eliminandoId === ped.id ? eliminarPedido(ped.id) : (eliminandoId = ped.id)" class="btn-status btn-eliminar">
                <SvgIcon :path="mdiDeleteOutline" :size="14" />
                {{ eliminandoId === ped.id ? '¿Confirmar?' : 'Eliminar' }}
              </button>
            </div>
            <!-- ── Editor de items ── -->
            <div v-if="editarId === ped.id" class="editor-pedido">
              <div class="editor-items">
                <div v-for="(item, idx) in editarItems" :key="idx" class="editor-item-row">
                  <button @click="cambiarCant(idx, -1)" class="qty-btn"><SvgIcon :path="mdiMinus" :size="13" /></button>
                  <span class="qty-val">{{ item.cantidad }}</span>
                  <button @click="cambiarCant(idx, 1)"  class="qty-btn"><SvgIcon :path="mdiPlus" :size="13" /></button>
                  <span class="editor-item-nombre">{{ item.nombre_producto }}</span>
                  <span class="editor-item-precio">${{ (item.precio_unitario * item.cantidad).toFixed(2) }}</span>
                  <button @click="editarItems.splice(idx, 1)" class="qty-btn qty-del"><SvgIcon :path="mdiClose" :size="13" /></button>
                </div>
                <p v-if="!editarItems.length" class="editor-empty">Sin ítems — agrega al menos uno</p>
              </div>
              <div class="editor-buscar">
                <input v-model="buscarProd" placeholder="🔍 Buscar producto para agregar..." class="editor-search" autocomplete="off" />
                <div v-if="productosFiltrados.length" class="editor-dropdown">
                  <button v-for="p in productosFiltrados" :key="p.id" @click="agregarItem(p)" class="editor-prod-opt">
                    <span>{{ p.nombre }}</span><span class="editor-prod-precio">${{ p.precio.toFixed(2) }}</span>
                  </button>
                </div>
              </div>
              <div class="editor-footer">
                <span class="editor-total">Subtotal ítems: <strong>${{ editarSubtotal.toFixed(2) }}</strong></span>
                <div class="editor-btns">
                  <button @click="editarId = null" class="btn-status btn-cancelar">Cancelar</button>
                  <button @click="guardarEdicion(ped)" class="btn-status btn-listo" :disabled="guardandoEdicion">
                    {{ guardandoEdicion ? 'Guardando...' : 'Guardar cambios' }}
                  </button>
                </div>
              </div>
            </div>

            <div v-if="ajustandoId === ped.id" class="ajuste-form">
              <div class="ajuste-row">
                <div class="ajuste-signo-wrap">
                  <button :class="['ajuste-signo', { active: ajusteForm.signo === 'descuento' }]" @click="ajusteForm.signo = 'descuento'">Descuento</button>
                  <button :class="['ajuste-signo', { active: ajusteForm.signo === 'cargo' }]" @click="ajusteForm.signo = 'cargo'">Cargo extra</button>
                </div>
                <input v-model="ajusteForm.monto" type="number" min="0" step="0.01" placeholder="Monto" class="ajuste-input" />
              </div>
              <input v-model="ajusteForm.nota" type="text" maxlength="100" placeholder="Motivo (ej: descuento familiar)" class="ajuste-input-text" />
              <div class="ajuste-btns">
                <button @click="ajustandoId = null" class="btn-status btn-cancelar">Cancelar</button>
                <button @click="guardarAjuste(ped)" class="btn-status btn-listo">Guardar</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onUnmounted, nextTick } from 'vue'
import {
  mdiCart, mdiRefresh, mdiAccount, mdiSeat,
  mdiMoped, mdiHome, mdiBank, mdiCash, mdiCheck, mdiCheckCircle,
  mdiMapMarker, mdiWhatsapp, mdiContentCopy, mdiPencil,
  mdiChartBar, mdiPlaylistEdit, mdiDeleteOutline, mdiPlus, mdiMinus, mdiClose,
} from '@mdi/js'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import { useApi } from '../../../composables/useApi.js'
import SvgIcon from '../../SvgIcon.vue'

const props = defineProps({
  restauranteId: { type: Number, required: true },
  active:        { type: Boolean, default: false },
})

const emit = defineEmits(['notif'])

const { get, put, del } = useApi()

// ── Pedidos ──
const pedidos        = ref([])
const loadingPedidos = ref(false)
const copiadoId      = ref(null)
const ajustandoId    = ref(null)
const ajusteForm     = ref({ monto: '', signo: 'descuento', nota: '' })
let   pedidosInterval = null

// ── Editor de pedido ──
const editarId        = ref(null)
const editarItems     = ref([])       // copia mutable de los items del pedido
const guardandoEdicion = ref(false)
const productosMenu   = ref([])       // lista plana de todos los productos del menú
const buscarProd      = ref('')
const eliminandoId    = ref(null)

const productosFiltrados = computed(() => {
  const q = buscarProd.value.trim().toLowerCase()
  if (q.length < 2) return []
  return productosMenu.value.filter(p => p.nombre.toLowerCase().includes(q) && p.activo !== 0).slice(0, 12)
})

const editarSubtotal = computed(() =>
  editarItems.value.reduce((s, i) => s + i.precio_unitario * i.cantidad, 0)
)

// ── Reporte de ventas ──
const reporteAbierto = ref(false)
const hoyDate = new Date()
const localIso = (d = new Date()) =>
  `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`
const hoy = localIso()
const periodos = [
  { id: 'hoy',    label: 'Hoy'           },
  { id: 'ayer',   label: 'Ayer'          },
  { id: 'semana', label: 'Esta semana'   },
  { id: 'mes',    label: 'Este mes'      },
  { id: 'custom', label: 'Personalizado' },
]
const periodoActivo = ref('hoy')
const fechaDesde    = ref(hoy)
const fechaHasta    = ref(hoy)
const pickerDesde   = ref(null)
const pickerHasta   = ref(null)
const reporteCargando = ref(false)
const resumen       = ref(null)
const porDia        = ref([])

const labelRango = computed(() => {
  if (periodoActivo.value === 'custom') return null
  const desde = fmtFecha(fechaDesde.value)
  const hasta = fechaHasta.value === hoy ? `Hoy (${fmtFecha(hoy)})` : fmtFecha(fechaHasta.value)
  if (periodoActivo.value === 'hoy') return desde
  return `${desde} — ${hasta}`
})

function calcularRango(id) {
  const ahora = new Date()
  const yy    = ahora.getFullYear()
  const mm    = String(ahora.getMonth() + 1).padStart(2, '0')
  if (id === 'hoy')  return [hoy, hoy]
  if (id === 'ayer') { const a = new Date(ahora); a.setDate(a.getDate()-1); return [localIso(a), localIso(a)] }
  if (id === 'mes')  return [`${yy}-${mm}-01`, hoy]
  const dow   = ahora.getDay() || 7
  const lunes = new Date(ahora)
  lunes.setDate(ahora.getDate() - (dow - 1))
  return [localIso(lunes), hoy]
}

const fmtDia = (d) => d ? d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' }) : ''
const fmtIso = (d) => d ? localIso(d) : ''
const fmt    = (v) => Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const fmtFecha = (iso) => {
  const [y, m, d] = iso.split('-')
  const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic']
  return `${parseInt(d)} ${meses[parseInt(m) - 1]} ${y}`
}

async function cargarReporte() {
  if (!props.restauranteId) return
  if (periodoActivo.value === 'custom' && !pickerHasta.value) {
    fechaHasta.value = fechaDesde.value
  }
  reporteCargando.value = true
  try {
    const data = await get('reportes', {
      restaurante_id: props.restauranteId,
      desde: fechaDesde.value,
      hasta: fechaHasta.value,
    })
    resumen.value = data.resumen
    porDia.value  = data.por_dia || []
  } catch (err) {
    emit('notif', { texto: err.message || 'Error al cargar reporte', tipo: 'error' })
    resumen.value = null
  } finally {
    reporteCargando.value = false
  }
}

function selPeriodo(id) {
  periodoActivo.value = id
  pickerDesde.value   = null
  pickerHasta.value   = null
  if (id !== 'custom') {
    const [d, h] = calcularRango(id)
    fechaDesde.value = d
    fechaHasta.value = h
    cargarReporte()
  }
}

// Cargar reporte al abrir el colapsable por primera vez
watch(reporteAbierto, (open) => {
  if (open && !resumen.value) selPeriodo('hoy')
})

const agruparOpciones = (opciones) => {
  const map = {}
  for (const o of opciones) {
    if (!map[o.grupo_nombre]) map[o.grupo_nombre] = { grupo: o.grupo_nombre, items: [] }
    map[o.grupo_nombre].items.push({ nombre: o.opcion_nombre, precio_extra: parseFloat(o.precio_extra) })
  }
  return Object.values(map)
}

const formatHora = (ts) => {
  const d = new Date(ts)
  return d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }) + ' · ' + d.toLocaleDateString('es-MX', { day: 'numeric', month: 'short' })
}

const statusLabel = (s) => ({ nuevo: 'Nuevo', visto: 'Visto', en_preparacion: 'En preparación', listo: 'Listo', entregado: 'Entregado', cancelado: 'Cancelado' })[s] || s

const fmtFechaProgramada = (fecha, hora) => {
  if (!fecha) return ''
  const [y, m, d] = fecha.split('-')
  const fechaStr = `${d}/${m}/${y}`
  return hora ? `${fechaStr} ${hora.slice(0, 5)}` : fechaStr
}

function generarTextoWA(ped) {
  const lineas = [
    `*-- NUEVO PEDIDO --*`,
    `*#${ped.numero_pedido}*`,
    ``,
    `*Pedido:*`,
    ...ped.items.flatMap((item, idx, arr) => {
      const obs = item.observacion ? ` _(${item.observacion})_` : ''
      const linea = `  ${item.cantidad}x ${item.nombre_producto}${obs} — $${Number(item.subtotal).toFixed(2)}`
      const opLines = (item.opciones || []).map(o =>
        `    · ${o.opcion_nombre}${Number(o.precio_extra) > 0 ? ` +$${Number(o.precio_extra).toFixed(2)}` : ''}`
      )
      const sep = idx < arr.length - 1 ? ['──────────'] : []
      return [linea, ...opLines, ...sep]
    }),
    ``,
    `Subtotal: $${Number(ped.subtotal).toFixed(2)}`,
    ...(Number(ped.costo_envio) > 0 ? [`Envio: $${Number(ped.costo_envio).toFixed(2)}`] : []),
    ...(Number(ped.costo_envio) === 0 && ped.tipo_entrega === 'envio' ? [`Envio: GRATIS`] : []),
    ...(Number(ped.descuento_recompensa) > 0 ? [`Descuento recompensa: -$${Number(ped.descuento_recompensa).toFixed(2)}`] : []),
    ...(Number(ped.descuento_promo) > 0 ? [`Codigo ${ped.codigo_promo}: -$${Number(ped.descuento_promo).toFixed(2)}`] : []),
    ...(Number(ped.ajuste_manual) < 0 ? [`Ajuste: -$${Math.abs(Number(ped.ajuste_manual)).toFixed(2)}${ped.ajuste_nota ? ` (${ped.ajuste_nota})` : ''}`] : []),
    ...(Number(ped.ajuste_manual) > 0 ? [`Cargo extra: +$${Number(ped.ajuste_manual).toFixed(2)}${ped.ajuste_nota ? ` (${ped.ajuste_nota})` : ''}`] : []),
    `*Total: $${Number(ped.total_final ?? ped.total).toFixed(2)}*`,
    ``,
    `*Entrega:* ${ped.tipo_entrega === 'envio' ? 'A domicilio' : 'Recoger en local'}`,
    ...(ped.tipo_entrega === 'envio' && ped.direccion ? [`*Direccion:* ${ped.direccion}`] : []),
    ...(ped.tipo_entrega === 'envio' && ped.referencia ? [`*Referencia:* ${ped.referencia}`] : []),
    ...(ped.telefono ? [`*Tel:* ${ped.telefono}`] : []),
    `*Pago:* ${ped.metodo_pago === 'transferencia' ? 'Transferencia' : ped.metodo_pago === 'terminal' ? 'Terminal a domicilio' : 'Efectivo'}`,
    ...(ped.metodo_pago === 'efectivo' && ped.denominacion ? [`Con: $${Number(ped.denominacion).toFixed(0)}`] : []),
    `*Nombre:* ${ped.nombre_cliente}`,
    ...(ped.mesa ? [`*Mesa:* ${ped.mesa}`] : []),
  ]
  return lineas.join('\n')
}

async function copiarPedido(ped) {
  try {
    await navigator.clipboard.writeText(generarTextoWA(ped))
    copiadoId.value = ped.id
    setTimeout(() => { copiadoId.value = null }, 2000)
  } catch {
    emit('notif', { texto: 'No se pudo copiar al portapapeles', tipo: 'error' })
  }
}

async function loadPedidos() {
  const scrollY = window.scrollY
  loadingPedidos.value = true
  try {
    const res = await get('pedidos', { restaurante_id: props.restauranteId })
    pedidos.value = res.pedidos || []
    await nextTick()
    window.scrollTo({ top: scrollY, behavior: 'instant' })
  } finally {
    loadingPedidos.value = false
  }
}

function iniciarAjuste(ped) {
  editarId.value    = null
  eliminandoId.value = null
  const actual = Number(ped.ajuste_manual) || 0
  ajusteForm.value = {
    monto: actual !== 0 ? String(Math.abs(actual)) : '',
    signo: actual >= 0 ? 'cargo' : 'descuento',
    nota:  ped.ajuste_nota || '',
  }
  ajustandoId.value = ped.id
}

async function guardarAjuste(ped) {
  const monto  = parseFloat(ajusteForm.value.monto) || 0
  const ajuste = ajusteForm.value.signo === 'descuento' ? -Math.abs(monto) : Math.abs(monto)
  try {
    await put('pedidos', { ajuste_manual: ajuste, ajuste_nota: ajusteForm.value.nota, restaurante_id: props.restauranteId }, { id: ped.id })
    ajustandoId.value = null
    await loadPedidos()
    emit('notif', { texto: 'Ajuste guardado', tipo: 'ok' })
  } catch (err) {
    emit('notif', { texto: err.message || 'Error al guardar ajuste', tipo: 'error' })
  }
}

async function cargarProductosMenu() {
  if (productosMenu.value.length) return
  try {
    const data = await get('menu', { restaurante_id: props.restauranteId })
    const lista = []
    for (const cat of data.categorias || []) {
      for (const p of cat.productos || []) {
        lista.push({ id: p.id, nombre: p.nombre, precio: parseFloat(p.precio), activo: p.activo })
      }
    }
    productosMenu.value = lista
  } catch { /* silencioso */ }
}

function iniciarEditar(ped) {
  editarId.value    = ped.id
  ajustandoId.value = null
  buscarProd.value  = ''
  editarItems.value = ped.items.map(i => ({
    producto_id:    i.producto_id,
    nombre_producto: i.nombre_producto,
    precio_unitario: parseFloat(i.precio_unitario),
    cantidad:        parseInt(i.cantidad),
    observacion:     i.observacion || '',
  }))
  cargarProductosMenu()
}

function cambiarCant(idx, delta) {
  const item = editarItems.value[idx]
  const nueva = item.cantidad + delta
  if (nueva < 1) {
    editarItems.value.splice(idx, 1)
  } else {
    item.cantidad = nueva
  }
}

function agregarItem(prod) {
  const existe = editarItems.value.find(i => i.producto_id === prod.id && !i.observacion)
  if (existe) {
    existe.cantidad++
  } else {
    editarItems.value.push({
      producto_id:     prod.id,
      nombre_producto: prod.nombre,
      precio_unitario: prod.precio,
      cantidad:        1,
      observacion:     '',
    })
  }
  buscarProd.value = ''
}

async function guardarEdicion(ped) {
  if (editarItems.value.length === 0) {
    emit('notif', { texto: 'El pedido debe tener al menos 1 item', tipo: 'error' })
    return
  }
  guardandoEdicion.value = true
  try {
    await put('pedidos', {
      restaurante_id: props.restauranteId,
      items: editarItems.value,
    }, { id: ped.id })
    editarId.value = null
    await loadPedidos()
    emit('notif', { texto: 'Pedido actualizado', tipo: 'ok' })
  } catch (err) {
    emit('notif', { texto: err.message || 'Error al guardar', tipo: 'error' })
  } finally {
    guardandoEdicion.value = false
  }
}

async function eliminarPedido(id) {
  eliminandoId.value = id
  try {
    await del('pedidos', { id })
    await loadPedidos()
    emit('notif', { texto: 'Pedido eliminado', tipo: 'ok' })
  } catch (err) {
    emit('notif', { texto: err.message || 'Error al eliminar', tipo: 'error' })
  } finally {
    eliminandoId.value = null
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
}, { immediate: true })

onUnmounted(() => clearInterval(pedidosInterval))
</script>

<style scoped>
.btn-refresh { display: inline-flex; align-items: center; gap: 5px; background: #f5f5f5; border: 1px solid #e0e0e0; color: #555; padding: 5px 12px; border-radius: 7px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: background 0.15s; }
.btn-refresh:hover { background: #ebebeb; }

.pedidos-lista { display: flex; flex-direction: column; }
.pedido-card   { border-bottom: 1px solid #f0f0f0; padding: 16px 20px; }
.pedido-card:last-child { border-bottom: none; }
.pedido-card--programado { border-left: 3px solid #6C8EBF; padding-left: 17px; }

.chip-programado {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  background: #EBF0F8;
  color: #3A5A8C;
  border-radius: 6px;
  padding: 2px 8px;
  font-size: 0.75rem;
  font-weight: 600;
  margin-left: 6px;
}

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
.pedido-tag      { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 5px; font-weight: 600; background: #f0f0f0; color: #555; }
.tag-envio   { background: #e3f2fd; color: #1565c0; }
.tag-recoger { background: #f3e5f5; color: #6a1b9a; }
.tag-pago    { background: #e8f5e9; color: #2e7d32; }
.pedido-dir          { display: inline-flex; align-items: center; gap: 3px; font-size: 0.8rem; color: #888; }
.pedido-referencia   { font-size: 0.8rem; color: #888; }
.pedido-denominacion { font-size: 0.8rem; color: #888; }

.pedido-tel-wa { display: inline-flex; align-items: center; gap: 4px; font-size: 0.85rem; color: #25d366; font-weight: 600; text-decoration: none; }
.pedido-tel-wa:hover { text-decoration: underline; }

.pedido-items-list  { background: #fafafa; border-radius: 8px; padding: 10px 12px; display: flex; flex-direction: column; gap: 10px; }
.pedido-item-row    { display: flex; flex-direction: column; gap: 4px; font-size: 0.85rem; }
.pedido-item-top    { display: flex; align-items: baseline; gap: 6px; }
.pedido-item-cant   { font-weight: 700; color: var(--accent); min-width: 24px; flex-shrink: 0; }
.pedido-item-nombre { flex: 1; font-weight: 600; color: #1a1a1a; }
.pedido-item-precio { font-weight: 700; color: #555; flex-shrink: 0; }

.pedido-item-opciones { padding-left: 30px; display: flex; flex-direction: column; gap: 3px; }
.pedido-opc-grupo     { display: flex; flex-wrap: wrap; align-items: center; gap: 4px; }
.pedido-opc-label     { font-size: 0.73rem; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 0.03em; flex-shrink: 0; }
.pedido-opc-chip      { font-size: 0.78rem; background: #ede9fe; color: #5b21b6; border-radius: 5px; padding: 1px 7px; font-weight: 500; }
.pedido-opc-extra     { font-weight: 700; color: #7c3aed; }

.pedido-item-obs { padding-left: 30px; font-size: 0.78rem; color: #999; font-style: italic; }

.pedido-totales { display: flex; justify-content: flex-end; align-items: center; gap: 12px; font-size: 0.88rem; color: #888; flex-wrap: wrap; }
.pedido-totales strong { color: #1a1a1a; font-size: 0.95rem; }
.pedido-descuento { font-size: 0.78rem; font-weight: 700; color: #27ae60; background: #eafaf1; border-radius: 6px; padding: 2px 8px; }
.pedido-cargo     { font-size: 0.78rem; font-weight: 700; color: #e65100; background: #fff3e0; border-radius: 6px; padding: 2px 8px; }

.pedido-acciones { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
.btn-status  { display: inline-flex; align-items: center; gap: 5px; padding: 6px 14px; border: none; border-radius: 7px; font-size: 0.82rem; font-weight: 700; cursor: pointer; transition: opacity 0.15s; }
.btn-status:hover { opacity: 0.82; }
.btn-visto     { background: #fff3e0; color: #e65100; }
.btn-prep      { background: #e3f2fd; color: #1565c0; }
.btn-listo     { background: #e8f5e9; color: #2e7d32; }
.btn-entregado { background: #c8e6c9; color: #1b5e20; }
.btn-cancelar  { background: #ffebee; color: #c62828; }
.btn-copiar    { background: #f0f0f0; color: #555; margin-left: auto; }
.btn-copiar:hover { background: #e4e4e4; }
.btn-ajustar   { background: #fef9c3; color: #854d0e; }
.btn-ajustar:hover { background: #fef08a; }
.btn-editar    { background: #e0f2fe; color: #0369a1; }
.btn-editar:hover { background: #bae6fd; }
.btn-eliminar  { background: #fee2e2; color: #b91c1c; }
.btn-eliminar:hover { background: #fecaca; }

/* ── Editor inline de pedido ── */
.editor-pedido { margin-top: 10px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 12px 14px; display: flex; flex-direction: column; gap: 10px; }
.editor-items  { display: flex; flex-direction: column; gap: 6px; }
.editor-item-row { display: flex; align-items: center; gap: 6px; background: #fff; border-radius: 7px; padding: 6px 10px; }
.qty-btn { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 6px; border: 1px solid #cbd5e1; background: #f8fafc; cursor: pointer; flex-shrink: 0; }
.qty-btn:hover { background: #e2e8f0; }
.qty-del  { border-color: #fca5a5; background: #fff1f2; }
.qty-del:hover { background: #ffe4e6; }
.qty-val  { min-width: 22px; text-align: center; font-weight: 600; font-size: 0.9rem; }
.editor-item-nombre { flex: 1; font-size: 0.88rem; }
.editor-item-precio { font-size: 0.88rem; font-weight: 600; color: #0369a1; white-space: nowrap; }
.editor-empty { text-align: center; color: #94a3b8; font-size: 0.85rem; padding: 8px 0; }
.editor-buscar { position: relative; }
.editor-search { width: 100%; padding: 7px 10px; border: 1px solid #bae6fd; border-radius: 8px; font-size: 0.88rem; box-sizing: border-box; background: #fff; }
.editor-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #bae6fd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,.1); z-index: 50; max-height: 220px; overflow-y: auto; }
.editor-prod-opt { display: flex; justify-content: space-between; align-items: center; width: 100%; padding: 8px 12px; text-align: left; background: none; border: none; border-bottom: 1px solid #f0f9ff; cursor: pointer; font-size: 0.87rem; }
.editor-prod-opt:last-child { border-bottom: none; }
.editor-prod-opt:hover { background: #f0f9ff; }
.editor-prod-precio { color: #0369a1; font-weight: 600; margin-left: 8px; white-space: nowrap; }
.editor-footer { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
.editor-total  { font-size: 0.9rem; color: #0c4a6e; }
.editor-btns   { display: flex; gap: 6px; }

.ajuste-form { margin-top: 10px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; }
.ajuste-row  { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.ajuste-signo-wrap { display: flex; gap: 4px; }
.ajuste-signo { padding: 4px 10px; border-radius: 6px; border: 1px solid #e0e0e0; background: #f5f5f5; color: #555; font-size: 0.8rem; font-weight: 600; cursor: pointer; }
.ajuste-signo.active { background: var(--accent, #e65100); color: #fff; border-color: var(--accent, #e65100); }
.ajuste-input { width: 90px; padding: 5px 8px; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 0.88rem; }
.ajuste-input-text { width: 100%; padding: 5px 8px; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 0.88rem; }
.ajuste-btns { display: flex; gap: 8px; justify-content: flex-end; }

/* ── Corte de ventas ── */
.periodo-pills { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 6px; }
.periodo-pill  { padding: 6px 14px; border-radius: 20px; border: 1px solid #e0e0e0; background: #f5f5f5; color: #555; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: background 0.15s, color 0.15s; }
.periodo-pill.active { background: var(--accent, #e65100); color: #fff; border-color: var(--accent, #e65100); }

.rango-label { font-size: 0.78rem; color: #888; margin: 0 0 8px; }

.fechas-custom { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
.fechas-custom :deep(.dp__input) { padding: 6px 10px 6px 34px; font-size: 0.88rem; border-radius: 8px; }
.fechas-custom :deep(.dp__input_wrap) { min-width: 140px; }
.fechas-sep { color: #aaa; }

.reporte-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; margin-top: 14px; }
.stat-card { background: #fafafa; border: 1px solid #f0f0f0; border-radius: 10px; padding: 12px 14px; display: flex; flex-direction: column; gap: 3px; }
.stat-label { font-size: 0.7rem; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: 0.04em; }
.stat-valor { font-size: 1.2rem; font-weight: 800; color: #1a1a1a; }
.stat-sub   { font-size: 0.72rem; color: #aaa; }
.stat-principal { border-color: var(--accent, #e65100); border-width: 2px; }
.stat-principal .stat-valor { color: var(--accent, #e65100); }
.stat-descuento .stat-valor { color: #e74c3c; }
.stat-envio .stat-valor     { color: #1565c0; }

.dia-tabla { width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-top: 4px; }
.dia-tabla th { padding: 8px 12px; font-size: 0.72rem; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 2px solid #f0f0f0; background: #fafafa; }
.dia-tabla td { padding: 8px 12px; border-bottom: 1px solid #f5f5f5; color: #333; }
.dia-tabla tr:last-child td { border-bottom: none; }
.txt-right { text-align: right; }
.empty-reporte { font-size: 0.85rem; color: #aaa; text-align: center; padding: 16px 0 4px; }
.btn-sm { padding: 6px 14px; font-size: 0.82rem; }
</style>
