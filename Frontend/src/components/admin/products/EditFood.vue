<script setup>
import axios from '../../../BaseURL';
import { ref, defineProps, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter();
const isError = ref(false);
const isLoading = ref(false); 

const food = ref({
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

async function getFoodById() {
  try{
    const foodId = Number(props.id); 
    const response = await axios.get(`http://localhost/food/${foodId}`);
    food.value = response.data;
  }
  catch (error) {
    console.error("Somtgin whent wrong by the getFoodByID: ", error);
  }
}

onMounted(() => {
  getFoodById();
});


async function updateFood() {
  isLoading.value = true; 
  try {
    const foodId = Number(props.id); 
    const response = await axios.put(`http://localhost/food/${foodId}`, food.value);
    if (response.data) {
        router.push("/products"); 
    } else {
      isError.value = true;
    }
    isLoading.value = false; 
  }
  catch(error) {
    isError.value = true;
    isLoading.value = false; 
    console.error("Ging fout bij de update food:: ", error );
  }

}

</script>

<template>
    <section>
      <div class="container">
        <form ref="form">
          <h2 class="mt-3 mt-lg-5">Edit a food</h2>
          <h5 class="mb-4"></h5>
  
          <div class="input-group mb-3">
            <span class="input-group-text">Name</span>
            <input type="text" class="form-control" v-model="food.name" />
          </div>
  
          <div class="input-group mb-3">
            <span class="input-group-text">Price</span>
            <input type="number" class="form-control" v-model="food.price" />
          </div>
  
          <div class="input-group mb-3">
            <span class="input-group-text">Description</span>
            <textarea
              class="form-control"
              v-model="food.description"
            ></textarea>
          </div>
  
          <div class="input-group mb-3">
            <span class="input-group-text">Image URL</span>
            <input type="text" class="form-control" v-model="food.image" />
          </div>
  
          <div class="input-group mb-3">
            <span class="input-group-text">Stock</span>
            <input type="number" class="form-control" v-model="food.stock" />
          </div>
  
          <div class="input-group mt-4">
            <button type="button" class="btn btn-primary me-2" @click="updateFood()">
              <span v-if="isLoading">⏳ Editing...</span>
              <span v-else>Edit Food</span>
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
          Failed to update food
        </div>
      </div>
    </section>
  </template>
    
  <style>
  </style>