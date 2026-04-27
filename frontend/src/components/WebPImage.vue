<script setup lang="ts">
import { computed } from 'vue'
import { getFullImageUrl } from '../services/api'

interface Props {
  src: string
  alt: string
  class?: string
  eager?: boolean
  width?: string | number
  height?: string | number
}

const props = defineProps<Props>()

// Konwertuj ścieżkę JPG na WebP
const webpSrc = computed(() => {
  const fullUrl = getFullImageUrl(props.src)
  // Zamień rozszerzenie .jpg lub .jpeg na .webp
  return fullUrl.replace(/\.(jpg|jpeg)$/i, '.webp')
})

const jpgSrc = computed(() => {
  return getFullImageUrl(props.src)
})
</script>

<template>
  <picture>
    <!-- WebP format dla nowoczesnych przeglądarek -->
    <source :srcset="webpSrc" type="image/webp">
    <!-- Fallback JPG dla starszych przeglądarek -->
    <img
      :src="jpgSrc"
      :alt="alt"
      :class="props.class"
      :loading="props.eager ? 'eager' : 'lazy'"
      :fetchpriority="props.eager ? 'high' : 'auto'"
      :width="props.width"
      :height="props.height"
      decoding="async"
    >
  </picture>
</template>

<style scoped>
picture {
  display: contents;
}

img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
