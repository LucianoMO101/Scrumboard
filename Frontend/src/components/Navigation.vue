<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/AuthStore'

const authStore = useAuthStore()
const router = useRouter()

const logout = () => {
  authStore.logout()
  router.push('/login')
}
</script>

<template>
  <nav class="bg-gradient-to-r from-blue-600 to-blue-800 text-white shadow-lg sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex justify-between items-center h-16">
        <!-- Logo -->
        <RouterLink 
          to="/dashboard" 
          class="font-bold text-2xl hover:text-gray-100 transition no-underline"
        >
          <span class="text-white">Project</span><span class="text-red-500">Hub</span>
          📋
        </RouterLink>

        <!-- Nav Links -->
        <div class="hidden md:flex items-center space-x-8">
          <RouterLink 
            to="/dashboard" 
            class="font-medium text-white no-underline hover:text-blue-200 transition duration-200"
          >
            Dashboard
          </RouterLink>
          <RouterLink 
            to="/projects" 
            class="font-medium text-white no-underline hover:text-blue-200 transition duration-200"
          >
            Projects
          </RouterLink>
          <RouterLink 
            to="/teams" 
            class="font-medium text-white no-underline hover:text-blue-200 transition duration-200"
          >
            Teams
          </RouterLink>

          <!-- User Menu -->
          <div class="flex items-center space-x-4 pl-6 border-l border-blue-400">
            <span class="text-sm font-medium">{{ authStore.user?.firstname }}</span>
            <button
              @click="logout"
              class="bg-red-600 px-4 py-2 rounded-lg font-medium hover:bg-red-700 transition duration-200"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<style scoped>
/* Remove default link underlines */
:deep(a) {
  text-decoration: none;
}

.no-underline {
  text-decoration: none !important;
}
</style>

