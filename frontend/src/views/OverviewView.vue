<script setup lang="ts">
import { onMounted } from 'vue'
import AppNav from '../components/AppNav.vue'
import MyOrdersPanel from '../components/MyOrdersPanel.vue'
import OrderbookPanel from '../components/OrderbookPanel.vue'
import { useProfileStore } from '../stores/profile'

const profile = useProfileStore()

onMounted(() => {
  profile.refresh()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <AppNav />

    <main class="mx-auto max-w-5xl space-y-6 p-6">
      <section class="rounded-lg bg-white p-6 shadow">
        <div class="flex items-center justify-between">
          <h2 class="text-base font-semibold text-gray-900">Wallet</h2>
          <button
            class="text-xs text-indigo-600 hover:underline"
            :disabled="profile.loading"
            @click="profile.refresh"
          >
            {{ profile.loading ? 'Refreshing…' : 'Refresh' }}
          </button>
        </div>

        <div class="mt-4">
          <div class="text-sm text-gray-500">USD balance</div>
          <div class="mt-1 text-2xl font-semibold text-gray-900">
            ${{ profile.balance }}
          </div>
        </div>

        <div class="mt-6">
          <div class="text-sm text-gray-500">Assets</div>
          <table class="mt-2 w-full text-sm">
            <thead class="text-left text-xs uppercase text-gray-500">
              <tr>
                <th class="pb-2">Symbol</th>
                <th class="pb-2 text-right">Available</th>
                <th class="pb-2 text-right">Locked</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="profile.assets.length === 0" class="text-gray-500">
                <td colspan="3" class="py-2 italic">No assets yet.</td>
              </tr>
              <tr
                v-for="asset in profile.assets"
                :key="asset.symbol"
                class="border-t border-gray-100"
              >
                <td class="py-2 font-medium text-gray-900">{{ asset.symbol }}</td>
                <td class="py-2 text-right tabular-nums text-gray-700">
                  {{ asset.amount }}
                </td>
                <td class="py-2 text-right tabular-nums text-gray-500">
                  {{ asset.locked_amount }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <MyOrdersPanel />

      <OrderbookPanel />
    </main>
  </div>
</template>