import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useLeadsStore } from '../src/stores/leads'

describe('CRM Leads Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('initializes with empty leads and default stats', () => {
    const store = useLeadsStore()
    expect(store.leads).toEqual([])
    expect(store.stats.total).toBe(0)
    expect(store.currentFilter).toBe('ALL')
  })
})
