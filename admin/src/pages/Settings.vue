<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const logout = () => {
  authStore.logout()
  router.push('/onboarding')
}
</script>

<template>
  <div class="p-4 space-y-4">
    <header class="py-2">
      <h1 class="text-xl font-bold">Nastavení</h1>
    </header>
    <div class="p-4 bg-gray-50 dark:bg-zinc-800 rounded-2xl space-y-2">
      <p class="text-sm font-semibold">Uživatel: {{ authStore.business?.email || 'Přihlášen' }}</p>
      <button
        @click="logout"
        class="w-full py-2.5 px-4 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold transition"
      >
        Odhlásit se
      </button>
    </div>
  </div>
</template>
