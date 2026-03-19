<template>
  <div class="stack">
    <div class="header-row">
      <h2>Products</h2>
      <button v-if="auth.isSuperAdmin" class="btn primary" @click="showCreate = !showCreate">
        {{ showCreate ? 'Cancel' : 'Add Product' }}
      </button>
    </div>

    <div v-if="showCreate && auth.isSuperAdmin" class="card create-form">
      <h3>Create New Product</h3>
      <div class="form-grid">
        <label class="field">
          <span>Name</span>
          <input v-model="newProduct.name" placeholder="Name" />
        </label>
        <label class="field">
          <span>SKU</span>
          <input v-model="newProduct.sku" placeholder="SKU" />
        </label>
        <label class="field">
          <span>Price</span>
          <input v-model.number="newProduct.sale_price" type="number" step="0.01" />
        </label>
        <label class="field">
          <span>Tax %</span>
          <input v-model.number="newProduct.tax_percentage" type="number" />
        </label>
        <button class="btn primary" @click="createProduct" :disabled="creating">
          {{ creating ? 'Creating...' : 'Save Product' }}
        </button>
      </div>
      <p v-if="createError" class="error">{{ createError }}</p>
    </div>

    <div class="row">
      <input v-model="search" placeholder="Search name/SKU…" @keyup.enter="load" />
      <select v-model="isActive">
        <option value="">All Status</option>
        <option value="true">Active</option>
        <option value="false">Inactive</option>
      </select>
      <button class="btn" @click="load" :disabled="loading">Search</button>
    </div>

    <div v-if="loading" class="card">Loading…</div>
    <div v-else-if="error" class="card error">{{ error }}</div>

    <div v-else class="card">
      <table class="table">
        <thead>
          <tr><th>SKU</th><th>Name</th><th class="r">Sale</th><th class="r">Tax %</th><th>Status</th><th v-if="auth.isSuperAdmin"></th></tr>
        </thead>
        <tbody>
          <tr v-for="p in products" :key="p.id">
            <td>{{ p.sku }}</td>
            <td>{{ p.name }}</td>
            <td class="r">${{ p.sale_price }}</td>
            <td class="r">{{ p.tax_percentage }}%</td>
            <td><span :class="p.is_active ? 'ok' : 'muted'">{{ p.is_active ? 'Active' : 'Inactive' }}</span></td>
            <td v-if="auth.isSuperAdmin" class="r">
              <button class="link danger" @click="deleteProduct(p.id)" :disabled="deleting === p.id">
                {{ deleting === p.id ? '...' : 'Delete' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '../api'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()

type Product = {
  id: number
  name: string
  sku: string
  sale_price: string
  tax_percentage: string
  is_active: boolean
}

const search = ref('')
const isActive = ref<string>('')
const loading = ref(false)
const error = ref<string | null>(null)
const products = ref<Product[]>([])

const showCreate = ref(false)
const creating = ref(false)
const createError = ref<string | null>(null)
const newProduct = ref({ name: '', sku: '', sale_price: 0, tax_percentage: 10, is_active: true })
const deleting = ref<number | null>(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    const res = await api.get('/products', { params: { search: search.value, is_active: isActive.value, per_page: 50 } })
    products.value = res.data?.data ?? []
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Failed to load products'
  } finally {
    loading.value = false
  }
}

async function createProduct() {
  if (!newProduct.value.name || !newProduct.value.sku) return (createError.value = 'Name and SKU are required')
  creating.value = true
  createError.value = null
  try {
    await api.post('/products', newProduct.value)
    newProduct.value = { name: '', sku: '', sale_price: 0, tax_percentage: 10, is_active: true }
    showCreate.value = false
    load()
  } catch (e: any) {
    createError.value = e?.response?.data?.message ?? 'Failed to create product'
  } finally {
    creating.value = false
  }
}

async function deleteProduct(id: number) {
  if (!confirm('Are you sure you want to delete this product?')) return
  deleting.value = id
  try {
    await api.delete(`/products/${id}`)
    load()
  } catch (e: any) {
    alert(e?.response?.data?.message ?? 'Failed to delete product')
  } finally {
    deleting.value = null
  }
}

onMounted(load)
</script>

<style scoped>
.stack { display: grid; gap: 14px; }
.header-row { display: flex; justify-content: space-between; align-items: center; }
h2 { margin: 0; font-size: 18px; }
h3 { margin: 0 0 12px 0; font-size: 15px; }
.row { display: flex; gap: 10px; align-items: center; }
.create-form { padding: 16px; margin-bottom: 4px; border-color: rgba(99, 102, 241, 0.3); }
.form-grid { display: flex; gap: 12px; align-items: end; flex-wrap: wrap; }
.field { display: flex; flex-direction: column; gap: 4px; }
.field span { font-size: 11px; color: rgba(229,231,235,0.6); }
input, select {
  padding: 8px 12px;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(0,0,0,.25);
  color: #e5e7eb;
}
select option { background: #1f2937; color: #fff; }
.btn {
  padding: 9px 14px;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(255,255,255,.08);
  color: #e5e7eb;
  cursor: pointer;
  font-size: 13px;
}
.btn.primary {
  background: rgba(99, 102, 241, 0.25);
  border-color: rgba(99, 102, 241, 0.4);
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
.link { background: transparent; border: none; cursor: pointer; font-size: 12px; }
.link.danger { color: #f87171; }
.ok { color: #bbf7d0; }
.muted { color: rgba(229,231,235,.6); }
.error { color: #fecaca; }
</style>


