<script setup>
import CrudPage from '../../components/CrudPage.vue'

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'description', label: 'Description', class: 'hide-sm' },
  { key: 'products_count', label: 'Products', class: 'num' },
]
const fields = [
  { key: 'name', label: 'Name', required: true },
  { key: 'description', label: 'Description' },
]
</script>

<template>
  <CrudPage
    title="Categories"
    singular="Category"
    description="Group products for easier searching and reporting."
    endpoint="/categories"
    :columns="columns"
    :fields="fields"
  />
</template>
