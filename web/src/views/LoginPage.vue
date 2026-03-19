<template>
  <div class="center">
    <div class="card">
      <h1>Login</h1>
      <p class="muted">Use the seeded credentials from the README.</p>

      <form @submit.prevent="submit" class="form">
        <label>
          <span>Email</span>
          <input v-model="email" type="email" autocomplete="username" required />
        </label>
        <label>
          <span>Password</span>
          <input v-model="password" type="password" autocomplete="current-password" required />
        </label>
        <button class="btn primary" type="submit" :disabled="auth.loading">
          {{ auth.loading ? 'Signing in…' : 'Sign in' }}
        </button>
      </form>

      <p v-if="error" class="error">{{ error }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const email = ref('superadmin@example.com')
const password = ref('password')
const error = ref<string | null>(null)

async function submit() {
  error.value = null
  try {
    await auth.login(email.value, password.value)
    router.push('/dashboard')
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Login failed'
  }
}
</script>

<style scoped>
.center {
  min-height: calc(100vh - 56px);
  display: grid;
  place-items: center;
}
.card {
  width: min(420px, calc(100vw - 32px));
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 16px;
  padding: 18px;
}
h1 {
  margin: 0 0 6px;
  font-size: 22px;
}
.muted {
  margin: 0 0 14px;
  color: rgba(229, 231, 235, 0.7);
  font-size: 13px;
}
.form {
  display: grid;
  gap: 10px;
}
label {
  display: grid;
  gap: 6px;
}
span {
  font-size: 12px;
  color: rgba(229, 231, 235, 0.8);
}
input {
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(0, 0, 0, 0.25);
  color: #e5e7eb;
}
.btn {
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.08);
  color: #e5e7eb;
  cursor: pointer;
}
.btn.primary {
  background: rgba(99, 102, 241, 0.25);
  border-color: rgba(99, 102, 241, 0.4);
}
.error {
  margin-top: 12px;
  color: #fecaca;
  font-size: 13px;
}
</style>

