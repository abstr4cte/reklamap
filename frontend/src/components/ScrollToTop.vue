<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

const isVisible = ref(false)

const handleScroll = () => {
  isVisible.value = window.scrollY > 400
}

const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true })
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <Teleport to="body">
    <Transition name="scroll-top">
      <button
        v-if="isVisible"
        @click="scrollToTop"
        class="scroll-to-top btn-interactive"
        aria-label="Wróć na górę strony"
        title="Wróć na górę"
      >
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 15l-6-6-6 6"/>
        </svg>
      </button>
    </Transition>
  </Teleport>
</template>

<style scoped>
.scroll-to-top {
  position: fixed;
  bottom: 6.5rem;
  right: 2rem;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
  z-index: 990;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.scroll-to-top:hover {
  transform: translateY(-4px) scale(1.05);
  box-shadow: 0 12px 32px rgba(102, 126, 234, 0.5);
}

.scroll-to-top:active {
  transform: translateY(-2px) scale(0.98);
}

/* Transition */
.scroll-top-enter-active,
.scroll-top-leave-active {
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

.scroll-top-enter-from,
.scroll-top-leave-to {
  opacity: 0;
  transform: translateY(20px) scale(0.8);
}

@media (max-width: 768px) {
  .scroll-to-top {
    bottom: 5rem;
    right: 1.25rem;
    width: 36px;
    height: 36px;
  }
}
</style>
