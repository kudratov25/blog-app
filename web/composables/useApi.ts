import type { User } from '~/types'

export function useApi() {
  const config = useRuntimeConfig()
  const token = useCookie<string | null>('auth_token')
  const user = useState<User | null>('auth_user', () => null)
  // During SSR, pass the visitor's IP on so the API rate-limits per visitor
  const forwarded = import.meta.server ? useRequestHeaders(['x-forwarded-for']) : {}

  const baseURL = import.meta.server
    ? (config.apiBase || 'http://nginx/api/v1')
    : (config.public.apiBase || 'http://localhost:8000/api/v1')

  function getHeaders(): Record<string, string> {
    const headers: Record<string, string> = {
      Accept: 'application/json',
      ...forwarded,
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

  function request<T>(
    method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE',
    url: string,
    opts: { body?: any, params?: Record<string, any> } = {},
  ): Promise<T> {
    return $fetch<T, string>(`${baseURL}${url}`, {
      method,
      ...opts,
      headers: getHeaders(),
      onResponseError: handleUnauthorized,
    })
  }

  const get = <T>(url: string, params?: Record<string, any>) => request<T>('GET', url, { params })
  const post = <T>(url: string, body?: any) => request<T>('POST', url, { body })
  const put = <T>(url: string, body?: any) => request<T>('PUT', url, { body })
  const patch = <T>(url: string, body?: any) => request<T>('PATCH', url, { body })
  const del = <T>(url: string) => request<T>('DELETE', url)

  return {
    baseURL,
    get,
    post,
    put,
    patch,
    del,
  }
}
