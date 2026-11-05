<script setup lang="ts">
const route = useRoute()

const navItems = [
  { name: 'Dashboard', path: '/studio-portal' },
  { name: 'Articles', path: '/studio-portal/posts' },
  { name: 'Projects', path: '/studio-portal/projects' },
  { name: 'Categories', path: '/studio-portal/categories' },
  { name: 'Tags', path: '/studio-portal/tags' },
  { name: 'Media Library', path: '/studio-portal/media' },
  { name: 'Messages', path: '/studio-portal/messages' },
  { name: 'Settings', path: '/studio-portal/settings' },
]

function isActive(path: string) {
  if (path === '/studio-portal') return route.path === '/studio-portal'
  return route.path.startsWith(path)
}
</script>

<template>
  <aside class="w-64 shrink-0 bg-white border-r border-gray-200/80 min-h-screen hidden md:flex flex-col justify-between p-4">
    <div>
      <div class="px-3 py-2 flex items-center justify-between mb-6">
        <NuxtLink to="/studio-portal" class="font-bold text-gray-900 text-sm tracking-tight flex items-center gap-2">
          <span class="w-6 h-6 rounded-lg bg-gray-900 text-white flex items-center justify-center text-[11px] font-bold">
            S
          </span>
          <span>Studio Admin</span>
        </NuxtLink>
      </div>

      <div class="px-2 mb-5">
        <NuxtLink
          to="/studio-portal/posts/create"
          class="btn-primary w-full"
        >
          + Write Article
        </NuxtLink>
      </div>

      <nav class="space-y-1">
        <NuxtLink
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          class="flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
          :class="isActive(item.path)
            ? 'bg-gray-900 text-white shadow-sm'
            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
        >
          {{ item.name }}
        </NuxtLink>
      </nav>
    </div>

    <div class="p-3 border-t border-gray-100 text-xs text-gray-500">
      <NuxtLink to="/" target="_blank" class="hover:text-gray-900 font-semibold flex items-center justify-between">
        <span>View Live Site</span>
        <span>&nearr;</span>
      </NuxtLink>
    </div>
  </aside>
</template>
