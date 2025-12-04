<template>
  <div class="date-picker-wrapper">
    <input
      type="date"
      :value="modelValue ? formatForInput(modelValue) : ''"
      @change="handleChange"
      :min="minDate ? formatForInput(minDate) : ''"
      class="hidden-date-input"
    />
    <div class="date-display" @click="focusInput">
      {{ modelValue ? formatForDisplay(modelValue) : placeholder }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps<{
  modelValue: Date | null
  minDate?: Date
  placeholder?: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: Date | null): void
}>()

const hiddenInput = ref<HTMLInputElement>()

const formatForInput = (date: Date): string => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const formatForDisplay = (date: Date): string => {
  const year = String(date.getFullYear()).slice(-2)
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${day}.${month}.${year}`
}

const handleChange = (event: Event) => {
  const value = (event.target as HTMLInputElement).value
  if (value) {
    const date = new Date(value + 'T00:00:00')
    emit('update:modelValue', date)
  } else {
    emit('update:modelValue', null)
  }
}

const focusInput = () => {
  const input = document.querySelector('.hidden-date-input') as HTMLInputElement
  input?.showPicker?.()
}
</script>

<style scoped>
.date-picker-wrapper {
  position: relative;
}

.hidden-date-input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
  width: 0;
  height: 0;
}

.date-display {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 0.95rem;
  transition: all 0.2s ease;
  font-family: inherit;
  background: white;
  cursor: pointer;
  color: #1f2937;
}

.date-display:hover {
  border-color: #9ca3af;
}

.date-display:focus-within {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
</style>
