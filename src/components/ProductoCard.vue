<template>
  <div class="producto-card" @click="$emit('click')">
    <div class="imagen-container">
      <img
        :src="producto.foto_principal"
        :alt="producto.nombre"
        class="producto-imagen"
      />
      <div v-if="producto.tiene_ar" class="badge-ar">
        <span>3D/AR</span>
      </div>
      <div v-if="producto.es_destacado" class="badge-destacado">
        <span>⭐ Destacado</span>
      </div>
    </div>

    <div class="contenido">
      <h3 class="producto-nombre">{{ producto.nombre }}</h3>
      <p v-if="producto.descripcion" class="producto-descripcion">
        {{ truncar(producto.descripcion, 60) }}
      </p>
      <div class="footer">
        <span class="precio">${{ producto.precio.toFixed(2) }}</span>
        <button class="btn-ver" aria-label="Ver detalles">Ver</button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  producto: {
    type: Object,
    required: true
  }
})

defineEmits(['click'])

const truncar = (texto, length) => {
  if (!texto) return ''
  return texto.length > length ? texto.substring(0, length) + '...' : texto
}
</script>

<style scoped>
.producto-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.producto-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
}

.imagen-container {
  position: relative;
  width: 100%;
  height: 200px;
  overflow: hidden;
  background: #e0e0e0;
}

.producto-imagen {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.producto-card:hover .producto-imagen {
  transform: scale(1.05);
}

.badge-ar,
.badge-destacado {
  position: absolute;
  top: 10px;
  right: 10px;
  background: rgba(255, 107, 53, 0.95);
  color: white;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
}

.badge-destacado {
  top: 50px;
}

.contenido {
  padding: 16px;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.producto-nombre {
  font-size: 1.1rem;
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
}

.producto-descripcion {
  font-size: 0.9rem;
  color: #666;
  margin-bottom: 12px;
  flex: 1;
  line-height: 1.4;
}

.footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
}

.precio {
  font-size: 1.3rem;
  font-weight: 700;
  color: #FF6B35;
}

.btn-ver {
  background: #FF6B35;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.9rem;
  transition: background 0.3s ease;
}

.btn-ver:hover {
  background: #E55A2B;
}
</style>
