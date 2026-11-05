<script setup lang="ts">
import type { Post, Project } from '~/types'

const route = useRoute()
const router = useRouter()
const { get } = useApi()

const query = ref((route.query.q as string) || '')
const searching = ref(false)
const results = ref<{ posts: Post[]; projects: Project[] }>({
  posts: [],
  projects: [],
})

let debounceTimer: ReturnType<typeof setTimeout> | null = null

async function performSearch() {
  const term = query.value.trim()
  if (!term) {
    results.value = { posts: [], projects: [] }
    router.replace({ query: {} })
    return
  }

  searching.value = true
  try {
    const res = await get<{ data: { posts: Post[]; projects: Project[] } }>('/search', { q: term })
    results.value = res.data
    router.replace({ query: { q: term } })
  } finally {
    searching.value = false
  }
}

watch(query, () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    performSearch()
  }, 220)
})

onMounted(() => {
  if (query.value.trim()) {
    performSearch()
  }
})

const { settings } = useSiteSettings()

useHead({
  title: () => settings.value?.author_name ? `Search — ${settings.value.author_name}` : 'Search',
})
</script>

<template>
  <div class="space-y-6">
    <div class="bg-white rounded-3xl border border-gray-200/90 p-6 sm:p-8 shadow-[0_2px_12px_rgba(0,0,0,0.02)] space-y-5">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 mb-1">Search</h1>
        <p class="text-gray-600 text-sm">Find articles and case studies by keyword.</p>
      </div>

      <div class="relative flex items-center">
        <svg class="w-4 h-4 text-gray-400 absolute left-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>

        <input
          v-model="query"
          type="search"
          autofocus
          placeholder="Search articles and case studies..."
          class="w-full pl-11 pr-24 py-3 border border-gray-200 rounded-2xl text-sm bg-gray-50 text-gray-900 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-gray-400 focus:ring-0 transition-colors"
        >

        <div class="absolute right-3 flex items-center gap-2">
          <span v-if="searching" class="text-[11px] font-semibold text-indigo-600 animate-pulse">
            Searching...
          </span>
          <button
            v-if="query"
            type="button"
            class="px-2.5 py-1 rounded-lg bg-gray-200/70 hover:bg-gray-300 text-gray-600 text-xs font-semibold transition-colors"
            @click="query = ''"
          >
            Clear
          </button>
        </div>
      </div>
    </div>

    <div v-if="results.posts.length || results.projects.length" class="space-y-8">
      <section v-if="results.posts.length" class="space-y-4">
        <h2 class="text-lg font-bold text-gray-900 px-1">
          Matching Articles ({{ results.posts.length }})
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <PostCard v-for="post in results.posts" :key="post.id" :post="post" />
        </div>
      </section>

      <section v-if="results.projects.length" class="space-y-4">
        <h2 class="text-lg font-bold text-gray-900 px-1">
          Matching Case Studies ({{ results.projects.length }})
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <ProjectCard v-for="project in results.projects" :key="project.id" :project="project" />
        </div>
      </section>
    </div>

    <div v-else-if="query.trim().length >= 1 && !searching" class="bento-card p-12 text-center text-gray-500 text-sm">
      No results found for "{{ query }}".
    </div>
  </div>
</template>
