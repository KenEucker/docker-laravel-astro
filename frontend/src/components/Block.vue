<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { fetchBlock, type BlockData } from '../lib/blocks/api'

import Hero from '../blocks/Hero.vue'
import RichText from '../blocks/RichText.vue'
import ImageBlock from '../blocks/ImageBlock.vue'
import Cta from '../blocks/Cta.vue'
import FeatureGrid from '../blocks/FeatureGrid.vue'
import HtmlBlock from '../blocks/HtmlBlock.vue'

const props = withDefaults(
  defineProps<{
    blockKey: string
    preview?: boolean
    hideOnFail?: boolean
  }>(),
  {
    preview: false,
    hideOnFail: true,
  }
)

const block = ref<BlockData | null>(null)
const loading = ref(true)
const hidden = ref(false)

const componentMap: Record<string, any> = {
  hero: Hero,
  richText: RichText,
  image: ImageBlock,
  cta: Cta,
  featureGrid: FeatureGrid,
  html: HtmlBlock,
}

const Component = computed(() => {
  const type = block.value?.type
  return type ? componentMap[type] ?? null : null
})

const hasRenderableBlock = computed(() => !!block.value && !!Component.value)

const load = async () => {
  loading.value = true
  hidden.value = false
  block.value = null

  try {
    const data = await fetchBlock(props.blockKey, props.preview)

    if (!data) {
      if (import.meta.env.DEV) {
        console.warn(`[BlockClient] Block not found: "${props.blockKey}"`)
      }
      if (props.hideOnFail) hidden.value = true
      return
    }

    if (!componentMap[data.type]) {
      console.error(
        `[BlockClient] Unknown block type "${data.type}" for "${props.blockKey}".`
      )
      if (props.hideOnFail) hidden.value = true
      return
    }

    block.value = data
  } catch (err) {
    console.error(`[BlockClient] Failed to load block "${props.blockKey}"`, err)
    if (props.hideOnFail) hidden.value = true
  } finally {
    loading.value = false
  }
}

// Single source of truth: load immediately + on prop changes
watch(
  () => [props.blockKey, props.preview],
  () => { void load() },
  { immediate: true }
)
</script>

<template>
  <!-- Hide self entirely on fail -->
  <div v-if="!hidden">
    <!-- Placeholder while loading -->
    <slot v-if="loading" name="placeholder">
      <div class="p-4 border-2 border-yellow-500 bg-yellow-50 text-yellow-700">
        Loading block…
      </div>
    </slot>

    <!-- Render block when ready -->
    <component
      v-else-if="hasRenderableBlock"
      :is="Component"
      :data="block!.data"
    />
  </div>
</template>
