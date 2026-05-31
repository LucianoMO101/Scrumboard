import axios from 'axios'

const apiUrl = import.meta.env.VITE_API_URL || (typeof window !== 'undefined' ? window.location.origin : 'http://localhost')

const instance = axios.create({
  baseURL: apiUrl,
})

// Set default authorization header if token exists
const token = localStorage.getItem('accessToken')
if (token) {
  instance.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

// Add response interceptor for 401 errors and token refresh
instance.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config

    // Check if error is 401 and we haven't retried this request yet
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true

      try {
        const refreshToken = localStorage.getItem('refreshToken')
        if (refreshToken) {
          const response = await instance.post('/auth/refresh', {
            refresh_token: refreshToken,
          })

          const { access_token, refresh_token } = response.data.data
          localStorage.setItem('accessToken', access_token)
          localStorage.setItem('refreshToken', refresh_token)

          // Update the authorization header
          instance.defaults.headers.common['Authorization'] = `Bearer ${access_token}`
          originalRequest.headers['Authorization'] = `Bearer ${access_token}`

          return instance(originalRequest)
        }
      } catch (refreshError) {
        // Refresh failed, redirect to login
        localStorage.removeItem('accessToken')
        localStorage.removeItem('refreshToken')
        localStorage.removeItem('user')
        window.location.href = '/login'
        return Promise.reject(refreshError)
      }
    }

    return Promise.reject(error)
  }
)

export default instance

