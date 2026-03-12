<template>
  <div class="menu-publico" :class="`tema-${tema}`">
    <!-- Loading -->
    <div v-if="loading" class="full-loading">
      <div class="loading-spinner"></div>
      <p>Cargando menú...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="full-error">
      <span>😕</span>
      <p>{{ error }}</p>
    </div>

    <template v-else>
      <!-- ── Header del restaurante ── -->
      <header class="menu-header">
        <div class="header-inner">
          <div class="header-info">
            <img
              v-if="restaurante?.logo_url"
              :src="restaurante.logo_url"
              :alt="restaurante.nombre"
              class="header-logo"
            />
            <!-- <h1 class="rest-nombre">{{ restaurante?.nombre }}</h1> -->
            <p v-if="restaurante?.descripcion" class="rest-desc">{{ restaurante.descripcion }}</p>
          </div>
          <div v-if="mesaNumero" class="mesa-chip">
            <span>🪑</span> Mesa {{ mesaNumero }}
          </div>
        </div>
      </header>

      <!-- ── Navegación de categorías (sticky) ── -->
      <nav class="cat-nav">
        <button
          v-for="cat in categorias"
          :key="cat.id"
          :class="['cat-nav-btn', { active: catActiva === cat.id }]"
          @click="irACategoria(cat.id)"
        >
          <span v-if="cat.icono" class="cat-nav-icon">{{ cat.icono }}</span>
          {{ cat.nombre }}
        </button>
      </nav>

      <!-- ── Contenido del menú ── -->
      <main class="menu-contenido">
        <section
          v-for="cat in categorias"
          :key="cat.id"
          :id="`cat-${cat.id}`"
          class="categoria-seccion"
        >
          <div class="cat-titulo">
            <span v-if="cat.icono" class="cat-icono">{{ cat.icono }}</span>
            <h2>{{ cat.nombre }}</h2>
            <div class="cat-linea"></div>
          </div>

          <div class="productos-lista">
            <ProductoCard
              v-for="prod in cat.productos"
              :key="prod.id"
              :producto="prod"
              :pedidos-activos="pedidosActivos"
              @click="abrirModal(prod)"
              @agregar="agregarAlCarrito(prod, '')"
            />
          </div>
        </section>
      </main>

      <!-- ── Footer ── -->
      <footer class="menu-footer">
        <p>Menú digital con tecnología 3D • Toca cualquier platillo para ver más</p>
      </footer>
    </template>

    <!-- Modal de producto -->
    <ProductoModal
      v-if="productoSeleccionado"
      :producto="productoSeleccionado"
      :pedidos-activos="pedidosActivos"
      @close="productoSeleccionado = null"
      @agregar="({ producto, observacion }) => { agregarAlCarrito(producto, observacion); productoSeleccionado = null }"
    />

    <!-- Toast "agregado al carrito" -->
    <transition name="toast-anim">
      <div v-if="toastNombre" class="carrito-toast">
        ✓ {{ toastNombre }} agregado
      </div>
    </transition>

    <!-- Carrito flotante -->
    <CarritoFlotante
      v-if="pedidosActivos && carrito.length"
      :carrito="carrito"
      @abrir="mostrarCheckout = true"
    />

    <!-- Checkout -->
    <CheckoutModal
      v-show="mostrarCheckout"
      :carrito="carrito"
      :pedidos-config="pedidosConfig"
      :mesa="mesaNumero"
      :restaurante-id="restaurante?.id"
      @close="mostrarCheckout = false"
      @confirmado="onPedidoConfirmado"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useApi } from '../composables/useApi.js'
import ProductoCard from '../components/ProductoCard.vue'
import ProductoModal from '../components/ProductoModal.vue'
import CarritoFlotante from '../components/CarritoFlotante.vue'
import CheckoutModal from '../components/CheckoutModal.vue'

const route = useRoute()
const { get, loading, error } = useApi()

const restaurante = ref(null)
const categorias = ref([])
const productoSeleccionado = ref(null)
const catActiva = ref(null)
const mesaNumero = route.query.mesa || null

// ── Carrito ──
const carrito = ref([])
const mostrarCheckout = ref(false)
const toastNombre = ref('')
let toastTimer = null

const pedidosActivos = computed(() => !!restaurante.value?.pedidos_activos)
const pedidosConfig  = computed(() => restaurante.value || {})

const tema = computed(() => restaurante.value?.tema || 'calido')

const agregarAlCarrito = (producto, observacion = '') => {
  const existente = carrito.value.find(i => i.producto.id === producto.id && i.observacion === observacion)
  if (existente) {
    existente.cantidad++
  } else {
    carrito.value.push({ producto, cantidad: 1, observacion })
  }
  // Toast de confirmación
  toastNombre.value = producto.nombre
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toastNombre.value = '' }, 1800)
}

const onPedidoConfirmado = () => {
  carrito.value = []
  mostrarCheckout.value = false
}

onMounted(async () => {
  const slug = route.query.r
  if (!slug) {
    error.value = 'No se especificó el restaurante. Escanea el código QR de tu mesa.'
    return
  }
  try {
    const data = await get('menu', { restaurante: slug }, false)
    restaurante.value = data.restaurante
    categorias.value = data.categorias || []
    if (categorias.value.length) {
      catActiva.value = categorias.value[0].id
    }
    initObserver()
  } catch (err) {
    console.error('Error cargando menú:', err)
  }
})

// IntersectionObserver para destacar la categoría visible
let observer = null
const initObserver = () => {
  observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          const id = parseInt(entry.target.dataset.catId)
          if (id) catActiva.value = id
        }
      }
    },
    { rootMargin: '-20% 0px -60% 0px', threshold: 0 }
  )
  setTimeout(() => {
    categorias.value.forEach(cat => {
      const el = document.getElementById(`cat-${cat.id}`)
      if (el) {
        el.dataset.catId = cat.id
        observer.observe(el)
      }
    })
  }, 200)
}

onUnmounted(() => {
  if (observer) observer.disconnect()
})

const irACategoria = (catId) => {
  catActiva.value = catId
  const el = document.getElementById(`cat-${catId}`)
  if (el) {
    const offset = 120 // header + cat-nav height
    const top = el.getBoundingClientRect().top + window.scrollY - offset
    window.scrollTo({ top, behavior: 'smooth' })
  }
}

const abrirModal = (producto) => {
  productoSeleccionado.value = producto
}
</script>

<style scoped>
/* ═══════════════════════════════════════
   VARIABLES POR TEMA
═══════════════════════════════════════ */

/* Tema: Cálido (default) */
.tema-calido {
  --header-bg: linear-gradient(145deg, #b5451b 0%, #d4691e 50%, #e8841f 100%);
  --header-text: #fff;
  --header-sub: rgba(255,255,255,0.82);
  --page-bg: #fdf6f0;
  --card-bg: #ffffff;
  --card-border: #f0e0d0;
  --text-main: #3d2c1e;
  --text-sub: #9d7355;
  --accent: #d4691e;
  --accent-light: #fff0e6;
  --cat-nav-bg: #fff;
  --cat-nav-shadow: 0 2px 8px rgba(180,80,30,0.08);
  --cat-nav-text: #7d5238;
  --cat-nav-active-bg: #d4691e;
  --cat-nav-active-text: #fff;
  --section-title: #3d2c1e;
  --divider: #e8c9a8;
  --footer-bg: #f5ebe0;
  --footer-text: #b08060;
  --mesa-bg: rgba(255,255,255,0.22);
  --mesa-text: #fff;
}

/* Tema: Oscuro (elegante) */
.tema-oscuro {
  --header-bg: linear-gradient(145deg, #0a0a1a 0%, #111128 50%, #1a1a38 100%);
  --header-text: #f0c040;
  --header-sub: rgba(240,192,64,0.65);
  --page-bg: #0e0e1e;
  --card-bg: #1a1a2e;
  --card-border: #252545;
  --text-main: #e8e8f0;
  --text-sub: #8888aa;
  --accent: #f0c040;
  --accent-light: rgba(255,255,255,0.05);
  --cat-nav-bg: #14142a;
  --cat-nav-shadow: 0 2px 12px rgba(0,0,0,0.4);
  --cat-nav-text: #9090b8;
  --cat-nav-active-bg: #f0c040;
  --cat-nav-active-text: #1a1a2e;
  --section-title: #e8e8f0;
  --divider: #252545;
  --footer-bg: #0a0a1a;
  --footer-text: #44445a;
  --mesa-bg: rgba(240,192,64,0.18);
  --mesa-text: #f0c040;
}

/* Botones de acción: fondo oscuro + borde dorado + texto dorado */
.tema-oscuro :deep(.btn-ver),
.tema-oscuro :deep(.btn-agregar),
.tema-oscuro :deep(.btn-agregar-carrito),
.tema-oscuro :deep(.btn-confirmar),
.tema-oscuro :deep(.carrito-flotante) {
  background: #1e1e48;
  color: #f0c040;
  border: 1.5px solid #f0c040;
}
.tema-oscuro :deep(.btn-ver:hover),
.tema-oscuro :deep(.btn-agregar:hover),
.tema-oscuro :deep(.btn-agregar-carrito:hover),
.tema-oscuro :deep(.btn-confirmar:hover:not(:disabled)),
.tema-oscuro :deep(.carrito-flotante:hover) {
  background: rgba(240,192,64,0.15);
}

/* Badges decorativos: fondo sutil dorado + texto dorado */
.tema-oscuro :deep(.badge-3d),
.tema-oscuro :deep(.pill-3d),
.tema-oscuro :deep(.ar-btn) {
  background: rgba(240,192,64,0.18);
  color: #f0c040;
  border: 1px solid rgba(240,192,64,0.4);
}

/* Tema: Moderno (minimalista) */
.tema-moderno {
  --header-bg: #ffffff;
  --header-text: #111111;
  --header-sub: #777777;
  --page-bg: #f2f4f6;
  --card-bg: #ffffff;
  --card-border: #e8eaed;
  --text-main: #111111;
  --text-sub: #666666;
  --accent: #1a7f5a;
  --accent-light: #eaf5f0;
  --cat-nav-bg: #ffffff;
  --cat-nav-shadow: 0 1px 0 #e0e0e0;
  --cat-nav-text: #555555;
  --cat-nav-active-bg: #1a7f5a;
  --cat-nav-active-text: #fff;
  --section-title: #111111;
  --divider: #e0e0e0;
  --footer-bg: #ffffff;
  --footer-text: #aaaaaa;
  --mesa-bg: #f0f0f0;
  --mesa-text: #333;
}

/* Tema: Express (comida rápida) */
.tema-rapida {
  --header-bg: linear-gradient(145deg, #c0392b 0%, #d43f2e 50%, #e74c3c 100%);
  --header-text: #ffffff;
  --header-sub: rgba(255,255,255,0.82);
  --page-bg: #fff8f0;
  --card-bg: #ffffff;
  --card-border: #ffe0cc;
  --text-main: #1a1a1a;
  --text-sub: #666666;
  --accent: #d43f2e;
  --accent-light: #fff0ee;
  --cat-nav-bg: #ffffff;
  --cat-nav-shadow: 0 2px 6px rgba(200,60,50,0.12);
  --cat-nav-text: #555555;
  --cat-nav-active-bg: #d43f2e;
  --cat-nav-active-text: #fff;
  --section-title: #1a1a1a;
  --divider: #f0d0c8;
  --footer-bg: #fff;
  --footer-text: #bbb;
  --mesa-bg: rgba(255,255,255,0.25);
  --mesa-text: #fff;
}

/* Tema: Rosa (romántico, suave) */
.tema-rosa {
  --header-bg: linear-gradient(145deg, #FF8276 0%, #ea8c84 50%, #EA9087 100%);
  --header-text: #ffffff;
  --header-sub: rgba(255,255,255,0.82);
  --page-bg: #FFEFEF;
  --card-bg: #ffffff;
  --card-border: #ffd0cc;
  --text-main: #5a2030;
  --text-sub: #a06070;
  --accent: #FF8276;
  --accent-light: #ffe8e6;
  --cat-nav-bg: #ffffff;
  --cat-nav-shadow: 0 2px 8px rgba(255,130,118,0.12);
  --cat-nav-text: #a06070;
  --cat-nav-active-bg: #FF8276;
  --cat-nav-active-text: #fff;
  --section-title: #5a2030;
  --divider: #ffd0cc;
  --footer-bg: #fff;
  --footer-text: #d0a0a0;
  --mesa-bg: rgba(255,255,255,0.25);
  --mesa-text: #fff;
}

/* ═══════════════════════════════════════
   ESTILOS GENERALES
═══════════════════════════════════════ */

.menu-publico {
  width: 100%;
  min-height: 100vh;
  background: var(--page-bg);
  transition: background 0.3s;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* ── Loading / Error ── */
.full-loading,
.full-error {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  gap: 16px;
  color: #888;
}

.full-error span {
  font-size: 3rem;
}

.loading-spinner {
  width: 44px;
  height: 44px;
  border: 3px solid rgba(0,0,0,0.08);
  border-top-color: var(--accent, #FF6B35);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ── Header ── */
.menu-header {
  background: var(--header-bg);
  color: var(--header-text);
  padding: 32px 20px 28px;
  position: relative;
  overflow: hidden;
}

.header-inner {
  max-width: 680px;
  margin: 0 auto;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  position: relative;
  z-index: 1;
}

.header-info {
  flex: 1;
  text-align: center;
}

.header-logo {
  display: block;
  width: 130px;
  height: 130px;
  border-radius: 50%;
  object-fit: cover;
  margin: 0 auto 14px;
  border: 3px solid rgba(255,255,255,0.6);
  box-shadow: 0 4px 16px rgba(0,0,0,0.18);
}

.rest-nombre {
  font-size: 1.9rem;
  font-weight: 800;
  line-height: 1.1;
  margin-bottom: 6px;
  letter-spacing: -0.5px;
}

.rest-desc {
  font-size: 0.92rem;
  color: var(--header-sub);
  line-height: 1.4;
  max-width: 380px;
  display: inline;
}

.mesa-chip {
  background: var(--mesa-bg);
  color: var(--mesa-text);
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 700;
  white-space: nowrap;
  backdrop-filter: blur(4px);
  border: 1px solid rgba(255,255,255,0.2);
  flex-shrink: 0;
}

/* ── Navegación categorías ── */
.cat-nav {
  position: sticky;
  top: 0;
  z-index: 50;
  background: var(--cat-nav-bg);
  box-shadow: var(--cat-nav-shadow);
  padding: 10px 16px;
  display: flex;
  gap: 8px;
  overflow-x: auto;
  scrollbar-width: none;
  -webkit-overflow-scrolling: touch;
}

.cat-nav::-webkit-scrollbar { display: none; }

.cat-nav-btn {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 8px 16px;
  border: none;
  border-radius: 20px;
  background: transparent;
  color: var(--cat-nav-text);
  font-size: 0.87rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s;
  border: 1.5px solid transparent;
}

.cat-nav-btn:hover {
  background: var(--accent-light);
  color: var(--accent);
}

.cat-nav-btn.active {
  background: var(--cat-nav-active-bg);
  color: var(--cat-nav-active-text);
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.cat-nav-icon {
  font-size: 1rem;
}

/* ── Contenido ── */
.menu-contenido {
  max-width: 720px;
  margin: 0 auto;
  padding: 16px 16px 32px;
}

/* ── Sección de categoría ── */
.categoria-seccion {
  margin-bottom: 36px;
  scroll-margin-top: 100px;
}

.cat-titulo {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
}

.cat-icono {
  font-size: 1.4rem;
}

.cat-titulo h2 {
  font-size: 1.2rem;
  font-weight: 800;
  color: var(--section-title);
  letter-spacing: -0.2px;
  flex-shrink: 0;
}

.cat-linea {
  flex: 1;
  height: 1px;
  background: var(--divider);
  margin-left: 4px;
}

/* ── Lista de productos ── */
.productos-lista {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

@media (min-width: 768px) {
  .productos-lista {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
  }
}

/* ── Footer ── */
.menu-footer {
  background: var(--footer-bg);
  border-top: 1px solid var(--divider);
  text-align: center;
  padding: 20px;
  color: var(--footer-text);
  font-size: 0.78rem;
}

/* ── Responsive ── */
@media (min-width: 600px) {
  .rest-nombre {
    font-size: 2.4rem;
  }

  .menu-contenido {
    padding: 24px 24px 40px;
  }
}

@media (min-width: 768px) {
  .menu-contenido {
    max-width: 1100px;
    padding: 24px 32px 48px;
  }
}

/* ── Toast carrito ── */
.carrito-toast {
  position: fixed;
  bottom: 90px;
  left: 50%;
  transform: translateX(-50%);
  background: var(--accent, #FF6B35);
  color: #fff;
  padding: 10px 20px;
  border-radius: 999px;
  font-size: 0.88rem;
  font-weight: 700;
  white-space: nowrap;
  z-index: 500;
  pointer-events: none;
  box-shadow: 0 4px 16px rgba(0,0,0,0.18);
}

.toast-anim-enter-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.toast-anim-leave-active { transition: opacity 0.3s ease, transform 0.3s ease; }
.toast-anim-enter-from   { opacity: 0; transform: translateX(-50%) translateY(10px); }
.toast-anim-leave-to     { opacity: 0; transform: translateX(-50%) translateY(10px); }
</style>
