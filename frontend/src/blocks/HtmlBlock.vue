<script setup lang="ts">
/**
 * HTML Block Renderer (Vue)
 *
 * Renders raw HTML content.
 * WARNING: This should only be used for trusted content from admins.
 * Backend permission gates still apply.
 */
import { computed } from 'vue'
import DOMPurify from 'dompurify'

interface Props {
  data: {
    html: string
  }
}

const props = defineProps<Props>()

const sanitizedHtml = computed(() => {
  const html = props.data?.html ?? ''

  return DOMPurify.sanitize(html, {
    FORBID_TAGS: ['script', 'iframe', 'object', 'embed'],
    FORBID_ATTR: ['onerror', 'onload', 'onclick'],
  })
})
</script>

<template>
  <div class="html-block">
    <div v-html="sanitizedHtml" />
  </div>
</template>
