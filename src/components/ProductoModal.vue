<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-contenido">
      <button class="btn-close" @click="$emit('close')" aria-label="Cerrar">✕</button>

      <div class="modal-grid">
        <div class="modal-imagen">
          <ModelViewer3D
            v-if="producto.tiene_ar"
            :modelo-url="producto.modelo_glb_url"
          />
          <img
            v-else
            :src="producto.foto_principal"
            :alt="producto.nombre"
            class="foto-fallback"
          />
        </div>

        <div class="modal-info">
          <h2>{{ producto.nombre }}</h2>
          <p class="descripcion-completa">{{ producto.descripcion }}</p>

          <div class="precio-section">
            <span class="precio-label">Precio</span>
            <span class="precio-valor">${{ producto.precio.toFixed(2) }}</span>
          </div>

          <div v-if="producto.tiene_ar" class="acciones">
            <p class="info-ar">Rota el modelo con el dedo • Disponible en Android e iOS</p>
          </div>

          <div v-else class="sin-ar">
            <p>Modelo 3D no disponible aún. Pronto lo tendrás.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'
import ModelViewer3D from './ModelViewer3D.vue'

defineProps({
  producto: {
    type: Object,
    required: true
  }
})

defineEmits(['close'])
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.modal-contenido {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 900px;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
  animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
  from {
    transform: translateY(30px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.btn-close {
  position: absolute;
  top: 16px;
  right: 16px;
  background: rgba(0, 0, 0, 0.1);
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s ease;
  z-index: 10;
}

.btn-close:hover {
  background: rgba(0, 0, 0, 0.2);
}

.modal-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
  padding: 40px;
}

.modal-imagen {
  width: 100%;
  aspect-ratio: 1;
  background: #f5f5f5;
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.foto-fallback {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.modal-info {
  display: flex;
  flex-direction: column;
}

.modal-info h2 {
  font-size: 1.8rem;
  font-weight: 700;
  color: #333;
  margin-bottom: 12px;
}

.descripcion-completa {
  font-size: 1rem;
  color: #666;
  line-height: 1.6;
  margin-bottom: 24px;
}

.precio-section {
  background: #f5f5f5;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 24px;
  display: flex;
  align-items: baseline;
  gap: 12px;
}

.precio-label {
  font-size: 0.95rem;
  color: #999;
  font-weight: 500;
}

.precio-valor {
  font-size: 2rem;
  font-weight: 700;
  color: #FF6B35;
}

.acciones {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.btn-primary {
  background: #FF6B35;
  color: white;
  border: none;
  padding: 14px 24px;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s ease;
}

.btn-primary:hover {
  background: #E55A2B;
}

.info-ar {
  font-size: 0.85rem;
  color: #999;
  text-align: center;
}

.sin-ar {
  background: #fff3e0;
  border-left: 4px solid #FF9800;
  padding: 16px;
  border-radius: 4px;
}

.sin-ar p {
  color: #E65100;
  font-size: 0.95rem;
}

@media (max-width: 700px) {
  .modal-grid {
    grid-template-columns: 1fr;
    gap: 20px;
    padding: 20px;
  }

  .modal-imagen {
    aspect-ratio: auto;
    height: 300px;
  }

  .modal-info h2 {
    font-size: 1.4rem;
  }

  .precio-valor {
    font-size: 1.5rem;
  }
}
</style>
