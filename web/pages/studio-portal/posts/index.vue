<script setup lang="ts">
import type { PaginatedResponse, Post } from '~/types'

definePageMeta({
  layout: 'admin',
  middleware: 'auth',
})

const { get, post: apiPost, del } = useApi()
const { confirm } = useConfirm()

const selectedStatus = ref('')
const search = ref('')
const currentPage = ref(1)
const selectedIds = ref<number[]>([])
const deleting = ref(false)

const { data: postsData, refresh, pending } = await useAsyncData(
  'admin-posts-list',
  () => {
    const params: Record<string, any> = {
      page: currentPage.value,
      per_page: 15,
    }
    if (selectedStatus.value) params.status = selectedStatus.value
    if (search.value.trim()) params.search = search.value.trim()
    return get<PaginatedResponse<Post>>('/admin/posts', params)
  },
  { watch: [selectedStatus, currentPage] }
)

const posts = computed(() => postsData.value?.data || [])

const allSelected = computed(() => {
  return posts.value.length > 0 && posts.value.every(p => selectedIds.value.includes(p.id))
})

function toggleSelectAll() {
  if (allSelected.value) {
    selectedIds.value = []
  } else {
    selectedIds.value = posts.value.map(p => p.id)
  }
}

function toggleSelect(id: number) {
  const idx = selectedIds.value.indexOf(id)
  if (idx >= 0) {
    selectedIds.value.splice(idx, 1)
  } else {
    selectedIds.value.push(id)
  }
}

function filterStatus(st: string) {
  selectedStatus.value = st
  currentPage.value = 1
  selectedIds.value = []
}

let searchDebounce: ReturnType<typeof setTimeout> | null = null

function onSearch() {
  currentPage.value = 1
  selectedIds.value = []
  refresh()
}

watch(search, () => {
  if (searchDebounce) clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => {
    onSearch()
  }, 220)
})

async function handleDelete(p: Post) {
  const ok = await confirm({
    title: 'Delete Article',
    message: `Are you sure you want to permanently delete "${p.title}"? This action cannot be undone.`,
    confirmText: 'Delete Article',
    cancelText: 'Cancel',
    variant: 'danger',
  })
  if (!ok) return

  deleting.value = true
  try {
    await del(`/admin/posts/${p.id}`)
    selectedIds.value = selectedIds.value.filter(id => id !== p.id)
    await refresh()
  } finally {
    deleting.value = false
  }
}

async function handleBulkDelete() {
  if (!selectedIds.value.length) return
  const count = selectedIds.value.length
  const ok = await confirm({
    title: `Delete ${count} Selected Article${count > 1 ? 's' : ''}`,
    message: `Are you sure you want to permanently delete ${count} selected article${count > 1 ? 's' : ''}? This action cannot be undone.`,
    confirmText: `Delete ${count} Article${count > 1 ? 's' : ''}`,
    cancelText: 'Cancel',
    variant: 'danger',
  })
  if (!ok) return

  deleting.value = true
  try {
    for (const id of selectedIds.value) {
      await del(`/admin/posts/${id}`)
    }
    selectedIds.value = []
    await refresh()
  } finally {
    deleting.value = false
  }
}

async function togglePublish(p: Post) {
  if (p.status === 'published') {
    await apiPost(`/admin/posts/${p.id}/unpublish`)
  } else {
    await apiPost(`/admin/posts/${p.id}/publish`)
  }
  await refresh()
}

useHead({
  title: 'Articles — Studio Admin',
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Articles</h1>
        <p class="text-xs text-gray-500 mt-0.5">Create, edit, publish, or delete articles</p>
      </div>

      <div class="flex items-center gap-3">
        <button
          v-if="selectedIds.length > 0"
          type="button"
          :disabled="deleting"
          class="btn-danger"
          @click="handleBulkDelete"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          <span>{{ deleting ? 'Deleting...' : `Delete Selected (${selectedIds.length})` }}</span>
        </button>

        <NuxtLink
          to="/studio-portal/posts/create"
          class="btn-primary"
        >
          <span>+ New Article</span>
        </NuxtLink>
      </div>
    </div>

    <!-- Filters Bar -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-gray-200/90 shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
      <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl text-xs">
        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg font-semibold transition-all"
          :class="selectedStatus === '' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-900'"
          @click="filterStatus('')"
        >
          All
        </button>
        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg font-semibold transition-all"
          :class="selectedStatus === 'published' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-900'"
          @click="filterStatus('published')"
        >
          Published
        </button>
        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg font-semibold transition-all"
          :class="selectedStatus === 'draft' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-900'"
          @click="filterStatus('draft')"
        >
          Drafts
        </button>
      </div>

      <div class="relative w-full sm:w-64">
        <input
          v-model="search"
          type="text"
          placeholder="Search articles..."
          class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-gray-400"
          @keyup.enter="onSearch"
        >
      </div>
    </div>

    <!-- Articles Table -->
    <div class="bg-white rounded-3xl border border-gray-200/90 overflow-hidden shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-gray-50 border-b border-gray-200/80 text-xs uppercase font-semibold text-gray-500">
            <tr>
              <th class="pl-6 pr-2 py-4 w-10">
                <input
                  type="checkbox"
                  :checked="allSelected"
                  class="w-4 h-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900 cursor-pointer"
                  @change="toggleSelectAll"
                >
              </th>
              <th class="px-4 py-4">Article</th>
              <th class="px-4 py-4">Category</th>
              <th class="px-4 py-4">Status</th>
              <th class="px-4 py-4">Reads</th>
              <th class="px-4 py-4">Published</th>
              <th class="px-6 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="post in posts" :key="post.id" class="hover:bg-gray-50/70 transition-colors">
              <td class="pl-6 pr-2 py-4 align-middle">
                <input
                  type="checkbox"
                  :checked="selectedIds.includes(post.id)"
                  class="w-4 h-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900 cursor-pointer"
                  @change="toggleSelect(post.id)"
                >
              </td>
              <td class="px-4 py-4 align-middle">
                <NuxtLink :to="`/studio-portal/posts/${post.id}/edit`" class="font-bold text-gray-900 hover:text-gray-600 transition-colors block text-[15px]">
                  {{ post.title }}
                </NuxtLink>
                <div class="text-xs font-normal text-gray-400 mt-0.5 font-mono">/articles/{{ post.slug }}</div>
              </td>
              <td class="px-4 py-4 align-middle text-xs font-semibold text-gray-600">
                <span v-if="post.category" class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700">
                  {{ post.category.name }}
                </span>
                <span v-else class="text-gray-400">—</span>
              </td>
              <td class="px-4 py-4 align-middle">
                <span
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider"
                  :class="post.status === 'published' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : 'bg-amber-50 text-amber-700 border border-amber-200/60'"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="post.status === 'published' ? 'bg-emerald-500' : 'bg-amber-500'" />
                  {{ post.status }}
                </span>
              </td>
              <td class="px-4 py-4 align-middle whitespace-nowrap">
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600">
                  <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <span>{{ (post.views_count ?? 0).toLocaleString() }}</span>
                </span>
              </td>
              <td class="px-4 py-4 align-middle text-xs text-gray-500 whitespace-nowrap">
                {{ post.published_at ? new Date(post.published_at).toLocaleDateString() : '—' }}
              </td>
              <td class="px-6 py-4 align-middle">
                <div class="flex items-center justify-end gap-2.5">
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
                    title="Delete article"
                    class="btn-danger"
                    @click="handleDelete(post)"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Delete</span>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!posts.length && !pending">
              <td colspan="7" class="px-6 py-12 text-center text-xs text-gray-400">
                No articles found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <PaginationNav :meta="postsData?.meta" @change="(p) => currentPage = p" />
  </div>
</template>
