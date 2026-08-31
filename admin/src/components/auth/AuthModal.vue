<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useOnboardingStore } from '@/stores/onboarding'
import { useApi } from '@/composables/useApi'
import type { BusinessDTO } from '@/types'

const emit = defineEmits<{
  close: []
}>()

const router = useRouter()
const authStore = useAuthStore()
const onboardingStore = useOnboardingStore()
const api = useApi()

const magicEmail = ref(onboardingStore.draft.email || '')
const magicPin = ref('')
const pinSent = ref(false)
const loading = ref(false)
const error = ref('')
const devPinHint = ref('')

const handleClaimAfterAuth = async () => {
  try {
    const claimRes = await api.post<{
      access_token: string
      refresh_token: string
      business: BusinessDTO
    }>('/api/v1/onboarding/claim-draft', {
      session_draft_id: onboardingStore.sessionDraftId,
      email: magicEmail.value,
    })

    authStore.setAuth(claimRes.access_token, claimRes.refresh_token, claimRes.business)
    emit('close')
    router.push('/crm')
  } catch (err: any) {
    error.value = err.message || 'Nepodařilo se aktivovat web. Zkuste to znovu.'
  }
}

const handleProviderLogin = async (provider: 'seznam' | 'google' | 'apple') => {
  loading.value = true
  error.value = ''

  try {
    const res = await api.post<{
      access_token: string
      refresh_token: string
      business_id: string
      business?: BusinessDTO
    }>(`/api/v1/auth/provider/${provider}`, {
      code: `mock_code_${provider}_${Date.now()}`,
      id_token: `mock_token_${provider}_${Date.now()}`,
      identity_token: `mock_token_${provider}_${Date.now()}`,
      email: onboardingStore.draft.email || `uzivatel@${provider}.cz`,
    })

    authStore.setAuth(res.access_token, res.refresh_token, res.business)
    await handleClaimAfterAuth()
  } catch (err: any) {
    error.value = err.message || 'Přihlášení selhalo.'
  } finally {
    loading.value = false
  }
}

const requestMagicPin = async () => {
  if (!magicEmail.value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(magicEmail.value)) {
    error.value = 'Zadejte platný e-mail.'
    return
  }

  loading.value = true
  error.value = ''

  try {
    const res = await api.post<{ status: string; dev_pin?: string }>(
      '/api/v1/auth/magic-link/request',
      { email: magicEmail.value }
    )
    pinSent.value = true
    if (res.dev_pin) {
      devPinHint.value = `Testovací PIN kód: ${res.dev_pin}`
    }
  } catch (err: any) {
    error.value = err.message || 'Nepodařilo se odeslat PIN.'
  } finally {
    loading.value = false
  }
}

const verifyMagicPin = async () => {
  if (!magicPin.value || magicPin.value.length < 6) {
    error.value = 'Zadejte 6-místný PIN kód.'
    return
  }

  loading.value = true
  error.value = ''

  try {
    const res = await api.post<{
      access_token: string
      refresh_token: string
      business?: BusinessDTO
    }>('/api/v1/auth/magic-link/verify', {
      email: magicEmail.value,
      pin: magicPin.value,
    })

    authStore.setAuth(res.access_token, res.refresh_token, res.business)
    await handleClaimAfterAuth()
  } catch (err: any) {
    error.value = err.message || 'Neplatný nebo vypršený PIN kód.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-fade-in">
    <div class="w-full max-w-sm rounded-3xl bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 p-6 shadow-2xl space-y-5">
      <div class="flex items-center justify-between">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
          Aktivovat web na 14 dní zdarma
        </h3>
        <button
          @click="emit('close')"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-lg p-1"
        >
          ✕
        </button>
      </div>

      <p class="text-xs text-gray-500 dark:text-gray-400">
        Přihlaste se jedním klikem. Bez zadávání platební karty. Web se ihned spustí.
      </p>

      <p v-if="error" class="text-xs text-red-500 font-medium p-2.5 rounded-lg bg-red-50 dark:bg-red-950/30">
        {{ error }}
      </p>

      <!-- 1-Click Social Providers -->
      <div class="space-y-2.5">
        <button
          @click="handleProviderLogin('seznam')"
          :disabled="loading"
          class="w-full py-3 px-4 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold text-sm shadow-xs transition flex items-center justify-center gap-3 active:scale-[0.99]"
        >
          <span class="font-bold">S</span> Přihlásit se přes Seznam.cz
        </button>

        <button
          @click="handleProviderLogin('google')"
          :disabled="loading"
          class="w-full py-3 px-4 rounded-xl border border-gray-300 dark:border-zinc-700 hover:bg-gray-50 dark:hover:bg-zinc-800 text-gray-800 dark:text-zinc-200 font-semibold text-sm shadow-xs transition flex items-center justify-center gap-3 active:scale-[0.99]"
        >
          <span>G</span> Pokračovat přes Google
        </button>

        <button
          @click="handleProviderLogin('apple')"
          :disabled="loading"
          class="w-full py-3 px-4 rounded-xl bg-black hover:bg-zinc-800 text-white font-semibold text-sm shadow-xs transition flex items-center justify-center gap-3 active:scale-[0.99]"
        >
          <span></span> Přihlásit se přes Apple
        </button>
      </div>

      <!-- Divider -->
      <div class="relative flex py-1 items-center">
        <div class="flex-grow border-t border-gray-200 dark:border-zinc-700"></div>
        <span class="flex-shrink mx-3 text-xs text-gray-400 uppercase tracking-wider">nebo e-mailem</span>
        <div class="flex-grow border-t border-gray-200 dark:border-zinc-700"></div>
      </div>

      <!-- Magic Link PIN Flow -->
      <div v-if="!pinSent" class="space-y-2">
        <input
          v-model="magicEmail"
          type="email"
          placeholder="vas@email.cz"
          class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
        />
        <button
          @click="requestMagicPin"
          :disabled="loading || !magicEmail"
          class="w-full py-2.5 rounded-xl bg-gray-900 dark:bg-zinc-100 hover:bg-gray-800 dark:hover:bg-white text-white dark:text-zinc-900 font-medium text-sm transition"
        >
          Poslat ověřovací PIN kód
        </button>
      </div>

      <div v-else class="space-y-2 animate-fade-in">
        <p class="text-xs text-green-600 dark:text-green-400 font-medium">
          Zadejte 6-místný PIN odeslaný na {{ magicEmail }}:
        </p>
        <p v-if="devPinHint" class="text-xs text-blue-500 font-mono">
          {{ devPinHint }}
        </p>
        <input
          v-model="magicPin"
          type="text"
          inputmode="numeric"
          maxlength="6"
          placeholder="123456"
          class="w-full px-3.5 py-2.5 text-center tracking-widest text-lg font-bold rounded-xl border border-blue-500 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
        />
        <button
          @click="verifyMagicPin"
          :disabled="loading || magicPin.length < 6"
          class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm transition"
        >
          Ověřit a spustit web
        </button>
      </div>
    </div>
  </div>
</template>
