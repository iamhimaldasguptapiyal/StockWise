import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import http, { TOKEN_KEY } from '../api/http'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem(TOKEN_KEY))
  const user = ref(null)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'admin')

  function setSession(data) {
    token.value = data.token
    user.value = data.user
    localStorage.setItem(TOKEN_KEY, data.token)
  }

  async function login(credentials) {
    const { data } = await http.post('/auth/login', credentials)
    setSession(data)
  }

  async function register(details) {
    const { data } = await http.post('/auth/register', details)
    setSession(data)
  }

  // Restores the user after a page refresh when only the token is stored.
  async function fetchUser() {
    if (!token.value || user.value) return
    try {
      const { data } = await http.get('/auth/me')
      user.value = data.user
    } catch {
      clear()
    }
  }

  async function logout() {
    try {
      await http.post('/auth/logout')
    } finally {
      clear()
    }
  }

  function clear() {
    token.value = null
    user.value = null
    localStorage.removeItem(TOKEN_KEY)
  }

  return { token, user, isAuthenticated, isAdmin, login, register, fetchUser, logout, clear }
})
