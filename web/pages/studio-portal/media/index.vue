<script setup lang="ts">
import type { Media, PaginatedResponse } from '~/types'

definePageMeta({
  layout: 'admin',
  middleware: 'auth',
})

const { get, del, baseURL } = useApi()
const { confirm } = useConfirm()
const token = useCookie<string | null>('auth_token')

const { data: mediaData, refresh } = await useAsyncData('admin-media', () =>
  get<PaginatedResponse<Media>>('/admin/media')
)

const mediaItems = computed(() => mediaData.value?.data || [])

const uploading = ref(false)
const uploadError = ref('')
const copiedId = ref<number | null>(null)

async function handleFileUpload(e: Event) {
  const target = e.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  uploading.value = true
  uploadError.value = ''

  const formData = new FormData()
  formData.append('file', file)

  try {
    await $fetch(`${baseURL}/admin/media`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token.value}`,
      },
      body: formData,
    })
    target.value = ''
    await refresh()
  } catch (err: any) {
    uploadError.value = err.data?.message || 'Failed to upload media asset.'
  } finally {
    uploading.value = false
  }
}

async function handleDelete(m: Media) {
  const ok = await confirm({
    title: 'Delete Media File',
    message: `Are you sure you want to delete "${m.original_name}"?`,
    confirmText: 'Delete File',
    cancelText: 'Cancel',
    variant: 'danger',
  })
  if (!ok) return

  uploadError.value = ''
  try {
    await del(`/admin/media/${m.id}`)
    await refresh()
  } catch (err: any) {
    uploadError.value = err.data?.message || 'Failed to delete media file.'
  }
}

function copyUrl(m: Media) {
  navigator.clipboard.writeText(m.url)
  copiedId.value = m.id
  setTimeout(() => {
    copiedId.value = null
  }, 2000)
}

useHead({
  title: 'Media Library — Studio Admin',
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Media Library</h1>
        <p class="text-xs text-gray-500 mt-0.5">Upload diagrams, cover illustrations, and screenshots</p>
      </div>

      <div>
        <label class="btn-primary">
          <span>{{ uploading ? 'Uploading...' : '+ Upload Asset' }}</span>
          <input
            type="file"
            accept="image/*"
            class="hidden"
            :disabled="uploading"
            @change="handleFileUpload"
          >
        </label>
      </div>
    </div>

    <div v-if="uploadError" class="p-3.5 bg-red-50 border border-red-200 text-red-700 text-xs rounded-2xl font-semibold">
      {{ uploadError }}
    </div>

    <!-- Media Grid -->
    <div v-if="mediaItems.length" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
      <div
        v-for="m in mediaItems"
        :key="m.id"
        class="group bg-white rounded-3xl border border-gray-200/90 overflow-hidden flex flex-col shadow-[0_2px_10px_rgba(0,0,0,0.02)]"
      >
        <div class="aspect-video bg-gray-100 relative overflow-hidden flex items-center justify-center">
          <img
            :src="m.url"
            :alt="m.alt_text || m.original_name"
            class="object-cover w-full h-full"
            loading="lazy"
          >
        </div>

        <div class="p-4 flex-1 flex flex-col justify-between text-xs">
          <div>
            <div class="font-bold text-gray-900 truncate" :title="m.original_name">
              {{ m.original_name }}
            </div>
            <div class="text-[11px] text-gray-400 mt-0.5">
              {{ Math.round(m.size_bytes / 1024) }} KB
            </div>
          </div>

          <div class="flex items-center justify-between gap-2.5 pt-3.5 mt-3.5 border-t border-gray-100">
            <button
              type="button"
              class="btn-secondary flex-1"
              @click="copyUrl(m)"
            >
              <span>{{ copiedId === m.id ? 'Copied!' : 'Copy URL' }}</span>
            </button>
            <button
              type="button"
              class="btn-danger"
              @click="handleDelete(m)"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              <span>Delete</span>
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-else class="text-center py-16 bg-white rounded-3xl border border-gray-200/90 text-xs text-gray-400">
      No media files uploaded yet. Upload images up to 5MB.
    </div>
  </div>
</template>
