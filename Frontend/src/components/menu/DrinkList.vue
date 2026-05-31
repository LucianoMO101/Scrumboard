<script setup>
import DrinkListItem from "./DrinkListItem.vue";
import { ref, onMounted } from 'vue';
import axios from '../../BaseURL';
import { useRouter } from 'vue-router'

const router = useRouter();


const products = ref([])

async function getDrinks() {
  try {
    const response = await axios.get('http://localhost/drink');
    products.value = response.data
  }
  catch (error) {
    console.error("Error try to fetch drinks: ", error);
  }
}

onMounted(getDrinks);

function goBack() {
  router.push('/menu');
}

</script>

<template>
    <section>
      <div class="container">
        <div class="d-flex  mt-3 mt-lg-5">
          <button class="btn btn-secondary" @click="goBack">⬅️ Go Back</button>
          <h1 class="fw-bold ms-5 flex-grow-1">Drink Menu</h1>
        </div>

        <div class="row mt-3">
          <DrinkListItem
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