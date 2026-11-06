<script setup lang="ts">
const { post } = useApi()

const numA = ref(4)
const numB = ref(3)

function generateChallenge() {
  numA.value = Math.floor(Math.random() * 7) + 2
  numB.value = Math.floor(Math.random() * 7) + 1
}

const form = reactive({
  name: '',
  email: '',
  subject: '',
  message: '',
  website: '', // Honeypot trap (must stay empty)
  _rendered_at: Date.now(),
  human_answer: '',
})

onMounted(() => {
  generateChallenge()
  form._rendered_at = Date.now()
})

const submitting = ref(false)
const successMessage = ref('')
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string[]>>({})

async function handleSubmit() {
  submitting.value = true
  errorMessage.value = ''
  successMessage.value = ''
  fieldErrors.value = {}

  try {
    const res = await post<{ message: string }>('/contact', {
      ...form,
      human_answer: Number(form.human_answer),
      human_expected: numA.value + numB.value,
    })
    successMessage.value = res.message || 'Thank you! Your message has been sent.'
    form.name = ''
    form.email = ''
    form.subject = ''
    form.message = ''
    form.human_answer = ''
    form._rendered_at = Date.now()
    generateChallenge()
  } catch (err: any) {
    if (err.data?.errors) {
      fieldErrors.value = err.data.errors
    } else {
      errorMessage.value = err.data?.message || 'Failed to send message. Please try again.'
    }
  } finally {
    submitting.value = false
  }
}

const { settings } = useSiteSettings()

usePageSeo({
  title: () => settings.value?.author_name ? `Contact — ${settings.value.author_name}` : 'Contact',
  description: () => settings.value?.author_connect_text || 'Get in touch for engineering discussions, technical collaborations, and backend architecture consulting.',
})
</script>

<template>
  <div class="max-w-2xl mx-auto bg-white rounded-3xl border border-gray-200/90 p-7 sm:p-10 shadow-[0_2px_12px_rgba(0,0,0,0.02)] space-y-6">
    <header class="border-b border-gray-100 pb-6 space-y-1.5">
      <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900">Get in Touch</h1>
      <p class="text-gray-600 text-sm">
        Have a question, project inquiry, or architecture topic to discuss? Send a note below.
      </p>
    </header>

    <div v-if="successMessage" class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium">
      {{ successMessage }}
    </div>

    <div v-if="errorMessage" class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs font-medium">
      {{ errorMessage }}
    </div>

    <form class="space-y-4" @submit.prevent="handleSubmit">
      <!-- Honeypot trap for bots (hidden from real users) -->
      <div class="hidden" aria-hidden="true">
        <label for="contact_website">Website</label>
        <input
          id="contact_website"
          v-model="form.website"
          type="text"
          name="website"
          tabindex="-1"
          autocomplete="off"
        >
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label for="name" class="block text-xs font-semibold text-gray-700 mb-1.5">Name</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            required
            class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 text-gray-900 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-gray-400 focus:ring-0"
            placeholder="Your name"
          >
          <p v-if="fieldErrors.name" class="text-xs text-red-600 mt-1">{{ fieldErrors.name[0] }}</p>
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
          <p v-if="fieldErrors.email" class="text-xs text-red-600 mt-1">{{ fieldErrors.email[0] }}</p>
        </div>
      </div>

      <div>
        <label for="subject" class="block text-xs font-semibold text-gray-700 mb-1.5">Subject (optional)</label>
        <input
          id="subject"
          v-model="form.subject"
          type="text"
          class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 text-gray-900 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-gray-400 focus:ring-0"
          placeholder="What is this regarding?"
        >
      </div>

      <div>
        <label for="message" class="block text-xs font-semibold text-gray-700 mb-1.5">Message</label>
        <textarea
          id="message"
          v-model="form.message"
          rows="5"
          required
          class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 text-gray-900 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-gray-400 focus:ring-0"
          placeholder="Write your message..."
        />
        <p v-if="fieldErrors.message" class="text-xs text-red-600 mt-1">{{ fieldErrors.message[0] }}</p>
      </div>

      <!-- Bot Protection Challenge -->
      <div class="p-4 rounded-2xl bg-gray-50 border border-gray-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <label for="human_answer" class="block text-xs font-bold text-gray-800">
            Bot Protection: What is {{ numA }} + {{ numB }}?
          </label>
          <p class="text-[11px] text-gray-500 mt-0.5">Quick check to prevent automated spam</p>
        </div>
        <div class="w-full sm:w-28">
          <input
            id="human_answer"
            v-model="form.human_answer"
            type="number"
            required
            placeholder="?"
            class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm font-bold text-center bg-white text-gray-900 focus:outline-none focus:border-gray-400"
          >
        </div>
      </div>
      <p v-if="fieldErrors.human_expected || fieldErrors.human_answer" class="text-xs text-red-600">
        {{ fieldErrors.human_expected?.[0] || fieldErrors.human_answer?.[0] }}
      </p>

      <button
        type="submit"
        :disabled="submitting"
        class="btn-primary px-6 py-2.5"
      >
        {{ submitting ? 'Sending...' : 'Send Message &rarr;' }}
      </button>
    </form>
  </div>
</template>
