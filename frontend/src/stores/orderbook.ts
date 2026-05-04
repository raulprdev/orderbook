import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../lib/api'
import type { Side, Symbol } from '../types/enums'

export interface OpenOrder {
  id: number
  symbol: Symbol
  side: Side
  price: string
  amount: string
  status: string
}

export const useOrderbookStore = defineStore('orderbook', () => {
  const symbol = ref<Symbol>('BTC')
  const orders = ref<OpenOrder[]>([])
  const loading = ref(false)

  async function refresh(): Promise<void> {
    loading.value = true
    try {
      const { data } = await api.get('/api/orders', {
        params: { symbol: symbol.value },
      })
      orders.value = data.orders
    } finally {
      loading.value = false
    }
  }

  async function changeSymbol(next: Symbol): Promise<void> {
    symbol.value = next
    await refresh()
  }

  return { symbol, orders, loading, refresh, changeSymbol }
})