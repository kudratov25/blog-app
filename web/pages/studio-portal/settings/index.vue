<script setup lang="ts">
definePageMeta({
  layout: 'admin',
  middleware: 'auth',
})

const { get, put } = useApi()
const { refreshSiteSettings } = useSiteSettings()

const { data: res, refresh } = await useAsyncData('admin-settings-page', () =>
  get<{ data: Record<string, string> }>('/admin/settings')
)

const currentSettings = computed(() => res.value?.data || {})

const form = reactive({
  site_title: '',
  site_subtitle: '',
  site_description: '',
  author_name: '',
  author_email: '',
  author_bio: '',
  author_about_extended: '',
  author_connect_text: '',
  author_github: '',
  author_linkedin: '',
  author_telegram: '',
})

watch(currentSettings, (val) => {
  if (val) {
    form.site_title = val.site_title || ''
    form.site_subtitle = val.site_subtitle || ''
    form.site_description = val.site_description || ''
    form.author_name = val.author_name || ''
    form.author_email = val.author_email || ''
    form.author_bio = val.author_bio || ''
    form.author_about_extended = val.author_about_extended || ''
    form.author_connect_text = val.author_connect_text || ''
    form.author_github = val.author_github || ''
    form.author_linkedin = val.author_linkedin || ''
    form.author_telegram = val.author_telegram || ''
  }
}, { immediate: true })

const saving = ref(false)
const message = ref('')
const errorMessage = ref('')

async function handleSave() {
  saving.value = true
  errorMessage.value = ''
  message.value = ''

  const settingsArray = Object.entries(form).map(([key, value]) => ({
    key,
    value,
  }))

  try {
    await put('/admin/settings', { settings: settingsArray })
    message.value = 'Settings updated successfully.'
    await Promise.all([refresh(), refreshSiteSettings()])
  } catch (err: any) {
    errorMessage.value = err.data?.message || 'Failed to update settings.'
  } finally {
    saving.value = false
  }
}

useHead({
  title: 'Settings — Studio Admin',
})
</script>

<template>
  <div class="space-y-6 max-w-3xl">
    <div>
      <h1 class="text-xl font-bold text-gray-900">Settings</h1>
      <p class="text-xs text-gray-500 mt-0.5">Configure homepage hero, About page content, email, and social profiles</p>
    </div>

    <div v-if="message" class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-2xl">
      {{ message }}
    </div>

    <div v-if="errorMessage" class="p-3.5 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-2xl">
      {{ errorMessage }}
    </div>

    <form class="space-y-6 bg-white p-6 sm:p-8 rounded-3xl border border-gray-200/90 shadow-[0_2px_12px_rgba(0,0,0,0.02)]" @submit.prevent="handleSave">
      <div class="space-y-4">
        <h2 class="text-xs font-bold uppercase tracking-wider text-gray-400">Website &amp; Hero Configuration</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Role / Subtitle Badge</label>
            <input
              v-model="form.site_subtitle"
              type="text"
              placeholder="Software Engineer · Backend & Systems"
              class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-gray-400"
            >
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Hero Headline / Site Title</label>
            <input
              v-model="form.site_title"
              type="text"
              class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-gray-400"
            >
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1.5">Publication Description</label>
          <textarea
            v-model="form.site_description"
            rows="2"
            class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-gray-400"
          />
        </div>
      </div>

      <div class="space-y-4 border-t border-gray-100 pt-6">
        <h2 class="text-xs font-bold uppercase tracking-wider text-gray-400">Author, About Page &amp; Contact Profiles</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Author Name</label>
            <input
              v-model="form.author_name"
              type="text"
              class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-gray-400"
            >
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Contact Email</label>
            <input
              v-model="form.author_email"
              type="email"
              placeholder="Leave blank to hide email"
              class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-gray-400"
            >
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1.5">Primary Bio (shown on Homepage &amp; About page)</label>
          <textarea
            v-model="form.author_bio"
            rows="3"
            class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-gray-400"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1.5">Extended About Paragraph (shown on About page)</label>
          <textarea
            v-model="form.author_about_extended"
            rows="3"
            class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-gray-400"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1.5">Connect Box Text (shown on About page sidebar)</label>
          <textarea
            v-model="form.author_connect_text"
            rows="2"
            class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-gray-400"
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">GitHub Profile URL</label>
            <input
              v-model="form.author_github"
              type="url"
              placeholder="https://github.com/..."
              class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-gray-400"
            >
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">LinkedIn Profile URL</label>
            <input
              v-model="form.author_linkedin"
              type="url"
              placeholder="https://linkedin.com/in/..."
              class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-gray-400"
            >
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Telegram URL</label>
            <input
              v-model="form.author_telegram"
              type="url"
              placeholder="https://t.me/..."
              class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-gray-400"
            >
          </div>
        </div>
      </div>

      <div class="pt-2">
        <button
          type="submit"
          :disabled="saving"
          class="btn-primary"
        >
          {{ saving ? 'Saving...' : 'Save Settings' }}
        </button>
      </div>
    </form>
  </div>
</template>
