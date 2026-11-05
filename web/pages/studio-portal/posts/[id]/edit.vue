<script setup lang="ts">
import type { Category, Post, PostRevision, Tag } from '~/types'

definePageMeta({
  layout: 'admin',
  middleware: 'auth',
})

const route = useRoute()
const postId = route.params.id as string
const { get, put, post: apiPost, del } = useApi()
const { confirm } = useConfirm()

const [{ data: postRes }, { data: categoriesRes }, { data: tagsRes }, { data: revisionsRes, refresh: refreshRevisions }] = await Promise.all([
  useAsyncData(`admin-post-${postId}`, () => get<{ data: Post }>(`/admin/posts/${postId}`)),
  useAsyncData('admin-categories', () => get<{ data: Category[] }>('/admin/categories')),
  useAsyncData('admin-tags', () => get<{ data: Tag[] }>('/admin/tags')),
  useAsyncData(`admin-revisions-${postId}`, () => get<{ data: PostRevision[] }>(`/admin/posts/${postId}/revisions`)),
])

const post = computed(() => postRes.value?.data)
const categories = computed(() => categoriesRes.value?.data || [])
const tagsList = ref<Tag[]>(tagsRes.value?.data || [])
const revisions = computed(() => revisionsRes.value?.data || [])

watch(() => tagsRes.value?.data, (val) => {
  if (val) tagsList.value = [...val]
})

const form = reactive({
  title: post.value?.title || '',
  slug: post.value?.slug || '',
  excerpt: post.value?.excerpt || '',
  body: post.value?.body || '',
  category_id: (post.value?.category?.id || '') as string | number,
  tags: (post.value?.tags?.map(t => t.id) || []) as number[],
  status: post.value?.status || 'draft',
  is_featured: post.value?.is_featured || false,
  seo_title: post.value?.seo_title || '',
  seo_description: post.value?.seo_description || '',
  revision_note: '',
})

const activeTab = ref<'editor' | 'revisions'>('editor')
const saving = ref(false)
const message = ref('')
const errorMessage = ref('')

function onTagCreated(tag: Tag) {
  if (!tagsList.value.some(t => t.id === tag.id)) {
    tagsList.value.push(tag)
  }
}

async function submit(targetStatus?: string) {
  if (targetStatus) {
    form.status = targetStatus as any
  }

  saving.value = true
  errorMessage.value = ''
  message.value = ''

  try {
    const payload = {
      ...form,
      category_id: form.category_id ? Number(form.category_id) : null,
    }
    await put<{ data: Post }>(`/admin/posts/${postId}`, payload)
    message.value = 'Article saved successfully.'
    form.revision_note = ''
    await refreshRevisions()
  } catch (err: any) {
    errorMessage.value = err.data?.message || 'Failed to update article.'
  } finally {
    saving.value = false
  }
}

async function deleteArticle() {
  const ok = await confirm({
    title: 'Delete Article',
    message: `Are you sure you want to permanently delete "${form.title || 'this article'}"? This action cannot be undone.`,
    confirmText: 'Delete Article',
    cancelText: 'Cancel',
    variant: 'danger',
  })
  if (!ok) return

  saving.value = true
  try {
    await del(`/admin/posts/${postId}`)
    await navigateTo('/studio-portal/posts')
  } catch {
    errorMessage.value = 'Failed to delete article.'
    saving.value = false
  }
}

async function restoreRevision(rev: PostRevision) {
  const ok = await confirm({
    title: 'Restore Revision',
    message: `Restore the revision from ${new Date(rev.created_at).toLocaleString()}? Your current changes will be snapshotted first.`,
    confirmText: 'Restore Version',
    cancelText: 'Cancel',
    variant: 'default',
  })
  if (!ok) return

  saving.value = true
  try {
    const res = await apiPost<{ data: Post }>(`/admin/posts/${postId}/revisions/${rev.id}/restore`)
    form.title = res.data.title
    form.body = res.data.body || ''
    form.excerpt = res.data.excerpt || ''
    activeTab.value = 'editor'
    message.value = 'Revision restored successfully.'
    await refreshRevisions()
  } catch {
    errorMessage.value = 'Failed to restore revision.'
  } finally {
    saving.value = false
  }
}

useHead({
  title: `Edit: ${post.value?.title || 'Article'} — Studio Admin`,
})
</script>

<template>
  <div class="space-y-5">
    <!-- Mode Switcher (Editor vs Revisions) + Delete Action -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="inline-flex items-center bg-white border border-gray-200/90 p-1 rounded-2xl text-xs shadow-sm">
        <button
          type="button"
          class="px-4 py-1.5 rounded-xl font-semibold transition-all"
          :class="activeTab === 'editor' ? 'bg-gray-900 text-white' : 'text-gray-600 hover:text-gray-900'"
          @click="activeTab = 'editor'"
        >
          Article Editor
        </button>
        <button
          type="button"
          class="px-4 py-1.5 rounded-xl font-semibold transition-all"
          :class="activeTab === 'revisions' ? 'bg-gray-900 text-white' : 'text-gray-600 hover:text-gray-900'"
          @click="activeTab = 'revisions'"
        >
          Revisions ({{ revisions.length }})
        </button>
      </div>

      <div class="flex items-center gap-3">
        <NuxtLink
          v-if="form.slug"
          :to="`/articles/${form.slug}`"
          target="_blank"
          class="px-3.5 py-2 rounded-xl border border-gray-200 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
        >
          View Live &nearr;
        </NuxtLink>

        <button
          type="button"
          class="px-3.5 py-2 rounded-xl border border-red-100 bg-red-50/70 text-red-600 text-xs font-semibold hover:bg-red-600 hover:text-white hover:border-red-600 transition-all inline-flex items-center gap-1.5"
          @click="deleteArticle"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          <span>Delete Article</span>
        </button>
      </div>
    </div>

    <!-- Main Editor -->
    <div v-show="activeTab === 'editor'">
      <AdminPostEditor
        :model-value="form"
        :categories="categories"
        :tags="tagsList"
        :saving="saving"
        :is-edit="true"
        :message="message"
        :error-message="errorMessage"
        @submit="submit"
        @tag-created="onTagCreated"
      />
    </div>

    <!-- Revisions List View -->
    <div v-show="activeTab === 'revisions'" class="bg-white rounded-3xl border border-gray-200/90 p-6 sm:p-8 space-y-4 shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
      <h2 class="text-base font-bold text-gray-900">Revision History</h2>
      <div v-if="revisions.length" class="divide-y divide-gray-100">
        <div
          v-for="rev in revisions"
          :key="rev.id"
          class="py-4 flex items-center justify-between text-sm"
        >
          <div>
            <div class="font-semibold text-gray-900">
              {{ rev.revision_note || 'Revision snapshot' }}
            </div>
            <div class="text-xs text-gray-400 mt-0.5">
              Saved {{ new Date(rev.created_at).toLocaleString() }} by {{ rev.editor?.name || 'Admin' }}
            </div>
          </div>

          <button
            type="button"
            class="px-3.5 py-2 text-xs border border-gray-200 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
            @click="restoreRevision(rev)"
          >
            Restore this version
          </button>
        </div>
      </div>
      <div v-else class="text-xs text-gray-400 py-8 text-center">
        No revision snapshots recorded yet.
      </div>
    </div>
  </div>
</template>
