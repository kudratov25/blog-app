export default defineNuxtRouteMiddleware(async () => {
  const { user, token, fetchUser } = useAuth()

  if (!token.value) {
    return navigateTo('/studio-portal/login', { replace: true })
  }

  if (!user.value) {
    const currentUser = await fetchUser()
    if (!currentUser || !token.value) {
      return navigateTo('/studio-portal/login', { replace: true })
    }
  }
})
