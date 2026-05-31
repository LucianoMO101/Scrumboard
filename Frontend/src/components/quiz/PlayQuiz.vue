<script setup>
import { ref, onMounted } from 'vue';
import axios from '@/BaseURL';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const quiz = ref(null);
const questions = ref([]);
const answers = ref({});
const error = ref(null);

async function fetchQuiz() {
  try {
    const quizResponse = await axios.get(`/quiz/${route.params.id}`);
    quiz.value = quizResponse.data;

    const questionsResponse = await axios.get(`/quiz/${route.params.id}/questions`);
    questions.value = questionsResponse.data;
  } catch (err) {
    error.value = 'Failed to load quiz.';
  }
}

async function submitAnswers() {
  try {
    let score = 0;

    // Calculate the score
    questions.value.forEach((question) => {
      if (answers[question.question_id] === question.correct_answer) {
        score++;
      }
    });

    // Send the result to the backend
    await axios.post('/quiz-results', {
      quiz_id: quiz.value.quiz_id,
      score,
    });

    alert(`Quiz submitted! Your score: ${score}/${questions.value.length}`);
    router.push('/quizzes');
  } catch (err) {
    error.value = 'Failed to submit quiz.';
  }
}

onMounted(fetchQuiz);
</script>

<template>
  <div>
    <h1>{{ quiz?.title }}</h1>
    <p>{{ quiz?.description }}</p>
    <div v-if="error">{{ error }}</div>
    <form @submit.prevent="submitAnswers">
      <div v-for="question in questions" :key="question.question_id">
        <p>{{ question.question_text }}</p>
        <input v-model="answers[question.question_id]" />
      </div>
      <button type="submit">Submit</button>
    </form>
  </div>
</template>