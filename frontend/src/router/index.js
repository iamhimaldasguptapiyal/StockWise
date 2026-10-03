import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  { path: '/login', name: 'login', component: () => import('../views/LoginView.vue'), meta: { guest: true, title: 'Sign in' } },
  { path: '/register', name: 'register', component: () => import('../views/RegisterView.vue'), meta: { guest: true, title: 'Create account' } },

  { path: '/', name: 'dashboard', component: () => import('../views/DashboardView.vue'), meta: { title: 'Dashboard' } },
  { path: '/products', name: 'products', component: () => import('../views/ProductsView.vue'), meta: { title: 'Products' } },
  { path: '/products/new', name: 'product-create', component: () => import('../views/ProductFormView.vue'), meta: { admin: true, title: 'New product' } },
  { path: '/products/:id', name: 'product-detail', component: () => import('../views/ProductDetailView.vue'), props: true, meta: { title: 'Product' } },
  { path: '/products/:id/edit', name: 'product-edit', component: () => import('../views/ProductFormView.vue'), props: true, meta: { admin: true, title: 'Edit product' } },
  { path: '/stock', name: 'stock', component: () => import('../views/StockLevelsView.vue'), meta: { title: 'Stock levels' } },
  { path: '/movements', name: 'movements', component: () => import('../views/MovementsView.vue'), meta: { title: 'Stock movements' } },
  { path: '/movements/new', name: 'movement-create', component: () => import('../views/RecordMovementView.vue'), meta: { title: 'Record movement' } },

  { path: '/admin/categories', name: 'categories', component: () => import('../views/admin/CategoriesView.vue'), meta: { admin: true, title: 'Categories' } },
  { path: '/admin/suppliers', name: 'suppliers', component: () => import('../views/admin/SuppliersView.vue'), meta: { admin: true, title: 'Suppliers' } },
  { path: '/admin/branches', name: 'branches', component: () => import('../views/admin/BranchesView.vue'), meta: { admin: true, title: 'Branches' } },
  { path: '/admin/users', name: 'users', component: () => import('../views/admin/UsersView.vue'), meta: { admin: true, title: 'Users' } },

  { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('../views/NotFoundView.vue'), meta: { public: true, title: 'Not found' } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  await auth.fetchUser()

  if (to.meta.public) return true
  if (to.meta.guest) return auth.isAuthenticated ? { name: 'dashboard' } : true
  if (!auth.isAuthenticated) return { name: 'login', query: { redirect: to.fullPath } }
  if (to.meta.admin && !auth.isAdmin) return { name: 'dashboard' }
  return true
})

router.afterEach((to) => {
  document.title = `${to.meta.title ?? 'StockWise'} · StockWise`
})

export default router
