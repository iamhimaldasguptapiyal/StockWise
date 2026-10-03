<script setup>
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import AppIcon from './AppIcon.vue'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const menuOpen = ref(false)

const mainLinks = [
  { to: '/', label: 'Dashboard', icon: 'home' },
  { to: '/products', label: 'Products', icon: 'box' },
  { to: '/stock', label: 'Stock levels', icon: 'layers' },
  { to: '/movements', label: 'Movements', icon: 'swap' },
]
const adminLinks = [
  { to: '/admin/categories', label: 'Categories', icon: 'tag' },
  { to: '/admin/suppliers', label: 'Suppliers', icon: 'truck' },
  { to: '/admin/branches', label: 'Branches', icon: 'store' },
  { to: '/admin/users', label: 'Users', icon: 'users' },
]

// Close the mobile menu after navigating.
watch(() => route.fullPath, () => (menuOpen.value = false))

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="shell">
    <header class="topbar">
      <RouterLink to="/" class="brand">
        <img src="/favicon.svg" alt="" width="28" height="28" />
        <span>StockWise</span>
      </RouterLink>
      <button
        class="btn btn-ghost menu-toggle"
        type="button"
        :aria-expanded="menuOpen"
        aria-controls="sidebar"
        @click="menuOpen = !menuOpen"
      >
        <AppIcon :name="menuOpen ? 'close' : 'menu'" />
        <span class="sr-only">Toggle navigation</span>
      </button>
    </header>

    <aside id="sidebar" class="sidebar" :class="{ open: menuOpen }">
      <RouterLink to="/" class="brand brand-desktop">
        <img src="/favicon.svg" alt="" width="30" height="30" />
        <span>StockWise</span>
      </RouterLink>

      <nav aria-label="Main">
        <ul class="nav-list">
          <li v-for="link in mainLinks" :key="link.to">
            <RouterLink :to="link.to" class="nav-link" :exact-active-class="link.to === '/' ? 'active' : ''" :active-class="link.to === '/' ? '' : 'active'">
              <AppIcon :name="link.icon" /> {{ link.label }}
            </RouterLink>
          </li>
        </ul>

        <template v-if="auth.isAdmin">
          <p class="nav-heading">Administration</p>
          <ul class="nav-list">
            <li v-for="link in adminLinks" :key="link.to">
              <RouterLink :to="link.to" class="nav-link" active-class="active">
                <AppIcon :name="link.icon" /> {{ link.label }}
              </RouterLink>
            </li>
          </ul>
        </template>
      </nav>

      <div class="user-card" v-if="auth.user">
        <div>
          <strong>{{ auth.user.name }}</strong>
          <span class="muted small">
            {{ auth.isAdmin ? 'Administrator' : 'Staff' }}<template v-if="auth.user.branch"> · {{ auth.user.branch.name }}</template>
          </span>
        </div>
        <button class="btn btn-ghost btn-sm" type="button" @click="logout">
          <AppIcon name="logout" /> Sign out
        </button>
      </div>
    </aside>

    <main id="main" class="content" tabindex="-1">
      <slot />
    </main>
  </div>
</template>
