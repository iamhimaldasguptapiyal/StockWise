<script setup>
import { onMounted, ref } from 'vue'
import http, { parseError } from '../api/http'
import { useLookupStore } from '../stores/lookups'
import { useToastStore } from '../stores/toast'
import AppIcon from './AppIcon.vue'
import FormField from './FormField.vue'
import ModalDialog from './ModalDialog.vue'

/**
 * Generic list + create/edit modal + delete for simple admin resources.
 *
 * columns: [{ key, label, value?: (row) => string, class? }]
 * fields:  [{ key, label, type?, required?, options?: () => [{ value, label }], hint?, createOnly?: boolean }]
 */
const props = defineProps({
  title: { type: String, required: true },
  singular: { type: String, required: true },
  description: { type: String, default: '' },
  endpoint: { type: String, required: true },
  columns: { type: Array, required: true },
  fields: { type: Array, required: true },
  canDelete: { type: Function, default: () => true },
})

const lookups = useLookupStore()
const toast = useToastStore()

const rows = ref([])
const loading = ref(true)
const error = ref('')
const editing = ref(null) // null = closed, {} = create, row = edit
const form = ref({})
const errors = ref({})
const formMessage = ref('')
const saving = ref(false)

async function load() {
  loading.value = true
  try {
    rows.value = (await http.get(props.endpoint)).data
  } catch (e) {
    error.value = parseError(e).message
  } finally {
    loading.value = false
  }
}

function open(row = {}) {
  editing.value = row
  errors.value = {}
  formMessage.value = ''
  form.value = Object.fromEntries(props.fields.map((f) => [f.key, row[f.key] ?? (f.default ?? '')]))
}

function close() {
  editing.value = null
}

function validate() {
  const e = {}
  for (const f of props.fields) {
    const isCreate = !editing.value?.id
    if (f.required && (isCreate || !f.createOnly) && String(form.value[f.key] ?? '').trim() === '') {
      e[f.key] = `${f.label} is required.`
    }
    if (f.type === 'email' && form.value[f.key] && !/^\S+@\S+\.\S+$/.test(form.value[f.key])) {
      e[f.key] = 'Enter a valid email address.'
    }
  }
  errors.value = e
  return !Object.keys(e).length
}

async function save() {
  formMessage.value = ''
  if (!validate()) return
  saving.value = true
  const payload = Object.fromEntries(Object.entries(form.value).map(([k, v]) => [k, v === '' ? null : v]))
  try {
    if (editing.value.id) {
      await http.put(`${props.endpoint}/${editing.value.id}`, payload)
      toast.success(`${props.singular} updated.`)
    } else {
      await http.post(props.endpoint, payload)
      toast.success(`${props.singular} created.`)
    }
    lookups.invalidate()
    close()
    load()
  } catch (e) {
    const parsed = parseError(e)
    errors.value = parsed.fields
    formMessage.value = parsed.message
  } finally {
    saving.value = false
  }
}

async function remove(row) {
  if (!confirm(`Delete ${props.singular.toLowerCase()} "${row.name}"?`)) return
  try {
    await http.delete(`${props.endpoint}/${row.id}`)
    toast.success(`${props.singular} deleted.`)
    lookups.invalidate()
    load()
  } catch (e) {
    toast.error(parseError(e).message)
  }
}

onMounted(load)
</script>

<template>
  <div class="page-header">
    <div>
      <h1>{{ title }}</h1>
      <p v-if="description" class="muted">{{ description }}</p>
    </div>
    <button class="btn btn-primary" type="button" @click="open()"><AppIcon name="plus" /> New {{ singular.toLowerCase() }}</button>
  </div>

  <div class="card">
    <p v-if="error" class="alert alert-error" role="alert">{{ error }}</p>
    <div class="table-wrap" :aria-busy="loading">
      <table>
        <thead>
          <tr>
            <th v-for="c in columns" :key="c.key" scope="col" :class="c.class">{{ c.label }}</th>
            <th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!loading && !rows.length">
            <td :colspan="columns.length + 1" class="muted center">Nothing here yet.</td>
          </tr>
          <tr v-for="row in rows" :key="row.id">
            <td v-for="c in columns" :key="c.key" :class="c.class">{{ c.value ? c.value(row) : (row[c.key] ?? '—') }}</td>
            <td class="actions">
              <button class="btn btn-ghost btn-sm" type="button" :aria-label="`Edit ${row.name}`" @click="open(row)"><AppIcon name="edit" /></button>
              <button v-if="canDelete(row)" class="btn btn-ghost btn-sm text-danger" type="button" :aria-label="`Delete ${row.name}`" @click="remove(row)"><AppIcon name="trash" /></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <ModalDialog v-if="editing" :title="editing.id ? `Edit ${singular.toLowerCase()}` : `New ${singular.toLowerCase()}`" @close="close">
    <form novalidate @submit.prevent="save">
      <p v-if="formMessage" class="alert alert-error" role="alert">{{ formMessage }}</p>
      <FormField
        v-for="f in fields"
        :key="f.key"
        :id="`f-${f.key}`"
        :label="f.label"
        :error="errors[f.key]"
        :hint="editing.id && f.createOnly ? 'Leave blank to keep the current value.' : f.hint"
        :required="f.required && (!editing.id || !f.createOnly)"
        v-slot="{ describedBy, invalid }"
      >
        <select v-if="f.options" :id="`f-${f.key}`" v-model="form[f.key]" :aria-describedby="describedBy" :aria-invalid="invalid">
          <option v-for="o in f.options()" :key="o.value" :value="o.value">{{ o.label }}</option>
        </select>
        <input
          v-else
          :id="`f-${f.key}`"
          v-model.trim="form[f.key]"
          :type="f.type || 'text'"
          :autocomplete="f.type === 'password' ? 'new-password' : 'off'"
          :aria-describedby="describedBy"
          :aria-invalid="invalid"
        />
      </FormField>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" @click="close">Cancel</button>
        <button type="submit" class="btn btn-primary" :disabled="saving">{{ saving ? 'Saving…' : 'Save' }}</button>
      </div>
    </form>
  </ModalDialog>
</template>
