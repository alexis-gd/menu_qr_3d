<script setup>
defineProps({
  path:  { type: String, required: true },
  size:  { type: [Number, String], default: 24 },
  color: { type: String, default: 'currentColor' },
})
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" :fill="color" aria-hidden="true">
    <path :d="path" />
  </svg>
</template>
