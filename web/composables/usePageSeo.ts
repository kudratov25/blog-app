interface PageSeoOptions {
  title: MaybeRefOrGetter<string>
  description?: MaybeRefOrGetter<string>
  image?: MaybeRefOrGetter<string | null | undefined>
  canonicalUrl?: MaybeRefOrGetter<string | null | undefined>
  type?: 'website' | 'article' | 'profile'
  publishedAt?: MaybeRefOrGetter<string | null | undefined>
  updatedAt?: MaybeRefOrGetter<string | null | undefined>
  jsonLd?: MaybeRefOrGetter<Record<string, any> | null | undefined>
}

export function usePageSeo(options: PageSeoOptions) {
  const route = useRoute()
  const reqUrl = useRequestURL()
  const { settings } = useSiteSettings()

  const origin = computed(() => reqUrl.origin || 'https://blog.kudratov.uz')

  const fullTitle = computed(() => toValue(options.title))
  const desc = computed(() => {
    const raw = toValue(options.description)
    if (raw) return raw
    return settings.value?.site_description || 'Engineering notes on backend architecture, databases, and scalable systems.'
  })

  const canonical = computed(() => {
    const custom = toValue(options.canonicalUrl)
    if (custom) return custom
    return `${origin.value}${route.path}`
  })

  const imageUrl = computed(() => {
    const img = toValue(options.image)
    if (!img) return `${origin.value}/avatar.webp`
    if (img.startsWith('http://') || img.startsWith('https://')) return img
    return `${origin.value}${img.startsWith('/') ? '' : '/'}${img}`
  })

  const hasCoverImage = computed(() => Boolean(toValue(options.image)))
  const siteName = computed(() => settings.value?.author_name || 'Sherzod Kudratov')

  useSeoMeta({
    title: fullTitle,
    description: desc,
    ogTitle: fullTitle,
    ogDescription: desc,
    ogType: options.type || 'website',
    ogUrl: canonical,
    ogSiteName: siteName,
    ogImage: imageUrl,
    twitterCard: () => hasCoverImage.value ? 'summary_large_image' : 'summary',
    twitterTitle: fullTitle,
    twitterDescription: desc,
    twitterImage: imageUrl,
    articlePublishedTime: () => options.type === 'article' ? (toValue(options.publishedAt) || undefined) : undefined,
    articleModifiedTime: () => options.type === 'article' ? (toValue(options.updatedAt) || undefined) : undefined,
    articleAuthor: () => options.type === 'article' && settings.value?.author_name ? [settings.value.author_name] : undefined,
  })

  useHead(() => {
    const ld = toValue(options.jsonLd)
    return {
      htmlAttrs: { lang: 'en' },
      link: [
        { rel: 'canonical', href: canonical.value },
      ],
      script: ld
        ? [
            {
              type: 'application/ld+json',
              innerHTML: JSON.stringify(ld),
            },
          ]
        : [],
    }
  })
}
