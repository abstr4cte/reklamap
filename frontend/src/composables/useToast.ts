import { ref } from 'vue'
import type ToastNotification from '../components/ToastNotification.vue'

type ToastType = 'success' | 'error' | 'info'

// Global toast instance
const toastInstance = ref<InstanceType<typeof ToastNotification> | null>(null)

export function useToast() {
  const setToastInstance = (instance: InstanceType<typeof ToastNotification> | null) => {
    toastInstance.value = instance
  }

  const showToast = (message: string, type: ToastType = 'info') => {
    if (toastInstance.value) {
      toastInstance.value.add(message, type)
    }
  }

  return {
    setToastInstance,
    showToast,
    toastInstance
  }
}
