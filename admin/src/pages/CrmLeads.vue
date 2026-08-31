<script setup lang="ts">
import { ref } from 'vue'

const leads = ref([])
</script>

<template>
  <div class="p-4 space-y-4">
    <header class="flex justify-between items-center py-2">
      <h1 class="text-xl font-bold">Poptávky (CRM)</h1>
    </header>
    <div class="p-6 text-center text-gray-500 bg-gray-50 dark:bg-zinc-800 rounded-2xl">
      Zatím nemáte žádné nové poptávky.
    </div>
  </div>
</template>
