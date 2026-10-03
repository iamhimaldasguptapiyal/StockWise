<script setup>
import { onMounted } from 'vue'
import CrudPage from '../../components/CrudPage.vue'
import { useAuthStore } from '../../stores/auth'
import { useLookupStore } from '../../stores/lookups'

const auth = useAuthStore()
const lookups = useLookupStore()

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'email', label: 'Email', class: 'hide-sm' },
  { key: 'role', label: 'Role', value: (row) => (row.role === 'admin' ? 'Administrator' : 'Staff') },
  { key: 'branch', label: 'Branch', value: (row) => row.branch?.name ?? '—' },
]
const fields = [
  { key: 'name', label: 'Full name', required: true },
  { key: 'email', label: 'Email', type: 'email', required: true },
  { key: 'password', label: 'Password', type: 'password', required: true, createOnly: true, hint: 'At least 8 characters with letters and numbers.' },
  {
    key: 'role',
    label: 'Role',
    required: true,
    default: 'staff',
    options: () => [
      { value: 'staff', label: 'Staff' },
      { value: 'admin', label: 'Administrator' },
    ],
  },
  {
    key: 'branch_id',
    label: 'Branch',
    options: () => [{ value: '', label: 'Not assigned' }, ...lookups.branches.map((b) => ({ value: b.id, label: b.name }))],
  },
]

onMounted(() => lookups.load())
</script>

<template>
  <CrudPage
    title="Users"
    singular="User"
    description="Manage who can sign in and what they can do."
    endpoint="/users"
    :columns="columns"
    :fields="fields"
    :can-delete="(row) => row.id !== auth.user?.id"
  />
</template>
