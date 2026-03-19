<template>
  <div class="stack">
    <div class="header-row">
      <h2>Dashboard</h2>
      <div v-if="auth.isSuperAdmin" class="branch-select">
        <select v-model.number="selectedBranch" @change="load">
          <option :value="0">Select Branch…</option>
          <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
      </div>
    </div>

    <div v-if="auth.isSuperAdmin && !selectedBranch" class="card muted-msg">
      Select a branch above to view its dashboard.
    </div>

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
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()

type Dashboard = {
  total_sales_today: string
  total_sales_month: string
  total_orders: number
  top_products: Array<{ product_id: number; name: string; sku: string; quantity_sold: number }>
  low_stock: Array<{ product_id: number; name: string; sku: string; quantity: number }>
}
type Branch = { id: number; name: string }

const loading = ref(false)
const error = ref<string | null>(null)
const data = ref<Dashboard | null>(null)
const branches = ref<Branch[]>([])
const selectedBranch = ref<number>(auth.user?.branch?.id ?? 0)

async function loadBranches() {
  if (!auth.isSuperAdmin) return
  try {
    const res = await api.get('/branches', { params: { per_page: 100 } })
    branches.value = res.data?.data ?? []
    if (branches.value.length > 0 && !selectedBranch.value) {
      selectedBranch.value = branches.value[0].id
    }
  } catch {
    // silently ignore
  }
}

async function load() {
  const bid = auth.isSuperAdmin ? selectedBranch.value : (auth.user?.branch?.id ?? 0)
  if (!bid) return
  loading.value = true
  error.value = null
  try {
    const params = auth.isSuperAdmin ? { branch_id: bid } : {}
    const res = await api.get(auth.isSuperAdmin ? '/reports' : '/dashboard', { params })
    data.value = res.data
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Failed to load dashboard'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadBranches()
  load()
})
</script>

<style scoped>
.stack { display: grid; gap: 14px; }
.header-row { display: flex; justify-content: space-between; align-items: center; }
h2 { margin: 0; font-size: 18px; }
h3 { margin: 0 0 10px; font-size: 14px; color: rgba(229,231,235,.9); }
.branch-select select {
  padding: 8px 12px;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(0,0,0,.25);
  color: #e5e7eb;
}
.branch-select select option { background: #1f2937; color: #fff; }
.muted-msg { color: rgba(229,231,235,.6); font-size: 13px; }
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

