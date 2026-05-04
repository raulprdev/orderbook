<script setup lang="ts">
import { onMounted } from 'vue'
import { useOrderbookStore } from '../stores/orderbook'
import { SYMBOLS, type Symbol } from '../types/enums'

const orderbook = useOrderbookStore()

onMounted(() => {
  orderbook.refresh()
})

function selectSymbol(event: Event): void {
  const value = (event.target as HTMLSelectElement).value as Symbol
  orderbook.changeSymbol(value)
}
</script>

<template>
  <section class="rounded-lg bg-white p-6 shadow">
    <div class="flex items-center justify-between">
      <h2 class="text-base font-semibold text-gray-900">Orderbook</h2>
      <div class="flex items-center gap-2">
        <select
          :value="orderbook.symbol"
          class="rounded border border-gray-300 px-2 py-1 text-sm"
          @change="selectSymbol"
        >
          <option v-for="s in SYMBOLS" :key="s" :value="s">{{ s }}</option>
        </select>
        <button
          class="text-xs text-indigo-600 hover:underline"
          :disabled="orderbook.loading"
          @click="orderbook.refresh"
        >
          {{ orderbook.loading ? 'Refreshing…' : 'Refresh' }}
        </button>
      </div>
    </div>

    <table class="mt-4 w-full text-sm">
      <thead class="text-left text-xs uppercase text-gray-500">
        <tr>
          <th class="pb-2">Side</th>
          <th class="pb-2 text-right">Price</th>
          <th class="pb-2 text-right">Amount</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="orderbook.orders.length === 0" class="text-gray-500">
          <td colspan="3" class="py-2 italic">No open orders for {{ orderbook.symbol }}.</td>
        </tr>
        <tr
          v-for="order in orderbook.orders"
          :key="order.id"
          class="border-t border-gray-100"
        >
          <td class="py-2">
            <span
              :class="order.side === 'buy' ? 'text-green-700' : 'text-red-700'"
              class="font-medium uppercase"
            >
              {{ order.side }}
            </span>
          </td>
          <td class="py-2 text-right tabular-nums text-gray-900">${{ order.price }}</td>
          <td class="py-2 text-right tabular-nums text-gray-700">{{ order.amount }}</td>
        </tr>
      </tbody>
    </table>
  </section>
</template>