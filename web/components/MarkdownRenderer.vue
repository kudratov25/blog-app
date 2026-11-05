<script setup lang="ts">
import { Marked } from 'marked'
import { markedHighlight } from 'marked-highlight'
import hljs from 'highlight.js'

const props = defineProps<{
  content: string
}>()

const markedInstance = new Marked(
  markedHighlight({
    emptyLangClass: 'hljs',
    langPrefix: 'hljs language-',
    highlight(code, lang) {
      const language = hljs.getLanguage(lang) ? lang : 'plaintext'
      return hljs.highlight(code, { language }).value
    },
  })
)

markedInstance.setOptions({
  gfm: true,
  breaks: true,
})

const renderedHtml = computed(() => {
  if (!props.content) return ''
  return markedInstance.parse(props.content) as string
})
</script>

<template>
  <div class="prose max-w-none" v-html="renderedHtml" />
</template>
