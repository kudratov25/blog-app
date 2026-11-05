<script setup lang="ts">
import type { Category, PaginatedResponse, Post } from '~/types'

const route = useRoute()
const router = useRouter()
const { get } = useApi()

const searchInput = ref((route.query.search as string) || '')
const selectedCategory = ref((route.query.category as string) || '')
const currentPage = ref(Number(route.query.page) || 1)

const { data: categories } = await useAsyncData('articles-categories', () =>
  get<{ data: Category[] }>('/categories')
)

const { data: postsData, refresh, pending } = await useAsyncData(
  'articles-list',
  () => {
    const params: Record<string, any> = {
      page: currentPage.value,
      per_page: 10,
    }
    if (selectedCategory.value) {
      params.category = selectedCategory.value
    }
    if (searchInput.value.trim()) {
      params.search = searchInput.value.trim()
    }
    return get<PaginatedResponse<Post>>('/posts', params)
  },
  { watch: [currentPage, selectedCategory] }
)

let searchDebounce: ReturnType<typeof setTimeout> | null = null

function onSearch() {
  currentPage.value = 1
  updateUrl()
  refresh()
}

watch(searchInput, () => {
  if (searchDebounce) clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => {
    onSearch()
  }, 220)
})

function onCategoryChange(slug: string) {
  selectedCategory.value = slug
  currentPage.value = 1
  updateUrl()
  refresh()
}

function onPageChange(page: number) {
  currentPage.value = page
  updateUrl()
  refresh()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function updateUrl() {
  const query: Record<string, any> = {}
  if (currentPage.value > 1) query.page = currentPage.value
  if (selectedCategory.value) query.category = selectedCategory.value
  if (searchInput.value.trim()) query.search = searchInput.value.trim()
  router.push({ query })
}

const { settings } = useSiteSettings()

useHead({
  title: () => settings.value?.author_name ? `Articles — ${settings.value.author_name}` : 'Articles',
})
</script>

<template>
  <div class="space-y-6">
    <!-- Bento Header & Filter Card -->
    <div class="bg-white rounded-3xl border border-gray-200/90 p-6 sm:p-8 shadow-[0_2px_12px_rgba(0,0,0,0.02)] space-y-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 mb-1.5">
          Articles
        </h1>
        <p class="text-gray-600 text-sm sm:text-base">
          Essays and technical notes on backend architecture, databases, and systems engineering.
        </p>
      </div>

      <div class="pt-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-1.5 flex-wrap">
          <button
            type="button"
            class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors"
            :class="selectedCategory === '' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 hover:text-gray-900'"
            @click="onCategoryChange('')"
          >
            All
          </button>
          <button
            v-for="cat in categories?.data"
            :key="cat.id"
            type="button"
            class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors"
            :class="selectedCategory === cat.slug ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 hover:text-gray-900'"
            @click="onCategoryChange(cat.slug)"
          >
            {{ cat.name }}
          </button>
        </div>

        <div class="w-full sm:w-64">
          <input
            v-model="searchInput"
            type="text"
            placeholder="Filter by keyword..."
            class="w-full px-3.5 py-2 rounded-xl text-xs bg-gray-50 border border-gray-200 text-gray-900 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-gray-400 focus:ring-0"
            @keyup.enter="onSearch"
          >
        </div>
      </div>
    </div>

    <!-- Listing -->
    <div v-if="pending" class="bento-card p-12 text-center text-sm text-gray-500">
      Loading articles...
    </div>
    <div
      v-else-if="postsData?.data && postsData.data.length"
      class="grid grid-cols-1 md:grid-cols-2 gap-5"
    >
      <PostCard v-for="post in postsData.data" :key="post.id" :post="post" />
    </div>
    <div v-else class="bento-card p-12 text-center text-sm text-gray-500">
      No articles found matching your filter.
    </div>

    <PaginationNav :meta="postsData?.meta" @change="onPageChange" />
  </div>
</template>
