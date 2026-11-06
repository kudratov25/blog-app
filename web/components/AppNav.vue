<script setup lang="ts">
const isMobileOpen = ref(false)
const route = useRoute()
const { settings } = useSiteSettings()

const brandName = computed(() => settings.value?.author_name || 'Developer Blog')
const brandInitial = computed(() => (brandName.value.trim()[0] || 'S').toUpperCase())

watch(() => route.path, () => {
  isMobileOpen.value = false
})

const navLinks = [
  { name: 'Articles', path: '/articles' },
  { name: 'Projects', path: '/projects' },
  { name: 'About', path: '/about' },
  { name: 'Contact', path: '/contact' },
]
</script>

<template>
  <header class="sticky top-0 z-40 pt-4 px-4 sm:px-6 lg:px-8">
    <div
      v-if="isMobileOpen"
      class="fixed inset-0 z-40 bg-black/15 backdrop-blur-[1px] sm:hidden"
      aria-hidden="true"
      @click="isMobileOpen = false"
    />

    <div class="relative z-50 max-w-5xl mx-auto">
      <div class="bg-white/90 backdrop-blur-md border border-gray-200/90 rounded-2xl px-5 h-14 flex items-center justify-between shadow-[0_2px_12px_rgba(0,0,0,0.03)]">
        <NuxtLink to="/" class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-lg bg-gray-900 text-white flex items-center justify-center text-xs font-bold">
            {{ brandInitial }}
          </span>
          <span class="font-bold text-sm text-gray-900 tracking-tight">
            {{ brandName }}
          </span>
        </NuxtLink>

        <nav class="hidden sm:flex items-center gap-1 text-sm">
          <NuxtLink
            v-for="link in navLinks"
            :key="link.path"
            :to="link.path"
            class="px-3.5 py-1.5 rounded-xl transition-all text-xs font-semibold"
            :class="route.path.startsWith(link.path)
              ? 'bg-gray-900 text-white'
              : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'"
          >
            {{ link.name }}
          </NuxtLink>
          <NuxtLink
            to="/search"
            class="ml-1 p-2 rounded-xl transition-colors"
            :class="route.path === '/search' ? 'bg-gray-900 text-white' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100'"
            title="Search"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </NuxtLink>
          <ThemeToggle />
        </nav>

        <div class="flex items-center gap-1 sm:hidden">
          <ThemeToggle />
          <NuxtLink to="/search" class="p-1.5 text-gray-600 hover:text-gray-900" aria-label="Search">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </NuxtLink>
          <button
            type="button"
            class="p-1.5 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors"
            :aria-expanded="isMobileOpen"
            aria-label="Toggle navigation menu"
            @click="isMobileOpen = !isMobileOpen"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path v-if="!isMobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <Transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="opacity-0 -translate-y-1.5 scale-[0.98]"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 -translate-y-1.5 scale-[0.98]"
      >
        <div
          v-if="isMobileOpen"
          class="sm:hidden absolute left-0 right-0 top-full mt-2 bg-white/95 backdrop-blur-md border border-gray-200/90 rounded-2xl p-2.5 space-y-1 shadow-[0_12px_32px_rgba(0,0,0,0.12)] origin-top"
        >
          <NuxtLink
            v-for="link in navLinks"
            :key="link.path"
            :to="link.path"
            class="block px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-colors"
            :class="route.path.startsWith(link.path)
              ? 'bg-gray-900 text-white'
              : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'"
            @click="isMobileOpen = false"
          >
            {{ link.name }}
          </NuxtLink>
        </div>
      </Transition>
    </div>
  </header>
</template>
