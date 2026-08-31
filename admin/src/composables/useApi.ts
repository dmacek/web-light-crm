import { ref } from 'vue'

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000'

export function useApi() {
  const loading = ref(false)
  const error = ref<string | null>(null)

  const getHeaders = (customHeaders: Record<string, string> = {}) => {
    const token = localStorage.getItem('access_token')
    const draftId = localStorage.getItem('session_draft_id')

    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...customHeaders,
    }

    if (token) {
      headers['Authorization'] = `Bearer ${token}`
    }
    if (draftId) {
      headers['session_draft_id'] = draftId
    }

    return headers
  }

  const request = async <T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<T> => {
    loading.value = true
    error.value = null

    try {
      const url = endpoint.startsWith('http') ? endpoint : `${API_BASE_URL}${endpoint}`
      const headers = getHeaders((options.headers as Record<string, string>) || {})

      const response = await fetch(url, {
        ...options,
        headers,
      })

      if (response.status === 204) {
        return {} as T
      }

      const data = await response.json()

      if (!response.ok) {
        const errorMsg = data?.error?.message || `Request failed with status ${response.status}`
        error.value = errorMsg
        throw new Error(errorMsg)
      }

      return data as T
    } catch (err: any) {
      error.value = err.message || 'Network error'
      throw err
    } finally {
      loading.value = false
    }
  }

  const get = <T>(endpoint: string) => request<T>(endpoint, { method: 'GET' })
  const post = <T>(endpoint: string, body?: any) =>
    request<T>(endpoint, {
      method: 'POST',
      body: body ? JSON.stringify(body) : undefined,
    })
  const put = <T>(endpoint: string, body?: any) =>
    request<T>(endpoint, {
      method: 'PUT',
      body: body ? JSON.stringify(body) : undefined,
    })
  const patch = <T>(endpoint: string, body?: any) =>
    request<T>(endpoint, {
      method: 'PATCH',
      body: body ? JSON.stringify(body) : undefined,
    })
  const del = <T>(endpoint: string) => request<T>(endpoint, { method: 'DELETE' })

  return {
    loading,
    error,
    request,
    get,
    post,
    put,
    patch,
    del,
    API_BASE_URL,
  }
}
