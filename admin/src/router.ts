import { createRouter, createWebHistory } from 'vue-router'
import Onboarding from '@/pages/Onboarding.vue'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/',
    redirect: () => {
      const token = localStorage.getItem('access_token')
      return token ? '/crm' : '/onboarding'
    }
  },
  {
    path: '/onboarding',
    name: 'onboarding',
    component: Onboarding
  },
  {
    path: '/crm',
    name: 'crm',
    component: () => import('@/pages/CrmLeads.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/editor',
    name: 'editor',
    component: () => import('@/pages/ContentEditor.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/settings',
    name: 'settings',
    component: () => import('@/pages/Settings.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/pages/Login.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('access_token')
  if (to.meta.requiresAuth && !token) {
    next('/onboarding')
  } else {
    next()
  }
})

export default router
