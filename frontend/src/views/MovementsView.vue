<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import http, { parseError } from '../api/http'
import AppIcon from '../components/AppIcon.vue'
import MovementBadge from '../components/MovementBadge.vue'
import PaginationBar from '../components/PaginationBar.vue'
import { useLookupStore } from '../stores/lookups'
import { formatDateTime, signed } from '../utils/format'

const route = useRoute()
const lookups = useLookupStore()

const filters = reactive({ branch_id: '', type: '', product_id: route.query.product_id ?? '', page: 1 })
const rows = ref([])
const meta = ref({})
const loading = ref(false)
const error = ref('')

const types = [
  { value: 'in', label: 'Stock in' },
  { value: 'out', label: 'Stock out' },
  { value: 'adjustment', label: 'Adjustment' },
  { value: 'transfer_in', label: 'Transfer in' },
  { value: 'transfer_out', label: 'Transfer out' },
]

async function load() {
  loading.value = true
  error.value = ''
  try {
    const params = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== ''))
    const { data } = await http.get('/stock-movements', { params })
    rows.value = data.data
    meta.value = data
  } catch (e) {
    error.value = parseError(e).message
  } finally {
    loading.value = false
  }
}

watch(() => [filters.branch_id, filters.type], () => { filters.page = 1; load() })

function clearProduct() {
  filters.product_id = ''
  filters.page = 1
  load()
}

onMounted(() => {
  lookups.load()
  load()
})
</script>

<template>
  <div class="page-header">
    <div>
      <h1>Stock movements</h1>
      <p class="muted">An audit trail of every stock change, newest first.</p>
    </div>
    <RouterLink to="/movements/new" class="btn btn-primary"><AppIcon name="plus" /> Record movement</RouterLink>
  </div>

  <div class="card">
    <div class="toolbar">
      <label for="mv-branch" class="sr-only">Filter by branch</label>
      <select id="mv-branch" v-model="filters.branch_id">
        <option value="">All branches</option>
        <option v-for="b in lookups.branches" :key="b.id" :value="b.id">{{ b.name }}</option>
      </select>
      <label for="mv-type" class="sr-only">Filter by movement type</label>
      <select id="mv-type" v-model="filters.type">
        <option value="">All types</option>
        <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
      </select>
      <button v-if="filters.product_id" type="button" class="btn btn-ghost btn-sm" @click="clearProduct">
        Showing one product · Clear
      </button>
    </div>

    <p v-if="error" class="alert alert-error" role="alert">{{ error }}</p>

    <div class="table-wrap" :aria-busy="loading">
      <table>
        <thead>
          <tr>
            <th scope="col">When</th>
            <th scope="col">Type</th>
            <th scope="col">Product</th>
            <th scope="col">Branch</th>
            <th scope="col" class="num">Change</th>
            <th scope="col" class="num">Balance</th>
            <th scope="col" class="hide-sm">Reference / note</th>
            <th scope="col" class="hide-sm">By</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!loading && !rows.length">
            <td colspan="8" class="muted center">No movements found.</td>
          </tr>
          <tr v-for="m in rows" :key="m.id">
            <td class="nowrap">{{ formatDateTime(m.created_at) }}</td>
            <td><MovementBadge :type="m.type" /></td>
            <td><RouterLink :to="`/products/${m.product.id}`">{{ m.product.name }}</RouterLink></td>
            <td>{{ m.branch.code }}</td>
            <td class="num" :class="m.quantity < 0 ? 'text-danger' : 'text-success'">{{ signed(m.quantity) }}</td>
            <td class="num">{{ m.balance_after }}</td>
            <td class="hide-sm">
              <span v-if="m.reference">{{ m.reference }}</span>
              <span v-if="m.note" class="muted small block">{{ m.note }}</span>
              <span v-if="!m.reference && !m.note">—</span>
            </td>
            <td class="hide-sm">{{ m.user?.name ?? '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationBar :meta="meta" @change="(p) => { filters.page = p; load() }" />
  </div>
</template>
