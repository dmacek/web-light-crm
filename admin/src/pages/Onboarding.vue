<script setup lang="ts">
import { computed } from 'vue'
import { useOnboardingStore } from '@/stores/onboarding'
import StepIco from '@/components/onboarding/StepIco.vue'
import StepArchetype from '@/components/onboarding/StepArchetype.vue'
import StepContact from '@/components/onboarding/StepContact.vue'
import StepMedia from '@/components/onboarding/StepMedia.vue'
import StepPreview from '@/components/onboarding/StepPreview.vue'

const store = useOnboardingStore()

const currentComponent = computed(() => {
  switch (store.draft.step) {
    case 1:
      return StepIco
    case 2:
      return StepArchetype
    case 3:
      return StepContact
    case 4:
      return StepMedia
    case 5:
      return StepPreview
    default:
      return StepIco
  }
})
</script>

<template>
  <div class="mobile-container p-5 pb-10 justify-between">
    <!-- Header with progress bar -->
    <header class="space-y-3 pt-2">
      <div class="flex items-center justify-between">
        <h1 class="font-extrabold text-lg text-blue-600 dark:text-blue-400 tracking-tight">
          ⚡ Web-Light-CRM
        </h1>
        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
          Krok {{ store.draft.step }} z 5
        </span>
      </div>

      <!-- 5-segment progress bar -->
      <div class="grid grid-cols-5 gap-1.5 h-1.5 w-full">
        <div
          v-for="s in 5"
          :key="s"
          :class="[
            'rounded-full transition-all duration-300',
            s <= store.draft.step
              ? 'bg-blue-600 dark:bg-blue-500'
              : 'bg-gray-200 dark:bg-zinc-700'
          ]"
        ></div>
      </div>
    </header>

    <!-- Main Step Body -->
    <main class="my-auto py-6">
      <component :is="currentComponent" />
    </main>

    <!-- Footer note -->
    <footer class="text-center text-[11px] text-gray-400 pt-4">
      Rychlý generátor webů pro živnostníky v ČR • Podpora ARES
    </footer>
  </div>
</template>
