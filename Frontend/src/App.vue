<script setup>
import { useAuthStore } from '@/stores/AuthStore'
import { computed } from 'vue'
import { RouterView } from 'vue-router'
import Navigation from './components/Navigation.vue'

const authStore = useAuthStore()
const loggedIn = computed(() => authStore.isLoggedIn)
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <Navigation v-if="loggedIn" />
    <RouterView />
  </div>
</template>

<style scoped>
/* Global styles are handled by Tailwind CSS in main.css */
</style>

