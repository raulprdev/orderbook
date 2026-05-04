<script setup lang="ts">
import { useToastsStore } from '../stores/toasts'

const toasts = useToastsStore()

function variantClass(variant: string): string {
  switch (variant) {
    case 'success': return 'border-green-300 bg-green-50 text-green-900'
    case 'error': return 'border-red-300 bg-red-50 text-red-900'
    default: return 'border-indigo-300 bg-indigo-50 text-indigo-900'
  }
}
</script>

<template>
  <div class="pointer-events-none fixed right-6 top-6 z-50 flex w-80 flex-col gap-2">
    <transition-group name="toast">
      <div
        v-for="toast in toasts.items"
        :key="toast.id"
        :class="variantClass(toast.variant)"
        class="pointer-events-auto rounded-md border px-4 py-3 shadow-lg"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="text-sm">
            <div class="font-semibold">{{ toast.title }}</div>
            <div v-if="toast.body" class="mt-1 text-xs opacity-80">
              {{ toast.body }}
            </div>
          </div>
          <button
            class="text-xs opacity-60 hover:opacity-100"
            aria-label="Dismiss"
            @click="toasts.dismiss(toast.id)"
          >
            ✕
          </button>
        </div>
      </div>
    </transition-group>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s ease;
}
.toast-enter-from {
  transform: translateX(20px);
  opacity: 0;
}
.toast-leave-to {
  opacity: 0;
}
</style>
