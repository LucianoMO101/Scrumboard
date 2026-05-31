<script setup>
import { useRouter } from 'vue-router'

const router = useRouter();

function productOverview() {
    router.push('/products')
}

function orderOverview() {
    router.push('/orders');
}

</script>
<template>
  <div class="d-flex justify-content-center align-items-center" style="height: 100vh;">
    <div class="text-center" style="margin-top: -10vh;">
      <h1 class="fw-bold mb-3">Admin Dashboard</h1>
      <button class="btn btn-primary px-md-5 py-4 fw-bold shadow-lg m-2" style="font-size: 1.5rem;" @click="productOverview()">PRODUCTS OVERVIEW</button>
      <button class="btn btn-success px-md-5 py-4 fw-bold shadow-lg m-2" style="font-size: 1.5rem;" @click="orderOverview()">ORDER OVERVIEW</button>
    </div>
  </div>
</template>



