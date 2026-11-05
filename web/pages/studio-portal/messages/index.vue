<script setup lang="ts">
import type { ContactMessage, PaginatedResponse } from '~/types'

definePageMeta({
  layout: 'admin',
  middleware: 'auth',
})

const { get, put, del } = useApi()
const { confirm } = useConfirm()

const selectedStatus = ref('')
const { data: messagesData, refresh } = await useAsyncData('admin-messages', () => {
  const params: Record<string, any> = {}
  if (selectedStatus.value) params.status = selectedStatus.value
  return get<PaginatedResponse<ContactMessage>>('/admin/messages', params)
}, { watch: [selectedStatus] })

const messages = computed(() => messagesData.value?.data || [])
const activeMessage = ref<ContactMessage | null>(null)

function viewMessage(m: ContactMessage) {
  activeMessage.value = m
  if (m.status === 'unread') {
    updateStatus(m, 'read')
  }
}

async function updateStatus(m: ContactMessage, status: string) {
  await put(`/admin/messages/${m.id}`, { status })
  m.status = status as any
  await refresh()
}

async function handleDelete(m: ContactMessage) {
  const ok = await confirm({
    title: 'Delete Message',
    message: `Are you sure you want to permanently delete the message from "${m.name}"?`,
    confirmText: 'Delete Message',
    cancelText: 'Cancel',
    variant: 'danger',
  })
  if (!ok) return

  await del(`/admin/messages/${m.id}`)
  if (activeMessage.value?.id === m.id) {
    activeMessage.value = null
  }
  await refresh()
}

useHead({
  title: 'Messages — Studio Admin',
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Messages</h1>
        <p class="text-xs text-gray-500 mt-0.5">Reader inquiries and contact form submissions</p>
      </div>

      <div class="flex items-center gap-1 bg-white border border-gray-200/90 rounded-xl p-1 text-xs">
        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg font-semibold transition-colors"
          :class="selectedStatus === '' ? 'bg-gray-900 text-white' : 'text-gray-500 hover:text-gray-900'"
          @click="selectedStatus = ''"
        >
          All
        </button>
        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg font-semibold transition-colors"
          :class="selectedStatus === 'unread' ? 'bg-gray-900 text-white' : 'text-gray-500 hover:text-gray-900'"
          @click="selectedStatus = 'unread'"
        >
          Unread
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- List -->
      <div class="bg-white rounded-3xl border border-gray-200/90 overflow-hidden divide-y divide-gray-100 max-h-[700px] overflow-y-auto shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
        <div
          v-for="m in messages"
          :key="m.id"
          class="p-4 cursor-pointer hover:bg-gray-50 transition-colors"
          :class="activeMessage?.id === m.id ? 'bg-gray-50 border-l-4 border-gray-900' : ''"
          @click="viewMessage(m)"
        >
          <div class="flex items-center justify-between mb-1">
            <span class="text-xs font-bold text-gray-900">
              {{ m.name }}
            </span>
            <span class="text-[10px] text-gray-400">
              {{ new Date(m.created_at).toLocaleDateString() }}
            </span>
          </div>

          <div class="text-xs font-semibold text-gray-700 truncate">
            {{ m.subject || 'No subject' }}
          </div>

          <div class="text-xs text-gray-500 truncate mt-1">
            {{ m.message }}
          </div>
        </div>

        <div v-if="!messages.length" class="p-8 text-center text-xs text-gray-400">
          No messages found.
        </div>
      </div>

      <!-- Detail Pane -->
      <div class="lg:col-span-2 bg-white rounded-3xl border border-gray-200/90 p-6 sm:p-8 flex flex-col justify-between min-h-[400px] shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
        <div v-if="activeMessage" class="space-y-4">
          <div class="flex items-start justify-between border-b border-gray-100 pb-4">
            <div>
              <h2 class="text-base font-bold text-gray-900">{{ activeMessage.subject || 'No Subject' }}</h2>
              <div class="text-xs text-gray-500 mt-1">
                From <span class="font-semibold text-gray-800">{{ activeMessage.name }}</span> &lt;{{ activeMessage.email }}&gt;
              </div>
              <div class="text-[11px] text-gray-400 mt-0.5">
                Received {{ new Date(activeMessage.created_at).toLocaleString() }}
              </div>
            </div>

            <span
              class="px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider"
              :class="activeMessage.status === 'unread' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600'"
            >
              {{ activeMessage.status }}
            </span>
          </div>

          <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap py-2">
            {{ activeMessage.message }}
          </div>
        </div>

        <div v-else class="flex items-center justify-center h-full text-xs text-gray-400 py-16">
          Select a message from the list to view its contents.
        </div>

        <div v-if="activeMessage" class="flex items-center justify-between border-t border-gray-100 pt-4 mt-6">
          <div>
            <button
              v-if="activeMessage.status !== 'resolved'"
              type="button"
              class="btn-primary"
              @click="updateStatus(activeMessage, 'resolved')"
            >
              Mark Resolved
            </button>
            <button
              v-else
              type="button"
              class="btn-secondary"
              @click="updateStatus(activeMessage, 'unread')"
            >
              Mark Unread
            </button>
          </div>

          <button
            type="button"
            class="btn-danger"
            @click="handleDelete(activeMessage)"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span>Delete Message</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
