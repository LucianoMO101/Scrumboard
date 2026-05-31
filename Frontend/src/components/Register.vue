<template>
  <div class="min-h-screen bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-md">
      <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Create Account</h1>

      <!-- Error Message -->
      <div v-if="authStore.error" class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
        {{ authStore.error }}
      </div>

      <form @submit.prevent="handleRegister" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
          <input
            v-model="firstname"
            type="text"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
            placeholder="John"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
          <input
            v-model="lastname"
            type="text"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
            placeholder="Doe"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
            placeholder="your@email.com"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input
            v-model="password"
            type="password"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
            placeholder="••••••••"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
          <input
            v-model="passwordConfirm"
            type="password"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
            placeholder="••••••••"
          />
        </div>

        <button
          type="submit"
          :disabled="authStore.isLoading"
          class="w-full bg-green-600 text-white py-2 rounded-lg font-semibold hover:bg-green-700 disabled:bg-gray-400 transition duration-200"
        >
          {{ authStore.isLoading ? 'Creating Account...' : 'Create Account' }}
        </button>
      </form>

      <p class="text-center text-gray-600 mt-6">
        Already have an account?
        <RouterLink to="/login" class="text-green-600 font-semibold hover:underline">
          Sign in here
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

const firstname = ref('')
const lastname = ref('')
const email = ref('')
const password = ref('')
const passwordConfirm = ref('')

const handleRegister = async () => {
  if (!firstname.value.trim() || !lastname.value.trim() || !email.value.trim() || !password.value) {
    authStore.error = 'All fields are required'
    return
  }
  if (password.value !== passwordConfirm.value) {
    authStore.error = 'Passwords do not match'
    return
  }

  const success = await authStore.register(firstname.value.trim(), lastname.value.trim(), email.value.trim(), password.value)
  if (success) {
    router.push('/login')
  }
}
</script>
