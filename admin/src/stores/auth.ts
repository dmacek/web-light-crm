import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { BusinessDTO } from '@/types'
import { useApi } from '@/composables/useApi'

const getStorageItem = (key: string): string | null => {
  if (typeof window !== 'undefined' && window.localStorage) {
    try {
      return window.localStorage.getItem(key)
    } catch {
      return null
    }
  }
  return null
}

const setStorageItem = (key: string, value: string): void => {
  if (typeof window !== 'undefined' && window.localStorage) {
    try {
      window.localStorage.setItem(key, value)
    } catch {}
  }
}

const removeStorageItem = (key: string): void => {
  if (typeof window !== 'undefined' && window.localStorage) {
    try {
      window.localStorage.removeItem(key)
    } catch {}
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(getStorageItem('access_token'))
  const refreshToken = ref<string | null>(getStorageItem('refresh_token'))
  const businessStr = getStorageItem('business')
  const business = ref<BusinessDTO | null>(
    businessStr ? JSON.parse(businessStr) : null
  )

  const isAuthenticated = computed(() => !!token.value)
  const api = useApi()

  const setAuth = (newAccessToken: string, newRefreshToken: string, businessData?: BusinessDTO) => {
    token.value = newAccessToken
    refreshToken.value = newRefreshToken
    setStorageItem('access_token', newAccessToken)
    setStorageItem('refresh_token', newRefreshToken)

    if (businessData) {
      business.value = businessData
      setStorageItem('business', JSON.stringify(businessData))
    }
  }

  const logout = () => {
    token.value = null
    refreshToken.value = null
    business.value = null
    removeStorageItem('access_token')
    removeStorageItem('refresh_token')
    removeStorageItem('business')
  }

  const refreshSession = async () => {
    if (!refreshToken.value) return false

    try {
      const data = await api.post<{ access_token: string; refresh_token: string }>(
        '/api/v1/auth/refresh-token',
        { refresh_token: refreshToken.value }
      )
      setAuth(data.access_token, data.refresh_token, business.value || undefined)
      return true
    } catch {
      logout()
      return false
    }
  }

  return {
    token,
    refreshToken,
    business,
    isAuthenticated,
    setAuth,
    logout,
    refreshSession,
  }
})
