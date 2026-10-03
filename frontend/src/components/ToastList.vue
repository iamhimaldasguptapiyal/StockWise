<script setup>
import { useToastStore } from '../stores/toast'

const toast = useToastStore()
</script>

<template>
  <div class="toasts" role="status" aria-live="polite">
    <div v-for="t in toast.toasts" :key="t.id" class="toast" :class="`toast-${t.type}`">
      <span>{{ t.message }}</span>
      <button type="button" class="toast-close" aria-label="Dismiss notification" @click="toast.dismiss(t.id)">×</button>
    </div>
  </div>
</template>
