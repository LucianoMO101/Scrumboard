<script setup>
import { useLoginStore } from '@/stores/LoginStore';
import { ref } from 'vue';
import { useRouter } from 'vue-router'

const router = useRouter();
const loginStore = useLoginStore();

const credentials = ref({
  email: "",
  password: ""
});

async function login() {
  const success = await loginStore.login(credentials.value.email, credentials.value.password);
  if (success) {
    const role = loginStore.getRole;
    if (role == "admin") {
      router.push('/admin');
    }
    else { router.push('/tablenumber'); } 
  } else {
    errorMessage.value = "Login failed. Please check your credentials.";
  }
}

function register(){
    router.push('/register');
}
</script>

<template>
  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header text-center">
            <h1>Login</h1>
          </div>
          <div class="card-body">
            <form @submit.prevent="login">
              <div class="mb-3">
                <label class="form-label">Email:</label>
                <input type="email" v-model="credentials.email" class="form-control" required />
              </div>

              <div class="mb-3">
                <label class="form-label">Password:</label>
                <input type="password" v-model="credentials.password" class="form-control" required />
              </div>

              <button type="submit" class="btn btn-primary w-100">
                <span v-if="loginStore.isLoading">⏳ Loading...</span>
                <span v-else>Login</span>
              </button>&nbsp;&nbsp;
              <button @click="register()" class="btn btn-warning w-100">
                Register
              </button>
            </form>
            <div class="mt-3">
              <span v-if="loginStore.error != null">{{ loginStore.error }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
