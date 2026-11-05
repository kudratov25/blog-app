<script setup lang="ts">
import type { PaginatedResponse, Project } from '~/types'

definePageMeta({
  layout: 'admin',
  middleware: 'auth',
})

const { get, post: apiPost, put, del } = useApi()
const { confirm } = useConfirm()

const selectedStatus = ref('')

const { data: projectsData, refresh: refreshProjects, pending } = await useAsyncData(
  'admin-projects',
  () => {
    const params: Record<string, any> = { per_page: 25 }
    if (selectedStatus.value) params.status = selectedStatus.value
    return get<PaginatedResponse<Project>>('/admin/projects', params)
  },
  { watch: [selectedStatus] }
)

const projects = computed(() => projectsData.value?.data || [])

const form = reactive({
  title: '',
  slug: '',
  summary: '',
  body: '',
  role: '',
  repository_url: '',
  demo_url: '',
  status: 'draft' as 'draft' | 'published' | 'archived',
  is_featured: false,
})

const isCreating = ref(false)
const editingId = ref<number | null>(null)
const saving = ref(false)
const loadingProject = ref(false)
const errorMessage = ref('')
const viewMode = ref<'edit' | 'split' | 'preview'>('split')

function filterStatus(st: string) {
  selectedStatus.value = st
}

function startCreate() {
  editingId.value = null
  isCreating.value = true
  errorMessage.value = ''
  form.title = ''
  form.slug = ''
  form.summary = ''
  form.body = ''
  form.role = ''
  form.repository_url = ''
  form.demo_url = ''
  form.status = 'draft'
  form.is_featured = false
}

async function startEdit(p: Project) {
  isCreating.value = true
  editingId.value = p.id
  errorMessage.value = ''
  form.title = p.title
  form.slug = p.slug
  form.summary = p.summary
  form.body = p.body || ''
  form.role = p.role || ''
  form.repository_url = p.repository_url || ''
  form.demo_url = p.demo_url || ''
  form.status = (p.status as 'draft' | 'published' | 'archived') || 'draft'
  form.is_featured = Boolean(p.is_featured)

  // Fetch full project resource so case study body is always loaded
  loadingProject.value = true
  try {
    const res = await get<{ data: Project }>(`/admin/projects/${p.id}`)
    if (res?.data) {
      form.body = res.data.body || ''
      form.status = (res.data.status as 'draft' | 'published' | 'archived') || form.status
      form.is_featured = Boolean(res.data.is_featured)
    }
  } catch {
    // fallback to list item data
  } finally {
    loadingProject.value = false
  }
}

function cancel() {
  isCreating.value = false
  editingId.value = null
  errorMessage.value = ''
}

async function handleSave(targetStatus?: 'draft' | 'published') {
  if (targetStatus) {
    form.status = targetStatus
  }
  saving.value = true
  errorMessage.value = ''

  try {
    if (editingId.value) {
      await put(`/admin/projects/${editingId.value}`, form)
    } else {
      await apiPost('/admin/projects', form)
    }
    cancel()
    await refreshProjects()
  } catch (err: any) {
    errorMessage.value = err.data?.message || 'Failed to save project.'
  } finally {
    saving.value = false
  }
}

async function togglePublish(p: Project) {
  const newStatus = p.status === 'published' ? 'draft' : 'published'
  await put(`/admin/projects/${p.id}`, {
    title: p.title,
    summary: p.summary,
    status: newStatus,
  })
  await refreshProjects()
}

async function handleDelete(p: Project) {
  const ok = await confirm({
    title: 'Delete Project',
    message: `Are you sure you want to permanently delete "${p.title}"? This action cannot be undone.`,
    confirmText: 'Delete Project',
    cancelText: 'Cancel',
    variant: 'danger',
  })
  if (!ok) return

  await del(`/admin/projects/${p.id}`)
  await refreshProjects()
}

useHead({
  title: 'Projects — Studio Admin',
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Projects &amp; Case Studies</h1>
        <p class="text-xs text-gray-500 mt-0.5">Manage engineering case studies, drafts, and portfolio entries</p>
      </div>

      <button
        v-if="!isCreating"
        type="button"
        class="btn-primary"
        @click="startCreate"
      >
        + New Project
      </button>
    </div>

    <!-- Status Filter Bar (All / Published / Drafts) -->
    <div
      v-if="!isCreating"
      class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-gray-200/90 shadow-[0_2px_10px_rgba(0,0,0,0.02)]"
    >
      <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl text-xs w-fit">
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

      <div class="text-xs text-gray-400 font-medium">
        Showing {{ projects.length }} project{{ projects.length === 1 ? '' : 's' }}
      </div>
    </div>

    <!-- Comfortable Project Editor -->
    <div v-if="isCreating" class="space-y-5">
      <div class="bg-white rounded-3xl border border-gray-200/90 p-6 sm:p-8 space-y-5 shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-4">
          <div class="flex items-center gap-3">
            <h2 class="text-base font-bold text-gray-900">
              {{ editingId ? 'Edit Project Case Study' : 'New Project Case Study' }}
            </h2>
            <span
              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider"
              :class="form.status === 'published' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : 'bg-amber-50 text-amber-700 border border-amber-200/60'"
            >
              <span class="w-1.5 h-1.5 rounded-full" :class="form.status === 'published' ? 'bg-emerald-500' : 'bg-amber-500'" />
              {{ form.status }}
            </span>
          </div>

          <div class="flex items-center gap-2.5">
            <button
              type="button"
              class="btn-secondary"
              @click="cancel"
            >
              Cancel
            </button>

            <button
              type="button"
              :disabled="saving"
              class="btn-secondary"
              @click="handleSave('draft')"
            >
              {{ saving && form.status === 'draft' ? 'Saving...' : 'Save as Draft' }}
            </button>

            <button
              type="button"
              :disabled="saving"
              class="btn-primary"
              @click="handleSave('published')"
            >
              {{ saving && form.status === 'published' ? 'Publishing...' : (editingId && form.status === 'published' ? 'Update Published' : 'Publish Project') }}
            </button>
          </div>
        </div>

        <div v-if="errorMessage" class="p-3.5 bg-red-50 border border-red-200 text-red-700 text-xs rounded-2xl font-semibold">
          {{ errorMessage }}
        </div>

        <div class="space-y-4">
          <input
            v-model="form.title"
            type="text"
            required
            placeholder="Project Title..."
            class="w-full border-0 p-0 text-2xl font-bold text-gray-900 placeholder-gray-300 focus:ring-0 focus:outline-none"
          >

          <textarea
            v-model="form.summary"
            rows="2"
            required
            placeholder="Short summary of the project and its impact..."
            class="w-full border-0 p-0 text-sm text-gray-600 placeholder-gray-400 focus:ring-0 focus:outline-none resize-none"
          />

          <div class="grid grid-cols-1 md:grid-cols-4 gap-3 pt-4 border-t border-gray-100">
            <div>
              <label class="block text-[11px] font-semibold text-gray-500 mb-1">Role</label>
              <input
                v-model="form.role"
                type="text"
                class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none"
                placeholder="Lead Architect"
              >
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-gray-500 mb-1">Slug</label>
              <input
                v-model="form.slug"
                type="text"
                class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs font-mono bg-gray-50 focus:bg-white focus:outline-none"
                placeholder="auto-generated"
              >
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-gray-500 mb-1">GitHub URL</label>
              <input
                v-model="form.repository_url"
                type="url"
                class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none"
                placeholder="https://github.com/..."
              >
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-gray-500 mb-1">Live Demo URL</label>
              <input
                v-model="form.demo_url"
                type="url"
                class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none"
                placeholder="https://..."
              >
            </div>
          </div>

          <div class="flex items-center gap-6 pt-2">
            <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-700 cursor-pointer select-none">
              <input
                v-model="form.is_featured"
                type="checkbox"
                class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
              >
              <span>Feature on Homepage &amp; Projects Top</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Case Study Markdown Editor Card -->
      <div class="bg-white rounded-3xl border border-gray-200/90 overflow-hidden shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
        <div class="bg-gray-50 border-b border-gray-200/80 px-6 py-2.5 flex items-center justify-between">
          <span class="text-xs font-bold text-gray-700">
            Case Study Body (Markdown)
            <span v-if="loadingProject" class="ml-2 text-gray-400 font-normal">Loading content...</span>
          </span>
          <div class="flex items-center bg-gray-200/70 p-0.5 rounded-xl text-xs">
            <button
              type="button"
              class="px-3 py-1 rounded-lg font-semibold"
              :class="viewMode === 'edit' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600'"
              @click="viewMode = 'edit'"
            >
              Write
            </button>
            <button
              type="button"
              class="px-3 py-1 rounded-lg font-semibold"
              :class="viewMode === 'split' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600'"
              @click="viewMode = 'split'"
            >
              Split
            </button>
            <button
              type="button"
              class="px-3 py-1 rounded-lg font-semibold"
              :class="viewMode === 'preview' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600'"
              @click="viewMode = 'preview'"
            >
              Preview
            </button>
          </div>
        </div>

        <div
          class="grid divide-y lg:divide-y-0 lg:divide-x divide-gray-100"
          :class="viewMode === 'split' ? 'grid-cols-1 lg:grid-cols-2' : 'grid-cols-1'"
        >
          <div v-show="viewMode !== 'preview'" class="flex flex-col">
            <textarea
              v-model="form.body"
              rows="18"
              class="w-full flex-1 border-0 focus:ring-0 p-6 sm:p-8 text-[15px] font-mono text-gray-800 leading-[1.8] resize-y focus:outline-none"
              placeholder="Write the architectural case study in Markdown..."
            />
          </div>
          <div v-show="viewMode !== 'edit'" class="p-6 sm:p-8 bg-gray-50/40 overflow-y-auto max-h-[600px]">
            <MarkdownRenderer :content="form.body || '*Case study preview...*'" />
          </div>
        </div>
      </div>
    </div>

    <!-- Projects Table -->
    <div class="bg-white rounded-3xl border border-gray-200/90 overflow-hidden shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-gray-50 border-b border-gray-200/80 text-xs uppercase font-semibold text-gray-500">
            <tr>
              <th class="px-6 py-4">Project</th>
              <th class="px-6 py-4">Role</th>
              <th class="px-6 py-4">Status</th>
              <th class="px-6 py-4">Reads</th>
              <th class="px-6 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="p in projects" :key="p.id" class="hover:bg-gray-50/80 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <button
                    type="button"
                    class="font-bold text-gray-900 hover:text-indigo-600 transition-colors text-left text-[15px]"
                    @click="startEdit(p)"
                  >
                    {{ p.title }}
                  </button>
                  <span
                    v-if="p.is_featured"
                    class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-600 text-[10px] font-bold uppercase tracking-wider"
                  >
                    Featured
                  </span>
                </div>
                <div class="text-xs font-normal text-gray-500 mt-0.5 line-clamp-1">{{ p.summary }}</div>
              </td>
              <td class="px-6 py-4 text-xs font-medium text-gray-600 whitespace-nowrap">
                {{ p.role || '—' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider"
                  :class="p.status === 'published' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : 'bg-amber-50 text-amber-700 border border-amber-200/60'"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="p.status === 'published' ? 'bg-emerald-500' : 'bg-amber-500'" />
                  {{ p.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600">
                  <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <span>{{ (p.views_count ?? 0).toLocaleString() }}</span>
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-2.5">
                  <button
                    type="button"
                    class="btn-secondary"
                    @click="togglePublish(p)"
                  >
                    {{ p.status === 'published' ? 'Unpublish' : 'Publish' }}
                  </button>

                  <button
                    type="button"
                    class="btn-edit"
                    @click="startEdit(p)"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Edit</span>
                  </button>

                  <button
                    type="button"
                    class="btn-danger"
                    @click="handleDelete(p)"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Delete</span>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!projects.length && !pending">
              <td colspan="5" class="px-6 py-12 text-center text-xs text-gray-400">
                No projects found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
