<script setup lang="ts">
/**
 * CTA (Call to Action) Block Renderer (Vue)
 *
 * Renders a prominent call-to-action section.
 */
import { computed } from 'vue'

interface Props {
  data: {
    title: string
    body?: string
    button_label: string
    button_url: string
    variant?: 'primary' | 'secondary'
  }
}

const props = defineProps<Props>()

const variant = computed(() => props.data.variant ?? 'primary')

const variantClasses: Record<'primary' | 'secondary', string> = {
  primary: 'bg-blue-600 hover:bg-blue-700 text-white',
  secondary: 'bg-gray-600 hover:bg-gray-700 text-white',
}
</script>

<template>
  <section class="bg-gray-50 py-16 px-4 my-8 rounded-lg">
    <div class="max-w-4xl mx-auto text-center">
      <h2 class="text-3xl md:text-4xl font-bold mb-4 text-gray-900">
        {{ data.title }}
      </h2>

      <p
        v-if="data.body"
        class="text-lg text-gray-600 mb-8"
      >
        {{ data.body }}
      </p>

      <a
        :href="data.button_url"
        :class="[
          'inline-block px-8 py-4 rounded-lg font-semibold text-lg transition-colors',
          variantClasses[variant],
        ]"
      >
        {{ data.button_label }}
      </a>
    </div>
  </section>
</template>
