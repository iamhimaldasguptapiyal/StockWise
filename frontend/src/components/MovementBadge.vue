<script setup>
import { computed } from 'vue'

const props = defineProps({ type: { type: String, required: true } })

const labels = {
  in: 'Stock in',
  out: 'Stock out',
  adjustment: 'Adjustment',
  transfer_in: 'Transfer in',
  transfer_out: 'Transfer out',
}
const tone = computed(() => (['in', 'transfer_in'].includes(props.type) ? 'success' : props.type === 'adjustment' ? 'neutral' : 'warning'))
</script>

<template>
  <span class="badge" :class="`badge-${tone}`">{{ labels[type] ?? type }}</span>
</template>
