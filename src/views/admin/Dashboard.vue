<template>
  <div class="admin-panel" :class="{ 'tema-oscuro-admin': temaAdmin === 'oscuro' }" :style="{ '--accent': temaAccent }">
    <!-- ═══ Header ═══ -->
    <header class="panel-header">
      <div class="header-left">
        <img v-if="restaurante?.logo_url" :src="restaurante.logo_url" class="header-logo-img" alt="logo" />
        <span v-else class="header-icon">
          <SvgIcon :path="mdiSilverwareForkKnife" :size="28" />
        </span>
        <div>
          <h1 class="header-title">{{ restaurante?.nombre || 'Mi Restaurante' }}</h1>
          <span class="header-sub">Panel de administración</span>
        </div>
      </div>
      <button @click="logout" class="btn-logout"><SvgIcon :path="mdiLogout" :size="16" /> Salir</button>
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
      <!-- ── Aviso trial próximo a vencer (no bloqueante) ── -->
      <div v-if="!trialVencido && trialDiasAdmin !== null && trialDiasAdmin <= 2" class="trial-aviso">
        ⏳ Tu período de prueba vence {{ trialDiasAdmin === 0 ? 'hoy' : trialDiasAdmin === 1 ? 'mañana' : `en ${trialDiasAdmin} días` }}.
        <a :href="`https://wa.me/${VENTAS_WA}?text=${encodeURIComponent('Hola, quiero contratar el menú digital.')}`" target="_blank" rel="noopener noreferrer">Contratar ahora →</a>
      </div>

      <!-- ═══ Tabs ═══ -->
      <nav class="tab-nav">
        <button
          v-for="tab in tabs" :key="tab.id"
          :class="['tab-btn', { active: tabActivo === tab.id }]"
          @click="tabActivo = tab.id"
        >
          <span class="tab-icon"><SvgIcon :path="tab.icon" :size="18" /></span>
          <span class="tab-label">{{ tab.label }}</span>
        </button>
      </nav>

      <!-- Notificación flotante (global para todos los tabs) -->
      <transition name="notif-anim">
        <div v-if="notif" :class="['notif', `notif-${notif.tipo}`]">{{ notif.texto }}</div>
      </transition>

      <!-- ═══ Tab Components ═══ -->
      <TabPlatillos
        v-show="tabActivo === 'menu'"
        :restaurante-id="restauranteId"
        :categorias="categorias"
        :accent="temaAccent"
        @notif="mostrarNotif"
        @categorias-changed="loadCategorias"
      />

      <TabApariencia
        ref="tabAparienciaRef"
        v-show="tabActivo === 'apariencia'"
        :restaurante-id="restauranteId"
        :restaurante="restaurante"
        :menu-url="menuUrl"
        :active="tabActivo === 'apariencia'"
        @notif="mostrarNotif"
        @restaurante-updated="onRestauranteUpdated"
        @tema-preview="temaPreview = $event"
      />

      <TabNegocio
        ref="tabNegocioRef"
        v-show="tabActivo === 'negocio'"
        :restaurante-id="restauranteId"
        :restaurante="restaurante"
        :menu-url="menuUrl"
        @notif="mostrarNotif"
        @restaurante-updated="onRestauranteUpdated"
      />

      <TabPedidos
        v-show="tabActivo === 'pedidos'"
        :restaurante-id="restauranteId"
        :active="tabActivo === 'pedidos'"
        @notif="mostrarNotif"
      />

      <!-- Botón guardar global (solo en tabs que lo necesitan) -->
      <div v-if="tabActivo === 'apariencia' || tabActivo === 'negocio'" class="sticky-save-bar">
        <button class="btn-primary btn-guardar-global" @click="guardarTabActivo" :disabled="guardandoTab">
          <SvgIcon :path="mdiContentSave" :size="17" />
          {{ guardandoTab ? 'Guardando...' : 'Guardar cambios' }}
        </button>
      </div>
    </div>
  </div>

  <!-- ── Overlay: trial vencido (bloqueante) ── -->
  <Teleport to="body">
    <div v-if="trialVencido" class="trial-overlay">
      <div class="trial-overlay__card">
        <div class="trial-overlay__icon">🔒</div>
        <h2 class="trial-overlay__titulo">Período de prueba vencido</h2>
        <p class="trial-overlay__texto">
          Tu acceso de prueba ha expirado. Contáctanos para activar tu cuenta y seguir usando el menú digital.
        </p>
        <a
          :href="`https://wa.me/${VENTAS_WA}?text=${encodeURIComponent('Hola, quiero contratar el menú digital. Mi restaurante es: ' + (restaurante?.nombre || ''))}`"
          target="_blank"
          rel="noopener noreferrer"
          class="trial-overlay__btn"
        >
          📲 Contratar por WhatsApp
        </a>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { mdiSilverwareForkKnife, mdiPalette, mdiCog, mdiCart, mdiLogout, mdiContentSave } from '@mdi/js'
import { resetAuth } from '../../router/index.js'
import { useApi } from '../../composables/useApi.js'
import { THEMES as temas, THEMES_EXTRA as TEMAS_EXTRA } from '../../utils/themes.js'
import SvgIcon       from '../../components/SvgIcon.vue'
import TabPlatillos  from '../../components/admin/tabs/TabPlatillos.vue'
import TabApariencia from '../../components/admin/tabs/TabApariencia.vue'
import TabNegocio    from '../../components/admin/tabs/TabNegocio.vue'
import TabPedidos    from '../../components/admin/tabs/TabPedidos.vue'

const router = useRouter()
const { get, post } = useApi()

// ── Trial (demos) ──
const trialVencido = computed(() => {
  const exp = restaurante.value?.trial_expires_at
  if (!exp) return false
  return new Date(exp) <= new Date()
})
const trialDiasAdmin = computed(() => {
  const exp = restaurante.value?.trial_expires_at
  if (!exp) return null
  return Math.max(0, Math.ceil((new Date(exp) - new Date()) / 86400000))
})
const VENTAS_WA = '529231311146'

// ── Estado compartido ──
const restaurante     = ref(null)
const restauranteId   = ref(null)
const categorias      = ref([])
const cargandoInicial = ref(true)
const errorInicial    = ref(null)
const notif           = ref(null)
const VALID_TABS = ['menu', 'apariencia', 'negocio', 'pedidos']
const _savedTab  = localStorage.getItem('dashboard_tab')
const tabActivo  = ref(VALID_TABS.includes(_savedTab) ? _savedTab : 'menu')
watch(tabActivo, val => localStorage.setItem('dashboard_tab', val))
const temaPreview       = ref(null)
const tabAparienciaRef  = ref(null)
const tabNegocioRef     = ref(null)

const guardandoTab = computed(() => {
  if (tabActivo.value === 'apariencia') return tabAparienciaRef.value?.guardando ?? false
  if (tabActivo.value === 'negocio')    return tabNegocioRef.value?.guardando    ?? false
  return false
})

function guardarTabActivo() {
  if (tabActivo.value === 'apariencia') tabAparienciaRef.value?.guardar()
  if (tabActivo.value === 'negocio')    tabNegocioRef.value?.guardar()
}

const tabs = [
  { id: 'menu',       icon: mdiSilverwareForkKnife,   label: 'Menú'       },
  { id: 'apariencia', icon: mdiPalette,                label: 'Apariencia' },
  { id: 'negocio',    icon: mdiCog,                   label: 'Negocio'    },
  { id: 'pedidos',    icon: mdiCart,                  label: 'Pedidos'    },
]

// Tema para el wrapper (live preview en Apariencia, valor guardado en el resto)
const temaAdmin = computed(() => temaPreview.value || restaurante.value?.tema || 'calido')
const temaAccent = computed(() => {
  const t = temas.find(t => t.id === temaAdmin.value) || temas[0]
  const extra = TEMAS_EXTRA[t.id] || TEMAS_EXTRA.calido
  return extra.accent || t.accent
})

const menuUrl = computed(() => {
  if (!restaurante.value?.slug) return ''
  const origin = import.meta.env.VITE_PUBLIC_ORIGIN || window.location.origin
  const base   = import.meta.env.BASE_URL
  return `${origin}${base}?r=${restaurante.value.slug}`
})

const mostrarNotif = ({ texto, tipo = 'ok' }) => {
  notif.value = { texto, tipo }
  setTimeout(() => { notif.value = null }, 3000)
}

const logout = async () => {
  try {
    await post('logout', {})
  } catch {
    // si falla el request igual limpiamos estado local y redirigimos
  }
  resetAuth()
  router.push('/admin')
}

async function loadCategorias() {
  const res = await get('categorias', { restaurante_id: restauranteId.value })
  categorias.value = res.categorias || []
}

const onRestauranteUpdated = (data) => {
  restaurante.value = { ...restaurante.value, ...data }
  // Si se actualizó el tema, limpiar el preview
  if (data.tema) temaPreview.value = null
}

// ── Favicon dinámico ──
watch(() => restaurante.value?.logo_url, (url) => {
  if (!url) return
  let link = document.querySelector("link[rel~='icon']")
  if (!link) { link = document.createElement('link'); link.rel = 'icon'; document.head.appendChild(link) }
  link.href = url
}, { immediate: true })

onMounted(async () => {
  try {
    const res = await get('restaurantes')
    const lista = res.restaurantes || []
    if (!lista.length) { errorInicial.value = 'No hay restaurante configurado.'; return }
    const rest = lista[0]
    restaurante.value  = rest
    restauranteId.value = rest.id
    await loadCategorias()
  } catch (err) {
    errorInicial.value = 'Error al conectar: ' + err.message
  } finally {
    cargandoInicial.value = false
  }
})
</script>

<style scoped>
/* ─── Trial ─── */
.trial-aviso {
  background: #f39c12;
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  padding: 10px 20px;
  display: flex;
  gap: 12px;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
}
.trial-aviso a { color: #fff; text-decoration: underline; }

.trial-overlay {
  position: fixed;
  inset: 0;
  z-index: 9000;
  background: rgba(0,0,0,0.75);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.trial-overlay__card {
  background: #fff;
  border-radius: 16px;
  padding: 40px 32px;
  max-width: 420px;
  width: 100%;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.trial-overlay__icon { font-size: 48px; margin-bottom: 12px; }
.trial-overlay__titulo { font-size: 22px; font-weight: 700; color: #1a1a1a; margin: 0 0 12px; }
.trial-overlay__texto { color: #555; line-height: 1.6; margin: 0 0 24px; }
.trial-overlay__btn {
  display: inline-block;
  background: #25d366;
  color: #fff;
  text-decoration: none;
  padding: 14px 28px;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 700;
  transition: background 0.2s;
}
.trial-overlay__btn:hover { background: #1ebe5d; }

/* ─── Base ─── */
.admin-panel {
  --accent: #FF6B35;
  min-height: 100vh;
  background: #f0f2f5;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* ─── Header ─── */
.panel-header {
  background: #fff; border-bottom: 1px solid #e8e8e8;
  padding: 0 24px; height: 64px;
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 100;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
.header-left     { display: flex; align-items: center; gap: 12px; }
.header-icon     { font-size: 1.8rem; }
.header-logo-img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #f0f0f0; }
.header-title    { font-size: 1.1rem; font-weight: 700; color: #1a1a1a; margin: 0; }
.header-sub      { font-size: 0.75rem; color: #aaa; }
.btn-logout {
  display: inline-flex; align-items: center; gap: 6px;
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
  border-radius: 50%; animation: spin-lg 0.7s linear infinite;
}
@keyframes spin-lg { to { transform: rotate(360deg); } }

/* ─── Body ─── */
.panel-body { max-width: 900px; margin: 0 auto; padding: 20px 16px 80px; }

/* ─── Tabs nav ─── */
.tab-nav {
  display: flex; gap: 4px; background: #fff; border-radius: 14px;
  padding: 6px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); margin-bottom: 20px;
  position: sticky; top: 64px; z-index: 90;
}
.tab-btn {
  flex: 1; display: flex; align-items: center; justify-content: center;
  gap: 7px; padding: 10px; border: none; border-radius: 10px;
  background: transparent; color: #999; font-size: 0.88rem;
  font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.tab-btn:hover  { background: #f5f5f5; color: #333; }
.tab-btn.active { background: var(--accent); color: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
.tab-icon { font-size: 1.05rem; }

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

/* ─── Tema oscuro ─── */
.tema-oscuro-admin .tab-btn.active {
  background: #1e1e48; color: #f0c040;
  border: 1px solid #f0c040;
  box-shadow: 0 2px 8px rgba(240,192,64,0.2);
}

/* ─── Responsive ─── */
@media (max-width: 600px) {
  .panel-header { padding: 0 14px; }
  .header-title { font-size: 1rem; }
  .tab-btn .tab-label { display: none; }
  .tab-icon { font-size: 1.3rem; }
}
</style>
