<template>
  <model-viewer
    class="model-viewer"
    :src="modeloUrl"
    alt="Modelo 3D del producto"
    camera-controls
    auto-rotate
    shadow-intensity="1"
    ar
    ar-modes="webxr scene-viewer quick-look"
  >
    <button slot="ar-button" class="ar-btn">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="5" y="2" width="14" height="20" rx="2"/>
        <circle cx="12" cy="17" r="1"/>
      </svg>
      Ver en tu mesa (AR)
    </button>
  </model-viewer>
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
    script.src = 'https://cdn.jsdelivr.net/npm/@google/model-viewer@4.0.0/dist/model-viewer.min.js'
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

.ar-btn {
  position: absolute;
  bottom: 16px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--accent, #FF6B35);
  color: #fff;
  border: none;
  padding: 12px 22px;
  border-radius: 28px;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
  box-shadow: 0 4px 18px rgba(0,0,0,0.25);
  animation: ar-pulse 2.4s ease-in-out infinite;
}

@keyframes ar-pulse {
  0%, 100% { box-shadow: 0 4px 18px rgba(0,0,0,0.25); }
  50%       { box-shadow: 0 4px 28px rgba(255,107,53,0.55), 0 0 0 6px rgba(255,107,53,0.15); }
}
</style>
