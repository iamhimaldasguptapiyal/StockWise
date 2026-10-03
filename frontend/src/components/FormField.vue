<script setup>
// Label + control + error message wired together with ids for screen readers.
defineProps({
  id: { type: String, required: true },
  label: { type: String, required: true },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  required: { type: Boolean, default: false },
})
</script>

<template>
  <div class="field" :class="{ 'has-error': error }">
    <label :for="id">
      {{ label }}<span v-if="required" class="required" aria-hidden="true"> *</span>
    </label>
    <slot :describedBy="[hint && `${id}-hint`, error && `${id}-error`].filter(Boolean).join(' ') || undefined" :invalid="!!error" />
    <p v-if="hint" :id="`${id}-hint`" class="hint">{{ hint }}</p>
    <p v-if="error" :id="`${id}-error`" class="error-text" role="alert">{{ error }}</p>
  </div>
</template>
