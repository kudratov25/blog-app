<script setup lang="ts">
const { user, logout } = useAuth()
</script>

<template>
  <header class="h-14 bg-white border-b border-gray-200/80 flex items-center justify-between px-6">
    <div class="text-xs font-medium text-gray-400">
      Content Management Studio
    </div>

    <div class="flex items-center gap-3">
      <ThemeToggle />
      <span class="text-xs text-gray-700 font-semibold">
        {{ user?.name || 'Admin' }}
      </span>

      <button
        type="button"
        class="btn-secondary !py-1.5 !px-3"
        @click="logout"
      >
        Sign out
      </button>
    </div>
  </header>
</template>
