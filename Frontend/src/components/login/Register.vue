<script setup>
import { ref } from 'vue'
import axios from '../../BaseURL';
import { useRouter } from 'vue-router'

const router = useRouter();
const isLoading = ref(false); // ✅ New loading state
const isError = ref(false);

const user = ref({
    firstname: "",
    email: "",
    password: "",
});

async function register() {
    isLoading.value = true; 
    try {
        const response = await axios.post('http://localhost/register', user.value);
        
        if (response.data && response.data.id) {
            router.push("/login"); 
        } else {
            isError.value = true;
        }
    } catch (error) {
        console.error("Error register:", error);
        isError.value = true;
    }
    isLoading.value = false;
}

</script>

<template>
    <div class="container mt-5">
      <div class="row justify-content-center">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header text-center">
              <h1>Register</h1>
            </div>
            <div class="card-body">
              <form @submit.prevent="register">
                <div class="mb-3">
                  <label class="form-label">Full Name:</label>
                  <input type="text" v-model="user.firstname" class="form-control" required />
                </div>
  
                <div class="mb-3">
                  <label class="form-label">Email:</label>
                  <input type="email" v-model="user.email" class="form-control" required />
                </div>
  
                <div class="mb-3">
                  <label class="form-label">Password:</label>
                  <input type="password" v-model="user.password" class="form-control" required />
                </div>
  
                <button type="submit" class="btn btn-primary w-100" :disabled="isLoading">
                  <span v-if="isLoading">⏳ Creating...</span>
                  <span v-else>Create Account</span>
                </button>&nbsp;&nbsp;
                <button type="button" class="btn btn-danger w-100" @click="$router.push('/login')" :disabled="isLoading">
                  Back to Login
                </button>
              </form>
  
              <div class="mt-3 text-center">
                <p v-if="successMessage" class="alert alert-success">{{ successMessage }}</p>
                <p v-if="isError" class="alert alert-danger">Failed to register. Please try again.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>