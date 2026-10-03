import axios from 'axios'

export const TOKEN_KEY = 'stockwise_token'

const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: { Accept: 'application/json' },
  timeout: 15000,
})

// Attach the Sanctum bearer token to every request.
http.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY)
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// An expired or revoked token sends the user back to the login page.
http.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && !error.config.url.startsWith('/auth/login')) {
      localStorage.removeItem(TOKEN_KEY)
      if (window.location.pathname !== '/login') {
        window.location.assign('/login?expired=1')
      }
    }
    return Promise.reject(error)
  },
)

/**
 * Turn an axios error into { message, fields } for display in forms.
 */
export function parseError(error) {
  const data = error.response?.data
  if (!error.response) {
    return { message: 'Cannot reach the server. Check that the API is running.', fields: {} }
  }
  const fields = {}
  for (const [key, messages] of Object.entries(data?.errors || {})) {
    fields[key] = messages[0]
  }
  return { message: data?.message || 'Something went wrong. Please try again.', fields }
}

export default http
