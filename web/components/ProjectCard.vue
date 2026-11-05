<script setup lang="ts">
import type { Project } from '~/types'

defineProps<{
  project: Project
}>()
</script>

<template>
  <div class="group relative p-6 sm:p-7 rounded-2xl bg-white border border-gray-200/90 shadow-[0_2px_10px_rgba(0,0,0,0.02)] hover:shadow-[0_8px_24px_rgba(0,0,0,0.06)] hover:border-gray-300 transition-all flex flex-col justify-between">
    <div>
      <div class="flex items-center justify-between gap-2 mb-3">
        <span v-if="project.role" class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-semibold">
          {{ project.role }}
        </span>
        <div class="flex items-center gap-3 text-xs">
          <span class="inline-flex items-center gap-1 text-gray-400 font-medium">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <span>{{ (project.views_count ?? 0).toLocaleString() }} reads</span>
          </span>
          <span class="font-semibold text-gray-400 group-hover:text-gray-900 transition-colors">
            Case Study &nearr;
          </span>
        </div>
      </div>

      <h3 class="font-bold text-lg text-gray-900 group-hover:text-gray-600 transition-colors mb-2">
        <NuxtLink :to="`/projects/${project.slug}`">
          <span class="absolute inset-0 z-10" />
          {{ project.title }}
        </NuxtLink>
      </h3>

      <p class="text-gray-600 text-sm leading-relaxed mb-5 line-clamp-2">
        {{ project.summary }}
      </p>
    </div>

    <div v-if="project.technologies && project.technologies.length" class="flex flex-wrap gap-1.5 pt-4 border-t border-gray-100">
      <span
        v-for="tech in project.technologies"
        :key="tech.id"
        class="px-2.5 py-0.5 rounded-lg bg-gray-100 text-[11px] font-semibold text-gray-600"
      >
        {{ tech.name }}
      </span>
    </div>
  </div>
</template>
