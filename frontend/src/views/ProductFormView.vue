<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import http, { parseError } from '../api/http'
import AppIcon from '../components/AppIcon.vue'
import FormField from '../components/FormField.vue'
import { useLookupStore } from '../stores/lookups'
import { useToastStore } from '../stores/toast'

const props = defineProps({ id: { type: String, default: null } })

const router = useRouter()
const lookups = useLookupStore()
const toast = useToastStore()

const isEdit = computed(() => !!props.id)
const form = reactive({
  sku: '',
  name: '',
  description: '',
  category_id: '',
  supplier_id: '',
  cost_price: '',
  sell_price: '',
  reorder_level: 10,
})
const errors = ref({})
const message = ref('')
const saving = ref(false)
const loading = ref(false)

const margin = computed(() => {
  const cost = Number(form.cost_price)
  const sell = Number(form.sell_price)
  return sell > 0 && cost >= 0 ? Math.round(((sell - cost) / sell) * 100) : null
})

onMounted(async () => {
  lookups.load()
  if (!isEdit.value) return
  loading.value = true
  try {
    const { data } = await http.get(`/products/${props.id}`)
    Object.keys(form).forEach((key) => (form[key] = data[key] ?? ''))
  } catch (e) {
    message.value = parseError(e).message
  } finally {
    loading.value = false
  }
})

function validate() {
  const e = {}
  if (!form.sku) e.sku = 'SKU is required.'
  else if (!/^[A-Za-z0-9-]+$/.test(form.sku)) e.sku = 'Use letters, numbers and dashes only.'
  if (!form.name) e.name = 'Name is required.'
  if (form.cost_price === '' || Number(form.cost_price) < 0) e.cost_price = 'Enter a cost of 0 or more.'
  if (form.sell_price === '' || Number(form.sell_price) < 0) e.sell_price = 'Enter a price of 0 or more.'
  else if (Number(form.sell_price) < Number(form.cost_price)) e.sell_price = 'Sell price should not be lower than cost.'
  if (form.reorder_level === '' || Number(form.reorder_level) < 0) e.reorder_level = 'Enter a reorder level of 0 or more.'
  errors.value = e
  return !Object.keys(e).length
}

async function submit() {
  message.value = ''
  if (!validate()) return
  saving.value = true
  const payload = { ...form, category_id: form.category_id || null, supplier_id: form.supplier_id || null }
  try {
    const { data } = isEdit.value
      ? await http.put(`/products/${props.id}`, payload)
      : await http.post('/products', payload)
    toast.success(isEdit.value ? 'Product updated.' : 'Product created.')
    router.push(`/products/${data.id}`)
  } catch (e) {
    const parsed = parseError(e)
    errors.value = parsed.fields
    message.value = parsed.message
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="page-header">
    <div>
      <RouterLink :to="isEdit ? `/products/${id}` : '/products'" class="back-link"><AppIcon name="arrowLeft" /> Back</RouterLink>
      <h1>{{ isEdit ? 'Edit product' : 'New product' }}</h1>
    </div>
  </div>

  <form class="card form-card" novalidate :aria-busy="loading" @submit.prevent="submit">
    <p v-if="message" class="alert alert-error" role="alert">{{ message }}</p>

    <div class="form-grid">
      <FormField id="sku" label="SKU" :error="errors.sku" hint="Unique stock-keeping code, e.g. HT-HAM-16" required v-slot="{ describedBy, invalid }">
        <input id="sku" v-model.trim="form.sku" maxlength="40" :aria-describedby="describedBy" :aria-invalid="invalid" />
      </FormField>
      <FormField id="name" label="Product name" :error="errors.name" required v-slot="{ describedBy, invalid }">
        <input id="name" v-model.trim="form.name" maxlength="255" :aria-describedby="describedBy" :aria-invalid="invalid" />
      </FormField>
      <FormField id="category" label="Category" :error="errors.category_id" v-slot="{ describedBy }">
        <select id="category" v-model="form.category_id" :aria-describedby="describedBy">
          <option value="">Uncategorised</option>
          <option v-for="c in lookups.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </FormField>
      <FormField id="supplier" label="Supplier" :error="errors.supplier_id" v-slot="{ describedBy }">
        <select id="supplier" v-model="form.supplier_id" :aria-describedby="describedBy">
          <option value="">No supplier</option>
          <option v-for="s in lookups.suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </FormField>
      <FormField id="cost_price" label="Cost price (AUD)" :error="errors.cost_price" required v-slot="{ describedBy, invalid }">
        <input id="cost_price" v-model="form.cost_price" type="number" min="0" step="0.01" inputmode="decimal" :aria-describedby="describedBy" :aria-invalid="invalid" />
      </FormField>
      <FormField id="sell_price" label="Sell price (AUD)" :error="errors.sell_price" :hint="margin !== null ? `Gross margin: ${margin}%` : ''" required v-slot="{ describedBy, invalid }">
        <input id="sell_price" v-model="form.sell_price" type="number" min="0" step="0.01" inputmode="decimal" :aria-describedby="describedBy" :aria-invalid="invalid" />
      </FormField>
      <FormField id="reorder_level" label="Reorder level" :error="errors.reorder_level" hint="A branch is flagged low when its stock falls to this level." required v-slot="{ describedBy, invalid }">
        <input id="reorder_level" v-model="form.reorder_level" type="number" min="0" step="1" :aria-describedby="describedBy" :aria-invalid="invalid" />
      </FormField>
    </div>

    <FormField id="description" label="Description" :error="errors.description" v-slot="{ describedBy }">
      <textarea id="description" v-model="form.description" rows="3" maxlength="2000" :aria-describedby="describedBy"></textarea>
    </FormField>

    <div class="form-actions">
      <RouterLink :to="isEdit ? `/products/${id}` : '/products'" class="btn btn-ghost">Cancel</RouterLink>
      <button class="btn btn-primary" type="submit" :disabled="saving">{{ saving ? 'Saving…' : 'Save product' }}</button>
    </div>
  </form>
</template>
