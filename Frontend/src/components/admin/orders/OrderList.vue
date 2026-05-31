<script setup>
import OrderListItem from "./OrderListItem.vue";
import { ref, onMounted } from 'vue';
import axios from '../../../BaseURL';
import { useRouter } from "vue-router";

const router = useRouter();
const orders = ref([])

async function getOrders() {
  try {
    const response = await axios.get('http://localhost/order');
    orders.value = response.data
  }
  catch (error) {
    console.error("Error try to fetch orders: ", error);
  }
}


onMounted(() => {
    getOrders();
});

function removeOrder(id) {
  orders.value = orders.value.filter(order => order.id !== id);
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
          <h1 class="fw-bold ms-5 flex-grow-1">Orders</h1>
        </div>
        <div class="row mt-3">
          <OrderListItem
            v-for="order in orders"
            :key="order.id"
            :order="order"
            @orderDeleted="removeOrder"
          />
        </div>
      </div>
    </section>
</template>