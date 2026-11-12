<script setup lang="ts">
import type { Media, Project } from '~/types'
import type { ProjectFormState } from '~/components/AdminProjectEditor.vue'

definePageMeta({
  layout: 'admin',
  middleware: 'auth',
})

const { post: apiPost } = useApi()

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
const saving = ref(false)
const errorMessage = ref('')

async function submit(targetStatus: 'draft' | 'published') {
  form.status = targetStatus
  saving.value = true
  errorMessage.value = ''
  try {
    const res = await apiPost<{ data: Project }>('/admin/projects', form)
    await navigateTo(`/studio-portal/projects/${res.data.id}/edit?saved=1`)
  } catch (err: any) {
    errorMessage.value = err.data?.message || 'Failed to create project.'
  } finally {
    saving.value = false
  }
}

useHead({ title: 'New Project — Studio Admin' })
</script>

<template>
  <AdminProjectEditor
    :model-value="form"
    :cover="cover"
    :saving="saving"
    :error-message="errorMessage"
    @submit="submit"
    @cancel="navigateTo('/studio-portal/projects')"
    @update:cover="cover = $event"
  />
</template>
