<script setup lang="ts">
import { ref } from 'vue'
import FeedbackModal from './FeedbackModal.vue'

const feedbackModal = ref<InstanceType<typeof FeedbackModal> | null>(null)

const openFeedback = () => {
  feedbackModal.value?.openModal()
}
</script>

<template>
  <div>
    <button @click="openFeedback" class="feedback-button" aria-label="Wyślij feedback">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>

    <FeedbackModal ref="feedbackModal" />
  </div>
</template>

<style scoped>
.feedback-button {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 1rem;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  font-weight: 600;
  font-size: 1rem;
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
  transition: all 0.3s ease;
  z-index: 1000;
  width: 56px;
  height: 56px;
}

.feedback-button:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(102, 126, 234, 0.5);
}

.feedback-button:active {
  transform: translateY(-2px);
}

.button-text {
  font-size: 0.95rem;
}

@media (max-width: 640px) {
  .feedback-button {
    bottom: 1rem;
    right: 1rem;
    padding: 0.875rem;
    width: 48px;
    height: 48px;
  }
  
  .feedback-button svg {
    width: 20px;
    height: 20px;
  }

  .button-text {
    font-size: 0.875rem;
  }
}
</style>
