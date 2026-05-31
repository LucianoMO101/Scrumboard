<script setup>
import { ref, defineProps } from 'vue'
import axios from '../../BaseURL';
import { useCartStore } from '@/stores/CartStore';

const cart = useCartStore();

defineProps({
    product: Object
})

function removeFromCart(id, type) {
    cart.removeFromCart(id, type)
}

function addQuanity(id, type) {
    cart.addQuantity(id, type)
}

function removeQuantity(id, type) {
    cart.removeQuantity(id, type)
}

</script>
<template>
    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4 col-xxl-3 p-2">
      <div class="card product-card h-100">
        <div class="card-body">
          <img v-if="product.image" :src="product.image" width="100%" height="100" />
          <div class="float-start">
            <!-- <p>{{ product.id }}</p> -->
            <h1>{{ product.name }}</h1>
          </div>
          <span class="price float-end ms-1">{{ product.price }}</span>
          <span class="price float-end">{{ product.quantity }} x </span>
        </div>
        <div class="card-footer d-flex flex-column align-items-center">
          <!-- Buttons always stack vertically -->
          <button class="btn btn-success mb-2" @click="addQuanity(product.id, product.type)"> Add Quantity </button>
          <button class="btn btn-warning mb-2" @click="removeQuantity(product.id, product.type)"> Remove Quantity </button>
          <button class="btn btn-danger mb-2" @click="removeFromCart(product.id, product.type)"> Remove from Cart </button>
        </div>
      </div>
    </div>
</template>
  
  