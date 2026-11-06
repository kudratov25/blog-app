<script setup lang="ts">
import type { Category, PaginatedResponse, Post, Project } from '~/types'

const { get } = useApi()
const { settings, fetchSiteSettings } = useSiteSettings()

const [{ data: latestPosts }, { data: projectsData }, { data: categories }] = await Promise.all([
  useAsyncData('latest-posts', () => get<PaginatedResponse<Post>>('/posts', { per_page: 6 })),
  useAsyncData('home-projects', () => get<PaginatedResponse<Project>>('/projects', { per_page: 4 })),
  useAsyncData('home-categories', () => get<{ data: Category[] }>('/categories')),
  useAsyncData('home-settings', () => fetchSiteSettings()),
])

const posts = computed(() => latestPosts.value?.data || [])
const projects = computed(() => projectsData.value?.data || [])
// "Software Engineer · Backend & Systems" -> "Software Engineer"
const authorRole = computed(() => (settings.value?.site_subtitle || '').split('·')[0].trim())

usePageSeo({
  title: () => settings.value?.author_name
    ? `${settings.value.author_name} — ${settings.value.site_subtitle || 'Engineering Blog'}`
    : (settings.value?.site_title || 'Engineering Blog'),
  description: () => settings.value?.site_description || settings.value?.author_bio || '',
  type: 'website',
})
</script>

<template>
  <div class="space-y-10">
    <!-- Top Bento Hero Row -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-5">
      <!-- Main Intro Card (2 cols) -->
      <div class="lg:col-span-2 bg-white rounded-3xl border border-gray-200/90 p-7 sm:p-10 shadow-[0_2px_12px_rgba(0,0,0,0.02)] flex flex-col justify-between">
        <div class="space-y-4">
          <div v-if="settings.site_subtitle" class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-semibold">
            {{ settings.site_subtitle }}
          </div>
          <h1 class="text-2xl sm:text-4xl font-bold tracking-tight text-gray-900 leading-[1.2]">
            {{ settings.site_title || 'Engineering notes on resilient backends, databases, and scalable systems.' }}
          </h1>
          <p v-if="settings.site_description" class="text-gray-600 text-base leading-relaxed max-w-xl">
            {{ settings.site_description }}
          </p>
        </div>

        <div class="flex items-center gap-3 pt-8 flex-wrap">
          <NuxtLink
            to="/articles"
            class="px-5 py-2.5 rounded-xl bg-gray-900 text-white text-xs font-semibold hover:bg-gray-800 transition-colors"
          >
            Explore Articles &rarr;
          </NuxtLink>
          <NuxtLink
            to="/projects"
            class="px-5 py-2.5 rounded-xl bg-gray-100 text-gray-800 text-xs font-semibold hover:bg-gray-200 transition-colors"
          >
            View Case Studies
          </NuxtLink>
        </div>
      </div>

      <!-- Right Bento Card (Author & Connect from Backend Settings) -->
      <div class="theme-fixed bg-gray-900 text-white rounded-3xl p-7 sm:p-8 flex flex-col justify-between">
        <div class="space-y-5">
          <div class="flex items-center gap-3.5">
            <img
              src="/avatar.webp"
              :alt="settings.author_name || 'Author'"
              width="60"
              height="60"
              class="w-[60px] h-[60px] rounded-full object-cover ring-2 ring-white/10 shrink-0"
            >
            <div class="min-w-0">
              <div class="text-base font-bold text-white tracking-tight truncate">
                {{ settings.author_name || 'About' }}
              </div>
              <div v-if="authorRole" class="text-xs text-gray-400 truncate">
                {{ authorRole }}
              </div>
            </div>
          </div>
          <p class="text-sm text-gray-300 leading-relaxed line-clamp-5">
            {{ settings.author_bio || settings.site_description || 'Writing technical notes and architectural case studies.' }}
          </p>
          <div v-if="settings.author_email" class="text-xs text-gray-400">
            <a :href="`mailto:${settings.author_email}`" class="text-white font-semibold hover:underline">
              {{ settings.author_email }}
            </a>
          </div>
        </div>

        <div class="pt-6 mt-6 border-t border-gray-800 flex items-center justify-between text-xs text-gray-400">
          <div class="flex items-center gap-4">
            <a
              v-if="settings.author_github"
              :href="settings.author_github"
              target="_blank"
              rel="noopener noreferrer"
              class="p-1 -m-1 text-gray-300 hover:text-white transition-colors"
              title="GitHub"
              aria-label="GitHub"
            >
              <SocialIcon name="github" class="!w-[18px] !h-[18px]" />
            </a>
            <a
              v-if="settings.author_linkedin"
              :href="settings.author_linkedin"
              target="_blank"
              rel="noopener noreferrer"
              class="p-1 -m-1 text-gray-300 hover:text-white transition-colors"
              title="LinkedIn"
              aria-label="LinkedIn"
            >
              <SocialIcon name="linkedin" class="!w-[18px] !h-[18px]" />
            </a>
            <a
              v-if="settings.author_telegram"
              :href="settings.author_telegram"
              target="_blank"
              rel="noopener noreferrer"
              class="p-1 -m-1 text-gray-300 hover:text-white transition-colors"
              title="Telegram"
              aria-label="Telegram"
            >
              <SocialIcon name="telegram" class="!w-[18px] !h-[18px]" />
            </a>
          </div>
          <NuxtLink to="/contact" class="text-white font-semibold hover:underline">
            Contact &rarr;
          </NuxtLink>
        </div>
      </div>
    </section>

    <!-- Articles Bento Grid -->
    <section class="space-y-5">
      <div class="flex items-center justify-between px-1">
        <h2 class="text-xl font-bold text-gray-900 tracking-tight">Latest Articles</h2>
        <NuxtLink to="/articles" class="text-xs font-semibold text-gray-600 hover:text-gray-900">
          View all &rarr;
        </NuxtLink>
      </div>

      <div v-if="posts.length" class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <PostCard v-for="post in posts" :key="post.id" :post="post" />
      </div>
      <div v-else class="bento-card p-10 text-center text-sm text-gray-500">
        No articles published yet.
      </div>
    </section>

    <!-- Projects Bento Grid -->
    <section v-if="projects.length" class="space-y-5">
      <div class="flex items-center justify-between px-1">
        <h2 class="text-xl font-bold text-gray-900 tracking-tight">System Case Studies</h2>
        <NuxtLink to="/projects" class="text-xs font-semibold text-gray-600 hover:text-gray-900">
          All projects &rarr;
        </NuxtLink>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <ProjectCard v-for="project in projects" :key="project.id" :project="project" />
      </div>
    </section>

    <!-- Topics Bento Strip -->
    <section v-if="categories?.data?.length" class="bg-white rounded-2xl border border-gray-200/90 p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
      <div>
        <h2 class="text-sm font-bold text-gray-900">Browse by Topic</h2>
        <p class="text-xs text-gray-500 mt-0.5">Filter articles by architectural domain</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <NuxtLink
          v-for="cat in categories.data"
          :key="cat.id"
          :to="`/topics/${cat.slug}`"
          class="px-3.5 py-1.5 rounded-xl bg-gray-100 hover:bg-gray-900 hover:text-white text-gray-700 text-xs font-semibold transition-colors"
        >
          {{ cat.name }}
        </NuxtLink>
      </div>
    </section>
  </div>
</template>
