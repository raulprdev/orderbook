<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

async function logout(): Promise<void> {
  await auth.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <header class="bg-white shadow">
    <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
      <div class="flex items-center gap-6">
        <h1 class="text-lg font-semibold text-gray-900">Orderbook</h1>
        <nav class="flex gap-4 text-sm">
          <RouterLink
            :to="{ name: 'overview' }"
            class="text-gray-700 hover:text-indigo-600"
            active-class="font-semibold text-indigo-600"
          >
            Overview
          </RouterLink>
          <RouterLink
            :to="{ name: 'place-order' }"
            class="text-gray-700 hover:text-indigo-600"
            active-class="font-semibold text-indigo-600"
          >
            Place order
          </RouterLink>
        </nav>
      </div>
      <div class="flex items-center gap-4 text-sm">
        <span class="text-gray-600">{{ auth.user?.email }}</span>
        <button
          class="rounded bg-gray-200 px-3 py-1 text-gray-800 hover:bg-gray-300"
          @click="logout"
        >
          Sign out
        </button>
      </div>
    </div>
  </header>
</template>