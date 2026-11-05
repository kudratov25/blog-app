<script setup lang="ts">
import type { Project } from '~/types'

const route = useRoute()
const slug = route.params.slug as string
const { get } = useApi()

const { data: res, error } = await useAsyncData(`project-${slug}`, () =>
  get<{ data: Project }>(`/projects/${slug}`)
)

if (error.value || !res.value?.data) {
  throw createError({ statusCode: 404, statusMessage: 'Project not found' })
}

const project = computed(() => res.value!.data)

const { settings } = useSiteSettings()

useHead({
  title: () => {
    const base = project.value.seo_title || project.value.title
    return settings.value?.author_name ? `${base} — ${settings.value.author_name}` : base
  },
})
</script>

<template>
  <article class="max-w-3xl mx-auto bg-white rounded-3xl border border-gray-200/90 p-6 sm:p-12 shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
    <div class="mb-6">
      <NuxtLink to="/projects" class="text-xs font-semibold text-gray-500 hover:text-gray-900 transition-colors">
        &larr; All case studies
      </NuxtLink>
    </div>

    <header class="mb-8 pb-8 border-b border-gray-100 space-y-4">
      <div class="flex items-center gap-3 flex-wrap">
        <div v-if="project.role" class="inline-block px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-semibold">
          {{ project.role }}
        </div>
        <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500">
          <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          <span>{{ (project.views_count ?? 0).toLocaleString() }} reads</span>
        </span>
      </div>

      <h1 class="text-2xl sm:text-4xl font-bold tracking-tight text-gray-900">
        {{ project.title }}
      </h1>

      <p class="text-gray-600 text-base sm:text-lg leading-relaxed">
        {{ project.summary }}
      </p>

      <div v-if="project.demo_url || project.repository_url" class="flex items-center gap-3 pt-2 text-xs font-semibold">
        <a
          v-if="project.demo_url"
          :href="project.demo_url"
          target="_blank"
          rel="noopener noreferrer"
          class="px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-gray-800 transition-colors"
        >
          Live Demo &nearr;
        </a>

        <a
          v-if="project.repository_url"
          :href="project.repository_url"
          target="_blank"
          rel="noopener noreferrer"
          class="px-4 py-2 rounded-xl bg-gray-100 text-gray-800 hover:bg-gray-200 transition-colors"
        >
          Source Code &nearr;
        </a>
      </div>

      <div v-if="project.technologies && project.technologies.length" class="pt-3 flex flex-wrap gap-1.5">
        <span
          v-for="tech in project.technologies"
          :key="tech.id"
          class="px-2.5 py-1 rounded-lg bg-gray-50 border border-gray-200/80 text-xs font-semibold text-gray-600"
        >
          {{ tech.name }}
        </span>
      </div>
    </header>

    <div v-if="project.body">
      <MarkdownRenderer :content="project.body" />
    </div>
  </article>
</template>
