<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

defineProps({ title: { type: String, required: true } })
const emit = defineEmits(['close'])

const dialog = ref(null)
let previouslyFocused = null

function onKeydown(event) {
  if (event.key === 'Escape') emit('close')
}

// Move focus into the dialog on open and restore it on close (keyboard users).
onMounted(async () => {
  previouslyFocused = document.activeElement
  document.addEventListener('keydown', onKeydown)
  await nextTick()
  dialog.value?.querySelector('input, select, textarea, button')?.focus()
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  previouslyFocused?.focus?.()
})
</script>

<template>
  <div class="modal-backdrop" @click.self="emit('close')">
    <div ref="dialog" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
      <div class="modal-header">
        <h2 id="modal-title">{{ title }}</h2>
        <button type="button" class="btn btn-ghost btn-sm" aria-label="Close dialog" @click="emit('close')">×</button>
      </div>
      <slot />
    </div>
  </div>
</template>
