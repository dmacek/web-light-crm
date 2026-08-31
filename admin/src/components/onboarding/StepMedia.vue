<script setup lang="ts">
import { ref } from 'vue'
import { useOnboardingStore } from '@/stores/onboarding'

const store = useOnboardingStore()
const fileInput = ref<HTMLInputElement | null>(null)
const uploading = ref(false)

const handleFileUpload = (e: Event) => {
  const target = e.target as HTMLInputElement
  const files = target.files
  if (!files || files.length === 0) return

  uploading.value = true

  // Client-side preview/conversion
  Array.from(files).forEach((file) => {
    const reader = new FileReader()
    reader.onload = (event) => {
      const dataUrl = event.target?.result as string
      store.draft.photos.push({
        id: Math.random().toString(36).substring(2, 9),
        image_url: dataUrl,
        thumbnail_url: dataUrl,
        caption: file.name.replace(/\.[^/.]+$/, ''),
      })
      store.persistDraft()
    }
    reader.readAsDataURL(file)
  })

  uploading.value = false
}

const removePhoto = (index: number) => {
  store.draft.photos.splice(index, 1)
  store.persistDraft()
}

const handleContinue = () => {
  store.nextStep()
}
</script>

<template>
  <div class="space-y-6">
    <div class="text-center space-y-2">
      <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 rounded-full text-xs font-semibold uppercase tracking-wider">
        Krok 4 z 5
      </span>
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Přidejte fotky své práce</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Reálné ukázky prací zvyšují počet poptávek až o 70 %. Tento krok můžete i přeskočit.
      </p>
    </div>

    <!-- Upload area -->
    <div class="space-y-4">
      <div
        @click="fileInput?.click()"
        class="border-2 border-dashed border-gray-300 dark:border-zinc-700 hover:border-blue-500 rounded-2xl p-6 text-center cursor-pointer transition bg-gray-50/50 dark:bg-zinc-800/50 flex flex-col items-center justify-center gap-2"
      >
        <span class="text-3xl">📸</span>
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">
          Klepněte pro nahrání fotek z telefonu
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          Podporuje JPG, PNG i HEIC z fotoaparátu
        </p>
        <input
          ref="fileInput"
          type="file"
          multiple
          accept="image/*"
          @change="handleFileUpload"
          class="hidden"
        />
      </div>

      <!-- Preview grid -->
      <div v-if="store.draft.photos.length > 0" class="grid grid-cols-3 gap-2">
        <div
          v-for="(photo, index) in store.draft.photos"
          :key="photo.id"
          class="relative aspect-square rounded-xl overflow-hidden group border border-gray-200 dark:border-zinc-700"
        >
          <img :src="photo.image_url" class="w-full h-full object-cover" />
          <button
            @click="removePhoto(index)"
            class="absolute top-1 right-1 p-1 bg-red-600 text-white rounded-full text-xs w-5 h-5 flex items-center justify-center shadow-md"
          >
            ✕
          </button>
        </div>
      </div>
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
        {{ store.draft.photos.length > 0 ? 'Pokračovat k náhledu →' : 'Přeskočit a zobrazit náhled →' }}
      </button>
    </div>
  </div>
</template>
