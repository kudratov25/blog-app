<script setup lang="ts">
const year = new Date().getFullYear()
const { settings } = useSiteSettings()

const authorName = computed(() => settings.value?.author_name || '')
const githubUrl = computed(() => settings.value?.author_github || '')
const linkedinUrl = computed(() => settings.value?.author_linkedin || '')
const telegramUrl = computed(() => settings.value?.author_telegram || '')
const authorEmail = computed(() => settings.value?.author_email || '')
</script>

<template>
  <footer class="mt-auto pt-6 pb-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto bg-white border border-gray-200/90 rounded-2xl px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-500 shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
      <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 text-center sm:text-left">
        <p class="font-semibold text-gray-700">
          &copy; {{ year }} <span v-if="authorName">{{ authorName }}.</span> All rights reserved.
        </p>
        <a
          v-if="authorEmail"
          :href="`mailto:${authorEmail}`"
          class="text-gray-400 hover:text-indigo-600 transition-colors font-medium"
        >
          {{ authorEmail }}
        </a>
      </div>

      <div class="flex items-center gap-5 font-semibold text-gray-600 flex-wrap justify-center">
        <a
          v-if="githubUrl"
          :href="githubUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="hover:text-gray-900 transition-colors"
        >
          GitHub
        </a>
        <a
          v-if="linkedinUrl"
          :href="linkedinUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="hover:text-gray-900 transition-colors"
        >
          LinkedIn
        </a>
        <a
          v-if="telegramUrl"
          :href="telegramUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="hover:text-gray-900 transition-colors"
        >
          Telegram
        </a>
      </div>
    </div>
  </footer>
</template>
