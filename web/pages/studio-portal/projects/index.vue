<script setup lang="ts">
import type { PaginatedResponse, Project } from '~/types'

definePageMeta({
  layout: 'admin',
  middleware: 'auth',
})

const { get, put, del } = useApi()
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

function filterStatus(st: string) {
  selectedStatus.value = st
}

async function togglePublish(p: Project) {
  const newStatus = p.status === 'published' ? 'draft' : 'published'
  await put(`/admin/projects/${p.id}`, {
    title: p.title,
    slug: p.slug,
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

      <NuxtLink to="/studio-portal/projects/create" class="btn-primary">
        + New Project
      </NuxtLink>
    </div>

    <!-- Status Filter Bar (All / Published / Drafts) -->
    <div
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
                  <NuxtLink
                    :to="`/studio-portal/projects/${p.id}/edit`"
                    class="font-bold text-gray-900 hover:text-indigo-600 transition-colors text-left text-[15px]"
                  >
                    {{ p.title }}
                  </NuxtLink>
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

                  <NuxtLink
                    :to="`/studio-portal/projects/${p.id}/edit`"
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
