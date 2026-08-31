<script setup lang="ts">
import { ref } from 'vue'
import { useOnboardingStore } from '@/stores/onboarding'
import type { Archetype } from '@/types'

const store = useOnboardingStore()

const archetypes: Array<{
  id: Archetype
  title: string
  desc: string
  icon: string
}> = [
  {
    id: 'PROVOZOVNA',
    title: 'Kamenná provozovna',
    desc: 'Kadeřnictví, kosmetika, autoservis, masáže, ordinace',
    icon: '📍',
  },
  {
    id: 'VYJEZDOVE_REMESLO',
    title: 'Výjezdové řemeslo',
    desc: 'Instalatér, elektrikář, zámečník, havárie, malíř',
    icon: '🚐',
  },
  {
    id: 'ZAKAZKOVA_VYROBA',
    title: 'Zakázková výroba',
    desc: 'Truhlář, zámečnictví, šití, dorty, umělecká tvorba',
    icon: '🔨',
  },
  {
    id: 'OSTATNI',
    title: 'Ostatní služby',
    desc: 'Konzultace, doučování, správa nemovitostí, fitness',
    icon: '✨',
  },
]

const popularTrades = [
  'Instalatérství a topenářství',
  'Elektroinstalace a revize',
  'Kadeřnictví a holičství',
  'Autoservis a pneuservis',
  'Truhlářství a nábytek',
  'Malířství a natěračství',
  'Úklidové služby',
]

const selectTrade = (trade: string) => {
  store.draft.main_trade_name = trade
}

const handleContinue = () => {
  if (!store.draft.main_trade_name) {
    store.draft.main_trade_name = 'Služby a řemesla'
  }
  store.nextStep()
}
</script>

<template>
  <div class="space-y-6">
    <div class="text-center space-y-2">
      <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 rounded-full text-xs font-semibold uppercase tracking-wider">
        Krok 2 z 5
      </span>
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Jaký typ služeb nabízíte?</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Podle typu podnikání předpřipravíme rozložení webu i ceník.
      </p>
    </div>

    <!-- 4 Archetype Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <button
        v-for="item in archetypes"
        :key="item.id"
        type="button"
        @click="store.draft.archetype = item.id"
        :class="[
          'p-4 rounded-xl text-left border-2 transition-all flex items-start gap-3.5',
          store.draft.archetype === item.id
            ? 'border-blue-600 bg-blue-50/50 dark:bg-blue-950/30 ring-1 ring-blue-600'
            : 'border-gray-200 dark:border-zinc-700 hover:border-gray-300 bg-white dark:bg-zinc-800'
        ]"
      >
        <span class="text-2xl p-2 rounded-lg bg-white dark:bg-zinc-700 shadow-xs">{{ item.icon }}</span>
        <div>
          <p class="font-bold text-gray-900 dark:text-white text-sm">{{ item.title }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ item.desc }}</p>
        </div>
      </button>
    </div>

    <!-- Trade input & chips -->
    <div class="space-y-2">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        Váš hlavní obor / řemeslo
      </label>
      <input
        v-model="store.draft.main_trade_name"
        type="text"
        placeholder="např. Instalatér a servis kotlů"
        class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none transition shadow-xs text-sm"
      />
      
      <!-- Quick suggestions -->
      <div class="flex flex-wrap gap-1.5 pt-1">
        <button
          v-for="trade in popularTrades"
          :key="trade"
          type="button"
          @click="selectTrade(trade)"
          class="px-2.5 py-1 text-xs rounded-full bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 dark:hover:bg-zinc-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-zinc-700 transition"
        >
          + {{ trade }}
        </button>
      </div>
    </div>

    <div class="flex gap-3 pt-2">
      <button
        @click="store.prevStep"
        class="w-1/3 py-3 px-4 rounded-xl border border-gray-300 dark:border-zinc-700 text-gray-700 dark:text-gray-300 font-semibold text-sm hover:bg-gray-50 dark:hover:bg-zinc-800 transition"
      >
        ← Zpět
      </button>
      <button
        @click="handleContinue"
        class="w-2/3 py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-md transition active:scale-[0.99]"
      >
        Pokračovat ke kontaktům →
      </button>
    </div>
  </div>
</template>
