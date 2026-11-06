<script setup lang="ts">
import type { Category, PaginatedResponse, Post } from '~/types'

const route = useRoute()
const slug = route.params.slug as string
const { get } = useApi()

interface CategoryDetailResponse {
  data: {
    category: Category
    posts: PaginatedResponse<Post>
  }
}

const { data: res, error } = await useAsyncData(`topic-${slug}`, () =>
  get<CategoryDetailResponse>(`/categories/${slug}`)
)

if (error.value || !res.value?.data) {
  throw createError({ statusCode: 404, statusMessage: 'Topic not found' })
}

const category = computed(() => res.value!.data.category)
const posts = computed(() => res.value!.data.posts.data || [])

const { settings } = useSiteSettings()

usePageSeo({
  title: () => settings.value?.author_name
    ? `${category.value.name} — ${settings.value.author_name}`
    : category.value.name,
  description: () => category.value.description || `Articles and engineering notes filed under ${category.value.name}.`,
})
</script>

<template>
  <div class="space-y-6">
    <header class="bg-white rounded-3xl border border-gray-200/90 p-6 sm:p-8 shadow-[0_2px_12px_rgba(0,0,0,0.02)] space-y-2">
      <NuxtLink to="/articles" class="text-xs font-semibold text-gray-500 hover:text-gray-900 transition-colors inline-block mb-1">
        &larr; All articles
      </NuxtLink>
      <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900">
        {{ category.name }}
      </h1>
      <p v-if="category.description" class="text-gray-600 text-sm sm:text-base leading-relaxed">
        {{ category.description }}
      </p>
    </header>

    <div v-if="posts.length" class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <PostCard v-for="post in posts" :key="post.id" :post="post" />
    </div>
    <div v-else class="bento-card p-12 text-center text-gray-500 text-sm">
      No articles published in this topic yet.
    </div>
  </div>
</template>
