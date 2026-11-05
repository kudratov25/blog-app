import type { User } from '~/types'

export function useApi() {
  const config = useRuntimeConfig()
  const token = useCookie<string | null>('auth_token')
  const user = useState<User | null>('auth_user', () => null)

  const baseURL = import.meta.server
    ? (config.apiBase || 'http://nginx/api/v1')
    : (config.public.apiBase || 'http://localhost:8000/api/v1')

  function getHeaders(): Record<string, string> {
    const headers: Record<string, string> = {
      Accept: 'application/json',
    }
    if (token.value) {
      headers.Authorization = `Bearer ${token.value}`
    }
    return headers
  }

  async function handleUnauthorized({ response }: { response: { status: number } }) {
    if (response?.status === 401) {
      token.value = null
      user.value = null
      if (import.meta.client) {
        const route = useRoute()
        if (route.path.startsWith('/studio-portal') && route.path !== '/studio-portal/login') {
          await navigateTo('/studio-portal/login', { replace: true })
        }
      }
    }
  }

  async function get<T>(url: string, params?: Record<string, any>): Promise<T> {
    return await $fetch<T>(`${baseURL}${url}`, {
      method: 'GET',
      headers: getHeaders(),
      params,
      onResponseError: handleUnauthorized,
    })
  }

  async function post<T>(url: string, body?: any): Promise<T> {
    return await $fetch<T>(`${baseURL}${url}`, {
      method: 'POST',
      headers: getHeaders(),
      body,
      onResponseError: handleUnauthorized,
    })
  }

  async function put<T>(url: string, body?: any): Promise<T> {
    return await $fetch<T>(`${baseURL}${url}`, {
      method: 'PUT',
      headers: getHeaders(),
      body,
      onResponseError: handleUnauthorized,
    })
  }

  async function patch<T>(url: string, body?: any): Promise<T> {
    return await $fetch<T>(`${baseURL}${url}`, {
      method: 'PATCH',
      headers: getHeaders(),
      body,
      onResponseError: handleUnauthorized,
    })
  }

  async function del<T>(url: string): Promise<T> {
    return await $fetch<T>(`${baseURL}${url}`, {
      method: 'DELETE',
      headers: getHeaders(),
      onResponseError: handleUnauthorized,
    })
  }

  return {
    baseURL,
    get,
    post,
    put,
    patch,
    del,
  }
}
