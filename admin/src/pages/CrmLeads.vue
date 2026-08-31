<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useLeadsStore } from '@/stores/leads'
import { useAuthStore } from '@/stores/auth'
import type { LeadDTO, LeadStatus } from '@/types'

const leadsStore = useLeadsStore()
const authStore = useAuthStore()

const activeReminderLeadId = ref<string | null>(null)
const reminderDateTime = ref('')

onMounted(() => {
  leadsStore.fetchLeads()
})

const getStatusBadge = (status: LeadStatus) => {
  switch (status) {
    case 'NEW':
      return { label: 'Nová poptávka', bg: 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-300', dot: 'bg-red-500' }
    case 'CALL_BACK':
      return { label: 'Zavolat zpět', bg: 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300', dot: 'bg-amber-500' }
    case 'RESOLVED':
      return { label: 'Vyřešeno', bg: 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-300', dot: 'bg-green-500' }
  }
}

const formatDate = (iso: string) => {
  const d = new Date(iso)
  return d.toLocaleDateString('cs-CZ', {
    day: 'numeric',
    month: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const openReminderModal = (lead: LeadDTO) => {
  activeReminderLeadId.value = lead.lead_id
  reminderDateTime.value = lead.reminder_at ? lead.reminder_at.slice(0, 16) : ''
}

const saveReminder = async () => {
  if (activeReminderLeadId.value) {
    await leadsStore.setReminder(
      activeReminderLeadId.value,
      reminderDateTime.value ? new Date(reminderDateTime.value).toISOString() : null
    )
    activeReminderLeadId.value = null
  }
}
</script>

<template>
  <div class="p-4 space-y-4 max-w-[440px] mx-auto">
    <!-- Header -->
    <header class="flex justify-between items-center pt-2">
      <div>
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">
          CRM Poptávky
        </h1>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ authStore.business?.business_profile?.company_name || 'Moje Podnikání' }}
        </p>
      </div>
      <button
        @click="leadsStore.fetchLeads()"
        class="p-2 rounded-xl bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 text-sm"
        title="Obnovit"
      >
        🔄
      </button>
    </header>

    <!-- Stats Bar -->
    <div class="grid grid-cols-4 gap-2">
      <div class="p-3 rounded-2xl bg-blue-50 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900 text-center">
        <p class="text-[10px] uppercase font-bold text-blue-700 dark:text-blue-300">Dnes</p>
        <p class="text-lg font-black text-blue-900 dark:text-blue-200">{{ leadsStore.stats.today }}</p>
      </div>
      <div class="p-3 rounded-2xl bg-red-50 dark:bg-red-950/30 border border-red-100 dark:border-red-900 text-center">
        <p class="text-[10px] uppercase font-bold text-red-700 dark:text-red-300">Nové</p>
        <p class="text-lg font-black text-red-900 dark:text-red-200">{{ leadsStore.stats.new }}</p>
      </div>
      <div class="p-3 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900 text-center">
        <p class="text-[10px] uppercase font-bold text-amber-700 dark:text-amber-300">Zavolat</p>
        <p class="text-lg font-black text-amber-900 dark:text-amber-200">{{ leadsStore.stats.call_back }}</p>
      </div>
      <div class="p-3 rounded-2xl bg-green-50 dark:bg-green-950/30 border border-green-100 dark:border-green-900 text-center">
        <p class="text-[10px] uppercase font-bold text-green-700 dark:text-green-300">Hotovo</p>
        <p class="text-lg font-black text-green-900 dark:text-green-200">{{ leadsStore.stats.resolved }}</p>
      </div>
    </div>

    <!-- Status Tabs -->
    <div class="flex gap-1.5 p-1 bg-gray-100 dark:bg-zinc-800 rounded-xl text-xs font-semibold">
      <button
        @click="leadsStore.fetchLeads('ALL')"
        :class="['flex-1 py-1.5 rounded-lg transition', leadsStore.currentFilter === 'ALL' ? 'bg-white dark:bg-zinc-700 text-blue-600 dark:text-white shadow-xs font-bold' : 'text-gray-500 dark:text-gray-400']"
      >
        Vše ({{ leadsStore.stats.total }})
      </button>
      <button
        @click="leadsStore.fetchLeads('NEW')"
        :class="['flex-1 py-1.5 rounded-lg transition', leadsStore.currentFilter === 'NEW' ? 'bg-white dark:bg-zinc-700 text-red-600 dark:text-white shadow-xs font-bold' : 'text-gray-500 dark:text-gray-400']"
      >
        Nové ({{ leadsStore.stats.new }})
      </button>
      <button
        @click="leadsStore.fetchLeads('CALL_BACK')"
        :class="['flex-1 py-1.5 rounded-lg transition', leadsStore.currentFilter === 'CALL_BACK' ? 'bg-white dark:bg-zinc-700 text-amber-600 dark:text-white shadow-xs font-bold' : 'text-gray-500 dark:text-gray-400']"
      >
        Zavolat
      </button>
      <button
        @click="leadsStore.fetchLeads('RESOLVED')"
        :class="['flex-1 py-1.5 rounded-lg transition', leadsStore.currentFilter === 'RESOLVED' ? 'bg-white dark:bg-zinc-700 text-green-600 dark:text-white shadow-xs font-bold' : 'text-gray-500 dark:text-gray-400']"
      >
        Hotovo
      </button>
    </div>

    <!-- Leads List -->
    <div v-if="leadsStore.loading" class="text-center py-12 text-sm text-gray-500">
      Načítám poptávky...
    </div>

    <div v-else-if="leadsStore.leads.length === 0" class="text-center py-16 p-6 bg-gray-50 dark:bg-zinc-800/50 rounded-3xl space-y-2 border border-gray-100 dark:border-zinc-800">
      <span class="text-3xl">📭</span>
      <h3 class="font-bold text-gray-800 dark:text-white text-base">Žádné poptávky v této záložce</h3>
      <p class="text-xs text-gray-400">Jakmile zákazník vyplní formulář na vašem webu, poptávka se ihned objeví zde.</p>
    </div>

    <div v-else class="space-y-3">
      <article
        v-for="lead in leadsStore.leads"
        :key="lead.lead_id"
        class="p-4 rounded-2xl bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700/80 shadow-xs space-y-3"
      >
        <!-- Header with Sender & Status Pill -->
        <div class="flex items-start justify-between gap-2">
          <div>
            <h3 class="font-bold text-gray-900 dark:text-white text-base">
              {{ lead.sender_name }}
            </h3>
            <p class="text-xs text-gray-400">
              {{ formatDate(lead.created_at) }}
            </p>
          </div>

          <!-- Status Dropdown / Cycle -->
          <div class="relative">
            <select
              :value="lead.status"
              @change="(e) => leadsStore.updateStatus(lead.lead_id, (e.target as HTMLSelectElement).value as LeadStatus)"
              :class="[
                'text-xs font-bold px-2.5 py-1 rounded-full border-none appearance-none cursor-pointer focus:outline-none shadow-xs',
                getStatusBadge(lead.status).bg
              ]"
            >
              <option value="NEW">🔴 Nová</option>
              <option value="CALL_BACK">🟡 Zavolat zpět</option>
              <option value="RESOLVED">🟢 Vyřešeno</option>
            </select>
          </div>
        </div>

        <!-- Message Body -->
        <p class="text-sm text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-zinc-900/60 p-3 rounded-xl border border-gray-100 dark:border-zinc-800">
          {{ lead.message }}
        </p>

        <!-- Reminder Badge if set -->
        <div v-if="lead.reminder_at" class="flex items-center gap-1.5 text-xs text-blue-600 dark:text-blue-400 font-medium">
          <span>⏰ Připomenutí:</span>
          <span>{{ formatDate(lead.reminder_at) }}</span>
        </div>

        <!-- 1-Tap Action Buttons -->
        <div class="grid grid-cols-3 gap-2 pt-1">
          <!-- Volat -->
          <a
            :href="`tel:${lead.sender_phone}`"
            class="py-2.5 px-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-xs"
          >
            <span>📞</span> Volat
          </a>

          <!-- SMS -->
          <a
            :href="`sms:${lead.sender_phone}?body=Dobrý den, ${lead.sender_name}, reaguji na Vaši poptávku ohledně služeb.`"
            class="py-2.5 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-xs"
          >
            <span>💬</span> SMS
          </a>

          <!-- Připomenout -->
          <button
            @click="openReminderModal(lead)"
            class="py-2.5 px-3 rounded-xl border border-gray-300 dark:border-zinc-700 hover:bg-gray-50 dark:hover:bg-zinc-700 text-gray-700 dark:text-gray-200 font-semibold text-xs flex items-center justify-center gap-1"
          >
            <span>⏰</span> Odložit
          </button>
        </div>
      </article>
    </div>

    <!-- Reminder Modal -->
    <div
      v-if="activeReminderLeadId"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
    >
      <div class="w-full max-w-xs p-5 rounded-3xl bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 space-y-4 shadow-xl">
        <h3 class="font-bold text-base text-gray-900 dark:text-white">
          Nastavit připomenutí
        </h3>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Datum a čas</label>
          <input
            v-model="reminderDateTime"
            type="datetime-local"
            class="w-full px-3 py-2 text-sm rounded-xl border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
          />
        </div>
        <div class="flex gap-2">
          <button
            @click="activeReminderLeadId = null"
            class="w-1/2 py-2 text-xs rounded-xl border border-gray-300 dark:border-zinc-700 text-gray-700 dark:text-gray-300"
          >
            Zrušit
          </button>
          <button
            @click="saveReminder"
            class="w-1/2 py-2 text-xs rounded-xl bg-blue-600 text-white font-bold"
          >
            Uložit
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
