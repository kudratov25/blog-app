/**
 * Counts a read from the browser, not from SSR.
 *
 * The beacon fires only after the page has been visible for READ_DELAY_MS,
 * so server-side renders, crawlers that don't run JS, and instant bounces
 * are never counted. The logged-in author's own visits are skipped too.
 */
const READ_DELAY_MS = 5000

export function useTrackView(type: 'posts' | 'projects', slug: string) {
  if (import.meta.server) return

  const { post } = useApi()
  const token = useCookie<string | null>('auth_token')

  let timer: ReturnType<typeof setTimeout> | null = null
  let sent = false

  const send = () => {
    if (sent || token.value || navigator.webdriver) return
    sent = true
    post(`/${type}/${encodeURIComponent(slug)}/view`).catch(() => {})
  }

  const schedule = () => {
    if (timer || sent || document.visibilityState !== 'visible') return
    timer = setTimeout(send, READ_DELAY_MS)
  }

  const onVisibility = () => {
    if (document.visibilityState === 'visible') {
      schedule()
    } else if (timer) {
      clearTimeout(timer)
      timer = null
    }
  }

  onMounted(() => {
    schedule()
    document.addEventListener('visibilitychange', onVisibility)
  })

  onBeforeUnmount(() => {
    if (timer) clearTimeout(timer)
    document.removeEventListener('visibilitychange', onVisibility)
  })
}
