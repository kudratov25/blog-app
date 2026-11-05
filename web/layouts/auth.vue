<script setup lang="ts">
useHead({
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive, nosnippet' },
    { name: 'googlebot', content: 'noindex, nofollow' },
  ],
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-[#f3f4f6] px-4 py-12 font-sans">
    <div class="w-full max-w-sm">
      <div class="mb-8">
        <NuxtLink to="/" class="text-xs font-semibold text-gray-500 hover:text-gray-900 transition-colors">
          &larr; Back to website
        </NuxtLink>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 mt-5">Studio Sign in</h1>
        <p class="text-xs text-gray-500 mt-1">Enter your credentials to access the publishing workspace.</p>
      </div>

      <div class="bg-white p-7 rounded-3xl border border-gray-200/90 shadow-[0_2px_12px_rgba(0,0,0,0.03)]">
        <slot />
      </div>
    </div>
  </div>
</template>
