<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppLayout from './components/AppLayout.vue'
import ToastList from './components/ToastList.vue'

const route = useRoute()
const bare = computed(() => route.meta.guest || route.meta.public)
</script>

<template>
  <a class="skip-link" href="#main">Skip to main content</a>
  <RouterView v-if="bare" />
  <AppLayout v-else>
    <RouterView />
  </AppLayout>
  <ToastList />
</template>
