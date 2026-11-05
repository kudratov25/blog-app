<script setup lang="ts">
import type { Tag } from '~/types'

definePageMeta({
  layout: 'admin',
  middleware: 'auth',
})

const { get, post: apiPost, put, del } = useApi()
const { confirm } = useConfirm()

const { data: res, refresh } = await useAsyncData('admin-tags-page', () =>
  get<{ data: Tag[] }>('/admin/tags')
)

const tags = computed(() => res.value?.data || [])

const form = reactive({
  name: '',
  slug: '',
})

const editingId = ref<number | null>(null)
const saving = ref(false)
const errorMessage = ref('')

function startEdit(t: Tag) {
  editingId.value = t.id
  form.name = t.name
  form.slug = t.slug
}

function cancelEdit() {
  editingId.value = null
  form.name = ''
  form.slug = ''
}

async function handleSave() {
  saving.value = true
  errorMessage.value = ''

  try {
    if (editingId.value) {
      await put(`/admin/tags/${editingId.value}`, form)
    } else {
      await apiPost('/admin/tags', form)
    }
    cancelEdit()
    await refresh()
  } catch (err: any) {
    errorMessage.value = err.data?.message || 'Failed to save tag.'
  } finally {
    saving.value = false
  }
}

async function handleDelete(t: Tag) {
  const ok = await confirm({
    title: 'Delete Tag',
    message: `Are you sure you want to delete the tag "#${t.name}"?`,
    confirmText: 'Delete Tag',
    cancelText: 'Cancel',
    variant: 'danger',
  })
  if (!ok) return

  await del(`/admin/tags/${t.id}`)
  await refresh()
}

useHead({
  title: 'Tags — Studio Admin',
})
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-bold text-gray-900">Tags</h1>
      <p class="text-xs text-gray-500 mt-0.5">Keywords for indexing and topic discovery</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Form (Create/Edit) -->
      <div class="bg-white p-6 rounded-3xl border border-gray-200/90 h-fit space-y-4 shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
        <h2 class="text-sm font-bold text-gray-900">
          {{ editingId ? 'Edit Tag' : 'Create Tag' }}
        </h2>

        <div v-if="errorMessage" class="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl font-semibold">
          {{ errorMessage }}
        </div>

        <form class="space-y-4" @submit.prevent="handleSave">
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Tag Name</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-gray-400"
              placeholder="PostgreSQL"
            >
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Slug (optional)</label>
            <input
              v-model="form.slug"
              type="text"
              class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-gray-400 font-mono"
              placeholder="postgresql"
            >
          </div>

          <div class="flex items-center gap-2.5 pt-2">
            <button
              type="submit"
              :disabled="saving"
              class="btn-primary"
            >
              {{ editingId ? 'Update Tag' : '+ Add Tag' }}
            </button>
            <button
              v-if="editingId"
              type="button"
              class="btn-secondary"
              @click="cancelEdit"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>

      <!-- Tags List Table -->
      <div class="lg:col-span-2 bg-white rounded-3xl border border-gray-200/90 overflow-hidden shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
        <table class="w-full text-left text-sm">
          <thead class="bg-gray-50 border-b border-gray-200/80 text-xs uppercase font-semibold text-gray-500">
            <tr>
              <th class="px-6 py-4">Tag</th>
              <th class="px-6 py-4">Slug</th>
              <th class="px-6 py-4">Articles</th>
              <th class="px-6 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="t in tags" :key="t.id" class="hover:bg-gray-50/70">
              <td class="px-6 py-4 font-bold text-gray-900">
                #{{ t.name }}
              </td>
              <td class="px-6 py-4 text-xs font-mono text-gray-500">
                {{ t.slug }}
              </td>
              <td class="px-6 py-4 text-xs font-semibold text-gray-600">
                {{ t.posts_count ?? 0 }}
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-2.5">
                  <button
                    type="button"
                    class="btn-edit"
                    @click="startEdit(t)"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Edit</span>
                  </button>
                  <button
                    type="button"
                    class="btn-danger"
                    @click="handleDelete(t)"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Delete</span>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!tags.length">
              <td colspan="4" class="px-6 py-12 text-center text-xs text-gray-400">
                No tags yet.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
