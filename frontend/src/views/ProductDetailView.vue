<script setup>
import { onMounted, ref, watch } from 'vue'
import http, { parseError } from '../api/http'
import AppIcon from '../components/AppIcon.vue'
import MovementBadge from '../components/MovementBadge.vue'
import { useAuthStore } from '../stores/auth'
import { formatCurrency, formatDateTime, formatNumber, signed } from '../utils/format'

const props = defineProps({ id: { type: String, required: true } })
const auth = useAuthStore()

const product = ref(null)
const error = ref('')

async function load() {
  error.value = ''
  try {
    product.value = (await http.get(`/products/${props.id}`)).data
  } catch (e) {
    error.value = parseError(e).message
  }
}

onMounted(load)
watch(() => props.id, load)
</script>

<template>
  <RouterLink to="/products" class="back-link"><AppIcon name="arrowLeft" /> All products</RouterLink>

  <p v-if="error" class="alert alert-error" role="alert">{{ error }}</p>
  <p v-else-if="!product" class="muted" aria-busy="true">Loading product…</p>

  <template v-else>
    <div class="page-header">
      <div>
        <h1>{{ product.name }}</h1>
        <p class="muted"><code>{{ product.sku }}</code> · {{ product.category?.name ?? 'Uncategorised' }}</p>
      </div>
      <div class="header-actions">
        <RouterLink :to="{ path: '/movements/new', query: { product_id: product.id } }" class="btn btn-secondary">Record movement</RouterLink>
        <RouterLink v-if="auth.isAdmin" :to="`/products/${product.id}/edit`" class="btn btn-primary"><AppIcon name="edit" /> Edit</RouterLink>
      </div>
    </div>

    <div class="grid-2">
      <section class="card">
        <h2>Details</h2>
        <dl class="details">
          <dt>Supplier</dt><dd>{{ product.supplier?.name ?? '—' }}</dd>
          <dt>Cost price</dt><dd>{{ formatCurrency(product.cost_price) }}</dd>
          <dt>Sell price</dt><dd>{{ formatCurrency(product.sell_price) }}</dd>
          <dt>Reorder level</dt><dd>{{ product.reorder_level }} per branch</dd>
          <dt>Total stock</dt><dd>{{ formatNumber(product.total_stock) }} units</dd>
        </dl>
        <p v-if="product.description" class="description">{{ product.description }}</p>
      </section>

      <section class="card">
        <h2>Stock by branch</h2>
        <p v-if="!product.stock_levels.length" class="muted">No stock has been recorded for this product yet.</p>
        <table v-else>
          <thead><tr><th scope="col">Branch</th><th scope="col" class="num">On hand</th><th scope="col">Status</th></tr></thead>
          <tbody>
            <tr v-for="level in product.stock_levels" :key="level.id">
              <td>{{ level.branch.name }}</td>
              <td class="num">{{ level.quantity }}</td>
              <td>
                <span v-if="level.quantity === 0" class="badge badge-danger">Out of stock</span>
                <span v-else-if="level.quantity <= product.reorder_level" class="badge badge-warning">Low</span>
                <span v-else class="badge badge-success">OK</span>
              </td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>

    <section class="card">
      <div class="card-header">
        <h2>Recent movements</h2>
        <RouterLink :to="{ path: '/movements', query: { product_id: product.id } }" class="small">See all</RouterLink>
      </div>
      <p v-if="!product.movements.length" class="muted">No movements recorded.</p>
      <div v-else class="table-wrap">
        <table>
          <thead>
            <tr><th scope="col">When</th><th scope="col">Type</th><th scope="col">Branch</th><th scope="col" class="num">Change</th><th scope="col" class="num">Balance</th><th scope="col" class="hide-sm">Reference</th><th scope="col">By</th></tr>
          </thead>
          <tbody>
            <tr v-for="m in product.movements" :key="m.id">
              <td class="nowrap">{{ formatDateTime(m.created_at) }}</td>
              <td><MovementBadge :type="m.type" /></td>
              <td>{{ m.branch?.name }}</td>
              <td class="num" :class="m.quantity < 0 ? 'text-danger' : 'text-success'">{{ signed(m.quantity) }}</td>
              <td class="num">{{ m.balance_after }}</td>
              <td class="hide-sm">{{ m.reference ?? '—' }}</td>
              <td>{{ m.user?.name ?? '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </template>
</template>
