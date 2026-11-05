<script setup lang="ts">
const { settings, fetchSiteSettings } = useSiteSettings()

await useAsyncData('about-settings', () => fetchSiteSettings())

onMounted(() => {
  fetchSiteSettings()
})

useHead({
  title: () => settings.value?.author_name ? `About — ${settings.value.author_name}` : 'About',
})
</script>

<template>
  <div class="space-y-6">
    <!-- Top Bento Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
      <div class="lg:col-span-2 bg-white rounded-3xl border border-gray-200/90 p-7 sm:p-10 shadow-[0_2px_12px_rgba(0,0,0,0.02)] space-y-4">
        <div v-if="settings.site_subtitle" class="inline-flex items-center px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-semibold">
          {{ settings.site_subtitle }}
        </div>
        <div v-else class="inline-flex items-center px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-semibold">
          About the Author
        </div>

        <h1 v-if="settings.author_name" class="text-2xl sm:text-4xl font-bold tracking-tight text-gray-900">
          {{ settings.author_name }}
        </h1>

        <p v-if="settings.author_bio" class="text-gray-600 text-base leading-relaxed whitespace-pre-line">
          {{ settings.author_bio }}
        </p>

        <p v-if="settings.author_about_extended" class="text-gray-600 text-sm leading-relaxed pt-2 whitespace-pre-line">
          {{ settings.author_about_extended }}
        </p>
      </div>

      <div class="bg-gray-900 text-white rounded-3xl p-7 sm:p-8 flex flex-col justify-between">
        <div class="space-y-4">
          <div class="text-xs font-semibold uppercase tracking-wider text-gray-400">
            Connect
          </div>
          <p v-if="settings.author_connect_text" class="text-sm text-gray-300 leading-relaxed whitespace-pre-line">
            {{ settings.author_connect_text }}
          </p>
          <div v-if="settings.author_email" class="text-xs text-gray-400 pt-1">
            Email:
            <a :href="`mailto:${settings.author_email}`" class="text-white font-semibold hover:underline ml-1">
              {{ settings.author_email }}
            </a>
          </div>
        </div>

        <div class="space-y-2.5 pt-6">
          <NuxtLink
            to="/contact"
            class="block w-full text-center py-2.5 rounded-xl bg-white text-gray-900 text-xs font-bold hover:bg-gray-100 transition-colors"
          >
            Send a Message &rarr;
          </NuxtLink>
          <a
            v-if="settings.author_github"
            :href="settings.author_github"
            target="_blank"
            rel="noopener noreferrer"
            class="block w-full text-center py-2.5 rounded-xl bg-gray-800 text-gray-200 text-xs font-semibold hover:bg-gray-700 transition-colors"
          >
            GitHub Profile &nearr;
          </a>
          <a
            v-if="settings.author_linkedin"
            :href="settings.author_linkedin"
            target="_blank"
            rel="noopener noreferrer"
            class="block w-full text-center py-2.5 rounded-xl bg-gray-800 text-gray-200 text-xs font-semibold hover:bg-gray-700 transition-colors"
          >
            LinkedIn Profile &nearr;
          </a>
          <a
            v-if="settings.author_telegram"
            :href="settings.author_telegram"
            target="_blank"
            rel="noopener noreferrer"
            class="block w-full text-center py-2.5 rounded-xl bg-gray-800 text-gray-200 text-xs font-semibold hover:bg-gray-700 transition-colors"
          >
            Telegram &nearr;
          </a>
        </div>
      </div>
    </div>
  </div>
</template>
