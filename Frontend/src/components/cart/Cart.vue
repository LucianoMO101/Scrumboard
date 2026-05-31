<script setup>
import CartItem from './CartItem.vue';
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/CartStore';
import { computed } from 'vue';
import { useLoginStore } from '@/stores/LoginStore';

const router = useRouter();
const login = useLoginStore();
const cart = useCartStore();

const products = computed(() => cart.getCart)
const total = computed(() => cart.getTotal)

async function checkOut() {
    if (!login.getTableNumber) {
        router.push('/tablenumber')
        return;
    }
    try {
        const response = await cart.checkout();
        if (response == true) {
          router.push('/checkout');
        }
    } catch (error) {
        console.error(error);
    }
}

function goBack() {
  router.push('/menu');
}


</script>
<template>
    <section>
      <div class="container">
        <div class="d-flex  mt-3 mt-lg-5">
          <button class="btn btn-secondary" @click="goBack">⬅️ Go Back</button>
          <h1 class="fw-bold ms-5 flex-grow-1">Cart</h1>
        </div>

        <div class="row mt-3">
          <CartItem
            v-for="product in products"
            :key="product.id"
            :product="product"
          />
        </div>
        <div class="d-flex justify-content-end align-items-center mt-3 mt-lg-5">
            <h2 class="fw-bold me-3">Total = {{ total }}</h2> <!-- ✅ Pushes text closer to the button -->
            <button class="btn btn-success fw-bold" @click="checkOut">
              <span v-if="cart.isLoading">⏳ Checking out...</span>
              <span v-else>Checkout</span>
            </button>
        </div>
        <div v-if="cart.error" class="mt-3 alert alert-danger text-center">
            {{ cart.error }}
        </div>
      </div>
    </section>
</template>
<style>
</style>