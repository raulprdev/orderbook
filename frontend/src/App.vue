<script setup lang="ts">
import { onUnmounted, watch } from 'vue'
import { echo } from './lib/echo'
import { useAuthStore } from './stores/auth'
import { useMyOrdersStore } from './stores/myOrders'
import { useOrderbookStore } from './stores/orderbook'
import { useProfileStore } from './stores/profile'

const auth = useAuthStore()
const profile = useProfileStore()
const orderbook = useOrderbookStore()
const myOrders = useMyOrdersStore()

let subscribedUserId: number | null = null

function subscribe(userId: number): void {
  if (subscribedUserId === userId) return
  unsubscribe()

  echo.private(`user.${userId}`).listen('.order.matched', () => {
    profile.refresh()
    myOrders.refresh()
    orderbook.refresh()
  })

  subscribedUserId = userId
}

function unsubscribe(): void {
  if (subscribedUserId !== null) {
    echo.leave(`private-user.${subscribedUserId}`)
    subscribedUserId = null
  }
}

watch(
  () => auth.user?.id,
  (id) => {
    if (typeof id === 'number') {
      subscribe(id)
    } else {
      unsubscribe()
    }
  },
  { immediate: true },
)

onUnmounted(() => unsubscribe())
</script>

<template>
  <RouterView />
</template>