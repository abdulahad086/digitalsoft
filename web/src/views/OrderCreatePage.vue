<template>
  <div class="stack">
    <h2>Create Order</h2>

    <div class="card">
      <div class="row">
        <label class="field" v-if="auth.isSuperAdmin">
          <span>Branch</span>
          <select v-model="branchId">
            <option :value="null">Select Branch...</option>
            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </label>
        <label class="field" v-else>
          <span>Branch</span>
          <input :value="auth.user?.branch?.name" disabled />
        </label>

        <label class="field">
          <span>Product</span>
          <select v-model="selectedProductId">
            <option :value="null">Select Product...</option>
            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} (${{ p.sale_price }})</option>
          </select>
        </label>

        <label class="field">
          <span>Qty</span>
          <input v-model.number="quantity" type="number" min="1" />
        </label>
        <button class="btn" @click="addLine">Add</button>
      </div>

      <table class="table" v-if="items.length">
        <thead>
          <tr><th>Product</th><th class="r">Price</th><th class="r">Qty</th><th class="r">Total</th><th></th></tr>
        </thead>
        <tbody>
          <tr v-for="(it, idx) in items" :key="idx">
            <td>{{ it.name }}</td>
            <td class="r">${{ it.price }}</td>
            <td class="r">{{ it.quantity }}</td>
            <td class="r">${{ (it.price * it.quantity).toFixed(2) }}</td>
            <td class="r"><button class="link" @click="remove(idx)">Remove</button></td>
          </tr>
        </tbody>
        <tfoot>
          <tr><td colspan="3" class="r">Subtotal</td><td class="r">${{ subtotal.toFixed(2) }}</td><td></td></tr>
          <tr><td colspan="3" class="r">Tax (10%)</td><td class="r">${{ tax.toFixed(2) }}</td><td></td></tr>
          <tr class="grand"><td colspan="3" class="r">Grand Total</td><td class="r">${{ total.toFixed(2) }}</td><td></td></tr>
        </tfoot>
      </table>

      <div class="actions">
        <button class="btn primary" @click="submit" :disabled="loading || items.length === 0">
          {{ loading ? 'Creating…' : 'Create order' }}
        </button>
      </div>

      <p v-if="error" class="error">{{ error }}</p>
      <p v-if="success" class="ok">{{ success }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { api } from '../api'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()

const branchId = ref<number | null>(auth.user?.branch?.id ?? null)
const selectedProductId = ref<number | null>(null)
const quantity = ref<number>(1)
const items = ref<Array<{ product_id: number; name: string; price: number; quantity: number }>>([])

const branches = ref<any[]>([])
const products = ref<any[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const success = ref<string | null>(null)

const subtotal = computed(() => items.value.reduce((acc, it) => acc + (it.price * it.quantity), 0))
const tax = computed(() => subtotal.value * 0.1)
const total = computed(() => subtotal.value + tax.value)

async function loadData() {
  try {
    const [bRes, pRes] = await Promise.all([
      api.get('/branches'),
      api.get('/products')
    ])
    branches.value = bRes.data?.data ?? []
    products.value = pRes.data?.data ?? []
  } catch (e) {
    console.error('Failed to load branches/products', e)
  }
}

function addLine() {
  error.value = null
  success.value = null
  if (!branchId.value) return (error.value = 'Branch is required')
  if (!selectedProductId.value) return (error.value = 'Product is required')
  
  const p = products.value.find(x => x.id === selectedProductId.value)
  if (!p) return

  items.value.push({ 
    product_id: p.id, 
    name: p.name, 
    price: parseFloat(p.sale_price), 
    quantity: quantity.value 
  })
  selectedProductId.value = null
  quantity.value = 1
}

function remove(i: number) {
  items.value.splice(i, 1)
}

async function submit() {
  error.value = null
  success.value = null
  if (!branchId.value) return (error.value = 'Branch is required')
  loading.value = true
  try {
    await api.post('/orders', { 
      branch_id: branchId.value, 
      items: items.value.map(it => ({ product_id: it.product_id, quantity: it.quantity })) 
    })
    items.value = []
    success.value = 'Order created successfully'
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Failed to create order'
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
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
.row { display: flex; gap: 10px; align-items: end; flex-wrap: wrap; }
.field { display: grid; gap: 6px; }
.field span { font-size: 12px; color: rgba(229,231,235,.75); }
input, select {
  width: 200px;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(0,0,0,.25);
  color: #e5e7eb;
}
select option { background: #1f2937; color: #fff; }
.btn {
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(255,255,255,.08);
  color: #e5e7eb;
  cursor: pointer;
}
.btn.primary {
  background: rgba(99, 102, 241, 0.25);
  border-color: rgba(99, 102, 241, 0.4);
}
.table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 12px; }
.table th, .table td { padding: 8px 6px; border-bottom: 1px solid rgba(255,255,255,.08); text-align: left; }
.r { text-align: right; }
tfoot td { border-top: 1px solid rgba(255,255,255,0.15); font-weight: 500; }
.grand { font-size: 15px; font-weight: 700; color: #818cf8; }
.actions { margin-top: 12px; display: flex; justify-content: end; }
.link {
  background: transparent;
  border: none;
  color: #93c5fd;
  cursor: pointer;
}
.error { margin-top: 12px; color: #fecaca; font-size: 13px; }
.ok { margin-top: 12px; color: #bbf7d0; font-size: 13px; }
</style>


