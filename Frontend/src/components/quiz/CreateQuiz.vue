<!-- filepath: Frontend/src/components/quiz/CreateQuiz.vue -->
<script setup>
import { ref } from 'vue';
import axios from '@/BaseURL';
import { useRouter } from 'vue-router';

const router = useRouter();
const quiz = ref({
  title: '',
  description: '',
});
const error = ref(null);

async function createQuiz() {
  try {
    await axios.post('/quiz', quiz.value);
    router.push('/quizzes');
  } catch (err) {
    error.value = 'Failed to create quiz.';
  }
}
</script>

<template>
  <div>
    <h1>Create a New Quiz</h1>
    <form @submit.prevent="createQuiz">
      <div>
        <label>Title:</label>
        <input v-model="quiz.title" required />
      </div>
      <div>
        <label>Description:</label>
        <textarea v-model="quiz.description"></textarea>
      </div>
      <button type="submit">Create Quiz</button>
    </form>
    <div v-if="error">{{ error }}</div>
  </div>
</template>