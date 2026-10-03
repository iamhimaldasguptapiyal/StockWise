<script setup>
defineProps({ meta: { type: Object, required: true } })
defineEmits(['change'])
</script>

<template>
  <nav v-if="meta.last_page > 1" class="pagination" aria-label="Pagination">
    <span class="muted small">Showing {{ meta.from }}–{{ meta.to }} of {{ meta.total }}</span>
    <div class="pagination-buttons">
      <button class="btn btn-ghost btn-sm" type="button" :disabled="meta.current_page <= 1" @click="$emit('change', meta.current_page - 1)">
        Previous
      </button>
      <span class="small" aria-current="page">Page {{ meta.current_page }} of {{ meta.last_page }}</span>
      <button class="btn btn-ghost btn-sm" type="button" :disabled="meta.current_page >= meta.last_page" @click="$emit('change', meta.current_page + 1)">
        Next
      </button>
    </div>
  </nav>
</template>
