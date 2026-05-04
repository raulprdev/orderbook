import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/LoginView.vue'),
    meta: { guest: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../views/RegisterView.vue'),
    meta: { guest: true },
  },
  {
    path: '/',
    name: 'overview',
    component: () => import('../views/OverviewView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/place-order',
    name: 'place-order',
    component: () => import('../views/PlaceOrderView.vue'),
    meta: { requiresAuth: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

let bootstrapped = false

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!bootstrapped) {
    await auth.bootstrap()
    bootstrapped = true
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' }
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'overview' }
  }
})

export default router