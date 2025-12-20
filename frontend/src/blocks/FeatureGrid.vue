<script setup lang="ts">
/**
 * Feature Grid Block Renderer (Vue)
 *
 * Renders a grid of features with title, body, and optional link.
 */
interface Props {
  data: {
    title?: string
    items: Array<{
      title: string
      body: string
      url?: string
    }>
  }
}

defineProps<Props>()
</script>

<template>
  <section class="py-12 px-4">
    <div class="max-w-6xl mx-auto">
      <h2
        v-if="data.title"
        class="text-3xl md:text-4xl font-bold text-center mb-12 text-gray-900"
      >
        {{ data.title }}
      </h2>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div
          v-for="(item, index) in data.items"
          :key="index"
          class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow"
        >
          <a v-if="item.url" :href="item.url" class="block">
            <h3
              class="text-xl font-semibold mb-3 text-blue-600 hover:text-blue-700"
            >
              {{ item.title }}
            </h3>
            <p class="text-gray-600">
              {{ item.body }}
            </p>
          </a>

          <template v-else>
            <h3 class="text-xl font-semibold mb-3 text-gray-900">
              {{ item.title }}
            </h3>
            <p class="text-gray-600">
              {{ item.body }}
            </p>
          </template>
        </div>
      </div>
    </div>
  </section>
</template>
