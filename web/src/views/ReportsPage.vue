<template>
  <div class="stack">
    <h2>Reports</h2>

    <div class="row">
      <input v-model.number="branchId" type="number" min="1" placeholder="Branch ID (Super Admin only)" />
      <button class="btn" @click="load" :disabled="loading">Load</button>
    </div>

    <div v-if="loading" class="card">Loading…</div>
    <div v-else-if="error" class="card error">{{ error }}</div>

    <div v-else-if="data" class="card">
      <pre class="pre">{{ data }}</pre>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '../api'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const branchId = ref<number | null>(auth.user?.branch?.id ?? null)
const loading = ref(false)
const error = ref<string | null>(null)
const data = ref<any>(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    const res = await api.get('/reports', { params: { branch_id: auth.isSuperAdmin ? branchId.value : undefined } })
    data.value = res.data
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Failed to load reports'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.stack { display: grid; gap: 14px; }
h2 { margin: 0; font-size: 18px; }
.row { display: flex; gap: 10px; align-items: center; }
input {
  flex: 1;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(0,0,0,.25);
  color: #e5e7eb;
}
.btn {
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(255,255,255,.08);
  color: #e5e7eb;
  cursor: pointer;
}
.card {
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 14px;
  padding: 14px;
}
.pre {
  margin: 0;
  font-size: 12px;
  white-space: pre-wrap;
  word-break: break-word;
}
.error { color: #fecaca; }
</style>

