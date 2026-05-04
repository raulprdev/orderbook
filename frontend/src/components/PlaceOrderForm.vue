<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../lib/api'
import { useProfileStore } from '../stores/profile'
import { SIDES, SYMBOLS, type Side, type Symbol } from '../types/enums'

const profile = useProfileStore()
const router = useRouter()

const symbol = ref<Symbol>('BTC')
const side = ref<Side>('buy')
const price = ref<string>('')
const amount = ref<string>('')

const submitting = ref(false)
const errors = ref<Record<string, string[]>>({})
const generalError = ref<string | null>(null)
const successMessage = ref<string | null>(null)

// Hardcoded to mirror backend config/orderbook.php commission_basis_points (150 = 1.5%).
// In production we'd fetch this from a server-driven config endpoint to keep them in sync.
const COMMISSION_BASIS_POINTS = 150

interface PreviewBreakdown {
  volume: string
  fee: string
  total: string
}

const previewBreakdown = computed<PreviewBreakdown | null>(() => {
  const p = parseFloat(price.value)
  const a = parseFloat(amount.value)
  if (!Number.isFinite(p) || !Number.isFinite(a) || p <= 0 || a <= 0) {
    return null
  }
  const volume = p * a
  const fee = (volume * COMMISSION_BASIS_POINTS) / 10_000
  return {
    volume: volume.toFixed(2),
    fee: fee.toFixed(2),
    total: (volume + fee).toFixed(2),
  }
})

function fieldError(field: string): string | null {
  return errors.value[field]?.[0] ?? null
}

async function submit(): Promise<void> {
  errors.value = {}
  generalError.value = null
  successMessage.value = null
  submitting.value = true
  try {
    const { data } = await api.post('/api/orders', {
      symbol: symbol.value,
      side: side.value,
      price: price.value,
      amount: amount.value,
    })
    successMessage.value = `Order #${data.order.id} placed (${data.order.status}).`
    price.value = ''
    amount.value = ''
    await profile.refresh()
    setTimeout(() => router.push({ name: 'overview' }), 600)
  } catch (e: any) {
    if (e.response?.status === 422) {
      if (e.response.data.errors) {
        errors.value = e.response.data.errors
      } else {
        generalError.value = e.response.data.message ?? 'Order rejected'
      }
    } else {
      generalError.value = e.response?.data?.message ?? 'Failed to place order'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section class="rounded-lg bg-white p-6 shadow">
    <h2 class="text-base font-semibold text-gray-900">Place order</h2>

    <form class="mt-4 space-y-4" @submit.prevent="submit">
      <div class="grid grid-cols-2 gap-4">
        <label class="block">
          <span class="text-sm text-gray-700">Symbol</span>
          <select
            v-model="symbol"
            class="mt-1 block w-full rounded border border-gray-300 px-3 py-2"
          >
            <option v-for="s in SYMBOLS" :key="s" :value="s">{{ s }}</option>
          </select>
        </label>

        <label class="block">
          <span class="text-sm text-gray-700">Side</span>
          <select
            v-model="side"
            class="mt-1 block w-full rounded border border-gray-300 px-3 py-2"
          >
            <option v-for="s in SIDES" :key="s" :value="s">{{ s }}</option>
          </select>
        </label>

        <label class="block">
          <span class="text-sm text-gray-700">Price (USD)</span>
          <input
            v-model="price"
            type="text"
            inputmode="decimal"
            placeholder="95000"
            required
            class="mt-1 block w-full rounded border border-gray-300 px-3 py-2"
          >
          <span v-if="fieldError('price')" class="text-xs text-red-600">{{ fieldError('price') }}</span>
        </label>

        <label class="block">
          <span class="text-sm text-gray-700">Amount</span>
          <input
            v-model="amount"
            type="text"
            inputmode="decimal"
            placeholder="0.01"
            required
            class="mt-1 block w-full rounded border border-gray-300 px-3 py-2"
          >
          <span v-if="fieldError('amount')" class="text-xs text-red-600">{{ fieldError('amount') }}</span>
        </label>
      </div>

      <div v-if="previewBreakdown" class="space-y-1 rounded bg-gray-50 p-3 text-sm text-gray-700">
        <div class="flex justify-between">
          <span>Volume (price × amount)</span>
          <span class="tabular-nums">${{ previewBreakdown.volume }}</span>
        </div>
        <template v-if="side === 'buy'">
          <div class="flex justify-between text-gray-500">
            <span>Fee (1.5%)</span>
            <span class="tabular-nums">+${{ previewBreakdown.fee }}</span>
          </div>
          <div class="flex justify-between border-t border-gray-200 pt-1 font-semibold text-gray-900">
            <span>You'll pay</span>
            <span class="tabular-nums">${{ previewBreakdown.total }}</span>
          </div>
        </template>
        <template v-else>
          <div class="flex justify-between border-t border-gray-200 pt-1 font-semibold text-gray-900">
            <span>You'll receive</span>
            <span class="tabular-nums">${{ previewBreakdown.volume }}</span>
          </div>
        </template>
      </div>

      <div v-if="generalError" class="rounded bg-red-50 p-3 text-sm text-red-700">
        {{ generalError }}
      </div>
      <div v-if="successMessage" class="rounded bg-green-50 p-3 text-sm text-green-700">
        {{ successMessage }}
      </div>

      <button
        type="submit"
        :disabled="submitting"
        class="w-full rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700 disabled:opacity-50"
      >
        {{ submitting ? 'Placing…' : `Place ${side} order` }}
      </button>
    </form>
  </section>
</template>