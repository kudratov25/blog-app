export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig(event)
  const apiBase = config.apiBase || config.public?.apiBase || 'http://127.0.0.1:8000/api/v1'
  const reqUrl = getRequestURL(event)
  const origin = reqUrl.origin || 'https://blog.kudratov.uz'

  const urls: Array<{ loc: string; lastmod?: string; priority?: string }> = [
    { loc: `${origin}/`, priority: '1.0' },
    { loc: `${origin}/articles`, priority: '0.9' },
    { loc: `${origin}/projects`, priority: '0.9' },
    { loc: `${origin}/about`, priority: '0.8' },
    { loc: `${origin}/contact`, priority: '0.6' },
  ]

  try {
    const [postsRes, projectsRes, categoriesRes] = await Promise.all([
      $fetch<{ data: Array<{ slug: string; updated_at?: string; published_at?: string }> }>(`${apiBase}/posts`, {
        params: { per_page: 100 },
      }).catch(() => ({ data: [] })),
      $fetch<{ data: Array<{ slug: string; updated_at?: string }> }>(`${apiBase}/projects`, {
        params: { per_page: 100 },
      }).catch(() => ({ data: [] })),
      $fetch<{ data: Array<{ slug: string }> }>(`${apiBase}/categories`).catch(() => ({ data: [] })),
    ])

    for (const post of postsRes.data || []) {
      urls.push({
        loc: `${origin}/articles/${post.slug}`,
        lastmod: (post.updated_at || post.published_at || '').split('T')[0] || undefined,
        priority: '0.8',
      })
    }

    for (const project of projectsRes.data || []) {
      urls.push({
        loc: `${origin}/projects/${project.slug}`,
        lastmod: (project.updated_at || '').split('T')[0] || undefined,
        priority: '0.8',
      })
    }

    for (const cat of categoriesRes.data || []) {
      urls.push({
        loc: `${origin}/topics/${cat.slug}`,
        priority: '0.7',
      })
    }
  } catch {
    // Fallback to static core routes if API is temporarily unreachable
  }

  const xmlItems = urls
    .map((u) => {
      const lastmodTag = u.lastmod ? `\n    <lastmod>${u.lastmod}</lastmod>` : ''
      const priorityTag = u.priority ? `\n    <priority>${u.priority}</priority>` : ''
      return `  <url>\n    <loc>${u.loc}</loc>${lastmodTag}${priorityTag}\n  </url>`
    })
    .join('\n')

  const xml = `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${xmlItems}\n</urlset>`

  setHeader(event, 'Content-Type', 'application/xml; charset=utf-8')
  setHeader(event, 'Cache-Control', 'public, max-age=600, s-maxage=600')
  return xml
})
