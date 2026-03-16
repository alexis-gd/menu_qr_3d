<template>
  <Teleport to="body">
    <div v-if="modelValue" class="upload-toast">
      <span class="upload-toast-texto">{{ modelValue.texto }}</span>
      <div class="upload-toast-bar-wrap">
        <div class="upload-toast-bar" :style="{ width: modelValue.progreso + '%' }"></div>
      </div>
      <span class="upload-toast-pct">{{ modelValue.progreso }}%</span>
    </div>
  </Teleport>
</template>

<script setup>
defineProps({ modelValue: Object }) // { texto: string, progreso: 0..100 }
</script>

<style scoped>
.upload-toast {
  position: fixed;
  bottom: 80px;
  left: 50%;
  transform: translateX(-50%);
  background: #1a1a2e;
  color: #fff;
  padding: 14px 20px;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.35);
  z-index: 1100;
  min-width: 240px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 0.88rem;
  font-weight: 600;
}
.upload-toast-bar-wrap {
  background: rgba(255,255,255,0.2);
  border-radius: 99px;
  height: 6px;
  overflow: hidden;
}
.upload-toast-bar {
  height: 100%;
  background: var(--accent, #4caf50);
  border-radius: 99px;
  transition: width 0.2s ease;
}
.upload-toast-pct {
  font-size: 0.78rem;
  opacity: 0.75;
  align-self: flex-end;
}
</style>
