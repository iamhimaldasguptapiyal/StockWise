<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import http, { parseError } from '../api/http'
import FormField from '../components/FormField.vue'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

const auth = useAuthStore()
const toast = useToastStore()
const router = useRouter()

const form = reactive({ name: '', email: '', password: '', password_confirmation: '', branch_id: '' })
const branches = ref([])
const errors = ref({})
const message = ref('')
const submitting = ref(false)

onMounted(async () => {
  try {
    branches.value = (await http.get('/branches/options')).data
  } catch {
    // Branch is optional; the form still works without the list.
  }
})

// Client-side checks mirror the API rules for instant feedback; the API remains the source of truth.
function validate() {
  const e = {}
  if (!form.name) e.name = 'Please enter your name.'
  if (!/^\S+@\S+\.\S+$/.test(form.email)) e.email = 'Please enter a valid email address.'
  if (form.password.length < 8 || !/[A-Za-z]/.test(form.password) || !/\d/.test(form.password)) {
    e.password = 'Use at least 8 characters with letters and numbers.'
  }
  if (form.password !== form.password_confirmation) e.password_confirmation = 'Passwords do not match.'
  errors.value = e
  return !Object.keys(e).length
}

async function submit() {
  message.value = ''
  if (!validate()) return
  submitting.value = true
  try {
    await auth.register({ ...form, branch_id: form.branch_id || null })
    toast.success('Welcome to StockWise!')
    router.push('/')
  } catch (error) {
    const parsed = parseError(error)
    errors.value = parsed.fields
    if (!Object.keys(parsed.fields).length) message.value = parsed.message
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <main id="main" class="auth-page">
    <div class="auth-card card">
      <div class="auth-brand">
        <img src="/favicon.svg" alt="" width="40" height="40" />
        <div>
          <h1>StockWise</h1>
          <p class="muted">Create a staff account</p>
        </div>
      </div>

      <p v-if="message" class="alert alert-error" role="alert">{{ message }}</p>

      <form novalidate @submit.prevent="submit">
        <FormField id="name" label="Full name" :error="errors.name" required v-slot="{ describedBy, invalid }">
          <input id="name" v-model.trim="form.name" autocomplete="name" :aria-describedby="describedBy" :aria-invalid="invalid" />
        </FormField>
        <FormField id="email" label="Email" :error="errors.email" required v-slot="{ describedBy, invalid }">
          <input id="email" v-model.trim="form.email" type="email" autocomplete="email" :aria-describedby="describedBy" :aria-invalid="invalid" />
        </FormField>
        <FormField id="branch" label="Home branch" :error="errors.branch_id" v-slot="{ describedBy }">
          <select id="branch" v-model="form.branch_id" :aria-describedby="describedBy">
            <option value="">Not assigned yet</option>
            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </FormField>
        <FormField id="password" label="Password" :error="errors.password" hint="At least 8 characters, including letters and numbers." required v-slot="{ describedBy, invalid }">
          <input id="password" v-model="form.password" type="password" autocomplete="new-password" :aria-describedby="describedBy" :aria-invalid="invalid" />
        </FormField>
        <FormField id="password_confirmation" label="Confirm password" :error="errors.password_confirmation" required v-slot="{ describedBy, invalid }">
          <input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" :aria-describedby="describedBy" :aria-invalid="invalid" />
        </FormField>
        <button class="btn btn-primary btn-block" type="submit" :disabled="submitting">
          {{ submitting ? 'Creating account…' : 'Create account' }}
        </button>
      </form>

      <p class="muted small center">Already registered? <RouterLink to="/login">Sign in</RouterLink></p>
    </div>
  </main>
</template>
