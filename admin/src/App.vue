<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const showBottomNav = computed(() => {
  return authStore.isAuthenticated && route.path !== '/onboarding' && route.path !== '/login'
})
</script>

<template>
  <div class="min-h-screen bg-gray-100 dark:bg-zinc-950 flex justify-center">
    <div class="w-full max-w-[440px] bg-white dark:bg-zinc-900 min-h-screen flex flex-col shadow-2xl relative">
      <router-view class="flex-1 pb-20" />

      <!-- Mobile Bottom Navigation Bar -->
      <nav
        v-if="showBottomNav"
        class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[440px] bg-white/90 dark:bg-zinc-900/90 backdrop-blur-md border-t border-gray-200 dark:border-zinc-800 flex justify-around py-3 px-2 z-40"
      >
        <router-link
          to="/crm"
          class="flex flex-col items-center gap-1 text-xs font-medium text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
          active-class="text-blue-600 dark:text-blue-400 font-bold"
        >
          <span class="text-lg">📥</span>
          <span>Poptávky</span>
        </router-link>

        <router-link
          to="/editor"
          class="flex flex-col items-center gap-1 text-xs font-medium text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
          active-class="text-blue-600 dark:text-blue-400 font-bold"
        >
          <span class="text-lg">✏️</span>
          <span>Můj Web</span>
        </router-link>

        <router-link
          to="/settings"
          class="flex flex-col items-center gap-1 text-xs font-medium text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
          active-class="text-blue-600 dark:text-blue-400 font-bold"
        >
          <span class="text-lg">⚙️</span>
          <span>Nastavení</span>
        </router-link>
      </nav>
    </div>
  </div>
</template>
