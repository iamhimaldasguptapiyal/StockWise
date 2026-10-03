import { defineStore } from 'pinia'
import { ref } from 'vue'

let nextId = 1

export const useToastStore = defineStore('toast', () => {
  const toasts = ref([])

  function show(message, type = 'success') {
    const id = nextId++
    toasts.value.push({ id, message, type })
    setTimeout(() => dismiss(id), 4000)
  }

  function dismiss(id) {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }

  return {
    toasts,
    dismiss,
    success: (message) => show(message, 'success'),
    error: (message) => show(message, 'error'),
  }
})
