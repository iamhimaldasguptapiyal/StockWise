<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import http, { parseError } from '../api/http'
import AppIcon from '../components/AppIcon.vue'
import FormField from '../components/FormField.vue'
import { useAuthStore } from '../stores/auth'
import { useLookupStore } from '../stores/lookups'
import { useToastStore } from '../stores/toast'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const lookups = useLookupStore()
const toast = useToastStore()

const mode = ref('movement') // 'movement' | 'transfer'
const products = ref([])
const productSearch = ref('')
const errors = ref({})
const message = ref('')
const saving = ref(false)
const available = ref(null)

// Staff are locked to their own branch; admins may choose any.
const branchLocked = computed(() => !auth.isAdmin && !!auth.user?.branch_id)
const defaultBranch = auth.user?.branch_id ?? ''

const form = reactive({
  product_id: route.query.product_id ? Number(route.query.product_id) : '',
  branch_id: defaultBranch,
  to_branch_id: '',
  type: 'in',
  quantity: '',
  reference: '',
  note: '',
})

const sourceBranches = computed(() => (branchLocked.value ? lookups.branches.filter((b) => b.id === auth.user.branch_id) : lookups.branches))
const destinationBranches = computed(() => lookups.branches.filter((b) => b.id !== Number(form.branch_id)))

const quantityHint = computed(() => {
  if (mode.value === 'movement' && form.type === 'adjustment') return 'Use a negative number to reduce stock (e.g. -2 for damaged items).'
  return available.value !== null ? `${available.value} unit(s) currently at this branch.` : ''
})

async function searchProducts() {
  const { data } = await http.get('/products', { params: { search: productSearch.value || undefined, per_page: 50 } })
  products.value = data.data
}

// Show current on-hand quantity so users can sanity-check before submitting.
async function refreshAvailable() {
  available.value = null
  if (!form.product_id || !form.branch_id) return
  const { data } = await http.get(`/products/${form.product_id}`)
  const level = data.stock_levels.find((l) => l.branch_id === Number(form.branch_id))
  available.value = level?.quantity ?? 0
}

let timer
watch(productSearch, () => {
  clearTimeout(timer)
  timer = setTimeout(searchProducts, 300)
})
watch(() => [form.product_id, form.branch_id], refreshAvailable)

function validate() {
  const e = {}
  const qty = Number(form.quantity)
  if (!form.product_id) e.product_id = 'Choose a product.'
  if (!form.branch_id) e[mode.value === 'transfer' ? 'from_branch_id' : 'branch_id'] = 'Choose a branch.'
  if (!Number.isInteger(qty) || qty === 0) e.quantity = 'Enter a whole number other than zero.'
  else if ((mode.value === 'transfer' || form.type !== 'adjustment') && qty < 0) e.quantity = 'Quantity must be positive.'
  else if ((mode.value === 'transfer' || form.type === 'out') && available.value !== null && qty > available.value) {
    e.quantity = `Only ${available.value} unit(s) available.`
  }
  if (mode.value === 'transfer' && !form.to_branch_id) e.to_branch_id = 'Choose the destination branch.'
  errors.value = e
  return !Object.keys(e).length
}

async function submit() {
  message.value = ''
  if (!validate()) return
  saving.value = true
  try {
    if (mode.value === 'transfer') {
      await http.post('/stock-transfers', {
        product_id: form.product_id,
        from_branch_id: form.branch_id,
        to_branch_id: form.to_branch_id,
        quantity: Number(form.quantity),
        note: form.note || null,
      })
      toast.success('Transfer completed.')
    } else {
      const { data } = await http.post('/stock-movements', {
        product_id: form.product_id,
        branch_id: form.branch_id,
        type: form.type,
        quantity: Number(form.quantity),
        reference: form.reference || null,
        note: form.note || null,
      })
      toast.success(`Saved. New balance: ${data.balance_after}.`)
    }
    router.push('/movements')
  } catch (e) {
    const parsed = parseError(e)
    // Map transfer field names back onto this form's shared fields.
    const fields = { ...parsed.fields }
    if (fields.from_branch_id) fields.branch_id = fields.from_branch_id
    errors.value = fields
    message.value = parsed.message
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await Promise.all([lookups.load(), searchProducts()])
  // Make sure a pre-selected product appears in the dropdown even if not in the first page.
  if (form.product_id && !products.value.some((p) => p.id === form.product_id)) {
    const { data } = await http.get(`/products/${form.product_id}`)
    products.value.unshift(data)
  }
  refreshAvailable()
})
</script>

<template>
  <div class="page-header">
    <div>
      <RouterLink to="/movements" class="back-link"><AppIcon name="arrowLeft" /> Movements</RouterLink>
      <h1>Record stock movement</h1>
    </div>
  </div>

  <form class="card form-card" novalidate @submit.prevent="submit">
    <fieldset class="segmented">
      <legend class="sr-only">Movement kind</legend>
      <label :class="{ active: mode === 'movement' }"><input v-model="mode" type="radio" value="movement" /> Receive / sell / adjust</label>
      <label :class="{ active: mode === 'transfer' }"><input v-model="mode" type="radio" value="transfer" /> Transfer between branches</label>
    </fieldset>

    <p v-if="message" class="alert alert-error" role="alert">{{ message }}</p>

    <div class="form-grid">
      <FormField id="product-search" label="Find product" hint="Type to filter the product list.">
        <input id="product-search" v-model="productSearch" type="search" placeholder="Name or SKU" />
      </FormField>
      <FormField id="product" label="Product" :error="errors.product_id" required v-slot="{ describedBy, invalid }">
        <select id="product" v-model="form.product_id" :aria-describedby="describedBy" :aria-invalid="invalid">
          <option value="">Select a product…</option>
          <option v-for="p in products" :key="p.id" :value="p.id">{{ p.sku }} — {{ p.name }}</option>
        </select>
      </FormField>

      <FormField id="branch" :label="mode === 'transfer' ? 'From branch' : 'Branch'" :error="errors.branch_id" :hint="branchLocked ? 'Staff can only record stock for their own branch.' : ''" required v-slot="{ describedBy, invalid }">
        <select id="branch" v-model="form.branch_id" :disabled="branchLocked" :aria-describedby="describedBy" :aria-invalid="invalid">
          <option value="">Select a branch…</option>
          <option v-for="b in sourceBranches" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
      </FormField>

      <FormField v-if="mode === 'transfer'" id="to-branch" label="To branch" :error="errors.to_branch_id" required v-slot="{ describedBy, invalid }">
        <select id="to-branch" v-model="form.to_branch_id" :aria-describedby="describedBy" :aria-invalid="invalid">
          <option value="">Select a branch…</option>
          <option v-for="b in destinationBranches" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
      </FormField>

      <FormField v-else id="type" label="Movement type" :error="errors.type" required v-slot="{ describedBy }">
        <select id="type" v-model="form.type" :aria-describedby="describedBy">
          <option value="in">Stock in (delivery received)</option>
          <option value="out">Stock out (sale / dispatch)</option>
          <option value="adjustment">Adjustment (stocktake, damage)</option>
        </select>
      </FormField>

      <FormField id="quantity" label="Quantity" :error="errors.quantity" :hint="quantityHint" required v-slot="{ describedBy, invalid }">
        <input id="quantity" v-model="form.quantity" type="number" step="1" :min="mode === 'movement' && form.type === 'adjustment' ? undefined : 1" :aria-describedby="describedBy" :aria-invalid="invalid" />
      </FormField>

      <FormField v-if="mode === 'movement'" id="reference" label="Reference" :error="errors.reference" hint="Invoice, PO or receipt number." v-slot="{ describedBy }">
        <input id="reference" v-model.trim="form.reference" maxlength="100" :aria-describedby="describedBy" />
      </FormField>
    </div>

    <FormField id="note" label="Note" :error="errors.note" v-slot="{ describedBy }">
      <input id="note" v-model.trim="form.note" maxlength="255" :aria-describedby="describedBy" />
    </FormField>

    <div class="form-actions">
      <RouterLink to="/movements" class="btn btn-ghost">Cancel</RouterLink>
      <button class="btn btn-primary" type="submit" :disabled="saving">
        {{ saving ? 'Saving…' : mode === 'transfer' ? 'Transfer stock' : 'Save movement' }}
      </button>
    </div>
  </form>
</template>
