<script setup lang="ts">
definePageMeta({
  layout: 'auth',
  middleware: 'guest',
})

const { login } = useAuth()

const form = reactive({
  email: '',
  password: '',
  website: '', // Honeypot field
  _rendered_at: Date.now(),
})

const loading = ref(false)
const errorMessage = ref('')

onMounted(() => {
  form._rendered_at = Date.now()
})

async function handleLogin() {
  loading.value = true
  errorMessage.value = ''

  try {
    await login(form)
    await navigateTo('/studio-portal')
  } catch (err: any) {
    errorMessage.value = err.data?.message || 'Invalid email or password.'
  } finally {
    loading.value = false
  }
}

useHead({
  title: 'Sign in — Studio',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive, nosnippet' },
    { name: 'googlebot', content: 'noindex, nofollow' },
  ],
})
</script>

<template>
  <div>
    <div v-if="errorMessage" class="mb-4 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-medium">
      {{ errorMessage }}
    </div>

    <form class="space-y-4" @submit.prevent="handleLogin">
      <!-- Honeypot trap for bots -->
      <div class="hidden" aria-hidden="true">
        <label for="login_website">Website</label>
        <input
          id="login_website"
          v-model="form.website"
          type="text"
          name="website"
          tabindex="-1"
          autocomplete="off"
        >
      </div>

      <div>
        <label for="email" class="block text-xs font-semibold text-gray-700 mb-1.5">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 text-gray-900 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-gray-400 focus:ring-0"
          placeholder="you@example.com"
        >
      </div>

      <div>
        <label for="password" class="block text-xs font-semibold text-gray-700 mb-1.5">Password</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          required
          class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 text-gray-900 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-gray-400 focus:ring-0"
          placeholder="••••••••"
        >
      </div>

      <button
        type="submit"
        :disabled="loading"
        class="btn-primary w-full py-2.5 mt-2"
      >
        {{ loading ? 'Signing in...' : 'Sign in' }}
      </button>
    </form>
  </div>
</template>
