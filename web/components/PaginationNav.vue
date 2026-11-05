<script setup lang="ts">
import type { PaginationMeta } from '~/types'

const props = defineProps<{
  meta?: PaginationMeta
}>()

const emit = defineEmits<{
  (e: 'change', page: number): void
}>()

function goTo(page: number) {
  if (!props.meta) return
  if (page < 1 || page > props.meta.last_page) return
  emit('change', page)
}
</script>

<template>
  <div v-if="meta && meta.last_page > 1" class="bg-white rounded-2xl border border-gray-200/90 px-5 py-3.5 flex items-center justify-between text-xs font-medium text-gray-600 shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
    <div>
      Page <span class="font-bold text-gray-900">{{ meta.current_page }}</span> of <span class="font-bold text-gray-900">{{ meta.last_page }}</span>
    </div>

    <div class="flex items-center gap-2.5">
      <button
        type="button"
        :disabled="meta.current_page <= 1"
        class="btn-secondary"
        @click="goTo(meta.current_page - 1)"
      >
        Previous
      </button>

      <button
        type="button"
        :disabled="meta.current_page >= meta.last_page"
        class="btn-secondary"
        @click="goTo(meta.current_page + 1)"
      >
        Next
      </button>
    </div>
  </div>
</template>
