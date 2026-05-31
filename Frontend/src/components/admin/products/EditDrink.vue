<script setup>
import axios from '../../../BaseURL';
import { ref, defineProps, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter();
const isError = ref(false);
const isLoading = ref(false); 

const drink = ref({
    id: 0,
    name: "",
    price: "",
    description: "",
    image: "",
    stock: 0,
});

const props = defineProps({
    id: [Number, String]
})

async function getDrinkById() {
  try{
    const response = await axios.get(`http://localhost/drink/${props.id}`);
    drink.value = response.data;
  }
  catch (error) {
    console.error("Somtgin whent wrong by the getFoodByID: ", error);
  }
}

onMounted(() => {
  getDrinkById();
});


async function updateDrink() {
  isLoading.value = true; 
  try {
    const response = await axios.put(`http://localhost/drink/${props.id}`, drink.value);
    if (response.data) {
        router.push("/products"); 
    } else {
        isError.value = true;
    }
    isLoading.value = false; 
  }
  catch(error) {
    isLoading.value = false; 
    isError.value = true;
    console.error("Ging fout bij de update drink:: ", error );
  }
}

</script>

<template>
    <section>
      <div class="container">
        <form ref="form">
          <h2 class="mt-3 mt-lg-5">Edit a drink</h2>
          <h5 class="mb-4"></h5>
  
          <div class="input-group mb-3">
            <span class="input-group-text">Name</span>
            <input type="text" class="form-control" v-model="drink.name" />
          </div>
  
          <div class="input-group mb-3">
            <span class="input-group-text">Price</span>
            <input type="number" class="form-control" v-model="drink.price" />
          </div>
  
          <div class="input-group mb-3">
            <span class="input-group-text">Description</span>
            <textarea
              class="form-control"
              v-model="drink.description"
            ></textarea>
          </div>
  
          <div class="input-group mb-3">
            <span class="input-group-text">Image URL</span>
            <input type="text" class="form-control" v-model="drink.image" />
          </div>
  
          <div class="input-group mb-3">
            <span class="input-group-text">Stock</span>
            <input type="number" class="form-control" v-model="drink.stock" />
          </div>
  
          <div class="input-group mt-4">
            <button type="button" class="btn btn-primary me-2" @click="updateDrink()">
              <span v-if="isLoading">⏳ Editing...</span>
              <span v-else>Edit Drink</span>
            </button>
            <button
              type="button"
              class="btn btn-danger"
              @click="$router.push('/products')"
            >
              Cancel
            </button>
          </div>
        </form>
        <div v-if="isError" class="mt-3 alert alert-danger text-center">
          Failed to update drink
        </div>
      </div>
    </section>
  </template>
    
  <style>
  </style>