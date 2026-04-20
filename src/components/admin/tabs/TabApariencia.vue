<template>
  <div class="tab-content">
    <!-- Info restaurante -->
    <div class="card">
      <div class="card-header"><h2>Información del restaurante</h2></div>
      <div class="card-body form-grid">
        <div class="field">
          <label>Nombre del restaurante</label>
          <input v-model="formRest.nombre" placeholder="Nombre visible en el menú" />
        </div>
        <div class="field field-full">
          <label>Descripción</label>
          <textarea v-model="formRest.descripcion" rows="2" placeholder="Descripción breve para el menú"></textarea>
        </div>
      </div>
    </div>

    <!-- Logo -->
    <div class="card">
      <div class="card-header"><h2>Logo del restaurante</h2></div>
      <div class="card-body">
        <p class="helper-text">Sube el logo que aparecerá en el menú.</p>
        <div class="logo-upload-row">
          <div class="logo-preview-wrap">
            <img v-if="restaurante?.logo_url" :src="restaurante.logo_url" class="logo-preview-img" alt="Logo actual" />
            <div v-else class="logo-preview-empty">
              <SvgIcon :path="mdiSilverwareForkKnife" :size="32" />
            </div>
          </div>
          <div class="logo-upload-actions">
            <label class="btn-upload-logo" :class="{ loading: logoSubiendo }">
              <input type="file" accept="image/jpeg,image/png,image/webp" @change="uploadLogo" :disabled="logoSubiendo" style="display:none" />
              <SvgIcon :path="mdiUpload" :size="15" />
              {{ logoSubiendo ? 'Subiendo...' : 'Subir logo' }}
            </label>
            <span class="logo-hint">JPG, PNG o WebP · máx. 2 MB</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Selector de tema -->
    <div class="card">
      <div class="card-header"><h2>Tema visual del menú</h2></div>
      <div class="card-body">
        <p class="helper-text">Elige el estilo que mejor represente a tu restaurante.</p>
        <div class="temas-grid">
          <div
            v-for="tema in temas" :key="tema.id"
            :class="['tema-card', { selected: formRest.tema === tema.id }]"
            :style="{ background: tema.bg, borderColor: formRest.tema === tema.id ? tema.accent : '#e0e0e0' }"
            @click="seleccionarTema(tema.id)"
          >
            <div class="tema-mockup" :style="{ background: tema.headerBg }">
              <div class="mock-title" :style="{ color: tema.headerText }">
                <SvgIcon :path="mdiSilverwareForkKnife" :size="10" :color="tema.headerText" /> Restaurante
              </div>
              <div class="mock-card" :style="{ background: tema.cardBg }">
                <div class="mock-img" :style="{ background: tema.accent + '40' }"></div>
                <div class="mock-info">
                  <div class="mock-name" :style="{ background: tema.text + '25' }"></div>
                  <div class="mock-price" :style="{ color: tema.accent }">$85</div>
                </div>
              </div>
            </div>
            <div class="tema-label" :style="{ color: tema.text }">
              <strong>{{ tema.nombre }}</strong>
              <span>{{ tema.desc }}</span>
              <span v-if="formRest.tema === tema.id" class="tema-activo"><SvgIcon :path="mdiCheck" :size="11" /></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- QR del menú -->
    <div class="card">
      <div class="card-header"><h2><SvgIcon :path="mdiQrcode" :size="18" style="vertical-align:-3px" /> Código QR del menú</h2></div>
      <div class="card-body qr-dashboard-body">
        <p class="helper-text">Imprime esta tarjeta y colócala en tus mesas. Tus clientes escanean el QR para ver el menú.</p>
        <div class="qr-url-box">
          <code class="qr-url-text">{{ menuUrl }}</code>
          <button @click="copiarUrl" class="btn-copy">{{ copiado ? '✓ Copiado' : 'Copiar' }}</button>
        </div>

        <div class="qr-card-layout">
          <!-- Card preview -->
          <div class="qr-card-preview-col">
            <p class="qr-preview-label">Vista previa</p>
            <div class="qr-card-dm" ref="qrCardDmEl">
              <div class="qr-card-dm-hdr" :style="{ background: temaActualData.headerBg }">
                <div class="qr-hdr-inner">
                  <img v-if="restaurante?.logo_url" :src="restaurante.logo_url" class="qr-hdr-logo" alt="logo" />
                  <span v-else class="qr-hdr-emoji">
                    <SvgIcon :path="mdiSilverwareForkKnife" :size="22" :color="temaActualData.headerText" />
                  </span>
                  <span class="qr-hdr-nombre" :style="{ color: temaActualData.headerText }">{{ restaurante?.nombre }}</span>
                </div>
              </div>
              <div class="qr-card-dm-body">
                <h3 class="qr-dm-title">Escanea el menú</h3>
                <p v-if="formRest.qr_frase_activa" class="qr-dm-frase">"{{ formRest.qr_frase }}"</p>
                <div class="qr-dm-qr-wrap" :style="{ borderColor: temaActualData.accent + '33', boxShadow: `0 5px 18px ${temaActualData.accent}20` }">
                  <img v-if="qrDataUrl" :src="qrDataUrl" class="qr-dm-img" alt="QR" />
                  <div v-else class="qr-dm-placeholder"><div class="spinner"></div></div>
                </div>
                <div v-if="formRest.qr_wifi_activo" class="qr-dm-wifi" :style="{ color: temaActualData.accent }">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M5 12.55a11 11 0 0 1 14.08 0"/>
                    <path d="M1.42 9a16 16 0 0 1 21.16 0"/>
                    <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
                    <line x1="12" y1="20" x2="12.01" y2="20"/>
                  </svg>
                  <div class="qr-wifi-texts">
                    <span class="qr-wifi-net">{{ formRest.qr_wifi_nombre || 'Nombre de red' }}</span>
                    <span class="qr-wifi-pass">{{ formRest.qr_wifi_clave || '••••••••' }}</span>
                  </div>
                </div>
              </div>
              <div class="qr-card-dm-bar" :style="{ background: temaActualData.headerBg }"></div>
            </div>
          </div>

          <!-- Controls -->
          <div class="qr-card-controls-col">
            <h4 class="qr-ctrl-title">Personalizar tarjeta</h4>

            <div class="qr-ctrl-group">
              <div class="qr-ctrl-header">
                <span class="qr-ctrl-label">Frase motivacional</span>
                <label class="sw">
                  <input type="checkbox" v-model="formRest.qr_frase_activa" />
                  <span class="sw-track" :style="formRest.qr_frase_activa ? { background: temaActualData.accent } : {}"></span>
                </label>
              </div>
              <input v-if="formRest.qr_frase_activa" :value="formRest.qr_frase" @input="formRest.qr_frase = ucfirst($event.target.value)" class="qr-ctrl-input" maxlength="60" placeholder="Ej: Delicioso desde el primer vistazo" />
            </div>

            <div class="qr-ctrl-group">
              <div class="qr-ctrl-header">
                <span class="qr-ctrl-label">Info WiFi</span>
                <label class="sw">
                  <input type="checkbox" v-model="formRest.qr_wifi_activo" />
                  <span class="sw-track" :style="formRest.qr_wifi_activo ? { background: temaActualData.accent } : {}"></span>
                </label>
              </div>
              <template v-if="formRest.qr_wifi_activo">
                <input v-model="formRest.qr_wifi_nombre" class="qr-ctrl-input" placeholder="Nombre de red" />
                <input v-model="formRest.qr_wifi_clave" class="qr-ctrl-input" placeholder="Contraseña" />
              </template>
            </div>

            <div class="qr-ctrl-actions">
              <div class="qr-quality-row">
                <span class="qr-ctrl-label">Calidad</span>
                <div class="qr-quality-btns">
                  <button :class="['qr-q-btn', { active: escalaDescarga === 2 }]" @click="escalaDescarga = 2">Normal</button>
                  <button :class="['qr-q-btn', { active: escalaDescarga === 3 }]" @click="escalaDescarga = 3">Alta</button>
                </div>
              </div>
              <button @click="descargarCard" class="btn-dl-card" :style="{ background: temaActualData.accent }" :disabled="!qrDataUrl">
                ⬇ Descargar tarjeta (PNG)
              </button>
              <a v-if="qrDataUrl" :href="qrDataUrl" :download="`qr-menu-${restaurante?.slug || 'menu'}.png`" class="btn-dl-solo">
                ⬇ Solo QR (PNG)
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>


    <UploadToast :model-value="uploadToast" />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { mdiSilverwareForkKnife, mdiUpload, mdiQrcode, mdiCheck } from '@mdi/js'
import { useApi } from '../../../composables/useApi.js'
import { useUpload } from '../../../composables/useUpload.js'
import UploadToast from '../UploadToast.vue'
import { ucfirst } from '../../../utils/ucfirst.js'
import { THEMES as temas, THEMES_EXTRA as TEMAS_EXTRA } from '../../../utils/themes.js'
import SvgIcon from '../../SvgIcon.vue'
import QRCode from 'qrcode'
import html2canvas from 'html2canvas'

const props = defineProps({
  restauranteId: { type: Number, required: true },
  restaurante:   { type: Object, default: null },
  menuUrl:       { type: String, default: '' },
  active:        { type: Boolean, default: false },
})

const emit = defineEmits(['notif', 'restaurante-updated', 'tema-preview'])

const { put } = useApi()
const { uploadToast, xhrUpload } = useUpload()

const guardando    = ref(false)
const logoSubiendo = computed(() => uploadToast.value !== null)
const qrCardDmEl   = ref(null)
const escalaDescarga = ref(2)
const qrDataUrl    = ref(null)
const copiado      = ref(false)

const formRest = ref({
  nombre: '', descripcion: '', tema: 'calido',
  qr_frase: 'Delicioso desde el primer vistazo', qr_frase_activa: true,
  qr_wifi_nombre: '', qr_wifi_clave: '', qr_wifi_activo: false,
})

// Inicializar / reinicializar cuando llega el restaurante
watch(() => props.restaurante, (rest) => {
  if (!rest) return
  formRest.value = {
    nombre:           rest.nombre || '',
    descripcion:      rest.descripcion || '',
    tema:             rest.tema || 'calido',
    qr_frase:         rest.qr_frase || 'Delicioso desde el primer vistazo',
    qr_frase_activa:  Boolean(rest.qr_frase_activa ?? true),
    qr_wifi_nombre:   rest.qr_wifi_nombre || '',
    qr_wifi_clave:    rest.qr_wifi_clave || '',
    qr_wifi_activo:   Boolean(rest.qr_wifi_activo ?? false),
  }
}, { immediate: true })

const temaActualData = computed(() => {
  const t = temas.find(t => t.id === formRest.value.tema) || temas[0]
  return { ...t, ...(TEMAS_EXTRA[t.id] || TEMAS_EXTRA.calido) }
})

const seleccionarTema = (temaId) => {
  formRest.value.tema = temaId
  emit('tema-preview', temaId)
}

const generarQR = async () => {
  if (!props.menuUrl) return
  try {
    qrDataUrl.value = await QRCode.toDataURL(props.menuUrl, {
      width: 300, margin: 2,
      color: { dark: '#1a1a1a', light: '#ffffff' },
    })
  } catch {}
}

// Generar QR cuando el tab se activa
watch(() => props.active, (isActive) => {
  if (isActive) setTimeout(generarQR, 100)
}, { immediate: true })

const descargarCard = async () => {
  if (!qrCardDmEl.value || !qrDataUrl.value) return
  const canvas = await html2canvas(qrCardDmEl.value, {
    scale: escalaDescarga.value, useCORS: true, backgroundColor: null, logging: false,
  })
  const link = document.createElement('a')
  link.download = `tarjeta-qr-${props.restaurante?.slug || 'menu'}.png`
  link.href = canvas.toDataURL('image/png')
  link.click()
}

const copiarUrl = async () => {
  await navigator.clipboard.writeText(props.menuUrl)
  copiado.value = true
  setTimeout(() => { copiado.value = false }, 2000)
}

const uploadLogo = async (event) => {
  const file = event.target.files?.[0]
  if (!file) return
  const fd = new FormData()
  fd.append('logo', file)
  fd.append('restaurante_id', props.restauranteId)
  try {
    const data = await xhrUpload(`${import.meta.env.BASE_URL}api/?route=upload-logo`, fd, 'Subiendo logo…')
    emit('restaurante-updated', { logo_url: data.logo_url })
    emit('notif', { texto: 'Logo actualizado', tipo: 'ok' })
  } catch (err) {
    emit('notif', { texto: err.message, tipo: 'error' })
  } finally {
    event.target.value = ''
  }
}

async function guardarRestaurante() {
  if (!formRest.value.nombre?.trim()) {
    emit('notif', { texto: 'El nombre del restaurante es requerido', tipo: 'error' })
    return
  }
  guardando.value = true
  try {
    // Obtener campos de negocio del restaurante actual para no sobrescribirlos con vacíos
    const rest = props.restaurante || {}
    const payload = {
      ...formRest.value,
      // Preservar campos de negocio que no edita este tab
      compartir_mensaje:     rest.compartir_mensaje     || '',
      pedidos_activos:       rest.pedidos_activos       ?? false,
      pedidos_envio_activo:  rest.pedidos_envio_activo  ?? true,
      pedidos_envio_costo:   rest.pedidos_envio_costo   || 0,
      pedidos_whatsapp:      rest.pedidos_whatsapp      || '',
      pedidos_trans_activo:  rest.pedidos_trans_activo  ?? false,
      pedidos_trans_clabe:   rest.pedidos_trans_clabe   || '',
      pedidos_trans_cuenta:  rest.pedidos_trans_cuenta  || '',
      pedidos_trans_titular: rest.pedidos_trans_titular || '',
      pedidos_trans_banco:   rest.pedidos_trans_banco   || '',
    }
    await put('restaurantes', payload, { id: props.restauranteId })
    emit('restaurante-updated', { ...formRest.value })
    emit('notif', { texto: 'Cambios guardados', tipo: 'ok' })
  } catch (err) { emit('notif', { texto: err.message, tipo: 'error' }) }
  finally { guardando.value = false }
}
defineExpose({ guardar: guardarRestaurante, guardando })
</script>

<style scoped>
/* ─── Logo ─── */
.logo-upload-row { display: flex; align-items: center; gap: 20px; }
.logo-preview-wrap { width: 80px; height: 80px; border-radius: 12px; overflow: hidden; border: 1px solid #e0e0e0; background: #f8f8f8; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
.logo-preview-img   { width: 100%; height: 100%; object-fit: contain; }
.logo-preview-empty { font-size: 2rem; }
.logo-upload-actions { display: flex; flex-direction: column; gap: 6px; }
.btn-upload-logo {
  display: inline-block; padding: 8px 16px; background: #f0f0f0; border: 1.5px solid #ddd;
  border-radius: 8px; font-size: 0.88rem; font-weight: 600; color: #333;
  cursor: pointer; transition: background 0.15s; user-select: none;
}
.btn-upload-logo:hover    { background: #e4e4e4; }
.btn-upload-logo.loading  { opacity: 0.6; cursor: not-allowed; }
.logo-hint { font-size: 0.78rem; color: #aaa; }

/* ─── Temas ─── */
.temas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; }
.tema-card {
  border: 2px solid #e0e0e0; border-radius: 12px; overflow: hidden;
  cursor: pointer; transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
}
.tema-card:hover    { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
.tema-card.selected { box-shadow: 0 4px 16px rgba(0,0,0,0.15); }
.tema-mockup { padding: 12px; display: flex; flex-direction: column; gap: 7px; }
.mock-title  { font-size: 0.7rem; font-weight: 700; }
.mock-card   { border-radius: 6px; padding: 7px; display: flex; gap: 6px; align-items: center; }
.mock-img    { width: 26px; height: 26px; border-radius: 4px; flex-shrink: 0; }
.mock-info   { flex: 1; display: flex; flex-direction: column; gap: 4px; }
.mock-name   { height: 7px; border-radius: 3px; }
.mock-price  { font-size: 0.7rem; font-weight: 800; }
.tema-label  { padding: 8px 12px; display: flex; flex-direction: column; gap: 2px; background: rgba(255,255,255,0.12); border-top: 1px solid rgba(0,0,0,0.05); }
.tema-label strong { font-size: 0.85rem; }
.tema-label span   { font-size: 0.72rem; opacity: 0.65; }
.tema-activo {
  display: inline-flex; align-items: center; justify-content: center;
  width: 18px; height: 18px; border-radius: 50%;
  background: #2e7d32; color: #fff;
  opacity: 1 !important; flex-shrink: 0;
}

/* ─── QR ─── */
.qr-dashboard-body  { display: flex; flex-direction: column; gap: 16px; }
.qr-url-box { display: flex; gap: 8px; align-items: center; background: #f5f5f5; border-radius: 8px; padding: 10px 14px; width: 100%; box-sizing: border-box; }
.qr-url-text { font-size: 0.78rem; color: #555; flex: 1; word-break: break-all; font-family: monospace; }
.btn-copy { background: #fff; border: 1.5px solid #ddd; color: #555; padding: 5px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer; white-space: nowrap; transition: all 0.2s; flex-shrink: 0; }
.btn-copy:hover { border-color: var(--accent); color: var(--accent); }
.qr-card-layout { display: flex; gap: 28px; align-items: flex-start; flex-wrap: wrap; }
.qr-card-preview-col { display: flex; flex-direction: column; align-items: center; gap: 8px; flex-shrink: 0; }
.qr-preview-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #aaa; margin: 0; }
.qr-card-dm { width: 240px; border-radius: 20px; box-shadow: 0 14px 44px rgba(0,0,0,0.16); display: flex; flex-direction: column; overflow: hidden; background: #fff; }
.qr-card-dm-hdr { height: 108px; position: relative; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.qr-hdr-inner  { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; gap: 5px; }
.qr-hdr-emoji  { font-size: 1.8rem; line-height: 1; }
.qr-hdr-logo   { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.4); }
.qr-hdr-nombre { font-size: 0.8rem; font-weight: 700; text-align: center; padding: 0 10px; }
.qr-card-dm-body { flex: 1; display: flex; flex-direction: column; align-items: center; padding: 12px 14px 10px; gap: 8px; text-align: center; }
.qr-dm-title { margin: 0; font-size: 1rem; font-weight: 800; color: #1a1a1a; }
.qr-dm-frase { margin: 0; font-size: 0.68rem; color: #888; font-style: italic; }
.qr-dm-qr-wrap  { border-radius: 11px; border: 2px solid; padding: 6px; background: #fff; }
.qr-dm-img      { width: 108px; height: 108px; display: block; }
.qr-dm-placeholder { width: 108px; height: 108px; display: flex; align-items: center; justify-content: center; background: #f5f5f5; border-radius: 6px; }
.qr-dm-wifi     { display: flex; align-items: center; gap: 6px; font-weight: 600; }
.qr-wifi-texts  { display: flex; flex-direction: column; text-align: left; }
.qr-wifi-net    { font-size: 0.68rem; font-weight: 700; }
.qr-wifi-pass   { font-size: 0.63rem; opacity: 0.65; }
.qr-card-dm-bar { height: 9px; }
.qr-card-controls-col { flex: 1; min-width: 220px; display: flex; flex-direction: column; gap: 14px; }
.qr-ctrl-title  { margin: 0; font-size: 1rem; font-weight: 700; color: #222; }
.qr-ctrl-group  { background: #f9f9f9; border: 1px solid #eee; border-radius: 10px; padding: 12px; display: flex; flex-direction: column; gap: 8px; }
.qr-ctrl-header { display: flex; align-items: center; justify-content: space-between; }
.qr-ctrl-label  { font-size: 0.83rem; font-weight: 600; color: #444; }
.qr-ctrl-input  { width: 100%; box-sizing: border-box; padding: 7px 10px; border: 1px solid #e0e0e0; border-radius: 7px; font-size: 0.88rem; outline: none; }
.qr-ctrl-input:focus { border-color: #aaa; }
.qr-ctrl-actions { display: flex; flex-direction: column; gap: 8px; margin-top: 4px; }
.qr-quality-row  { display: flex; align-items: center; justify-content: space-between; }
.qr-quality-btns { display: flex; gap: 6px; }
.qr-q-btn { padding: 5px 14px; border: 1px solid #ddd; border-radius: 6px; background: #f5f5f5; font-size: 0.8rem; cursor: pointer; transition: background 0.15s, color 0.15s; }
.qr-q-btn.active { background: #222; color: #fff; border-color: #222; }
.btn-dl-card { color: #fff; border: none; padding: 11px; border-radius: 9px; font-size: 0.9rem; font-weight: 700; cursor: pointer; transition: opacity 0.15s; }
.btn-dl-card:hover     { opacity: 0.88; }
.btn-dl-card:disabled  { opacity: 0.5; cursor: not-allowed; }
.btn-dl-solo { display: inline-block; text-align: center; background: #eeeeee; color: #555; text-decoration: none; padding: 9px; border-radius: 9px; font-size: 0.85rem; font-weight: 600; transition: background 0.15s; }
.btn-dl-solo:hover { background: #e0e0e0; }

@media (max-width: 600px) {
  .temas-grid { grid-template-columns: 1fr 1fr; }
}
</style>
