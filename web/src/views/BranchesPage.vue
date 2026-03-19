<template>
  <div class="stack">
    <div class="header-row">
      <h2>Branches</h2>
      <button v-if="auth.isSuperAdmin" class="btn primary" @click="showCreate = !showCreate">
        {{ showCreate ? 'Cancel' : 'Add Branch' }}
      </button>
    </div>

    <div v-if="showCreate && auth.isSuperAdmin" class="card create-form">
      <h3>Create New Branch</h3>
      <div class="form-grid">
        <label class="field">
          <span>Name</span>
          <input v-model="newBranch.name" placeholder="Branch Name" />
        </label>
        <label class="field">
          <span>Address</span>
          <input v-model="newBranch.address" placeholder="Address" />
        </label>
        <button class="btn primary" @click="createBranch" :disabled="creating">
          {{ creating ? 'Creating...' : 'Save Branch' }}
        </button>
      </div>
      <p v-if="createError" class="error">{{ createError }}</p>
    </div>

    <div class="row">
      <input v-model="search" placeholder="Search branches…" @keyup.enter="load" />
      <button class="btn" @click="load" :disabled="loading">Search</button>
    </div>

    <div v-if="loading" class="card">Loading…</div>
    <div v-else-if="error" class="card error">{{ error }}</div>

    <div v-else class="card">
      <table class="table">
        <thead>
          <tr><th>ID</th><th>Name</th><th>Address</th><th>Manager</th><th v-if="auth.isSuperAdmin"></th></tr>
        </thead>
        <tbody>
          <tr v-for="b in branches" :key="b.id">
            <td>{{ b.id }}</td>
            <td>{{ b.name }}</td>
            <td>{{ b.address }}</td>
            <td>{{ b.manager?.email ?? '-' }}</td>
            <td v-if="auth.isSuperAdmin" class="r">
              <button class="link danger" @click="deleteBranch(b.id)" :disabled="deleting === b.id">
                {{ deleting === b.id ? '...' : 'Delete' }}
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

type Branch = { id: number; name: string; address: string; manager?: { email: string } | null }

const search = ref('')
const loading = ref(false)
const error = ref<string | null>(null)
const branches = ref<Branch[]>([])

const showCreate = ref(false)
const creating = ref(false)
const createError = ref<string | null>(null)
const newBranch = ref({ name: '', address: '' })
const deleting = ref<number | null>(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    const res = await api.get('/branches', { params: { search: search.value, per_page: 50 } })
    branches.value = res.data?.data ?? []
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Failed to load branches'
  } finally {
    loading.value = false
  }
}

async function createBranch() {
  if (!newBranch.value.name) return (createError.value = 'Name is required')
  creating.value = true
  createError.value = null
  try {
    await api.post('/branches', newBranch.value)
    newBranch.value = { name: '', address: '' }
    showCreate.value = false
    load()
  } catch (e: any) {
    createError.value = e?.response?.data?.message ?? 'Failed to create branch'
  } finally {
    creating.value = false
  }
}

async function deleteBranch(id: number) {
  if (!confirm('Are you sure you want to delete this branch?')) return
  deleting.value = id
  try {
    await api.delete(`/branches/${id}`)
    load()
  } catch (e: any) {
    alert(e?.response?.data?.message ?? 'Failed to delete branch')
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
input {
  padding: 8px 12px;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(0,0,0,.25);
  color: #e5e7eb;
}
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
.error { color: #fecaca; }
</style>


