<script setup lang="ts">
import type { NuxtError } from '#app'

defineProps({
  error: Object as () => NuxtError,
})

function handleError() {
  clearError({ redirect: '/' })
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-[#fafafa] px-4 text-center">
    <div class="max-w-md space-y-4">
      <div class="text-4xl font-semibold text-zinc-300">
        {{ error?.statusCode || 404 }}
      </div>
      <h1 class="text-xl font-semibold text-zinc-900">
        {{ error?.statusCode === 404 ? 'Page not found' : 'Something went wrong' }}
      </h1>
      <p class="text-sm text-zinc-500">
        {{ error?.statusMessage || 'The page you are looking for does not exist.' }}
      </p>
      <div class="pt-2">
        <button
          type="button"
          class="px-4 py-2 rounded-lg bg-zinc-900 text-white text-xs font-medium hover:bg-zinc-800 transition-colors"
          @click="handleError"
        >
          Back to home
        </button>
      </div>
    </div>
  </div>
</template>
