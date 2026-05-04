import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import api, { ensureCsrfCookie } from '../lib/api'

export interface AuthUser {
  id: number
  name: string
  email: string
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)

  const isAuthenticated = computed(() => user.value !== null)

  async function login(email: string, password: string): Promise<void> {
    await ensureCsrfCookie()
    const { data } = await api.post('/login', { email, password })
    user.value = data.user
  }

  async function register(payload: {
    name: string
    email: string
    password: string
    password_confirmation: string
  }): Promise<void> {
    await ensureCsrfCookie()
    const { data } = await api.post('/register', payload)
    user.value = data.user
  }

  async function logout(): Promise<void> {
    await api.post('/logout')
    user.value = null
  }

  function setUser(value: AuthUser | null): void {
    user.value = value
  }

  return { user, isAuthenticated, login, register, logout, setUser }
})