<script setup lang="ts">
import type { Category, Media, Tag } from '~/types'

export interface PostFormState {
  title: string
  slug: string
  excerpt: string
  body: string
  category_id: string | number
  tags: number[]
  status: string
  is_featured: boolean
  seo_title: string
  seo_description: string
  revision_note: string
}

const props = defineProps<{
  modelValue: PostFormState
  categories: Category[]
  tags: Tag[]
  saving?: boolean
  isEdit?: boolean
  message?: string
  errorMessage?: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', val: PostFormState): void
  (e: 'submit', targetStatus?: string): void
  (e: 'tagCreated', tag: Tag): void
}>()

const form = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
})

const { post: apiPost, baseURL } = useApi()
const token = useCookie<string | null>('auth_token')

const editorRef = ref<HTMLTextAreaElement | null>(null)
const viewMode = ref<'edit' | 'split' | 'preview'>('edit')
const isFullscreen = ref(false)
const showSettings = ref(false)
const uploadingImage = ref(false)
const newTagName = ref('')
const creatingTag = ref(false)

// Word count & reading time
const wordCount = computed(() => {
  const text = (form.value.body || '').trim()
  if (!text) return 0
  return text.split(/\s+/).length
})

const readingTime = computed(() => {
  return Math.max(1, Math.ceil(wordCount.value / 200))
})

// Toggle tag selection
function toggleTag(tagId: number) {
  const idx = form.value.tags.indexOf(tagId)
  if (idx >= 0) {
    form.value.tags.splice(idx, 1)
  } else {
    form.value.tags.push(tagId)
  }
}

async function createQuickTag() {
  const name = newTagName.value.trim()
  if (!name) return
  creatingTag.value = true
  try {
    const res = await apiPost<{ data: Tag }>('/admin/tags', { name })
    emit('tagCreated', res.data)
    if (!form.value.tags.includes(res.data.id)) {
      form.value.tags.push(res.data.id)
    }
    newTagName.value = ''
  } catch {
    // ignore duplicate tag errors
  } finally {
    creatingTag.value = false
  }
}

// Markdown toolbar insertion helpers
function wrapSelection(prefix: string, suffix = prefix, placeholder = 'text') {
  const el = editorRef.value
  if (!el) return

  const start = el.selectionStart
  const end = el.selectionEnd
  const current = form.value.body || ''
  const selected = current.slice(start, end) || placeholder

  const replacement = `${prefix}${selected}${suffix}`
  form.value.body = current.slice(0, start) + replacement + current.slice(end)

  nextTick(() => {
    el.focus()
    const newCursorStart = start + prefix.length
    const newCursorEnd = newCursorStart + selected.length
    el.setSelectionRange(newCursorStart, newCursorEnd)
  })
}

function insertBlock(snippet: string, cursorOffset = snippet.length) {
  const el = editorRef.value
  if (!el) return

  const start = el.selectionStart
  const end = el.selectionEnd
  const current = form.value.body || ''
  const needsLeadingNewline = start > 0 && current[start - 1] !== '\n'
  const prefix = needsLeadingNewline ? '\n\n' : ''

  form.value.body = current.slice(0, start) + prefix + snippet + current.slice(end)

  nextTick(() => {
    el.focus()
    const pos = start + prefix.length + cursorOffset
    el.setSelectionRange(pos, pos)
  })
}

function insertCodeBlock() {
  const snippet = '```php\n// Write code here\n```\n'
  insertBlock(snippet, 7)
}

function insertLink() {
  const el = editorRef.value
  if (!el) return
  const selected = (form.value.body || '').slice(el.selectionStart, el.selectionEnd) || 'link text'
  wrapSelection('[', '](https://)', selected)
}

async function handleImageUpload(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  uploadingImage.value = true
  const formData = new FormData()
  formData.append('file', file)

  try {
    const res = await $fetch<{ data: Media }>(`${baseURL}/admin/media`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token.value}`,
      },
      body: formData,
    })
    const markdownImg = `![${res.data.original_name}](${res.data.url})\n`
    insertBlock(markdownImg)
  } catch (err) {
    alert('Failed to upload image. Please check file size (<5MB).')
  } finally {
    uploadingImage.value = false
    input.value = ''
  }
}

function handleKeydown(e: KeyboardEvent) {
  // Tab inserts 2 spaces
  if (e.key === 'Tab') {
    e.preventDefault()
    const el = editorRef.value
    if (!el) return
    const start = el.selectionStart
    const end = el.selectionEnd
    const current = form.value.body || ''
    form.value.body = current.slice(0, start) + '  ' + current.slice(end)
    nextTick(() => {
      el.setSelectionRange(start + 2, start + 2)
    })
    return
  }

  // Cmd/Ctrl shortcuts
  if (e.metaKey || e.ctrlKey) {
    if (e.key.toLowerCase() === 's') {
      e.preventDefault()
      emit('submit')
    } else if (e.key.toLowerCase() === 'b') {
      e.preventDefault()
      wrapSelection('**', '**', 'bold text')
    } else if (e.key.toLowerCase() === 'i') {
      e.preventDefault()
      wrapSelection('*', '*', 'italic text')
    } else if (e.key.toLowerCase() === 'e') {
      e.preventDefault()
      wrapSelection('`', '`', 'code')
    } else if (e.key.toLowerCase() === 'k') {
      e.preventDefault()
      insertLink()
    }
  } else if (e.key === 'Escape' && isFullscreen.value) {
    isFullscreen.value = false
  }
}
</script>

<template>
  <div
    :class="isFullscreen
      ? 'fixed inset-0 z-50 bg-[#f3f4f6] overflow-y-auto p-4 sm:p-8'
      : 'space-y-5'"
  >
    <div :class="isFullscreen ? 'max-w-6xl mx-auto space-y-5' : 'space-y-5'">
      <!-- Top Action Bar -->
      <div class="bg-white rounded-2xl border border-gray-200/90 px-5 py-3.5 flex flex-wrap items-center justify-between gap-3 sticky top-3 z-30 shadow-[0_2px_12px_rgba(0,0,0,0.03)]">
        <div class="flex items-center gap-3">
          <NuxtLink
            to="/studio-portal/posts"
            class="px-3 py-1.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold transition-colors"
          >
            &larr; Posts
          </NuxtLink>

          <span
            class="px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider"
            :class="form.status === 'published' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
          >
            {{ form.status || 'draft' }}
          </span>

          <span class="hidden sm:inline text-xs text-gray-400 font-medium">
            {{ wordCount }} words &middot; ~{{ readingTime }} min read
          </span>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-3.5 py-2 rounded-xl border text-xs font-semibold transition-colors flex items-center gap-1.5"
            :class="showSettings
              ? 'bg-gray-900 text-white border-gray-900'
              : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50'"
            @click="showSettings = !showSettings"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
            </svg>
            <span>Settings &amp; SEO</span>
          </button>

          <button
            type="button"
            :disabled="saving"
            class="px-4 py-2 rounded-xl border border-gray-200 bg-white text-gray-700 text-xs font-semibold hover:bg-gray-50 disabled:opacity-50 transition-colors"
            @click="emit('submit', 'draft')"
          >
            {{ saving ? 'Saving...' : 'Save Draft' }}
          </button>

          <button
            type="button"
            :disabled="saving"
            class="px-4 py-2 rounded-xl bg-gray-900 text-white text-xs font-semibold hover:bg-gray-800 disabled:opacity-50 transition-colors"
            @click="emit('submit', 'published')"
          >
            {{ form.status === 'published' ? 'Update Article' : 'Publish Now' }}
          </button>
        </div>
      </div>

      <!-- Feedback banners -->
      <div v-if="message" class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">
        {{ message }}
      </div>
      <div v-if="errorMessage" class="p-3.5 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs font-semibold">
        {{ errorMessage }}
      </div>

      <!-- Collapsible Settings & SEO Drawer -->
      <div v-if="showSettings" class="bg-white rounded-3xl border border-gray-200/90 p-6 sm:p-7 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-5">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <h3 class="text-sm font-bold text-gray-900">Article Settings &amp; SEO</h3>
          <button type="button" class="text-xs font-semibold text-gray-400 hover:text-gray-900" @click="showSettings = false">
            Close &times;
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">URL Slug</label>
            <input
              v-model="form.slug"
              type="text"
              placeholder="auto-generated-from-title"
              class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-xs font-mono bg-gray-50 focus:bg-white focus:outline-none focus:border-gray-400"
            >
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Revision Note</label>
            <input
              v-model="form.revision_note"
              type="text"
              placeholder="e.g. Added benchmark results"
              class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-gray-400"
            >
          </div>

          <div class="flex items-end pb-1">
            <label class="inline-flex items-center gap-2.5 cursor-pointer select-none">
              <input
                v-model="form.is_featured"
                type="checkbox"
                class="w-4 h-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900"
              >
              <span class="text-xs font-semibold text-gray-800">Feature this article on homepage</span>
            </label>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-gray-100">
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">SEO Meta Title (optional)</label>
            <input
              v-model="form.seo_title"
              type="text"
              placeholder="Custom title for search engines"
              class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-gray-400"
            >
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">SEO Meta Description (optional)</label>
            <input
              v-model="form.seo_description"
              type="text"
              placeholder="Custom description for search results"
              class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-gray-400"
            >
          </div>
        </div>
      </div>

      <!-- Main Writing Canvas Card -->
      <div class="bg-white rounded-3xl border border-gray-200/90 shadow-[0_2px_12px_rgba(0,0,0,0.02)] overflow-hidden">
        <!-- Title, Subtitle & Quick Taxonomy Header -->
        <div class="p-6 sm:p-8 pb-5 border-b border-gray-100 space-y-4">
          <input
            v-model="form.title"
            type="text"
            placeholder="Article Title..."
            class="w-full border-0 p-0 text-2xl sm:text-3xl font-bold text-gray-900 placeholder-gray-300 focus:ring-0 focus:outline-none bg-transparent tracking-tight"
          >

          <textarea
            v-model="form.excerpt"
            rows="2"
            placeholder="Write a concise summary or lead paragraph (shown on article cards)..."
            class="w-full border-0 p-0 text-sm sm:text-base text-gray-600 placeholder-gray-400 focus:ring-0 focus:outline-none bg-transparent resize-none leading-relaxed"
          />

          <!-- Quick Category & Tags Bar -->
          <div class="pt-3 border-t border-gray-100 flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
              <span class="text-xs font-semibold text-gray-400">Category:</span>
              <select
                v-model="form.category_id"
                class="px-3 py-1.5 border border-gray-200 rounded-xl text-xs font-semibold bg-gray-50 text-gray-800 focus:outline-none focus:border-gray-400"
              >
                <option value="">Uncategorized</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>

            <div class="h-4 w-[1px] bg-gray-200 hidden sm:block" />

            <!-- Tags Pills -->
            <div class="flex items-center gap-1.5 flex-wrap">
              <span class="text-xs font-semibold text-gray-400 mr-1">Tags:</span>
              <button
                v-for="t in tags"
                :key="t.id"
                type="button"
                class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all"
                :class="form.tags.includes(t.id)
                  ? 'bg-gray-900 text-white'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                @click="toggleTag(t.id)"
              >
                #{{ t.name }}
              </button>

              <!-- Quick add tag -->
              <div class="inline-flex items-center ml-1">
                <input
                  v-model="newTagName"
                  type="text"
                  placeholder="+ Add tag"
                  class="w-24 px-2.5 py-1 text-xs border border-dashed border-gray-300 rounded-lg bg-transparent text-gray-700 placeholder-gray-400 focus:outline-none focus:border-gray-500"
                  @keyup.enter.prevent="createQuickTag"
                >
              </div>
            </div>
          </div>
        </div>

        <!-- Markdown Formatting Toolbar -->
        <div class="bg-gray-50/80 border-b border-gray-200/80 px-4 sm:px-6 py-2.5 flex flex-wrap items-center justify-between gap-2">
          <!-- Left Formatting Tools -->
          <div class="flex items-center gap-1 flex-wrap">
            <button
              type="button"
              title="Heading 2"
              class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-200/80 transition-colors"
              @click="insertBlock('## Heading\n', 3)"
            >
              H2
            </button>
            <button
              type="button"
              title="Heading 3"
              class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-200/80 transition-colors"
              @click="insertBlock('### Subheading\n', 4)"
            >
              H3
            </button>

            <div class="w-[1px] h-4 bg-gray-200 mx-1" />

            <button
              type="button"
              title="Bold (Cmd+B)"
              class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-200/80 transition-colors"
              @click="wrapSelection('**', '**', 'bold')"
            >
              B
            </button>
            <button
              type="button"
              title="Italic (Cmd+I)"
              class="px-2.5 py-1.5 rounded-lg text-xs italic font-serif font-semibold text-gray-700 hover:bg-gray-200/80 transition-colors"
              @click="wrapSelection('*', '*', 'italic')"
            >
              I
            </button>
            <button
              type="button"
              title="Inline Code (Cmd+E)"
              class="px-2.5 py-1.5 rounded-lg text-xs font-mono font-semibold text-gray-700 hover:bg-gray-200/80 transition-colors"
              @click="wrapSelection('`', '`', 'code')"
            >
              &lt;/&gt;
            </button>
            <button
              type="button"
              title="Code Block"
              class="px-2.5 py-1.5 rounded-lg text-xs font-mono text-gray-700 hover:bg-gray-200/80 transition-colors"
              @click="insertCodeBlock"
            >
              ``` Code Block
            </button>

            <div class="w-[1px] h-4 bg-gray-200 mx-1" />

            <button
              type="button"
              title="Bullet List"
              class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-gray-700 hover:bg-gray-200/80 transition-colors"
              @click="insertBlock('- List item\n- Another item\n', 2)"
            >
              &bull; List
            </button>
            <button
              type="button"
              title="Blockquote"
              class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-gray-700 hover:bg-gray-200/80 transition-colors"
              @click="insertBlock('> Key takeaway or quote...\n', 2)"
            >
              &ldquo; Quote
            </button>
            <button
              type="button"
              title="Insert Link (Cmd+K)"
              class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-gray-700 hover:bg-gray-200/80 transition-colors"
              @click="insertLink"
            >
              Link
            </button>

            <!-- Direct Image Upload -->
            <label
              class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-gray-700 hover:bg-gray-200/80 transition-colors cursor-pointer inline-flex items-center gap-1"
              title="Upload and insert image"
            >
              <span>{{ uploadingImage ? 'Uploading...' : '+ Image' }}</span>
              <input
                type="file"
                accept="image/*"
                class="hidden"
                :disabled="uploadingImage"
                @change="handleImageUpload"
              >
            </label>
          </div>

          <!-- Right View Mode & Focus Controls -->
          <div class="flex items-center gap-2">
            <div class="flex items-center bg-gray-200/70 p-0.5 rounded-xl text-xs">
              <button
                type="button"
                class="px-3 py-1 rounded-lg font-semibold transition-all"
                :class="viewMode === 'edit' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                @click="viewMode = 'edit'"
              >
                Write
              </button>
              <button
                type="button"
                class="px-3 py-1 rounded-lg font-semibold transition-all"
                :class="viewMode === 'split' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                @click="viewMode = 'split'"
              >
                Split
              </button>
              <button
                type="button"
                class="px-3 py-1 rounded-lg font-semibold transition-all"
                :class="viewMode === 'preview' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                @click="viewMode = 'preview'"
              >
                Preview
              </button>
            </div>

            <button
              type="button"
              class="px-2.5 py-1.5 rounded-xl border border-gray-200 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-100 transition-colors"
              :title="isFullscreen ? 'Exit Fullscreen (Esc)' : 'Fullscreen Focus Mode'"
              @click="isFullscreen = !isFullscreen"
            >
              {{ isFullscreen ? 'Exit Focus' : 'Focus Mode' }}
            </button>
          </div>
        </div>

        <!-- Editor & Preview Panes -->
        <div
          class="grid divide-y lg:divide-y-0 lg:divide-x divide-gray-100"
          :class="viewMode === 'split' ? 'grid-cols-1 lg:grid-cols-2' : 'grid-cols-1'"
        >
          <!-- Write Pane -->
          <div v-show="viewMode !== 'preview'" class="flex flex-col">
            <textarea
              ref="editorRef"
              v-model="form.body"
              :rows="isFullscreen ? 28 : 22"
              class="w-full flex-1 border-0 focus:ring-0 p-6 sm:p-8 text-[15px] font-mono text-gray-800 leading-[1.8] resize-y focus:outline-none placeholder-gray-400"
              :class="viewMode === 'edit' ? 'max-w-3xl mx-auto' : ''"
              placeholder="Start writing your article in Markdown... (Tip: Use Cmd+B for bold, Cmd+E for inline code, Cmd+S to save)"
              @keydown="handleKeydown"
            />
          </div>

          <!-- Live Preview Pane -->
          <div
            v-show="viewMode !== 'edit'"
            class="p-6 sm:p-10 bg-gray-50/40 overflow-y-auto"
            :class="viewMode === 'preview' ? 'max-w-3xl mx-auto w-full bg-white' : 'max-h-[750px]'"
          >
            <div v-if="viewMode === 'split'" class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-4">
              Live Preview
            </div>
            <MarkdownRenderer :content="form.body || '*Start typing to see your formatted article...*'" />
          </div>
        </div>

        <!-- Bottom Editor Status Bar -->
        <div class="bg-gray-50 border-t border-gray-100 px-6 py-2.5 flex items-center justify-between text-xs text-gray-400">
          <div>
            Markdown supported &middot; Press <kbd class="px-1.5 py-0.5 bg-white border border-gray-200 rounded text-[11px] text-gray-600">Cmd/Ctrl + S</kbd> to save
          </div>
          <div>
            {{ (form.body || '').length }} characters &middot; {{ wordCount }} words
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
