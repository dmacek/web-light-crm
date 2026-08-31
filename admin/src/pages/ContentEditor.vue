<script setup lang="ts">
import { ref } from 'vue'

const activeTab = ref('services')
</script>

<template>
  <div class="p-4 space-y-4">
    <header class="py-2">
      <h1 class="text-xl font-bold">Správa obsahu webu</h1>
    </header>
    <div class="p-6 text-center text-gray-500 bg-gray-50 dark:bg-zinc-800 rounded-2xl">
      Editor služeb, ceníku a Chameleon stylů.
    </div>
  </div>
</template>
