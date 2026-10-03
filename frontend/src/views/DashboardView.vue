<script setup>
import { computed, onMounted, ref } from 'vue'
import http, { parseError } from '../api/http'
import AppIcon from '../components/AppIcon.vue'
import MovementBadge from '../components/MovementBadge.vue'
import { useAuthStore } from '../stores/auth'
import { formatCurrency, formatDateTime, formatNumber, signed } from '../utils/format'

const auth = useAuthStore()
const data = ref(null)
const error = ref('')

const maxBranchUnits = computed(() => Math.max(1, ...(data.value?.by_branch ?? []).map((b) => Number(b.total_units) || 0)))

onMounted(async () => {
  try {
    data.value = (await http.get('/dashboard')).data
  } catch (e) {
    error.value = parseError(e).message
  }
})
</script>

<template>
  <div class="page-header">
    <div>
      <h1>Dashboard</h1>
      <p class="muted">Welcome back, {{ auth.user?.name }}.</p>
    </div>
    <RouterLink to="/movements/new" class="btn btn-primary"><AppIcon name="plus" /> Record movement</RouterLink>
  </div>

  <p v-if="error" class="alert alert-error" role="alert">{{ error }}</p>
  <p v-else-if="!data" class="muted" aria-busy="true">Loading dashboard…</p>

  <template v-else>
    <section class="stats" aria-label="Key figures">
      <div class="card stat">
        <span class="stat-label">Products</span>
        <span class="stat-value">{{ formatNumber(data.totals.products) }}</span>
      </div>
      <div class="card stat">
        <span class="stat-label">Units on hand</span>
        <span class="stat-value">{{ formatNumber(data.totals.units_on_hand) }}</span>
      </div>
      <div class="card stat">
        <span class="stat-label">Stock value (cost)</span>
        <span class="stat-value">{{ formatCurrency(data.totals.stock_value) }}</span>
      </div>
      <RouterLink to="/stock?low_only=1" class="card stat stat-link" :class="{ 'stat-warning': data.totals.low_stock_items }">
        <span class="stat-label">Low-stock lines</span>
        <span class="stat-value">{{ formatNumber(data.totals.low_stock_items) }}</span>
      </RouterLink>
    </section>

    <div class="grid-2">
      <section class="card">
        <div class="card-header">
          <h2><AppIcon name="alert" /> Needs reordering</h2>
          <RouterLink to="/stock?low_only=1" class="small">View all</RouterLink>
        </div>
        <p v-if="!data.low_stock.length" class="muted">All branches are above their reorder levels.</p>
        <div v-else class="table-wrap">
          <table>
            <thead>
              <tr><th scope="col">Product</th><th scope="col">Branch</th><th scope="col" class="num">On hand</th><th scope="col" class="num">Reorder at</th></tr>
            </thead>
            <tbody>
              <tr v-for="row in data.low_stock" :key="row.id">
                <td><RouterLink :to="`/products/${row.product.id}`">{{ row.product.name }}</RouterLink></td>
                <td>{{ row.branch.code }}</td>
                <td class="num"><span class="badge" :class="row.quantity === 0 ? 'badge-danger' : 'badge-warning'">{{ row.quantity }}</span></td>
                <td class="num">{{ row.product.reorder_level }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="card">
        <div class="card-header"><h2><AppIcon name="store" /> Units by branch</h2></div>
        <ul class="bar-list">
          <li v-for="b in data.by_branch" :key="b.id">
            <div class="bar-label"><span>{{ b.name }}</span><strong>{{ formatNumber(b.total_units) }}</strong></div>
            <div class="bar-track" aria-hidden="true">
              <div class="bar-fill" :style="{ width: `${((Number(b.total_units) || 0) / maxBranchUnits) * 100}%` }"></div>
            </div>
          </li>
        </ul>
      </section>
    </div>

    <section class="card">
      <div class="card-header">
        <h2><AppIcon name="swap" /> Recent activity</h2>
        <RouterLink to="/movements" class="small">Full log</RouterLink>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th scope="col">When</th><th scope="col">Type</th><th scope="col">Product</th><th scope="col">Branch</th><th scope="col" class="num">Change</th><th scope="col">By</th></tr>
          </thead>
          <tbody>
            <tr v-for="m in data.recent_movements" :key="m.id">
              <td class="nowrap">{{ formatDateTime(m.created_at) }}</td>
              <td><MovementBadge :type="m.type" /></td>
              <td>{{ m.product?.name }}</td>
              <td>{{ m.branch?.code }}</td>
              <td class="num" :class="m.quantity < 0 ? 'text-danger' : 'text-success'">{{ signed(m.quantity) }}</td>
              <td>{{ m.user?.name ?? '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </template>
</template>
