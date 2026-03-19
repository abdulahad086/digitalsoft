<template>
  <div class="stack">
    <h2>Inventory</h2>

    <div class="row">
      <input v-model="search" placeholder="Search name/SKU…" @keyup.enter="load" />
      <input v-model.number="lowStockBelow" type="number" min="1" placeholder="Low stock ≤" />
      <button class="btn" @click="load" :disabled="loading">Search</button>
    </div>

    <div v-if="loading" class="card">Loading…</div>
    <div v-else-if="error" class="card error">{{ error }}</div>

    <div v-else class="card">
      <table class="table">
        <thead>
          <tr><th>SKU</th><th>Name</th><th class="r">Qty</th></tr>
        </thead>
        <tbody>
          <tr v-for="i in rows" :key="i.id">
            <td>{{ i.product?.sku }}</td>
            <td>{{ i.product?.name }}</td>
            <td class="r" :class="{ danger: i.quantity <= 5 }">{{ i.quantity }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '../api'

type Row = { id: number; quantity: number; product?: { sku: string; name: string } }

const search = ref('')
const lowStockBelow = ref<number | null>(null)
const loading = ref(false)
const error = ref<string | null>(null)
const rows = ref<Row[]>([])

async function load() {
  loading.value = true
  error.value = null
  try {
    const res = await api.get('/inventory', {
      params: { search: search.value, low_stock_below: lowStockBelow.value ?? undefined, per_page: 50 },
    })
    rows.value = res.data?.data ?? []
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Failed to load inventory'
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
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(0,0,0,.25);
  color: #e5e7eb;
}
input:first-child { flex: 1; }
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
.table { width: 100%; border-collapse: collapse; font-size: 13px; }
.table th, .table td { padding: 8px 6px; border-bottom: 1px solid rgba(255,255,255,.08); text-align: left; }
.r { text-align: right; }
.danger { color: #fecaca; font-weight: 700; }
.error { color: #fecaca; }
</style>

