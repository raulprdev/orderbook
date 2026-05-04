<script setup lang="ts">
import AppNav from '../components/AppNav.vue'
import PlaceOrderForm from '../components/PlaceOrderForm.vue'
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <AppNav />

    <main class="mx-auto max-w-2xl p-6">
      <PlaceOrderForm />
    </main>
  </div>
</template>