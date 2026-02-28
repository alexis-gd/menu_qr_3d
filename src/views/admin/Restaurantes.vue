<template>
  <div class="admin-restaurantes">
    <h1>Restaurantes</h1>
    <div class="nuevo">
      <input v-model="form.nombre" placeholder="Nombre" />
      <input v-model="form.slug" placeholder="Slug" />
      <input v-model="form.descripcion" placeholder="Descripción" />
      <button @click="crear">Crear</button>
    </div>

    <div class="lista">
      <div v-for="r in restaurantes" :key="r.id" class="item">
        <div>
          <strong>{{ r.nombre }}</strong>
          <div class="meta">/{{ r.slug }} — {{ r.descripcion }}</div>
        </div>
      </div>
    </div>

    <div v-if="error" class="error">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '../../composables/useApi.js'

const restaurantes = ref([])
const error = ref(null)
const form = ref({ nombre: '', slug: '', descripcion: '' })
const { get, post } = useApi()

async function load() {
  error.value = null
  try {
    const res = await get('restaurantes')
    restaurantes.value = res.restaurantes || []
  } catch (err) {
    error.value = err.message || 'Error cargando restaurantes'
  }
}

async function crear() {
  error.value = null
  if (!form.value.nombre || !form.value.slug) {
    error.value = 'Nombre y slug requeridos'
    return
  }
  try {
    const res = await post('restaurantes', {
      nombre: form.value.nombre,
      slug: form.value.slug,
      descripcion: form.value.descripcion
    })
    await load()
    form.value = { nombre: '', slug: '', descripcion: '' }
  } catch (err) {
    error.value = err.message || 'Error creando restaurante'
  }
}

onMounted(load)
</script>

<style scoped>
.admin-restaurantes { max-width:900px; margin:20px auto; padding:20px }
.nuevo { display:flex; gap:8px; margin-bottom:16px }
.nuevo input { flex:1; padding:8px; border-radius:6px; border:1px solid #ddd }
.nuevo button { background:#FF6B35; color:white; border:none; padding:8px 16px; border-radius:6px }
.lista .item { padding:12px; background:#fff; border-radius:8px; margin-bottom:8px; box-shadow:0 4px 10px rgba(0,0,0,0.04) }
.meta { color:#666; font-size:0.9rem }
.error { color:#d32f2f; margin-top:12px }
</style>
