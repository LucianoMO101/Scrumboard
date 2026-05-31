<script setup>
import { useRouter } from 'vue-router'

const router = useRouter();

function foodlist() {
    router.push('/foodlist')
}

function drinklist() {
    router.push('/drinklist');
}

function goToCart() {
    router.push('/cart')
}

</script>
<template>
  <div class="d-flex justify-content-center align-items-center" style="height: 100vh;">
    <div class="text-center" style="margin-top: -10vh;">
      <h1 class="fw-bold mb-3">🐔 Welcome to the Chicken Farm 🐔</h1>

      <!-- Larger Buttons -->
      <button class="btn btn-primary px-md-5 py-4 fw-bold shadow-lg m-2" style="font-size: 1.5rem;" @click="foodlist()">🍽️ FOOD MENU</button>
      <button class="btn btn-primary px-md-5 py-4 fw-bold shadow-lg m-2" style="font-size: 1.5rem;" @click="drinklist()">🍹 DRINK MENU</button>

      <!-- Go to Cart Button Below -->
      <div class="mt-3">
        <button class="btn btn-success px-5 py-4 fw-bold shadow-lg" style="font-size: 1.5rem;" @click="goToCart()">🛒 GO TO CART</button>
      </div>
    </div>
  </div>
</template>



