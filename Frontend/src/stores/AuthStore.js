import { defineStore } from 'pinia'
import axios from '@/BaseURL'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    accessToken: localStorage.getItem('accessToken') || null,
    refreshToken: localStorage.getItem('refreshToken') || null,
    isLoading: false,
    error: null,
  }),

  getters: {
    isLoggedIn: (state) => !!state.accessToken,
    getCurrentUser: (state) => state.user,
  },

  actions: {
    async register(firstname, lastname, email, password) {
      this.isLoading = true
      this.error = null
      try {
        console.log('Registering user with:', { firstname, lastname, email })
        const response = await axios.post('/auth/register', {
          firstname,
          lastname,
          email,
          password,
        })

        console.log('Registration response:', response.data)
        this.user = response.data.data
        return true
      } catch (error) {
        console.error('Registration error full:', error)
        console.error('Response data:', error.response?.data)
        console.error('Response status:', error.response?.status)
        this.error = error.response?.data?.error || error.message || 'Registration failed'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async login(email, password) {
      this.isLoading = true
      this.error = null
      try {
        console.log('AuthStore.login called with:', { email })
        const response = await axios.post('/auth/login', {
          email,
          password,
        })

        console.log('Login successful, response:', response.data)
        const { data } = response.data
        this.user = {
          user_id: data.user_id,
          firstname: data.firstname,
          lastname: data.lastname,
          email: data.email,
          default_team_id: data.default_team_id,
        }
        this.accessToken = data.access_token
        this.refreshToken = data.refresh_token

        // Store in localStorage
        localStorage.setItem('accessToken', data.access_token)
        localStorage.setItem('refreshToken', data.refresh_token)
        localStorage.setItem('user', JSON.stringify(this.user))

        // Set default authorization header
        axios.defaults.headers.common['Authorization'] = `Bearer ${data.access_token}`

        return true
      } catch (error) {
        console.error('Login error full:', error)
        console.error('Error response:', error.response)
        console.error('Error message:', error.message)
        this.error = error.response?.data?.error || error.message || 'Login failed'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async refresh() {
      if (!this.refreshToken) {
        this.logout()
        return false
      }

      try {
        const response = await axios.post('/auth/refresh', {
          refresh_token: this.refreshToken,
        })

        const { data } = response.data
        this.accessToken = data.access_token
        this.refreshToken = data.refresh_token

        localStorage.setItem('accessToken', data.access_token)
        localStorage.setItem('refreshToken', data.refresh_token)
        axios.defaults.headers.common['Authorization'] = `Bearer ${data.access_token}`

        return true
      } catch (error) {
        console.error('Token refresh failed:', error)
        this.logout()
        return false
      }
    },

    logout() {
      this.user = null
      this.accessToken = null
      this.refreshToken = null
      localStorage.removeItem('accessToken')
      localStorage.removeItem('refreshToken')
      localStorage.removeItem('user')
      delete axios.defaults.headers.common['Authorization']
    },

    hydrate() {
      const token = localStorage.getItem('accessToken')
      const user = localStorage.getItem('user')
      if (token && user) {
        this.accessToken = token
        this.user = JSON.parse(user)
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
      }
    },
  },
})
