<script setup lang="ts">
import { onUnmounted, watch } from 'vue'
import AppToasts from './components/AppToasts.vue'
import { echo } from './lib/echo'
import { useAuthStore } from './stores/auth'
import { useMyOrdersStore } from './stores/myOrders'
import { useOrderbookStore } from './stores/orderbook'
import { useProfileStore } from './stores/profile'
import { useToastsStore } from './stores/toasts'

interface OrderMatchedPayload {
  buy_order_id: number
  sell_order_id: number
  buyer_user_id: number
  seller_user_id: number
  symbol: string
  price: string
  amount: string
  volume: string
  fee: string
}

const auth = useAuthStore()
const profile = useProfileStore()
const orderbook = useOrderbookStore()
const myOrders = useMyOrdersStore()
const toasts = useToastsStore()

let subscribedUserId: number | null = null

function onOrderMatched(payload: OrderMatchedPayload): void {
  profile.refresh()
  myOrders.refresh()
  orderbook.refresh()

  const userId = auth.user?.id
  const wasBuyer = userId === payload.buyer_user_id
  const action = wasBuyer ? 'Bought' : 'Sold'

  toasts.push({
    title: `${action} ${payload.amount} ${payload.symbol}`,
    body: `at $${payload.price} (volume $${payload.volume})`,
    variant: 'success',
  })
}

function subscribe(userId: number): void {
  if (subscribedUserId === userId) return
  unsubscribe()

  echo.private(`user.${userId}`).listen('.order.matched', onOrderMatched)

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
  <AppToasts />
</template>
