<script setup lang="ts">
import type { Media, Project } from '~/types'
import type { ProjectFormState } from '~/components/AdminProjectEditor.vue'

definePageMeta({
  layout: 'admin',
  middleware: 'auth',
})

const route = useRoute()
const projectId = route.params.id as string
const { get, put, del } = useApi()
const { confirm } = useConfirm()

const { data: projectRes, pending } = await useAsyncData(
  `admin-project-${projectId}`,
  () => get<{ data: Project }>(`/admin/projects/${projectId}`),
)

const form = reactive<ProjectFormState>({
  title: '',
  slug: '',
  summary: '',
  body: '',
  role: '',
  repository_url: '',
  demo_url: '',
  started_at: '',
  completed_at: '',
  status: 'draft',
  is_featured: false,
  cover_media_id: null,
  seo_title: '',
  seo_description: '',
})
const cover = ref<Media | null>(null)

function fill(p: Project) {
  form.title = p.title
  form.slug = p.slug
  form.summary = p.summary
  form.body = p.body || ''
  form.role = p.role || ''
  form.repository_url = p.repository_url || ''
  form.demo_url = p.demo_url || ''
  form.started_at = p.started_at?.slice(0, 10) || ''
  form.completed_at = p.completed_at?.slice(0, 10) || ''
  form.status = p.status || 'draft'
  form.is_featured = Boolean(p.is_featured)
  form.cover_media_id = p.cover?.id ?? null
  form.seo_title = p.seo_title || ''
  form.seo_description = p.seo_description || ''
  cover.value = p.cover || null
}

// Studio pages render in the browser only, so the data may arrive after setup.
watch(() => projectRes.value?.data, (p) => { if (p) fill(p) }, { immediate: true })

const saving = ref(false)
const message = ref(route.query.saved ? 'Project created.' : '')
const errorMessage = ref('')

async function submit(targetStatus: 'draft' | 'published') {
  form.status = targetStatus
  saving.value = true
  errorMessage.value = ''
  message.value = ''
  try {
    const res = await put<{ data: Project }>(`/admin/projects/${projectId}`, form)
    fill(res.data)
    message.value = 'Project saved.'
  } catch (err: any) {
    errorMessage.value = err.data?.message || 'Failed to save project.'
  } finally {
    saving.value = false
  }
}

async function deleteProject() {
  const ok = await confirm({
    title: 'Delete Project',
    message: `Are you sure you want to permanently delete "${form.title || 'this project'}"? This action cannot be undone.`,
    confirmText: 'Delete Project',
    cancelText: 'Cancel',
    variant: 'danger',
  })
  if (!ok) return
  saving.value = true
  try {
    await del(`/admin/projects/${projectId}`)
    await navigateTo('/studio-portal/projects')
  } catch {
    errorMessage.value = 'Failed to delete project.'
    saving.value = false
  }
}

useHead({
  title: computed(() => `Edit: ${form.title || 'Project'} — Studio Admin`),
})
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <NuxtLink to="/studio-portal/projects" class="text-xs font-semibold text-gray-500 hover:text-gray-900">
        &larr; All projects
      </NuxtLink>
      <div class="flex items-center gap-3">
        <NuxtLink
          v-if="form.slug && form.status === 'published'"
          :to="`/projects/${form.slug}`"
          target="_blank"
          class="px-3.5 py-2 rounded-xl border border-gray-200 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
        >
          View Live &nearr;
        </NuxtLink>
        <button type="button" class="btn-danger" @click="deleteProject">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          <span>Delete Project</span>
        </button>
      </div>
    </div>

    <AdminProjectEditor
      :model-value="form"
      :cover="cover"
      :saving="saving"
      :loading="pending"
      :is-edit="true"
      :message="message"
      :error-message="errorMessage"
      @submit="submit"
      @cancel="navigateTo('/studio-portal/projects')"
      @update:cover="cover = $event"
    />
  </div>
</template>
