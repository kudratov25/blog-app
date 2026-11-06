<script setup lang="ts">
const { fetchSiteSettings } = useSiteSettings()
const route = useRoute()

await useAsyncData('layout-site-settings', () => fetchSiteSettings())

onMounted(() => {
  fetchSiteSettings()
})

watch(() => route.fullPath, () => {
  fetchSiteSettings()
})
</script>

<template>
  <div class="min-h-screen flex flex-col bg-page text-gray-900">
    <AppNav />
    <main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
      <slot />
    </main>
    <AppFooter />
  </div>
</template>
