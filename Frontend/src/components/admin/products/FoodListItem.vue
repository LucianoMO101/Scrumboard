<script setup>
import axios from '../../../BaseURL';
import { ref, defineProps, defineEmits } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter();

defineProps({
    food: Object
})

const emit = defineEmits(['productDeleted'])

async function deleteProduct(id) {
    try {
        const responce = await axios.delete(`http://localhost/food/${id}`)
        emit('productDeleted', id);
    }
    catch (error) {
        console.error("Something went wrong with the deleteProduct:: ", error);
    }
}

function editProduct(id) {
    router.push(`/editfood/${id}`)
};

</script>

<template>
    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4 col-xxl-3 p-2">
        <div class="card product-card h-100">
            <div class="card-body">
                <img v-if="food.image" :src="food.image" width="100%" height="100" />
                <img v-else src="/images/default.jpg" width="100%" height="100"/>
                <div class="float-start">
                    <!-- <p>{{ product.id }}</p> -->
                    <p>{{ food.name }}</p>
                    <p>
                        <small>{{ food.description }}</small>
                    </p>
                </div>
                <span class="price float-end">{{ food.price }}</span>
            </div>
            <div class="card-footer">
                <button class="btn btn-warning" @click="editProduct(food.id)">
                    Edit</button>&nbsp;&nbsp;
                <button class="btn btn-danger" @click="deleteProduct(food.id)">
                    Delete
                </button>
            </div>
        </div>
    </div>
</template>

<style>

</style>