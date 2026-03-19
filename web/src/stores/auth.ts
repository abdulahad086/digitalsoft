import { defineStore } from 'pinia'
import { api } from '../api'

type RoleName = 'Super Admin' | 'Branch Manager' | 'Sales User'

type User = {
  id: number
  name: string
  email: string
  role?: { id: number; name: RoleName }
  branch?: { id: number; name: string } | null
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    booted: false as boolean,
    loading: false as boolean,
    token: localStorage.getItem('token') as string | null,
    user: null as User | null,
  }),
  getters: {
    isAuthed: (s) => !!s.token,
    roleName: (s) => s.user?.role?.name ?? null,
    isSuperAdmin(): boolean {
      return this.roleName === 'Super Admin'
    },
  },
  actions: {
    clear() {
      this.token = null
      this.user = null
      localStorage.removeItem('token')
    },
    async bootstrap() {
      this.booted = true
      if (!this.token) return
      try {
        const { data } = await api.get('/me')
        this.user = data?.data ?? data
      } catch {
        this.clear()
      }
    },
    async login(email: string, password: string) {
      this.loading = true
      try {
        const { data } = await api.post('/auth/login', { email, password })
        this.token = data.token
        localStorage.setItem('token', this.token ?? '')
        this.user = data.user?.data ?? data.user
      } finally {
        this.loading = false
      }
    },
    async logout() {
      if (!this.token) return
      this.loading = true
      try {
        await api.post('/auth/logout')
      } finally {
        this.clear()
        this.loading = false
      }
    },
  },
})

