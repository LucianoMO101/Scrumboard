<script setup>
import { useLoginStore } from '@/stores/LoginStore';
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const loginStore = useLoginStore();

const tableNumber = ref("");
const error = ref("");

function setTableNumber() {
    if (tableNumber.value > 0) {
        loginStore.setTableNumber(tableNumber.value);
        router.push('/menu');
    }
    else { error.value = "Put in a valid tablenumber "}
}
</script>

<template>
    <div class="container">
      <div class="d-flex vh-100 justify-content-center align-items-center">
        <div class="card text-center p-5 shadow-lg">
          <h1 class="display-4 fw-bold mb-4">👋 Welcome</h1>
          <p class="lead mb-4">Enter Your Table Number</p>
  
          <div class="mb-3">
            <input
              v-model="tableNumber"
              type="number"
              class="form-control text-center fs-3 py-3"
              placeholder="Table #"
            />
          </div>
  
          <button
            class="btn btn-success fs-4 py-3 px-5 w-100 mt-4"
            @click="setTableNumber"
          >
            Confirm
          </button>
          <div class="mt-3">
            <span v-if="error">{{ error }}</span>
          </div>
        </div>
      </div>
    </div>
</template>

  
  
  
  