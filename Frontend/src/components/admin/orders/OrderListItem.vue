<script setup>
import axios from '../../../BaseURL';
import { ref, defineProps, defineEmits } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter();

defineProps({
    order: Object
})

const emit = defineEmits(['orderDeleted'])

async function payOrder(id) {
    try {
        const response = await axios.delete(`http://localhost/order/${id}`)
        emit('orderDeleted', id);
    }
    catch (error) {
        console.error("Something went wrong with the deleteProduct:: ", error);
    }
}

function checkOrder(id) {
    router.push(`/ordercheck/${id}`)
};

</script>

<template>
    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4 col-xxl-3 p-2">
        <div class="card product-card h-100">
            <div class="card-body">
                <h1 class="price float-start me-2"> Table {{ order.table_number }}</h1>
                <h1 class="price float-start"> Total amount: {{ order.total_amount }}</h1>
                <p class="price float-start">{{ order.created_at }}</p>
            </div>
            <div class="card-footer">
                <button class="btn btn-success" @click="checkOrder(order.id)">
                    Check Order</button>&nbsp;&nbsp;
                <button class="btn btn-danger" @click="payOrder(order.id)">
                    Order Payed
                </button>
            </div>
        </div>
    </div>
</template>
