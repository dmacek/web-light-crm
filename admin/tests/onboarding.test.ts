import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useOnboardingStore } from '../src/stores/onboarding'

describe('Onboarding Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('initializes with step 1 and default state', () => {
    const store = useOnboardingStore()
    expect(store.draft.step).toBe(1)
    expect(store.draft.archetype).toBe('PROVOZOVNA')
    expect(store.sessionDraftId).toBeDefined()
  })

  it('updates draft data with ARES lookup', () => {
    const store = useOnboardingStore()
    store.setAresData({
      ico: '27082440',
      company_name: 'Alza.cz a.s.',
      street: 'Jankovcova 1522/53',
      city: 'Praha',
      zip: '17000',
      formatted_address: 'Jankovcova 1522/53, 17000 Praha',
    })

    expect(store.draft.ico).toBe('27082440')
    expect(store.draft.company_name).toBe('Alza.cz a.s.')
    expect(store.draft.city).toBe('Praha')
  })

  it('progresses steps correctly', () => {
    const store = useOnboardingStore()
    expect(store.draft.step).toBe(1)
    store.nextStep()
    expect(store.draft.step).toBe(2)
    store.nextStep()
    expect(store.draft.step).toBe(3)
    store.prevStep()
    expect(store.draft.step).toBe(2)
  })
})
