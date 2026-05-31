<script setup>
import FoodListItem from "./FoodListItem.vue";
import DrinkListItem from "./DrinkListItem.vue";
import { ref, onMounted } from 'vue';
import axios from '../../../BaseURL';
import { useRouter } from "vue-router";

const router = useRouter();
const foods = ref([])
const drinks = ref([])

async function getFoods() {
  try {
    const response = await axios.get('http://localhost/food');
    foods.value = response.data
  }
  catch (error) {
    console.error("Error try to fetch foods: ", error);
    foods.value = [];
  }
}

async function getDrinks() {
  try {
    const response = await axios.get('http://localhost/drink');
    drinks.value = response.data
  }
  catch (error) {
    console.error("Error try to fetch drinks: ", error);
  }
}

onMounted(async () => {
    await getFoods();
    await getDrinks();
});

function removeFood(id) {
  foods.value = foods.value.filter(food => food.id !== id);
}

function removeDrink(id) {
  drinks.value = drinks.value.filter(drink => drink.id !== id);
}

function goBack() {
  router.push('/admin');
}

</script>

<template>
    <section>
      <div class="container">
        <div class="d-flex  mt-3 mt-lg-5">
          <button class="btn btn-secondary" @click="goBack">⬅️ Go Back</button>
          <h1 class="fw-bold ms-5 flex-grow-1">Menu</h1>
        </div>
          <button type="button" class="btn btn-success mt-3 fw-bold" @click="$router.push('/createproduct');">
              Add menu item
            </button>
        <div class="row my-5">
          <h2>Food</h2>
          <FoodListItem
            v-for="food in foods"
            :key="food.id"
            :food="food"
            @productDeleted="removeFood"
          />
          <h2 class="mt-5">Drink</h2>
          <DrinkListItem
            v-for="drink in drinks"
            :key="drink.id"
            :drink="drink"
            @drinkDeleted="removeDrink"
          />

        </div>
      </div>
    </section>
  </template>
  
  <style>
  </style>