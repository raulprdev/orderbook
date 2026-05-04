import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../lib/api'
import type { OrderStatus, Side, Symbol } from '../types/enums'

export interface MyOrder {
  id: number
  symbol: Symbol
  side: Side
  price: string
  amount: string
  status: OrderStatus
}

export const useMyOrdersStore = defineStore('myOrders', () => {
  const orders = ref<MyOrder[]>([])
  const loading = ref(false)
  const cancellingIds = ref<Set<number>>(new Set())

  async function refresh(): Promise<void> {
    loading.value = true
    try {
      const { data } = await api.get('/api/orders/mine')
      orders.value = data.orders
    } finally {
      loading.value = false
    }
  }

  async function cancel(orderId: number): Promise<void> {
    cancellingIds.value.add(orderId)
    try {
      await api.post(`/api/orders/${orderId}/cancel`)
      await refresh()
    } finally {
      cancellingIds.value.delete(orderId)
    }
  }

  return { orders, loading, cancellingIds, refresh, cancel }
})