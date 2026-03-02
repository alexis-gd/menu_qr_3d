<template>
  <div class="admin-panel">
    <!-- ═══ Header ═══ -->
    <header class="panel-header">
      <div class="header-left">
        <span class="header-icon">🍽️</span>
        <div>
          <h1 class="header-title">{{ restaurante?.nombre || 'Mi Restaurante' }}</h1>
          <span class="header-sub">Panel de administración</span>
        </div>
      </div>
      <button @click="logout" class="btn-logout">Salir</button>
    </header>

    <!-- Loading inicial -->
    <div v-if="cargandoInicial" class="loading-screen">
      <div class="spinner-lg"></div>
      <p>Cargando panel...</p>
    </div>

    <div v-else-if="errorInicial" class="error-screen">
      <p>{{ errorInicial }}</p>
    </div>

    <div v-else class="panel-body">
      <!-- ═══ Tabs ═══ -->
      <nav class="tab-nav">
        <button
          v-for="tab in tabs" :key="tab.id"
          :class="['tab-btn', { active: tabActivo === tab.id }]"
          @click="tabActivo = tab.id"
        >
          <span class="tab-icon">{{ tab.icon }}</span>
          <span class="tab-label">{{ tab.label }}</span>
        </button>
      </nav>

      <!-- Notificación flotante -->
      <transition name="notif-anim">
        <div v-if="notif" :class="['notif', `notif-${notif.tipo}`]">{{ notif.texto }}</div>
      </transition>

      <!-- ════════════════════════════════
           TAB: PLATILLOS
      ════════════════════════════════ -->
      <div v-show="tabActivo === 'platillos'" class="tab-content">
        <!-- Formulario nuevo platillo -->
        <div class="card">
          <div class="card-header collapsible" @click="formAbierto = !formAbierto">
            <h2>+ Agregar platillo</h2>
            <span class="chevron">{{ formAbierto ? '▲' : '▼' }}</span>
          </div>
          <div v-show="formAbierto" class="card-body">
            <div class="form-grid">
              <div class="field">
                <label>Categoría *</label>
                <select v-model="formProd.categoria_id">
                  <option value="" disabled>Selecciona categoría</option>
                  <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                    {{ cat.icono || '' }} {{ cat.nombre }}
                  </option>
                </select>
              </div>
              <div class="field">
                <label>Nombre del platillo *</label>
                <input v-model="formProd.nombre" placeholder="Ej: Tacos al Pastor" />
              </div>
              <div class="field">
                <label>Precio *</label>
                <input v-model="formProd.precio" type="number" min="0" step="0.01" placeholder="0.00" />
              </div>
              <div class="field field-full">
                <label>Descripción breve</label>
                <textarea v-model="formProd.descripcion" rows="2" placeholder="Descripción opcional..."></textarea>
              </div>
              <div class="field field-full">
                <button @click="crearProducto" class="btn-primary" :disabled="guardando">
                  {{ guardando ? 'Guardando...' : '+ Agregar platillo' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Lista de productos -->
        <div class="card">
          <div class="card-header">
            <h2>Platillos del menú</h2>
            <span class="count-badge">{{ productos.length }}</span>
          </div>
          <div class="card-body no-pad">
            <div v-if="loadingProductos" class="loading-inline"><div class="spinner"></div></div>
            <div v-else-if="!productos.length" class="empty-state">
              <span>🍽️</span>
              <p>Sin platillos todavía.<br>Agrega el primero arriba.</p>
            </div>
            <div v-else class="prod-lista">
              <div
                v-for="prod in productosOrdenados"
                :key="prod.id"
                class="prod-item"
                :class="{ editing: prodEditando === prod.id }"
              >
                <!-- ── Modo edición ── -->
                <div v-if="prodEditando === prod.id" class="prod-edit-form">
                  <div class="edit-thumb">
                    <img v-if="prod.foto_principal" :src="thumbUrl(prod.foto_principal)" :alt="prod.nombre" />
                    <span v-else>📷</span>
                  </div>
                  <div class="edit-fields">
                    <select v-model="formEdit.categoria_id">
                      <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                    </select>
                    <input v-model="formEdit.nombre" placeholder="Nombre" />
                    <input v-model="formEdit.precio" type="number" min="0" step="0.01" placeholder="Precio" />
                    <textarea v-model="formEdit.descripcion" rows="2" placeholder="Descripción"></textarea>
                    <div class="edit-actions">
                      <button @click="guardarEdicionProducto(prod.id)" class="btn-save">✓ Guardar</button>
                      <button @click="cancelarEdicion" class="btn-cancel">✕ Cancelar</button>
                    </div>
                  </div>
                </div>

                <!-- ── Modo normal ── -->
                <template v-else>
                  <!-- Miniatura (click → preview) -->
                  <div class="prod-thumb" @click="abrirPreview(prod)" :title="'Ver foto'">
                    <img
                      v-if="prod.foto_principal"
                      :src="thumbUrl(prod.foto_principal)"
                      :alt="prod.nombre"
                      @error="($e) => $e.target.style.display='none'"
                    />
                    <span v-else class="thumb-empty">📷</span>
                    <div v-if="prod.foto_principal" class="thumb-overlay">👁</div>
                  </div>

                  <!-- Info -->
                  <div class="prod-info">
                    <strong class="prod-nombre">{{ prod.nombre }}</strong>
                    <span class="prod-cat">{{ catMap[prod.categoria_id] || '—' }}</span>
                    <span class="prod-precio">${{ Number(prod.precio).toFixed(2) }}</span>
                  </div>

                  <!-- Badges -->
                  <div class="prod-badges">
                    <span v-if="prod.tiene_ar" class="badge badge-3d">3D ✓</span>
                    <span v-else class="badge badge-no3d">Sin 3D</span>
                  </div>

                  <!-- Acciones -->
                  <div class="prod-actions">
                    <button @click="iniciarEdicion(prod)" class="btn-icon btn-edit" title="Editar platillo">✏️</button>
                    <label class="btn-icon btn-foto" title="Subir foto">
                      📷 <input type="file" multiple accept="image/*" @change="subirFotos(prod.id, $event)" hidden />
                    </label>
                    <label
                      v-if="!prod.tiene_ar"
                      class="btn-icon btn-3d"
                      title="Subir modelo 3D (.glb)"
                    >
                      📦 <input type="file" accept=".glb" @change="subirGlb(prod.id, $event)" hidden />
                    </label>
                    <button @click="eliminarProducto(prod.id)" class="btn-icon btn-del" title="Eliminar">🗑</button>
                  </div>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════
           TAB: CATEGORÍAS
      ════════════════════════════════ -->
      <div v-show="tabActivo === 'categorias'" class="tab-content">
        <div class="card">
          <div class="card-header">
            <h2>Nueva categoría</h2>
          </div>
          <div class="card-body">
            <div class="form-row">
              <div class="field" style="flex:1">
                <label>Nombre</label>
                <input v-model="formCat.nombre" placeholder="Ej: Entradas, Bebidas..." @keyup.enter="crearCategoria" />
              </div>
              <div class="field">
                <label>Ícono</label>
                <div class="emoji-wrap">
                  <button type="button" class="emoji-btn" @click.stop="togglePicker('nuevo')">
                    <span class="emoji-display">{{ formCat.icono || '📋' }}</span>
                    <span class="picker-caret">▾</span>
                  </button>
                  <div v-if="pickerAbierto === 'nuevo'" class="emoji-picker" @click.stop>
                    <div v-for="g in emojiGrupos" :key="g.nombre" class="emoji-grupo">
                      <div class="emoji-grupo-titulo">{{ g.nombre }}</div>
                      <div class="emoji-grid">
                        <button v-for="e in g.emojis" :key="e" type="button" class="emoji-opt"
                          :class="{ selected: formCat.icono === e }"
                          @click="seleccionarEmoji(e, 'nuevo')">{{ e }}</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="field field-btn">
                <label>&nbsp;</label>
                <button @click="crearCategoria" class="btn-primary">Agregar</button>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h2>Categorías</h2>
            <span class="count-badge">{{ categorias.length }}</span>
          </div>
          <div class="card-body no-pad">
            <div v-if="!categorias.length" class="empty-state" style="padding:32px">
              <span>📋</span>
              <p>Sin categorías todavía.</p>
            </div>
            <div v-else class="cat-lista">
              <div v-for="cat in categorias" :key="cat.id" class="cat-item">
                <!-- Modo edición -->
                <div v-if="catEditando === cat.id" class="cat-edit-form">
                  <div class="emoji-wrap">
                    <button type="button" class="emoji-btn emoji-btn-sm" @click.stop="togglePicker(cat.id)">
                      <span class="emoji-display">{{ formCatEdit.icono || '📋' }}</span>
                      <span class="picker-caret">▾</span>
                    </button>
                    <div v-if="pickerAbierto === cat.id" class="emoji-picker emoji-picker-right" @click.stop>
                      <div v-for="g in emojiGrupos" :key="g.nombre" class="emoji-grupo">
                        <div class="emoji-grupo-titulo">{{ g.nombre }}</div>
                        <div class="emoji-grid">
                          <button v-for="e in g.emojis" :key="e" type="button" class="emoji-opt"
                            :class="{ selected: formCatEdit.icono === e }"
                            @click="seleccionarEmoji(e, cat.id)">{{ e }}</button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <input v-model="formCatEdit.nombre" placeholder="Nombre" class="input-nombre" @keyup.enter="guardarEdicionCategoria(cat.id)" />
                  <button @click="guardarEdicionCategoria(cat.id)" class="btn-save-sm">✓</button>
                  <button @click="catEditando = null" class="btn-cancel-sm">✕</button>
                </div>

                <!-- Modo normal -->
                <template v-else>
                  <span class="cat-emoji">{{ cat.icono || '📋' }}</span>
                  <span class="cat-nombre">{{ cat.nombre }}</span>
                  <span class="cat-count">{{ conteoProductos(cat.id) }} platillo(s)</span>
                  <button @click="iniciarEdicionCategoria(cat)" class="btn-icon btn-edit" title="Editar">✏️</button>
                  <button @click="eliminarCategoria(cat.id)" class="btn-icon btn-del" title="Eliminar">🗑</button>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════
           TAB: APARIENCIA & QR
      ════════════════════════════════ -->
      <div v-show="tabActivo === 'apariencia'" class="tab-content">
        <!-- Info restaurante -->
        <div class="card">
          <div class="card-header">
            <h2>Información del restaurante</h2>
          </div>
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

        <!-- Selector de tema -->
        <div class="card">
          <div class="card-header">
            <h2>Tema visual del menú</h2>
          </div>
          <div class="card-body">
            <p class="helper-text">Elige el estilo que mejor represente a tu restaurante.</p>
            <div class="temas-grid">
              <div
                v-for="tema in temas" :key="tema.id"
                :class="['tema-card', { selected: formRest.tema === tema.id }]"
                :style="{ background: tema.bg, borderColor: formRest.tema === tema.id ? tema.accent : '#e0e0e0' }"
                @click="formRest.tema = tema.id"
              >
                <!-- Miniatura preview -->
                <div class="tema-mockup" :style="{ background: tema.headerBg }">
                  <div class="mock-title" :style="{ color: tema.headerText }">🍽️ Restaurante</div>
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
                  <span v-if="formRest.tema === tema.id" class="tema-activo">✓ Activo</span>
                </div>
              </div>
            </div>
            <button @click="guardarRestaurante" class="btn-primary" style="margin-top:16px" :disabled="guardando">
              {{ guardando ? 'Guardando...' : 'Guardar cambios' }}
            </button>
          </div>
        </div>

        <!-- QR del menú -->
        <div class="card">
          <div class="card-header">
            <h2>🔲 Código QR del menú</h2>
          </div>
          <div class="card-body qr-section">
            <p class="helper-text">Imprime este QR y colócalo en tus mesas. Tus clientes lo escanean para ver el menú.</p>
            <div class="qr-url-box">
              <code class="qr-url-text">{{ menuUrl }}</code>
              <button @click="copiarUrl" class="btn-copy">{{ copiado ? '✓ Copiado' : 'Copiar' }}</button>
            </div>
            <div class="qr-preview-wrap">
              <canvas ref="qrCanvasRef" class="qr-canvas"></canvas>
              <div v-if="!qrGenerado" class="qr-loading">
                <div class="spinner"></div>
              </div>
            </div>
            <a
              v-if="qrDataUrl"
              :href="qrDataUrl"
              :download="`qr-menu-${restaurante?.slug || 'menu'}.png`"
              class="btn-download-qr"
            >
              ⬇ Descargar QR (PNG)
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ Modal preview de foto ═══ -->
    <div v-if="preview" class="preview-overlay" @click="preview = null">
      <div class="preview-box">
        <button class="preview-close" @click="preview = null">✕</button>
        <img :src="preview.url" :alt="preview.nombre" class="preview-img" />
        <p class="preview-nombre">{{ preview.nombre }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../../composables/useApi.js'
import QRCode from 'qrcode'

const router = useRouter()
const { get, post, put, del } = useApi()

// ── Estado general ──
const restaurante     = ref(null)
const restauranteId   = ref(null)
const categorias      = ref([])
const productos       = ref([])
const cargandoInicial = ref(true)
const errorInicial    = ref(null)
const loadingProductos = ref(false)
const guardando       = ref(false)
const notif           = ref(null)
const tabActivo       = ref('platillos')
const formAbierto     = ref(true)

// ── Preview de foto ──
const preview = ref(null)

// ── Emoji picker ──
const pickerAbierto = ref(null)

const emojiGrupos = [
  { nombre: 'Platos', emojis: ['🍕','🍔','🌮','🌯','🥗','🍖','🥩','🍗','🍱','🍜','🍝','🍲','🫕','🥘','🌭','🫔','🥙','🍛'] },
  { nombre: 'Mariscos', emojis: ['🍣','🦐','🦞','🦀','🐟','🍤','🦑','🍙','🦪','🐠'] },
  { nombre: 'Bebidas', emojis: ['🥤','☕','🧃','🧋','🍵','🍺','🍷','🍹','🥛','🍸','🧉','🫖'] },
  { nombre: 'Postres', emojis: ['🍰','🎂','🍮','🍦','🧁','🍩','🍪','🍫','🍬','🍭','🥧','🍡'] },
  { nombre: 'Extras', emojis: ['⭐','🔥','💎','🌿','🥇','❤️','🌶️','🥑','✨','🆕','🎯','🫙'] },
]

const togglePicker = (id) => {
  pickerAbierto.value = pickerAbierto.value === id ? null : id
}

const seleccionarEmoji = (emoji, target) => {
  if (target === 'nuevo') formCat.value.icono = emoji
  else formCatEdit.value.icono = emoji
  pickerAbierto.value = null
}

const cerrarPickerGlobal = (e) => {
  if (!e.target.closest('.emoji-wrap')) pickerAbierto.value = null
}

// ── Edición ──
const prodEditando = ref(null)
const formEdit     = ref({})
const catEditando  = ref(null)
const formCatEdit  = ref({})

// ── QR ──
const qrCanvasRef = ref(null)
const qrDataUrl   = ref(null)
const qrGenerado  = ref(false)
const copiado     = ref(false)

const menuUrl = computed(() => {
  if (!restaurante.value?.slug) return ''
  const base = import.meta.env.BASE_URL
  return `${window.location.origin}${base}?r=${restaurante.value.slug}`
})

// Tabs
const tabs = [
  { id: 'platillos',  icon: '🍽️', label: 'Platillos'  },
  { id: 'categorias', icon: '📋', label: 'Categorías' },
  { id: 'apariencia', icon: '🎨', label: 'Apariencia' },
]

// Temas
const temas = [
  { id: 'calido',  nombre: 'Cálido',   desc: 'Bistró, tacos, casero',  bg: '#fdf6f0', cardBg: '#fff', text: '#3d2c1e', accent: '#d4691e', headerBg: 'linear-gradient(135deg,#b5451b,#e8841f)', headerText: '#fff' },
  { id: 'oscuro',  nombre: 'Oscuro',   desc: 'Bar, premium, elegante', bg: '#1a1a2e', cardBg: '#1e1e2e', text: '#e8e8f0', accent: '#f0c040', headerBg: 'linear-gradient(135deg,#0a0a1a,#1a1a38)', headerText: '#f0c040' },
  { id: 'moderno', nombre: 'Moderno',  desc: 'Saludable, minimalista', bg: '#f4f4f4', cardBg: '#fff',    text: '#111111', accent: '#1a7f5a', headerBg: '#fff', headerText: '#111' },
  { id: 'rapida',  nombre: 'Express',  desc: 'Rápido, cafetería',      bg: '#fffbf0', cardBg: '#fff',    text: '#1a1a1a', accent: '#d43f2e', headerBg: 'linear-gradient(135deg,#c0392b,#e74c3c)', headerText: '#fff' },
]

// Formularios
const formProd = ref({ categoria_id: '', nombre: '', precio: '', descripcion: '' })
const formCat  = ref({ nombre: '', icono: '' })
const formRest = ref({ nombre: '', descripcion: '', tema: 'calido' })

// Mapas y cómputos
const catMap = computed(() => {
  const m = {}
  categorias.value.forEach(c => { m[c.id] = c.nombre })
  return m
})

const productosOrdenados = computed(() =>
  [...productos.value].sort((a, b) => {
    const ca = catMap.value[a.categoria_id] || ''
    const cb = catMap.value[b.categoria_id] || ''
    return ca.localeCompare(cb) || a.nombre.localeCompare(b.nombre)
  })
)

const conteoProductos = (catId) => productos.value.filter(p => p.categoria_id == catId).length

const thumbUrl = (ruta) => ruta ? '/uploads/' + ruta : null

// Notificación temporal
const mostrarNotif = (texto, tipo = 'ok') => {
  notif.value = { texto, tipo }
  setTimeout(() => { notif.value = null }, 3000)
}

const logout = () => {
  localStorage.removeItem('admin_token')
  router.push('/admin')
}

// ── Generar QR ──
const generarQR = async () => {
  if (!menuUrl.value || !qrCanvasRef.value) return
  try {
    await QRCode.toCanvas(qrCanvasRef.value, menuUrl.value, {
      width: 260,
      margin: 2,
      color: { dark: '#1a1a1a', light: '#ffffff' },
    })
    qrDataUrl.value = qrCanvasRef.value.toDataURL('image/png')
    qrGenerado.value = true
  } catch {}
}

const copiarUrl = async () => {
  await navigator.clipboard.writeText(menuUrl.value)
  copiado.value = true
  setTimeout(() => { copiado.value = false }, 2000)
}

// Regenerar QR cuando se cambie al tab de apariencia y ya tengamos slug
watch(tabActivo, (tab) => {
  if (tab === 'apariencia') {
    setTimeout(generarQR, 100)
  }
})

// ── Preview foto ──
const abrirPreview = (prod) => {
  if (!prod.foto_principal) return
  preview.value = { url: thumbUrl(prod.foto_principal), nombre: prod.nombre }
}

// ── Edición de productos ──
const iniciarEdicion = (prod) => {
  prodEditando.value = prod.id
  formEdit.value = {
    categoria_id: prod.categoria_id,
    nombre: prod.nombre,
    precio: prod.precio,
    descripcion: prod.descripcion || '',
  }
}

const cancelarEdicion = () => {
  prodEditando.value = null
  formEdit.value = {}
}

const guardarEdicionProducto = async (id) => {
  if (!formEdit.value.nombre?.trim()) {
    mostrarNotif('El nombre es requerido', 'error')
    return
  }
  guardando.value = true
  try {
    await put('productos', {
      categoria_id: formEdit.value.categoria_id,
      nombre: formEdit.value.nombre.trim(),
      precio: parseFloat(formEdit.value.precio),
      descripcion: formEdit.value.descripcion.trim(),
    }, { id })
    prodEditando.value = null
    await loadProductos()
    mostrarNotif('Platillo actualizado')
  } catch (err) {
    mostrarNotif(err.message, 'error')
  } finally {
    guardando.value = false
  }
}

// ── Edición de categorías ──
const iniciarEdicionCategoria = (cat) => {
  catEditando.value = cat.id
  formCatEdit.value = { nombre: cat.nombre, icono: cat.icono || '' }
}

const guardarEdicionCategoria = async (id) => {
  if (!formCatEdit.value.nombre?.trim()) {
    mostrarNotif('El nombre es requerido', 'error')
    return
  }
  try {
    await put('categorias', {
      nombre: formCatEdit.value.nombre.trim(),
      icono: formCatEdit.value.icono.trim() || null,
    }, { id })
    catEditando.value = null
    await loadCategorias()
    mostrarNotif('Categoría actualizada')
  } catch (err) {
    mostrarNotif(err.message, 'error')
  }
}

// ── Carga inicial ──
onMounted(async () => {
  document.addEventListener('click', cerrarPickerGlobal)
  try {
    const res = await get('restaurantes')
    const lista = res.restaurantes || []
    if (!lista.length) {
      errorInicial.value = 'No hay restaurante configurado.'
      return
    }
    const rest = lista[0]
    restaurante.value = rest
    restauranteId.value = rest.id
    formRest.value = { nombre: rest.nombre || '', descripcion: rest.descripcion || '', tema: rest.tema || 'calido' }
    await Promise.all([loadCategorias(), loadProductos()])
  } catch (err) {
    errorInicial.value = 'Error al conectar: ' + err.message
  } finally {
    cargandoInicial.value = false
  }
})

onUnmounted(() => {
  document.removeEventListener('click', cerrarPickerGlobal)
})

// ── Categorías ──
async function loadCategorias() {
  const res = await get('categorias', { restaurante_id: restauranteId.value })
  categorias.value = res.categorias || []
}

async function crearCategoria() {
  if (!formCat.value.nombre.trim()) { mostrarNotif('Escribe un nombre', 'error'); return }
  try {
    await post('categorias', { restaurante_id: restauranteId.value, nombre: formCat.value.nombre.trim(), icono: formCat.value.icono.trim() || null })
    formCat.value = { nombre: '', icono: '' }
    await loadCategorias()
    mostrarNotif('Categoría creada')
  } catch (err) { mostrarNotif(err.message, 'error') }
}

async function eliminarCategoria(id) {
  if (!confirm('¿Eliminar esta categoría?')) return
  try {
    await del('categorias', { id })
    await loadCategorias()
    mostrarNotif('Categoría eliminada')
  } catch (err) { mostrarNotif(err.message, 'error') }
}

// ── Productos ──
async function loadProductos() {
  loadingProductos.value = true
  try {
    const res = await get('productos', { restaurante_id: restauranteId.value })
    productos.value = res.productos || []
  } finally {
    loadingProductos.value = false
  }
}

async function crearProducto() {
  const f = formProd.value
  if (!f.categoria_id || !f.nombre.trim() || f.precio === '') { mostrarNotif('Categoría, nombre y precio son requeridos', 'error'); return }
  guardando.value = true
  try {
    await post('productos', { categoria_id: f.categoria_id, nombre: f.nombre.trim(), precio: parseFloat(f.precio), descripcion: f.descripcion.trim() })
    formProd.value = { categoria_id: '', nombre: '', precio: '', descripcion: '' }
    await loadProductos()
    mostrarNotif('Platillo agregado')
  } catch (err) { mostrarNotif(err.message, 'error') }
  finally { guardando.value = false }
}

async function eliminarProducto(id) {
  if (!confirm('¿Eliminar este platillo del menú?')) return
  try {
    await del('productos', { id })
    await loadProductos()
    mostrarNotif('Platillo eliminado')
  } catch (err) { mostrarNotif(err.message, 'error') }
}

async function subirFotos(prodId, event) {
  const files = event.target.files
  if (!files.length) return
  const fd = new FormData()
  fd.append('producto_id', prodId)
  for (let i = 0; i < files.length; i++) fd.append('fotos[]', files[i])
  try {
    const token = localStorage.getItem('admin_token')
    const res = await fetch(`/api/?route=upload-fotos&token=${token}`, { method: 'POST', body: fd })
    if (!res.ok) throw new Error('Error al subir fotos')
    event.target.value = ''
    await loadProductos()
    mostrarNotif('Foto subida correctamente')
  } catch (err) { mostrarNotif(err.message, 'error') }
}

async function subirGlb(prodId, event) {
  const file = event.target.files[0]
  if (!file) return
  const fd = new FormData()
  fd.append('producto_id', prodId)
  fd.append('modelo', file)
  try {
    const token = localStorage.getItem('admin_token')
    const res = await fetch(`/api/?route=upload-glb&token=${token}`, { method: 'POST', body: fd })
    const data = await res.json()
    if (!res.ok) throw new Error(data.error || 'Error al subir el modelo')
    event.target.value = ''
    await loadProductos()
    mostrarNotif('Modelo 3D subido. ¡Ya disponible en el menú!')
  } catch (err) { mostrarNotif(err.message, 'error') }
}

// ── Restaurante ──
async function guardarRestaurante() {
  guardando.value = true
  try {
    await put('restaurantes', formRest.value, { id: restauranteId.value })
    restaurante.value = { ...restaurante.value, ...formRest.value }
    mostrarNotif('Cambios guardados')
  } catch (err) { mostrarNotif(err.message, 'error') }
  finally { guardando.value = false }
}
</script>

<style scoped>
/* ─── Base ─── */
.admin-panel {
  min-height: 100vh;
  background: #f0f2f5;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* ─── Header ─── */
.panel-header {
  background: #fff;
  border-bottom: 1px solid #e8e8e8;
  padding: 0 24px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
.header-left { display: flex; align-items: center; gap: 12px; }
.header-icon { font-size: 1.8rem; }
.header-title { font-size: 1.1rem; font-weight: 700; color: #1a1a1a; margin: 0; }
.header-sub { font-size: 0.75rem; color: #aaa; }
.btn-logout {
  background: transparent; border: 1.5px solid #ddd; color: #777;
  padding: 7px 16px; border-radius: 8px; font-size: 0.85rem;
  font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.btn-logout:hover { border-color: #e53935; color: #e53935; }

/* ─── Loading / Error ─── */
.loading-screen, .error-screen {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; min-height: 60vh; gap: 16px; color: #888;
}
.spinner-lg {
  width: 44px; height: 44px;
  border: 3px solid #e0e0e0; border-top-color: #FF6B35;
  border-radius: 50%; animation: spin 0.7s linear infinite;
}
.spinner {
  width: 28px; height: 28px;
  border: 2.5px solid #e0e0e0; border-top-color: #FF6B35;
  border-radius: 50%; animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ─── Body ─── */
.panel-body { max-width: 900px; margin: 0 auto; padding: 20px 16px 60px; }

/* ─── Tabs ─── */
.tab-nav {
  display: flex; gap: 4px; background: #fff; border-radius: 14px;
  padding: 6px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); margin-bottom: 20px;
}
.tab-btn {
  flex: 1; display: flex; align-items: center; justify-content: center;
  gap: 7px; padding: 10px; border: none; border-radius: 10px;
  background: transparent; color: #999; font-size: 0.88rem;
  font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.tab-btn:hover { background: #f5f5f5; color: #333; }
.tab-btn.active { background: #FF6B35; color: #fff; box-shadow: 0 2px 8px rgba(255,107,53,0.3); }
.tab-icon { font-size: 1.05rem; }

/* ─── Cards ─── */
.card { background: #fff; border-radius: 14px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); margin-bottom: 16px; }
.card-header {
  padding: 16px 20px; border-bottom: 1px solid #f0f0f0;
  display: flex; align-items: center; justify-content: space-between;
}
.card-header.collapsible { cursor: pointer; user-select: none; }
.card-header h2 { font-size: 0.95rem; font-weight: 700; color: #1a1a1a; margin: 0; }
.chevron { font-size: 0.8rem; color: #bbb; }
.count-badge { background: #f0f0f0; color: #666; border-radius: 20px; padding: 2px 10px; font-size: 0.8rem; font-weight: 700; }
.card-body { padding: 20px; }
.card-body.no-pad { padding: 0; }

/* ─── Formularios ─── */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-row { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
.field { display: flex; flex-direction: column; gap: 5px; }
.field-full { grid-column: 1 / -1; }
.field-btn { flex-shrink: 0; }
.field label { font-size: 0.8rem; font-weight: 600; color: #555; }
.field input, .field select, .field textarea {
  padding: 10px 12px; border: 1.5px solid #e0e0e0; border-radius: 8px;
  font-size: 0.9rem; outline: none; background: #fafafa; transition: border-color 0.2s; font-family: inherit;
}
.field input:focus, .field select:focus, .field textarea:focus { border-color: #FF6B35; background: #fff; }
.field textarea { resize: vertical; min-height: 60px; }

/* ─── Botones ─── */
.btn-primary {
  background: linear-gradient(135deg, #FF6B35 0%, #f7931e 100%);
  color: #fff; border: none; padding: 10px 20px; border-radius: 8px;
  font-size: 0.9rem; font-weight: 700; cursor: pointer;
  transition: opacity 0.2s, transform 0.1s; white-space: nowrap;
}
.btn-primary:hover:not(:disabled) { opacity: 0.88; transform: translateY(-1px); }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-icon {
  width: 34px; height: 34px; display: flex; align-items: center;
  justify-content: center; border: none; border-radius: 8px;
  cursor: pointer; font-size: 0.9rem; transition: background 0.2s; flex-shrink: 0;
}
label.btn-icon { cursor: pointer; }
.btn-edit  { background: #fff3e0; }
.btn-edit:hover  { background: #ffe0b2; }
.btn-foto  { background: #e3f2fd; }
.btn-foto:hover  { background: #bbdefb; }
.btn-3d    { background: #f3e5f5; }
.btn-3d:hover    { background: #e1bee7; }
.btn-del   { background: #ffebee; color: #c62828; }
.btn-del:hover   { background: #ffcdd2; }

.btn-save  { background: #2e7d32; color: #fff; border: none; padding: 7px 14px; border-radius: 7px; font-size: 0.85rem; font-weight: 700; cursor: pointer; }
.btn-save:hover  { background: #1b5e20; }
.btn-cancel { background: #f5f5f5; color: #555; border: none; padding: 7px 14px; border-radius: 7px; font-size: 0.85rem; font-weight: 600; cursor: pointer; }
.btn-cancel:hover { background: #e0e0e0; }

.btn-save-sm   { background: #2e7d32; color: #fff; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; }
.btn-cancel-sm { background: #f5f5f5; color: #555; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; }

/* ─── Lista de productos ─── */
.loading-inline { display: flex; justify-content: center; padding: 32px; }
.empty-state {
  text-align: center; color: #bbb;
  display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 40px;
}
.empty-state span { font-size: 2.5rem; }
.empty-state p { font-size: 0.9rem; line-height: 1.5; }

.prod-lista { display: flex; flex-direction: column; }

.prod-item {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 16px; border-bottom: 1px solid #f5f5f5; transition: background 0.15s;
}
.prod-item:last-child { border-bottom: none; }
.prod-item:hover { background: #fafafa; }
.prod-item.editing { background: #fffde7; align-items: flex-start; }

/* ── Miniatura ── */
.prod-thumb {
  width: 56px; height: 56px; border-radius: 8px; background: #f0f0f0;
  overflow: hidden; flex-shrink: 0; cursor: pointer; position: relative;
}
.prod-thumb img { width: 100%; height: 100%; object-fit: cover; }
.thumb-empty { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; opacity: 0.35; }
.thumb-overlay {
  position: absolute; inset: 0; background: rgba(0,0,0,0.45);
  display: flex; align-items: center; justify-content: center;
  opacity: 0; transition: opacity 0.2s; font-size: 1.2rem;
}
.prod-thumb:hover .thumb-overlay { opacity: 1; }

/* ── Info platillo ── */
.prod-info { flex: 1; display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.prod-nombre { font-size: 0.92rem; font-weight: 700; color: #1a1a1a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.prod-cat  { font-size: 0.75rem; color: #aaa; }
.prod-precio { font-size: 0.88rem; font-weight: 700; color: #FF6B35; }

.prod-badges { flex-shrink: 0; }
.badge { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; }
.badge-3d   { background: #e8f5e9; color: #2e7d32; }
.badge-no3d { background: #f5f5f5; color: #bbb; }

.prod-actions { display: flex; gap: 5px; flex-shrink: 0; }

/* ── Formulario edición inline ── */
.prod-edit-form { display: flex; gap: 12px; width: 100%; padding: 4px 0; }
.edit-thumb {
  width: 56px; height: 56px; border-radius: 8px; background: #f0f0f0;
  overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
}
.edit-thumb img { width: 100%; height: 100%; object-fit: cover; }
.edit-fields { flex: 1; display: flex; flex-direction: column; gap: 7px; }
.edit-fields select,
.edit-fields input,
.edit-fields textarea {
  padding: 7px 10px; border: 1.5px solid #e0e0e0; border-radius: 7px;
  font-size: 0.88rem; outline: none; font-family: inherit; background: #fff;
}
.edit-fields select:focus,
.edit-fields input:focus,
.edit-fields textarea:focus { border-color: #FF6B35; }
.edit-fields textarea { resize: vertical; min-height: 50px; }
.edit-actions { display: flex; gap: 8px; }

/* ─── Categorías ─── */
.cat-lista { display: flex; flex-direction: column; }
.cat-item {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 16px; border-bottom: 1px solid #f5f5f5;
}
.cat-item:last-child { border-bottom: none; }
.cat-emoji  { font-size: 1.3rem; width: 28px; text-align: center; flex-shrink: 0; }
.cat-nombre { flex: 1; font-weight: 600; font-size: 0.9rem; color: #333; }
.cat-count  { font-size: 0.78rem; color: #bbb; flex-shrink: 0; }

/* ── Edición categoría inline ── */
.cat-edit-form { display: flex; align-items: center; gap: 8px; width: 100%; }
.input-nombre { flex: 1; padding: 7px 10px; border: 1.5px solid #e0e0e0; border-radius: 7px; font-size: 0.9rem; outline: none; }
.input-nombre:focus { border-color: #FF6B35; }

/* ─── Temas ─── */
.helper-text { font-size: 0.85rem; color: #999; margin-bottom: 14px; }
.temas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; }
.tema-card {
  border: 2px solid #e0e0e0; border-radius: 12px; overflow: hidden;
  cursor: pointer; transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
}
.tema-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
.tema-card.selected { box-shadow: 0 4px 16px rgba(0,0,0,0.15); }

.tema-mockup { padding: 12px; display: flex; flex-direction: column; gap: 7px; }
.mock-title { font-size: 0.7rem; font-weight: 700; }
.mock-card { border-radius: 6px; padding: 7px; display: flex; gap: 6px; align-items: center; }
.mock-img  { width: 26px; height: 26px; border-radius: 4px; flex-shrink: 0; }
.mock-info { flex: 1; display: flex; flex-direction: column; gap: 4px; }
.mock-name { height: 7px; border-radius: 3px; }
.mock-price { font-size: 0.7rem; font-weight: 800; }

.tema-label {
  padding: 8px 12px; display: flex; flex-direction: column; gap: 2px;
  background: rgba(255,255,255,0.12); border-top: 1px solid rgba(0,0,0,0.05);
}
.tema-label strong { font-size: 0.85rem; }
.tema-label span   { font-size: 0.72rem; opacity: 0.65; }
.tema-activo { color: #2e7d32 !important; font-weight: 700 !important; opacity: 1 !important; }

/* ─── QR ─── */
.qr-section { display: flex; flex-direction: column; gap: 16px; align-items: flex-start; }
.qr-url-box { display: flex; gap: 8px; align-items: center; background: #f5f5f5; border-radius: 8px; padding: 10px 14px; width: 100%; box-sizing: border-box; }
.qr-url-text { font-size: 0.78rem; color: #555; flex: 1; word-break: break-all; font-family: monospace; }
.btn-copy { background: #fff; border: 1.5px solid #ddd; color: #555; padding: 5px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer; white-space: nowrap; transition: all 0.2s; flex-shrink: 0; }
.btn-copy:hover { border-color: #FF6B35; color: #FF6B35; }

.qr-preview-wrap { position: relative; }
.qr-canvas { display: block; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,0.1); }
.qr-loading {
  position: absolute; inset: 0; background: #f5f5f5; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
}

.btn-download-qr {
  display: inline-flex; align-items: center; gap: 8px;
  background: #2e7d32; color: #fff; text-decoration: none;
  padding: 11px 20px; border-radius: 8px; font-size: 0.9rem; font-weight: 700;
  transition: background 0.2s;
}
.btn-download-qr:hover { background: #1b5e20; }

/* ─── Preview foto ─── */
.preview-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.8);
  display: flex; align-items: center; justify-content: center;
  z-index: 2000; padding: 20px; cursor: pointer;
}
.preview-box {
  position: relative; background: #fff; border-radius: 16px;
  overflow: hidden; max-width: 480px; width: 100%; cursor: default;
}
.preview-close {
  position: absolute; top: 12px; right: 12px; width: 34px; height: 34px;
  border-radius: 50%; background: rgba(0,0,0,0.15); border: none;
  cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; z-index: 1;
}
.preview-img { width: 100%; display: block; max-height: 70vh; object-fit: contain; }
.preview-nombre { padding: 12px 16px; font-weight: 700; font-size: 0.95rem; color: #333; margin: 0; text-align: center; }

/* ─── Notificación ─── */
.notif {
  position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
  padding: 12px 24px; border-radius: 10px; font-size: 0.88rem; font-weight: 600;
  box-shadow: 0 6px 24px rgba(0,0,0,0.2); z-index: 999; white-space: nowrap;
}
.notif-ok    { background: #1b5e20; color: #fff; }
.notif-error { background: #b71c1c; color: #fff; }

.notif-anim-enter-active, .notif-anim-leave-active { transition: opacity 0.3s, transform 0.3s; }
.notif-anim-enter-from { opacity: 0; transform: translateX(-50%) translateY(10px); }
.notif-anim-leave-to   { opacity: 0; transform: translateX(-50%) translateY(10px); }

/* ─── Emoji picker ─── */
.emoji-wrap {
  position: relative;
  display: inline-block;
}

.emoji-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 9px 12px;
  border: 1.5px solid #e0e0e0;
  border-radius: 8px;
  background: #fafafa;
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s;
  white-space: nowrap;
}

.emoji-btn:hover {
  border-color: #FF6B35;
  background: #fff;
}

.emoji-btn-sm {
  padding: 6px 10px;
}

.emoji-display { font-size: 1.2rem; line-height: 1; }
.picker-caret  { font-size: 0.65rem; color: #bbb; }

.emoji-picker {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  z-index: 300;
  background: #fff;
  border: 1px solid #e8e8e8;
  border-radius: 14px;
  box-shadow: 0 10px 36px rgba(0,0,0,0.16);
  padding: 14px;
  width: 284px;
  max-height: 360px;
  overflow-y: auto;
  scrollbar-width: thin;
}

/* En el edit inline, abrir hacia la derecha del botón */
.emoji-picker-right {
  left: 0;
  right: auto;
}

.emoji-grupo {
  margin-bottom: 12px;
}
.emoji-grupo:last-child {
  margin-bottom: 0;
}

.emoji-grupo-titulo {
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.7px;
  color: #bbb;
  margin-bottom: 6px;
  padding-left: 2px;
}

.emoji-grid {
  display: grid;
  grid-template-columns: repeat(9, 1fr);
  gap: 1px;
}

.emoji-opt {
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 1.05rem;
  border-radius: 6px;
  transition: background 0.12s, transform 0.12s;
  padding: 0;
}

.emoji-opt:hover {
  background: #f0f0f0;
  transform: scale(1.25);
}

.emoji-opt.selected {
  background: #fff3e0;
  outline: 2px solid #FF6B35;
}

/* ─── Responsive ─── */
@media (max-width: 600px) {
  .panel-header { padding: 0 14px; }
  .header-title { font-size: 1rem; }
  .tab-btn .tab-label { display: none; }
  .tab-icon { font-size: 1.3rem; }
  .form-grid { grid-template-columns: 1fr; }
  .temas-grid { grid-template-columns: 1fr 1fr; }
  .prod-badges { display: none; }
}
</style>
