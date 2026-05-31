<script setup>
import { useRouter } from 'vue-router';
import axios from '../../../BaseURL';
import { defineProps, ref, onMounted } from 'vue';

const router = useRouter();

const props = defineProps({
  id: [Number, String]
});

const foods = ref([]);
const drinks = ref([]);

async function getOrderDetails() {
  try {
    const response = await axios.get(`http://localhost/order/${props.id}`);
    
    foods.value = response.data.foods;
    drinks.value = response.data.drinks;
  } catch (error) {
    console.error("Something went wrong with fetching order details:", error);
  }
}

onMounted(() => {
  getOrderDetails();
});

function goBack() {
  router.push('/orders');
}
</script>


<template>
  <div class="container">
    <div class="d-flex  mt-3 mt-lg-5">
          <button class="btn btn-secondary" @click="goBack">⬅️ Go Back</button>
          <h2 class="fw-bold ms-5 flex-grow-1">Order Details for Order #{{ props.id }}</h2>
        </div>
    <div class="m-4">
      <h2 class="mb-3">Food Items:</h2>
      <ul class="list-group">
        <li v-for="(food, index) in foods" :key="index" class="list-group-item d-flex justify-content-between align-items-center">
          <span>{{ food.quantity }} x {{ food.food_name }}</span>
        </li>
      </ul>
    </div>
    <div class="m-4">
      <h2 class="mb-3">Drink Items:</h2>
      <ul class="list-group">
        <li v-for="(drink, index) in drinks" :key="index" class="list-group-item d-flex justify-content-between align-items-center">
          <span>{{ drink.quantity }} x {{ drink.drink_name }}</span>
        </li>
      </ul>
    </div>
  </div>
</template>
