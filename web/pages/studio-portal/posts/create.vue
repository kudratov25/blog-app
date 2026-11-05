<script setup lang="ts">
import type { Category, Post, Tag } from '~/types'

definePageMeta({
  layout: 'admin',
  middleware: 'auth',
})

const { get, post: apiPost } = useApi()

const [{ data: categoriesRes }, { data: tagsRes }] = await Promise.all([
  useAsyncData('admin-form-categories', () => get<{ data: Category[] }>('/admin/categories')),
  useAsyncData('admin-form-tags', () => get<{ data: Tag[] }>('/admin/tags')),
])

const categories = computed(() => categoriesRes.value?.data || [])
const tagsList = ref<Tag[]>(tagsRes.value?.data || [])

watch(() => tagsRes.value?.data, (val) => {
  if (val) tagsList.value = [...val]
})

const form = reactive({
  title: '',
  slug: '',
  excerpt: '',
  body: '',
  category_id: '' as string | number,
  tags: [] as number[],
  status: 'draft',
  is_featured: false,
  seo_title: '',
  seo_description: '',
  revision_note: 'Initial post creation',
})

const saving = ref(false)
const errorMessage = ref('')

function onTagCreated(tag: Tag) {
  if (!tagsList.value.some(t => t.id === tag.id)) {
    tagsList.value.push(tag)
  }
}

async function submit(targetStatus?: string) {
  if (targetStatus) {
    form.status = targetStatus
  }

  saving.value = true
  errorMessage.value = ''

  try {
    const payload = {
      ...form,
      category_id: form.category_id ? Number(form.category_id) : null,
    }
    const res = await apiPost<{ data: Post }>('/admin/posts', payload)
    await navigateTo(`/studio-portal/posts/${res.data.id}/edit`)
  } catch (err: any) {
    errorMessage.value = err.data?.message || 'Failed to save post. Please check required fields.'
  } finally {
    saving.value = false
  }
}

useHead({
  title: 'New Article — Studio Admin',
})
</script>

<template>
  <AdminPostEditor
    :model-value="form"
    :categories="categories"
    :tags="tagsList"
    :saving="saving"
    :error-message="errorMessage"
    @submit="submit"
    @tag-created="onTagCreated"
  />
</template>
