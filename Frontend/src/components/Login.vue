<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-md">
      <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Sign In</h1>

      <!-- Error Message -->
      <div v-if="authStore.error" class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
        {{ authStore.error }}
      </div>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="your@email.com"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input
            v-model="password"
            type="password"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="••••••••"
          />
        </div>

        <button
          type="submit"
          :disabled="authStore.isLoading"
          class="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 disabled:bg-gray-400 transition duration-200"
        >
          {{ authStore.isLoading ? 'Signing in...' : 'Sign In' }}
        </button>
      </form>

      <p class="text-center text-gray-600 mt-6">
        Don't have an account?
        <RouterLink to="/register" class="text-blue-600 font-semibold hover:underline">
          Register here
        </RouterLink>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/AuthStore'

const authStore = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')

const handleLogin = async () => {
  console.log('Attempting login with:', { email: email.value, password: password.value })
  console.log('Auth store:', authStore)
  const success = await authStore.login(email.value, password.value)
  console.log('Login success:', success)
  if (success) {
    router.push('/dashboard')
  }
}
</script>
