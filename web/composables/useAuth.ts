import type { User } from '~/types'

export const useAuth = () => {
  const user = useState<User | null>('auth_user', () => null)
  const token = useCookie<string | null>('auth_token', {
    maxAge: 60 * 60 * 24 * 7,
    sameSite: 'lax',
  })
  const { post, get } = useApi()

  const isAuthenticated = computed(() => !!token.value)

  async function login(credentials: Record<string, any>) {
    const res = await post<{ data: { token: string; user: User } }>('/auth/login', credentials)
    token.value = res.data.token
    user.value = res.data.user
    return res
  }

  async function logout() {
    try {
      if (token.value) {
        await post('/admin/auth/logout')
      }
    } catch {
      // Even if server request fails, clear local session
    } finally {
      token.value = null
      user.value = null
      await navigateTo('/studio-portal/login')
    }
  }

  async function fetchUser() {
    if (!token.value) {
      user.value = null
      return null
    }

    try {
      const res = await get<{ data: User }>('/admin/auth/me')
      user.value = res.data
      return res.data
    } catch {
      token.value = null
      user.value = null
      return null
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    login,
    logout,
    fetchUser,
  }
}
