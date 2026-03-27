import { ref } from 'vue'
import type ToastNotification from '../components/ToastNotification.vue'

type ToastType = 'success' | 'error' | 'info'

// Global toast instance
const toastInstance = ref<InstanceType<typeof ToastNotification> | null>(null)

export function useToast() {
  const setToastInstance = (instance: InstanceType<typeof ToastNotification> | null) => {
    toastInstance.value = instance
    console.log('[useToast] Toast instance set:', !!instance)
  }

  const showToast = (message: string, type: ToastType = 'info') => {
    console.log('[useToast] showToast called:', message, type, 'hasInstance:', !!toastInstance.value)
    if (toastInstance.value) {
      toastInstance.value.add(message, type)
      console.log('[useToast] Toast added successfully')
    } else {
      console.error('[useToast] Toast instance NOT initialized!', message, type)
    }
  }

  return {
    setToastInstance,
    showToast,
    toastInstance
  }
}
