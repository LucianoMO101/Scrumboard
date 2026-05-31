import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/AuthStore'

import Login from '../components/Login.vue'
import Register from '../components/Register.vue'
import Dashboard from '../components/Dashboard.vue'
import Projects from '../components/Projects.vue'
import SprintBoard from '../components/SprintBoard.vue'
import Teams from '../components/teams/Teams.vue'
import TeamDetail from '../components/teams/TeamDetail.vue'

const routes = [
  { path: '/login', component: Login, meta: { requiresGuest: true } },
  { path: '/register', component: Register, meta: { requiresGuest: true } },
  { path: '/', redirect: '/dashboard' },
  { path: '/dashboard', component: Dashboard, meta: { requiresAuth: true } },
  { path: '/projects', component: Projects, meta: { requiresAuth: true } },
  { path: '/sprint/:id', component: SprintBoard, meta: { requiresAuth: true } },
  { path: '/teams', component: Teams, meta: { requiresAuth: true } },
  { path: '/teams/:id', component: TeamDetail, meta: { requiresAuth: true } },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: routes,
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  
  // Hydrate auth from localStorage if needed
  if (!authStore.user && !authStore.isLoggedIn) {
    authStore.hydrate()
  }

  const isLoggedIn = authStore.isLoggedIn

  if (to.meta.requiresAuth && !isLoggedIn) {
    next('/login')
  } else if (to.meta.requiresGuest && isLoggedIn) {
    next('/dashboard')
  } else {
    next()
  }
})

export default router

