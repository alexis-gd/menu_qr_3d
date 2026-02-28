<template>
  <div class="login-page">
    <div class="login-box">
      <h2>Admin Login</h2>
      <form @submit.prevent="submit">
        <label>Email</label>
        <input v-model="email" type="email" required />
        <label>Contraseña</label>
        <input v-model="password" type="password" required />
        <button type="submit">Entrar</button>
        <p v-if="error" class="error">{{ error }}</p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../../composables/useApi.js'

const email = ref('')
const password = ref('')
const error = ref(null)
const router = useRouter()
const { post } = useApi()

const submit = async () => {
  error.value = null
  try {
    const res = await post('login', { email: email.value, password: password.value }, false)
    const token = res.token
    localStorage.setItem('admin_token', token)
    router.push('/admin/restaurantes')
  } catch (err) {
    error.value = err.message || 'Error al iniciar sesión'
  }
}
</script>

<style scoped>
.login-page { display:flex; align-items:center; justify-content:center; min-height:70vh; }
.login-box { background:white; padding:24px; border-radius:8px; width:320px; box-shadow:0 8px 20px rgba(0,0,0,0.08);} 
.login-box h2 { margin-bottom:12px }
.login-box label { display:block; margin-top:8px; font-size:0.9rem }
.login-box input { width:100%; padding:8px; margin-top:6px; border-radius:6px; border:1px solid #ddd }
button { margin-top:12px; width:100%; padding:10px; background:#FF6B35; color:white; border:none; border-radius:6px; font-weight:700 }
.error { color:#d32f2f; margin-top:8px }
</style>
