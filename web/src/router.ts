import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from './stores/auth'
import LoginPage from './views/LoginPage.vue'
import DashboardPage from './views/DashboardPage.vue'
import BranchesPage from './views/BranchesPage.vue'
import ProductsPage from './views/ProductsPage.vue'
import InventoryPage from './views/InventoryPage.vue'
import OrdersPage from './views/OrdersPage.vue'
import OrderCreatePage from './views/OrderCreatePage.vue'
import InventoryAdjustmentPage from './views/InventoryAdjustmentPage.vue'
import ReportsPage from './views/ReportsPage.vue'

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/dashboard' },
    { path: '/login', component: LoginPage },
    { path: '/dashboard', component: DashboardPage },
    { path: '/branches', component: BranchesPage },
    { path: '/products', component: ProductsPage },
    { path: '/inventory', component: InventoryPage },
    { path: '/inventory/manage', component: InventoryAdjustmentPage },
    { path: '/orders', component: OrdersPage },
    { path: '/orders/new', component: OrderCreatePage },
    { path: '/reports', component: ReportsPage },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.booted) {
    await auth.bootstrap()
  }

  if (!auth.isAuthed && to.path !== '/login') return '/login'
  if (auth.isAuthed && to.path === '/login') return '/dashboard'

  // Super Admin: branches + products only (no orders)
  if (to.path === '/branches' && !auth.isSuperAdmin) return '/dashboard'
  if (to.path === '/products' && !auth.isSuperAdmin) return '/dashboard'
  if ((to.path === '/orders' || to.path === '/orders/new') && auth.isSuperAdmin) return '/dashboard'

  // Sales User: no inventory, no reports
  if (to.path.startsWith('/inventory') && auth.roleName === 'Sales User') return '/dashboard'
  if (to.path === '/reports' && auth.roleName === 'Sales User') return '/dashboard'

  return true
})

