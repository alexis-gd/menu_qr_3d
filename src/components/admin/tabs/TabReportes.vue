<template>
  <div class="tab-content">
    <div class="card">
      <div class="card-header">
        <h2>Corte de ventas</h2>
      </div>
      <div class="card-body">
        <!-- Período rápido -->
        <div class="periodo-pills">
          <button
            v-for="p in periodos" :key="p.id"
            :class="['periodo-pill', { active: periodoActivo === p.id }]"
            @click="selPeriodo(p.id)"
          >{{ p.label }}</button>
        </div>
        <!-- Label de rango (esta semana / este mes) -->
        <p v-if="labelRango" class="rango-label">{{ labelRango }}</p>
        <!-- Fechas personalizadas -->
        <div v-if="periodoActivo === 'custom'" class="fechas-custom">
          <VueDatePicker
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
      </div>
    </div>

    <!-- Loading -->
    <div v-if="cargando" class="loading-inline"><div class="spinner"></div></div>

    <template v-else-if="resumen">
      <!-- Cards de resumen -->
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
        <div v-if="resumen.ajustes_negativos > 0" class="stat-card stat-descuento">
          <span class="stat-label">Ajustes manuales</span>
          <span class="stat-valor">-${{ fmt(resumen.ajustes_negativos) }}</span>
        </div>
      </div>

      <!-- Tabla por día (solo si rango > 1 día y hay datos) -->
      <div v-if="porDia.length > 1" class="card" style="margin-top:18px">
        <div class="card-header"><h2>Detalle por día</h2></div>
        <div class="card-body no-pad">
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
      </div>

      <!-- Sin datos -->
      <div v-if="resumen.total_pedidos === 0" class="empty-state" style="padding:32px">
        <p>Sin pedidos en este período.</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import { useApi } from '../../../composables/useApi.js'

const props = defineProps({
  restauranteId: { type: Number, required: true },
  active:        { type: Boolean, default: false },
})

const emit = defineEmits(['notif'])
const { get } = useApi()

const hoyDate = new Date()
const localIso = (d = new Date()) =>
  `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`
const hoy = localIso()

const periodos = [
  { id: 'hoy',    label: 'Hoy'          },
  { id: 'ayer',   label: 'Ayer'         },
  { id: 'semana', label: 'Esta semana'  },
  { id: 'mes',    label: 'Este mes'     },
  { id: 'custom', label: 'Personalizado' },
]

const periodoActivo = ref('hoy')
const fechaDesde    = ref(hoy)
const fechaHasta    = ref(hoy)
const pickerDesde   = ref(null)
const pickerHasta   = ref(null)
const cargando      = ref(false)
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
  // Esta semana (lunes → hoy)
  const dow   = ahora.getDay() || 7
  const lunes = new Date(ahora)
  lunes.setDate(ahora.getDate() - (dow - 1))
  return [localIso(lunes), hoy]
}

// Formatos para los pickers individuales (sin date-fns)
const fmtDia = (d) => d ? d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' }) : ''
const fmtIso = (d) => d ? localIso(d) : ''

async function cargarReporte() {
  if (!props.restauranteId) return
  // En modo custom, si no se eligió "Hasta", usar el mismo día que "Desde"
  if (periodoActivo.value === 'custom' && !pickerHasta.value) {
    fechaHasta.value = fechaDesde.value
  }
  cargando.value = true
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
    cargando.value = false
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

watch(() => props.active, (isActive) => {
  if (isActive && !resumen.value) selPeriodo('hoy')
})

const fmt = (v) => Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const fmtFecha = (iso) => {
  const [y, m, d] = iso.split('-')
  const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic']
  return `${parseInt(d)} ${meses[parseInt(m) - 1]} ${y}`
}
</script>

<style scoped>
.periodo-pills { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 6px; }
.periodo-pill  { padding: 6px 14px; border-radius: 20px; border: 1px solid #e0e0e0; background: #f5f5f5; color: #555; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: background 0.15s, color 0.15s; }
.periodo-pill.active { background: var(--accent, #e65100); color: #fff; border-color: var(--accent, #e65100); }

.rango-label { font-size: 0.78rem; color: #888; margin: 0 0 8px; }

.fechas-custom { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
.fechas-custom :deep(.dp__input) { padding: 6px 10px 6px 34px; font-size: 0.88rem; border-radius: 8px; }
.fechas-custom :deep(.dp__input_wrap) { min-width: 140px; }

.reporte-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; margin-top: 16px; }

.stat-card { background: #fff; border: 1px solid #f0f0f0; border-radius: 12px; padding: 14px 16px; display: flex; flex-direction: column; gap: 4px; }
.stat-label { font-size: 0.72rem; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: 0.04em; }
.stat-valor { font-size: 1.3rem; font-weight: 800; color: #1a1a1a; }
.stat-sub   { font-size: 0.75rem; color: #aaa; }

.stat-principal { border-color: var(--accent, #e65100); border-width: 2px; }
.stat-principal .stat-valor { color: var(--accent, #e65100); }
.stat-descuento .stat-valor { color: #e74c3c; }
.stat-envio .stat-valor     { color: #1565c0; }

.dia-tabla { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
.dia-tabla th { padding: 10px 16px; font-size: 0.75rem; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 2px solid #f0f0f0; background: #fafafa; }
.dia-tabla td { padding: 10px 16px; border-bottom: 1px solid #f5f5f5; color: #333; }
.dia-tabla tr:last-child td { border-bottom: none; }
.txt-right { text-align: right; }

.btn-sm { padding: 6px 14px; font-size: 0.82rem; }
</style>
