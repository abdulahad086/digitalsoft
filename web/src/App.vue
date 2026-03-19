<template>
  <div class="app-shell">
    <header class="topbar">
      <div class="brand">Smart Inventory</div>
      <div class="spacer" />
      <div v-if="auth.isAuthed" class="user">
        <span class="muted">{{ auth.user?.email }}</span>
        <button class="btn" @click="logout" :disabled="auth.loading">Logout</button>
      </div>
    </header>

    <div class="layout">
      <aside v-if="auth.isAuthed" class="sidebar">
        <RouterLink class="nav" to="/dashboard">Dashboard</RouterLink>
        <!-- Admin only: branch & product management -->
        <RouterLink class="nav" to="/branches" v-if="auth.isSuperAdmin">Branches</RouterLink>
        <RouterLink class="nav" to="/products" v-if="auth.isSuperAdmin">Products</RouterLink>
        <!-- Admin + Manager: inventory & reports -->
        <RouterLink class="nav" to="/inventory" v-if="auth.roleName !== 'Sales User'">Inventory</RouterLink>
        <RouterLink class="nav" to="/inventory/manage" v-if="auth.roleName !== 'Sales User'">Adjust Stock</RouterLink>
        <RouterLink class="nav" to="/reports" v-if="auth.roleName !== 'Sales User'">Reports</RouterLink>
        <!-- Manager + Sales: orders -->
        <RouterLink class="nav" to="/orders/new" v-if="!auth.isSuperAdmin">Create Order</RouterLink>
        <RouterLink class="nav" to="/orders" v-if="!auth.isSuperAdmin">Orders</RouterLink>
      </aside>

      <main class="content">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'

const auth = useAuthStore()
const router = useRouter()

async function logout() {
  await auth.logout()
  router.push('/login')
}
</script>

<style scoped>
.app-shell {
  min-height: 100vh;
  background: #0b1220;
  color: #e5e7eb;
}
.topbar {
  height: 56px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(0, 0, 0, 0.2);
  backdrop-filter: blur(10px);
  position: sticky;
  top: 0;
  z-index: 10;
}
.brand {
  font-weight: 700;
  letter-spacing: 0.3px;
}
.spacer {
  flex: 1;
}
.user {
  display: flex;
  align-items: center;
  gap: 12px;
}
.muted {
  color: rgba(229, 231, 235, 0.7);
  font-size: 13px;
}
.layout {
  display: grid;
  grid-template-columns: 220px 1fr;
  min-height: calc(100vh - 56px);
}
.sidebar {
  padding: 14px;
  border-right: 1px solid rgba(255, 255, 255, 0.08);
}
.nav {
  display: block;
  padding: 10px 10px;
  border-radius: 10px;
  margin-bottom: 6px;
  text-decoration: none;
  color: rgba(229, 231, 235, 0.9);
}
.nav.router-link-active {
  background: rgba(99, 102, 241, 0.2);
  border: 1px solid rgba(99, 102, 241, 0.35);
}
.content {
  padding: 18px;
}
.btn {
  border: 1px solid rgba(255, 255, 255, 0.16);
  background: rgba(255, 255, 255, 0.06);
  color: #e5e7eb;
  padding: 8px 10px;
  border-radius: 10px;
  cursor: pointer;
}
.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
@media (max-width: 900px) {
  .layout {
    grid-template-columns: 1fr;
  }
  .sidebar {
    display: none;
  }
}
</style>

