<script setup>
import axios from '../../../BaseURL';
import { ref, defineProps, defineEmits } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter();

defineProps({
    drink: Object
})

const emit = defineEmits(['drinkDeleted'])

async function deleteDrink(id) {
    try {
        const responce = await axios.delete(`http://localhost/drink/${id}`)
        emit('drinkDeleted', id);
    }
    catch (error) {
        console.error("Something went wrong with the deleteProduct:: ", error);
    }
}


function editProduct(id) {
    router.push(`/editdrink/${id}`)
};

</script>

<template>
    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4 col-xxl-3 p-2">
        <div class="card product-card h-100">
            <div class="card-body">
                <img v-if="drink.image" :src="drink.image" width="100%" height="100" />
                <img v-else src="/images/default.jpg" width="100%" height="100"/>
                <div class="float-start">
                    <!-- <p>{{ product.id }}</p> -->
                    <p>{{ drink.name }}</p>
                    <p>
                        <small>{{ drink.description }}</small>
                    </p>
                </div>
                <span class="price float-end">{{ drink.price }}</span>
            </div>
            <div class="card-footer">
                <button class="btn btn-warning" @click="editProduct(drink.id)">
                    Edit</button>&nbsp;&nbsp;
                <button class="btn btn-danger" @click="deleteDrink(drink.id)">
                    Delete
                </button>
            </div>
        </div>
    </div>
</template>