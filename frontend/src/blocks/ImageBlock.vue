<script setup lang="ts">
/**
 * Image Block Renderer (Vue)
 *
 * Renders a single image with optional caption.
 */
interface Props {
  data: {
    url: string
    alt?: string
    caption?: string
  }
}

defineProps<Props>()
</script>

<template>
  <figure class="my-8">
    <img
      :src="data.url"
      :alt="data.alt || ''"
      class="w-full h-auto rounded-lg shadow-lg"
    />

    <figcaption
      v-if="data.caption"
      class="mt-2 text-center text-sm text-gray-600"
    >
      {{ data.caption }}
    </figcaption>
  </figure>
</template>
