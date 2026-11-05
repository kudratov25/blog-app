<script setup lang="ts">
const { state, handleConfirm, handleCancel } = useConfirm()

function onKeydown(e: KeyboardEvent) {
  if (!state.isOpen) return
  if (e.key === 'Escape') {
    handleCancel()
  }
}

onMounted(() => {
  window.addEventListener('keydown', onKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="state.isOpen"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/40 backdrop-blur-sm"
        @click.self="handleCancel"
      >
        <div class="bg-white rounded-3xl border border-gray-200/90 max-w-md w-full p-6 sm:p-7 shadow-[0_20px_50px_rgba(0,0,0,0.15)] space-y-5">
          <div class="flex items-start gap-4">
            <div
              class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0"
              :class="state.variant === 'danger' ? 'bg-red-50 text-red-600 border border-red-100' : 'bg-gray-100 text-gray-900'"
            >
              <svg v-if="state.variant === 'danger'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>

            <div class="space-y-1.5 pt-0.5">
              <h3 class="text-base font-bold text-gray-900 leading-snug">
                {{ state.title }}
              </h3>
              <p class="text-sm text-gray-600 leading-relaxed">
                {{ state.message }}
              </p>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-2">
            <button
              type="button"
              class="btn-secondary"
              @click="handleCancel"
            >
              {{ state.cancelText }}
            </button>
            <button
              type="button"
              :class="state.variant === 'danger'
                ? 'px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-semibold transition-all inline-flex items-center justify-center gap-1.5 shadow-sm'
                : 'btn-primary'"
              @click="handleConfirm"
            >
              {{ state.confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
