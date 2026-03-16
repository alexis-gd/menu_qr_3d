<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-brand">
        <span class="brand-icon">
          <SvgIcon :path="mdiSilverwareForkKnife" :size="44" color="#FF6B35" />
        </span>
        <h1>Panel Admin</h1>
        <p>Gestiona el menú de tu restaurante</p>
      </div>

      <form @submit.prevent="submit" class="login-form">
        <div class="field-group">
          <label>Correo electrónico</label>
          <input v-model="email" type="email" placeholder="admin@restaurante.com" required />
        </div>
        <div class="field-group">
          <label>Contraseña</label>
          <input v-model="password" type="password" placeholder="••••••••" required />
        </div>
        <button type="submit" class="btn-login" :disabled="cargando">
          {{ cargando ? 'Entrando...' : 'Entrar al panel' }}
        </button>
        <p v-if="errorMsg" class="error-msg">{{ errorMsg }}</p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { mdiSilverwareForkKnife } from '@mdi/js'
import { useApi } from '../../composables/useApi.js'
import { resetAuth } from '../../router/index.js'
import SvgIcon from '../../components/SvgIcon.vue'

const email = ref('')
const password = ref('')
const errorMsg = ref(null)
const cargando = ref(false)
const router = useRouter()
const { post } = useApi()

const submit = async () => {
  errorMsg.value = null
  cargando.value = true
  try {
    await post('login', { email: email.value, password: password.value }, false)
    // Limpiar la caché del guard (estaba en false por la visita inicial al login)
    resetAuth()
    router.push('/admin/dashboard')
  } catch (err) {
    errorMsg.value = err.message || 'Credenciales incorrectas'
  } finally {
    cargando.value = false
  }
}
// La redirección si ya está autenticado la maneja el router guard (beforeEach)
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
  padding: 20px;
}

.login-card {
  background: #fff;
  border-radius: 20px;
  padding: 48px 40px;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.35);
}

.login-brand {
  text-align: center;
  margin-bottom: 36px;
}

.brand-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 12px;
}

.login-brand h1 {
  font-size: 1.6rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 6px;
}

.login-brand p {
  font-size: 0.9rem;
  color: #888;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field-group label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #444;
  letter-spacing: 0.3px;
}

.field-group input {
  padding: 12px 16px;
  border: 1.5px solid #e0e0e0;
  border-radius: 10px;
  font-size: 0.95rem;
  transition: border-color 0.2s;
  outline: none;
  background: #fafafa;
}

.field-group input:focus {
  border-color: #FF6B35;
  background: #fff;
}

.btn-login {
  padding: 14px;
  background: linear-gradient(135deg, #FF6B35 0%, #f7931e 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  transition: opacity 0.2s, transform 0.1s;
  letter-spacing: 0.3px;
}

.btn-login:hover:not(:disabled) {
  opacity: 0.92;
  transform: translateY(-1px);
}

.btn-login:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error-msg {
  background: #ffebee;
  color: #c62828;
  border: 1px solid #ef9a9a;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 0.9rem;
  text-align: center;
  margin: 0;
}
</style>
