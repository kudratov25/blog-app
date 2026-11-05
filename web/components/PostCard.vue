<script setup lang="ts">
import type { Post } from '~/types'

const props = defineProps<{
  post: Post
  featured?: boolean
}>()

const formattedDate = computed(() => {
  if (!props.post.published_at) return null
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date(props.post.published_at))
})
</script>

<template>
  <article class="group relative bg-white rounded-2xl border border-gray-200/90 p-6 sm:p-7 shadow-[0_2px_10px_rgba(0,0,0,0.02)] hover:shadow-[0_8px_24px_rgba(0,0,0,0.06)] hover:border-gray-300 transition-all flex flex-col justify-between">
    <div>
      <div class="flex items-center justify-between gap-2 mb-4">
        <span
          v-if="post.category"
          class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-800 text-xs font-semibold"
        >
          {{ post.category.name }}
        </span>
        <time v-if="formattedDate" class="text-xs text-gray-400 font-medium" :datetime="post.published_at || ''">
          {{ formattedDate }}
        </time>
      </div>

      <h3 class="text-lg sm:text-xl font-bold text-gray-900 group-hover:text-gray-600 transition-colors leading-snug mb-2.5">
        <NuxtLink :to="`/articles/${post.slug}`">
          <span class="absolute inset-0 z-10" />
          {{ post.title }}
        </NuxtLink>
      </h3>

      <p v-if="post.excerpt" class="text-gray-600 text-sm leading-relaxed line-clamp-3 mb-5">
        {{ post.excerpt }}
      </p>
    </div>

    <div class="pt-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500 font-medium">
      <div class="flex items-center gap-3">
        <span>{{ post.reading_time || 5 }} min read</span>
        <span class="text-gray-300">&middot;</span>
        <span class="inline-flex items-center gap-1 text-gray-500">
          <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          <span>{{ (post.views_count ?? 0).toLocaleString() }} reads</span>
        </span>
      </div>
      <span class="text-gray-900 font-semibold group-hover:translate-x-0.5 transition-transform">
        Read article &rarr;
      </span>
    </div>
  </article>
</template>
