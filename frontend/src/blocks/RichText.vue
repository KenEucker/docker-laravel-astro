<script setup lang="ts">
/**
 * Rich Text Block Renderer (Vue)
 *
 * Renders markdown-formatted content safely.
 * Markdown is converted to HTML and sanitized.
 */
import { computed } from 'vue'
import { marked } from 'marked'
import DOMPurify from 'dompurify'

interface Props {
  data: {
    markdown: string
  }
}

const props = defineProps<Props>()

// Configure marked for safe rendering (do once per module load)
marked.setOptions({
  breaks: true,
  gfm: true,
})

const sanitizedHtml = computed(() => {
  const markdown = props.data?.markdown ?? ''

  // Convert markdown to HTML
  const raw = marked.parse(markdown) as string

  // Sanitize the HTML
  return DOMPurify.sanitize(raw, {
    ALLOWED_TAGS: [
      'p', 'br', 'strong', 'em', 'u', 's', 'a', 'ul', 'ol', 'li',
      'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
      'blockquote', 'code', 'pre', 'hr', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
    ],
    ALLOWED_ATTR: ['href', 'title', 'target', 'rel'],
  })
})
</script>

<template>
  <div class="prose prose-lg max-w-none">
    <div v-html="sanitizedHtml" />
  </div>
</template>
