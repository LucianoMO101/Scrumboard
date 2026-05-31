<!-- filepath: Frontend/src/components/quiz/PlayQuizzes.vue -->
<script setup>
import { ref, onMounted } from 'vue';
import axios from '@/BaseURL';

const quizzes = ref([]);
const error = ref(null);

async function fetchQuizzes() {
  try {
    const response = await axios.get('/quiz');
    quizzes.value = response.data;
  } catch (err) {
    error.value = 'Failed to load quizzes.';
  }
}

onMounted(fetchQuizzes);
</script>

<template>
  <div>
    <h1>Available Quizzes</h1>
    <div v-if="error">{{ error }}</div>
    <ul>
      <li v-for="quiz in quizzes" :key="quiz.quiz_id">
        <router-link :to="`/quiz/${quiz.quiz_id}`">{{ quiz.title }}</router-link>
      </li>
    </ul>
  </div>
</template>