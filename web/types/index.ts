export interface User {
  id: number
  name: string
  email: string
}

export interface Category {
  id: number
  name: string
  slug: string
  description: string | null
  posts_count?: number
}

export interface Tag {
  id: number
  name: string
  slug: string
  posts_count?: number
}

export interface Media {
  id: number
  path: string
  url: string
  original_name: string
  mime_type: string
  size_bytes: number
  width: number | null
  height: number | null
  alt_text: string | null
  caption: string | null
  created_at?: string
}

export interface Post {
  id: number
  title: string
  slug: string
  excerpt: string | null
  body?: string
  status: 'draft' | 'scheduled' | 'published' | 'archived'
  is_featured: boolean
  reading_time: number
  views_count?: number
  published_at: string | null
  scheduled_at?: string | null
  seo_title?: string | null
  seo_description?: string | null
  canonical_url?: string | null
  created_at: string
  updated_at: string
  author?: {
    id: number
    name: string
  }
  category?: Category | null
  cover?: Media | null
  tags?: Tag[]
}

export interface PostRevision {
  id: number
  post_id: number
  title: string
  body: string
  excerpt: string | null
  revision_note: string | null
  created_at: string
  editor?: {
    id: number
    name: string
  }
}

export interface Technology {
  id: number
  name: string
  slug: string
}

export interface Project {
  id: number
  title: string
  slug: string
  summary: string
  body: string | null
  status: 'draft' | 'published' | 'archived'
  role: string | null
  started_at: string | null
  completed_at: string | null
  repository_url: string | null
  demo_url: string | null
  is_featured: boolean
  display_order: number
  views_count?: number
  seo_title: string | null
  seo_description: string | null
  created_at: string
  updated_at: string
  cover?: Media | null
  technologies?: Technology[]
}

export interface ContactMessage {
  id: number
  name: string
  email: string
  subject: string | null
  message: string
  status: 'unread' | 'read' | 'resolved'
  ip_address?: string | null
  created_at: string
}

export interface PaginationMeta {
  current_page: number
  from: number | null
  last_page: number
  per_page: number
  to: number | null
  total: number
}

export interface PaginatedResponse<T> {
  data: T[]
  meta?: PaginationMeta
  links?: {
    first?: string
    last?: string
    prev?: string | null
    next?: string | null
  }
}
