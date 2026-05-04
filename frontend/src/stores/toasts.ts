import { defineStore } from 'pinia'
import { ref } from 'vue'

export type ToastVariant = 'info' | 'success' | 'error'

export interface Toast {
  id: number
  title: string
  body?: string
  variant: ToastVariant
}

export const useToastsStore = defineStore('toasts', () => {
  const items = ref<Toast[]>([])
  let nextId = 1

  function push(toast: Omit<Toast, 'id'>, ttlMs = 10_000): void {
    const id = nextId++
    items.value.push({ ...toast, id })
    setTimeout(() => dismiss(id), ttlMs)
  }

  function dismiss(id: number): void {
    items.value = items.value.filter((t) => t.id !== id)
  }

  return { items, push, dismiss }
})
