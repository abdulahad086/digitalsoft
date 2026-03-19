<template>
  <div class="stack">
    <h2>Inventory Management</h2>

    <div class="card tabs">
      <button :class="{ active: tab === 'add' }" @click="tab = 'add'">Add Stock</button>
      <button :class="{ active: tab === 'adjust' }" @click="tab = 'adjust'">Adjust Stock</button>
      <button :class="{ active: tab === 'transfer' }" @click="tab = 'transfer'">Transfer Stock</button>
    </div>

    <div v-if="tab === 'add'" class="card form-box">
      <h3>Add New Stock</h3>
      <div class="rows">
        <label class="field" v-if="auth.isSuperAdmin">
          <span>Branch</span>
          <select v-model="form.branch_id">
            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </label>
        <label class="field" v-else>
          <span>Branch</span>
          <input :value="auth.user?.branch?.name" disabled />
        </label>

        <label class="field">
          <span>Product</span>
          <select v-model="form.product_id">
            <option :value="0">Select Product...</option>
            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
          </select>
        </label>

        <label class="field">
          <span>Quantity to Add</span>
          <input v-model.number="form.quantity" type="number" min="1" />
        </label>
        <label class="field">
          <span>Note (Optional)</span>
          <input v-model="form.note" type="text" />
        </label>
        <button class="btn primary" @click="submitAdd" :disabled="loading">Submit</button>
      </div>
    </div>

    <div v-if="tab === 'adjust'" class="card form-box">
      <h3>Adjust Stock Level</h3>
      <p class="muted">Use positive for addition, negative for deduction.</p>
      <div class="rows">
        <label class="field" v-if="auth.isSuperAdmin">
          <span>Branch</span>
          <select v-model="adjust.branch_id">
            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </label>
        <label class="field" v-else>
          <span>Branch</span>
          <input :value="auth.user?.branch?.name" disabled />
        </label>

        <label class="field">
          <span>Product</span>
          <select v-model="adjust.product_id">
            <option :value="0">Select Product...</option>
            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
          </select>
        </label>

        <label class="field">
          <span>Delta (+/-)</span>
          <input v-model.number="adjust.delta" type="number" />
        </label>
        <label class="field">
          <span>Note (Optional)</span>
          <input v-model="adjust.note" type="text" />
        </label>
        <button class="btn primary" @click="submitAdjust" :disabled="loading">Submit</button>
      </div>
    </div>

    <div v-if="tab === 'transfer'" class="card form-box">
      <h3>Transfer Between Branches</h3>
      <div class="rows">
        <label class="field">
          <span>From Branch</span>
          <select v-model="transfer.from_branch_id" :disabled="!auth.isSuperAdmin">
            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </label>
        <label class="field">
          <span>To Branch</span>
          <select v-model="transfer.to_branch_id">
            <option :value="0">Select target branch...</option>
            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </label>
        <label class="field">
          <span>Product</span>
          <select v-model="transfer.product_id">
            <option :value="0">Select Product...</option>
            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
          </select>
        </label>
        <label class="field">
          <span>Quantity</span>
          <input v-model.number="transfer.quantity" type="number" min="1" />
        </label>
        <button class="btn primary" @click="submitTransfer" :disabled="loading">Submit</button>
      </div>
    </div>

    <p v-if="resMsg" :class="{ ok: resStatus === 'ok', err: resStatus === 'err' }">{{ resMsg }}</p>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '../api'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const tab = ref('add')
const loading = ref(false)
const resMsg = ref('')
const resStatus = ref('')

const branches = ref<any[]>([])
const products = ref<any[]>([])

const form = ref({ branch_id: auth.user?.branch?.id ?? 0, product_id: 0, quantity: 0, note: '' })
const adjust = ref({ branch_id: auth.user?.branch?.id ?? 0, product_id: 0, delta: 0, note: '' })
const transfer = ref({ from_branch_id: auth.user?.branch?.id ?? 0, to_branch_id: 0, product_id: 0, quantity: 0, note: '' })

async function loadData() {
  try {
    const [bRes, pRes] = await Promise.all([
      api.get('/branches'),
      api.get('/products')
    ])
    branches.value = bRes.data?.data ?? []
    products.value = pRes.data?.data ?? []
    
    if (auth.isSuperAdmin && branches.value.length > 0) {
      if (!form.value.branch_id) form.value.branch_id = branches.value[0].id
      if (!adjust.value.branch_id) adjust.value.branch_id = branches.value[0].id
      if (!transfer.value.from_branch_id) transfer.value.from_branch_id = branches.value[0].id
    }
  } catch (e) {
    console.error('Failed to load branches/products', e)
  }
}

function setMsg(m: string, s: string) {
  resMsg.value = m
  resStatus.value = s
  setTimeout(() => resMsg.value = '', 4000)
}

async function submitAdd() {
  if (form.value.product_id === 0) return setMsg('Please select a product', 'err')
  loading.value = true
  try {
    await api.post('/inventory/add-stock', form.value)
    setMsg('Stock added successfully', 'ok')
    form.value.product_id = 0
    form.value.quantity = 0
    form.value.note = ''
  } catch (e: any) {
    setMsg(e?.response?.data?.message ?? 'Error adding stock', 'err')
  } finally { loading.value = false }
}

async function submitAdjust() {
  if (adjust.value.product_id === 0) return setMsg('Please select a product', 'err')
  loading.value = true
  try {
    await api.post('/inventory/adjust-stock', adjust.value)
    setMsg('Stock adjusted successfully', 'ok')
    adjust.value.product_id = 0
    adjust.value.delta = 0
    adjust.value.note = ''
  } catch (e: any) {
    setMsg(e?.response?.data?.message ?? 'Error adjusting stock', 'err')
  } finally { loading.value = false }
}

async function submitTransfer() {
  if (transfer.value.product_id === 0) return setMsg('Please select a product', 'err')
  if (transfer.value.to_branch_id === 0) return setMsg('Please select target branch', 'err')
  if (transfer.value.to_branch_id === transfer.value.from_branch_id) return setMsg('Cannot transfer to same branch', 'err')
  
  loading.value = true
  try {
    await api.post('/inventory/transfer-stock', transfer.value)
    setMsg('Transfer completed successfully', 'ok')
    transfer.value.product_id = 0
    transfer.value.to_branch_id = 0
    transfer.value.quantity = 0
  } catch (e: any) {
    setMsg(e?.response?.data?.message ?? 'Error during transfer', 'err')
  } finally { loading.value = false }
}

onMounted(loadData)
</script>

<style scoped>
.stack { display: grid; gap: 16px; max-width: 600px; }
.tabs { display: flex; gap: 10px; background: rgba(255,255,255,.03); padding: 8px; border-radius: 12px; }
.tabs button {
  flex: 1; padding: 10px; border-radius: 8px; border: none; background: transparent; color: #e5e7eb; cursor: pointer; transition: .2s;
}
.tabs button.active { background: rgba(99, 102, 241, 0.2); color: #818cf8; font-weight: 600; }

.form-box { padding: 20px; display: grid; gap: 16px; }
h3 { margin: 0; font-size: 16px; }
.muted { color: rgba(229,231,235,.6); font-size: 13px; margin: -10px 0 0 0; }
.rows { display: grid; gap: 14px; }
.field { display: flex; flex-direction: column; gap: 6px; }
.field span { font-size: 12px; color: rgba(229,231,235,.7); }
input, select {
  padding: 10px 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,.12); background: rgba(0,0,0,.3); color: #fff;
}
select option { background: #1f2937; color: #fff; }
.btn.primary { margin-top: 10px; padding: 12px; border-radius: 10px; background: #6366f1; color: #fff; border: none; cursor: pointer; font-weight: 600; }
.btn:disabled { opacity: .5; }

.ok { color: #4ade80; font-size: 14px; }
.err { color: #f87171; font-size: 14px; }

.card {
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 14px;
}
</style>

