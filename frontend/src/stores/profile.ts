import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../lib/api'
import type { Symbol } from '../types/enums'

export interface AssetSummary {
  symbol: Symbol
  amount: string
  locked_amount: string
}

export const useProfileStore = defineStore('profile', () => {
  const balance = ref<string>('0.00')
  const assets = ref<AssetSummary[]>([])
  const loading = ref(false)

  async function refresh(): Promise<void> {
    loading.value = true
    try {
      const { data } = await api.get('/api/profile')
      balance.value = data.balance
      assets.value = data.assets
    } finally {
      loading.value = false
    }
  }

  return { balance, assets, loading, refresh }
})