<script setup lang="ts">
import { ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useOnboardingStore } from '@/stores/onboarding'
import { useApi } from '@/composables/useApi'
import type { AresResult } from '@/types'

const store = useOnboardingStore()
const api = useApi()

const ico = ref(store.draft.ico)
const searching = ref(false)
const errorMessage = ref('')
const aresFound = ref(false)

const searchAres = useDebounceFn(async (value: string) => {
  const clean = value.replace(/\s+/g, '')
  if (clean.length !== 8 || !/^\d{8}$/.test(clean)) {
    return
  }

  searching.value = true
  errorMessage.value = ''

  try {
    const data = await api.get<AresResult>(`/api/v1/integrations/ares/${clean}`)
    if (data && data.company_name) {
      store.setAresData(data)
      aresFound.value = true
    }
  } catch (err: any) {
    errorMessage.value = err.message || 'IČO nebylo nalezeno v registru ARES.'
    aresFound.value = false
  } finally {
    searching.value = false
  }
}, 400)

watch(ico, (newVal) => {
  store.draft.ico = newVal
  searchAres(newVal)
})

const handleContinue = () => {
  if (!store.draft.company_name) {
    errorMessage.value = 'Vyplňte prosím název firmy nebo zadejte platné IČO.'
    return
  }
  store.nextStep()
}
</script>

<template>
  <div class="space-y-6">
    <div class="text-center space-y-2">
      <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 rounded-full text-xs font-semibold uppercase tracking-wider">
        Krok 1 z 5
      </span>
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Zadejte své IČO</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Data o firmě a adresu automaticky načteme z registru ARES.
      </p>
    </div>

    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          IČO (8 číslic)
        </label>
        <div class="relative">
          <input
            v-model="ico"
            type="text"
            inputmode="numeric"
            maxlength="8"
            placeholder="např. 12345678"
            class="w-full px-4 py-3 text-lg rounded-xl border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none transition shadow-xs"
          />
          <div v-if="searching" class="absolute right-3 top-3.5">
            <span class="animate-spin text-blue-600">⏳</span>
          </div>
        </div>
        <p v-if="errorMessage" class="text-xs text-red-500 mt-1.5 font-medium">
          {{ errorMessage }}
        </p>
      </div>

      <!-- Auto-filled company preview -->
      <div v-if="store.draft.company_name" class="p-4 rounded-xl bg-blue-50/60 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 space-y-1.5 animate-fade-in">
        <p class="text-xs font-semibold uppercase tracking-wider text-blue-800 dark:text-blue-300">
          Nalezený subjekt
        </p>
        <p class="font-bold text-gray-900 dark:text-white text-base">
          {{ store.draft.company_name }}
        </p>
        <p class="text-sm text-gray-600 dark:text-gray-400">
          {{ store.draft.street }}, {{ store.draft.zip }} {{ store.draft.city }}
        </p>
      </div>

      <!-- Manual fallback if no IČO -->
      <div v-if="!aresFound" class="pt-2">
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
          Nebo zadejte název ručně:
        </label>
        <input
          v-model="store.draft.company_name"
          type="text"
          placeholder="Název vašeho podnikání"
          class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
        />
      </div>
    </div>

    <button
      @click="handleContinue"
      :disabled="!store.draft.company_name || searching"
      class="w-full py-3.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold text-base shadow-md transition active:scale-[0.99] flex items-center justify-center gap-2"
    >
      Pokračovat k výběru oboru →
    </button>
  </div>
</template>
