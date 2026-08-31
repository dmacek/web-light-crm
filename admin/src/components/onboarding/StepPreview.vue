<script setup lang="ts">
import { ref } from 'vue'
import { useOnboardingStore } from '@/stores/onboarding'
import AuthModal from '@/components/auth/AuthModal.vue'

const store = useOnboardingStore()
const showAuthModal = ref(false)

const openAuth = () => {
  showAuthModal.value = true
}
</script>

<template>
  <div class="space-y-6">
    <div class="text-center space-y-2">
      <span class="inline-block px-3 py-1 bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300 rounded-full text-xs font-semibold uppercase tracking-wider">
        Krok 5 z 5 — Váš web je připraven!
      </span>
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Živý náhled vašeho webu</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Takhle uvidí váš nový web zákazníci na mobilu.
      </p>
    </div>

    <!-- Scaled mobile site card preview -->
    <div class="rounded-3xl border-4 border-gray-900 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 shadow-xl space-y-4">
      <!-- Mini Browser bar -->
      <div class="flex items-center justify-between text-xs text-gray-400 border-b border-gray-100 dark:border-zinc-700 pb-2">
        <span class="font-mono text-blue-600 dark:text-blue-400">🔒 moje-firma.tvojeaplikace.cz</span>
        <span>⚡ 100%</span>
      </div>

      <!-- Simulated Web page header & hero -->
      <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-600 to-blue-800 text-white space-y-2">
        <span class="text-xs uppercase tracking-wider bg-white/20 px-2 py-0.5 rounded-md">
          {{ store.draft.main_trade_name || 'Profesionální služby' }}
        </span>
        <h3 class="text-xl font-extrabold leading-tight">
          {{ store.draft.company_name || 'Moje Podnikání' }}
        </h3>
        <p class="text-xs text-blue-100">
          📍 {{ store.draft.street }}, {{ store.draft.city }}
        </p>

        <div class="pt-2 flex gap-2">
          <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-white text-blue-800 font-bold text-xs shadow-xs">
            📞 {{ store.draft.phone }}
          </span>
        </div>
      </div>

      <!-- Sample services list -->
      <div class="space-y-2 pt-1">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
          Nabízené služby & ceník
        </p>
        <div class="space-y-1.5">
          <div class="p-2.5 rounded-xl bg-gray-50 dark:bg-zinc-700/50 flex justify-between items-center text-xs">
            <div>
              <p class="font-semibold text-gray-900 dark:text-white">Konzultace a diagnostika</p>
              <p class="text-gray-500 dark:text-gray-400">Osobní prohlídka a kalkulace</p>
            </div>
            <span class="font-bold text-blue-600 dark:text-blue-400">od 500 Kč</span>
          </div>

          <div class="p-2.5 rounded-xl bg-gray-50 dark:bg-zinc-700/50 flex justify-between items-center text-xs">
            <div>
              <p class="font-semibold text-gray-900 dark:text-white">Kompletní realizace</p>
              <p class="text-gray-500 dark:text-gray-400">Dle individuální domluvy</p>
            </div>
            <span class="font-bold text-blue-600 dark:text-blue-400">Dohodou</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 14-day trial CTA -->
    <div class="space-y-3 pt-2">
      <button
        @click="openAuth"
        class="w-full py-4 px-4 rounded-2xl bg-green-600 hover:bg-green-700 text-white font-extrabold text-base shadow-lg shadow-green-600/30 transition transform active:scale-[0.99] flex items-center justify-center gap-2 animate-bounce-short"
      >
        🚀 Spustit web na 14 dní ZDARMA
      </button>

      <p class="text-center text-xs text-gray-400">
        Okamžitá aktivace • Bez zadávání platební karty • Zrušení kdykoliv
      </p>

      <div class="flex justify-center">
        <button
          @click="store.prevStep"
          class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 py-1"
        >
          ← Upravit zadané údaje
        </button>
      </div>
    </div>

    <!-- Registration & Auth Modal -->
    <AuthModal v-if="showAuthModal" @close="showAuthModal = false" />
  </div>
</template>
