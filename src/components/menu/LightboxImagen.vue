<template>
  <Teleport to="body">
    <div class="lb-overlay" @click.self="$emit('close')">
      <button class="lb-close" @click="$emit('close')" aria-label="Cerrar">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
      <div class="lb-wrap">
        <img :src="src" :alt="alt" class="lb-img" decoding="async" />
        <img v-if="logoUrl" :src="logoUrl" class="lb-watermark" alt="" />
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'

defineProps({ src: String, alt: String, logoUrl: { type: String, default: null } })
const emit = defineEmits(['close'])

const onKey = (e) => { if (e.key === 'Escape') emit('close') }
onMounted(() => document.addEventListener('keydown', onKey))
onUnmounted(() => document.removeEventListener('keydown', onKey))
</script>

<style scoped>
.lb-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.93);
  z-index: 3000;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: lbFade 0.18s ease-out;
}

@keyframes lbFade {
  from { opacity: 0; }
  to   { opacity: 1; }
}

.lb-wrap {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.lb-img {
  max-width: 100vw;
  max-height: 100vh;
  object-fit: contain;
  animation: lbZoom 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  touch-action: pinch-zoom;
  display: block;
}

.lb-watermark {
  position: absolute;
  bottom: 30px;
  right: 30px;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  object-fit: cover;
  opacity: 0.45;
  pointer-events: none;
}

@keyframes lbZoom {
  from { transform: scale(0.9); opacity: 0; }
  to   { transform: scale(1);   opacity: 1; }
}

.lb-close {
  position: fixed;
  top: 16px;
  right: 16px;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.15);
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  backdrop-filter: blur(4px);
  transition: background 0.2s;
}

.lb-close:hover {
  background: rgba(255, 255, 255, 0.28);
}
</style>
