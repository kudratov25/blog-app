<script setup lang="ts">
useHead({
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive, nosnippet' },
    { name: 'googlebot', content: 'noindex, nofollow' },
  ],
})
</script>

<template>
  <div class="min-h-screen flex bg-page text-gray-900 font-sans">
    <AdminSidebar />
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
      <AdminTopbar />
      <main class="flex-1 p-5 sm:p-8 max-w-6xl w-full mx-auto">
        <slot />
      </main>
    </div>
  </div>
</template>
