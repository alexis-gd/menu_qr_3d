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

    <!-- Modal QR -->
    <div v-if="qrModal" class="qr-overlay" @click.self="qrModal = null">
      <div class="qr-box">
        <h3>Mesa {{ qrModal.numero }}</h3>
        <p class="qr-url">{{ qrModal.url }}</p>
        <img :src="qrModal.dataUrl" alt="Código QR" class="qr-img" />
        <div class="qr-acciones">
          <a
            :href="qrModal.dataUrl"
            :download="`qr-mesa-${qrModal.numero}.png`"
            class="btn-download"
          >Descargar PNG</a>
          <button @click="qrModal = null" class="btn-cerrar">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useApi } from '../../composables/useApi.js'
import QRCode from 'qrcode'

const route = useRoute()
const restauranteId = route.params.id
const { get, post, del } = useApi()

const mesas = ref([])
const restauranteNombre = ref('')
const restauranteSlug = ref('')
const nuevaNumero = ref('')
const error = ref(null)
const loading = ref(false)
const qrModal = ref(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    const res = await get('mesas', { restaurante_id: restauranteId })
    mesas.value = res.mesas || []
    restauranteNombre.value = res.restaurante_nombre || ''
    restauranteSlug.value = res.restaurante_slug || ''
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
  const dataUrl = await QRCode.toDataURL(url, { width: 300, margin: 2 })
  qrModal.value = { numero: mesa.numero, url, dataUrl }
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

/* Modal QR */
.qr-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.55);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000;
}
.qr-box {
  background: white; border-radius: 16px;
  padding: 32px; text-align: center;
  max-width: 380px; width: 90%;
}
.qr-box h3 { margin: 0 0 8px; font-size: 1.3rem }
.qr-url { font-size: 0.75rem; color: #888; word-break: break-all; margin-bottom: 16px }
.qr-img { width: 300px; height: 300px; display: block; margin: 0 auto 20px }
.qr-acciones { display: flex; gap: 10px; justify-content: center }
.btn-download {
  background: #4caf50; color: white;
  padding: 10px 20px; border-radius: 8px;
  text-decoration: none; font-size: 0.95rem
}
.btn-cerrar {
  background: #eee; border: none;
  padding: 10px 20px; border-radius: 8px;
  cursor: pointer; font-size: 0.95rem
}
</style>
