<script setup lang="ts">
import type { ContactMessage, Post } from '~/types'

definePageMeta({
  layout: 'admin',
  middleware: 'auth',
})

const { get, post: apiPost, del } = useApi()
const { confirm } = useConfirm()

interface DashboardData {
  stats: {
    published_posts: number
    draft_posts: number
    scheduled_posts: number
    total_projects: number
    unread_messages: number
    total_categories: number
    total_tags: number
    total_views?: number
    buffered_views?: number
  }
  recent_posts: Post[]
  recent_messages: ContactMessage[]
}

const { data: res, refresh } = await useAsyncData('admin-dashboard', () =>
  get<{ data: DashboardData }>('/admin/dashboard')
)

const stats = computed(() => res.value?.data.stats)
const recentPosts = computed(() => res.value?.data.recent_posts || [])
const recentMessages = computed(() => res.value?.data.recent_messages || [])

async function togglePublish(post: Post) {
  if (post.status === 'published') {
    await apiPost(`/admin/posts/${post.id}/unpublish`)
  } else {
    await apiPost(`/admin/posts/${post.id}/publish`)
  }
  await refresh()
}

const flushingViews = ref(false)
async function flushBufferedViews() {
  flushingViews.value = true
  try {
    await apiPost('/admin/dashboard/flush-views')
    await refresh()
  } finally {
    flushingViews.value = false
  }
}

async function deletePost(post: Post) {
  const ok = await confirm({
    title: 'Delete Article',
    message: `Are you sure you want to permanently delete "${post.title}"? This action cannot be undone.`,
    confirmText: 'Delete Article',
    cancelText: 'Cancel',
    variant: 'danger',
  })
  if (!ok) return

  await del(`/admin/posts/${post.id}`)
  await refresh()
}

useHead({
  title: 'Dashboard — Studio Admin',
})
</script>

<template>
  <div class="space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-900 tracking-tight">Dashboard</h1>
        <p class="text-xs text-gray-500 mt-0.5">Overview of your publication</p>
      </div>

      <NuxtLink to="/studio-portal/posts/create" class="btn-primary">
        <span>+ New Article</span>
      </NuxtLink>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
      <div class="p-5 rounded-2xl bg-white border border-gray-200/90 shadow-[0_2px_10px_rgba(0,0,0,0.02)] flex flex-col justify-between">
        <div class="flex items-center justify-between gap-2">
          <span class="text-xs font-medium text-gray-500">Total Reads</span>
          <button
            v-if="(stats?.buffered_views ?? 0) > 0"
            type="button"
            :disabled="flushingViews"
            title="Click to flush cached views to database now"
            class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white text-[10px] font-bold transition-colors"
            @click="flushBufferedViews"
          >
            +{{ stats?.buffered_views }} cached &middot; Sync
          </button>
        </div>
        <div class="text-2xl font-bold text-indigo-600 mt-1">{{ (stats?.total_views ?? 0).toLocaleString() }}</div>
      </div>
      <div class="p-5 rounded-2xl bg-white border border-gray-200/90 shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
        <div class="text-xs font-medium text-gray-500">Published</div>
        <div class="text-2xl font-bold text-gray-900 mt-1">{{ stats?.published_posts ?? 0 }}</div>
      </div>
      <div class="p-5 rounded-2xl bg-white border border-gray-200/90 shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
        <div class="text-xs font-medium text-gray-500">Drafts</div>
        <div class="text-2xl font-bold text-gray-900 mt-1">{{ stats?.draft_posts ?? 0 }}</div>
      </div>
      <div class="p-5 rounded-2xl bg-white border border-gray-200/90 shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
        <div class="text-xs font-medium text-gray-500">Projects</div>
        <div class="text-2xl font-bold text-gray-900 mt-1">{{ stats?.total_projects ?? 0 }}</div>
      </div>
      <div class="p-5 rounded-2xl bg-white border border-gray-200/90 shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
        <div class="text-xs font-medium text-gray-500">Unread Messages</div>
        <div class="text-2xl font-bold text-gray-900 mt-1">{{ stats?.unread_messages ?? 0 }}</div>
      </div>
    </div>

    <!-- Recent Posts & Messages -->
    <div class="space-y-6">
      <div class="bg-white rounded-3xl border border-gray-200/90 overflow-hidden shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 class="text-sm font-bold text-gray-900">Recent Articles</h2>
          <NuxtLink to="/studio-portal/posts" class="text-xs font-semibold text-gray-500 hover:text-gray-900">
            Manage all &rarr;
          </NuxtLink>
        </div>
        <div v-if="recentPosts.length" class="divide-y divide-gray-100">
          <div
            v-for="post in recentPosts"
            :key="post.id"
            class="px-6 py-4 hover:bg-gray-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-sm transition-colors"
          >
            <div class="min-w-0 flex-1">
              <NuxtLink :to="`/studio-portal/posts/${post.id}/edit`" class="font-bold text-gray-900 hover:text-gray-600 truncate block text-sm">
                {{ post.title }}
              </NuxtLink>
              <div class="text-xs text-gray-400 mt-0.5 flex items-center gap-2 flex-wrap">
                <span>{{ post.category?.name || 'Uncategorized' }}</span>
                <span>&middot;</span>
                <span class="uppercase font-semibold text-[11px]" :class="post.status === 'published' ? 'text-emerald-600' : 'text-amber-600'">
                  {{ post.status }}
                </span>
                <span>&middot;</span>
                <span class="inline-flex items-center gap-1 text-gray-500 font-medium">
                  <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <span>{{ (post.views_count ?? 0).toLocaleString() }} reads</span>
                </span>
              </div>
            </div>

            <div class="flex items-center gap-2.5 shrink-0">
              <button
                type="button"
                class="btn-secondary"
                @click="togglePublish(post)"
              >
                {{ post.status === 'published' ? 'Unpublish' : 'Publish' }}
              </button>

              <NuxtLink
                :to="`/studio-portal/posts/${post.id}/edit`"
                class="btn-edit"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                <span>Edit</span>
              </NuxtLink>

              <button
                type="button"
                class="btn-danger"
                @click="deletePost(post)"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>Delete</span>
              </button>
            </div>
          </div>
        </div>
        <div v-else class="p-8 text-center text-xs text-gray-400">
          No articles created yet.
        </div>
      </div>

      <div class="bg-white rounded-3xl border border-gray-200/90 overflow-hidden shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 class="text-sm font-bold text-gray-900">Recent Messages</h2>
          <NuxtLink to="/studio-portal/messages" class="text-xs font-semibold text-gray-500 hover:text-gray-900">
            View all &rarr;
          </NuxtLink>
        </div>
        <div v-if="recentMessages.length" class="divide-y divide-gray-100">
          <div
            v-for="msg in recentMessages"
            :key="msg.id"
            class="px-6 py-4 hover:bg-gray-50/70 flex items-center justify-between text-sm transition-colors"
          >
            <div class="min-w-0 pr-4">
              <div class="font-semibold text-gray-900 truncate text-xs">{{ msg.name }} ({{ msg.email }})</div>
              <div class="text-[11px] text-gray-500 mt-0.5 truncate">{{ msg.subject || msg.message }}</div>
            </div>
            <div class="flex items-center gap-3">
              <span
                class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider"
                :class="msg.status === 'unread' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600'"
              >
                {{ msg.status }}
              </span>
              <NuxtLink to="/studio-portal/messages" class="btn-secondary">
                Open
              </NuxtLink>
            </div>
          </div>
        </div>
        <div v-else class="p-8 text-center text-xs text-gray-400">
          No messages yet.
        </div>
      </div>
    </div>
  </div>
</template>
