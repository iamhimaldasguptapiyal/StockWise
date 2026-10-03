import { defineStore } from 'pinia'
import { ref } from 'vue'
import http from '../api/http'

/**
 * Cached reference data (branches, categories, suppliers) shared by
 * filters and form dropdowns across pages.
 */
export const useLookupStore = defineStore('lookups', () => {
  const branches = ref([])
  const categories = ref([])
  const suppliers = ref([])
  const loaded = ref(false)

  async function load(force = false) {
    if (loaded.value && !force) return
    const [b, c, s] = await Promise.all([
      http.get('/branches'),
      http.get('/categories'),
      http.get('/suppliers'),
    ])
    branches.value = b.data
    categories.value = c.data
    suppliers.value = s.data
    loaded.value = true
  }

  // Called after an admin edits reference data so dropdowns stay current.
  function invalidate() {
    loaded.value = false
  }

  return { branches, categories, suppliers, load, invalidate }
})
