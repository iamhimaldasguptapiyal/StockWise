<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { parseError } from '../api/http'
import FormField from '../components/FormField.vue'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const form = reactive({ email: '', password: '' })
const errors = ref({})
const message = ref(route.query.expired ? 'Your session has expired. Please sign in again.' : '')
const submitting = ref(false)

async function submit() {
  errors.value = {}
  message.value = ''
  submitting.value = true
  try {
    await auth.login(form)
    router.push(route.query.redirect || '/')
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
          <p class="muted">Inventory across every branch, in one place.</p>
        </div>
      </div>

      <h2>Sign in</h2>
      <p v-if="message" class="alert alert-error" role="alert">{{ message }}</p>

      <form novalidate @submit.prevent="submit">
        <FormField id="email" label="Email" :error="errors.email" required v-slot="{ describedBy, invalid }">
          <input id="email" v-model.trim="form.email" type="email" autocomplete="email" required :aria-describedby="describedBy" :aria-invalid="invalid" />
        </FormField>
        <FormField id="password" label="Password" :error="errors.password" required v-slot="{ describedBy, invalid }">
          <input id="password" v-model="form.password" type="password" autocomplete="current-password" required :aria-describedby="describedBy" :aria-invalid="invalid" />
        </FormField>
        <button class="btn btn-primary btn-block" type="submit" :disabled="submitting">
          {{ submitting ? 'Signing in…' : 'Sign in' }}
        </button>
      </form>

      <p class="muted small center">No account? <RouterLink to="/register">Create one</RouterLink></p>

      <details class="demo-creds">
        <summary>Demo accounts</summary>
        <p class="small">Admin: <code>admin@stockwise.test</code><br />Staff: <code>staff@stockwise.test</code><br />Password: <code>Password123</code></p>
      </details>
    </div>
  </main>
</template>
