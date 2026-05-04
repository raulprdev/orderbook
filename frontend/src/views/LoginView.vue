<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const submitting = ref(false)
const error = ref<string | null>(null)

async function submit(): Promise<void> {
  error.value = null
  submitting.value = true
  try {
    await auth.login(email.value, password.value)
    await router.push({ name: 'dashboard' })
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'Login failed'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <form
      class="w-full max-w-sm space-y-4 rounded-lg bg-white p-8 shadow"
      @submit.prevent="submit"
    >
      <h1 class="text-xl font-semibold text-gray-900">Sign in</h1>

      <div v-if="error" class="rounded bg-red-50 p-3 text-sm text-red-700">
        {{ error }}
      </div>

      <label class="block">
        <span class="text-sm text-gray-700">Email</span>
        <input
          v-model="email"
          type="email"
          required
          autocomplete="email"
          class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none"
        >
      </label>

      <label class="block">
        <span class="text-sm text-gray-700">Password</span>
        <input
          v-model="password"
          type="password"
          required
          autocomplete="current-password"
          class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none"
        >
      </label>

      <button
        type="submit"
        :disabled="submitting"
        class="w-full rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700 disabled:opacity-50"
      >
        {{ submitting ? 'Signing in…' : 'Sign in' }}
      </button>

      <p class="text-center text-sm text-gray-600">
        No account?
        <RouterLink to="/register" class="text-indigo-600 hover:underline">Register</RouterLink>
      </p>
    </form>
  </div>
</template>