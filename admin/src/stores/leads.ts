import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { LeadDTO, LeadStatus } from '@/types'
import { useApi } from '@/composables/useApi'

export interface CrmStats {
  total: number
  new: number
  call_back: number
  resolved: number
  today: number
}

export const useLeadsStore = defineStore('leads', () => {
  const leads = ref<LeadDTO[]>([])
  const stats = ref<CrmStats>({
    total: 0,
    new: 0,
    call_back: 0,
    resolved: 0,
    today: 0,
  })
  const currentFilter = ref<LeadStatus | 'ALL'>('ALL')
  const loading = ref(false)
  const api = useApi()

  const fetchLeads = async (statusFilter?: LeadStatus | 'ALL') => {
    loading.value = true
    if (statusFilter) {
      currentFilter.value = statusFilter
    }

    try {
      const query = currentFilter.value !== 'ALL' ? `?status=${currentFilter.value}` : ''
      const res = await api.get<{ leads: LeadDTO[]; stats: CrmStats }>(`/api/v1/crm/leads${query}`)
      leads.value = res.leads
      stats.value = res.stats
    } catch {
      // Ignore or handle
    } finally {
      loading.value = false
    }
  }

  const updateStatus = async (leadId: string, newStatus: LeadStatus) => {
    try {
      const updated = await api.patch<LeadDTO>(`/api/v1/crm/leads/${leadId}/status`, {
        status: newStatus,
      })

      const idx = leads.value.findIndex((l) => l.lead_id === leadId)
      if (idx !== -1) {
        leads.value[idx] = updated
      }
      // Refresh stats
      await fetchLeads()
    } catch (err: any) {
      alert(err.message || 'Nepodařilo se změnit stav poptávky')
    }
  }

  const setReminder = async (leadId: string, reminderAt: string | null) => {
    try {
      const updated = await api.patch<LeadDTO>(`/api/v1/crm/leads/${leadId}/reminder`, {
        reminder_at: reminderAt,
      })
      const idx = leads.value.findIndex((l) => l.lead_id === leadId)
      if (idx !== -1) {
        leads.value[idx] = updated
      }
    } catch (err: any) {
      alert(err.message || 'Nepodařilo se nastavit připomenutí')
    }
  }

  return {
    leads,
    stats,
    currentFilter,
    loading,
    fetchLeads,
    updateStatus,
    setReminder,
  }
})
