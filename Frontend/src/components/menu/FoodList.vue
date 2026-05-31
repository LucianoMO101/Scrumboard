<script setup>
import FoodListItem from "./FoodListItem.vue";
import { ref, onMounted } from 'vue';
import axios from '../../BaseURL';
import { useRouter } from 'vue-router'

const router = useRouter();

const products = ref([])

async function getFoods() {
  try {
    const response = await axios.get('http://localhost/food');
    products.value = response.data
  }
  catch (error) {
    console.error("Error try to fetch foods: ", error);
  }
}

onMounted(getFoods);

function goBack() {
  router.push('/menu');
}

</script>

<template>
    <section>
      <div class="container">
        <div class="d-flex  mt-3 mt-lg-5">
          <button class="btn btn-secondary" @click="goBack">⬅️ Go Back</button>
          <h1 class="fw-bold ms-5 flex-grow-1">Food Menu</h1>
        </div>

        <div class="row mt-3">
          <FoodListItem
            v-for="product in products"
            :key="product.id"
            :product="product"
          />
        </div>
      </div>
    </section>
  </template>
  
  <style>
  </style>