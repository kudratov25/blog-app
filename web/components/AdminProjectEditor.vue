<script setup lang="ts">
import type { Media } from '~/types'

export interface ProjectFormState {
  title: string
  slug: string
  summary: string
  body: string
  role: string
  repository_url: string
  demo_url: string
  started_at: string
  completed_at: string
  status: 'draft' | 'published' | 'archived'
  is_featured: boolean
  cover_media_id: number | null
  seo_title: string
  seo_description: string
}

const props = defineProps<{
  modelValue: ProjectFormState
  cover?: Media | null
  saving?: boolean
  isEdit?: boolean
  loading?: boolean
  message?: string
  errorMessage?: string
}>()

const emit = defineEmits<{
  (e: 'submit', targetStatus: 'draft' | 'published'): void
  (e: 'cancel'): void
  (e: 'update:cover', media: Media | null): void
}>()

// The parent owns the reactive form object; fields are edited in place.
const form = props.modelValue

const { baseURL } = useApi()
const token = useCookie<string | null>('auth_token')

const viewMode = ref<'edit' | 'split' | 'preview'>('split')
const uploadingCover = ref(false)
const uploadingImage = ref(false)
const uploadError = ref('')
const editorRef = ref<HTMLTextAreaElement | null>(null)

async function uploadMedia(file: File): Promise<Media> {
  const formData = new FormData()
  formData.append('file', file)
  const res = await $fetch<{ data: Media }>(`${baseURL}/admin/media`, {
    method: 'POST',
    headers: { Authorization: `Bearer ${token.value}`, Accept: 'application/json' },
    body: formData,
  })
  return res.data
}

async function handleCoverUpload(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  uploadingCover.value = true
  uploadError.value = ''
  try {
    const media = await uploadMedia(file)
    form.cover_media_id = media.id
    emit('update:cover', media)
  } catch {
    uploadError.value = 'Failed to upload cover image. Use JPEG, PNG, GIF or WebP under 5MB.'
  } finally {
    uploadingCover.value = false
    input.value = ''
  }
}

function removeCover() {
  form.cover_media_id = null
  emit('update:cover', null)
}

async function handleBodyImageUpload(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  uploadingImage.value = true
  uploadError.value = ''
  try {
    const media = await uploadMedia(file)
    const snippet = `\n![${media.alt_text || media.original_name}](${media.url})\n`
    const el = editorRef.value
    const body = form.body || ''
    const at = el ? el.selectionStart : body.length
    form.body = body.slice(0, at) + snippet + body.slice(at)
  } catch {
    uploadError.value = 'Failed to upload image. Use JPEG, PNG, GIF or WebP under 5MB.'
  } finally {
    uploadingImage.value = false
    input.value = ''
  }
}

const fieldClass = 'w-full px-3 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none'
</script>

<template>
  <div class="space-y-5">
    <div class="bg-white rounded-3xl border border-gray-200/90 p-6 sm:p-8 space-y-5 shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-4">
        <div class="flex items-center gap-3">
          <h2 class="text-base font-bold text-gray-900">
            {{ isEdit ? 'Edit Project Case Study' : 'New Project Case Study' }}
          </h2>
          <span
            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider"
            :class="form.status === 'published' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : 'bg-amber-50 text-amber-700 border border-amber-200/60'"
          >
            <span class="w-1.5 h-1.5 rounded-full" :class="form.status === 'published' ? 'bg-emerald-500' : 'bg-amber-500'" />
            {{ form.status }}
          </span>
          <span v-if="loading" class="text-xs text-gray-400">Loading...</span>
        </div>

        <div class="flex items-center gap-2.5">
          <button type="button" class="btn-secondary" @click="emit('cancel')">
            {{ isEdit ? 'Back' : 'Cancel' }}
          </button>
          <button type="button" :disabled="saving" class="btn-secondary" @click="emit('submit', 'draft')">
            {{ saving && form.status === 'draft' ? 'Saving...' : 'Save as Draft' }}
          </button>
          <button type="button" :disabled="saving" class="btn-primary" @click="emit('submit', 'published')">
            {{ saving && form.status === 'published' ? 'Publishing...' : (isEdit && form.status === 'published' ? 'Update Published' : 'Publish Project') }}
          </button>
        </div>
      </div>

      <div v-if="message" class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs rounded-2xl font-semibold">
        {{ message }}
      </div>
      <div v-if="errorMessage || uploadError" class="p-3.5 bg-red-50 border border-red-200 text-red-700 text-xs rounded-2xl font-semibold">
        {{ errorMessage || uploadError }}
      </div>

      <div class="space-y-4">
        <input
          v-model="form.title"
          type="text"
          required
          placeholder="Project Title..."
          class="w-full border-0 p-0 text-2xl font-bold text-gray-900 placeholder-gray-300 focus:ring-0 focus:outline-none bg-transparent"
        >

        <textarea
          v-model="form.summary"
          rows="2"
          required
          placeholder="Short summary of the project and its impact..."
          class="w-full border-0 p-0 text-sm text-gray-600 placeholder-gray-400 focus:ring-0 focus:outline-none resize-none bg-transparent"
        />

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 pt-4 border-t border-gray-100">
          <div>
            <label class="block text-[11px] font-semibold text-gray-500 mb-1">Role</label>
            <input v-model="form.role" type="text" :class="fieldClass" placeholder="Lead Architect">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-500 mb-1">Slug</label>
            <input v-model="form.slug" type="text" :class="[fieldClass, 'font-mono']" placeholder="auto-generated">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-500 mb-1">GitHub URL</label>
            <input v-model="form.repository_url" type="url" :class="fieldClass" placeholder="https://github.com/...">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-500 mb-1">Live Demo URL</label>
            <input v-model="form.demo_url" type="url" :class="fieldClass" placeholder="https://...">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-500 mb-1">Started</label>
            <input v-model="form.started_at" type="date" :class="fieldClass">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-500 mb-1">Completed <span class="font-normal text-gray-400">(empty = ongoing)</span></label>
            <input v-model="form.completed_at" type="date" :class="fieldClass">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-500 mb-1">SEO Title</label>
            <input v-model="form.seo_title" type="text" maxlength="255" :class="fieldClass" placeholder="Defaults to project title">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-500 mb-1">SEO Description</label>
            <input v-model="form.seo_description" type="text" maxlength="255" :class="fieldClass" placeholder="Defaults to summary">
          </div>
        </div>

        <!-- Cover image -->
        <div class="pt-4 border-t border-gray-100">
          <label class="block text-[11px] font-semibold text-gray-500 mb-2">Cover Image</label>
          <div class="flex flex-wrap items-center gap-4">
            <img
              v-if="cover?.url"
              :src="cover.url"
              :alt="cover.alt_text || 'Cover image'"
              class="h-20 w-40 object-cover rounded-xl border border-gray-200"
            >
            <div v-else class="h-20 w-40 rounded-xl border border-dashed border-gray-300 flex items-center justify-center text-[11px] text-gray-400">
              No cover
            </div>
            <div class="flex items-center gap-2">
              <label class="btn-secondary cursor-pointer">
                <span>{{ uploadingCover ? 'Uploading...' : (cover ? 'Replace' : 'Upload') }}</span>
                <input type="file" accept="image/*" class="hidden" :disabled="uploadingCover" @change="handleCoverUpload">
              </label>
              <button v-if="cover" type="button" class="btn-secondary" @click="removeCover">
                Remove
              </button>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-6 pt-2">
          <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-700 cursor-pointer select-none">
            <input
              v-model="form.is_featured"
              type="checkbox"
              class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            >
            <span>Feature on Homepage &amp; Projects Top</span>
          </label>
        </div>
      </div>
    </div>

    <!-- Case Study Markdown Editor Card -->
    <div class="bg-white rounded-3xl border border-gray-200/90 overflow-hidden shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
      <div class="bg-gray-50 border-b border-gray-200/80 px-6 py-2.5 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <span class="text-xs font-bold text-gray-700">Case Study Body (Markdown)</span>
          <label class="px-2.5 py-1 rounded-lg text-xs font-medium text-gray-700 hover:bg-gray-200/80 transition-colors cursor-pointer">
            <span>{{ uploadingImage ? 'Uploading...' : '+ Image' }}</span>
            <input type="file" accept="image/*" class="hidden" :disabled="uploadingImage" @change="handleBodyImageUpload">
          </label>
        </div>
        <div class="flex items-center bg-gray-200/70 p-0.5 rounded-xl text-xs">
          <button
            v-for="mode in (['edit', 'split', 'preview'] as const)"
            :key="mode"
            type="button"
            class="px-3 py-1 rounded-lg font-semibold"
            :class="viewMode === mode ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600'"
            @click="viewMode = mode"
          >
            {{ mode === 'edit' ? 'Write' : mode === 'split' ? 'Split' : 'Preview' }}
          </button>
        </div>
      </div>

      <div
        class="grid divide-y lg:divide-y-0 lg:divide-x divide-gray-100"
        :class="viewMode === 'split' ? 'grid-cols-1 lg:grid-cols-2' : 'grid-cols-1'"
      >
        <div v-show="viewMode !== 'preview'" class="flex flex-col">
          <textarea
            ref="editorRef"
            v-model="form.body"
            rows="18"
            class="w-full flex-1 border-0 focus:ring-0 p-6 sm:p-8 text-[15px] font-mono text-gray-800 leading-[1.8] resize-y focus:outline-none bg-transparent"
            placeholder="Write the architectural case study in Markdown..."
          />
        </div>
        <div v-show="viewMode !== 'edit'" class="p-6 sm:p-8 bg-gray-50/40 overflow-y-auto max-h-[600px]">
          <MarkdownRenderer :content="form.body || '*Case study preview...*'" />
        </div>
      </div>
    </div>
  </div>
</template>
