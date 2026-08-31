<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AuthModal from '@/components/auth/AuthModal.vue'

const router = useRouter()
const showModal = ref(true)

const closeModal = () => {
  showModal.value = false
  router.push('/onboarding')
}
</script>

<template>
  <div class="p-6 flex flex-col items-center justify-center min-h-[60vh]">
    <AuthModal v-if="showModal" @close="closeModal" />
  </div>
</template>
