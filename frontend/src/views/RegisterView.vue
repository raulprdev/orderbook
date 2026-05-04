<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const submitting = ref(false)
const errors = ref<Record<string, string[]>>({})
const generalError = ref<string | null>(null)

async function submit(): Promise<void> {
  errors.value = {}
  generalError.value = null
  submitting.value = true
  try {
    await auth.register({
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    await router.push({ name: 'overview' })
  } catch (e: any) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors ?? {}
    } else {
      generalError.value = e.response?.data?.message ?? 'Registration failed'
    }
  } finally {
    submitting.value = false
  }
}

function fieldError(field: string): string | null {
  return errors.value[field]?.[0] ?? null
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <form
      class="w-full max-w-sm space-y-4 rounded-lg bg-white p-8 shadow"
      @submit.prevent="submit"
    >
      <h1 class="text-xl font-semibold text-gray-900">Create account</h1>

      <div v-if="generalError" class="rounded bg-red-50 p-3 text-sm text-red-700">
        {{ generalError }}
      </div>

      <label class="block">
        <span class="text-sm text-gray-700">Name</span>
        <input
          v-model="name"
          type="text"
          required
          class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none"
        >
        <span v-if="fieldError('name')" class="text-xs text-red-600">{{ fieldError('name') }}</span>
      </label>

      <label class="block">
        <span class="text-sm text-gray-700">Email</span>
        <input
          v-model="email"
          type="email"
          required
          autocomplete="email"
          class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none"
        >
        <span v-if="fieldError('email')" class="text-xs text-red-600">{{ fieldError('email') }}</span>
      </label>

      <label class="block">
        <span class="text-sm text-gray-700">Password</span>
        <input
          v-model="password"
          type="password"
          required
          autocomplete="new-password"
          minlength="8"
          class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none"
        >
        <span v-if="fieldError('password')" class="text-xs text-red-600">{{ fieldError('password') }}</span>
      </label>

      <label class="block">
        <span class="text-sm text-gray-700">Confirm password</span>
        <input
          v-model="passwordConfirmation"
          type="password"
          required
          autocomplete="new-password"
          minlength="8"
          class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none"
        >
      </label>

      <button
        type="submit"
        :disabled="submitting"
        class="w-full rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700 disabled:opacity-50"
      >
        {{ submitting ? 'Registering…' : 'Register' }}
      </button>

      <p class="text-center text-sm text-gray-600">
        Have an account?
        <RouterLink to="/login" class="text-indigo-600 hover:underline">Sign in</RouterLink>
      </p>
    </form>
  </div>
</template>