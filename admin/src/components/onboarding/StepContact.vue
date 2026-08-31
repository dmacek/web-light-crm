<script setup lang="ts">
import { ref } from 'vue'
import { useOnboardingStore } from '@/stores/onboarding'

const store = useOnboardingStore()

const phone = ref(store.draft.phone || '+420 ')
const email = ref(store.draft.email || '')
const error = ref('')

const handlePhoneInput = (e: Event) => {
  const target = e.target as HTMLInputElement
  let val = target.value
  if (!val.startsWith('+420')) {
    val = '+420 ' + val.replace(/^\+420\s*/, '')
  }
  phone.value = val
  store.draft.phone = val
}

const handleContinue = () => {
  error.value = ''

  const cleanPhone = phone.value.replace(/\s+/g, '')
  if (!/^\+420\d{9}$/.test(cleanPhone)) {
    error.value = 'Zadejte platné české telefonní číslo (+420 followed by 9 digits).'
    return
  }

  if (email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    error.value = 'Zadejte platný formát e-mailu.'
    return
  }

  store.draft.phone = phone.value
  store.draft.email = email.value
  store.nextStep()
}
</script>

<template>
  <div class="space-y-6">
    <div class="text-center space-y-2">
      <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 rounded-full text-xs font-semibold uppercase tracking-wider">
        Krok 3 z 5
      </span>
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Kam vám mají zákazníci volat?</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Telefonní číslo bude výrazně zobrazeno na vašem webu pro přímé volání.
      </p>
    </div>

    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Telefonní číslo (povinné) *
        </label>
        <input
          :value="phone"
          @input="handlePhoneInput"
          type="tel"
          placeholder="+420 777 123 456"
          class="w-full px-4 py-3 text-base rounded-xl border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none transition shadow-xs"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          E-mailová adresa (pro notifikace poptávek)
        </label>
        <input
          v-model="email"
          type="email"
          placeholder="např. tomas@elektrikari.cz"
          class="w-full px-4 py-3 text-base rounded-xl border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none transition shadow-xs"
        />
      </div>

      <p v-if="error" class="text-xs text-red-500 font-medium">
        {{ error }}
      </p>
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
        Pokračovat k fotkám →
      </button>
    </div>
  </div>
</template>
