import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Archetype, AresResult } from '@/types'
import { useApi } from '@/composables/useApi'

export interface DraftState {
  step: number
  ico: string
  company_name: string
  street: string
  city: string
  zip: string
  archetype: Archetype
  main_trade_name: string
  phone: string
  email: string
  photos: Array<{ id: string; image_url: string; thumbnail_url: string; caption: string }>
  services: Array<{ id: string; title: string; description: string; price_text: string; order: number }>
}

const getStorageItem = (key: string): string | null => {
  if (typeof window !== 'undefined' && window.localStorage) {
    try {
      return window.localStorage.getItem(key)
    } catch {
      return null
    }
  }
  return null
}

const setStorageItem = (key: string, value: string): void => {
  if (typeof window !== 'undefined' && window.localStorage) {
    try {
      window.localStorage.setItem(key, value)
    } catch {}
  }
}

export const useOnboardingStore = defineStore('onboarding', () => {
  const sessionDraftId = ref<string>(
    getStorageItem('session_draft_id') || `draft_${Math.random().toString(36).substring(2, 10)}`
  )

  const draft = ref<DraftState>({
    step: 1,
    ico: '',
    company_name: '',
    street: '',
    city: '',
    zip: '',
    archetype: 'PROVOZOVNA',
    main_trade_name: '',
    phone: '',
    email: '',
    photos: [],
    services: [],
  })

  const api = useApi()

  // Save session_draft_id
  setStorageItem('session_draft_id', sessionDraftId.value)

  const setAresData = (ares: AresResult) => {
    draft.value.ico = ares.ico
    draft.value.company_name = ares.company_name
    draft.value.street = ares.street
    draft.value.city = ares.city
    draft.value.zip = ares.zip
    persistDraft()
  }

  const persistDraft = async () => {
    try {
      await api.post('/api/v1/onboarding/draft', draft.value)
    } catch {
      // Background sync, non-blocking
    }
  }

  const nextStep = () => {
    if (draft.value.step < 5) {
      draft.value.step++
      persistDraft()
    }
  }

  const prevStep = () => {
    if (draft.value.step > 1) {
      draft.value.step--
      persistDraft()
    }
  }

  return {
    sessionDraftId,
    draft,
    setAresData,
    persistDraft,
    nextStep,
    prevStep,
  }
})
