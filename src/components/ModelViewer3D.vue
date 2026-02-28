<template>
  <model-viewer
    class="model-viewer"
    :src="modeloUrl"
    alt="Modelo 3D del producto"
    camera-controls
    auto-rotate
    shadow-intensity="1"
  ></model-viewer>
</template>

<script setup>
import { defineProps, onMounted } from 'vue'

defineProps({
  modeloUrl: {
    type: String,
    required: true
  }
})

onMounted(() => {
  // Cargar el web component de Google Model-Viewer si no está cargado.
  // Este script puede estar en dist/index.html o en una CDN pública en producción.
  if (!customElements.get('model-viewer')) {
    const script = document.createElement('script')
    script.src = 'https://cdn.jsdelivr.net/npm/@google/model-viewer'
    script.type = 'module'
    document.head.appendChild(script)
  }
})
</script>

<style scoped>
.model-viewer {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
}
</style>
