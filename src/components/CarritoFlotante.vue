<template>
  <button class="carrito-flotante" @click="$emit('abrir')">
    <span class="carrito-icon">🛒</span>
    <span class="carrito-texto">Ver pedido</span>
    <span class="carrito-badge">{{ totalItems }}</span>
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  carrito: { type: Array, required: true }
})

defineEmits(['abrir'])

const totalItems = computed(() => props.carrito.reduce((s, i) => s + i.cantidad, 0))
</script>

<style scoped>
.carrito-flotante {
  position: fixed;
  bottom: 24px;
  right: 20px;
  z-index: 500;
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--accent, #FF6B35);
  color: #fff;
  border: none;
  border-radius: 28px;
  padding: 13px 20px 13px 16px;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 6px 24px rgba(0,0,0,0.22);
  transition: transform 0.2s, opacity 0.2s;
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.carrito-flotante:hover {
  transform: translateY(-2px);
  opacity: 0.92;
}

.carrito-icon { font-size: 1.1rem; }

.carrito-badge {
  background: #fff;
  color: var(--accent, #FF6B35);
  border-radius: 50%;
  min-width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 800;
  padding: 0 4px;
}

@keyframes slideUp {
  from { transform: translateY(60px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}
</style>
