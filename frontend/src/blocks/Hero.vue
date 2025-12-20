<script setup lang="ts">
/**
 * Hero Block Renderer (Vue)
 *
 * Renders a hero section with headline, subheadline, image, and CTAs.
 */
interface Props {
  data: {
    headline: string
    subheadline?: string
    image_url?: string
    ctas?: Array<{
      label: string
      url: string
    }>
  }
}

defineProps<Props>()
</script>

<template>
  <section
    class="relative bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20 px-4"
  >
    <!-- Background image -->
    <div v-if="data.image_url" class="absolute inset-0 opacity-20">
      <img
        :src="data.image_url"
        alt=""
        class="w-full h-full object-cover"
      />
    </div>

    <div class="relative max-w-6xl mx-auto text-center">
      <h1 class="text-4xl md:text-6xl font-bold mb-4">
        {{ data.headline }}
      </h1>

      <p
        v-if="data.subheadline"
        class="text-xl md:text-2xl mb-8 text-blue-100"
      >
        {{ data.subheadline }}
      </p>

      <div
        v-if="data.ctas && data.ctas.length > 0"
        class="flex flex-wrap gap-4 justify-center"
      >
        <a
          v-for="(cta, index) in data.ctas"
          :key="index"
          :href="cta.url"
          :class="[
            'px-8 py-3 rounded-lg font-semibold transition-colors',
            index === 0
              ? 'bg-white text-blue-800 hover:bg-blue-50'
              : 'bg-blue-700 text-white hover:bg-blue-600 border-2 border-white',
          ]"
        >
          {{ cta.label }}
        </a>
      </div>
    </div>
  </section>
</template>
