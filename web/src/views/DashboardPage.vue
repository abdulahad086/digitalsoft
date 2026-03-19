<template>
  <div class="stack">
    <h2>Dashboard</h2>

    <div v-if="loading" class="card">Loading…</div>
    <div v-else-if="error" class="card error">{{ error }}</div>

    <div v-else class="grid">
      <div class="card">
        <div class="k">Sales Today</div>
        <div class="v">{{ data?.total_sales_today }}</div>
      </div>
      <div class="card">
        <div class="k">Sales This Month</div>
        <div class="v">{{ data?.total_sales_month }}</div>
      </div>
      <div class="card">
        <div class="k">Total Orders</div>
        <div class="v">{{ data?.total_orders }}</div>
      </div>
    </div>

    <div v-if="data" class="grid2">
      <div class="card">
        <h3>Top 5 Products</h3>
        <table class="table">
          <thead>
            <tr><th>SKU</th><th>Name</th><th class="r">Qty</th></tr>
          </thead>
          <tbody>
            <tr v-for="p in data.top_products" :key="p.product_id">
              <td>{{ p.sku }}</td>
              <td>{{ p.name }}</td>
              <td class="r">{{ p.quantity_sold }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="card">
        <h3>Low Stock</h3>
        <table class="table">
          <thead>
            <tr><th>SKU</th><th>Name</th><th class="r">Qty</th></tr>
          </thead>
          <tbody>
            <tr v-for="p in data.low_stock" :key="p.product_id">
              <td>{{ p.sku }}</td>
              <td>{{ p.name }}</td>
              <td class="r" :class="{ danger: p.quantity <= 5 }">{{ p.quantity }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '../api'

type Dashboard = {
  total_sales_today: string
  total_sales_month: string
  total_orders: number
  top_products: Array<{ product_id: number; name: string; sku: string; quantity_sold: number }>
  low_stock: Array<{ product_id: number; name: string; sku: string; quantity: number }>
}

const loading = ref(false)
const error = ref<string | null>(null)
const data = ref<Dashboard | null>(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    const res = await api.get('/dashboard')
    data.value = res.data
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Failed to load dashboard'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.stack { display: grid; gap: 14px; }
h2 { margin: 0; font-size: 18px; }
h3 { margin: 0 0 10px; font-size: 14px; color: rgba(229,231,235,.9); }
.grid { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 12px; }
.grid2 { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 12px; }
.card {
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 14px;
  padding: 14px;
}
.k { font-size: 12px; color: rgba(229,231,235,.7); }
.v { font-size: 22px; font-weight: 700; margin-top: 6px; }
.table { width: 100%; border-collapse: collapse; font-size: 13px; }
.table th, .table td { padding: 8px 6px; border-bottom: 1px solid rgba(255,255,255,.08); }
.r { text-align: right; }
.danger { color: #fecaca; font-weight: 700; }
.error { color: #fecaca; }
@media (max-width: 900px) {
  .grid { grid-template-columns: 1fr; }
  .grid2 { grid-template-columns: 1fr; }
}
</style>

