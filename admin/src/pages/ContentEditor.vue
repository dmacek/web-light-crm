<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useApi } from '@/composables/useApi'
import type { Mood, ServiceItem, WebContentDTO } from '@/types'

const api = useApi()

const activeTab = ref<'services' | 'banner' | 'design' | 'gallery'>('services')
const loading = ref(false)
const saving = ref(false)
const savedMessage = ref('')

const config = ref<WebContentDTO>({
  business_id: '',
  version: 1,
  design: {
    mood: 'MODERN',
    color_palette: {
      primary: '#2563eb',
      secondary: '#1e40af',
      background: '#ffffff',
    },
    block_variants: {
      hero: 'FULL_IMAGE_OVERLAY',
      pricing: 'LIST_DOTS',
      gallery: 'GRID_2X2',
    },
  },
  content: {
    vacation_banner: {
      active: false,
      text: '',
    },
    services: [],
    gallery: [],
    opening_hours: '',
    contact: {
      phone: '',
      email: '',
      address_visible: true,
    },
  },
})

const moods: Array<{ id: Mood; name: string; desc: string }> = [
  { id: 'MODERN', name: 'Moderní', desc: 'Čistý a přehledný styl, ideální pro většinu služeb' },
  { id: 'TRADITIONAL', name: 'Tradiční', desc: 'Důvěryhodný poctivý styl s patkovým písmem' },
  { id: 'BOLD', name: 'Výrazný', desc: 'Silné kontrasty a dynamický moderní vzhled' },
  { id: 'ELEGANT', name: 'Elegantní', desc: 'Jemné linie, prémiový styl pro kosmetiku a salóny' },
]

const colorPalettes = [
  { name: 'Modrá Pro', primary: '#2563eb', secondary: '#1e40af', bg: '#ffffff' },
  { name: 'Smaragdová', primary: '#059669', secondary: '#065f46', bg: '#ffffff' },
  { name: 'Jantarová', primary: '#d97706', secondary: '#b45309', bg: '#ffffff' },
  { name: 'Grafitová', primary: '#334155', secondary: '#1e293b', bg: '#ffffff' },
  { name: 'Rubínová', primary: '#dc2626', secondary: '#991b1b', bg: '#ffffff' },
  { name: 'Fialová', primary: '#4f46e5', secondary: '#3730a3', bg: '#ffffff' },
  { name: 'Dřevo & Teplo', primary: '#854d0e', secondary: '#713f12', bg: '#fefce8' },
  { name: 'Zlatá Elegance', primary: '#ca8a04', secondary: '#854d0e', bg: '#fefce8' },
  { name: 'Tmavá Noční', primary: '#0f172a', secondary: '#3b82f6', bg: '#020617' },
  { name: 'Minimalistická', primary: '#18181b', secondary: '#71717a', bg: '#fafafa' },
]

onMounted(async () => {
  loading.value = true
  try {
    const res = await api.get<WebContentDTO>('/api/v1/website/config')
    config.value = res
  } catch {
  } finally {
    loading.value = false
  }
})

const addService = () => {
  if (config.value.content.services.length >= 30) {
    alert('Maximální počet služeb je 30')
    return
  }
  config.value.content.services.push({
    id: Math.random().toString(36).substring(2, 9),
    title: 'Nová služba',
    description: 'Popis nabízené služby a podrobnosti',
    price_text: 'Dle domluvy',
    order: config.value.content.services.length + 1,
  })
}

const removeService = (index: number) => {
  config.value.content.services.splice(index, 1)
}

const selectPalette = (pal: typeof colorPalettes[0]) => {
  config.value.design.color_palette = {
    primary: pal.primary,
    secondary: pal.secondary,
    background: pal.bg,
  }
}

const handleFileUpload = (e: Event) => {
  const target = e.target as HTMLInputElement
  const files = target.files
  if (!files || files.length === 0) return

  Array.from(files).forEach((file) => {
    if (config.value.content.gallery.length >= 20) {
      alert('Maximální počet fotografií je 20')
      return
    }

    const reader = new FileReader()
    reader.onload = (ev) => {
      const dataUrl = ev.target?.result as string
      config.value.content.gallery.push({
        id: Math.random().toString(36).substring(2, 9),
        image_url: dataUrl,
        thumbnail_url: dataUrl,
        caption: file.name.replace(/\.[^/.]+$/, ''),
      })
    }
    reader.readAsDataURL(file)
  })
}

const removePhoto = (index: number) => {
  config.value.content.gallery.splice(index, 1)
}

const saveConfig = async () => {
  saving.value = true
  savedMessage.value = ''

  try {
    const updated = await api.put<WebContentDTO>('/api/v1/website/config', config.value)
    config.value = updated
    savedMessage.value = '✓ Změny na webu byly úspěšně uloženy'
    setTimeout(() => {
      savedMessage.value = ''
    }, 3000)
  } catch (err: any) {
    alert(err.message || 'Chyba při ukládání')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="p-4 space-y-4 max-w-[440px] mx-auto pb-24">
    <!-- Header -->
    <header class="flex justify-between items-center pt-2">
      <div>
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">
          Úprava obsahu webu
        </h1>
        <p class="text-xs text-gray-500">
          Změny se ihned projeví na vašem veřejném webu.
        </p>
      </div>
      <button
        @click="saveConfig"
        :disabled="saving"
        class="py-2 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-bold shadow-xs transition"
      >
        {{ saving ? 'Ukládám...' : 'Uložit web' }}
      </button>
    </header>

    <!-- Success Toast -->
    <p v-if="savedMessage" class="p-2.5 text-xs text-green-700 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-900 rounded-xl font-medium text-center animate-fade-in">
      {{ savedMessage }}
    </p>

    <!-- Editor Nav Tabs -->
    <div class="flex gap-1 p-1 bg-gray-100 dark:bg-zinc-800 rounded-xl text-xs font-semibold overflow-x-auto">
      <button
        @click="activeTab = 'services'"
        :class="['flex-1 py-1.5 px-2 rounded-lg transition whitespace-nowrap', activeTab === 'services' ? 'bg-white dark:bg-zinc-700 text-blue-600 dark:text-white shadow-xs font-bold' : 'text-gray-500']"
      >
        📋 Služby ({{ config.content.services.length }})
      </button>
      <button
        @click="activeTab = 'gallery'"
        :class="['flex-1 py-1.5 px-2 rounded-lg transition whitespace-nowrap', activeTab === 'gallery' ? 'bg-white dark:bg-zinc-700 text-blue-600 dark:text-white shadow-xs font-bold' : 'text-gray-500']"
      >
        📷 Galerie ({{ config.content.gallery.length }})
      </button>
      <button
        @click="activeTab = 'banner'"
        :class="['flex-1 py-1.5 px-2 rounded-lg transition whitespace-nowrap', activeTab === 'banner' ? 'bg-white dark:bg-zinc-700 text-blue-600 dark:text-white shadow-xs font-bold' : 'text-gray-500']"
      >
        🏖️ Dovolená
      </button>
      <button
        @click="activeTab = 'design'"
        :class="['flex-1 py-1.5 px-2 rounded-lg transition whitespace-nowrap', activeTab === 'design' ? 'bg-white dark:bg-zinc-700 text-blue-600 dark:text-white shadow-xs font-bold' : 'text-gray-500']"
      >
        🎨 Design
      </button>
    </div>

    <!-- TAB 1: Services & Pricing -->
    <div v-if="activeTab === 'services'" class="space-y-3">
      <div class="flex justify-between items-center">
        <h3 class="text-sm font-bold text-gray-800 dark:text-white">
          Služby a ceník (max 30)
        </h3>
        <button
          @click="addService"
          class="text-xs font-bold text-blue-600 dark:text-blue-400 p-1"
        >
          + Přidat službu
        </button>
      </div>

      <div class="space-y-3">
        <div
          v-for="(service, idx) in config.content.services"
          :key="service.id"
          class="p-4 rounded-2xl bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 space-y-2 relative"
        >
          <div class="flex justify-between items-center">
            <span class="text-xs font-bold text-gray-400">#{{ idx + 1 }}</span>
            <button
              @click="removeService(idx)"
              class="text-xs text-red-500 hover:text-red-700 font-semibold"
            >
              Odstranit
            </button>
          </div>

          <div>
            <label class="block text-[11px] font-medium text-gray-400">Název služby</label>
            <input
              v-model="service.title"
              type="text"
              class="w-full px-3 py-1.5 text-sm rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-gray-900 dark:text-white"
            />
          </div>

          <div>
            <label class="block text-[11px] font-medium text-gray-400">Cena / sazba</label>
            <input
              v-model="service.price_text"
              type="text"
              placeholder="např. 650 Kč/hod nebo od 1 200 Kč"
              class="w-full px-3 py-1.5 text-sm rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-gray-900 dark:text-white"
            />
          </div>

          <div>
            <label class="block text-[11px] font-medium text-gray-400">Popis</label>
            <textarea
              v-model="service.description"
              rows="2"
              class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-gray-900 dark:text-white"
            ></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: Gallery -->
    <div v-if="activeTab === 'gallery'" class="space-y-3">
      <div class="flex justify-between items-center">
        <h3 class="text-sm font-bold text-gray-800 dark:text-white">
          Fotogalerie prací ({{ config.content.gallery.length }}/20)
        </h3>
      </div>

      <label class="block border-2 border-dashed border-gray-300 dark:border-zinc-700 rounded-2xl p-5 text-center cursor-pointer hover:border-blue-500 transition">
        <span class="text-2xl">📸</span>
        <p class="text-xs font-semibold mt-1">Nahrát další fotky z mobilu</p>
        <input type="file" multiple accept="image/*" @change="handleFileUpload" class="hidden" />
      </label>

      <div class="grid grid-cols-2 gap-2">
        <div
          v-for="(photo, index) in config.content.gallery"
          :key="photo.id"
          class="relative aspect-square rounded-xl overflow-hidden group border border-gray-200 dark:border-zinc-700"
        >
          <img :src="photo.image_url" class="w-full h-full object-cover" />
          <button
            @click="removePhoto(index)"
            class="absolute top-1.5 right-1.5 p-1 bg-red-600 text-white rounded-full text-xs w-6 h-6 flex items-center justify-center shadow-md"
          >
            ✕
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 3: Vacation Banner -->
    <div v-if="activeTab === 'banner'" class="p-4 rounded-2xl bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-bold text-sm text-gray-900 dark:text-white">Dovolenkový / Stavový banner</h3>
          <p class="text-xs text-gray-400">Zobrazí výrazný pruh v horní části webu.</p>
        </div>
        <input
          v-model="config.content.vacation_banner.active"
          type="checkbox"
          class="h-5 w-5 rounded text-blue-600 focus:ring-blue-500"
        />
      </div>

      <div v-if="config.content.vacation_banner.active" class="space-y-2">
        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
          Text oznámení
        </label>
        <input
          v-model="config.content.vacation_banner.text"
          type="text"
          placeholder="např. Máme dovolenou do 15. srpna. Urgentní havárie volejte na..."
          class="w-full px-3 py-2 text-sm rounded-xl border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-gray-900 dark:text-white"
        />
      </div>
    </div>

    <!-- TAB 4: Design & Chameleon Moods -->
    <div v-if="activeTab === 'design'" class="space-y-4">
      <!-- 4 Moods -->
      <div class="space-y-2">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Styl & Nálada webu (Mood)</h3>
        <div class="grid grid-cols-2 gap-2">
          <button
            v-for="m in moods"
            :key="m.id"
            type="button"
            @click="config.design.mood = m.id"
            :class="[
              'p-3 rounded-xl text-left border-2 transition',
              config.design.mood === m.id
                ? 'border-blue-600 bg-blue-50/50 dark:bg-blue-950/30 font-bold'
                : 'border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800'
            ]"
          >
            <p class="text-xs font-bold text-gray-900 dark:text-white">{{ m.name }}</p>
            <p class="text-[10px] text-gray-400 mt-0.5 leading-tight">{{ m.desc }}</p>
          </button>
        </div>
      </div>

      <!-- 10 Color Palettes -->
      <div class="space-y-2">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Barevná paleta</h3>
        <div class="grid grid-cols-2 gap-2">
          <button
            v-for="pal in colorPalettes"
            :key="pal.name"
            type="button"
            @click="selectPalette(pal)"
            :class="[
              'p-2.5 rounded-xl border flex items-center gap-2.5 transition',
              config.design.color_palette.primary === pal.primary
                ? 'border-blue-600 ring-1 ring-blue-600 bg-white dark:bg-zinc-800'
                : 'border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800'
            ]"
          >
            <div class="w-5 h-5 rounded-full flex-shrink-0" :style="{ backgroundColor: pal.primary }"></div>
            <span class="text-xs font-medium text-gray-800 dark:text-gray-200">{{ pal.name }}</span>
          </button>
        </div>
      </div>

      <!-- Block Layout Variants -->
      <div class="space-y-3 pt-2">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Rozvržení bloků</h3>

        <!-- Hero block -->
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Úvodní sekce (Hero)</label>
          <select
            v-model="config.design.block_variants.hero"
            class="w-full px-3 py-2 text-xs rounded-xl border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-gray-900 dark:text-white"
          >
            <option value="FULL_IMAGE_OVERLAY">Barevné pozadí na celou šířku</option>
            <option value="SPLIT_TEXT_IMAGE">Text vlevo + fotka vpravo</option>
            <option value="COMPACT_CARD">Kompaktní karta s akčními tlačítky</option>
          </select>
        </div>

        <!-- Pricing block -->
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Zobrazení ceníku</label>
          <select
            v-model="config.design.block_variants.pricing"
            class="w-full px-3 py-2 text-xs rounded-xl border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-gray-900 dark:text-white"
          >
            <option value="LIST_DOTS">Řádkový ceník s tečkami (Konzultace .... 500 Kč)</option>
            <option value="CARDS_GRID">Mřížka karet s cenami</option>
            <option value="COMPACT_TABLE">Kompaktní tabulka</option>
          </select>
        </div>

        <!-- Gallery block -->
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Rozvržení galerie</label>
          <select
            v-model="config.design.block_variants.gallery"
            class="w-full px-3 py-2 text-xs rounded-xl border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-gray-900 dark:text-white"
          >
            <option value="GRID_2X2">Mřížka čtvercových fotek (2x2 / 3x3)</option>
            <option value="CAROUSEL_SLIDER">Vodorovný posuvný pás (Slider)</option>
            <option value="FEATURED_HERO">Jedna velká hlavní fotka + menší vedle</option>
          </select>
        </div>
      </div>
    </div>
  </div>
</template>
