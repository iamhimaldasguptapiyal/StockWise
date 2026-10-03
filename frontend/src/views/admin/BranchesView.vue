<script setup>
import CrudPage from '../../components/CrudPage.vue'
import { formatNumber } from '../../utils/format'

const columns = [
  { key: 'code', label: 'Code' },
  { key: 'name', label: 'Branch' },
  { key: 'address', label: 'Address', class: 'hide-sm' },
  { key: 'users_count', label: 'Staff', class: 'num' },
  { key: 'total_units', label: 'Units', class: 'num', value: (row) => formatNumber(row.total_units) },
]
const fields = [
  { key: 'name', label: 'Branch name', required: true },
  { key: 'code', label: 'Short code', required: true, hint: 'Up to 10 letters/numbers, e.g. SYD' },
  { key: 'address', label: 'Address' },
  { key: 'phone', label: 'Phone', type: 'tel' },
]
</script>

<template>
  <CrudPage
    title="Branches"
    singular="Branch"
    description="Store locations that hold stock."
    endpoint="/branches"
    :columns="columns"
    :fields="fields"
  />
</template>
