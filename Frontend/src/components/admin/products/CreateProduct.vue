<script setup>
import { ref } from 'vue'
import axios from '../../../BaseURL';
import { useRouter } from 'vue-router'

const router = useRouter();
const isLoading = ref(false);
const isError = ref(false);

const product = ref({
    name: "",
    price: "",
    description: "",
    image: "",
    stock: 0,
});

const category = ref("food");

async function addProduct() {
    isLoading.value = true; 
    try {
        let endpoint = category.value === "food" ? "http://localhost/food" : "http://localhost/drink";

        const response = await axios.post(endpoint, product.value);
        
        if (response.data && response.data.id) {
            router.push("/products"); 
        } else {
            isError.value = true;
        }
    } catch (error) {
        isError.value = true;
        console.error("Error adding product:", error);
    } finally {
        isLoading.value = false;
    }
}

// const handleImageUpload = (event) => {
//   const file = event.target.files[0];
//   if (file) {
//     const imageUrl = `/images/food/${file.name}`; // Assuming the image will be stored in the public/food folder
//     product.value.image = imageUrl;
//     console.log(image)
//     // Save the file in the public/food folder (you can simulate it in frontend development)
//     const reader = new FileReader();
//     reader.onload = () => {
//       const imgElement = document.createElement("img");
//       imgElement.src = reader.result; // simulate file display, won't actually save to public folder
//       // You can now display the image in the UI if you want (optional)
//     };
//     reader.readAsDataURL(file);
//   }
// };

</script>

<template>
    <section>
        <div class="container">
            <form>
                <h2>Create a product</h2>
                <div class="input-group mb-3">
                    <span class="input-group-text">Category</span>
                    <select class="form-select" v-model="category">
                        <option value="food">Food</option>
                        <option value="drink">Drink</option>
                    </select>
                </div>
                <div class="input-group mb-3">
                    <span class="input-group-text">Name</span>
                    <input type="text" class="form-control" v-model="product.name" />
                </div>

                <div class="input-group mb-3">
                    <span class="input-group-text">Price</span>
                    <input type="number" class="form-control" v-model="product.price" />
                </div>

                <div class="input-group mb-3">
                    <span class="input-group-text">Description</span>
                    <textarea class="form-control" v-model="product.description"></textarea>
                </div>
                
                <!-- <div class="input-group mb-3">
                    <span class="input-group-text">Image</span>
                    <input type="file" class="form-control" @change="handleImageUpload" />
                </div> -->

                <!-- Het uploaden van een image moet geloof ik door de backend zou te veel tijd kosten dacht ik -->
                <div class="input-group mb-3">
                    <span class="input-group-text">Image URL</span>
                    <input type="text" class="form-control" v-model="product.image" />
                </div>

                <div class="input-group mb-3">
                    <span class="input-group-text">Stock</span>
                    <input type="number" class="form-control" v-model="product.stock" />
                </div>

                <div class="input-group mt-4">

                    <button type="button" class="btn btn-success me-2" @click="addProduct" :disabled="isLoading">
                        <span v-if="isLoading">⏳ Adding...</span>
                        <span v-else>Create Product</span>
                    </button>

                    <button type="button" class="btn btn-danger" @click="$router.push('/products')" :disabled="isLoading">
                        Cancel
                    </button>
                </div>
                <div v-if="isError" class="mt-3 alert alert-danger text-center">
                    Failed to add food
                </div>
            </form>
        </div>
    </section>
</template>
