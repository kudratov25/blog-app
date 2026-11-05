<script setup lang="ts">
import type { Post } from '~/types'

const route = useRoute()
const slug = route.params.slug as string
const { get } = useApi()

const { data: postData, error } = await useAsyncData(`post-${slug}`, () =>
  get<{ data: Post }>(`/posts/${slug}`)
)

if (error.value || !postData.value?.data) {
  throw createError({ statusCode: 404, statusMessage: 'Article not found' })
}

const post = computed(() => postData.value!.data)

const formattedDate = computed(() => {
  if (!post.value.published_at) return null
  return new Intl.DateTimeFormat('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date(post.value.published_at))
})

const { settings } = useSiteSettings()

useHead({
  title: () => {
    const base = post.value.seo_title || post.value.title
    return settings.value?.author_name ? `${base} — ${settings.value.author_name}` : base
  },
  meta: [
    { name: 'description', content: post.value.seo_description || post.value.excerpt || '' },
  ],
})
</script>

<template>
  <article class="max-w-3xl mx-auto bg-white rounded-3xl border border-gray-200/90 p-6 sm:p-12 shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
    <div class="mb-6">
      <NuxtLink
        to="/articles"
        class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-gray-900 transition-colors"
      >
        &larr; Back to articles
      </NuxtLink>
    </div>

    <header class="mb-8 pb-8 border-b border-gray-100 space-y-4">
      <div class="flex items-center gap-2.5 text-xs text-gray-500 flex-wrap">
        <NuxtLink
          v-if="post.category"
          :to="`/topics/${post.category.slug}`"
          class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-800 font-semibold hover:bg-gray-200 transition-colors"
        >
          {{ post.category.name }}
        </NuxtLink>
        <time v-if="formattedDate" :datetime="post.published_at || ''" class="font-medium">{{ formattedDate }}</time>
        <span v-if="post.reading_time">&middot; {{ post.reading_time }} min read</span>
        <span>&middot;</span>
        <span class="inline-flex items-center gap-1 font-medium text-gray-600">
          <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          <span>{{ (post.views_count ?? 0).toLocaleString() }} reads</span>
        </span>
      </div>

      <h1 class="text-2xl sm:text-4xl font-bold tracking-tight text-gray-900 leading-tight">
        {{ post.title }}
      </h1>

      <p v-if="post.excerpt" class="text-base sm:text-lg text-gray-600 leading-relaxed">
        {{ post.excerpt }}
      </p>
    </header>

    <div>
      <MarkdownRenderer :content="post.body || ''" />
    </div>

    <footer v-if="post.tags && post.tags.length" class="mt-10 pt-6 border-t border-gray-100 flex items-center gap-2 flex-wrap">
      <span class="text-xs font-semibold text-gray-400 mr-1">Topics:</span>
      <span
        v-for="tag in post.tags"
        :key="tag.id"
        class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium"
      >
        {{ tag.name }}
      </span>
    </footer>
  </article>
</template>
