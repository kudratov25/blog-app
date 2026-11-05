<script setup lang="ts">
import type { PaginatedResponse, Project } from '~/types'

const { get } = useApi()

const { data: projectsData, pending } = await useAsyncData('projects-page', () =>
  get<PaginatedResponse<Project>>('/projects')
)

const projects = computed(() => projectsData.value?.data || [])

const { settings } = useSiteSettings()

useHead({
  title: () => settings.value?.author_name ? `Projects — ${settings.value.author_name}` : 'Projects',
})
</script>

<template>
  <div class="space-y-6">
    <header class="bg-white rounded-3xl border border-gray-200/90 p-6 sm:p-8 shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
      <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 mb-1.5">
        System Case Studies
      </h1>
      <p class="text-gray-600 text-sm sm:text-base">
        Selected systems, architectural decisions, and open source engineering work.
      </p>
    </header>

    <div v-if="pending" class="bento-card p-12 text-center text-gray-500 text-sm">
      Loading projects...
    </div>
    <div v-else-if="projects.length" class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <ProjectCard v-for="project in projects" :key="project.id" :project="project" />
    </div>
    <div v-else class="bento-card p-12 text-center text-gray-500 text-sm">
      No projects listed yet.
    </div>
  </div>
</template>
