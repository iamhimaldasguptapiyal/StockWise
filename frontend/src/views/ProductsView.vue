<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import http, { parseError } from '../api/http'
import AppIcon from '../components/AppIcon.vue'
import PaginationBar from '../components/PaginationBar.vue'
import { useAuthStore } from '../stores/auth'
import { useLookupStore } from '../stores/lookups'
import { useToastStore } from '../stores/toast'
import { formatCurrency, formatNumber } from '../utils/format'

const auth = useAuthStore()
const lookups = useLookupStore()
const toast = useToastStore()

const filters = reactive({ search: '', category_id: '', sort: 'name', direction: 'asc', page: 1 })
const products = ref([])
const meta = ref({})
const loading = ref(false)
const error = ref('')

async function load() {
  loading.value = true
  error.value = ''
  try {
    const params = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== ''))
    const { data } = await http.get('/products', { params })
    products.value = data.data
    meta.value = data
  } catch (e) {
    error.value = parseError(e).message
  } finally {
    loading.value = false
  }
}

// Debounce typing so we don't send a request on every keystroke.
let timer
watch(() => filters.search, () => {
  clearTimeout(timer)
  timer = setTimeout(() => { filters.page = 1; load() }, 300)
})
watch(() => [filters.category_id, filters.sort, filters.direction], () => { filters.page = 1; load() })

function sortBy(column) {
  if (filters.sort === column) filters.direction = filters.direction === 'asc' ? 'desc' : 'asc'
  else Object.assign(filters, { sort: column, direction: 'asc' })
}
function ariaSort(column) {
  if (filters.sort !== column) return 'none'
  return filters.direction === 'asc' ? 'ascending' : 'descending'
}

function goTo(page) {
  filters.page = page
  load()
}

async function remove(product) {
  if (!confirm(`Delete "${product.name}"? Its stock levels and movement history will also be removed.`)) return
  try {
    await http.delete(`/products/${product.id}`)
    toast.success('Product deleted.')
    load()
  } catch (e) {
    toast.error(parseError(e).message)
  }
}

onMounted(() => {
  lookups.load()
  load()
})
</script>

<template>
  <div class="page-header">
    <div>
      <h1>Products</h1>
      <p class="muted">The product catalogue shared by all branches.</p>
    </div>
    <RouterLink v-if="auth.isAdmin" to="/products/new" class="btn btn-primary"><AppIcon name="plus" /> New product</RouterLink>
  </div>

  <div class="card">
    <div class="toolbar" role="search">
      <div class="search">
        <AppIcon name="search" />
        <label for="product-search" class="sr-only">Search products</label>
        <input id="product-search" v-model="filters.search" type="search" placeholder="Search by name or SKU" />
      </div>
      <label for="category-filter" class="sr-only">Filter by category</label>
      <select id="category-filter" v-model="filters.category_id">
        <option value="">All categories</option>
        <option v-for="c in lookups.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
    </div>

    <p v-if="error" class="alert alert-error" role="alert">{{ error }}</p>

    <div class="table-wrap" :aria-busy="loading">
      <table>
        <caption class="sr-only">Products, sortable by column</caption>
        <thead>
          <tr>
            <th scope="col" :aria-sort="ariaSort('sku')"><button class="th-sort" type="button" @click="sortBy('sku')">SKU</button></th>
            <th scope="col" :aria-sort="ariaSort('name')"><button class="th-sort" type="button" @click="sortBy('name')">Name</button></th>
            <th scope="col" class="hide-sm">Category</th>
            <th scope="col" class="num" :aria-sort="ariaSort('sell_price')"><button class="th-sort" type="button" @click="sortBy('sell_price')">Price</button></th>
            <th scope="col" class="num" :aria-sort="ariaSort('total_stock')"><button class="th-sort" type="button" @click="sortBy('total_stock')">Total stock</button></th>
            <th scope="col" v-if="auth.isAdmin"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!loading && !products.length">
            <td :colspan="auth.isAdmin ? 6 : 5" class="muted center">No products match your search.</td>
          </tr>
          <tr v-for="p in products" :key="p.id">
            <td><code>{{ p.sku }}</code></td>
            <td><RouterLink :to="`/products/${p.id}`">{{ p.name }}</RouterLink></td>
            <td class="hide-sm">{{ p.category?.name ?? '—' }}</td>
            <td class="num">{{ formatCurrency(p.sell_price) }}</td>
            <td class="num">{{ formatNumber(p.total_stock) }}</td>
            <td v-if="auth.isAdmin" class="actions">
              <RouterLink :to="`/products/${p.id}/edit`" class="btn btn-ghost btn-sm" :aria-label="`Edit ${p.name}`"><AppIcon name="edit" /></RouterLink>
              <button class="btn btn-ghost btn-sm text-danger" type="button" :aria-label="`Delete ${p.name}`" @click="remove(p)"><AppIcon name="trash" /></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationBar :meta="meta" @change="goTo" />
  </div>
</template>
