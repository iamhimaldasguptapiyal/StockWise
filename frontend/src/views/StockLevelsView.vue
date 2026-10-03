<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import http, { parseError } from '../api/http'
import AppIcon from '../components/AppIcon.vue'
import PaginationBar from '../components/PaginationBar.vue'
import { useLookupStore } from '../stores/lookups'

const route = useRoute()
const router = useRouter()
const lookups = useLookupStore()

// Filters live in the URL so a filtered view can be bookmarked or shared.
const filters = reactive({
  branch_id: route.query.branch_id ?? '',
  search: route.query.search ?? '',
  low_only: route.query.low_only === '1',
  page: Number(route.query.page) || 1,
})
const rows = ref([])
const meta = ref({})
const loading = ref(false)
const error = ref('')

async function load() {
  loading.value = true
  error.value = ''
  const params = {
    branch_id: filters.branch_id || undefined,
    search: filters.search || undefined,
    low_only: filters.low_only ? 1 : undefined,
    page: filters.page,
  }
  router.replace({ query: { ...params, page: params.page > 1 ? params.page : undefined } })
  try {
    const { data } = await http.get('/stock-levels', { params })
    rows.value = data.data
    meta.value = data
  } catch (e) {
    error.value = parseError(e).message
  } finally {
    loading.value = false
  }
}

let timer
watch(() => filters.search, () => {
  clearTimeout(timer)
  timer = setTimeout(() => { filters.page = 1; load() }, 300)
})
watch(() => [filters.branch_id, filters.low_only], () => { filters.page = 1; load() })

function status(row) {
  if (row.quantity === 0) return { label: 'Out of stock', cls: 'badge-danger' }
  if (row.quantity <= row.product.reorder_level) return { label: 'Low', cls: 'badge-warning' }
  return { label: 'OK', cls: 'badge-success' }
}

onMounted(() => {
  lookups.load()
  load()
})
</script>

<template>
  <div class="page-header">
    <div>
      <h1>Stock levels</h1>
      <p class="muted">Live on-hand quantities for every product at every branch.</p>
    </div>
    <RouterLink to="/movements/new" class="btn btn-primary"><AppIcon name="plus" /> Record movement</RouterLink>
  </div>

  <div class="card">
    <div class="toolbar" role="search">
      <div class="search">
        <AppIcon name="search" />
        <label for="stock-search" class="sr-only">Search products</label>
        <input id="stock-search" v-model="filters.search" type="search" placeholder="Search by name or SKU" />
      </div>
      <label for="branch-filter" class="sr-only">Filter by branch</label>
      <select id="branch-filter" v-model="filters.branch_id">
        <option value="">All branches</option>
        <option v-for="b in lookups.branches" :key="b.id" :value="String(b.id)">{{ b.name }}</option>
      </select>
      <label class="checkbox">
        <input v-model="filters.low_only" type="checkbox" /> Low stock only
      </label>
    </div>

    <p v-if="error" class="alert alert-error" role="alert">{{ error }}</p>

    <div class="table-wrap" :aria-busy="loading">
      <table>
        <thead>
          <tr>
            <th scope="col">Product</th>
            <th scope="col" class="hide-sm">SKU</th>
            <th scope="col">Branch</th>
            <th scope="col" class="num">On hand</th>
            <th scope="col" class="num hide-sm">Reorder at</th>
            <th scope="col">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!loading && !rows.length">
            <td colspan="6" class="muted center">No stock lines match these filters.</td>
          </tr>
          <tr v-for="row in rows" :key="row.id">
            <td><RouterLink :to="`/products/${row.product.id}`">{{ row.product.name }}</RouterLink></td>
            <td class="hide-sm"><code>{{ row.product.sku }}</code></td>
            <td>{{ row.branch.name }}</td>
            <td class="num"><strong>{{ row.quantity }}</strong></td>
            <td class="num hide-sm">{{ row.product.reorder_level }}</td>
            <td><span class="badge" :class="status(row).cls">{{ status(row).label }}</span></td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationBar :meta="meta" @change="(p) => { filters.page = p; load() }" />
  </div>
</template>
