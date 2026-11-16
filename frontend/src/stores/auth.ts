import { ref } from 'vue'
import { defineStore } from 'pinia'
import axios from 'axios'
import { api } from '@/lib/api'

export interface AuthUser {
  id: number
  name: string
  email: string
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)
  const initialized = ref(false)

  async function initialize(): Promise<void> {
    if (initialized.value) return

    try {
      const response = await api.get<AuthUser>('/api/me')
      user.value = response.data
    } catch (error) {
      if (
        axios.isAxiosError(error) &&
        error.response?.status === 401
      ) {
        user.value = null
      } else {
        throw error
      }
    } finally {
      initialized.value = true
    }
  }

  async function login(
    email: string,
    password: string,
  ): Promise<void> {
    await api.get('/sanctum/csrf-cookie')

    await api.post('/auth/login', {
      email,
      password,
    })

    initialized.value = false
    await initialize()
  }

  async function logout(): Promise<void> {
    await api.post('/auth/logout')

    user.value = null
    initialized.value = true
  }

  return {
    user,
    initialized,
    initialize,
    login,
    logout,
  }
})
