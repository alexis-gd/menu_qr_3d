<template>
  <div class="admin-mesas">
    <div class="header-bar">
      <router-link to="/admin/restaurantes" class="btn-back">← Volver</router-link>
      <h1>Mesas — {{ restauranteNombre }}</h1>
    </div>

    <div class="nueva-mesa">
      <input
        v-model="nuevaNumero"
        placeholder="Número o nombre (ej: 5, VIP, Terraza-2)"
        @keyup.enter="crear"
      />
      <button @click="crear">Agregar mesa</button>
    </div>

    <div v-if="error" class="error">{{ error }}</div>
    <p v-if="loading" class="cargando">Cargando...</p>

    <table v-else-if="mesas.length">
      <thead>
        <tr><th>Mesa</th><th>Acciones</th></tr>
      </thead>
      <tbody>
        <tr v-for="mesa in mesas" :key="mesa.id">
          <td>Mesa {{ mesa.numero }}</td>
          <td class="acciones-col">
            <button @click="verQR(mesa)" class="btn-qr">Ver QR</button>
            <button @click="eliminar(mesa.id)" class="btn-danger">Eliminar</button>
          </td>
        </tr>
      </tbody>
    </table>
    <p v-else-if="!loading" class="sin-datos">No hay mesas. Crea la primera arriba.</p>

    <!-- Modal QR rediseñado -->
    <div v-if="qrModal" class="qr-overlay" @click.self="qrModal = null">
      <div class="qr-dialog">

        <!-- Card preview -->
        <div class="card-preview-col">
          <p class="preview-label">Vista previa</p>
          <div class="qr-card">
            <div class="qr-card-header" :style="{ background: tema.headerBg }">
              <div class="hdr-deco hdr-deco-1" :style="{ background: tema.decoColor }"></div>
              <div class="hdr-deco hdr-deco-2" :style="{ background: tema.decoColor }"></div>
              <div class="hdr-content">
                <span class="hdr-emoji"><SvgIcon :path="mdiSilverwareForkKnife" :size="32" :color="tema.headerText" /></span>
                <span class="hdr-nombre" :style="{ color: tema.headerText }">{{ restauranteNombre }}</span>
              </div>
            </div>

            <div class="qr-card-body">
              <h2 class="card-title">Escanea el menú</h2>
              <p v-if="fraseActiva" class="card-frase">"{{ fraseTexto }}"</p>
              <div
                class="card-qr-wrap"
                :style="{ borderColor: tema.accent + '33', boxShadow: `0 6px 20px ${tema.accent}22` }"
              >
                <img :src="qrModal.dataUrl" alt="QR" class="card-qr-img" />
              </div>
              <div v-if="wifiActivo" class="card-wifi" :style="{ color: tema.accent }">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M5 12.55a11 11 0 0 1 14.08 0"/>
                  <path d="M1.42 9a16 16 0 0 1 21.16 0"/>
                  <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
                  <line x1="12" y1="20" x2="12.01" y2="20"/>
                </svg>
                <div class="wifi-texts">
                  <span class="wifi-net">{{ wifiNombre || 'Nombre de red' }}</span>
                  <span class="wifi-pass">{{ wifiClave || '••••••••' }}</span>
                </div>
              </div>
              <div class="card-mesa-badge">Mesa #{{ qrModal.numero }}</div>
            </div>

            <div class="qr-card-bar" :style="{ background: tema.accent }"></div>
          </div>
        </div>

        <!-- Controls -->
        <div class="controls-col">
          <h3 class="controls-title">Personalizar tarjeta</h3>

          <div class="ctrl-group">
            <div class="ctrl-row-header">
              <span class="ctrl-label">Frase motivacional</span>
              <label class="sw">
                <input type="checkbox" v-model="fraseActiva" />
                <span class="sw-track" :style="fraseActiva ? { background: tema.accent } : {}"></span>
              </label>
            </div>
            <input
              v-if="fraseActiva"
              v-model="fraseTexto"
              class="ctrl-input"
              maxlength="60"
              placeholder="Ej: Delicioso desde el primer vistazo"
            />
          </div>

          <div class="ctrl-group">
            <div class="ctrl-row-header">
              <span class="ctrl-label">Info WiFi</span>
              <label class="sw">
                <input type="checkbox" v-model="wifiActivo" />
                <span class="sw-track" :style="wifiActivo ? { background: tema.accent } : {}"></span>
              </label>
            </div>
            <template v-if="wifiActivo">
              <input v-model="wifiNombre" class="ctrl-input" placeholder="Nombre de red" />
              <input v-model="wifiClave" class="ctrl-input" placeholder="Contraseña" />
            </template>
          </div>

          <p class="qr-url-chip">{{ qrModal.url }}</p>

          <div class="ctrl-actions">
            <button @click="descargarPNG" class="btn-dl" :style="{ background: tema.accent }">
              ⬇ Descargar PNG
            </button>
            <button @click="qrModal = null" class="btn-cl">Cerrar</button>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { mdiSilverwareForkKnife } from '@mdi/js'
import { useApi } from '../../composables/useApi.js'
import SvgIcon from '../../components/SvgIcon.vue'
import QRCode from 'qrcode'

const route = useRoute()
const restauranteId = route.params.id
const { get, post, del } = useApi()

const mesas = ref([])
const restauranteNombre = ref('')
const restauranteSlug = ref('')
const restauranteTema = ref('calido')
const nuevaNumero = ref('')
const error = ref(null)
const loading = ref(false)
const qrModal = ref(null)

// Card config (persiste mientras el componente está montado)
const fraseActiva = ref(true)
const fraseTexto = ref('Delicioso desde el primer vistazo')
const wifiActivo = ref(false)
const wifiNombre = ref('')
const wifiClave = ref('')

const TEMAS = {
  calido:  {
    accent: '#d4691e',
    headerBg: 'linear-gradient(145deg, #b5451b 0%, #d4691e 50%, #e8841f 100%)',
    headerText: '#ffffff',
    decoColor: 'rgba(255,255,255,0.15)',
    canvasH1: '#b5451b', canvasH2: '#e8841f',
  },
  oscuro:  {
    accent: '#f0c040',
    headerBg: 'linear-gradient(145deg, #0a0a1a 0%, #111128 50%, #1a1a38 100%)',
    headerText: '#f0c040',
    decoColor: 'rgba(240,192,64,0.1)',
    canvasH1: '#0a0a1a', canvasH2: '#1a1a38',
  },
  moderno: {
    accent: '#1a7f5a',
    headerBg: 'linear-gradient(145deg, #f0f8f4 0%, #e0f5ec 100%)',
    headerText: '#111111',
    decoColor: 'rgba(26,127,90,0.1)',
    canvasH1: '#f0f8f4', canvasH2: '#e0f5ec',
  },
  rapida:  {
    accent: '#d43f2e',
    headerBg: 'linear-gradient(145deg, #c0392b 0%, #d43f2e 50%, #e74c3c 100%)',
    headerText: '#ffffff',
    decoColor: 'rgba(255,255,255,0.15)',
    canvasH1: '#c0392b', canvasH2: '#e74c3c',
  },
}

const tema = computed(() => TEMAS[restauranteTema.value] || TEMAS.calido)

async function load() {
  loading.value = true
  error.value = null
  try {
    const res = await get('mesas', { restaurante_id: restauranteId })
    mesas.value = res.mesas || []
    restauranteNombre.value = res.restaurante_nombre || ''
    restauranteSlug.value = res.restaurante_slug || ''
    restauranteTema.value = res.restaurante_tema || 'calido'
  } catch (err) {
    error.value = err.message
  } finally {
    loading.value = false
  }
}

async function crear() {
  const numero = nuevaNumero.value.trim()
  if (!numero) return
  error.value = null
  try {
    await post('mesas', { restaurante_id: restauranteId, numero })
    nuevaNumero.value = ''
    await load()
  } catch (err) {
    error.value = err.message
  }
}

async function eliminar(id) {
  error.value = null
  try {
    await del('mesas', { id })
    await load()
  } catch (err) {
    error.value = err.message
  }
}

async function verQR(mesa) {
  const base = import.meta.env.BASE_URL
  const url = `${window.location.origin}${base}?r=${restauranteSlug.value}&mesa=${mesa.numero}`
  const dataUrl = await QRCode.toDataURL(url, { width: 300, margin: 2, color: { dark: '#1a1a1a', light: '#ffffff' } })
  qrModal.value = { numero: mesa.numero, url, dataUrl }
}

async function descargarPNG() {
  const W = 600, H = 900, R = 36
  const canvas = document.createElement('canvas')
  canvas.width = W
  canvas.height = H
  const ctx = canvas.getContext('2d')
  const t = tema.value
  const headerH = Math.round(H * 0.40)

  // Fondo blanco con bordes redondeados
  ctx.fillStyle = '#ffffff'
  rrect(ctx, 0, 0, W, H, R); ctx.fill()

  // Header con gradiente
  const grad = ctx.createLinearGradient(0, 0, W * 0.8, headerH)
  grad.addColorStop(0, t.canvasH1)
  grad.addColorStop(1, t.canvasH2)
  ctx.fillStyle = grad
  rrect(ctx, 0, 0, W, headerH, [R, R, 0, 0]); ctx.fill()

  // Círculos decorativos en header
  ctx.save()
  ctx.globalAlpha = 0.12
  ctx.fillStyle = t.headerText === '#ffffff' ? '#ffffff' : t.accent
  ctx.beginPath(); ctx.arc(W + 50, -50, 160, 0, Math.PI * 2); ctx.fill()
  ctx.beginPath(); ctx.arc(-50, headerH + 50, 140, 0, Math.PI * 2); ctx.fill()
  ctx.restore()

  // Ícono MDI + nombre en header
  const iconSize = 52
  const iconX = W / 2 - iconSize / 2
  const iconY = headerH * 0.44 - iconSize * 0.75
  const iconScale = iconSize / 24
  ctx.save()
  ctx.fillStyle = t.headerText
  ctx.translate(iconX, iconY)
  ctx.scale(iconScale, iconScale)
  ctx.fill(new Path2D(mdiSilverwareForkKnife))
  ctx.restore()
  ctx.textAlign = 'center'
  ctx.fillStyle = t.headerText
  ctx.font = `bold 30px system-ui, -apple-system, sans-serif`
  ctx.fillText(restauranteNombre.value, W / 2, headerH * 0.68)

  // Título
  let y = headerH + 58
  ctx.fillStyle = '#1a1a1a'
  ctx.font = `bold 40px system-ui, -apple-system, sans-serif`
  ctx.fillText('Escanea el menú', W / 2, y)
  y += 36

  // Frase
  if (fraseActiva.value && fraseTexto.value) {
    ctx.font = `italic 22px Georgia, "Times New Roman", serif`
    ctx.fillStyle = '#888888'
    ctx.fillText(`"${fraseTexto.value}"`, W / 2, y)
    y += 36
  }

  // QR image
  const qrImg = new Image()
  qrImg.src = qrModal.value.dataUrl
  await new Promise(r => { qrImg.onload = r })
  const qrSize = 230
  const qrX = (W - qrSize) / 2
  const qrY = y + 16

  // Wrapper del QR
  ctx.strokeStyle = t.accent + '44'
  ctx.lineWidth = 2
  ctx.fillStyle = '#ffffff'
  rrect(ctx, qrX - 14, qrY - 14, qrSize + 28, qrSize + 28, 20)
  ctx.fill(); ctx.stroke()
  ctx.drawImage(qrImg, qrX, qrY, qrSize, qrSize)
  y = qrY + qrSize + 36

  // WiFi
  if (wifiActivo.value) {
    ctx.fillStyle = t.accent
    ctx.font = `bold 20px system-ui, -apple-system, sans-serif`
    ctx.fillText(`📶  ${wifiNombre.value || 'WiFi'}`, W / 2, y)
    y += 28
    ctx.fillStyle = '#777777'
    ctx.font = `18px system-ui, -apple-system, sans-serif`
    ctx.fillText(wifiClave.value || '', W / 2, y)
    y += 32
  }

  // Mesa badge
  const bW = 160, bH = 34
  const bX = (W - bW) / 2
  ctx.fillStyle = '#f0f0f0'
  rrect(ctx, bX, y, bW, bH, 17); ctx.fill()
  ctx.fillStyle = '#aaaaaa'
  ctx.font = `bold 15px system-ui, -apple-system, sans-serif`
  ctx.fillText(`Mesa #${qrModal.value.numero}`, W / 2, y + 22)

  // Barra inferior
  ctx.fillStyle = t.accent
  rrect(ctx, 0, H - 14, W, 14, [0, 0, R, R]); ctx.fill()

  const link = document.createElement('a')
  link.download = `qr-mesa-${qrModal.value.numero}.png`
  link.href = canvas.toDataURL('image/png')
  link.click()
}

function rrect(ctx, x, y, w, h, r) {
  const [tl, tr, br, bl] = Array.isArray(r) ? r : [r, r, r, r]
  ctx.beginPath()
  ctx.moveTo(x + tl, y)
  ctx.lineTo(x + w - tr, y);  ctx.quadraticCurveTo(x + w, y,     x + w, y + tr)
  ctx.lineTo(x + w, y + h - br); ctx.quadraticCurveTo(x + w, y + h, x + w - br, y + h)
  ctx.lineTo(x + bl, y + h);  ctx.quadraticCurveTo(x, y + h,     x, y + h - bl)
  ctx.lineTo(x, y + tl);      ctx.quadraticCurveTo(x, y,         x + tl, y)
  ctx.closePath()
}

onMounted(load)
</script>

<style scoped>
.admin-mesas { max-width: 700px; margin: 20px auto; padding: 20px }

.header-bar { display: flex; align-items: center; gap: 16px; margin-bottom: 20px }
.header-bar h1 { margin: 0; font-size: 1.4rem }
.btn-back { color: #FF6B35; text-decoration: none; font-size: 0.9rem }

.nueva-mesa { display: flex; gap: 8px; margin-bottom: 20px }
.nueva-mesa input { flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem }
.nueva-mesa button { background: #FF6B35; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer }

table { width: 100%; border-collapse: collapse }
th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid #eee }
th { font-weight: 600; color: #555 }

.acciones-col { display: flex; gap: 6px }
.btn-qr { background: #1976d2; color: white; border: none; padding: 4px 10px; border-radius: 4px; font-size: 0.85rem; cursor: pointer }
.btn-danger { background: #e53935; color: white; border: none; padding: 4px 10px; border-radius: 4px; font-size: 0.85rem; cursor: pointer }

.error { color: #d32f2f; margin-bottom: 12px }
.cargando, .sin-datos { color: #888; font-style: italic }

/* ── Overlay ── */
.qr-overlay {
  position: fixed; inset: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000; padding: 16px;
}

.qr-dialog {
  background: #f4f5f7;
  border-radius: 20px;
  display: flex;
  gap: 28px;
  padding: 28px;
  max-width: 740px;
  width: 100%;
  max-height: 92vh;
  overflow-y: auto;
  box-shadow: 0 28px 80px rgba(0, 0, 0, 0.22);
}

/* ── Card preview ── */
.card-preview-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.preview-label {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #999;
  margin: 0;
}

.qr-card {
  width: 256px;
  border-radius: 22px;
  box-shadow: 0 16px 48px rgba(0, 0, 0, 0.18);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #fff;
  flex-shrink: 0;
}

.qr-card-header {
  height: 120px;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.hdr-deco {
  position: absolute;
  border-radius: 50%;
  filter: blur(16px);
}
.hdr-deco-1 { width: 100px; height: 100px; top: -25px; right: -25px; }
.hdr-deco-2 { width: 80px;  height: 80px;  bottom: -20px; left: -20px; }

.hdr-content {
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
}
.hdr-emoji { font-size: 2rem; line-height: 1; }
.hdr-nombre { font-size: 0.85rem; font-weight: 700; text-align: center; padding: 0 12px; }

.qr-card-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 14px 16px 12px;
  gap: 8px;
  text-align: center;
}

.card-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 800;
  color: #1a1a1a;
}

.card-frase {
  margin: 0;
  font-size: 0.7rem;
  color: #888;
  font-style: italic;
}

.card-qr-wrap {
  border-radius: 12px;
  border: 2px solid;
  padding: 6px;
  background: #fff;
}

.card-qr-img { width: 120px; height: 120px; display: block; }

.card-wifi {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 600;
}
.wifi-texts { display: flex; flex-direction: column; text-align: left; }
.wifi-net  { font-size: 0.7rem; font-weight: 700; }
.wifi-pass { font-size: 0.65rem; opacity: 0.65; }

.card-mesa-badge {
  margin-top: auto;
  background: #f0f0f0;
  border-radius: 999px;
  padding: 3px 12px;
  font-size: 0.65rem;
  font-weight: 700;
  color: #aaa;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.qr-card-bar { height: 10px; }

/* ── Controls ── */
.controls-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 16px;
  min-width: 0;
}

.controls-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: #1a1a1a;
}

.ctrl-group {
  background: #fff;
  border-radius: 12px;
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}

.ctrl-row-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.ctrl-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #444;
}

/* Toggle switch */
.sw { position: relative; display: inline-flex; cursor: pointer; }
.sw input { opacity: 0; width: 0; height: 0; position: absolute; }
.sw-track {
  width: 40px; height: 22px;
  background: #ccc;
  border-radius: 11px;
  transition: background 0.2s;
  position: relative;
}
.sw-track::after {
  content: '';
  position: absolute;
  width: 16px; height: 16px;
  background: #fff;
  border-radius: 50%;
  top: 3px; left: 3px;
  transition: transform 0.2s;
  box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.sw input:checked ~ .sw-track::after { transform: translateX(18px); }

.ctrl-input {
  width: 100%;
  box-sizing: border-box;
  padding: 8px 10px;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  font-size: 0.9rem;
  outline: none;
  transition: border-color 0.15s;
}
.ctrl-input:focus { border-color: #999; }

.qr-url-chip {
  margin: 0;
  font-size: 0.68rem;
  color: #aaa;
  word-break: break-all;
  background: #eee;
  border-radius: 8px;
  padding: 6px 10px;
}

.ctrl-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: auto;
}

.btn-dl {
  color: white;
  border: none;
  padding: 11px;
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  transition: opacity 0.15s;
}
.btn-dl:hover { opacity: 0.88; }

.btn-cl {
  background: #e8e8e8;
  border: none;
  padding: 10px;
  border-radius: 10px;
  font-size: 0.9rem;
  font-weight: 600;
  color: #555;
  cursor: pointer;
}
.btn-cl:hover { background: #ddd; }

/* Responsive: stack en pantallas chicas */
@media (max-width: 580px) {
  .qr-dialog { flex-direction: column; align-items: center; padding: 20px 16px; }
  .controls-col { width: 100%; }
}
</style>
