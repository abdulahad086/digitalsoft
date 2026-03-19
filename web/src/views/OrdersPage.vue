<template>
  <div class="stack">
    <h2>Orders</h2>

    <div v-if="loading" class="card">Loading…</div>
    <div v-else-if="error" class="card error">{{ error }}</div>

    <div v-else class="card">
      <table class="table">
        <thead>
          <tr><th>ID</th><th>Branch</th><th>User</th><th class="r">Total</th><th>Ordered</th></tr>
        </thead>
        <tbody>
          <tr v-for="o in orders" :key="o.id">
            <td>{{ o.id }}</td>
            <td>{{ o.branch?.name ?? '-' }}</td>
            <td>{{ o.user?.email ?? '-' }}</td>
            <td class="r">{{ o.grand_total }}</td>
            <td>{{ fmt(o.ordered_at) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '../api'

type Order = {
  id: number
  grand_total: string
  ordered_at: string
  branch?: { name: string }
  user?: { email: string }
}

const loading = ref(false)
const error = ref<string | null>(null)
const orders = ref<Order[]>([])

function fmt(v: string) {
  try {
    return new Date(v).toLocaleString()
  } catch {
    return v
  }
}

async function load() {
  loading.value = true
  error.value = null
  try {
    const res = await api.get('/orders', { params: { per_page: 50 } })
    orders.value = res.data?.data ?? []
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Failed to load orders'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.stack { display: grid; gap: 14px; }
h2 { margin: 0; font-size: 18px; }
.card {
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 14px;
  padding: 14px;
}
.table { width: 100%; border-collapse: collapse; font-size: 13px; }
.table th, .table td { padding: 8px 6px; border-bottom: 1px solid rgba(255,255,255,.08); text-align: left; }
.r { text-align: right; }
.error { color: #fecaca; }
</style>

