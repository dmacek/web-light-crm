<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useApi } from '@/composables/useApi'
import type { SubscriptionDTO } from '@/types'

const authStore = useAuthStore()
const router = useRouter()
const api = useApi()

const subscription = ref<SubscriptionDTO | null>(null)
const selectedPlan = ref<'MONTHLY' | 'ANNUAL'>('ANNUAL')
const customDomainInput = ref('')
const subscribing = ref(false)
const successMsg = ref('')

onMounted(async () => {
  try {
    const res = await api.get<SubscriptionDTO>('/api/v1/billing/subscription')
    subscription.value = res
  } catch {}
})

const handleSubscribe = async () => {
  subscribing.value = true
  successMsg.value = ''

  try {
    const res = await api.post<{ status: string; subscription: SubscriptionDTO }>(
      '/api/v1/billing/subscribe',
      {
        plan: selectedPlan.value,
        custom_domain: selectedPlan.value === 'ANNUAL' ? customDomainInput.value : undefined,
      }
    )
    subscription.value = res.subscription
    successMsg.value = '✓ Předplatné bylo úspěšně aktivováno!'
  } catch (err: any) {
    alert(err.message || 'Nepodařilo se aktivovat předplatné')
  } finally {
    subscribing.value = false
  }
}

const logout = () => {
  authStore.logout()
  router.push('/onboarding')
}
</script>

<template>
  <div class="p-4 space-y-4 max-w-[440px] mx-auto pb-24">
    <!-- Header -->
    <header class="pt-2">
      <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">
        Nastavení & Předplatné
      </h1>
      <p class="text-xs text-gray-500">
        Správa účtu, domény a tarifu
      </p>
    </header>

    <!-- Profile card -->
    <div class="p-4 rounded-2xl bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 space-y-2 shadow-xs">
      <h3 class="text-xs font-bold uppercase text-gray-400">Podnikatelský profil</h3>
      <p class="text-sm font-bold text-gray-900 dark:text-white">
        {{ authStore.business?.business_profile?.company_name || 'Moje Provozovna' }}
      </p>
      <p class="text-xs text-gray-500">
        IČO: {{ authStore.business?.business_profile?.ico }} • E-mail: {{ authStore.business?.email }}
      </p>
      <p class="text-xs text-blue-600 dark:text-blue-400 font-mono">
        🌐 {{ authStore.business?.business_profile?.subdomain }}.tvojeaplikace.cz
      </p>
    </div>

    <!-- Subscription Status Card -->
    <div class="p-4 rounded-2xl bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 space-y-3 shadow-xs">
      <div class="flex items-center justify-between">
        <h3 class="text-xs font-bold uppercase text-gray-400">Stav tarifu</h3>
        <span
          :class="[
            'text-xs font-bold px-2.5 py-0.5 rounded-full',
            subscription?.status === 'ACTIVE'
              ? 'bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-300'
              : 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300'
          ]"
        >
          {{ subscription?.status === 'ACTIVE' ? 'Aktivní předplatné' : '14denní zkušební verze (TRIAL)' }}
        </span>
      </div>

      <p v-if="successMsg" class="text-xs text-green-600 font-medium text-center bg-green-50 p-2 rounded-lg">
        {{ successMsg }}
      </p>

      <!-- Plans options -->
      <div class="grid grid-cols-2 gap-2 pt-1">
        <!-- Monthly -->
        <button
          type="button"
          @click="selectedPlan = 'MONTHLY'"
          :class="[
            'p-3 rounded-xl border-2 text-left transition',
            selectedPlan === 'MONTHLY'
              ? 'border-blue-600 bg-blue-50/50 dark:bg-blue-950/30'
              : 'border-gray-200 dark:border-zinc-700'
          ]"
        >
          <p class="text-xs font-bold">Měsíční tarif</p>
          <p class="text-base font-extrabold text-blue-600 mt-1">250 Kč <span class="text-[10px] text-gray-400 font-normal">/ měs</span></p>
          <p class="text-[10px] text-gray-400 mt-1">Hosting, SSL, CRM a správa z mobilu</p>
        </button>

        <!-- Annual (Best Value) -->
        <button
          type="button"
          @click="selectedPlan = 'ANNUAL'"
          :class="[
            'p-3 rounded-xl border-2 text-left transition relative overflow-hidden',
            selectedPlan === 'ANNUAL'
              ? 'border-blue-600 bg-blue-50/50 dark:bg-blue-950/30'
              : 'border-gray-200 dark:border-zinc-700'
          ]"
        >
          <span class="absolute top-0 right-0 bg-green-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-bl-lg">
            DOMÉNA V CENĚ
          </span>
          <p class="text-xs font-bold">Roční tarif</p>
          <p class="text-base font-extrabold text-blue-600 mt-1">2 500 Kč <span class="text-[10px] text-gray-400 font-normal">/ rok</span></p>
          <p class="text-[10px] text-gray-400 mt-1">Sleva 500 Kč + <strong>.cz doména zdarma</strong></p>
        </button>
      </div>

      <!-- Domain input for Annual plan -->
      <div v-if="selectedPlan === 'ANNUAL'" class="space-y-1.5 pt-2">
        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
          Vyberte si vlastní .cz doménu zdarma:
        </label>
        <input
          v-model="customDomainInput"
          type="text"
          placeholder="např. tomas-elektro.cz"
          class="w-full px-3 py-2 text-xs rounded-xl border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-gray-900 dark:text-white"
        />
      </div>

      <button
        @click="handleSubscribe"
        :disabled="subscribing"
        class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-md transition"
      >
        {{ subscribing ? 'Zpracovávám...' : 'Aktivovat tarif' }}
      </button>
    </div>

    <!-- Logout button -->
    <button
      @click="logout"
      class="w-full py-3 px-4 rounded-2xl border border-red-200 dark:border-red-900/50 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/20 text-xs font-bold transition"
    >
      Odhlásit se z účtu
    </button>
  </div>
</template>
