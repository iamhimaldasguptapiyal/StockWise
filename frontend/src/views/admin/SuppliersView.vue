<script setup>
import CrudPage from '../../components/CrudPage.vue'

const columns = [
  { key: 'name', label: 'Supplier' },
  { key: 'contact_name', label: 'Contact', class: 'hide-sm' },
  { key: 'email', label: 'Email', class: 'hide-sm' },
  { key: 'phone', label: 'Phone' },
  { key: 'products_count', label: 'Products', class: 'num' },
]
const fields = [
  { key: 'name', label: 'Supplier name', required: true },
  { key: 'contact_name', label: 'Contact person' },
  { key: 'email', label: 'Email', type: 'email' },
  { key: 'phone', label: 'Phone', type: 'tel' },
  { key: 'address', label: 'Address' },
]
</script>

<template>
  <CrudPage
    title="Suppliers"
    singular="Supplier"
    description="Vendors that products are purchased from."
    endpoint="/suppliers"
    :columns="columns"
    :fields="fields"
  />
</template>
