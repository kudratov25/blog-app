export interface SiteSettings {
  site_title?: string
  site_subtitle?: string
  site_description?: string
  author_name?: string
  author_email?: string
  author_bio?: string
  author_about_extended?: string
  author_connect_text?: string
  author_github?: string
  author_linkedin?: string
  author_telegram?: string
}

export function useSiteSettings() {
  const { get } = useApi()
  const settingsState = useState<SiteSettings>('global-site-settings', () => ({}))

  async function fetchSiteSettings() {
    try {
      const res = await get<{ data: SiteSettings }>('/settings', { _t: Date.now() })
      if (res?.data) {
        settingsState.value = res.data
      }
    } catch {
      // fallback to current state
    }
    return settingsState.value
  }

  return {
    settings: settingsState,
    fetchSiteSettings,
    refreshSiteSettings: () => fetchSiteSettings(),
  }
}
