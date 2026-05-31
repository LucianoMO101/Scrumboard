<script setup>
import { useLoginStore } from '@/stores/LoginStore';
import { useRouter } from 'vue-router';

const router = useRouter();
const login = useLoginStore();

function goToMenu() {
    router.push('/menu')
}

function logout() {
    login.logout();
    router.push('/login');
}

</script>
<template>
  <div class="d-flex justify-content-center align-items-center" style="height: 100vh;">
    <div class="text-center" style="margin-top: -10vh;">
      <h1 class="fw-bold mb-3">Thank you for you order</h1>

      <!-- Larger Buttons -->
      <button class="btn btn-success px-md-5 py-4 fw-bold shadow-lg m-2" style="font-size: 1.5rem;" @click="goToMenu()">🍽️🍹 GO TO MENU</button>
      <button class="btn btn-danger px-md-5 py-4 fw-bold shadow-lg m-2" style="font-size: 1.5rem;" @click="logout()">🚪 LOGOUT</button>
    </div>
  </div>
</template>