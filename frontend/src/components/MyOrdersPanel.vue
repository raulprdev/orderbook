<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useMyOrdersStore } from '../stores/myOrders'
import { useProfileStore } from '../stores/profile'
import { ORDER_STATUSES, SIDES, SYMBOLS } from '../types/enums'

const myOrders = useMyOrdersStore()
const profile = useProfileStore()

type Filter<T extends string> = T | 'all'

const symbolFilter = ref<Filter<string>>('all')
const sideFilter = ref<Filter<string>>('all')
const statusFilter = ref<Filter<string>>('all')

const filteredOrders = computed(() =>
  myOrders.orders.filter((order) =>
    (symbolFilter.value === 'all' || order.symbol === symbolFilter.value) &&
    (sideFilter.value === 'all' || order.side === sideFilter.value) &&
    (statusFilter.value === 'all' || order.status === statusFilter.value)
  )
)

onMounted(() => {
  myOrders.refresh()
})

async function cancelOrder(orderId: number): Promise<void> {
  try {
    await myOrders.cancel(orderId)
    await profile.refresh()
  } catch (e: any) {
    alert(e.response?.data?.message ?? 'Cancel failed')
  }
}

function statusClass(status: string): string {
  switch (status) {
    case 'open': return 'bg-blue-100 text-blue-800'
    case 'filled': return 'bg-green-100 text-green-800'
    case 'cancelled': return 'bg-gray-100 text-gray-700'
    default: return 'bg-gray-100 text-gray-700'
  }
}
</script>

<template>
  <section class="rounded-lg bg-white p-6 shadow">
    <div class="flex items-center justify-between">
      <h2 class="text-base font-semibold text-gray-900">My orders</h2>
      <button
        class="text-xs text-indigo-600 hover:underline"
        :disabled="myOrders.loading"
        @click="myOrders.refresh"
      >
        {{ myOrders.loading ? 'Refreshing…' : 'Refresh' }}
      </button>
    </div>

    <div class="mt-4 flex flex-wrap gap-3 text-xs">
      <label class="flex items-center gap-1 text-gray-600">
        Symbol
        <select v-model="symbolFilter" class="rounded border border-gray-300 px-2 py-1">
          <option value="all">All</option>
          <option v-for="s in SYMBOLS" :key="s" :value="s">{{ s }}</option>
        </select>
      </label>
      <label class="flex items-center gap-1 text-gray-600">
        Side
        <select v-model="sideFilter" class="rounded border border-gray-300 px-2 py-1">
          <option value="all">All</option>
          <option v-for="s in SIDES" :key="s" :value="s">{{ s }}</option>
        </select>
      </label>
      <label class="flex items-center gap-1 text-gray-600">
        Status
        <select v-model="statusFilter" class="rounded border border-gray-300 px-2 py-1">
          <option value="all">All</option>
          <option v-for="s in ORDER_STATUSES" :key="s" :value="s">{{ s }}</option>
        </select>
      </label>
    </div>

    <table class="mt-3 w-full text-sm">
      <thead class="text-left text-xs uppercase text-gray-500">
        <tr>
          <th class="pb-2">Symbol</th>
          <th class="pb-2">Side</th>
          <th class="pb-2 text-right">Price</th>
          <th class="pb-2 text-right">Amount</th>
          <th class="pb-2">Status</th>
          <th class="pb-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="filteredOrders.length === 0" class="text-gray-500">
          <td colspan="6" class="py-2 italic">
            {{ myOrders.orders.length === 0 ? 'No orders yet.' : 'No orders match the current filter.' }}
          </td>
        </tr>
        <tr
          v-for="order in filteredOrders"
          :key="order.id"
          class="border-t border-gray-100"
        >
          <td class="py-2 font-medium text-gray-900">{{ order.symbol }}</td>
          <td class="py-2">
            <span
              :class="order.side === 'buy' ? 'text-green-700' : 'text-red-700'"
              class="font-medium uppercase"
            >
              {{ order.side }}
            </span>
          </td>
          <td class="py-2 text-right tabular-nums text-gray-700">${{ order.price }}</td>
          <td class="py-2 text-right tabular-nums text-gray-700">{{ order.amount }}</td>
          <td class="py-2">
            <span
              :class="statusClass(order.status)"
              class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium uppercase"
            >
              {{ order.status }}
            </span>
          </td>
          <td class="py-2 text-right">
            <button
              v-if="order.status === 'open'"
              class="text-xs text-red-600 hover:underline disabled:opacity-50"
              :disabled="myOrders.cancellingIds.has(order.id)"
              @click="cancelOrder(order.id)"
            >
              {{ myOrders.cancellingIds.has(order.id) ? 'Cancelling…' : 'Cancel' }}
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </section>
</template>