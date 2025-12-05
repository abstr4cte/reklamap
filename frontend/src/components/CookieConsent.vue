<script setup lang="ts">
import { ref, onMounted } from 'vue'

const isVisible = ref(false)

onMounted(() => {
  const consent = localStorage.getItem('cookie_consent')
  if (!consent) {
    // Small delay to not be jarring
    setTimeout(() => {
      isVisible.value = true
    }, 1000)
  }
})

const acceptCookies = () => {
  localStorage.setItem('cookie_consent', 'true')
  isVisible.value = false
}
</script>

<template>
  <Transition name="slide-up">
    <div v-if="isVisible" class="cookie-consent">
      <div class="content">
        <div class="icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="#4F46E5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 16V12" stroke="#4F46E5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 8H12.01" stroke="#4F46E5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <p class="text">
          Ta strona używa plików cookies. 
          <router-link to="/polityka-prywatnosci" class="link">Więcej informacji</router-link>
        </p>
      </div>
      <button @click="acceptCookies" class="accept-btn">
        Akceptuję
      </button>
    </div>
  </Transition>
</template>

<style scoped>
.cookie-consent {
  position: fixed;
  bottom: 2rem;
  left: 2rem;
  background: white;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  z-index: 9999;
  padding: 1.25rem;
  border-radius: 16px;
  max-width: 340px;
  width: calc(100% - 4rem);
  display: flex;
  flex-direction: column;
  gap: 1rem;
  border: 1px solid #f3f4f6;
}

.content {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.icon {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #EEF2FF;
  padding: 0.5rem;
  border-radius: 12px;
  flex-shrink: 0;
}

.text {
  color: #4B5563;
  font-size: 0.9rem;
  line-height: 1.5;
  margin: 0;
}

.link {
  color: #4F46E5;
  font-weight: 600;
  text-decoration: underline;
  transition: color 0.2s;
  white-space: nowrap;
}

.link:hover {
  color: #4338ca;
}

.accept-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s;
  width: 100%;
  box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.4);
}

.accept-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 8px -1px rgba(102, 126, 234, 0.5);
}

.accept-btn:active {
  transform: translateY(0);
}

/* Transitions */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(20px) scale(0.95);
  opacity: 0;
}

@media (max-width: 640px) {
  .cookie-consent {
    bottom: 1rem;
    left: 1rem;
    width: calc(100% - 2rem);
    max-width: none;
  }
}
</style>
